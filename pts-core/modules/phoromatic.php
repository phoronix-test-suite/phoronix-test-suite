<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2010, Phoronix Media
	Copyright (C) 2009 - 2010, Michael Larabel

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

class phoromatic extends pts_module_interface
{
	const module_name = "Phoromatic Client";
	const module_version = "0.6.1";
	const module_description = "The Phoromatic client is used for connecting to a Phoromatic server (Phoromatic.com or a locally run server) to facilitate the automatic running of tests, generally across multiple test nodes in a routine manner. For more details visit http://www.phoromatic.com/";
	const module_author = "Phoronix Media";

	static $phoromatic_server_build = false;

	static $phoromatic_host = null;
	static $phoromatic_account = null;
	static $phoromatic_verifier = null;
	static $phoromatic_system = null;

	public static function module_info()
	{
		return "The Phoromatic module contains the client support for interacting with Phoromatic and Phoromatic Tracker services. A public, free reference implementation of Phoromatic can be found at http://www.phoromatic.com/. A commercial version is available to enterprise customers for installation onto their intranet. For more information, contact Phoronix Media.";
	}
	public static function module_setup()
	{
		return array(
		new pts_module_option("remote_host", "Enter the URL to host", "HTTP_URL", "http://www.phoromatic.com/"),
		new pts_module_option("remote_account", "Enter the account code", "ALPHA_NUMERIC"),
		new pts_module_option("remote_verifier", "Enter the verification code", "ALPHA_NUMERIC"),
		new pts_module_option("system_description", "Enter a short (optional) description for this system", null, null, null, false)
		);
	}
	public static function module_setup_validate($options)
	{
		if(substr($options["remote_host"], -14) != "phoromatic.php")
		{
			$options["remote_host"] = pts_strings::add_trailing_slash($options["remote_host"]) . "phoromatic.php";
		}

		$server_response = phoromatic::upload_to_remote_server(array(
			"r" => "setup",
			"h" => phodevi::system_hardware(true),
			"s" => phodevi::system_software(true),
			"o" => phodevi::read_property("system", "hostname"),
			"sys_desc" => $options["system_description"]
			),
			$options["remote_host"], $options["remote_account"], $options["remote_verifier"]);

		$returned_id = self::read_xml_value($server_response, M_PHOROMATIC_GEN_RESPONSE);

		unset($options["system_description"]); // No reason to have this locally just pass it to the server

		if(!empty($returned_id))
		{
			$options["remote_system"] = $returned_id;
			echo "\nRun Phoromatic by entering: phoronix-test-suite phoromatic.start\n";
		}
		else
		{
			echo "\nConfiguration Failed!\n\n";
			$options = array();
		}

		return $options;
	}
	public static function user_commands()
	{
		return array(
			"start" => "user_start",
			"user_system_return" => "user_system_return",
			"upload_results" => "upload_unscheduled_results",
			"clone_results" => "clone_results",
			"system_schedule" => "system_schedule",
			"system_schedule_today" => "system_schedule_today",
			"send_message" => "report_message_to_server"
			);
	}

	//
	// User Run Commands
	//

