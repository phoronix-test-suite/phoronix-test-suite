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

define("M_PHOROMATIC_GEN_RESPONSE", "PhoronixTestSuite/Phoromatic/General/Response");
define("M_PHOROMATIC_ID", "PhoronixTestSuite/Phoromatic/General/ID");
define("M_PHOROMATIC_SYS_NAME", "PhoronixTestSuite/Phoromatic/General/SystemName");
define("M_PHOROMATIC_UPLOAD_TO_GLOBAL", "PhoronixTestSuite/Phoromatic/General/UploadToGlobal");
define("M_PHOROMATIC_ARCHIVE_RESULTS_LOCALLY", "PhoronixTestSuite/Phoromatic/General/ArchiveResultsLocally");
define("M_PHOROMATIC_RUN_INSTALL_COMMAND", "PhoronixTestSuite/Phoromatic/General/RunInstallCommand");

define("M_PHOROMATIC_SCHEDULE_TEST_TITLE", "PhoronixTestSuite/Phoromatic/Schedules/TestSchedule/Title");
define("M_PHOROMATIC_SCHEDULE_TEST_DESCRIPTION", "PhoronixTestSuite/Phoromatic/Schedules/TestSchedule/Description");
define("M_PHOROMATIC_SCHEDULE_TEST_ACTIVE_ON", "PhoronixTestSuite/Phoromatic/Schedules/TestSchedule/ActiveOn");
define("M_PHOROMATIC_SCHEDULE_TEST_START", "PhoronixTestSuite/Phoromatic/Schedules/TestSchedule/RunAt");

define("M_PHOROMATIC_RESPONSE_IDLE", "idle");
define("M_PHOROMATIC_RESPONSE_EXIT", "exit");
define("M_PHOROMATIC_RESPONSE_RUN_TEST", "benchmark");
define("M_PHOROMATIC_RESPONSE_SERVER_MAINTENANCE", "server_maintenance");
define("M_PHOROMATIC_RESPONSE_ERROR", "ERROR");
define("M_PHOROMATIC_RESPONSE_TRUE", "TRUE");
define("M_PHOROMATIC_RESPONSE_SETTING_DISABLED", "SETTING_DISABLED");

class phoromatic extends pts_module_interface
{
	const module_name = "Phoromatic Client";
	const module_version = "0.3.0";
	const module_description = "The Phoromatic client is used for connecting to a Phoromatic server (Phoromatic.com or a locally run server) to facilitate the automatic running of tests, generally across multiple test nodes in a routine manner. For more details visit http://www.phoromatic.com/";
	const module_author = "Phoronix Media";

	static $phoromatic_lock = null;

	static $phoromatic_host = null;
	static $phoromatic_account = null;
	static $phoromatic_verifier = null;
	static $phoromatic_system = null;

