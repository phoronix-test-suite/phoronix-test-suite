<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2013, Phoronix Media
	Copyright (C) 2009 - 2013, Michael Larabel

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

class ekofisk extends pts_module_interface
{
	const module_name = 'OpenBenchmarking.org Ekofisk';
	const module_version = '1.0.0';
	const module_description = 'The Ekofisk client is used for connecting to OpenBenchmarking.org to facilitate the automatic running of tests, generally across multiple test nodes in a routine manner.';
	const module_author = 'OpenBenchmarking.org';

	static $phoromatic_server_build = false;

	public static function module_info()
	{
		return '';
	}
	public static function user_commands()
	{
		return array(
			'start' => 'user_start',
			'user_system_return' => 'user_system_return',
			);
	}

	//
	// User Run Commands
	//

	public static function user_start()
	{
		if(pts_client::create_lock(PTS_USER_PATH . 'ekofisk_lock') == false)
		{
			trigger_error('Ekofisk is already running.', E_USER_ERROR);
			return false;
		}
		if(pts_openbenchmarking_client::user_name() == null)
		{
			trigger_error('You must be logged into your OpenBenchmarking.org account.', E_USER_ERROR);
			return false;
		}

		$last_communication_minute = null;
		$communication_attempts = 0;

		do
		{
			$exit_loop = false;

			if($last_communication_minute != date('i'))
			{
				echo PHP_EOL . 'Checking State From Server @ ' . date('H:i:s');
			}

			if($last_communication_minute == date('i') && $communication_attempts > 3)
			{
				// Something is wrong, Phoromatic shouldn't be communicating with server more than three times a minute
				$response = M_PHOROMATIC_RESPONSE_IDLE;
			}
			else
			{
				$server_response = self::make_server_request(array('ekofisk_task' => 'status_check'));
				$json = json_decode($server_response, true);
				$response = $json['openbenchmarking']['response']['ekofisk'];

				if(date('i') != $last_communication_minute)
				{
					$last_communication_minute = date('i');
					$communication_attempts = 0;
				}

				$communication_attempts++;
			}

			echo ' [' . $response . ']' . PHP_EOL;

			switch($response)
			{
				case M_PHOROMATIC_RESPONSE_RUN_TEST:
					$test_flags = pts_c::auto_mode | pts_c::recovery_mode;

					do
					{
						$suite_identifier = 'phoromatic-' . rand(1000, 9999);
					}
					while(is_file(PTS_TEST_SUITE_PATH . 'local/' . $suite_identifier . '/suite-definition.xml'));

					file_put_contents(PTS_TEST_SUITE_PATH . 'local/' . $suite_identifier . '/suite-definition.xml', $server_response);

					$phoromatic_schedule_id = $xml_parser->getXMLValue(M_PHOROMATIC_ID);
					$phoromatic_results_identifier = $xml_parser->getXMLValue(M_PHOROMATIC_SYS_NAME);
					$phoromatic_trigger = $xml_parser->getXMLValue(M_PHOROMATIC_TRIGGER);

					if(pts_strings::string_bool($xml_parser->getXMLValue(M_PHOROMATIC_RUN_INSTALL_COMMAND, M_PHOROMATIC_RESPONSE_TRUE)))
					{
						phoromatic::set_user_context($xml_parser->getXMLValue(M_PHOROMATIC_SET_CONTEXT_PRE_INSTALL), $phoromatic_trigger, $phoromatic_schedule_id, 'INSTALL');
						pts_client::set_test_flags($test_flags);
						pts_test_installer::standard_install($suite_identifier);
					}

					phoromatic::set_user_context($xml_parser->getXMLValue(M_PHOROMATIC_SET_CONTEXT_PRE_RUN), $phoromatic_trigger, $phoromatic_schedule_id, 'INSTALL');


					// Do the actual running
					if(pts_test_run_manager::initial_checks($suite_identifier))
					{
						$test_run_manager = new pts_test_run_manager($test_flags);

						// Load the tests to run
						if($test_run_manager->load_tests_to_run($suite_identifier))
						{

							if(pts_strings::string_bool($xml_parser->getXMLValue(M_PHOROMATIC_UPLOAD_TO_GLOBAL, 'FALSE')))
							{
								$test_run_manager->auto_upload_to_openbenchmarking();
							}

							// Save results?
							$test_run_manager->auto_save_results(date('Y-m-d H:i:s'), $phoromatic_results_identifier, 'A Phoromatic run.');

							// Run the actual tests
							$test_run_manager->pre_execution_process();
							$test_run_manager->call_test_runs();
							$test_run_manager->post_execution_process();

							// Upload to Phoromatic
							pts_file_io::unlink(PTS_TEST_SUITE_PATH . 'local/' . $suite_identifier . '/suite-definition.xml');

							// Upload test results

							if(is_file(PTS_SAVE_RESULTS_PATH . $save_identifier . '/composite.xml'))
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

									$uploaded_test_results = phoromatic::upload_test_results($save_identifier, $phoromatic_schedule_id, $phoromatic_results_identifier, $phoromatic_trigger);
									$times_tried++;
								}
								while($uploaded_test_results == false && $times_tried < 5);

								if($uploaded_test_results == false)
								{
									echo 'Server connection failed. Exiting...' . PHP_EOL;
									return false;
								}

								if(pts_strings::string_bool($xml_parser->getXMLValue(M_PHOROMATIC_ARCHIVE_RESULTS_LOCALLY, M_PHOROMATIC_RESPONSE_TRUE)) == false)
								{
									pts_client::remove_saved_result_file($save_identifier);
								}
							}
						}
					}
					break;
				case M_PHOROMATIC_RESPONSE_EXIT:
					echo PHP_EOL . 'Phoromatic received a remote command to exit.' . PHP_EOL;
					phoromatic::update_system_status('Exiting Phoromatic');
					pts_client::release_lock(PTS_USER_PATH . 'phoromatic_lock');
					$exit_loop = true;
					break;
				case M_PHOROMATIC_RESPONSE_SERVER_MAINTENANCE:
					// The Phoromatic server is down for maintenance, so don't bother updating system status and wait longer before checking back
					echo PHP_EOL . 'The Phoromatic server is currently down for maintenance. Waiting for service to be restored.' . PHP_EOL;
					sleep((15 - (date('i') % 15)) * 60);
					break;
				case M_PHOROMATIC_RESPONSE_SHUTDOWN:
					echo PHP_EOL . 'Shutting down the system.' . PHP_EOL;
					$exit_loop = true;
					shell_exec('poweroff'); // Currently assuming root
					break;
				case M_PHOROMATIC_RESPONSE_REBOOT:
					echo PHP_EOL . 'Rebooting the system.' . PHP_EOL;
					$exit_loop = true;
					shell_exec('reboot'); // Currently assuming root
					break;
				case M_PHOROMATIC_RESPONSE_IDLE:
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

	//
	// Core Functions
	//

	public static function user_system_process()
	{
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
				$response = M_PHOROMATIC_RESPONSE_IDLE;
			}
			else
			{
				$server_response = phoromatic::upload_to_remote_server(array('r' => 'status_check'));

				$xml_parser = new nye_XmlReader($server_response);
				$response = $xml_parser->getXMLValue(M_PHOROMATIC_GEN_RESPONSE);

				if(date('i') != $last_communication_minute)
				{
					$last_communication_minute = date('i');
					$communication_attempts = 0;
				}

				$communication_attempts++;
			}

			echo ' [' . $response . ']' . PHP_EOL;

			switch($response)
			{
				case M_PHOROMATIC_RESPONSE_RUN_TEST:
					$test_flags = pts_c::auto_mode | pts_c::recovery_mode;

					do
					{
						$suite_identifier = 'phoromatic-' . rand(1000, 9999);
					}
					while(is_file(PTS_TEST_SUITE_PATH . 'local/' . $suite_identifier . '/suite-definition.xml'));

					file_put_contents(PTS_TEST_SUITE_PATH . 'local/' . $suite_identifier . '/suite-definition.xml', $server_response);

					$phoromatic_schedule_id = $xml_parser->getXMLValue(M_PHOROMATIC_ID);
					$phoromatic_results_identifier = $xml_parser->getXMLValue(M_PHOROMATIC_SYS_NAME);
					$phoromatic_trigger = $xml_parser->getXMLValue(M_PHOROMATIC_TRIGGER);

					if(pts_strings::string_bool($xml_parser->getXMLValue(M_PHOROMATIC_RUN_INSTALL_COMMAND, M_PHOROMATIC_RESPONSE_TRUE)))
					{
						phoromatic::set_user_context($xml_parser->getXMLValue(M_PHOROMATIC_SET_CONTEXT_PRE_INSTALL), $phoromatic_trigger, $phoromatic_schedule_id, 'INSTALL');
						pts_client::set_test_flags($test_flags);
						pts_test_installer::standard_install($suite_identifier);
					}

					phoromatic::set_user_context($xml_parser->getXMLValue(M_PHOROMATIC_SET_CONTEXT_PRE_RUN), $phoromatic_trigger, $phoromatic_schedule_id, 'INSTALL');


					// Do the actual running
					if(pts_test_run_manager::initial_checks($suite_identifier))
					{
						$test_run_manager = new pts_test_run_manager($test_flags);

						// Load the tests to run
						if($test_run_manager->load_tests_to_run($suite_identifier))
						{

							if(pts_strings::string_bool($xml_parser->getXMLValue(M_PHOROMATIC_UPLOAD_TO_GLOBAL, 'FALSE')))
							{
								$test_run_manager->auto_upload_to_openbenchmarking();
							}

							// Save results?
							$test_run_manager->auto_save_results(date('Y-m-d H:i:s'), $phoromatic_results_identifier, 'A Phoromatic run.');

							// Run the actual tests
							$test_run_manager->pre_execution_process();
							$test_run_manager->call_test_runs();
							$test_run_manager->post_execution_process();

							// Upload to Phoromatic
							pts_file_io::unlink(PTS_TEST_SUITE_PATH . 'local/' . $suite_identifier . '/suite-definition.xml');

							// Upload test results

							if(is_file(PTS_SAVE_RESULTS_PATH . $save_identifier . '/composite.xml'))
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

									$uploaded_test_results = phoromatic::upload_test_results($save_identifier, $phoromatic_schedule_id, $phoromatic_results_identifier, $phoromatic_trigger);
									$times_tried++;
								}
								while($uploaded_test_results == false && $times_tried < 5);

								if($uploaded_test_results == false)
								{
									echo 'Server connection failed. Exiting...' . PHP_EOL;
									return false;
								}

								if(pts_strings::string_bool($xml_parser->getXMLValue(M_PHOROMATIC_ARCHIVE_RESULTS_LOCALLY, M_PHOROMATIC_RESPONSE_TRUE)) == false)
								{
									pts_client::remove_saved_result_file($save_identifier);
								}
							}
						}
					}
					break;
				case M_PHOROMATIC_RESPONSE_EXIT:
					echo PHP_EOL . 'Phoromatic received a remote command to exit.' . PHP_EOL;
					phoromatic::update_system_status('Exiting Phoromatic');
					pts_client::release_lock(PTS_USER_PATH . 'phoromatic_lock');
					$exit_loop = true;
					break;
				case M_PHOROMATIC_RESPONSE_SERVER_MAINTENANCE:
					// The Phoromatic server is down for maintenance, so don't bother updating system status and wait longer before checking back
					echo PHP_EOL . 'The Phoromatic server is currently down for maintenance. Waiting for service to be restored.' . PHP_EOL;
					sleep((15 - (date('i') % 15)) * 60);
					break;
				case M_PHOROMATIC_RESPONSE_SHUTDOWN:
					echo PHP_EOL . 'Shutting down the system.' . PHP_EOL;
					$exit_loop = true;
					shell_exec('poweroff'); // Currently assuming root
					break;
				case M_PHOROMATIC_RESPONSE_REBOOT:
					echo PHP_EOL . 'Rebooting the system.' . PHP_EOL;
					$exit_loop = true;
					shell_exec('reboot'); // Currently assuming root
					break;
				case M_PHOROMATIC_RESPONSE_IDLE:
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
		self::$phoromatic_server_build = self::read_xml_value($server_response, M_PHOROMATIC_SERVER_BUILD);