	public static function user_start()
	{
		if(pts_client::create_lock(PTS_USER_DIR . "phoromatic_lock") == false)
		{
			pts_client::$display->generic_error("Phoromatic is already running.");
			return false;
		}
		if(!phoromatic::phoromatic_setup_module())
		{
			return false;
		}

		phoromatic::user_system_process();
	}
	public static function upload_unscheduled_results($to_upload)
	{
		if(!phoromatic::phoromatic_setup_module())
		{
			return false;
		}

		if(!isset($to_upload[0]) || !pts_is_test_result($to_upload[0]))
		{
			echo "\nNo test result file was found to upload.\n";
			return false;
		}

		pts_set_assignment("PHOROMATIC_UPLOAD_TEST_LOGS", pts_user_io::prompt_bool_input("Would you like to upload the test logs", false));
		pts_set_assignment("PHOROMATIC_UPLOAD_SYSTEM_LOGS", pts_user_io::prompt_bool_input("Would you like to upload the system logs", false));

		phoromatic::upload_unscheduled_test_results($to_upload[0]);
	}
	public static function clone_results($to_clone)
	{
		if(!phoromatic::phoromatic_setup_module())
		{
			return false;
		}

		if(!isset($to_clone[0]) || empty($to_clone[0]))
		{
			echo "\nNo clone string was provided.\n";
			return false;
		}

		$server_response = phoromatic::upload_to_remote_server(array(
			"r" => "clone_test_results",
			"i" => $to_clone[0]
			));

		switch(self::read_xml_value($server_response, M_PHOROMATIC_GEN_RESPONSE))
		{
			case M_PHOROMATIC_RESPONSE_TRUE:
				$identifier = "phoromatic-clone-" . str_replace(array('_', ':'), null, $to_clone[0]);
				pts_client::save_test_result($identifier . "/composite.xml", $server_response); // TODO: regenerate the XML so that the Phoromatic response bits are not included
				echo "\nResult Saved To: " . SAVE_RESULTS_DIR . $identifier . "/composite.xml\n\n";
				pts_set_assignment_next("PREV_SAVE_RESULTS_IDENTIFIER", $identifier);
				pts_client::display_web_page(SAVE_RESULTS_DIR . $identifier . "/index.html");
				break;
			case M_PHOROMATIC_RESPONSE_SETTING_DISABLED:
				echo "\nYou need to enable this support from your Phoromatic account web interface.\n";
				break;
			default:
			case M_PHOROMATIC_RESPONSE_ERROR:
				echo "\nAn Error Occurred.\n";
				break;
		}
	}
	public static function system_schedule()
	{
		if(!phoromatic::phoromatic_setup_module())
		{
			return false;
		}

		$server_response = phoromatic::upload_to_remote_server(array(
			"r" => "system_schedule"
			));

		$schedule_xml = new tandem_XmlReader($server_response);
		$schedule_titles = $schedule_xml->getXmlArrayValues(M_PHOROMATIC_SCHEDULE_TEST_TITLE);
		$schedule_description = $schedule_xml->getXmlArrayValues(M_PHOROMATIC_SCHEDULE_TEST_DESCRIPTION);
		$schedule_active_on = $schedule_xml->getXmlArrayValues(M_PHOROMATIC_SCHEDULE_TEST_ACTIVE_ON);
		$schedule_start_time = $schedule_xml->getXmlArrayValues(M_PHOROMATIC_SCHEDULE_TEST_START);

		if(count($schedule_titles) == 0)
		{
			echo "\nNo test schedules for this system were found on the Phoromatic Server.\n";
		}
		else
		{
			for($i = 0; $i < count($schedule_titles); $i++)
			{
				echo self::phoromatic_schedule_entry_string($schedule_titles[$i], $schedule_description[$i], $schedule_start_time[$i], $schedule_active_on[$i]);
			}
		}

		echo "\n";
	}
	public static function system_schedule_today()
	{
		if(!phoromatic::phoromatic_setup_module())
		{
			return false;
		}

		$server_response = phoromatic::upload_to_remote_server(array(
			"r" => "system_schedule"
			));

		$schedule_xml = new tandem_XmlReader($server_response);
		$schedule_titles = $schedule_xml->getXmlArrayValues(M_PHOROMATIC_SCHEDULE_TEST_TITLE);
		$schedule_description = $schedule_xml->getXmlArrayValues(M_PHOROMATIC_SCHEDULE_TEST_DESCRIPTION);
		$schedule_active_on = $schedule_xml->getXmlArrayValues(M_PHOROMATIC_SCHEDULE_TEST_ACTIVE_ON);
		$schedule_start_time = $schedule_xml->getXmlArrayValues(M_PHOROMATIC_SCHEDULE_TEST_START);

		if(count($schedule_titles) == 0)
		{
			echo "\nNo test schedules for this system were found on the Phoromatic Server.\n";
		}
		else
		{
			for($i = 0; $i < count($schedule_titles); $i++)
			{
				if($schedule_active_on[$i][(date('w'))] != 1)
				{
					continue;
				}

				echo self::phoromatic_schedule_entry_string($schedule_titles[$i], $schedule_description[$i], $schedule_start_time[$i], $schedule_active_on[$i]);
			}
		}

		echo "\n";
	}
	public static function send_message_to_server($msg)
	{
		if(!phoromatic::phoromatic_setup_module())
		{
			return false;
		}

		if(empty($msg))
		{
			echo "\nPass the message as the first argument.\n";
			return false;
		}

		if(self::report_warning_to_phoromatic("MESSAGE: " . implode(' ', $msg)))
		{
			echo "\nMessage Sent To Phoromatic Server.\n";
		}
		else
		{
			echo "\nMessage Failed To Send.\n";
		}
	}

	//
	// Core Functions
	//

