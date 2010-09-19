<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel

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
	public static function run_test(&$test_run_manager, &$test_run_request)
	{
		$test_identifier = $test_run_request->test_profile->get_identifier();
		$extra_arguments = $test_run_request->get_arguments();
		$arguments_description = $test_run_request->get_arguments_description();

		// Do the actual test running process
		$test_directory = TEST_ENV_DIR . $test_identifier . "/";

		if(!is_dir($test_directory))
		{
			return false;
		}

		$lock_file = $test_directory . "run_lock";
		if(pts_client::create_lock($lock_file) == false)
		{
			pts_client::$display->test_run_error("The " . $test_identifier . " test is already running.");
			return false;
		}

		$parse_results_xml_file = $test_run_request->test_profile->get_file_parser_spec();

		$test_run_request->test_result_buffer = new pts_test_result_buffer();
		$execute_binary = $test_run_request->test_profile->get_test_executable();
		$times_to_run = $test_run_request->test_profile->get_times_to_run();
		$ignore_runs = $test_run_request->test_profile->get_runs_to_ignore();
		$result_format = $test_run_request->test_profile->get_result_format();
		$test_type = $test_run_request->test_profile->get_test_hardware_type();
		$allow_cache_share = $test_run_request->test_profile->allow_cache_share();
		$min_length = $test_run_request->test_profile->get_min_length();
		$max_length = $test_run_request->test_profile->get_max_length();

		if($test_run_request->test_profile->get_environment_testing_size() != -1 && ceil(disk_free_space(TEST_ENV_DIR) / 1048576) < $test_run_request->test_profile->get_environment_testing_size())
		{
			// Ensure enough space is available on disk during testing process
			pts_client::$display->test_run_error("There is not enough space (at " . TEST_ENV_DIR . ") for this test to run.");
			pts_client::release_lock($lock_file);
			return false;
		}

		$to_execute = $test_run_request->test_profile->get_test_executable_dir();
		$pts_test_arguments = trim($test_run_request->test_profile->get_default_arguments() . " " . str_replace($test_run_request->test_profile->get_default_arguments(), "", $extra_arguments) . " " . $test_run_request->test_profile->get_default_post_arguments());
		$extra_runtime_variables = self::extra_run_time_vars($test_identifier, $pts_test_arguments, $result_format);

		// Start
		$cache_share_pt2so = $test_directory . "cache-share-" . PTS_INIT_TIME . ".pt2so";
		$cache_share_present = $allow_cache_share && is_file($cache_share_pt2so);
		$test_run_request->set_used_arguments_description($arguments_description);
		pts_module_manager::module_process("__pre_test_run", $test_run_request);

		$time_test_start = time();
		pts_client::$display->test_run_start($test_run_manager, $test_run_request);

		if(!$cache_share_present)
		{
			pts_tests::call_test_script($test_identifier, "pre", "Running Pre-Test Script", $test_directory, $extra_runtime_variables, true);
		}

		pts_user_io::display_interrupt_message($test_run_request->test_profile->get_pre_run_message());
		$runtime_identifier = time();
		$execute_binary_prepend = "";

		if(!$cache_share_present && $test_run_request->test_profile->is_root_required())
		{
			$execute_binary_prepend = TEST_LIBRARIES_DIR . "root-access.sh ";
		}

		if($allow_cache_share && !is_file($cache_share_pt2so))
		{
			$cache_share = new pts_storage_object(false, false);
		}

		if($test_run_manager->get_results_identifier() != null && $test_run_manager->get_file_name() != null)
		{
			$backup_test_log_dir = SAVE_RESULTS_DIR . $test_run_manager->get_file_name() . "/test-logs/active/" . $test_run_manager->get_results_identifier() . '/';
			pts_file_io::delete($backup_test_log_dir);
			pts_file_io::mkdir($backup_test_log_dir, 0777, true);
		}
		else
		{
			$backup_test_log_dir = false;
		}

		for($i = 0, $abort_testing = false, $time_test_start_actual = time(), $defined_times_to_run = $times_to_run; $i < $times_to_run && !$abort_testing; $i++)
		{
			pts_client::$display->test_run_instance_header($test_run_request, ($i + 1), $times_to_run);
			$test_log_file = $test_directory . $test_identifier . "-" . $runtime_identifier . "-" . ($i + 1) . ".log";

			$test_extra_runtime_variables = array_merge($extra_runtime_variables, array(
			"LOG_FILE" => $test_log_file
			));

			$restored_from_cache = false;
			if($cache_share_present)
			{
				$cache_share = pts_storage_object::recover_from_file($cache_share_pt2so);

				if($cache_share)
				{
					$test_result = $cache_share->read_object("test_results_output_" . $i);
					$test_extra_runtime_variables["LOG_FILE"] = $cache_share->read_object("log_file_location_" . $i);
					file_put_contents($test_extra_runtime_variables["LOG_FILE"], $cache_share->read_object("log_file_" . $i));
					$restored_from_cache = true;
				}

				unset($cache_share);
			}

			if($restored_from_cache == false)
			{
				$test_run_command = "cd " . $to_execute . " && " . $execute_binary_prepend . "./" . $execute_binary . " " . $pts_test_arguments . " 2>&1";

				pts_client::test_profile_debug_message("Test Run Command: " . $test_run_command);

				if($parse_results_xml_file)
				{
					$is_monitoring = pts_test_result_parser::system_monitor_task_check($test_run_request->test_profile, $parse_results_xml_file, $test_directory);
				}
				$test_run_time_start = time();

				if(IS_WINDOWS || pts_client::read_env("USE_PHOROSCRIPT_INTERPRETER") != false)
				{
					$phoroscript = new pts_phoroscript_interpreter($to_execute . '/' . $execute_binary, $test_extra_runtime_variables, $to_execute);
					$phoroscript->execute_script($pts_test_arguments);
					$test_result = null;
				}
				else
				{
					$test_result = pts_client::shell_exec($test_run_command, $test_extra_runtime_variables);
				}

				$test_run_time = time() - $test_run_time_start;
				$monitor_result = $parse_results_xml_file && $is_monitoring ? pts_test_result_parser::system_monitor_task_post_test($test_run_request->test_profile, $parse_results_xml_file, $test_directory) : 0;
			}
		

			if(!isset($test_result[10240]) || pts_read_assignment("DEBUG_TEST_PROFILE"))
			{
				pts_client::$display->test_run_instance_output($test_result);
			}

			if(is_file($test_log_file) && trim($test_result) == null && (filesize($test_log_file) < 10240 || pts_is_assignment("DEBUG_TEST_PROFILE")))
			{
				$test_log_file_contents = file_get_contents($test_log_file);
				pts_client::$display->test_run_instance_output($test_log_file_contents);
				unset($test_log_file_contents);
			}

			$exit_status_pass = true;
			if(is_file(TEST_ENV_DIR . $test_identifier . "/test-exit-status"))
			{
				// If the test script writes its exit status to ~/test-exit-status, if it's non-zero the test run failed
				$exit_status = pts_file_io::file_get_contents(TEST_ENV_DIR . $test_identifier . "/test-exit-status");
				unlink(TEST_ENV_DIR . $test_identifier . "/test-exit-status");

				if($exit_status != 0 && !IS_BSD)
				{
					pts_client::$display->test_run_instance_error("The test exited with a non-zero exit status.");
					$exit_status_pass = false;
				}
			}

			if(!in_array(($i + 1), $ignore_runs) && $exit_status_pass)
			{
				if($parse_results_xml_file)
				{
					if(isset($monitor_result) && $monitor_result != 0)
					{
						$test_result = $monitor_result;
					}
					else
					{
						$test_result = pts_test_result_parser::parse_result($test_run_request, $parse_results_xml_file, $test_log_file);
					}
				}
				else
				{
					$test_result = null;
				}

				pts_client::test_profile_debug_message("Test Result Value: " . $test_result);

				if(!empty($test_result))
				{
					$test_run_request->test_result_buffer->add_test_result(null, $test_result, null);
				}
				else
				{
					pts_client::$display->test_run_instance_error("The test did not produce a result.");
				}

				if($allow_cache_share && !is_file($cache_share_pt2so))
				{
					$cache_share->add_object("test_results_output_" . $i, $test_result);
					$cache_share->add_object("log_file_location_" . $i, $test_extra_runtime_variables["LOG_FILE"]);
					$cache_share->add_object("log_file_" . $i, (is_file($test_log_file) ? file_get_contents($test_log_file) : null));
				}
			}

			if($i == ($times_to_run - 1))
			{
				// Should we increase the run count?
				$increase_run_count = false;

				if($defined_times_to_run == ($i + 1) && $test_run_request->test_result_buffer->get_count() > 0 && $test_run_request->test_result_buffer->get_count() < $defined_times_to_run)
				{
					// At least one run passed, but at least one run failed to produce a result. Increase count to try to get more successful runs
					$increase_run_count = $defined_times_to_run - $test_run_request->test_result_buffer->get_count();
				}
				else if($test_run_request->test_result_buffer->get_count() > 2 && $test_run_manager->do_dynamic_run_count() && $times_to_run < ($defined_times_to_run * 2))
				{
					// Dynamically increase run count if told to do so by external script or standard deviation is too high
					$increase_run_count = $test_run_manager->increase_run_count_check($test_run_request, $test_run_time);

					if($increase_run_count === -1)
					{
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
					$test_run_request->test_profile->set_times_to_run($times_to_run);
				}
			}

			if($times_to_run > 1 && $i < ($times_to_run - 1))
			{
				if(!$cache_share_present)
				{
					pts_tests::call_test_script($test_identifier, "interim", "Running Interim-Test Script", $test_directory, $extra_runtime_variables, true);
					sleep(2); // Rest for a moment between tests
				}

				pts_module_manager::module_process("__interim_test_run", $test_run_request);
			}

			if(is_file($test_log_file))
			{
				if($backup_test_log_dir)
				{
					copy($test_log_file, $backup_test_log_dir . $test_identifier . "-" . ($i + 1) . ".log");
				}

				if(pts_client::test_profile_debug_message("Log File At: " . $test_log_file) == false)
				{
					unlink($test_log_file);
				}
			}

			if(is_file(PTS_USER_DIR . "halt-testing") || is_file(PTS_USER_DIR . "skip-test"))
			{
				pts_client::release_lock($lock_file);
				return false;
			}

			pts_client::$display->test_run_instance_complete($test_run_request);
		}

		$time_test_end_actual = time();

		if(!$cache_share_present)
		{
			pts_tests::call_test_script($test_identifier, "post", "Running Post-Test Script", $test_directory, $extra_runtime_variables, true);
		}

		if($abort_testing)
		{
			pts_client::$display->test_run_error("This test execution has been abandoned.");
			return false;
		}

		// End
		$time_test_end = time();
		$time_test_elapsed = $time_test_end - $time_test_start;
		$time_test_elapsed_actual = $time_test_end_actual - $time_test_start_actual;

		if(!empty($min_length))
		{
			if($min_length > $time_test_elapsed_actual)
			{
				// The test ended too quickly, results are not valid
				pts_client::$display->test_run_error("This test ended prematurely.");
				return false;
			}
		}

		if(!empty($max_length))
		{
			if($max_length < $time_test_elapsed_actual)
			{
				// The test took too much time, results are not valid
				pts_client::$display->test_run_error("This test run was exhausted.");
				return false;
			}
		}

		if($allow_cache_share && !is_file($cache_share_pt2so) && $cache_share instanceOf pts_storage_object)
		{
			$cache_share->save_to_file($cache_share_pt2so);
			unset($cache_share);
		}

		if($test_run_manager->get_results_identifier() != null && (pts_config::read_bool_config(P_OPTION_LOG_INSTALLATION, "FALSE") || pts_read_assignment("IS_PCQS_MODE") || pts_read_assignment("IS_BATCH_MODE")))
		{
			if(is_file(TEST_ENV_DIR . $test_identifier . "/install.log"))
			{
				$backup_log_dir = SAVE_RESULTS_DIR . $test_run_manager->get_file_name() . "/installation-logs/" . $test_run_manager->get_results_identifier() . "/";
				pts_file_io::mkdir($backup_log_dir, 0777, true);
				copy(TEST_ENV_DIR . $test_identifier . "/install.log", $backup_log_dir . $test_identifier . ".log");
			}
		}

		if(is_file($test_directory . "/pts-test-note"))
		{
			pts_test_notes_manager::add_note(pts_file_io::file_get_contents($test_directory . "/pts-test-note"));
			unlink($test_directory . "pts-test-note");
		}

		// Fill in missing test details

		if(empty($arguments_description))
		{
			$arguments_description = $test_run_request->test_profile->get_test_subtitle();
		}

		$file_var_checks = array(
		array("pts-results-scale", "set_result_scale", null),
		array("pts-results-proportion", "set_result_proportion", null),
		array("pts-results-quantifier", "set_result_quantifier", null),
		array("pts-test-version", "set_version", null),
		array("pts-test-description", null, "set_used_arguments_description")
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
					if($set_function != null)
					{
						eval("\$test_run_request->test_profile->" . $set_function . "->(\$file_contents);");
					}
					else if($result_set_function != null)
					{
						call_user_func(array($test_run_request, $set_function), $file_contents);
						//eval("\$test_run_request->" . $set_function . "->(\$file_contents);");
					}
				}
			}
		}

		if(empty($arguments_description))
		{
			$arguments_description = "Phoronix Test Suite v" . PTS_VERSION;
		}

		foreach(pts_client::environmental_variables() as $key => $value)
		{
			$arguments_description = str_replace("$" . $key, $value, $arguments_description);

			if(!in_array($key, array("VIDEO_MEMORY", "NUM_CPU_CORES", "NUM_CPU_JOBS")))
			{
				$extra_arguments = str_replace("$" . $key, $value, $extra_arguments);
			}
		}

		// Any device notes to add to PTS test notes area?
		foreach(phodevi::read_device_notes($test_type) as $note)
		{
			pts_test_notes_manager::add_note($note);
		}

		// Any special information (such as forced AA/AF levels for graphics) to add to the description string of the result?
		if(($special_string = phodevi::read_special_settings_string($test_type)) != null)
		{
			if(strpos($arguments_description, $special_string) === false)
			{
				if($arguments_description != null)
				{
					$arguments_description .= " | ";
				}

				$arguments_description .= $special_string;
			}
		}

		// Result Calculation
		$test_run_request->set_used_arguments_description($arguments_description);
		$test_run_request->set_used_arguments($extra_arguments);
		pts_test_result_parser::calculate_end_result($test_run_request); // Process results

		pts_client::$display->test_run_end($test_run_request);

		pts_user_io::display_interrupt_message($test_run_request->test_profile->get_post_run_message());
		pts_module_manager::module_process("__post_test_run", $test_run_request);
		$report_elapsed_time = !$cache_share_present && $test_run_request->get_result() != 0;
		pts_tests::update_test_install_xml($test_identifier, ($report_elapsed_time ? $time_test_elapsed : 0));
		pts_storage_object::add_in_file(PTS_CORE_STORAGE, "total_testing_time", ($time_test_elapsed / 60));

		if($report_elapsed_time && pts_client::do_anonymous_usage_reporting() && $time_test_elapsed >= 60)
		{
			pts_global::upload_usage_data("test_complete", array($test_run_request, $time_test_elapsed));
		}

		// Remove lock
		pts_client::release_lock($lock_file);
	}
	private static function extra_run_time_vars($test_identifier, $pts_test_arguments = null, $result_format = null)
	{
		$vars = pts_tests::process_extra_test_variables($test_identifier);
		$vars["LC_ALL"] = "";
		$vars["LC_NUMERIC"] = "";
		$vars["LC_CTYPE"] = "";
		$vars["LC_MESSAGES"] = "";
		$vars["LANG"] = "";
		$vars["vblank_mode"] = '0';
		$vars["PTS_TEST_ARGUMENTS"] = "'" . $pts_test_arguments . "'";
		$vars["TEST_LIBRARIES_DIR"] = TEST_LIBRARIES_DIR;
		$vars["TIMED_KILL"] = TEST_LIBRARIES_DIR . "timed-kill.sh";
		$vars["PHP_BIN"] = PHP_BIN;

		return $vars;
	}
}

?>
