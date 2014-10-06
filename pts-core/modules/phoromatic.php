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
	const module_version = '0.2.0';
	const module_description = 'The Phoromatic client is used for connecting to a Phoromatic server (Phoromatic.com or a locally run server) to facilitate the automatic running of tests, generally across multiple test nodes in a routine manner. For more details visit http://www.phoromatic.com/. This module is intended to be used with Phoronix Test Suite 5.2+ clients and servers.';
	const module_author = 'Phoronix Media';

	private static $account_id = null;
	private static $server_address = null;
	private static $server_http_port = null;
	private static $server_ws_port = null;
	private static $is_running_as_phoromatic_node = false;
	private static $log_file = null;

	public static function module_info()
	{
		return 'The Phoromatic module contains the client support for interacting with Phoromatic and Phoromatic Tracker services.';
	}
	public static function user_commands()
	{
		return array('connect' => 'run_connection', 'explore' => 'explore_network'); //explore => explore_network
	}
	public static function explore_network()
	{
		pts_client::$display->generic_heading('Phoromatic Servers');

		$archived_servers = pts_client::available_phoromatic_servers();
		foreach($archived_servers as $archived_server)
		{
			$response = pts_network::http_get_contents('http://' . $archived_server['ip'] . ':' . $archived_server['http_port'] . '/server.php?phoromatic_info');

			if(!empty($response))
			{
				$response = json_decode($response, true);
				if($response && isset($response['pts']))
				{
					echo PHP_EOL . 'IP: ' . $archived_server['ip'] . PHP_EOL;
					echo 'HTTP PORT: ' . $archived_server['http_port'] . PHP_EOL;
					echo 'WEBSOCKET PORT: ' . $response['ws_port'] . PHP_EOL;
					echo 'SERVER: ' . $response['http_server'] . PHP_EOL;
					echo 'PHORONIX TEST SUITE: ' . $response['pts'] . ' [' . $response['pts_core'] . ']' . PHP_EOL;
				}

				$repo = pts_network::http_get_contents('http://' . $archived_server['ip'] . ':' . $archived_server['http_port'] . '/download-cache.php?repo');
				echo 'DOWNLOAD CACHE: ';
				if(!empty($repo))
				{
					$repo = json_decode($repo, true);
					if($repo && isset($repo['phoronix-test-suite']['download-cache']))
					{
						$total_file_size = 0;
						foreach($repo['phoronix-test-suite']['download-cache'] as $file_name => $inf)
						{
							$total_file_size += $repo['phoronix-test-suite']['download-cache'][$file_name]['file_size'];
						}
						echo count($repo['phoronix-test-suite']['download-cache']) . ' FILES / ' . round($total_file_size / 1000000) . ' MB CACHE SIZE';
					}
				}
				else
				{
					echo 'N/A';
				}
				echo PHP_EOL;

				$repo = pts_network::http_get_contents('http://' . $archived_server['ip'] . ':' . $archived_server['http_port'] . '/openbenchmarking-cache.php?repos');
				echo 'SUPPORTED OPENBENCHMARKING.ORG REPOSITORIES:' . PHP_EOL;
				if(!empty($repo))
				{
					$repo = json_decode($repo, true);
					if($repo && is_array($repo['repos']))
					{
						foreach($repo['repos'] as $data)
						{
							echo '      ' . $data['title'] . ' - Last Generated: ' . date('d M Y H:i', $data['generated']) . PHP_EOL;
						}
					}
				}
				else
				{
					echo '      N/A' . PHP_EOL;
				}
			}
		}
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
		$to_post['gsid'] = defined('PTS_GSID') ? PTS_GSID : null;
		$to_post['lip'] = pts_network::get_local_ip();
		$to_post['h'] = phodevi::system_hardware(true);
		$to_post['s'] = phodevi::system_software(true);
		$to_post['n'] = phodevi::read_property('system', 'hostname');
		$to_post['msi'] = PTS_MACHINE_SELF_ID;
		return pts_network::http_upload_via_post('http://' . $server_address . ':' . $server_http_port .  '/phoromatic.php', $to_post);
	}
	protected static function update_system_status($current_task, $estimated_time_remaining = 0)
	{
		static $last_msg = null;

		// Avoid an endless flow of "idling" messages, etc
		if($current_task != $last_msg)
			pts_client::$pts_logger->log($current_task);
		$last_msg = $current_task;

		return $server_response = phoromatic::upload_to_remote_server(array(
				'r' => 'update_system_status',
				'a' => $current_task,
				'time' => $estimated_time_remaining
				));
	}
	public static function startup_ping_check($server_ip, $http_port)
	{
		$server_response = phoromatic::upload_to_remote_server(array(
				'r' => 'ping',
				), $server_ip, $http_port);
	}
	public static function run_connection($args)
	{
		if(pts_client::create_lock(PTS_USER_PATH . 'phoromatic_lock') == false)
		{
			trigger_error('Phoromatic is already running.', E_USER_ERROR);
			return false;
		}

		if(pts_client::$pts_logger == false)
		{
			pts_client::$pts_logger = new pts_logger();
		}
		pts_client::$pts_logger->log(pts_title(true) . ' starting Phoromatic client');

		if(isset($args[0]) && $args[0] && strpos($args[0], '/', strpos($args[0], ':')) > 6)
		{
			pts_client::$pts_logger->log('Attempting to connect to Phoromatic Server: ' . $args[0]);
			self::$account_id = substr($args[0], strrpos($args[0], '/') + 1);
			self::$server_address = substr($args[0], 0, strpos($args[0], ':'));
			self::$server_http_port = substr($args[0], strlen(self::$server_address) + 1, -1 - strlen(self::$account_id));
			pts_client::$display->generic_heading('Server IP: ' . self::$server_address . PHP_EOL . 'Server HTTP Port: ' . self::$server_http_port . PHP_EOL . 'Account ID: ' . self::$account_id);
		}
		else
		{
			pts_client::$pts_logger->log('Attempting to auto-discover Phoromatic Server');
			$archived_servers = pts_client::available_phoromatic_servers();
			foreach($archived_servers as $archived_server)
			{
				$server_response = phoromatic::upload_to_remote_server(array(
					'r' => 'ping',
					), $archived_server['ip'], $archived_server['http_port']);

				$server_response = json_decode($server_response, true);
				if($server_response && isset($server_response['phoromatic']['account_id']))
				{
					self::$server_address = $archived_server['ip'];
					self::$server_http_port = $archived_server['http_port'];
					self::$account_id = $server_response['phoromatic']['account_id'];
				}
			}
		}

		if(self::$server_address == null || self::$server_http_port == null || self::$account_id == null)
		{
			echo PHP_EOL . 'You must pass the Phoromatic Server information as an argument to phoromatic.connect, or otherwise configure your network setup.' . PHP_EOL . '      e.g. phoronix-test-suite phoromatic.connect 192.168.1.2:5555/I0SSJY' . PHP_EOL . PHP_EOL;
			return false;
		}

		$times_failed = 0;

		while(1)
		{
			$server_response = phoromatic::upload_to_remote_server(array(
				'r' => 'start',
				));

			if($server_response == false)
			{
				$times_failed++;

				if($times_failed > 2)
				{
					pts_client::$pts_logger->log('Communication attempt to server failed');
					trigger_error('Communication with server failed.', E_USER_ERROR);
					return false;
				}
			}
			else if(substr($server_response, 0, 1) == '{')
			{
				$times_failed = 0;
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

				switch(isset($json['phoromatic']['task']) ? $json['phoromatic']['task'] : null)
				{
					case 'benchmark':
						self::$is_running_as_phoromatic_node = true;
						$test_flags = pts_c::auto_mode | pts_c::batch_mode;
						$suite_identifier = sha1(time() . rand(0, 100));
						pts_suite_nye_XmlReader::set_temporary_suite($suite_identifier, $json['phoromatic']['test_suite']);
						$phoromatic_schedule_id = $json['phoromatic']['trigger_id'];
						$phoromatic_results_identifier = $phoromatic_schedule_id;
						$phoromatic_save_identifier = $json['phoromatic']['save_identifier'];
						$phoromatic_trigger = $phoromatic_schedule_id;
						phoromatic::update_system_status('Running Benchmarks For Schedule: ' . $phoromatic_save_identifier . ' - ' . $phoromatic_schedule_id);

						if($json['phoromatic']['settings']['RunInstallCommand'])
						{
							phoromatic::set_user_context($json['phoromatic']['pre_install_set_context'], $phoromatic_trigger, $phoromatic_schedule_id, 'PRE_INSTALL');

							if($json['phoromatic']['settings']['ForceInstallTests'])
							{
								$test_flags |= pts_c::force_install;
							}

							pts_client::set_test_flags($test_flags);
							pts_test_installer::standard_install($suite_identifier);
							phoromatic::set_user_context($json['phoromatic']['post_install_set_context'], $phoromatic_trigger, $phoromatic_schedule_id, 'POST_INSTALL');
						}

						// Do the actual running
						if(pts_test_run_manager::initial_checks($suite_identifier))
						{
							$test_run_manager = new pts_test_run_manager($test_flags);
							pts_test_run_manager::set_batch_mode(array(
								'UploadResults' => false, // TODO XXX: give option back on Phoromatic web UI whether to upload results to OpenBenchmarking.org global too...
								'SaveResults' => true,
								'RunAllTestCombinations' => false,
								'OpenBrowser' => false
								));

							// Load the tests to run
							if($test_run_manager->load_tests_to_run($suite_identifier))
							{
								phoromatic::set_user_context($json['phoromatic']['pre_run_set_context'], $phoromatic_trigger, $phoromatic_schedule_id, 'PRE_RUN');
								if(false)
								{
									$test_run_manager->auto_upload_to_openbenchmarking();
									pts_openbenchmarking_client::override_client_setting('UploadSystemLogsByDefault', $json['phoromatic']['settings']['UploadSystemLogs']);
								}

								// Save results?
								$test_run_manager->auto_save_results($phoromatic_save_identifier, $phoromatic_results_identifier, 'A Phoromatic run.');

								// Run the actual tests
								$test_run_manager->pre_execution_process();
								$test_run_manager->call_test_runs();
								phoromatic::update_system_status('Benchmarks Completed For Schedule: ' . $phoromatic_save_identifier . ' - ' . $phoromatic_schedule_id);
								$test_run_manager->post_execution_process();

								// Handle uploading data to server
								$result_file = new pts_result_file($test_run_manager->get_file_name());
								$composite_xml = $result_file->xml_parser->getXML();
								$system_log_dir = PTS_SAVE_RESULTS_PATH . $result_file->get_identifier() . '/system-logs/';

								$upload_system_logs = $json['phoromatic']['settings']['UploadSystemLogs'];

								// TODO: Potentially integrate this code below shared with pts_openbenchmarking_client into a unified function for validating system log files
								$system_logs = null;
								$system_logs_hash = null;
								if(is_dir($system_log_dir) && $upload_system_logs)
								{
									$is_valid_log = true;
									$finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;

									foreach(pts_file_io::glob($system_log_dir . '*') as $log_dir)
									{
										if($is_valid_log == false || !is_dir($log_dir))
										{
											$is_valid_log = false;
											break;
										}

										foreach(pts_file_io::glob($log_dir . '/*') as $log_file)
										{
											if(!is_file($log_file))
											{
												$is_valid_log = false;
												break;
											}

											if($finfo && substr(finfo_file($finfo, $log_file), 0, 5) != 'text/')
											{
												$is_valid_log = false;
												break;
											}
										}
									}

									if($is_valid_log)
									{
										$system_logs_zip = pts_client::create_temporary_file();
										pts_compression::zip_archive_create($system_logs_zip, $system_log_dir);

										if(filesize($system_logs_zip) < 2097152)
										{
											// If it's over 2MB, probably too big
											$system_logs = base64_encode(file_get_contents($system_logs_zip));
											$system_logs_hash = sha1($system_logs);
										}
										else
										{
										//	trigger_error('The systems log attachment is too large to upload to OpenBenchmarking.org.', E_USER_WARNING);
										}

										unlink($system_logs_zip);
									}
								}

								$composite_xml_hash = sha1($composite_xml);
								$composite_xml_type = 'composite_xml';

								// Compress the result file XML if it's big
								if(isset($composite_xml[50000]) && function_exists('gzdeflate'))
								{
									$composite_xml_gz = gzdeflate($composite_xml);

									if($composite_xml_gz != false)
									{
										$composite_xml = $composite_xml_gz;
										$composite_xml_type = 'composite_xml_gz';
									}
								}

								// Upload to Phoromatic
								$server_response = phoromatic::upload_to_remote_server(array(
									'r' => 'result_upload',
									//'ob' => $ob_data['id'],
									'sched' => $json['phoromatic']['schedule_id'],
									'o' => $phoromatic_save_identifier,
									'ts' => $json['phoromatic']['trigger_id'],
									$composite_xml_type => base64_encode($composite_xml),
									'composite_xml_hash' => $composite_xml_hash,
									'system_logs_zip' => $system_logs,
									'system_logs_hash' => $system_logs_hash
									));
								pts_client::$pts_logger->log('XXX TEMP DEBUG MESSAGE: ' . $server_response);
								if(!$json['phoromatic']['settings']['ArchiveResultsLocally'])
								{
									pts_client::remove_saved_result_file($test_run_manager->get_file_name());
								}
							}
							phoromatic::set_user_context($json['phoromatic']['post_install_set_context'], $phoromatic_trigger, $phoromatic_schedule_id, 'POST_RUN');
						}
						self::$is_running_as_phoromatic_node = false;
					break;
					case 'exit':
						echo PHP_EOL . 'Phoromatic received a remote command to exit.' . PHP_EOL;
						phoromatic::update_system_status('Exiting Phoromatic');
					break;
				}
			}
			phoromatic::update_system_status('Idling, Waiting For Task');
			sleep(60);
		}
		pts_client::release_lock(PTS_USER_PATH . 'phoromatic_lock');
	}
	private static function set_user_context($context_script, $trigger, $schedule_id, $process)
	{
		if(!empty($context_script))
		{
			$context_file = pts_client::create_temporary_file();
			file_put_contents($context_file, $context_script);
			chmod($context_file, 0755);

			pts_file_io::mkdir(pts_module::save_dir());
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
			exec('./' . $context_script . ' ' . $trigger);

			// Just simply return true for now, perhaps check exit code status and do something
			return true;
		}

		return false;
	}
	public static function __pre_test_install($test_identifier)
	{
		if(!self::$is_running_as_phoromatic_node)
		{
			return false;
		}

		static $last_update_time = 0;

		if(time() > ($last_update_time + 600))
		{
			phoromatic::update_system_status('Installing Tests');
			$last_update_time = time();
		}
	}
	public static function __pre_test_run($pts_test_result)
	{
		if(!self::$is_running_as_phoromatic_node)
		{
			return false;
		}
		// TODO: need a way to get the estimated time remaining from the test_run_manager so we can pass that back to the update_system_status parameter so server can read it
		// TODO: report name of test identifier/run i.e. . ' For ' . PHOROMATIC_TITLE
		phoromatic::update_system_status('Running ' . $pts_test_result->test_profile->get_identifier());
	}
	public static function __event_user_error($user_error)
	{
		if(!self::$is_running_as_phoromatic_node)
		{
			return false;
		}
		// Report PTS user error warnings to Phoromatic server
		phoromatic::report_warning_to_phoromatic($user_error->get_error_string());
	}
	public static function __event_results_saved($test_run_manager)
	{
		/*if(pts_module::read_variable('AUTO_UPLOAD_RESULTS_TO_PHOROMATIC') && pts_module::is_module_setup())
		{
			phoromatic::upload_unscheduled_test_results($test_run_manager->get_file_name());
		}*/
	}

	//
	// TODO XXX: The code below here is Phoromatic legacy code still needing to be ported to the new interfaces of PTS 5.2 Khanino
	//

	//
	// User Run Commands
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
					$test_flags = pts_c::auto_mode | pts_c::batch_mode;

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
						pts_test_run_manager::set_batch_mode(array(
							'UploadResults' => true,
							'SaveResults' => true,
							'RunAllTestCombinations' => false,
							'OpenBrowser' => false
							));

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

	//
	// Other Functions
	//

	protected static function read_xml_value($file, $xml_option)
	{
		$xml_parser = new nye_XmlReader($file);
		return $xml_parser->getXMLValue($xml_option);
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
}

?>
