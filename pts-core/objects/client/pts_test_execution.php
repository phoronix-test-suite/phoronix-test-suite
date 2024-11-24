<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2022, Phoronix Media
	Copyright (C) 2008 - 2022, Michael Larabel

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

class pts_test_execution
{
	protected static $test_run_error_collection;

	protected static function test_run_error(&$test_run_manager, &$test_run_request, $error_msg)
	{
		$error_obj = array($test_run_manager, $test_run_request, $error_msg);
		pts_module_manager::module_process('__event_run_error', $error_obj);
		pts_client::$display->test_run_error($error_msg);
	}
	protected static function test_run_instance_error(&$test_run_manager, &$test_run_request, $error_msg)
	{
		$error_obj = array($test_run_manager, $test_run_request, $error_msg);
		pts_module_manager::module_process('__event_run_error', $error_obj);
		pts_client::$display->test_run_instance_error($error_msg);
		self::$test_run_error_collection[] = $error_msg;
	}
	public static function run_test(&$test_run_manager, &$test_run_request)
	{
		self::$test_run_error_collection = array();
		$test_identifier = $test_run_request->test_profile->get_identifier();
		$extra_arguments = $test_run_request->get_arguments();

		// Do the actual test running process
		$test_directory = $test_run_request->test_profile->get_install_dir();
		if(phodevi::is_windows())
		{
			$test_directory = str_replace(array('//', '/', '\\\\'), DIRECTORY_SEPARATOR, $test_directory);
		}

		if(!is_dir($test_directory))
		{
			return false;
		}

		$error = null;
		if(pts_test_run_options::validate_test_arguments_compatibility($test_run_request->get_arguments_description(), $test_run_request->test_profile, $error) == false)
		{
			self::test_run_error($test_run_manager, $test_run_request, '[' . $test_run_request->test_profile->get_identifier() . ' ' . $test_run_request->get_arguments_description() . '] ' . $error);
			return false;
		}
		$lock_file = $test_directory . 'run_lock';
		if(pts_client::create_lock($lock_file) == false && $test_run_manager->is_multi_test_stress_run() == false)
		{
			self::test_run_error($test_run_manager, $test_run_request, 'The ' . $test_identifier . ' test is already running.');
			return false;
		}

		$test_run_request->active = new pts_test_result_buffer_active();
		$test_run_request->generated_result_buffers = array();
		$execute_binary = $test_run_request->test_profile->get_test_executable();
		$times_to_run = $test_run_request->test_profile->get_times_to_run();
		$ignore_runs = $test_run_request->test_profile->get_runs_to_ignore();
		$ignore_runs_override = ($ir = pts_env::read('IGNORE_RUNS')) ? pts_strings::comma_explode($ir) : array();
		$test_type = $test_run_request->test_profile->get_test_hardware_type();
		$allow_cache_share = $test_run_request->test_profile->allow_cache_share() && $test_run_manager->allow_test_cache_share();
		$min_length = $test_run_request->test_profile->get_min_length();
		$max_length = $test_run_request->test_profile->get_max_length();
		$is_monitoring = false;

		if($test_run_request->test_profile->get_environment_testing_size() > 1 && ceil(disk_free_space($test_directory) / 1048576) < $test_run_request->test_profile->get_environment_testing_size())
		{
			// Ensure enough space is available on disk during testing process
			self::test_run_error($test_run_manager, $test_run_request, 'There is not enough space (at ' . $test_directory . ') for this test to run.');
			pts_client::release_lock($lock_file);
			return false;
		}

		$to_execute = $test_run_request->test_profile->get_test_executable_dir();
		$pts_test_arguments = trim($test_run_request->test_profile->get_default_arguments() . ' ' . ($test_run_request->test_profile->get_default_arguments() != null && !empty($extra_arguments) ? str_replace($test_run_request->test_profile->get_default_arguments(), '', $extra_arguments) : $extra_arguments) . ' ' . $test_run_request->test_profile->get_default_post_arguments());
		$extra_runtime_variables = pts_tests::extra_environment_variables($test_run_request->test_profile);

		pts_triggered_system_events::pre_run_reboot_triggered_check($test_run_request->test_profile, $extra_runtime_variables);

		// Start
		$cache_share_pt2so = $test_directory . 'cache-share-' . PTS_INIT_TIME . '.pt2so';
		$cache_share_present = $allow_cache_share && is_file($cache_share_pt2so);
		pts_module_manager::module_process('__pre_test_run', $test_run_request);

		$time_test_start = microtime(true);
		pts_client::$display->test_run_start($test_run_manager, $test_run_request);
		sleep(2);

		if(!$cache_share_present && !$test_run_manager->DEBUG_no_test_execution_just_result_parse)
		{
			$pre_output = pts_tests::call_test_script($test_run_request->test_profile, 'pre', 'Running Pre-Test Script', $pts_test_arguments, $extra_runtime_variables, true);

			if($pre_output != null)
			{
				pts_client::$display->test_run_instance_output($pre_output);
			}
			if(is_file($test_directory . 'pre-test-exit-status'))
			{
			  // If the pre script writes its exit status to ~/pre-test-exit-status, if it's non-zero the test run failed
			  $exit_status = pts_file_io::file_get_contents($test_directory . 'pre-test-exit-status');
			  unlink($test_directory . 'pre-test-exit-status');

			  if($exit_status != 0)
			  {
					self::test_run_instance_error($test_run_manager, $test_run_request, 'The pre run script quit with a non-zero exit status.' . PHP_EOL);
					self::test_run_error($test_run_manager, $test_run_request, 'This test execution has been abandoned.');
					return false;
			  }
			}
		}

		pts_client::$display->display_interrupt_message($test_run_request->test_profile->get_pre_run_message());
		$runtime_identifier = time();
		$execute_binary_prepend = '';
		$execute_binary_prepend_final = './';
		if($test_run_request->exec_binary_prepend != null)
		{
			$execute_binary_prepend = $test_run_request->exec_binary_prepend;
		}
		else if(pts_env::read('EXECUTE_BINARY_PREPEND') != false)
		{
			// This should be very similar behavior to the TEST_EXEC_PREPEND env var, but bug 807 raised that it was dropped
			$execute_binary_prepend = pts_env::read('EXECUTE_BINARY_PREPEND') . ' ';
		}

		if(!$cache_share_present && !$test_run_manager->DEBUG_no_test_execution_just_result_parse && $test_run_request->test_profile->is_root_required())
		{
			if(phodevi::is_root() == false)
			{
				pts_client::$display->test_run_error('This test must be run as root / administrator.');
			}

			$execute_binary_prepend .= ' ' . PTS_CORE_STATIC_PATH . 'root-access.sh ';
		}

		if($allow_cache_share && !is_file($cache_share_pt2so))
		{
			$cache_share = new pts_storage_object(false, false);
		}

		$backup_test_log_file = false;
		if($test_run_manager->do_save_results() && $test_run_manager->get_file_name() != null && pts_config::read_bool_config('PhoronixTestSuite/Options/Testing/SaveTestLogs', 'TRUE'))
		{
			$backup_test_log_dir = $test_run_manager->result_file->get_test_log_dir($test_run_request);
			if($backup_test_log_dir)
			{
				pts_file_io::mkdir($backup_test_log_dir, 0777, true);
				$backup_test_log_file = $backup_test_log_dir . $test_run_manager->get_results_identifier_simplified() . '.log';
			}
		}

		//
		// THE MAIN TESTING LOOP
		//

		for($i = 0, $times_result_produced = 0, $abort_testing = false, $time_test_start_actual = microtime(true), $defined_times_to_run = $times_to_run; $i < $times_to_run && $i < 256 && !$abort_testing; $i++)
		{
			if($test_run_manager->DEBUG_no_test_execution_just_result_parse)
			{
				$find_log_file = pts_file_io::glob($test_directory . basename($test_identifier) . '-*.log');
				if(!empty($find_log_file))
				{
					if(!isset($find_log_file[0]) || empty($find_log_file[0]))
					{
						pts_test_result_parser::debug_message('No existing log file found for this test profile. Generate one by first trying the debug-run command.');
						return false;
					}

					$test_log_file = $find_log_file[0];
					pts_test_result_parser::debug_message('Log File: ' . $test_log_file);
				}
				else
				{
					pts_test_result_parser::debug_message('No existing log file found for this test profile. Generate one by first trying the debug-run command.');
					return false;
				}
			}
			else if(phodevi::is_windows() && strpos($test_directory, ' ') !== false)
			{
				// On Windows systems with a space in the directory, to workaround some scripts easiest just punting the log file into temp dir
				$test_log_file = sys_get_temp_dir() . '\\' . basename($test_identifier) . '-' . $runtime_identifier . '-' . ($i + 1) . '.log';
			}
			else
			{
				$test_log_file = $test_directory . basename($test_identifier) . '-' . $runtime_identifier . '-' . ($i + 1) . '.log';
			}

			$is_expected_last_run = ($i == ($times_to_run - 1));
			$produced_monitoring_result = false;
			$has_result = false;

			$test_extra_runtime_variables = array_merge($extra_runtime_variables, array(
			'LOG_FILE' => $test_log_file,
			'DISPLAY' => getenv('DISPLAY'),
			'PATH' => pts_client::get_path(),
			'DEBUG_PATH' => pts_client::get_path(),
			));

			$restored_from_cache = false;
			if($cache_share_present)
			{
				$cache_share = pts_storage_object::recover_from_file($cache_share_pt2so);

				if($cache_share)
				{
					$test_result_std_output = $cache_share->read_object('test_results_output_' . $i);
					$test_extra_runtime_variables['LOG_FILE'] = $cache_share->read_object('log_file_location_' . $i);

					if($test_extra_runtime_variables['LOG_FILE'] != null)
					{
						file_put_contents($test_extra_runtime_variables['LOG_FILE'], $cache_share->read_object('log_file_' . $i));
						$test_run_time = 0; // This wouldn't be used for a cache share since it would always be the same, but declare the value so the variable is at least initialized
						$restored_from_cache = true;
					}
				}

				unset($cache_share);
			}

			if(!$test_run_manager->DEBUG_no_test_execution_just_result_parse && $restored_from_cache == false)
			{
				if(!phodevi::is_windows() && is_file($to_execute . '/' . $execute_binary) && !is_executable($to_execute . '/' . $execute_binary) && pts_client::executable_in_path('chmod'))
				{
					shell_exec('chmod +x ' . $to_execute . '/' . $execute_binary);
				}

				$test_prepend = pts_env::read('TEST_EXEC_PREPEND') != null ? pts_env::read('TEST_EXEC_PREPEND') . ' ': null;
				pts_client::$display->test_run_instance_header($test_run_request);
				sleep(2);

				$host_env = $_SERVER;
				unset($host_env['argv']);
				$to_exec = 'exec';
				$post_test_args = ' 2>&1';
				if(phodevi::is_windows())
				{
					if(is_executable('C:\Windows\System32\cmd.exe') && (pts_file_io::file_get_contents_first_line($to_execute . '/' . $execute_binary) == '@echo off' || substr($execute_binary, -4) == '.bat'))
					{
						pts_client::$display->test_run_message('Using cmd.exe batch...');
						$to_exec = 'C:\Windows\System32\cmd.exe';
						$execute_binary_prepend = ' /c ';
						$execute_binary_prepend_final = '';
						$post_test_args = '';
					}
					else if(is_executable('C:\cygwin64\bin\bash.exe'))
					{
						$to_exec = 'C:\cygwin64\bin\bash.exe';
						$test_extra_runtime_variables['PATH'] = (isset($test_extra_runtime_variables['PATH']) ? $test_extra_runtime_variables['PATH'] : null) . ';C:\cygwin64\bin';
					}
					else
					{
						$execute_binary = '"' . $execute_binary . '"';
					}
				}

				pts_test_result_parser::debug_message('Test Run Directory: ' . $to_execute);
				pts_test_result_parser::debug_message('Test Run Command: ' . $test_prepend . $execute_binary_prepend . $execute_binary_prepend_final . $execute_binary . ' ' . $pts_test_arguments);
				$is_monitoring = pts_test_result_parser::system_monitor_task_check($test_run_request);
				$test_run_time_start = microtime(true);

				$descriptorspec = array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w'));

				if($test_prepend != null && pts_client::executable_in_path(trim($test_prepend)))
				{
					$to_exec = '';
				}

				$terv = $test_extra_runtime_variables;
				if(phodevi::is_windows())
				{
					foreach($terv as $terv_i => &$value)
					{
						if((is_dir($value) || is_file($value) || $terv_i == 'LOG_FILE') && strpos($value, ' ') !== false)
						{
							$value = '"' . $value . '"';
						}
					}
				}

				pts_module_manager::module_process('__calling_test_script', $test_run_request);
				$test_process = proc_open($test_prepend . $to_exec . ' ' . $execute_binary_prepend . $execute_binary_prepend_final . $execute_binary . ' ' . $pts_test_arguments . $post_test_args, $descriptorspec, $pipes, $to_execute, array_merge($host_env, pts_client::environment_variables(), $terv));

				if(is_resource($test_process))
				{
					//echo proc_get_status($test_process)['pid'];
					pts_module_manager::module_process('__test_running', $test_process);
					$test_result_std_output = stream_get_contents($pipes[1]);
					fclose($pipes[1]);
					fclose($pipes[2]);
					$return_value = proc_close($test_process);
				}

				$test_run_time = microtime(true) - $test_run_time_start;
				$test_run_request->test_run_times[] = pts_math::set_precision($test_run_time, 2);

				$exit_status_pass = true;
				if(is_file($test_directory . 'test-exit-status'))
				{
					// If the test script writes its exit status to ~/test-exit-status, if it's non-zero the test run failed
					$exit_status = pts_file_io::file_get_contents($test_directory . 'test-exit-status');
					unlink($test_directory . 'test-exit-status');

					if($exit_status != 0)
					{
						//self::test_run_instance_error($test_run_manager, $test_run_request, 'The test quit with a non-zero exit status.');
						$exit_status_pass = false;
					}
				}

				$produced_monitoring_result = $is_monitoring ? pts_test_result_parser::system_monitor_task_post_test($test_run_request, $exit_status_pass && !in_array(($i + 1), $ignore_runs) && !in_array(($i + 1), $ignore_runs_override)) : false;
			}
			else
			{
				if($i == 1) // to only display once
				{
					pts_client::$display->test_run_message('Utilizing Data From Shared Cache');
				}
				$test_run_time = 0;
				$exit_status_pass = true;
			}

			pts_triggered_system_events::post_run_reboot_triggered_check($test_run_request->test_profile);

			if($test_result_std_output != null && !isset($test_result_std_output[10240]))
			{
				pts_client::$display->test_run_instance_output($test_result_std_output);
			}

			if(is_file($test_log_file) && filesize($test_log_file) == 0)
			{
				unlink($test_log_file);
			}
			if(is_file($test_log_file) && trim($test_result_std_output) == null)
			{
				$test_log_file_contents = file_get_contents($test_log_file);
				pts_client::$display->test_run_instance_output($test_log_file_contents);
				unset($test_log_file_contents);
			}

			if($exit_status_pass == false)
			{
				// If the test script writes its exit status to ~/test-exit-status, if it's non-zero the test run failed
				self::test_run_instance_error($test_run_manager, $test_run_request, 'The test quit with a non-zero exit status.');

				if($is_expected_last_run)
				{
					$scan_log = is_file($test_log_file) ? pts_file_io::file_get_contents($test_log_file) : $test_result_std_output;
					$test_run_error = pts_tests::scan_for_error($scan_log, $test_run_request->test_profile->get_test_executable_dir());

					if($test_run_error)
					{
						self::test_run_instance_error($test_run_manager, $test_run_request, 'E: ' . $test_run_error);
					}
				}
			}
			if($defined_times_to_run > 1 && in_array(($i + 1), $ignore_runs))
			{
				pts_client::$display->test_run_instance_error('Ignoring this run result per test profile definition.');
			}
			else if($i == 0 && $test_run_request->test_profile->is_root_required() && !phodevi::is_root() && $is_monitoring)
			{
				// Useful for test profiles like system/wireguard that are timed test but can be interrupted by sudo
				pts_client::$display->test_run_instance_error('Ignoring first run in case root/sudo transition time skewed result.');
			}
			else if(in_array(($i + 1), $ignore_runs_override))
			{
				pts_client::$display->test_run_instance_error('Ignoring this run result per IGNORE_RUNS environment variable.');
			}
			else if($exit_status_pass)
			{
				// if it was monitoring, active result should already be set
				if(!$produced_monitoring_result) // XXX once single-run-multiple-outputs is supported, this check can be disabled to allow combination of results
				{
					$has_result = pts_test_result_parser::parse_result($test_run_request, $test_extra_runtime_variables['LOG_FILE']);
				}

				$has_result = $has_result || $produced_monitoring_result;

				if($has_result)
				{
					$times_result_produced++;
					if($test_run_time < 2 && $test_run_request->get_estimated_run_time() > 60 && !$restored_from_cache && !$test_run_manager->DEBUG_no_test_execution_just_result_parse)
					{
						// If the test ended in less than two seconds, outputted some int, and normally the test takes much longer, then it's likely some invalid run
						pts_client::$display->test_run_instance_error('The test run ended quickly.');
						if($is_expected_last_run)
						{
							$scan_log = is_file($test_log_file) ? pts_file_io::file_get_contents($test_log_file) : $test_result_std_output;
							$test_run_error = pts_tests::scan_for_error($scan_log, $test_run_request->test_profile->get_test_executable_dir());

							if($test_run_error)
							{
								self::test_run_instance_error($test_run_manager, $test_run_request, 'E: ' . $test_run_error);
							}
						}
					}
				}
				else if($test_run_request->test_profile->get_display_format() != 'NO_RESULT')
				{
					self::test_run_instance_error($test_run_manager, $test_run_request, 'The test run did not produce a result.');
					if($is_expected_last_run)
					{
						$scan_log = is_file($test_log_file) ? pts_file_io::file_get_contents($test_log_file) : $test_result_std_output;
						$test_run_error = pts_tests::scan_for_error($scan_log, $test_run_request->test_profile->get_test_executable_dir());

						if($test_run_error)
						{
							self::test_run_instance_error($test_run_manager, $test_run_request, 'E: ' . $test_run_error);
						}
					}
				}

				if($allow_cache_share && !is_file($cache_share_pt2so))
				{
					$cache_share->add_object('test_results_output_' . $i, $test_result_std_output);
					$cache_share->add_object('log_file_location_' . $i, $test_extra_runtime_variables['LOG_FILE']);
					$cache_share->add_object('log_file_' . $i, (is_file($test_log_file) ? file_get_contents($test_log_file) : null));
				}
			}

			if($is_expected_last_run && $times_result_produced > floor(($i - 2) / 2) && !$cache_share_present && !$test_run_manager->DEBUG_no_test_execution_just_result_parse && $test_run_manager->do_dynamic_run_count())
			{
				// The later check above ensures if the test is failing often the run count won't uselessly be increasing
				// Should we increase the run count?
				$increase_run_count = false;
				$runs_ignored_count = count($ignore_runs);

				if($defined_times_to_run == ($i + 1) && $times_result_produced > 0 && $times_result_produced < $defined_times_to_run && $i < 64)
				{
					// At least one run passed, but at least one run failed to produce a result. Increase count to try to get more successful runs
					$increase_run_count = $defined_times_to_run - $times_result_produced;
				}
				else if($times_result_produced >= 2)
				{
					// Dynamically increase run count if needed for statistical significance or other reasons
					$first_tr = array_slice($test_run_request->generated_result_buffers, 0, 1);
					$first_tr = array_shift($first_tr);
					$increase_run_count = $test_run_manager->increase_run_count_check($test_run_request, $first_tr->active, $defined_times_to_run, $time_test_start_actual); // XXX maybe check all generated buffers to see if to extend?

					if($increase_run_count === -1)
					{
						self::test_run_error($test_run_manager, $test_run_request, 'This run will not be saved due to noisy result.');
						$abort_testing = true;
					}
					else if($increase_run_count == true)
					{
						// Just increase the run count one at a time
						$increase_run_count = 1;
					}
				}

				if($increase_run_count > 0)
				{
					$times_to_run += $increase_run_count;
					$is_expected_last_run = false;
					//$test_run_request->test_profile->set_times_to_run($times_to_run);
				}
			}

			if($times_to_run > 1 && $i < ($times_to_run - 1))
			{
				if($cache_share_present == false && !$test_run_manager->DEBUG_no_test_execution_just_result_parse)
				{
					$interim_output = pts_tests::call_test_script($test_run_request->test_profile, 'interim', 'Running Interim Test Script', $pts_test_arguments, $extra_runtime_variables, true);

					if($interim_output != null)
					{
						pts_client::$display->test_run_instance_output($interim_output);
					}
					sleep(2); // Rest for a moment between tests
				}

				pts_module_manager::module_process('__interim_test_run', $test_run_request);
			}

			if(is_file($test_log_file))
			{
				if($is_expected_last_run)
				{
					// For now just passing the last test log file...
					pts_test_result_parser::generate_extra_data($test_run_request, $test_log_file);
				}
				pts_module_manager::module_process('__test_log_output', $test_log_file);
				if($backup_test_log_file)
				{
					file_put_contents($backup_test_log_file, '#####' . PHP_EOL . $test_run_manager->get_results_identifier() . ' - Run ' . ($i + 1) . PHP_EOL . date('Y-m-d H:i:s') . PHP_EOL . '#####' . PHP_EOL . file_get_contents($test_log_file) . PHP_EOL, FILE_APPEND);
				}

				if(pts_test_result_parser::debug_message('Log File At: ' . $test_log_file) == false)
				{
					unlink($test_log_file);
				}
			}

			if(is_file(PTS_USER_PATH . 'halt-testing') || is_file(PTS_USER_PATH . 'skip-test'))
			{
				break;
			}

			pts_client::$display->test_run_instance_complete($test_run_request);
		}

		$time_test_end_actual = microtime(true);

		if($cache_share_present == false && !$test_run_manager->DEBUG_no_test_execution_just_result_parse)
		{
			$post_output = pts_tests::call_test_script($test_run_request->test_profile, 'post', 'Running Post-Test Script', $pts_test_arguments, $extra_runtime_variables, true);

			if($post_output != null)
			{
				pts_client::$display->test_run_instance_output($post_output);
			}
			if(is_file($test_directory . 'post-test-exit-status'))
			{
				// If the post script writes its exit status to ~/post-test-exit-status, if it's non-zero the test run failed
				$exit_status = pts_file_io::file_get_contents($test_directory . 'post-test-exit-status');
				unlink($test_directory . 'post-test-exit-status');

				if($exit_status != 0)
				{
					self::test_run_instance_error($test_run_manager, $test_run_request, 'The post run script quit with a non-zero exit status.' . PHP_EOL);
					$abort_testing = true;
				}
			}
		}
		if(is_file(PTS_USER_PATH . 'halt-testing') || is_file(PTS_USER_PATH . 'skip-test'))
		{
			pts_client::release_lock($lock_file);
			return false;
		}
		if($abort_testing && !is_dir('/mnt/c/Windows')) // bash on Windows has issues where this is always called, looks like bad exit status on Windows
		{
			self::test_run_error($test_run_manager, $test_run_request, 'This test execution has been abandoned.');
			return false;
		}

		// End
		$time_test_end = microtime(true);
		$time_test_elapsed = $time_test_end - $time_test_start;
		$time_test_elapsed_actual = $time_test_end_actual - $time_test_start_actual;

		if(!empty($min_length))
		{
			if($min_length > $time_test_elapsed_actual)
			{
				// The test ended too quickly, results are not valid
				self::test_run_error($test_run_manager, $test_run_request, 'This test ended prematurely.');
				return false;
			}
		}

		if(!empty($max_length))
		{
			if($max_length < $time_test_elapsed_actual)
			{
				// The test took too much time, results are not valid
				self::test_run_error($test_run_manager, $test_run_request, 'This test run was exhausted.');
				return false;
			}
		}

		if($allow_cache_share && !is_file($cache_share_pt2so) && $cache_share instanceof pts_storage_object)
		{
			$cache_share->save_to_file($cache_share_pt2so);
			unset($cache_share);
		}

		if($test_run_manager->do_save_results() && (pts_config::read_bool_config('PhoronixTestSuite/Options/Testing/SaveInstallationLogs', 'TRUE')))
		{
			if($test_run_request->test_profile->test_installation->has_install_log() && $test_run_manager->result_file->get_test_installation_log_dir())
			{
				$backup_log_dir = $test_run_manager->result_file->get_test_installation_log_dir() . $test_run_manager->get_results_identifier_simplified() . '/';
				pts_file_io::mkdir($backup_log_dir, 0777, true);
				copy($test_run_request->test_profile->test_installation->get_install_log_location(), $backup_log_dir . $test_run_request->test_profile->get_identifier_simplified() . '.log');
			}
		}

		// Fill in any missing test details
		foreach($test_run_request->generated_result_buffers as &$sub_tr)
		{
			$arguments_description = $sub_tr->get_arguments_description();

			if(empty($arguments_description))
			{
				$arguments_description = $sub_tr->test_profile->get_test_subtitle();
			}

			$file_var_checks = array(
			array('pts-results-scale', 'set_result_scale', null),
			array('pts-results-proportion', 'set_result_proportion', null),
			array('pts-results-quantifier', 'set_result_quantifier', null),
			array('pts-test-version', 'set_version', null),
			array('pts-test-description', null, 'set_used_arguments_description'),
			array('pts-footnote', null, null),
			);

			foreach($file_var_checks as &$file_check)
			{
				list($file, $set_function, $result_set_function) = $file_check;

				if(is_file($test_directory . $file))
				{
					$file_contents = pts_file_io::file_get_contents($test_directory . $file);
					unlink($test_directory . $file);

					if(!empty($file_contents))
					{
						if(strpos($file_contents, "\n") !== false)
						{
							// If finding a line break, presumably a bad parse...
							// Seeing this behavior for pts-test-version with
							// system/blender yielding multi-line version from bad parsing on some distros
							// using pts_file_io::file_get_contents already trims the string
							continue;
						}
						if($set_function != null)
						{
							call_user_func(array($sub_tr->test_profile, $set_function), $file_contents);
						}
						else if($result_set_function != null)
						{
							if($result_set_function == 'set_used_arguments_description')
							{
								$arguments_description = $file_contents;
							}
							else
							{
								call_user_func(array($sub_tr, $result_set_function), $file_contents);
							}
						}
						else if($file == 'pts-footnote')
						{
							$sub_tr->test_profile->test_installation->set_install_footnote($file_contents);
						}
					}
				}
			}

			foreach(pts_client::environment_variables() as $key => $value)
			{
				if($value === null)
				{
					// Fixes PHP 8.1+ warning
					$value = '';
				}
				$arguments_description = $arguments_description != null ? str_replace('$' . $key, $value, $arguments_description) : '';

				if(!empty($extra_arguments) && !in_array($key, array('VIDEO_MEMORY', 'NUM_CPU_CORES', 'NUM_CPU_JOBS')))
				{
					$extra_arguments = str_replace('$' . $key, $value, $extra_arguments);
				}
			}
			$sub_tr->set_used_arguments_description($arguments_description);
			$sub_tr->set_used_arguments($extra_arguments);

			$this_backup_test_log_dir = $test_run_manager->result_file->get_test_log_dir($sub_tr);
			if($backup_test_log_file && !empty($backup_test_log_dir) && $backup_test_log_dir != $this_backup_test_log_dir && count($toxfer = pts_file_io::glob($backup_test_log_dir . '/*')) > 0)
			{
				// If test generated dynamic arguments and such, the backup log file may be different
				// or in cases where one run generates multiple results...
				pts_file_io::mkdir($this_backup_test_log_dir);
				// TODO: come up with way in log viewer to de-duplicate/symlink rather than copy...
				// there is also the possibility of the original backup_test_log_dir hash not being used so could be removed
				foreach($toxfer as $bf)
				{
					copy($bf, $this_backup_test_log_dir . basename($bf));
				}
			}
		}

		// Result Calculation

		// Ending Tasks
		pts_client::$display->display_interrupt_message($test_run_request->test_profile->get_post_run_message());
		$test_successful = self::calculate_end_result_post_processing($test_run_manager, $test_run_request); // Process results

		// Ensure entry in result file even if no result... (And report error string if appropriate)
		if(!$test_successful && $test_run_manager->do_save_results())
		{
			$test_run_request->test_result_buffer = new pts_test_result_buffer();
			$rid = $test_run_manager->get_results_identifier() != null ? $test_run_manager->get_results_identifier() : 'Result';
			$test_run_request->test_result_buffer->add_test_result($rid, '', '', pts_test_run_manager::process_json_report_attributes($test_run_request, (!empty(self::$test_run_error_collection) ? implode(' ', array_unique(self::$test_run_error_collection)) : '')), '', '');
			$test_run_manager->result_file->add_result($test_run_request);
		}

		// End Finalize
		pts_module_manager::module_process('__post_test_run', $test_run_request);
		$report_elapsed_time = $cache_share_present == false && $times_result_produced > 0;
		if($report_elapsed_time)
		{
			$test_run_request->test_profile->test_installation->add_latest_run_time($test_run_request, $time_test_elapsed);
		}
		if($test_run_manager->is_multi_test_stress_run() == false)
		{
			$test_run_request->test_profile->test_installation->test_runtime_error_handler($test_run_request, self::$test_run_error_collection);
			$test_run_request->test_profile->test_installation->save_test_install_metadata();
			pts_storage_object::add_in_file(PTS_CORE_STORAGE, 'total_testing_time', ($time_test_elapsed / 60));
		}

		if($report_elapsed_time && pts_client::do_anonymous_usage_reporting() && $time_test_elapsed >= 10)
		{
			// If anonymous usage reporting enabled, report test run-time to OpenBenchmarking.org
			pts_openbenchmarking_client::upload_usage_data('test_complete', array($test_run_request, $time_test_elapsed));
		}

		// Remove lock
		pts_client::release_lock($lock_file);
		return $test_successful;
	}
	protected static function calculate_end_result_post_processing(&$test_run_manager, &$root_tr)
	{
		$test_successful = false;
		$generated_result_count = 0;

		foreach($root_tr->generated_result_buffers as &$test_result)
		{
			$trial_results = $test_result->active->results;
			$END_RESULT = 0;

			switch($test_result->test_profile->get_display_format())
			{
				case 'NO_RESULT':
					// Nothing to do, there are no results
					break;
				case 'LINE_GRAPH':
				case 'FILLED_LINE_GRAPH':
				case 'TEST_COUNT_PASS':
					// Just take the first result
					$END_RESULT = $trial_results[0];
					break;
				case 'IMAGE_COMPARISON':
					// Capture the image
					$iqc_image_png = $trial_results[0];

					if(is_file($iqc_image_png))
					{
						$img_file_64 = base64_encode(file_get_contents($iqc_image_png, FILE_BINARY));
						$END_RESULT = $img_file_64;
						unlink($iqc_image_png);
					}
					break;
				case 'PASS_FAIL':
				case 'MULTI_PASS_FAIL':
					// Calculate pass/fail type
					$END_RESULT = -1;

					if(count($trial_results) == 1)
					{
						$END_RESULT = $trial_results[0];
					}
					else
					{
						foreach($trial_results as $result)
						{
							if($result == 'FALSE' || $result == '0' || $result == 'FAIL' || $result == 'FAILED')
							{
								if($END_RESULT == -1 || $END_RESULT == 'PASS')
								{
									$END_RESULT = 'FAIL';
								}
							}
							else
							{
								if($END_RESULT == -1)
								{
									$END_RESULT = 'PASS';
								}
							}
						}
					}
					break;
				case 'BAR_GRAPH':
				default:
					// Result is of a normal numerical type
					switch($test_result->test_profile->get_result_quantifier())
					{
						case 'MAX':
							$END_RESULT = max($trial_results);
							break;
						case 'MIN':
							$END_RESULT = min($trial_results);
							break;
						case 'AVG':
						default:
							// assume AVG (average)
							$is_float = false;
							$TOTAL_RESULT = 0;
							$TOTAL_COUNT = 0;

							foreach($trial_results as $result)
							{
								$result = trim($result);

								if(is_numeric($result))
								{
									$TOTAL_RESULT += $result;
									$TOTAL_COUNT++;

									if(!$is_float && strpos($result, '.') !== false)
									{
										$is_float = true;
									}
								}
							}

							$END_RESULT = pts_math::set_precision($TOTAL_RESULT / ($TOTAL_COUNT > 0 ? $TOTAL_COUNT : 1), $test_result->get_result_precision());

							if(!$is_float)
							{
								$END_RESULT = round($END_RESULT);
							}

							if(count($min = $test_result->active->min_results) > 0)
							{
								$min = min($min);
								if($min === null)
								{
									$min = 0;
								}
								$min = round($min, 2);

								if($min < $END_RESULT && is_numeric($min) && $min != 0)
								{
									$test_result->active->set_min_result($min);
								}
							}
							if(count($max = $test_result->active->max_results) > 0)
							{
								$max = max($max);
								if($max === null)
								{
									$max = 0;
								}
								$max = round($max, 2);

								if($max > $END_RESULT && is_numeric($max) && $max != 0)
								{
									$test_result->active->set_max_result($max);
								}
							}
							break;
					}
					break;
			}

			$test_result->active->set_result($END_RESULT);

			pts_client::$display->test_run_end($test_result);

			// Finalize / result post-processing to generate save
			if($test_result->test_profile->get_display_format() == 'NO_RESULT')
			{
				$test_successful = true;
			}
			else if($test_result instanceof pts_test_result && $test_result->active)
			{
				$end_result = $test_result->active->get_result();

				$test_result->test_run_times = $root_tr->test_run_times;

				// removed count($result) > 0 in the move to pts_test_result
				if(((is_numeric($end_result) && $end_result > 0) || (!is_numeric($end_result) && isset($end_result[3]))))
				{
					pts_module_manager::module_process('__post_test_run_success', $test_result);
					$test_successful = true;

					if($generated_result_count >= 1)
					{
						// Prior to PTS 8.6, secondary result graphs wouldn't have their test profile identifier set but would be null
						// With PTS 8.6+, the identifier is now preserved... Except with below logic for preserving compatibility with older result files, only clear the identifier if comparing against an old result file having a match for no identifier set
						$ti_backup = $test_result->test_profile->get_identifier();
						$test_result->test_profile->set_identifier('');
						if(!$test_run_manager->result_file->result_hash_exists($test_result))
						{
							$test_result->test_profile->set_identifier($ti_backup);
						}
					}

					$test_result->test_result_buffer = new pts_test_result_buffer();
					$rid = $test_run_manager->get_results_identifier() != null ? $test_run_manager->get_results_identifier() : 'Result';
					$test_result->test_result_buffer->add_test_result($rid, $test_result->active->get_result(), $test_result->active->get_values_as_string(), pts_test_run_manager::process_json_report_attributes($test_result), $test_result->active->get_min_result(), $test_result->active->get_max_result());
					$added_comparison_hash = $test_run_manager->result_file->add_result($test_result);
					$generated_result_count++;
					$test_run_manager->test_run_success_counter++;

					// The merged data, get back the merged test_result object
					$results_comparison = clone $test_run_manager->result_file->get_result($added_comparison_hash);
					if($results_comparison && $results_comparison->test_result_buffer->get_count() > 1)
					{
						pts_client::$display->test_run_success_inline($results_comparison);
					}
					pts_module_manager::module_process('__test_run_success_inline_result', $results_comparison);
				}
			}
		}

		return $test_successful;
	}
}

?>