	public static function module_setup()
	{
		return array(
		new pts_module_option("remote_host", "Enter the URL to host", "HTTP_URL", "http://www.phoromatic.com/"),
		new pts_module_option("remote_account", "Enter the account code", "ALPHA_NUMERIC"),
		new pts_module_option("remote_verifier", "Enter the verification code", "ALPHA_NUMERIC")
		);
	}
	public static function module_setup_validate($options)
	{
		if(substr($options["remote_host"], -14) != "phoromatic.php")
		{
			$options["remote_host"] = pts_add_trailing_slash($options["remote_host"]) . "phoromatic.php";
		}

		$server_response = phoromatic::upload_to_remote_server(array("r" => "setup", "h" => pts_hw_string(),  "s" => pts_sw_string(),  "o" => phodevi::read_property("system", "hostname")),
		$options["remote_host"], $options["remote_account"], $options["remote_verifier"]);

		$returned_id = pts_xml_read_single_value($server_response, M_PHOROMATIC_GEN_RESPONSE);

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
			"system_schedule_today" => "system_schedule_today"
			);
	}

	//
	// User Run Commands
	//

	public static function user_start()
	{
		if(!pts_create_lock(PTS_USER_DIR . "phoromatic_lock", self::$phoromatic_lock))
		{
			echo pts_string_header("Phoromatic is already running.");
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

		switch(pts_xml_read_single_value($server_response, M_PHOROMATIC_GEN_RESPONSE))
		{
			case M_PHOROMATIC_RESPONSE_TRUE:
				$identifier = "phoromatic-clone-" . str_replace(array('_', ':'), null, $to_clone[0]);
				pts_save_result($identifier . "/composite.xml", $server_response); // TODO: regenerate the XML so that the Phoromatic response bits are not included
				echo "\nResult Saved To: " . SAVE_RESULTS_DIR . $identifier . "/composite.xml\n\n";
				pts_set_assignment_next("PREV_SAVE_RESULTS_IDENTIFIER", $identifier);
				pts_display_web_browser(SAVE_RESULTS_DIR . $identifier . "/index.html");
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
			echo "\nNo test schedules for this system were found on Phoromatic.\n";
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
			echo "\nNo test schedules for this system were found on Phoromatic.\n";
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
				$uploaded_test_results = phoromatic::upload_test_results($save_identifier);

				if(!$uploaded_test_results)
				{
					"\nFailed to upload test results on first attempt. Trying again in 60 seconds...\n";
					sleep(60);
					$uploaded_test_results = phoromatic::upload_test_results($save_identifier);

					if(!$uploaded_test_results)
					{
						echo "\nERROR OCCURRED IN UPLOADING RESULTS\n";
						return false;
					}
				}

				if(!pts_read_assignment("PHOROMATIC_ARCHIVE_RESULTS"))
				{
					pts_remove_test_result_dir($save_identifier);
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
			echo "\nRegistering Status With Phoromatic Server\n";
			$update_sd = phoromatic::update_system_details();

			if(!$update_sd)
			{
				echo "\nConnection to server failed. Trying again in 60 seconds...\n";
				sleep(60);

				$update_sd = phoromatic::update_system_details();

				if(!$update_sd)
				{
					echo "Server connection still failed. Exiting...\n";
					return false;
				}
			}

			$current_hw = pts_hw_string();
			$current_sw = pts_sw_string();
		}

		do
		{
			echo "\nChecking Status From Phoromatic Server @ " . date("H:i:s");

			if($last_communication_minute == date("i") && $communication_attempts > 2)
			{
				// Something is wrong, Phoromatic shouldn't be communicating with server more than three times a minute
				$response = "forced_idle";
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
					$args_to_pass = array("AUTOMATED_MODE" => true);

					do
					{
						$suite_identifier = "phoromatic-" . rand(1000, 9999);
					}
					while(is_file(XML_SUITE_LOCAL_DIR . $suite_identifier . ".xml"));

					$args_to_pass["AUTO_SAVE_NAME"] = date("Y-m-d H:i:s");
					$args_to_pass["PHOROMATIC_TITLE"] = $xml_parser->getXMLValue(P_SUITE_TITLE);
					$args_to_pass["PHOROMATIC_SCHEDULE_ID"] = $xml_parser->getXMLValue(M_PHOROMATIC_ID);
					$args_to_pass["AUTO_TEST_RESULTS_IDENTIFIER"] = $xml_parser->getXMLValue(M_PHOROMATIC_SYS_NAME);

					if(pts_string_bool($xml_parser->getXMLValue(M_PHOROMATIC_UPLOAD_TO_GLOBAL, "FALSE")))
					{
						$args_to_pass["AUTO_UPLOAD_TO_GLOBAL"] = true;
					}

					if(pts_string_bool($xml_parser->getXMLValue(M_PHOROMATIC_ARCHIVE_RESULTS_LOCALLY, M_PHOROMATIC_RESPONSE_TRUE)))
					{
						$args_to_pass["PHOROMATIC_ARCHIVE_RESULTS"] = true;
					}

					file_put_contents(XML_SUITE_LOCAL_DIR . $suite_identifier . ".xml", $server_response);

					if(pts_string_bool($xml_parser->getXMLValue(M_PHOROMATIC_RUN_INSTALL_COMMAND, M_PHOROMATIC_RESPONSE_TRUE)))
					{
						pts_run_option_next("install_test", $suite_identifier, array("AUTOMATED_MODE" => true));
					}

					pts_run_option_next("run_test", $suite_identifier, $args_to_pass);
					pts_run_option_next("phoromatic.user_system_return", $suite_identifier, $args_to_pass);
					break;
				case M_PHOROMATIC_RESPONSE_EXIT:
					echo "\nPhoromatic received a remote command to exit.\n";
					phoromatic::update_system_status("Exiting Phoromatic");
					pts_release_lock(self::$phoromatic_lock, PTS_USER_DIR . "phoromatic_lock");
					break;
				case M_PHOROMATIC_RESPONSE_SERVER_MAINTENANCE:
					// The Phoromatic server is down for maintenance, so don't bother updating system status and wait longer before checking back
					echo "\nThe Phoromatic server is currently down for maintenance. Waiting for service to be restored.\n";
					sleep((15 - (date("i") % 15)) * 60);
					break;
				case M_PHOROMATIC_RESPONSE_IDLE:
				default:
					phoromatic::update_system_status("Idling, Waiting For Task");
					sleep((10 - (date("i") % 10)) * 60); // Check with server every 10 minutes
					break;
			}

			if(pts_hw_string() != $current_hw || pts_sw_string() != $current_sw)
			{
				// Hardware and/or software has changed while PTS/Phoromatic has been running, update the Phoromatic Server
				echo "Updating Installed Hardware / Software With Phoromatic Server\n";
				phoromatic::update_system_details();
				$current_hw = pts_hw_string();
				$current_sw = pts_sw_string();
			}
		}
		while(!in_array($response, array(M_PHOROMATIC_RESPONSE_EXIT, M_PHOROMATIC_RESPONSE_RUN_TEST)));
	}

	//
	// Process Functions
	//


	public static function __pre_test_install($test_identifier)
	{
		static $last_update_time = 0;

		if(time() > ($last_update_time + 600))
		{
			phoromatic::update_system_status("Installing Tests");
			$last_update_time = time();
		}
	}
	public static function __pre_test_run($pts_test_result)
	{
		phoromatic::update_system_status("Running " . $pts_test_result->get_test_profile()->get_identifier() . " For " . pts_read_assignment("PHOROMATIC_TITLE"));
	}
	public static function __event_user_error($user_error)
	{
		// Report PTS user error warnings to Phoromatic server
		phoromatic::report_warning_to_phoromatic($user_error->get_error_string());
	}

	//
	// Other Functions
	//

	protected static function update_system_details()
	{
		$server_response = phoromatic::upload_to_remote_server(array("r" => "update_system_details", "h" => pts_hw_string(), "s" => pts_sw_string(), "gsid" => PTS_GSID));

		return pts_xml_read_single_value($server_response, M_PHOROMATIC_GEN_RESPONSE) == M_PHOROMATIC_RESPONSE_TRUE;
	}
	protected static function update_system_status($current_task)
	{
		$server_response = phoromatic::upload_to_remote_server(array("r" => "update_system_status", "a" => $current_task));

		return pts_xml_read_single_value($server_response, M_PHOROMATIC_GEN_RESPONSE) == M_PHOROMATIC_RESPONSE_TRUE;
	}
	protected static function report_warning_to_phoromatic($warning)
	{
		$server_response = phoromatic::upload_to_remote_server(array("r" => "report_pts_warning", "a" => $warning));

		return pts_xml_read_single_value($server_response, M_PHOROMATIC_GEN_RESPONSE) == M_PHOROMATIC_RESPONSE_TRUE;
	}
	protected static function upload_test_results($save_identifier)
	{
		$composite_xml = file_get_contents(SAVE_RESULTS_DIR . $save_identifier . "/composite.xml");
		$server_response = phoromatic::upload_to_remote_server(array(
			"r" => "upload_test_results",
			"c" => $composite_xml,
			"i" => pts_read_assignment("PHOROMATIC_SCHEDULE_ID"),
			"ti" => pts_read_assignment("AUTO_TEST_RESULTS_IDENTIFIER")
			));

		return pts_xml_read_single_value($server_response, M_PHOROMATIC_GEN_RESPONSE) == M_PHOROMATIC_RESPONSE_TRUE;
	}
	protected static function upload_unscheduled_test_results($save_identifier)
	{
		$composite_xml = file_get_contents(SAVE_RESULTS_DIR . $save_identifier . "/composite.xml");
		$server_response = phoromatic::upload_to_remote_server(array(
			"r" => "upload_test_results_unscheduled",
			"c" => $composite_xml,
			"i" => 0,
			"ti" => "Unknown"
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

		pts_attach_module($phoromatic);
		return true;
	}
	protected static function phoromatic_schedule_entry_string($title, $description, $start_time, $active_on)
	{
		echo "\n" . $title . ":\n";
		echo "\t" . $description . "\n";
		echo "\tRuns at " . $start_time . " on " . pts_parse_week_string($active_on) . ".\n";
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
			//$host
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

		return pts_http_upload_via_post($host, $to_post);
	}
}

?>