	public static function user_system_return($tests)
	{
		// Upload result here
		foreach($tests as $test)
		{
			if(is_file(XML_SUITE_LOCAL_DIR . $test . ".xml"))
			{
				// Remove old suite files
				unlink(XML_SUITE_LOCAL_DIR . $test . ".xml");
			}
		}

		if(($save_identifier = pts_read_assignment("PREV_SAVE_RESULTS_IDENTIFIER")) != false)
		{
			// Upload test results

			if(is_file(SAVE_RESULTS_DIR . $save_identifier . "/composite.xml"))
			{
				phoromatic::update_system_status("Uploading Test Results");

				$times_tried = 0;
				do
				{
					if($times_tried > 0)
					{
						echo "\nConnection to server failed. Trying again in 60 seconds...\n";
						sleep(60);
					}

					$uploaded_test_results = phoromatic::upload_test_results($save_identifier);
					$times_tried++;
				}
				while(!$uploaded_test_results && $times_tried < 5);

				if(!$uploaded_test_results)
				{
					echo "Server connection failed. Exiting...\n";
					return false;
				}

				if(!pts_read_assignment("PHOROMATIC_ARCHIVE_RESULTS"))
				{
					pts_client::remove_saved_result_file($save_identifier);
				}
			}
		}

		phoromatic::user_system_process();
	}
	public static function user_system_process()
	{
		$last_communication_minute = date("i");
		$communication_attempts = 0;
		static $current_hw = null;
		static $current_sw = null;

		if(define("PHOROMATIC_START", true))
		{
			echo "\nRegistering Status With Phoromatic Server @ " . date("H:i:s") . "\n";

			$times_tried = 0;
			do
			{
				if($times_tried > 0)
				{
					echo "\nConnection to server failed. Trying again in 60 seconds...\n";
					sleep(60);
				}

				$update_sd = phoromatic::update_system_details();
				$times_tried++;
			}
			while(!$update_sd && $times_tried < 5);

			if(!$update_sd)
			{
				echo "Server connection still failed. Exiting...\n";
				return false;
			}

			$current_hw = phodevi::system_hardware(true);
			$current_sw = phodevi::system_software(true);

			echo "\nIdling 30 seconds for system to settle...\n";
			sleep(30);
		}

		do
		{
			$exit_loop = false;
			echo "\nChecking Status From Phoromatic Server @ " . date("H:i:s");

			if($last_communication_minute == date('i') && $communication_attempts > 2)
			{
				// Something is wrong, Phoromatic shouldn't be communicating with server more than three times a minute
				$response = M_PHOROMATIC_RESPONSE_IDLE;
			}
			else
			{
				$server_response = phoromatic::upload_to_remote_server(array("r" => "status_check"));

				$xml_parser = new tandem_XmlReader($server_response);
				$response = $xml_parser->getXMLValue(M_PHOROMATIC_GEN_RESPONSE);

				if(date("i") != $last_communication_minute)
				{
					$last_communication_minute = date("i");
					$communication_attempts = 0;
				}

				$communication_attempts++;
			}

			echo " [" . $response . "]\n";

			switch($response)
			{
				case M_PHOROMATIC_RESPONSE_RUN_TEST:
					$test_args = array("AUTOMATED_MODE" => true);

					do
					{
						$suite_identifier = "phoromatic-" . rand(1000, 9999);
					}
					while(is_file(XML_SUITE_LOCAL_DIR . $suite_identifier . ".xml"));

					$test_args["AUTO_SAVE_NAME"] = date("Y-m-d H:i:s");
					$test_args["PHOROMATIC_TITLE"] = $xml_parser->getXMLValue(P_SUITE_TITLE);
					$test_args["PHOROMATIC_SCHEDULE_ID"] = $xml_parser->getXMLValue(M_PHOROMATIC_ID);
					$test_args["AUTO_TEST_RESULTS_IDENTIFIER"] = $xml_parser->getXMLValue(M_PHOROMATIC_SYS_NAME);
					$test_args["PHOROMATIC_UPLOAD_TEST_LOGS"] = pts_strings::string_bool($xml_parser->getXMLValue(M_PHOROMATIC_UPLOAD_TEST_LOGS));
					$test_args["PHOROMATIC_UPLOAD_SYSTEM_LOGS"] = pts_strings::string_bool($xml_parser->getXMLValue(M_PHOROMATIC_UPLOAD_SYSTEM_LOGS));
					$test_args["PHOROMATIC_TRIGGER"] = $xml_parser->getXMLValue(M_PHOROMATIC_TRIGGER);

					if(pts_strings::string_bool($xml_parser->getXMLValue(M_PHOROMATIC_UPLOAD_TO_GLOBAL, "FALSE")))
					{
						$test_args["AUTO_UPLOAD_TO_GLOBAL"] = true;
					}

					if(pts_strings::string_bool($xml_parser->getXMLValue(M_PHOROMATIC_ARCHIVE_RESULTS_LOCALLY, M_PHOROMATIC_RESPONSE_TRUE)))
					{
						$test_args["PHOROMATIC_ARCHIVE_RESULTS"] = true;
					}

					file_put_contents(XML_SUITE_LOCAL_DIR . $suite_identifier . ".xml", $server_response);

					if(pts_strings::string_bool($xml_parser->getXMLValue(M_PHOROMATIC_RUN_INSTALL_COMMAND, M_PHOROMATIC_RESPONSE_TRUE)))
					{
						phoromatic::set_user_context($xml_parser->getXMLValue(M_PHOROMATIC_SET_CONTEXT_PRE_INSTALL), $test_args["PHOROMATIC_TRIGGER"], $test_args["PHOROMATIC_SCHEDULE_ID"], "INSTALL");
						pts_client::run_next("install_test", $suite_identifier, array("AUTOMATED_MODE" => true));
					}

					phoromatic::set_user_context($xml_parser->getXMLValue(M_PHOROMATIC_SET_CONTEXT_PRE_RUN), $test_args["PHOROMATIC_TRIGGER"], $test_args["PHOROMATIC_SCHEDULE_ID"], "INSTALL");
					pts_client::run_next("run_test", $suite_identifier, $test_args);
					pts_client::run_next("phoromatic.user_system_return", $suite_identifier, $test_args);
					$exit_loop = true;
					break;
				case M_PHOROMATIC_RESPONSE_EXIT:
					echo "\nPhoromatic received a remote command to exit.\n";
					phoromatic::update_system_status("Exiting Phoromatic");
					pts_client::release_lock(PTS_USER_DIR . "phoromatic_lock");
					$exit_loop = true;
					break;
				case M_PHOROMATIC_RESPONSE_SERVER_MAINTENANCE:
					// The Phoromatic server is down for maintenance, so don't bother updating system status and wait longer before checking back
					echo "\nThe Phoromatic server is currently down for maintenance. Waiting for service to be restored.\n";
					sleep((15 - (date("i") % 15)) * 60);
					break;
				case M_PHOROMATIC_RESPONSE_SHUTDOWN:
					echo "\nShutting down the system.\n";
					$exit_loop = true;
					shell_exec("poweroff"); // Currently assuming root
					break;
				case M_PHOROMATIC_RESPONSE_REBOOT:
					echo "\nRebooting the system.\n";
					$exit_loop = true;
					shell_exec("reboot"); // Currently assuming root
					break;
				case M_PHOROMATIC_RESPONSE_IDLE:
				default:
					phoromatic::update_system_status("Idling, Waiting For Task");
					sleep((10 - (date("i") % 10)) * 60); // Check with server every 10 minutes
					break;
			}

			if(phodevi::system_hardware(true) != $current_hw || phodevi::system_software(true) != $current_sw)
			{
				// Hardware and/or software has changed while PTS/Phoromatic has been running, update the Phoromatic Server
				echo "Updating Installed Hardware / Software With Phoromatic Server\n";
				phoromatic::update_system_details();
				$current_hw = phodevi::system_hardware(true);
				$current_sw = phodevi::system_software(true);
			}
		}
		while($exit_loop == false);

		phoromatic::update_system_status("Offline");
	}

