<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2014, Phoronix Media
	Copyright (C) 2009 - 2014, Michael Larabel

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
	const module_name = 'Phoromatic Client';
	const module_version = '0.0.1';
	const module_description = 'The Phoromatic client is used for connecting to a Phoromatic server (Phoromatic.com or a locally run server) to facilitate the automatic running of tests, generally across multiple test nodes in a routine manner. For more details visit http://www.phoromatic.com/. This module is intended to be used with Phoronix Test Suite 5.2+ clients and servers.';
	const module_author = 'Phoronix Media';

	static $account_id = null;
	static $server_address = null;
	static $server_http_port = null;
	static $server_ws_port = null;

	public static function module_info()
	{
		return 'The Phoromatic module contains the client support for interacting with Phoromatic and Phoromatic Tracker services. A public, free reference implementation of Phoromatic can be found at http://www.phoromatic.com/. A commercial version is available to enterprise customers for installation onto their intranet. For more information, contact Phoronix Media.';
	}
	public static function user_commands()
	{
		return array('connect' => 'run_connection');
	}
	public static function module_setup()
	{
		return array(
		new pts_module_option('remote_host', 'Enter the URL to host', 'HTTP_URL', 'http://www.phoromatic.com/'),
		new pts_module_option('remote_account', 'Enter the account code', 'ALPHA_NUMERIC'),
		new pts_module_option('remote_verifier', 'Enter the verification code', 'ALPHA_NUMERIC'),
		new pts_module_option('system_description', 'Enter a short (optional) description for this system', null, null, null, false)
		);
	}
	protected static function upload_to_remote_server($to_post, $server_address = null, $server_http_port = null, $account_id = null)
	{
		static $last_communication_minute = null;
		static $communication_attempts = 0;

		if($last_communication_minute == date('i') && $communication_attempts > 3)
		{
				// Something is wrong, Phoromatic shouldn't be communicating with server more than four times a minute
				return false;
		}
		else
		{
			if(date('i') != $last_communication_minute)
			{
				$last_communication_minute = date('i');
				$communication_attempts = 0;
			}

			$communication_attempts++;
		}

		if($server_address == null && self::$server_address != null)
		{
			$server_address = self::$server_address;
		}
		if($server_http_port == null && self::$server_http_port != null)
		{
			$server_http_port = self::$server_http_port;
		}
		if($account_id == null && self::$account_id != null)
		{
			$account_id = self::$account_id;
		}

		$to_post['aid'] = $account_id;
		$to_post['pts'] = PTS_VERSION;
		$to_post['pts_core'] = PTS_CORE_VERSION;
		$to_post['gsid'] = PTS_GSID;
		$to_post['lip'] = pts_network::get_local_ip();
		return pts_network::http_upload_via_post('http://' . $server_address . ':' . $server_http_port .  '/phoromatic.php', $to_post);
	}
	public static function run_connection($args)
	{
		$account_id = substr($args[0], strrpos($args[0], '/') + 1);
		$ip = substr($args[0], 0, strpos($args[0], ':'));
		$http_port = substr($args[0], strlen($ip) + 1, -1 - strlen($account_id));
		pts_client::$display->generic_heading('Server IP: ' . $ip . PHP_EOL . 'Server HTTP Port: ' . $http_port . PHP_EOL . 'Account ID: ' . $account_id);

		$server_response = phoromatic::upload_to_remote_server(array(
			'r' => 'start',
			'h' => phodevi::system_hardware(true),
			's' => phodevi::system_software(true),
			'n' => phodevi::read_property('system', 'hostname'),
			'aid' => $account_id
			), $ip, $http_port, $account_id);

		if(substr($server_response, 0, 1) == '{')
		{
			$json = json_decode($server_response, true);

			if($json != null)
			{
				if(isset($json['phoromatic']['error']) && !empty($json['phoromatic']['error']))
				{
					trigger_error($json['phoromatic']['error'], E_USER_ERROR);
				}
				if(isset($json['phoromatic']['response']) && !empty($json['phoromatic']['response']))
				{
					echo PHP_EOL . $json['phoromatic']['response'] . PHP_EOL;
				}

			}
		}

		var_dump($server_response);
		var_dump($args);
	}






	public static function module_setup_validate($options)
	{
		if(substr($options['remote_host'], -14) != 'phoromatic.php')
		{
			$options['remote_host'] = pts_strings::add_trailing_slash($options['remote_host']) . 'phoromatic.php';
		}

		$server_response = phoromatic::upload_to_remote_server(array(
			'r' => 'start',
			'h' => phodevi::system_hardware(true),
			's' => phodevi::system_software(true),
			'n' => phodevi::read_property('system', 'hostname'),
			),
			$options['remote_host'], $options['remote_account'], $options['remote_verifier']);

		$returned_id = self::read_xml_value($server_response, 'PhoronixTestSuite/Phoromatic/General/Response');

		unset($options['system_description']); // No reason to have this locally just pass it to the server

		if(!empty($returned_id))
		{
			$options['remote_system'] = $returned_id;
			echo PHP_EOL . 'Run Phoromatic by entering: phoronix-test-suite phoromatic.start' . PHP_EOL;
		}
		else
		{
			echo PHP_EOL . 'Configuration Failed!' . PHP_EOL;
			$options = array();
		}

		return $options;
	}

	//
	// User Run Commands
	//

	public static function user_start()
	{
		if(pts_client::create_lock(PTS_USER_PATH . 'phoromatic_lock') == false)
		{
			trigger_error('Phoromatic is already running.', E_USER_ERROR);
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

		if(!isset($to_upload[0]) || pts_result_file::is_test_result_file($to_upload[0]) == false)
		{
			echo PHP_EOL . 'No test result file was found to upload.' . PHP_EOL;
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
			echo PHP_EOL . 'No clone string was provided.' . PHP_EOL;
			return false;
		}

		$server_response = phoromatic::upload_to_remote_server(array(
			'r' => 'clone_test_results',
			'i' => $to_clone[0]
			));

		switch(self::read_xml_value($server_response, 'PhoronixTestSuite/Phoromatic/General/Response'))
		{
			case 'TRUE':
				$identifier = 'phoromatic-clone-' . str_replace(array('_', ':'), null, $to_clone[0]);
				pts_client::save_test_result($identifier . '/composite.xml', $server_response); // TODO: regenerate the XML so that the Phoromatic response bits are not included
				echo PHP_EOL . 'Result Saved To: ' . PTS_SAVE_RESULTS_PATH . $identifier . '/composite.xml' . PHP_EOL . PHP_EOL;
				pts_client::display_web_page(PTS_SAVE_RESULTS_PATH . $identifier . '/index.html');
				break;
			case 'SETTING_DISABLED':
				echo PHP_EOL . 'You need to enable this support from your Phoromatic account web interface.' . PHP_EOL;
				break;
			default:
			case 'ERROR':
				echo PHP_EOL . 'An Error Occurred.' . PHP_EOL;
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
			'r' => 'system_schedule'
			));

		$schedule_xml = new nye_XmlReader($server_response);
		$schedule_titles = $schedule_xml->getXmlArrayValues('PhoronixTestSuite/Phoromatic/Schedules/TestSchedule/Title');
		$schedule_description = $schedule_xml->getXmlArrayValues('PhoronixTestSuite/Phoromatic/Schedules/TestSchedule/Description');
		$schedule_active_on = $schedule_xml->getXmlArrayValues('PhoronixTestSuite/Phoromatic/Schedules/TestSchedule/ActiveOn');
		$schedule_start_time = $schedule_xml->getXmlArrayValues('PhoronixTestSuite/Phoromatic/Schedules/TestSchedule/RunAt');

		if(count($schedule_titles) == 0)
		{
			echo PHP_EOL . 'No test schedules for this system were found on the Phoromatic Server.' . PHP_EOL;
		}
		else
		{
			for($i = 0; $i < count($schedule_titles); $i++)
			{
				echo self::phoromatic_schedule_entry_string($schedule_titles[$i], $schedule_description[$i], $schedule_start_time[$i], $schedule_active_on[$i]);
			}
		}

		echo PHP_EOL;
	}
	public static function system_schedule_today()
	{
		if(!phoromatic::phoromatic_setup_module())
		{
			return false;
		}

		$server_response = phoromatic::upload_to_remote_server(array(
			'r' => 'system_schedule'
			));

		$schedule_xml = new nye_XmlReader($server_response);
		$schedule_titles = $schedule_xml->getXmlArrayValues('PhoronixTestSuite/Phoromatic/Schedules/TestSchedule/Title');
		$schedule_description = $schedule_xml->getXmlArrayValues('PhoronixTestSuite/Phoromatic/Schedules/TestSchedule/Description');
		$schedule_active_on = $schedule_xml->getXmlArrayValues('PhoronixTestSuite/Phoromatic/Schedules/TestSchedule/ActiveOn');
		$schedule_start_time = $schedule_xml->getXmlArrayValues('PhoronixTestSuite/Phoromatic/Schedules/TestSchedule/RunAt');

		if(count($schedule_titles) == 0)
		{
			echo PHP_EOL . 'No test schedules for this system were found on the Phoromatic Server.' . PHP_EOL;
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

		echo PHP_EOL;
	}
	public static function send_message_to_server($msg)
	{
		if(!phoromatic::phoromatic_setup_module())
		{
			return false;
		}

		if(empty($msg))
		{
			echo PHP_EOL . 'Pass the message as the first argument.' . PHP_EOL;
			return false;
		}

		if(self::report_warning_to_phoromatic('MESSAGE: ' . implode(' ', $msg)))
		{
			echo PHP_EOL . 'Message Sent To Phoromatic Server.' . PHP_EOL;
		}
		else
		{
			echo PHP_EOL . 'Message Failed To Send.' . PHP_EOL;
		}
	}

	//
	// Core Functions
	//

	public static function user_system_process()
	{
		define('PHOROMATIC_PROCESS', true);
		$last_communication_minute = date('i');
		$communication_attempts = 0;
		static $current_hw = null;
		static $current_sw = null;

		if(define('PHOROMATIC_START', true))
		{
			echo PHP_EOL . 'Registering Status With Phoromatic Server @ ' . date('H:i:s') . PHP_EOL;

			$times_tried = 0;
			do
			{
				if($times_tried > 0)
				{
					echo PHP_EOL . 'Connection to server failed. Trying again in 60 seconds...' . PHP_EOL;
					sleep(60);
				}

				$update_sd = phoromatic::update_system_details();
				$times_tried++;
			}
			while(!$update_sd && $times_tried < 5);

			if(!$update_sd)
			{
				echo 'Server connection still failed. Exiting...' . PHP_EOL;
				return false;
			}

			$current_hw = phodevi::system_hardware(true);
			$current_sw = phodevi::system_software(true);

			echo PHP_EOL . 'Idling 30 seconds for system to settle...' . PHP_EOL;
			sleep(30);
		}

		do
		{
			$exit_loop = false;
			echo PHP_EOL . 'Checking Status From Phoromatic Server @ ' . date('H:i:s');

			if($last_communication_minute == date('i') && $communication_attempts > 2)
			{
				// Something is wrong, Phoromatic shouldn't be communicating with server more than three times a minute
				$response = 'idle';
			}
			else
			{
				$server_response = phoromatic::upload_to_remote_server(array('r' => 'status_check'));
				$xml_parser = new nye_XmlReader($server_response);
				$response = $xml_parser->getXMLValue('PhoronixTestSuite/Phoromatic/General/Response');

				if(date('i') != $last_communication_minute)
				{
					$last_communication_minute = date('i');
					$communication_attempts = 0;
				}

				$communication_attempts++;
			}

			if($response != null)
			{
				echo ' [' . $response . ']' . PHP_EOL;
			}

			switch($response)
			{
				case 'benchmark':
					$test_flags = pts_c::auto_mode;

					do
					{
						$suite_identifier = 'phoromatic-' . rand(1000, 9999);
					}
					while(is_file(PTS_TEST_SUITE_PATH . 'local/' . $suite_identifier . '/suite-definition.xml'));

					pts_file_io::mkdir(PTS_TEST_SUITE_PATH . 'local/' . $suite_identifier);
					file_put_contents(PTS_TEST_SUITE_PATH . 'local/' . $suite_identifier . '/suite-definition.xml', $server_response);

					$phoromatic_schedule_id = $xml_parser->getXMLValue('PhoronixTestSuite/Phoromatic/General/ID');
					$phoromatic_results_identifier = $xml_parser->getXMLValue('PhoronixTestSuite/Phoromatic/General/SystemName');
					$phoromatic_trigger = $xml_parser->getXMLValue('PhoronixTestSuite/Phoromatic/General/Trigger');
					self::$openbenchmarking_upload_json = null;

					$suite_identifier = 'local/' . $suite_identifier;
					if(pts_strings::string_bool($xml_parser->getXMLValue('PhoronixTestSuite/Phoromatic/General/RunInstallCommand', 'TRUE')))
					{
						phoromatic::set_user_context($xml_parser->getXMLValue('PhoronixTestSuite/Phoromatic/General/SetContextPreInstall'), $phoromatic_trigger, $phoromatic_schedule_id, 'INSTALL');

						if(pts_strings::string_bool($xml_parser->getXMLValue('PhoronixTestSuite/Phoromatic/General/ForceInstallTests', 'TRUE')))
						{
							$test_flags |= pts_c::force_install;
						}

						pts_client::set_test_flags($test_flags);
						pts_test_installer::standard_install($suite_identifier);
					}

					phoromatic::set_user_context($xml_parser->getXMLValue('PhoronixTestSuite/Phoromatic/General/SetContextPreRun'), $phoromatic_trigger, $phoromatic_schedule_id, 'INSTALL');


					// Do the actual running
					if(pts_test_run_manager::initial_checks($suite_identifier))
					{
						$test_run_manager = new pts_test_run_manager($test_flags);

						// Load the tests to run
						if($test_run_manager->load_tests_to_run($suite_identifier))
						{

							if(pts_strings::string_bool($xml_parser->getXMLValue('PhoronixTestSuite/Phoromatic/General/UploadToGlobal', 'FALSE')))
							{
								$test_run_manager->auto_upload_to_openbenchmarking();
								pts_openbenchmarking_client::override_client_setting('UploadSystemLogsByDefault', pts_strings::string_bool($xml_parser->getXMLValue('PhoronixTestSuite/Phoromatic/General/UploadSystemLogs', 'TRUE')));
							}

							// Save results?
							$save_identifier = date('Y-m-d H:i:s');
							$test_run_manager->auto_save_results($save_identifier, $phoromatic_results_identifier, 'A Phoromatic run.');

							// Run the actual tests
							$test_run_manager->pre_execution_process();
							$test_run_manager->call_test_runs();
							$test_run_manager->post_execution_process();

							// Upload to Phoromatic
							pts_file_io::unlink(PTS_TEST_SUITE_PATH . $suite_identifier . '/suite-definition.xml');

							// Upload test results

							if(is_file(PTS_SAVE_RESULTS_PATH . $test_run_manager->get_file_name() . '/composite.xml'))
							{
								phoromatic::update_system_status('Uploading Test Results');

								$times_tried = 0;
								do
								{
									if($times_tried > 0)
									{
										echo PHP_EOL . 'Connection to server failed. Trying again in 60 seconds...' . PHP_EOL;
										sleep(60);
									}

									$uploaded_test_results = phoromatic::upload_test_results($test_run_manager->get_file_name(), $phoromatic_schedule_id, $phoromatic_results_identifier, $phoromatic_trigger, $xml_parser);
									$times_tried++;
								}
								while($uploaded_test_results == false && $times_tried < 5);

								if($uploaded_test_results == false)
								{
									echo 'Server connection failed. Exiting...' . PHP_EOL;
									return false;
								}

								if(pts_strings::string_bool($xml_parser->getXMLValue('PhoronixTestSuite/Phoromatic/General/ArchiveResultsLocally', 'TRUE')) == false)
								{
									pts_client::remove_saved_result_file($test_run_manager->get_file_name());
								}
							}
						}
					}
					break;
				case 'exit':
					echo PHP_EOL . 'Phoromatic received a remote command to exit.' . PHP_EOL;
					phoromatic::update_system_status('Exiting Phoromatic');
					pts_client::release_lock(PTS_USER_PATH . 'phoromatic_lock');
					$exit_loop = true;
					break;
				case 'server_maintenance':
					// The Phoromatic server is down for maintenance, so don't bother updating system status and wait longer before checking back
					echo PHP_EOL . 'The Phoromatic server is currently down for maintenance. Waiting for service to be restored.' . PHP_EOL;
					sleep((15 - (date('i') % 15)) * 60);
					break;
				case 'SHUTDOWN':
					echo PHP_EOL . 'Shutting down the system.' . PHP_EOL;
					$exit_loop = true;
					shell_exec('poweroff'); // Currently assuming root
					break;
				case 'REBOOT':
					echo PHP_EOL . 'Rebooting the system.' . PHP_EOL;
					$exit_loop = true;
					shell_exec('reboot'); // Currently assuming root
					break;
				case 'idle':
				default:
					phoromatic::update_system_status('Idling, Waiting For Task');
					sleep((10 - (date('i') % 10)) * 60); // Check with server every 10 minutes
					break;
			}

			if(phodevi::system_hardware(true) != $current_hw || phodevi::system_software(true) != $current_sw)
			{
				// Hardware and/or software has changed while PTS/Phoromatic has been running, update the Phoromatic Server
				echo 'Updating Installed Hardware / Software With Phoromatic Server' . PHP_EOL;
				phoromatic::update_system_details();
				$current_hw = phodevi::system_hardware(true);
				$current_sw = phodevi::system_software(true);
			}
		}
		while($exit_loop == false);

		phoromatic::update_system_status('Offline');
	}

	//
	// Process Functions
	//


	public static function __pre_test_install($test_identifier)
	{
		static $last_update_time = 0;

		if(time() > ($last_update_time + 600))
		{
			phoromatic::update_system_status('Installing Tests');
			$last_update_time = time();
		}
	}
	public static function __pre_test_run($pts_test_result)
	{
		// TODO: need a way to get the estimated time remaining from the test_run_manager so we can pass that back to the update_system_status parameter so server can read it
		// TODO: report name of test identifier/run i.e. . ' For ' . PHOROMATIC_TITLE
		phoromatic::update_system_status('Running ' . $pts_test_result->test_profile->get_identifier());
	}
	public static function __event_user_error($user_error)
	{
		// Report PTS user error warnings to Phoromatic server
		phoromatic::report_warning_to_phoromatic($user_error->get_error_string());
	}
	public static function __event_results_saved($test_run_manager)
	{
		if(pts_module::read_variable('AUTO_UPLOAD_RESULTS_TO_PHOROMATIC') && pts_module::is_module_setup())
		{
			phoromatic::upload_unscheduled_test_results($test_run_manager->get_file_name());
		}
	}
	public static function __event_openbenchmarking_upload($json)
	{
		self::$openbenchmarking_upload_json = $json;
	}

	//
	// Other Functions
	//

	protected static function read_xml_value($file, $xml_option)
	{
		$xml_parser = new nye_XmlReader($file);
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

			$storage_path = pts_module::save_dir() . 'memory.pt2so';
			$storage_object = pts_storage_object::recover_from_file($storage_path);

			// We check to see if the context was already set but the system rebooted or something in that script
			if($storage_object == false)
			{
				$storage_object = new pts_storage_object(true, true);
			}
			else if($storage_object->read_object('last_set_context_trigger') == $trigger && $storage_object->read_object('last_set_context_schedule') == $schedule_id && $storage_object->read_object('last_set_context_process') == $process)
			{
				// If the script already ran once for this trigger, don't run it again
				return false;
			}

			$storage_object->add_object('last_set_context_trigger', $trigger);
			$storage_object->add_object('last_set_context_schedule', $schedule_id);
			$storage_object->add_object('last_set_context_process', $process);
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
		$server_response = phoromatic::upload_to_remote_server(array('r' => 'update_system_details', 'h' => phodevi::system_hardware(true), 's' => phodevi::system_software(true)));
		self::$phoromatic_server_build = self::read_xml_value($server_response, 'PhoronixTestSuite/Phoromatic/Server/ServerBuild');

		return self::read_xml_value($server_response, 'PhoronixTestSuite/Phoromatic/General/Response') == 'TRUE';
	}
	protected static function update_system_status($current_task, $estimated_time_remaining = 0)
	{
		$server_response = phoromatic::upload_to_remote_server(array('r' => 'update_system_status', 'a' => $current_task, 'time' => $estimated_time_remaining));

		return self::read_xml_value($server_response, 'PhoronixTestSuite/Phoromatic/General/Response') == 'TRUE';
	}
	protected static function report_warning_to_phoromatic($warning)
	{
		$server_response = phoromatic::upload_to_remote_server(array('r' => 'report_pts_warning', 'a' => $warning));

		return self::read_xml_value($server_response, 'PhoronixTestSuite/Phoromatic/General/Response') == 'TRUE';
	}
	private static function capture_test_logs($save_identifier, &$xml_parser = null)
	{
		$data = array('system-logs' => null, 'test-logs' => null);

		if(is_dir(PTS_SAVE_RESULTS_PATH . $save_identifier . '/system-logs/') && ($xml_parser == null || $xml_parser->getXMLValue('PhoronixTestSuite/Phoromatic/General/UploadSystemLogs', 'FALSE')))
		{
			$system_logs_zip = pts_client::create_temporary_file();
			pts_compression::zip_archive_create($system_logs_zip, PTS_SAVE_RESULTS_PATH . $save_identifier . '/system-logs/');
			$data['system-logs'] = base64_encode(file_get_contents($system_logs_zip));
			unlink($system_logs_zip);
		}

		if(is_dir(PTS_SAVE_RESULTS_PATH . $save_identifier . '/test-logs/') && ($xml_parser == null || $xml_parser->getXMLValue('PhoronixTestSuite/Phoromatic/General/UploadTestLogs', 'FALSE')))
		{
			$test_logs_zip = pts_client::create_temporary_file();
			pts_compression::zip_archive_create($test_logs_zip, PTS_SAVE_RESULTS_PATH . $save_identifier . '/test-logs/');
			$data['test-logs'] = base64_encode(file_get_contents($test_logs_zip));
			unlink($test_logs_zip);
		}

		return $data;
	}
	protected static function upload_test_results($save_identifier, $phoromatic_schedule_id, $results_identifier, $phoromatic_trigger, &$xml_parser = null)
	{
		$composite_xml = file_get_contents(PTS_SAVE_RESULTS_PATH . $save_identifier . '/composite.xml');

		if(self::$openbenchmarking_upload_json != null && isset(self::$openbenchmarking_upload_json['openbenchmarking']['upload']['id']))
		{
			$openbenchmarking_id = self::$openbenchmarking_upload_json['openbenchmarking']['upload']['id'];
		}
		else
		{
			$openbenchmarking_id = null;
		}

		$logs = self::capture_test_logs($save_identifier, $xml_parser);
		$server_response = phoromatic::upload_to_remote_server(array(
			'r' => 'upload_test_results',
			'c' => $composite_xml,
			'i' => $phoromatic_schedule_id,
			'ti' => $results_identifier,
			'ts' => $phoromatic_trigger,
			'sl' => $logs['system-logs'],
			'tl' => $logs['test-logs'],
			'ob' => $openbenchmarking_id,
			));

		return self::read_xml_value($server_response, 'PhoronixTestSuite/Phoromatic/General/Response') == 'TRUE';
	}
	protected static function upload_unscheduled_test_results($save_identifier)
	{
		$composite_xml = file_get_contents(PTS_SAVE_RESULTS_PATH . $save_identifier . '/composite.xml');

		$logs = self::capture_test_logs($save_identifier);
		$server_response = phoromatic::upload_to_remote_server(array(
			'r' => 'upload_test_results_unscheduled',
			'c' => $composite_xml,
			'i' => 0,
			'ti' => 'Unknown',
			'sl' => $logs['system-logs'],
			'tl' => $logs['test-logs']
			));

		$xml_parser = new nye_XmlReader($server_response);

		switch($xml_parser->getXMLValue('PhoronixTestSuite/Phoromatic/General/Response'))
		{
			case 'TRUE':
				echo PHP_EOL . 'Uploaded To Phoromatic.' . PHP_EOL;
				break;
			case 'ERROR':
				echo PHP_EOL . 'An Error Occurred.' . PHP_EOL;
				break;
			case 'SETTING_DISABLED':
				echo PHP_EOL . 'You need to enable this support from your Phoromatic account web interface.' . PHP_EOL;
				break;
		}

		return $xml_parser->getXMLValue('PhoronixTestSuite/Phoromatic/General/Response') == 'TRUE';
	}
	protected static function phoromatic_setup_module()
	{
		if(!pts_module::is_module_setup())
		{
			echo PHP_EOL . 'You first must run:' . PHP_EOL . PHP_EOL . 'phoronix-test-suite module-setup phoromatic' . PHP_EOL . PHP_EOL;
			return false;
		}

		self::$phoromatic_host = pts_module::read_option('remote_host');
		self::$phoromatic_account = pts_module::read_option('remote_account');
		self::$phoromatic_verifier = pts_module::read_option('remote_verifier');
		self::$phoromatic_system = pts_module::read_option('remote_system');

		if(extension_loaded('openssl') == false)
		{
			// OpenSSL is not supported therefore no HTTPS support
			self::$phoromatic_host = str_replace('https://', 'http://', self::$phoromatic_host);
		}

		$phoromatic = 'phoromatic';
		pts_module_manager::attach_module($phoromatic);
		return true;
	}
	protected static function phoromatic_schedule_entry_string($title, $description, $start_time, $active_on)
	{
		echo PHP_EOL . $title . ':' . PHP_EOL;
		echo "\t" . $description . PHP_EOL;
		echo "\t" . 'Runs at ' . $start_time . ' on ' . pts_strings::parse_week_string($active_on) . '.' . PHP_EOL;
	}
}

?>
