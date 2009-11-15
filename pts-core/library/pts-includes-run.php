<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
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

require_once(PTS_LIBRARY_PATH . "pts-includes-run_setup.php");
require_once(PTS_LIBRARY_PATH . "pts-includes-run_options.php");

function pts_cleanup_tests_to_run(&$to_run_identifiers)
{
	$skip_tests = (($e = getenv("SKIP_TESTS")) ? explode(",", $e) : false);

	for($i = 0; $i < count($to_run_identifiers); $i++)
	{
		$lower_identifier = strtolower($to_run_identifiers[$i]);

		if($skip_tests && in_array($lower_identifier, $skip_tests))
		{
			echo pts_string_header("Skipping test: " . $lower_identifier);
			unset($to_run_identifiers[$i]);
			continue;
		}
		else if(pts_is_test($lower_identifier))
		{
			$test_title = pts_test_read_xml($lower_identifier, P_TEST_TITLE);

			if(empty($test_title))
			{
				echo pts_string_header($lower_identifier . " is not a test.");
				unset($to_run_identifiers[$i]);
				continue;
			}
		}
		else if(pts_is_virtual_suite($lower_identifier))
		{
			foreach(pts_virtual_suite_tests($lower_identifier) as $virt_test)
			{
				array_push($to_run_identifiers, $virt_test);
			}
			unset($to_run_identifiers[$i]);
			continue;
		}

		$verify_install = pts_verify_test_installation($lower_identifier);

		if($verify_install == false)
		{
			// Eliminate this test, it's not properly installed
			unset($to_run_identifiers[$i]);
			continue;
		}
		else if(is_array($verify_install))
		{
			pts_set_assignment("NO_PROMPT_IN_RUN_ON_MISSING_TESTS", true);
			pts_run_option_next("install_test", $verify_install, pts_assignment_manager::get_all_assignments());
			pts_run_option_next("run_test", $to_run_identifiers, pts_assignment_manager::get_all_assignments());
			return false;
		}
			
		if(is_file($to_run_identifiers[$i]) && substr(basename($to_run_identifiers[$i]), -4) == ".svg")
		{
			// One of the arguments was an SVG results file, do prompts
			$test_extracted = pts_prompt_svg_result_options($to_run_identifiers[$i]);

			if(!empty($test_extracted))
			{
				$to_run_identifiers[$i] = $test_extracted;
			}
		}
	}

	return true;
}
function pts_verify_test_installation($identifiers)
{
	// Verify a test is installed
	$identifiers = pts_to_array($identifiers);
	$contains_a_suite = false;
	$tests_missing = array();
	$tests_installed = array();

	foreach($identifiers as $identifier)
	{
		if(!$contains_a_suite && (pts_is_suite($identifier) || pts_is_test_result($identifier)))
		{
			$contains_a_suite = true;
		}

		foreach(pts_contained_tests($identifier) as $test)
		{
			if(!pts_test_installed($test))
			{
				if(pts_test_supported($test))
				{
					pts_array_push($tests_missing, $test);
				}
			}
			else
			{
				pts_array_push($tests_installed, $test);
			}
		}
	}

	if(($test_missing_count = count($tests_missing)) > 0)
	{
		if(count($tests_missing) == 1)
		{
			echo pts_string_header($tests_missing[0] . " is not installed.\nTo install, run: phoronix-test-suite install " . $tests_missing[0]);
		}
		else
		{
			$message = "Multiple tests are not installed:\n\n";
			foreach($tests_missing as $single_package)
			{
				$message .= "- " . $single_package . "\n";
			}

			$message .= "\nTo install these tests, run: phoronix-test-suite install " . implode(" ", $tests_missing);

			echo pts_string_header($message);
		}

		if(!pts_read_assignment("AUTOMATED_MODE") && !pts_read_assignment("IS_BATCH_MODE") && !pts_read_assignment("NO_PROMPT_IN_RUN_ON_MISSING_TESTS"))
		{
			$stop_and_install = pts_bool_question("Would you like to install these tests now (Y/n)?", true);

			if($stop_and_install)
			{
				return $tests_missing;
			}
		}
	}

	return count($tests_installed) > 0 && ($test_missing_count == 0 || $contains_a_suite);
}
function pts_call_test_runs(&$test_run_manager, &$display_mode, &$tandem_xml = null)
{
	pts_unlink(PTS_USER_DIR . "halt-testing");
	pts_unlink(PTS_USER_DIR . "skip-test");

	$test_flag = true;
	$tests_to_run_count = $test_run_manager->get_test_count();
	$display_mode->test_run_process_start($test_run_manager);

	if(($total_loop_time_minutes = getenv("TOTAL_LOOP_TIME")) && is_numeric($total_loop_time_minutes) && $total_loop_time_minutes > 0)
	{
		$total_loop_time_seconds = $total_loop_time_minutes * 60;
		$loop_end_time = time() + $total_loop_time_seconds;

		echo pts_string_header("Estimated Run-Time: " . pts_format_time_string($total_loop_time_seconds, "SECONDS", true, 60));

		do
		{
			for($i = 0; $i < $tests_to_run_count && $test_flag && time() < $loop_end_time; $i++)
			{
				$test_flag = pts_process_test_run_request($test_run_manager, $tandem_xml, $display_mode, $i, 1, 1);
			}
		}
		while(time() < $loop_end_time && $test_flag);
	}
	else if(($total_loop_count = getenv("TOTAL_LOOP_COUNT")) && is_numeric($total_loop_count))
	{
		if(($estimated_length = pts_estimated_run_time($test_run_manager)) > 1)
		{
			echo pts_string_header("Estimated Run-Time: " . pts_format_time_string(($estimated_length * $total_loop_count), "SECONDS", true, 60));
		}

		for($loop = 0; $loop < $total_loop_count && $test_flag; $loop++)
		{
			for($i = 0; $i < $tests_to_run_count && $test_flag; $i++)
			{
				$test_flag = pts_process_test_run_request($test_run_manager, $tandem_xml, $display_mode, $i, ($loop * $tests_to_run_count + $i + 1), ($total_loop_count * $tests_to_run_count));
			}
		}
	}
	else
	{
		if(($estimated_length = pts_estimated_run_time($test_run_manager)) > 1)
		{
			echo pts_string_header("Estimated Run-Time: " . pts_format_time_string($estimated_length, "SECONDS", true, 60));
		}

		for($i = 0; $i < $tests_to_run_count && $test_flag; $i++)
		{
			$test_flag = pts_process_test_run_request($test_run_manager, $tandem_xml, $display_mode, $i, ($i + 1), $tests_to_run_count);
		}
	}

	pts_unlink(SAVE_RESULTS_DIR . $test_run_manager->get_file_name() . "/active.xml");

	foreach(glob(TEST_ENV_DIR . "*/cache-share-*.pt2so") as $cache_share_file)
	{
		unlink($cache_share_file);
	}
}
function pts_validate_test_installations_to_run(&$test_run_manager, &$display_mode)
{
	$failed_tests = array();
	$validated_run_requests = array();

	foreach($test_run_manager->get_tests_to_run() as $test_run_request)
	{
		if(!($test_run_request instanceOf pts_test_run_request))
		{
			// TODO: $test_run_request probably a pts_weighted_test_run_manager then, decide how to validate
			array_push($validated_run_requests, $test_run_request);
			continue;
		}

		// Validate the pts_test_run_request
		$test_identifier = $test_run_request->get_identifier();

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

		$test_profile = new pts_test_profile($test_identifier, $test_run_request->get_override_options());
		$test_type = $test_profile->get_test_hardware_type();

		if($test_type == "Graphics" && getenv("DISPLAY") == false)
		{
			$display_mode->test_run_error("No display server was found, cannot run " . $test_identifier);
			array_push($failed_tests, $test_identifier);
			continue;
		}

		if(getenv("NO_" . strtoupper($test_type) . "_TESTS") || (($e = getenv("SKIP_TESTS")) && in_array($test_identifier, explode(",", $e))))
		{
			array_push($failed_tests, $test_identifier);
			continue;
		}

		if($test_profile->is_root_required() && pts_read_assignment("IS_BATCH_MODE") && phodevi::read_property("system", "username") != "root")
		{
			$display_mode->test_run_error("Cannot run " . $test_identifier . " in batch mode as root access is required.");
			array_push($failed_tests, $test_identifier);
			continue;
		}

		if(pts_find_test_executable($test_identifier, $test_profile) == null)
		{
			$display_mode->test_run_error("The test executable for " . $test_identifier . " could not be found.");
			array_push($failed_tests, $test_identifier);
			continue;
		}

		array_push($validated_run_requests, $test_run_request);
	}

	$test_run_manager->set_tests_to_run($validated_run_requests);
}
function pts_process_test_run_request(&$test_run_manager, &$tandem_xml, &$display_mode, $run_index, $run_position = 1, $run_count = 1)
{
	$result = false;
	$test_run_request = $test_run_manager->get_test_to_run($run_index);

	if($test_run_request instanceOf pts_weighted_test_run_manager)
	{
		$test_run_requests = $test_run_request->get_tests_to_run();
		$weighted_value = $test_run_request->get_weight_initial_value();
		$is_weighted_run = true;
	}
	else
	{
		$test_run_requests = array($test_run_request);
		$is_weighted_run = false;
	}

	if($test_run_manager->get_file_name() != null)
	{
		$tandem_xml->saveXMLFile(SAVE_RESULTS_DIR . $test_run_manager->get_file_name() . "/active.xml");
	}

	foreach($test_run_requests as &$test_run_request)
	{
		if(pts_is_test($test_run_request->get_identifier()))
		{
			pts_set_assignment("TEST_RUN_POSITION", $run_position);
			pts_set_assignment("TEST_RUN_COUNT", $run_count);

			$result = pts_run_test($test_run_request, $display_mode);

			if($is_weighted_run)
			{
				if($result instanceOf pts_test_result)
				{
					$this_result = $result->get_result();
					$this_weight_expression = $test_run_request->get_weight_expression();
					$weighted_value = pts_evaluate_math_expression(str_replace("\$RESULT_VALUE", $this_result, str_replace("\$WEIGHTED_VALUE", $weighted_value, $this_weight_expression)));
				}
				else
				{
					return false;
				}
			}

			if(pts_unlink(PTS_USER_DIR . "halt-testing"))
			{
				// Stop the testing process entirely
				return false;
			}
			else if(pts_unlink(PTS_USER_DIR . "skip-test"))
			{
				// Just skip the current test and do not save the results, but continue testing
				continue;
			}

			if(($run_position == 1 && $run_count == 1) || $run_position < $run_count || $is_weighted_run)
			{
				sleep(pts_read_user_config(P_OPTION_TEST_SLEEPTIME, 5));
			}
		}
	}

	if($is_weighted_run)
	{
		$ws_xml_parser = new pts_suite_tandem_XmlReader($test_run_request->get_weight_suite_identifier());
		$bt_xml_parser = new pts_test_tandem_XmlReader($test_run_request->get_weight_test_profile());
		$result = new pts_test_result();

		if(($final_expression = $test_run_request->get_weight_final_expression()) != null)
		{
			$weighted_value = pts_evaluate_math_expression(str_replace("\$WEIGHTED_VALUE", $weighted_value, $final_expression));
		}

		$result->set_result($weighted_value);
		$result->set_result_scale($bt_xml_parser->getXMLValue(P_TEST_SCALE));
		$result->set_result_proportion($bt_xml_parser->getXMLValue(P_TEST_PROPORTION));
		$result->set_result_format($bt_xml_parser->getXMLValue(P_TEST_RESULTFORMAT));
		$result->set_attribute("EXTRA_ARGUMENTS", null); // TODO: build string as a composite of suite version + all test versions
		$result->set_attribute("TEST_IDENTIFIER", $test_run_request->get_weight_suite_identifier());
		$result->set_attribute("TEST_TITLE", $ws_xml_parser->getXMLValue(P_SUITE_TITLE));
		$result->set_attribute("TEST_VERSION", $ws_xml_parser->getXMLValue(P_SUITE_VERSION));
		$result->set_attribute("TEST_DESCRIPTION", $bt_xml_parser->getXMLValue(P_TEST_DESCRIPTION));
	}

	if($result instanceof pts_test_result)
	{
		$end_result = $result->get_result();
		$test_identifier = $test_run_manager->get_results_identifier();

		if(!empty($test_identifier) && count($result) > 0 && ((is_numeric($end_result) && $end_result > 0) || (!is_numeric($end_result) && strlen($end_result) > 2)))
		{
			$tandem_id = pts_request_new_id();
			pts_set_assignment("TEST_RAN", true);

			$tandem_xml->addXmlObject(P_RESULTS_TEST_TITLE, $tandem_id, $result->get_attribute("TEST_TITLE"));
			$tandem_xml->addXmlObject(P_RESULTS_TEST_VERSION, $tandem_id, $result->get_attribute("TEST_VERSION"));
			$tandem_xml->addXmlObject(P_RESULTS_TEST_ATTRIBUTES, $tandem_id, $result->get_attribute("TEST_DESCRIPTION"));
			$tandem_xml->addXmlObject(P_RESULTS_TEST_SCALE, $tandem_id, $result->get_result_scale());
			$tandem_xml->addXmlObject(P_RESULTS_TEST_PROPORTION, $tandem_id, $result->get_result_proportion());
			$tandem_xml->addXmlObject(P_RESULTS_TEST_RESULTFORMAT, $tandem_id, $result->get_result_format());
			$tandem_xml->addXmlObject(P_RESULTS_TEST_TESTNAME, $tandem_id, $result->get_attribute("TEST_IDENTIFIER"));
			$tandem_xml->addXmlObject(P_RESULTS_TEST_ARGUMENTS, $tandem_id, $result->get_attribute("EXTRA_ARGUMENTS"));
			$tandem_xml->addXmlObject(P_RESULTS_RESULTS_GROUP_IDENTIFIER, $tandem_id, $test_identifier, 5);
			$tandem_xml->addXmlObject(P_RESULTS_RESULTS_GROUP_VALUE, $tandem_id, $result->get_result(), 5);
			$tandem_xml->addXmlObject(P_RESULTS_RESULTS_GROUP_RAW, $tandem_id, $result->get_trial_results_string(), 5);
		}
		else
		{
			$test_run_manager->add_failed_test_run_request($test_run_request);
		}
	}

	return true;
}
function pts_save_test_file(&$results, $file_name)
{
	// Save the test file
	$j = 1;
	while(is_file(SAVE_RESULTS_DIR . $file_name . "/test-" . $j . ".xml"))
	{
		$j++;
	}

	$real_name = $file_name . "/test-" . $j . ".xml";

	pts_save_result($real_name, $results->getXML());

	if(!is_file(SAVE_RESULTS_DIR . $file_name . "/composite.xml"))
	{
		pts_save_result($file_name . "/composite.xml", file_get_contents(SAVE_RESULTS_DIR . $real_name));
	}
	else
	{
		// Merge Results
		$merged_results = pts_merge_test_results(file_get_contents(SAVE_RESULTS_DIR . $file_name . "/composite.xml"), file_get_contents(SAVE_RESULTS_DIR . $real_name));
		pts_save_result($file_name . "/composite.xml", $merged_results);
	}

	return $real_name;
}
function pts_find_test_executable($test_identifier, &$test_profile)
{
	$to_execute = null;
	$possible_paths = array_merge(array(TEST_ENV_DIR . $test_identifier . "/"), pts_trim_explode(",", $test_profile->get_test_executable_paths()));
	$execute_binary = $test_profile->get_test_executable();

	foreach($possible_paths as $possible_dir)
	{
		if(is_executable($possible_dir . $execute_binary))
		{
			$to_execute = $possible_dir;
			break;
		}
	}

	return $to_execute;
}
function pts_run_test(&$test_run_request, &$display_mode)
{
	$test_identifier = $test_run_request->get_identifier();
	$extra_arguments = $test_run_request->get_arguments();
	$arguments_description = $test_run_request->get_arguments_description();

	// Do the actual test running process
	$pts_test_result = new pts_test_result();
	$test_directory = TEST_ENV_DIR . $test_identifier . "/";

	if(!is_dir($test_directory))
	{
		return false;
	}

	$lock_file = $test_directory . "run_lock";
	$test_fp = null;
	if(!pts_create_lock($lock_file, $test_fp))
	{
		$display_mode->test_run_error("The " . $test_identifier . " test is already running.");
		return false;
	}

	$test_profile = new pts_test_profile($test_identifier, $test_run_request->get_override_options());
	$execute_binary = $test_profile->get_test_executable();
	$times_to_run = $test_profile->get_times_to_run();
	$ignore_runs = $test_profile->get_runs_to_ignore();
	$result_format = $test_profile->get_test_result_format();
	$test_type = $test_profile->get_test_hardware_type();
	$allow_cache_share = $test_profile->allow_cache_share();
	$min_length = $test_profile->get_min_length();
	$max_length = $test_profile->get_max_length();

	if($test_profile->get_environment_testing_size() != -1 && ceil(disk_free_space(TEST_ENV_DIR) / 1048576) < $test_profile->get_environment_testing_size())
	{
		// Ensure enough space is available on disk during testing process
		$display_mode->test_run_error("There is not enough space (at " . TEST_ENV_DIR . ") for this test to run.");
		pts_release_lock($test_fp, $lock_file);
		return false;
	}

	if(($force_runs = getenv("FORCE_TIMES_TO_RUN")) && is_numeric($force_runs))
	{
		$times_to_run = $force_runs;
	}

	if($times_to_run < 1 || (strlen($result_format) > 6 && substr($result_format, 0, 6) == "MULTI_"))
	{
		// Currently tests that output multiple results in one run can only be run once
		$times_to_run = 1;
	}

	$to_execute = pts_find_test_executable($test_identifier, $test_profile);

	if(pts_test_needs_updated_install($test_identifier))
	{
		echo pts_string_header("NOTE: This test installation is out of date.\nIt is recommended that " . $test_identifier . " be re-installed.");
	}

	$pts_test_arguments = trim($test_profile->get_default_arguments() . " " . str_replace($test_profile->get_default_arguments(), "", $extra_arguments));
	$extra_runtime_variables = pts_extra_run_time_vars($test_identifier, $pts_test_arguments);

	// Start
	$cache_share_pt2so = $test_directory . "cache-share-" . PTS_INIT_TIME . ".pt2so";
	$cache_share_present = $allow_cache_share && is_file($cache_share_pt2so);
	$pts_test_result->set_attribute("TEST_TITLE", $test_profile->get_test_title());
	$pts_test_result->set_attribute("TEST_VERSION", $test_profile->get_version());
	$pts_test_result->set_attribute("TEST_DESCRIPTION", $arguments_description);
	$pts_test_result->set_attribute("TEST_IDENTIFIER", $test_identifier);
	$pts_test_result->set_attribute("TIMES_TO_RUN", $times_to_run);
	$pts_test_result->set_result_format($result_format);
	$pts_test_result->set_result_proportion($test_profile->get_test_proportion());
	$pts_test_result->set_result_scale($test_profile->get_test_scale());
	$pts_test_result->set_result_quantifier($test_profile->get_test_quantifier());
	pts_module_process("__pre_test_run", $pts_test_result);

	$time_test_start = time();

	if(!$cache_share_present)
	{
		echo pts_call_test_script($test_identifier, "pre", "\nRunning Pre-Test Scripts...\n", $test_directory, $extra_runtime_variables);
	}

	pts_user_message($test_profile->get_pre_run_message());
	$runtime_identifier = pts_unique_runtime_identifier();
	$execute_binary_prepend = "";

	if(!$cache_share_present && $test_profile->is_root_required())
	{
		$execute_binary_prepend = TEST_LIBRARIES_DIR . "root-access.sh ";
	}

	if($allow_cache_share && !is_file($cache_share_pt2so))
	{
		$cache_share = new pts_storage_object(false, false);
	}

	$display_mode->test_run_start($pts_test_result);
	$archive_log_files = pts_is_assignment("TEST_RESULTS_IDENTIFIER") && (pts_string_bool(pts_read_user_config(P_OPTION_LOG_BENCHMARKFILES, "FALSE")) || pts_read_assignment("IS_PCQS_MODE") || pts_read_assignment("IS_BATCH_MODE"));

	for($i = 0, $abort_testing = false, $time_test_start_actual = time(), $defined_times_to_run = $times_to_run; $i < $times_to_run && !$abort_testing; $i++)
	{
		$display_mode->test_run_instance_header($pts_test_result, ($i + 1), $times_to_run);
		$benchmark_log_file = $test_directory . $test_identifier . "-" . $runtime_identifier . "-" . ($i + 1) . ".log";

		$test_extra_runtime_variables = array_merge($extra_runtime_variables, array(
		"LOG_FILE" => $benchmark_log_file
		));

		$restored_from_cache = false;
		if($cache_share_present)
		{
			$cache_share = pts_storage_object::recover_from_file($cache_share_pt2so);

			if($cache_share)
			{
				$test_results = $cache_share->read_object("test_results_output_" . $i);
				$test_extra_runtime_variables["LOG_FILE"] = $cache_share->read_object("log_file_location_" . $i);
				file_put_contents($test_extra_runtime_variables["LOG_FILE"], $cache_share->read_object("log_file_" . $i));
				$restored_from_cache = true;
			}

			unset($cache_share);
		}

		if(!$restored_from_cache)
		{
			$test_run_command = "cd " . $to_execute . " && " . $execute_binary_prepend . "./" . $execute_binary . " " . $pts_test_arguments . " 2>&1";

			pts_test_profile_debug_message($display_mode, "Test Run Command: " . $test_run_command);

			$test_run_time_start = time();
			$test_results = pts_exec($test_run_command, $test_extra_runtime_variables);
			$test_run_time = time() - $test_run_time_start;
		}
		

		if(!isset($test_results[10240]) || pts_is_assignment("DEBUG_TEST_PROFILE"))
		{
			$display_mode->test_run_output($test_results);
		}

		if(is_file($benchmark_log_file) && trim($test_results) == "" && (filesize($benchmark_log_file) < 10240 || pts_is_assignment("DEBUG_TEST_PROFILE")))
		{
			$benchmark_log_file_contents = file_get_contents($benchmark_log_file);
			$display_mode->test_run_output($benchmark_log_file_contents);
			unset($benchmark_log_file_contents);
		}

		$exit_status_pass = true;
		if(is_file(TEST_ENV_DIR . $test_identifier . "/test-exit-status"))
		{
			// If the test script writes its exit status to ~/test-exit-status, if it's non-zero the test run failed
			$exit_status = pts_file_get_contents(TEST_ENV_DIR . $test_identifier . "/test-exit-status");
			unlink(TEST_ENV_DIR . $test_identifier . "/test-exit-status");

			if($exit_status != 0 && !IS_BSD)
			{
				$display_mode->test_run_error("The test exited with a non-zero exit status. Test run failed.");
				$exit_status_pass = false;
			}
		}

		if(!in_array(($i + 1), $ignore_runs) && $exit_status_pass)
		{
			$test_extra_runtime_variables_post = $test_extra_runtime_variables;
			if(is_file(TEST_ENV_DIR . $test_identifier . "/pts-timer"))
			{
				$run_time = pts_file_get_contents(TEST_ENV_DIR . $test_identifier . "/pts-timer");
				unlink(TEST_ENV_DIR . $test_identifier . "/pts-timer");

				if(is_numeric($run_time))
				{
					$test_extra_runtime_variables_post = array_merge($test_extra_runtime_variables_post, array("TIMER_RESULT" => $run_time));
				}
			}
			else
			{
				$run_time = 0;
			}

			if(is_file($benchmark_log_file))
			{
				$test_results = "";
			}

			$test_results = pts_call_test_script($test_identifier, "parse-results", null, $test_results, $test_extra_runtime_variables_post);

			if(empty($test_results) && $run_time > 1)
			{
				$test_results = $run_time;
			}

			pts_test_profile_debug_message($display_mode, "Test Result Value: " . $test_results);
			$validate_result = trim(pts_call_test_script($test_identifier, "validate-result", null, $test_results, $test_extra_runtime_variables_post));

			if(!empty($validate_result) && !pts_string_bool($validate_result))
			{
				$test_results = null;
			}

			if(!empty($test_results))
			{
				$pts_test_result->add_trial_run_result($test_results);
			}

			if($allow_cache_share && !is_file($cache_share_pt2so))
			{
				$cache_share->add_object("test_results_output_" . $i, $test_results);
				$cache_share->add_object("log_file_location_" . $i, $test_extra_runtime_variables["LOG_FILE"]);
				$cache_share->add_object("log_file_" . $i, (is_file($benchmark_log_file) ? file_get_contents($benchmark_log_file) : null));
			}
		}

		if($i == ($times_to_run - 1) && $pts_test_result->trial_run_count() > 2 && pts_read_assignment("PTS_STATS_DYNAMIC_RUN_COUNT") && $times_to_run < ($defined_times_to_run * 2))
		{
			// Determine if results are statistically significant, otherwise up the run count
			$std_dev = pts_percent_standard_deviation($pts_test_result->get_trial_results());

			if(($ex_file = pts_read_assignment("PTS_STATS_EXPORT_TO")) != null && is_executable($ex_file) || is_executable(($ex_file = PTS_USER_DIR . $ex_file)))
			{
				$exit_status = trim(shell_exec($ex_file . " " . $pts_test_result->get_trial_results_string() . " > /dev/null 2>&1; echo $?"));

				switch($exit_status)
				{
					case 1:
						// Run the test again
						$request_increase = true;
						break;
					case 2:
						// Results are bad, abandon testing and do not record results
						$request_increase = false;
						$abort_testing = true;
						break;
					default:
						// Return was 0, results are valid, or was some other exit status
						$request_increase = false;
						break;

				}
			}
			else
			{
				$request_increase = false;
			}

			if(($request_increase || $std_dev >= pts_read_assignment("PTS_STATS_STD_DEV_THRESHOLD")) && floor($test_run_time / 60) < pts_read_assignment("PTS_STATS_NO_ON_LENGTH"))
			{
				$times_to_run++;
				$pts_test_result->set_attribute("TIMES_TO_RUN", $times_to_run);
			}
		}

		if($times_to_run > 1 && $i < ($times_to_run - 1))
		{
			if(!$cache_share_present)
			{
				echo pts_call_test_script($test_identifier, "interim", null, $test_directory, $extra_runtime_variables);
				sleep(2); // Rest for a moment between tests
			}

			pts_module_process("__interim_test_run", $pts_test_result);
		}

		if(is_file($benchmark_log_file))
		{
			if($archive_log_files)
			{
				$backup_log_dir = SAVE_RESULTS_DIR . pts_read_assignment("SAVE_FILE_NAME") . "/test-logs/" . pts_read_assignment("TEST_RESULTS_IDENTIFIER") . "/";
				$backup_filename = basename($benchmark_log_file);

				pts_mkdir($backup_log_dir, 0777, true);
				copy($benchmark_log_file, $backup_log_dir . $backup_filename);
			}

			if(!pts_test_profile_debug_message($display_mode, "Log File At: " . $benchmark_log_file))
			{
				unlink($benchmark_log_file);
			}
		}

		if(is_file(PTS_USER_DIR . "halt-testing") || is_file(PTS_USER_DIR . "skip-test"))
		{
			pts_release_lock($test_fp, $lock_file);
			return false;
		}
	}

	$time_test_end_actual = time();

	if(!$cache_share_present)
	{
		echo pts_call_test_script($test_identifier, "post", null, $test_directory, $extra_runtime_variables);
	}

	if($abort_testing)
	{
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
			return false;
		}
	}

	if(!empty($max_length))
	{
		if($max_length < $time_test_elapsed_actual)
		{
			// The test took too much time, results are not valid
			return false;
		}
	}

	if($allow_cache_share && !is_file($cache_share_pt2so) && $cache_share instanceOf pts_storage_object)
	{
		$cache_share->save_to_file($cache_share_pt2so);
		unset($cache_share);
	}

	if(pts_is_assignment("TEST_RESULTS_IDENTIFIER") && (pts_string_bool(pts_read_user_config(P_OPTION_LOG_INSTALLATION, "FALSE")) || pts_read_assignment("IS_PCQS_MODE") || pts_read_assignment("IS_BATCH_MODE")))
	{
		if(is_file(TEST_ENV_DIR . $test_identifier . "/install.log"))
		{
			$backup_log_dir = SAVE_RESULTS_DIR . pts_read_assignment("SAVE_FILE_NAME") . "/installation-logs/" . pts_read_assignment("TEST_RESULTS_IDENTIFIER") . "/";
			pts_mkdir($backup_log_dir, 0777, true);
			copy(TEST_ENV_DIR . $test_identifier . "/install.log", $backup_log_dir . $test_identifier . ".log");
		}
	}

	if(is_file($test_directory . "/pts-test-note"))
	{
		pts_test_notes_manager::add_note(pts_file_get_contents($test_directory . "/pts-test-note"));
		unlink($test_directory . "pts-test-note");
	}

	// Fill in missing test details

	if(empty($arguments_description))
	{
		$arguments_description = $test_profile->get_test_subtitle();
	}

	$file_var_checks = array(
	"result_scale" => "pts-results-scale",
	"result_proportion" => "pts-results-proportion",
	"result_quantifier" => "pts-results-quantifier",
	"test_version" => "pts-test-version",
	"arguments_description" => "pts-test-description"
	);

	foreach($file_var_checks as $var_check => $file_check)
	{
		if(empty($$var_check) && is_file($test_directory . $file_check))
		{
			$$var_check = pts_file_get_contents($test_directory . $file_check);
			unlink($test_directory . $file_check);
		}
	}

	if(empty($arguments_description))
	{
		$arguments_description = "Phoronix Test Suite v" . PTS_VERSION;
	}

	foreach(pts_env_variables() as $key => $value)
	{
		$arguments_description = str_replace("$" . $key, $value, $arguments_description);

		if(!in_array($key, array("VIDEO_MEMORY", "NUM_CPU_CORES", "NUM_CPU_JOBS")))
		{
			$extra_arguments = str_replace("$" . $key, $value, $extra_arguments);
		}
	}

	if($test_type == "Graphics")
	{
		// TODO: integrate such reporting capabilities through a Phodevi interface
		$extra_gfx_settings = array();
		$aa_level = phodevi::read_property("gpu", "aa-level");
		$af_level = phodevi::read_property("gpu", "af-level");

		if(!empty($aa_level))
		{
			array_push($extra_gfx_settings, "AA: " . $aa_level);
		}
		if(!empty($af_level))
		{
			array_push($extra_gfx_settings, "AF: " . $af_level);
		}

		if(count($extra_gfx_settings) > 0)
		{
			$extra_gfx_settings = implode(" - ", $extra_gfx_settings);

			if(strpos($arguments_description, $extra_gfx_settings) === false)
			{
				if($arguments_description != null)
				{
					$arguments_description .= " | ";
				}

				$arguments_description .= $extra_gfx_settings;
			}
		}
	}
	else if($test_type == "Disk")
	{
		$disk_scheduler = phodevi::read_property("disk", "scheduler");

		if(!empty($disk_scheduler))
		{
			pts_test_notes_manager::add_note("Disk Scheduler: " . $disk_scheduler);
		}
	}

	// Result Calculation
	$pts_test_result->set_attribute("TEST_DESCRIPTION", $arguments_description);
	$pts_test_result->set_attribute("EXTRA_ARGUMENTS", $extra_arguments);
	$pts_test_result->set_attribute("ELAPSED_TIME", $time_test_elapsed);
	$pts_test_result->calculate_end_result(); // Process results

	$display_mode->test_run_end($pts_test_result);

	pts_user_message($test_profile->get_post_run_message());
	pts_module_process("__post_test_run", $pts_test_result);
	$report_elapsed_time = !$cache_share_present && $pts_test_result->get_result() != 0;
	pts_test_refresh_install_xml($test_identifier, ($report_elapsed_time ? $time_test_elapsed : 0));

	if($report_elapsed_time && pts_anonymous_usage_reporting() && $time_test_elapsed >= 60)
	{
		pts_global_upload_usage_data("test_complete", $pts_test_result);
	}

	// Remove lock
	pts_release_lock($test_fp, $lock_file);

	return $result_format == "NO_RESULT" ? false : $pts_test_result;
}
function pts_test_refresh_install_xml($identifier, $this_test_duration = 0)
{
	// Refresh an install XML for pts-install.xml
	// Similar to pts_test_generate_install_xml()
 	$xml_parser = new pts_installed_test_tandem_XmlReader($identifier, false);
	$xml_writer = new tandem_XmlWriter();

	$test_duration = $xml_parser->getXMLValue(P_INSTALL_TEST_AVG_RUNTIME);
	if(!is_numeric($test_duration))
	{
		$test_duration = $this_test_duration;
	}
	if(is_numeric($this_test_duration) && $this_test_duration > 0)
	{
		$test_duration = ceil((($test_duration * $xml_parser->getXMLValue(P_INSTALL_TEST_TIMESRUN)) + $this_test_duration) / ($xml_parser->getXMLValue(P_INSTALL_TEST_TIMESRUN) + 1));
	}

	$test_version = $xml_parser->getXMLValue(P_INSTALL_TEST_VERSION);
	if(empty($test_version))
	{
		$test_version = pts_test_profile_version($identifier);
	}

	$test_checksum = $xml_parser->getXMLValue(P_INSTALL_TEST_CHECKSUM);
	if(empty($test_checksum))
	{
		$test_checksum = pts_test_checksum_installer($identifier);
	}

	$sys_identifier = $xml_parser->getXMLValue(P_INSTALL_TEST_SYSIDENTIFY);
	if(empty($sys_identifier))
	{
		$sys_identifier = pts_system_identifier_string();
	}

	$install_time = $xml_parser->getXMLValue(P_INSTALL_TEST_INSTALLTIME);
	if(empty($install_time))
	{
		$install_time = date("Y-m-d H:i:s");
	}

	$times_run = $xml_parser->getXMLValue(P_INSTALL_TEST_TIMESRUN);
	if(empty($times_run))
	{
		$times_run = 0;
	}
	$times_run++;

	$xml_writer->addXmlObject(P_INSTALL_TEST_NAME, 1, $identifier);
	$xml_writer->addXmlObject(P_INSTALL_TEST_VERSION, 1, $test_version);
	$xml_writer->addXmlObject(P_INSTALL_TEST_CHECKSUM, 1, $test_checksum);
	$xml_writer->addXmlObject(P_INSTALL_TEST_SYSIDENTIFY, 1, $sys_identifier);
	$xml_writer->addXmlObject(P_INSTALL_TEST_INSTALLTIME, 2, $install_time);
	$xml_writer->addXmlObject(P_INSTALL_TEST_LASTRUNTIME, 2, date("Y-m-d H:i:s"));
	$xml_writer->addXmlObject(P_INSTALL_TEST_TIMESRUN, 2, $times_run);
	$xml_writer->addXmlObject(P_INSTALL_TEST_AVG_RUNTIME, 2, $test_duration);
	$xml_writer->addXmlObject(P_INSTALL_TEST_LATEST_RUNTIME, 2, $this_test_duration);

	$xml_writer->saveXMLFile(TEST_ENV_DIR . $identifier . "/pts-install.xml");
}
function pts_test_profile_debug_message(&$display_mode, $message)
{
	$reported = false;

	if(pts_is_assignment("DEBUG_TEST_PROFILE"))
	{
		$display_mode->test_run_error($message);
		$reported = true;
	}

	return $reported;
}

?>