	//
	// Process Functions
	//


	public static function __pre_test_install($test_identifier)
	{
		if(!pts_read_assignment("PHOROMATIC_TITLE"))
		{
			return false;
		}

		static $last_update_time = 0;

		if(time() > ($last_update_time + 600))
		{
			phoromatic::update_system_status("Installing Tests");
			$last_update_time = time();
		}
	}
	public static function __pre_test_run($pts_test_result)
	{
		if(!pts_read_assignment("PHOROMATIC_TITLE"))
		{
			return false;
		}

		phoromatic::update_system_status("Running " . $pts_test_result->get_test_profile()->get_identifier() . " For " . pts_read_assignment("PHOROMATIC_TITLE"));
	}
	public static function __event_user_error($user_error)
	{
		// Report PTS user error warnings to Phoromatic server
		phoromatic::report_warning_to_phoromatic($user_error->get_error_string());
	}
	public static function __event_results_saved($test_run_manager)
	{
		if(pts_module::read_variable("AUTO_UPLOAD_RESULTS_TO_PHOROMATIC") && !pts_read_assignment("PHOROMATIC_TITLE") && pts_module::is_module_setup())
		{
			pts_set_assignment("PHOROMATIC_UPLOAD_TEST_LOGS", true);
			pts_set_assignment("PHOROMATIC_UPLOAD_SYSTEM_LOGS", true);

			phoromatic::upload_unscheduled_test_results($test_run_manager->get_file_name());
		}
	}

