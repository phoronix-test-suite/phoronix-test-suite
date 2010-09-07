<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel
	pts-includes-run.php: Functions needed for running tests/suites.

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

function pts_cleanup_tests_to_run(&$to_run_identifiers)
{
	$skip_tests = ($e = pts_client::read_env("SKIP_TESTS")) ? pts_strings::comma_explode($e) : false;

	$tests_verified = array();
	$tests_missing = array();

	foreach($to_run_identifiers as &$test_identifier)
	{
		$lower_identifier = strtolower($test_identifier);

		if($skip_tests && in_array($lower_identifier, $skip_tests))
		{
			echo "Skipping Test: " . $lower_identifier . "\n";
			continue;
		}
		else if(pts_is_test($lower_identifier))
		{
			$test_profile = new pts_test_profile($lower_identifier);

			if($test_profile->get_title() == null)
			{
				echo "Not A Test: " . $lower_identifier . "\n";
				continue;
			}
			else
			{
				if(pts_client::test_support_check($lower_identifier) == false)
				{
					continue;
				}
			}
		}
		else if(pts_is_suite($lower_identifier))
		{
			$test_suite = new pts_test_suite($lower_identifier);

			if($test_suite->is_core_version_supported() == false)
			{
				echo $lower_identifier . " is a suite not supported by this version of the Phoronix Test Suite.\n";
				continue;
			}
			else if(pts_read_assignment("CONFIGURE_TESTS_IN_SUITE"))
			{
				foreach(pts_contained_tests($lower_identifier) as $test)
				{
					if(!in_array($test, $to_run_identifiers))
					{
						array_push($to_run_identifiers, $test);
					}
				}
				continue;
			}
		}
		else if(pts_is_virtual_suite($lower_identifier))
		{
			foreach(pts_virtual_suite_tests($lower_identifier) as $virt_test)
			{
				array_push($to_run_identifiers, $virt_test);
			}
			continue;
		}
		else if(!pts_is_run_object($lower_identifier) && !pts_global::is_valid_global_id_format($lower_identifier) && !pts_is_test_result($lower_identifier))
		{
			echo "Not Recognized: " . $lower_identifier . "\n";
			continue;
		}

		if(pts_verify_test_installation($lower_identifier, $tests_missing) == false)
		{
			// Eliminate this test, it's not properly installed
			continue;
		}

		array_push($tests_verified, $test_identifier);
	}

	$to_run_identifiers = $tests_verified;

	if(count($tests_missing) > 0)
	{
		if(count($tests_missing) == 1)
		{
			pts_client::$display->generic_error($tests_missing[0] . " is not installed.\nTo install, run: phoronix-test-suite install " . $tests_missing[0]);
		}
		else
		{
			$message = "\n\nMultiple tests are not installed:\n\n";
			$message .= pts_user_io::display_text_list($tests_missing);
			$message .= "\nTo install, run: phoronix-test-suite install " . implode(' ', $tests_missing) . "\n\n";
			echo $message;
		}

		if(!pts_read_assignment("AUTOMATED_MODE") && !pts_read_assignment("IS_BATCH_MODE") && !pts_read_assignment("NO_PROMPT_IN_RUN_ON_MISSING_TESTS"))
		{
			$stop_and_install = pts_user_io::prompt_bool_input("Would you like to install these tests now", true);

			if($stop_and_install)
			{
				pts_client::run_next("install_test", $tests_missing, pts_assignment_manager::get_all_assignments());
				pts_client::run_next("run_test", $tests_missing, pts_assignment_manager::get_all_assignments(array("NO_PROMPT_IN_RUN_ON_MISSING_TESTS" => true)));
				return false;
			}
			else
			{
				pts_set_assignment("USER_REJECTED_TEST_INSTALL_NOTICE", true);
			}
		}
	}

	return true;
}
function pts_verify_test_installation($identifiers, &$tests_missing)
{
	// Verify a test is installed
	$identifiers = pts_arrays::to_array($identifiers);
	$contains_a_suite = false;
	$tests_installed = array();
	$current_tests_missing = array();

	foreach($identifiers as $identifier)
	{
		if(!$contains_a_suite && (pts_is_suite($identifier) || pts_is_test_result($identifier)))
		{
			$contains_a_suite = true;
		}

		foreach(pts_contained_tests($identifier) as $test)
		{
			if(pts_test_installed($test))
			{
				pts_arrays::unique_push($tests_installed, $test);
			}
			else
			{
				$test_profile = new pts_test_profile($test);

				if($test_profile->is_supported())
				{
					pts_arrays::unique_push($current_tests_missing, $test);
				}
			}
		}
	}

	$tests_missing = array_merge($tests_missing, $current_tests_missing);

	return count($tests_installed) > 0 && (count($current_tests_missing) == 0 || $contains_a_suite);
}
function pts_call_test_runs(&$test_run_manager, &$tandem_xml = null)
{
	pts_file_io::unlink(PTS_USER_DIR . "halt-testing");
	pts_file_io::unlink(PTS_USER_DIR . "skip-test");

	$test_flag = true;
	$tests_to_run_count = $test_run_manager->get_test_count();
	pts_client::$display->test_run_process_start($test_run_manager);

	if(($total_loop_time_minutes = pts_client::read_env("TOTAL_LOOP_TIME")) && is_numeric($total_loop_time_minutes) && $total_loop_time_minutes > 0)
	{
		$total_loop_time_seconds = $total_loop_time_minutes * 60;
		$loop_end_time = time() + $total_loop_time_seconds;

		pts_client::$display->generic_heading("Estimated Run-Time: " . pts_strings::format_time($total_loop_time_seconds, "SECONDS", true, 60));

		do
		{
			for($i = 0; $i < $tests_to_run_count && $test_flag && time() < $loop_end_time; $i++)
			{
				$test_flag = pts_process_test_run_request($test_run_manager, $tandem_xml, $i);
			}
		}
		while(time() < $loop_end_time && $test_flag);
	}
	else if(($total_loop_count = pts_client::read_env("TOTAL_LOOP_COUNT")) && is_numeric($total_loop_count))
	{
		if(($estimated_length = $test_run_manager->get_estimated_run_time()) > 1)
		{
			pts_client::$display->generic_heading("Estimated Run-Time: " . pts_strings::format_time(($estimated_length * $total_loop_count), "SECONDS", true, 60));
		}

		for($loop = 0; $loop < $total_loop_count && $test_flag; $loop++)
		{
			for($i = 0; $i < $tests_to_run_count && $test_flag; $i++)
			{
				$test_flag = pts_process_test_run_request($test_run_manager, $tandem_xml, $i, ($loop * $tests_to_run_count + $i + 1), ($total_loop_count * $tests_to_run_count));
			}
		}
	}
	else
	{
		if(($estimated_length = $test_run_manager->get_estimated_run_time()) > 1)
		{
			pts_client::$display->generic_heading("Estimated Run-Time: " . pts_strings::format_time($estimated_length, "SECONDS", true, 60));
		}

		for($i = 0; $i < $tests_to_run_count && $test_flag; $i++)
		{
			$test_flag = pts_process_test_run_request($test_run_manager, $tandem_xml, $i, ($i + 1), $tests_to_run_count);
		}
	}

	pts_file_io::unlink(SAVE_RESULTS_DIR . $test_run_manager->get_file_name() . "/active.xml");

	foreach(pts_file_io::glob(TEST_ENV_DIR . "*/cache-share-*.pt2so") as $cache_share_file)
	{
		// Process post-cache-share scripts
		$test_identifier = pts_extract_identifier_from_path($cache_share_file);
		echo pts_tests::call_test_script($test_identifier, "post-cache-share", null, null, pts_tests::process_extra_test_variables($test_identifier));
		unlink($cache_share_file);
	}
}
function pts_validate_test_installations_to_run(&$test_run_manager)
{
	$failed_tests = array();
	$validated_run_requests = array();
	$allow_global_uploads = true;
	$display_driver = phodevi::read_property("system", "display-driver");

	foreach($test_run_manager->get_tests_to_run() as $test_run_request)
	{
		if(!($test_run_request instanceOf pts_test_result))
		{
			array_push($validated_run_requests, $test_run_request);
			continue;
		}

		// Validate the empty pts_test_result
		$test_identifier = $test_run_request->test_profile->get_identifier();

		if(in_array($test_identifier, $failed_tests))
		{
			// The test has already been determined to not be installed right or other issue
			continue;
		}

		if(!is_dir(TEST_ENV_DIR . $test_identifier))
		{
			// The test is not setup
			array_push($failed_tests, $test_identifier);
			continue;
		}

		$test_type = $test_run_request->test_profile->get_test_hardware_type();

		if($test_type == "Graphics")
		{
			if(pts_client::read_env("DISPLAY") == false && !IS_WINDOWS)
			{
				pts_client::$display->test_run_error("No display server was found, cannot run " . $test_identifier);
				array_push($failed_tests, $test_identifier);
				continue;
			}
			else if(in_array($display_driver, array("vesa", "nv", "cirrus")))
			{
				pts_client::$display->test_run_error("A display driver without 3D acceleration was found, cannot run " . $test_identifier);
				array_push($failed_tests, $test_identifier);
				continue;
			}
		}

		$skip_tests = pts_client::read_env("SKIP_TESTS");
		if(pts_client::read_env("NO_" . strtoupper($test_type) . "_TESTS") || ($skip_tests && in_array($test_identifier, pts_strings::comma_explode($skip_tests))) || ($skip_tests && in_array($test_type, pts_strings::comma_explode($skip_tests))))
		{
			array_push($failed_tests, $test_identifier);
			continue;
		}

		if($test_run_request->test_profile->is_root_required() && pts_read_assignment("IS_BATCH_MODE") && phodevi::read_property("system", "username") != "root")
		{
			pts_client::$display->test_run_error("Cannot run " . $test_identifier . " in batch mode as root access is required.");
			array_push($failed_tests, $test_identifier);
			continue;
		}

		if(pts_find_test_executable_dir($test_identifier, $test_run_request->test_profile) == null)
		{
			pts_client::$display->test_run_error("The test executable for " . $test_identifier . " could not be found.");
			array_push($failed_tests, $test_identifier);
			continue;
		}

		if($allow_global_uploads && $test_run_request->test_profile->allow_global_uploads() == false)
		{
			// One of the contained test profiles does not allow Global uploads, so block it
			$allow_global_uploads = false;
		}

		array_push($validated_run_requests, $test_run_request);
	}

	if(!$allow_global_uploads)
	{
		pts_set_assignment("BLOCK_GLOBAL_UPLOADS", true);
	}

	$test_run_manager->set_tests_to_run($validated_run_requests);
}
function pts_process_test_run_request(&$test_run_manager, &$tandem_xml, $run_index, $run_position = -1, $run_count = -1)
{
	$result = false;

	if($test_run_manager->get_file_name() != null)
	{
		$tandem_xml->saveXMLFile(SAVE_RESULTS_DIR . $test_run_manager->get_file_name() . "/active.xml");
	}

	$test_run_request = $test_run_manager->get_test_to_run($run_index);

	if(pts_is_test($test_run_request->test_profile->get_identifier()))
	{
		pts_set_assignment("TEST_RUN_POSITION", $run_position);
		pts_set_assignment("TEST_RUN_COUNT", $run_count);

		if(($run_position != 1 && count(pts_file_io::glob(TEST_ENV_DIR . $test_run_request->test_profile->get_identifier() . "/cache-share-*.pt2so")) == 0))
		{
			sleep(pts_config::read_user_config(P_OPTION_TEST_SLEEPTIME, 5));
		}

		pts_run_test($test_run_manager, $test_run_request);

		if(pts_file_io::unlink(PTS_USER_DIR . "halt-testing"))
		{
			// Stop the testing process entirely
			return false;
		}
		else if(pts_file_io::unlink(PTS_USER_DIR . "skip-test"))
		{
			// Just skip the current test and do not save the results, but continue testing
			continue;
		}
	}

	$test_successful = false;


	if($test_run_request->test_profile->get_result_format() == "NO_RESULT")
	{
		$test_successful = true;
	}
	else if($test_run_request instanceof pts_test_result)
	{
		$end_result = $test_run_request->get_result();

		// removed count($result) > 0 in the move to pts_test_result
		if(count($test_run_request) > 0 && ((is_numeric($end_result) && $end_result > 0) || (!is_numeric($end_result) && isset($end_result[3]))))
		{
			$test_identifier = $test_run_manager->get_results_identifier();
			$test_successful = true;

			if(!empty($test_identifier))
			{
				$tandem_id = $tandem_xml->request_unique_id();
				pts_set_assignment("TEST_RAN", true);

				$tandem_xml->addXmlObject(P_RESULTS_TEST_TITLE, $tandem_id, $test_run_request->test_profile->get_title());
				$tandem_xml->addXmlObject(P_RESULTS_TEST_VERSION, $tandem_id, $test_run_request->test_profile->get_version());
				$tandem_xml->addXmlObject(P_RESULTS_TEST_PROFILE_VERSION, $tandem_id, $test_run_request->test_profile->get_test_profile_version());
				$tandem_xml->addXmlObject(P_RESULTS_TEST_ATTRIBUTES, $tandem_id, $test_run_request->get_arguments_description());
				$tandem_xml->addXmlObject(P_RESULTS_TEST_SCALE, $tandem_id, $test_run_request->test_profile->get_result_scale());
				$tandem_xml->addXmlObject(P_RESULTS_TEST_PROPORTION, $tandem_id, $test_run_request->test_profile->get_result_proportion());
				$tandem_xml->addXmlObject(P_RESULTS_TEST_RESULTFORMAT, $tandem_id, $test_run_request->test_profile->get_result_format());
				$tandem_xml->addXmlObject(P_RESULTS_TEST_TESTNAME, $tandem_id, $test_run_request->test_profile->get_identifier());
				$tandem_xml->addXmlObject(P_RESULTS_TEST_ARGUMENTS, $tandem_id, $test_run_request->get_arguments());
				$tandem_xml->addXmlObject(P_RESULTS_RESULTS_GROUP_IDENTIFIER, $tandem_id, $test_identifier, 5);
				$tandem_xml->addXmlObject(P_RESULTS_RESULTS_GROUP_VALUE, $tandem_id, $test_run_request->get_result(), 5);
				$tandem_xml->addXmlObject(P_RESULTS_RESULTS_GROUP_RAW, $tandem_id, $test_run_request->test_result_buffer->get_values_as_string(), 5);

				static $xml_write_pos = 1;
				pts_file_io::mkdir(SAVE_RESULTS_DIR . $test_run_manager->get_file_name() . "/test-logs/" . $xml_write_pos . "/");

				if(is_dir(SAVE_RESULTS_DIR . $test_run_manager->get_file_name() . "/test-logs/active/" . $test_run_manager->get_results_identifier()))
				{
					$test_log_write_dir = SAVE_RESULTS_DIR . $test_run_manager->get_file_name() . "/test-logs/" . $xml_write_pos . '/' . $test_run_manager->get_results_identifier() . '/';

					if(is_dir($test_log_write_dir))
					{
						pts_file_io::delete($test_log_write_dir, null, true);
					}

					rename(SAVE_RESULTS_DIR . $test_run_manager->get_file_name() . "/test-logs/active/" . $test_run_manager->get_results_identifier() . '/', $test_log_write_dir);
				}
				$xml_write_pos++;
				pts_module_manager::module_process("__post_test_run_process", $tandem_xml);
			}
		}

		pts_file_io::unlink(SAVE_RESULTS_DIR . $test_run_manager->get_file_name() . "/test-logs/active/");
	}

	if($test_successful == false && $test_run_request->test_profile->get_identifier() != null)
	{
		$test_run_manager->add_failed_test_run_request($test_run_request);

		// For now delete the failed test log files, but it may be a good idea to keep them
		pts_file_io::delete(SAVE_RESULTS_DIR . $test_run_manager->get_file_name() . "/test-logs/active/" . $test_run_manager->get_results_identifier() . "/", null, true);
	}

	return true;
}
function pts_save_test_file(&$results, $file_name, $result_identifier = null)
{
	// Save the test file
	$j = 1;
	while(is_file(SAVE_RESULTS_DIR . $file_name . "/test-" . $j . ".xml"))
	{
		$j++;
	}

	$real_name = $file_name . "/test-" . $j . ".xml";

	pts_client::save_test_result($real_name, $results->getXML());

	if(!is_file(SAVE_RESULTS_DIR . $file_name . "/composite.xml"))
	{
		pts_client::save_test_result($file_name . "/composite.xml", file_get_contents(SAVE_RESULTS_DIR . $real_name), true, $result_identifier);
	}
	else
	{
		// Merge Results
		$merged_results = pts_merge::merge_test_results(file_get_contents(SAVE_RESULTS_DIR . $file_name . "/composite.xml"), file_get_contents(SAVE_RESULTS_DIR . $real_name));
		pts_client::save_test_result($file_name . "/composite.xml", $merged_results, true, $result_identifier);
	}

	return $real_name;
}
function pts_find_test_executable_dir($test_identifier, &$test_profile)
{
	$to_execute = null;
	$test_dir = TEST_ENV_DIR . $test_identifier . '/';
	$execute_binary = $test_profile->get_test_executable();

	if(is_executable($test_dir . $execute_binary) || (IS_WINDOWS && is_file($test_dir . $execute_binary)))
	{
		$to_execute = $test_dir;
	}

	return $to_execute;
}
function pts_extra_run_time_vars($test_identifier, $pts_test_arguments = null, $result_format = null)
{
	$vars = pts_tests::process_extra_test_variables($test_identifier);
	$vars["LC_ALL"] = "";
	$vars["LC_NUMERIC"] = "";
	$vars["LC_CTYPE"] = "";
	$vars["LC_MESSAGES"] = "";
	$vars["LANG"] = "";
	$vars["vblank_mode"] = 0;
	$vars["PTS_TEST_ARGUMENTS"] = "'" . $pts_test_arguments . "'";
	$vars["TEST_LIBRARIES_DIR"] = TEST_LIBRARIES_DIR;
	$vars["TIMED_KILL"] = TEST_LIBRARIES_DIR . "timed-kill.sh";
	$vars["PHP_BIN"] = PHP_BIN;

	return $vars;
}
function pts_run_test(&$test_run_manager, &$test_run_request)
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

	$parse_results_xml_file = is_file(($parse_results_xml_file = pts_tests::test_resources_location($test_identifier) . "parse-results.xml")) ? $parse_results_xml_file : false;

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

	$to_execute = pts_find_test_executable_dir($test_identifier, $test_run_request->test_profile);

	$pts_test_arguments = trim($test_run_request->test_profile->get_default_arguments() . " " . str_replace($test_run_request->test_profile->get_default_arguments(), "", $extra_arguments) . " " . $test_run_request->test_profile->get_default_post_arguments());
	$extra_runtime_variables = pts_extra_run_time_vars($test_identifier, $pts_test_arguments, $result_format);

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

			pts_test_profile_debug_message("Test Run Command: " . $test_run_command);

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

			pts_test_profile_debug_message("Test Result Value: " . $test_result);

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

			if(!pts_test_profile_debug_message("Log File At: " . $test_log_file))
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
function pts_test_profile_debug_message($message)
{
	$reported = false;

	if(pts_is_assignment("DEBUG_TEST_PROFILE"))
	{
		pts_client::$display->test_run_instance_error($message);
		$reported = true;
	}

	return $reported;
}

?>