		return self::read_xml_value($server_response, M_PHOROMATIC_GEN_RESPONSE) == M_PHOROMATIC_RESPONSE_TRUE;
	}
	protected static function update_system_status($current_task, $estimated_time_remaining = 0)
	{
		$server_response = phoromatic::upload_to_remote_server(array('r' => 'update_system_status', 'a' => $current_task, 'time' => $estimated_time_remaining));

		return self::read_xml_value($server_response, M_PHOROMATIC_GEN_RESPONSE) == M_PHOROMATIC_RESPONSE_TRUE;
	}
	protected static function report_warning_to_phoromatic($warning)
	{
		$server_response = phoromatic::upload_to_remote_server(array('r' => 'report_pts_warning', 'a' => $warning));

		return self::read_xml_value($server_response, M_PHOROMATIC_GEN_RESPONSE) == M_PHOROMATIC_RESPONSE_TRUE;
	}
	private static function capture_test_logs($save_identifier)
	{
		$data = array('system-logs' => null, 'test-logs' => null);

		if(is_dir(PTS_SAVE_RESULTS_PATH . $save_identifier . '/system-logs/'))
		{
			$system_logs_zip = pts_client::create_temporary_file();
			pts_compression::zip_archive_create($system_logs_zip, PTS_SAVE_RESULTS_PATH . $save_identifier . '/system-logs/');
			$data['system-logs'] = base64_encode(file_get_contents($system_logs_zip));
			unlink($system_logs_zip);
		}

		if(is_dir(PTS_SAVE_RESULTS_PATH . $save_identifier . '/test-logs/'))
		{
			$test_logs_zip = pts_client::create_temporary_file();
			pts_compression::zip_archive_create($test_logs_zip, PTS_SAVE_RESULTS_PATH . $save_identifier . '/test-logs/');
			$data['test-logs'] = base64_encode(file_get_contents($test_logs_zip));
			unlink($test_logs_zip);
		}

		return $data;
	}
	protected static function upload_test_results($save_identifier, $phoromatic_schedule_id, $results_identifier, $phoromatic_trigger)
	{
		$composite_xml = file_get_contents(PTS_SAVE_RESULTS_PATH . $save_identifier . '/composite.xml');

		$logs = self::capture_test_logs($save_identifier);
		$server_response = phoromatic::upload_to_remote_server(array(
			'r' => 'upload_test_results',
			'c' => $composite_xml,
			'i' => $phoromatic_schedule_id,
			'ti' => $results_identifier,
			'ts' => $phoromatic_trigger,
			'sl' => $logs['system-logs'],
			'tl' => $logs['test-logs']
			));

		return self::read_xml_value($server_response, M_PHOROMATIC_GEN_RESPONSE) == M_PHOROMATIC_RESPONSE_TRUE;
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

		switch($xml_parser->getXMLValue(M_PHOROMATIC_GEN_RESPONSE))
		{
			case M_PHOROMATIC_RESPONSE_TRUE:
				echo PHP_EOL . 'Uploaded To Phoromatic.' . PHP_EOL;
				break;
			case M_PHOROMATIC_RESPONSE_ERROR:
				echo PHP_EOL . 'An Error Occurred.' . PHP_EOL;
				break;
			case M_PHOROMATIC_RESPONSE_SETTING_DISABLED:
				echo PHP_EOL . 'You need to enable this support from your Phoromatic account web interface.' . PHP_EOL;
				break;
		}

		return $xml_parser->getXMLValue(M_PHOROMATIC_GEN_RESPONSE) == M_PHOROMATIC_RESPONSE_TRUE;
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


	//
	// Connection
	//

	protected static function make_server_request($to_post = array()) // previously was upload_to_remote_server()
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

		return pts_openbenchmarking::make_openbenchmarking_request('ekofisk', $to_post);
	}
}

?>