	//
	// Other Functions
	//

	protected static function read_xml_value($file, $xml_option)
	{
	 	$xml_parser = new tandem_XmlReader($file);
		return $xml_parser->getXMLValue($xml_option);
	}
	private static function set_user_context($context_script, $trigger, $schedule_id, $process)
	{
		if(!empty($context_script))
		{
			if(!is_executable($context_script))
			{
				if(($context_script = pts_client::executable_in_path($context_script)) == false || !is_executable($context_script))
				{
					return false;
				}
			}

			$storage_path = pts_module::save_dir() . "memory.pt2so";
			$storage_object = pts_storage_object::recover_from_file($storage_path);

			// We check to see if the context was already set but the system rebooted or something in that script
			if($storage_object == false)
			{
				$storage_object = new pts_storage_object(true, true);
			}
			else if($storage_object->read_object("last_set_context_trigger") == $trigger && $storage_object->read_object("last_set_context_schedule") == $schedule_id && $storage_object->read_object("last_set_context_process") == $process)
			{
				// If the script already ran once for this trigger, don't run it again
				return false;
			}

			$storage_object->add_object("last_set_context_trigger", $trigger);
			$storage_object->add_object("last_set_context_schedule", $schedule_id);
			$storage_object->add_object("last_set_context_process", $process);
			$storage_object->save_to_file($storage_path);

			// Run the set context script
			exec($context_script . ' ' . $trigger);

			// Just simply return true for now, perhaps check exit code status and do something
			return true;
		}

		return false;
	}
	protected static function update_system_details()
	{
		$server_response = phoromatic::upload_to_remote_server(array("r" => "update_system_details", "h" => phodevi::system_hardware(true), "s" => phodevi::system_software(true)));
		self::$phoromatic_server_build = self::read_xml_value($server_response, M_PHOROMATIC_SERVER_BUILD);

		return self::read_xml_value($server_response, M_PHOROMATIC_GEN_RESPONSE) == M_PHOROMATIC_RESPONSE_TRUE;
	}
	protected static function update_system_status($current_task)
	{
		$server_response = phoromatic::upload_to_remote_server(array("r" => "update_system_status", "a" => $current_task, "time" => round(pts_read_assignment("EST_TIME_REMAINING") / 60)));

		return self::read_xml_value($server_response, M_PHOROMATIC_GEN_RESPONSE) == M_PHOROMATIC_RESPONSE_TRUE;
	}
	protected static function report_warning_to_phoromatic($warning)
	{
		$server_response = phoromatic::upload_to_remote_server(array("r" => "report_pts_warning", "a" => $warning));

		return self::read_xml_value($server_response, M_PHOROMATIC_GEN_RESPONSE) == M_PHOROMATIC_RESPONSE_TRUE;
	}
	private static function capture_test_logs($save_identifier)
	{
		$data = array("system-logs" => null, "test-logs" => null);

		if(is_dir(SAVE_RESULTS_DIR . $save_identifier . "/system-logs/") && pts_read_assignment("PHOROMATIC_UPLOAD_SYSTEM_LOGS"))
		{
			$system_logs_zip = pts_client::create_temporary_file();
			pts_compression::zip_archive_create($system_logs_zip, SAVE_RESULTS_DIR . $save_identifier . "/system-logs/");
			$data["system-logs"] = base64_encode(file_get_contents($system_logs_zip));
			unlink($system_logs_zip);
		}

		if(is_dir(SAVE_RESULTS_DIR . $save_identifier . "/test-logs/") && pts_read_assignment("PHOROMATIC_UPLOAD_TEST_LOGS"))
		{
			$test_logs_zip = pts_client::create_temporary_file();
			pts_compression::zip_archive_create($test_logs_zip, SAVE_RESULTS_DIR . $save_identifier . "/test-logs/");
			$data["test-logs"] = base64_encode(file_get_contents($test_logs_zip));
			unlink($test_logs_zip);
		}

		return $data;
	}
	protected static function upload_test_results($save_identifier)
	{
		$composite_xml = file_get_contents(SAVE_RESULTS_DIR . $save_identifier . "/composite.xml");

		$logs = self::capture_test_logs($save_identifier);
		$server_response = phoromatic::upload_to_remote_server(array(
			"r" => "upload_test_results",
			"c" => $composite_xml,
			"i" => pts_read_assignment("PHOROMATIC_SCHEDULE_ID"),
			"ti" => pts_read_assignment("AUTO_TEST_RESULTS_IDENTIFIER"),
			"ts" => pts_read_assignment("PHOROMATIC_TRIGGER"),
			"sl" => $logs["system-logs"],
			"tl" => $logs["test-logs"]
			));

		return self::read_xml_value($server_response, M_PHOROMATIC_GEN_RESPONSE) == M_PHOROMATIC_RESPONSE_TRUE;
	}
	protected static function upload_unscheduled_test_results($save_identifier)
	{
		$composite_xml = file_get_contents(SAVE_RESULTS_DIR . $save_identifier . "/composite.xml");

		$logs = self::capture_test_logs($save_identifier);
		$server_response = phoromatic::upload_to_remote_server(array(
			"r" => "upload_test_results_unscheduled",
			"c" => $composite_xml,
			"i" => 0,
			"ti" => "Unknown",
			"sl" => $logs["system-logs"],
			"tl" => $logs["test-logs"]
			));

		$xml_parser = new tandem_XmlReader($server_response);

		switch($xml_parser->getXMLValue(M_PHOROMATIC_GEN_RESPONSE))
		{
			case M_PHOROMATIC_RESPONSE_TRUE:
				echo "\nUploaded To Phoromatic.\n";
				break;
			case M_PHOROMATIC_RESPONSE_ERROR:
				echo "\nAn Error Occurred.\n";
				break;
			case M_PHOROMATIC_RESPONSE_SETTING_DISABLED:
				echo "\nYou need to enable this support from your Phoromatic account web interface.\n";
				break;
		}

		return $xml_parser->getXMLValue(M_PHOROMATIC_GEN_RESPONSE) == M_PHOROMATIC_RESPONSE_TRUE;
	}
	protected static function phoromatic_setup_module()
	{
		if(!pts_module::is_module_setup())
		{
			echo "\nYou first must run:\n\nphoronix-test-suite module-setup phoromatic\n\n";
			return false;
		}

		self::$phoromatic_host = pts_module::read_option("remote_host");
		self::$phoromatic_account = pts_module::read_option("remote_account");
		self::$phoromatic_verifier = pts_module::read_option("remote_verifier");
		self::$phoromatic_system = pts_module::read_option("remote_system");

		$phoromatic = "phoromatic";
		pts_module_manager::attach_module($phoromatic);
		return true;
	}
	protected static function phoromatic_schedule_entry_string($title, $description, $start_time, $active_on)
	{
		echo "\n" . $title . ":\n";
		echo "\t" . $description . "\n";
		echo "\tRuns at " . $start_time . " on " . pts_strings::parse_week_string($active_on) . ".\n";
	}


	//
	// Connection
	// 

	protected static function upload_to_remote_server($to_post, $host = null, $account = null, $verifier = null, $system = null)
	{
		static $last_communication_minute = null;
		static $communication_attempts = 0;

		if($last_communication_minute == date("i") && $communication_attempts > 3)
		{
				// Something is wrong, Phoromatic shouldn't be communicating with server more than four times a minute
				return false;
		}
		else
		{
			if(date("i") != $last_communication_minute)
			{
				$last_communication_minute = date("i");
				$communication_attempts = 0;
			}

			$communication_attempts++;
		}

		if($host != null)
		{
			//$host = $host;
			$to_post["aid"] = $account;
			$to_post["vid"] = $verifier;
			$to_post["sid"] = $system;
		}
		else if(self::$phoromatic_host != null)
		{
			$host = self::$phoromatic_host;
			$to_post["aid"] = self::$phoromatic_account;
			$to_post["vid"] = self::$phoromatic_verifier;
			$to_post["sid"] = self::$phoromatic_system;
		}
		else
		{
			echo "\nPhoromatic isn't configured. Run: phoronix-test-suite module-setup phoromatic\n\n";
			return;
		}

		$to_post["pts"] = PTS_VERSION;
		$to_post["pts_core"] = PTS_CORE_VERSION;
		$to_post["gsid"] = PTS_GSID;

		return pts_network::http_upload_via_post($host, $to_post);
	}
}

?>
