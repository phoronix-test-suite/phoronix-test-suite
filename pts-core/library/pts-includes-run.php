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
			$xml_parser = new pts_test_tandem_XmlReader($lower_identifier);
			$test_title = $xml_parser->getXMLValue(P_TEST_TITLE);

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

		if(pts_verify_test_installation($lower_identifier) == false)
		{
			// Eliminate this test, it's not properly installed
			unset($to_run_identifiers[$i]);
			continue;
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
}
function pts_verify_test_installation($identifiers)
{
	// Verify a test is installed
	$identifiers_o = $identifiers;
	$tests = array();
	$identifiers = pts_to_array($identifiers);

	foreach($identifiers as $identifier)
	{
		foreach(pts_contained_tests($identifier) as $this_test)
		{
			array_push($tests, $this_test);
		}
	}

	$tests = array_unique($tests);
	$needs_installing = array();
	$pass_count = 0;
	$valid_op = true;

	foreach($tests as $test)
	{
		if(!pts_test_installed($test))
		{
			if(pts_test_supported($test))
			{
				array_push($needs_installing, $test);
			}
			else
			{
				$valid_op = false;
			}
		}
		else
		{
			$pass_count++;
		}
	}
	
	if(count($needs_installing) > 0)
	{
		$needs_installing = array_unique($needs_installing);

		if(count($needs_installing) == 1)
		{
			echo pts_string_header($needs_installing[0] . " isn't installed.\nTo install, run: phoronix-test-suite install " . $needs_installing[0]);
		}
		else
		{
			$message = "Multiple tests need to be installed before proceeding:\n\n";
			foreach($needs_installing as $single_package)
			{
				$message .= "- " . $single_package . "\n";
			}

			$message .= "\nTo install these tests, run: phoronix-test-suite install " . implode(" ", $identifiers);

			echo pts_string_header($message);
		}

		if(pts_is_test($identifiers_o))
		{
			$valid_op = false;
		}
	}

	return $valid_op;
}
function pts_call_test_runs(&$test_run_manager, &$display_mode, &$tandem_xml = null)
{
	pts_unlink(PTS_USER_DIR . "halt-testing");

	$test_flag = true;
	$results_identifier = $test_run_manager->get_results_identifier();
	$save_name = $test_run_manager->get_file_name();
	$tests_to_run = $test_run_manager->get_tests_to_run();
	$tests_to_run_count = count($tests_to_run);

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
				$test_flag = pts_process_test_run_request($tandem_xml, $results_identifier, $tests_to_run[$i], $display_mode, $save_name, 1, 1);
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
				$test_flag = pts_process_test_run_request($tandem_xml, $results_identifier, $tests_to_run[$i], $display_mode, $save_name, ($loop * $tests_to_run_count + $i + 1), ($total_loop_count * $tests_to_run_count));
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
			$test_flag = pts_process_test_run_request($tandem_xml, $results_identifier, $tests_to_run[$i], $display_mode, $save_name, ($i + 1), $tests_to_run_count);
		}
	}

	pts_unlink(SAVE_RESULTS_DIR . $save_name . "/active.xml");

	foreach(glob(TEST_ENV_DIR . "*/cache-share-*.pt2so") as $cache_share_file)
	{
		unlink($cache_share_file);
	}
}
function pts_process_test_run_request(&$tandem_xml, $identifier, $pts_run, &$display_mode, $save_name = null, $run_position = 1, $run_count = 1)
{
	$result = false;

	if($pts_run instanceOf pts_weighted_test_run_manager)
	{
		$test_run_requests = $pts_run->get_tests_to_run();
		$weighted_value = $pts_run->get_weight_initial_value();
		$is_weighted_run = true;
	}
	else
	{
		$test_run_requests = array($pts_run);
		$is_weighted_run = false;
	}

	$active_xml = SAVE_RESULTS_DIR . $save_name . "/active.xml";
	if($save_name != null)
	{
		$tandem_xml->saveXMLFile($active_xml);
	}

	foreach($test_run_requests as $test_run_request)
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
				return false;
			}

			if(($run_position == 1 && $run_count == 1) || $run_position < $run_count || $is_weighted_run)
			{
				sleep(pts_read_user_config(P_OPTION_TEST_SLEEPTIME, 5));
			}
		}
	}

	if($is_weighted_run)
	{
		$ws_xml_parser = new pts_suite_tandem_XmlReader($pts_run->get_weight_suite_identifier());
		$bt_xml_parser = new pts_test_tandem_XmlReader($pts_run->get_weight_test_profile());
		$result = new pts_test_result();

		if(($final_expression = $pts_run->get_weight_final_expression()) != null)
		{
			$weighted_value = pts_evaluate_math_expression(str_replace("\$WEIGHTED_VALUE", $weighted_value, $final_expression));
		}

		$result->set_result($weighted_value);
		$result->set_result_scale($bt_xml_parser->getXMLValue(P_TEST_SCALE));
		$result->set_result_proportion($bt_xml_parser->getXMLValue(P_TEST_PROPORTION));
		$result->set_result_format($bt_xml_parser->getXMLValue(P_TEST_RESULTFORMAT));
		$result->set_attribute("EXTRA_ARGUMENTS", null); // TODO: build string as a composite of suite version + all test versions
		$result->set_attribute("TEST_IDENTIFIER", $pts_run->get_weight_suite_identifier());
		$result->set_attribute("TEST_TITLE", $ws_xml_parser->getXMLValue(P_SUITE_TITLE));
		$result->set_attribute("TEST_VERSION", $ws_xml_parser->getXMLValue(P_SUITE_VERSION));
		$result->set_attribute("TEST_DESCRIPTION", $bt_xml_parser->getXMLValue(P_TEST_DESCRIPTION));
	}

	if($result instanceof pts_test_result)
	{
		$end_result = $result->get_result();

		if(!empty($identifier) && count($result) > 0 && ((is_numeric($end_result) && $end_result > 0) || (!is_numeric($end_result) && strlen($end_result) > 2)))
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
			$tandem_xml->addXmlObject(P_RESULTS_RESULTS_GROUP_IDENTIFIER, $tandem_id, $identifier, 5);
			$tandem_xml->addXmlObject(P_RESULTS_RESULTS_GROUP_VALUE, $tandem_id, $result->get_result(), 5);
			$tandem_xml->addXmlObject(P_RESULTS_RESULTS_GROUP_RAW, $tandem_id, $result->get_trial_results_string(), 5);
		}
	}

	return true;
}
function pts_save_test_file($file_name, &$results = null, $raw_text = null)
{
	// Save the test file
	$j = 1;
	while(is_file(SAVE_RESULTS_DIR . $file_name . "/test-" . $j . ".xml"))
	{
		$j++;
	}

	$real_name = $file_name . "/test-" . $j . ".xml";

	if($results != null)
	{
		$r_file = $results->getXML();
	}
	else if($raw_text != null)
	{
		$r_file = $raw_text;
	}
	else
	{
		return false;
	}

	pts_save_result($real_name, $r_file);

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
function pts_run_test(&$test_run_request, &$display_mode)
{
	$test_identifier = $test_run_request->get_identifier();
	$extra_arguments = $test_run_request->get_arguments();
	$arguments_description = $test_run_request->get_arguments_description();
	$override_test_options = $test_run_request->get_override_options();

	// Do the actual test running process
	$pts_test_result = new pts_test_result();
	$test_directory = TEST_ENV_DIR . $test_identifier . "/";

	if(!is_dir($test_directory))
	{
		return $pts_test_result;
	}

	$lock_file = $test_directory . "run_lock";
	$test_fp = null;
	if(!pts_create_lock($lock_file, $test_fp))
	{
		$display_mode->test_run_error("The " . $test_identifier . " test is already running.");
		return $pts_test_result;
	}

	$xml_parser = new pts_test_tandem_XmlReader($test_identifier);
	$xml_parser->overrideXMLValues($override_test_options);
	$execute_binary = $xml_parser->getXMLValue(P_TEST_EXECUTABLE);
	$test_title = $xml_parser->getXMLValue(P_TEST_TITLE);
	$test_version = $xml_parser->getXMLValue(P_TEST_VERSION);
	$times_to_run = intval($xml_parser->getXMLValue(P_TEST_RUNCOUNT));
	$ignore_runs = pts_trim_explode(",", $xml_parser->getXMLValue(P_TEST_IGNORERUNS));
	$pre_run_message = $xml_parser->getXMLValue(P_TEST_PRERUNMSG);
	$post_run_message = $xml_parser->getXMLValue(P_TEST_POSTRUNMSG);
	$result_scale = $xml_parser->getXMLValue(P_TEST_SCALE);
	$result_proportion = $xml_parser->getXMLValue(P_TEST_PROPORTION);
	$result_format = $xml_parser->getXMLValue(P_TEST_RESULTFORMAT);
	$result_quantifier = $xml_parser->getXMLValue(P_TEST_QUANTIFIER);
	$arg_identifier = $xml_parser->getXMLArrayValues(P_TEST_OPTIONS_IDENTIFIER);
	$execute_path = $xml_parser->getXMLValue(P_TEST_POSSIBLEPATHS);
	$default_arguments = $xml_parser->getXMLValue(P_TEST_DEFAULTARGUMENTS);
	$test_type = $xml_parser->getXMLValue(P_TEST_HARDWARE_TYPE);
	$root_required = $xml_parser->getXMLValue(P_TEST_ROOTNEEDED) == "TRUE";
	$allow_cache_share = $xml_parser->getXMLValue(P_TEST_ALLOW_CACHE_SHARE) == "TRUE";
	$env_testing_size = $xml_parser->getXMLValue(P_TEST_ENVIRONMENT_TESTING_SIZE);

	if($test_type == "Graphics" && getenv("DISPLAY") == false)
	{
		pts_release_lock($test_fp, $lock_file);
		return $pts_test_result;
	}

	if(getenv("NO_" . strtoupper($test_type) . "_TESTS") || (($e = getenv("SKIP_TESTS")) && in_array($test_identifier, explode(",", $e))))
	{
		pts_release_lock($test_fp, $lock_file);
		return $pts_test_result;
	}

	if(!empty($env_testing_size) && ceil(disk_free_space(TEST_ENV_DIR) / 1048576) < $env_testing_size)
	{
		// Ensure enough space is available on disk during testing process
		$display_mode->test_run_error("There is not enough space (at " . TEST_ENV_DIR . ") for this test to run. " . $env_testing_size . " MB of space is needed.");
		pts_release_lock($test_fp, $lock_file);
		return $pts_test_result;
	}

	if(empty($result_format))
	{
		$result_format = "BAR_GRAPH";
	}
	else if(strlen($result_format) > 6 && substr($result_format, 0, 6) == "MULTI_") // Currently tests that output multiple results in one run can only be run once
	{
		$times_to_run = 1;
	}

	if(($force_runs = getenv("FORCE_TIMES_TO_RUN")) && is_int($force_runs))
	{
		$times_to_run = $force_runs;
	}
	else if(empty($times_to_run) || !is_int($times_to_run))
	{
		$times_to_run = 3;
	}

	if(!empty($test_type))
	{
		pts_set_assignment_once("TEST_" . strtoupper($test_type), 1);
	}

	if(empty($execute_binary))
	{
		$execute_binary = $test_identifier;
	}

	$execute_path_check = pts_trim_explode(",", $execute_path);
	array_push($execute_path_check, $test_directory);
	$to_execute = null;

	while(count($execute_path_check) > 0)
	{
		$path_check = array_pop($execute_path_check);

		if(is_executable($path_check . $execute_binary))
		{
			$to_execute = $path_check;
		}	
	}

	if(empty($to_execute))
	{
		$display_mode->test_run_error("The test executable for " . $test_identifier . " could not be found. Skipping test.");
		pts_release_lock($test_fp, $lock_file);
		return $pts_test_result;
	}

	if(pts_test_needs_updated_install($test_identifier))
	{
		echo pts_string_header("NOTE: This test installation is out of date.\nFor best results, " . $test_title . " should be re-installed.");
	}

	$pts_test_arguments = trim($default_arguments . " " . str_replace($default_arguments, "", $extra_arguments));
	$extra_runtime_variables = pts_run_additional_vars($test_identifier);
	$extra_runtime_variables["LC_ALL"] = "";
	$extra_runtime_variables["LC_CTYPE"] = "";
	$extra_runtime_variables["LC_MESSAGES"] = "";
	$extra_runtime_variables["LANG"] = "";
	$extra_runtime_variables["PTS_TEST_ARGUMENTS"] = "'" . $pts_test_arguments . "'";
	$extra_runtime_variables["TEST_LIBRARIES_DIR"] = TEST_LIBRARIES_DIR;
	$extra_runtime_variables["TIMER_START"] = TEST_LIBRARIES_DIR . "timer-start.sh";
	$extra_runtime_variables["TIMER_STOP"] = TEST_LIBRARIES_DIR . "timer-stop.sh";
	$extra_runtime_variables["TIMED_KILL"] = TEST_LIBRARIES_DIR . "timed-kill.sh";
	$extra_runtime_variables["SYSTEM_MONITOR_START"] = TEST_LIBRARIES_DIR . "system-monitoring-start.sh";
	$extra_runtime_variables["SYSTEM_MONITOR_STOP"] = TEST_LIBRARIES_DIR . "system-monitoring-stop.sh";
	$extra_runtime_variables["PHP_BIN"] = PHP_BIN;

	// Start
	$cache_share_pt2so = $test_directory . "cache-share-" . PTS_INIT_TIME . ".pt2so";
	$pts_test_result->set_attribute("TEST_TITLE", $test_title);
	$pts_test_result->set_attribute("TEST_DESCRIPTION", $arguments_description);
	$pts_test_result->set_attribute("TEST_IDENTIFIER", $test_identifier);
	$pts_test_result->set_attribute("TIMES_TO_RUN", $times_to_run);
	pts_module_process("__pre_test_run", $pts_test_result);

	$time_test_start = time();

	if(!($allow_cache_share && is_file($cache_share_pt2so)))
	{
		echo pts_call_test_script($test_identifier, "pre", "\nRunning Pre-Test Scripts...\n", $test_directory, $extra_runtime_variables);
	}

	pts_user_message($pre_run_message);

	$runtime_identifier = pts_unique_runtime_identifier();

	$execute_binary_prepend = "";

	if($root_required)
	{
		$execute_binary_prepend = TEST_LIBRARIES_DIR . "root-access.sh ";
	}

	if($allow_cache_share && !is_file($cache_share_pt2so))
	{
		$cache_share = new pts_storage_object(false, false);
	}

	$display_mode->test_run_start($pts_test_result);
	$defined_times_to_run = $times_to_run;

	for($i = 0; $i < $times_to_run; $i++)
	{
		$display_mode->test_run_instance_header($pts_test_result, ($i + 1), $times_to_run);
		$benchmark_log_file = $test_directory . $test_identifier . "-" . $runtime_identifier . "-" . ($i + 1) . ".log";

		$test_extra_runtime_variables = array_merge($extra_runtime_variables, array(
		"LOG_FILE" => $benchmark_log_file
		));

		$restored_from_cache = false;
		if($allow_cache_share && is_file($cache_share_pt2so))
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
			$test_run_time_start = time();
			$test_results = pts_exec("cd " . $to_execute . " && " . $execute_binary_prepend . "./" . $execute_binary . " " . $pts_test_arguments . " 2>&1", $test_extra_runtime_variables);
			$test_run_time = time() - $test_run_time_start;
		}
		

		if(!isset($test_results[10240]))
		{
			$display_mode->test_run_output($test_results);
		}

		if(is_file($benchmark_log_file) && trim($test_results) == "" && filesize($benchmark_log_file) < 10240)
		{
			$benchmark_log_file_contents = file_get_contents($benchmark_log_file);
			$display_mode->test_run_output($benchmark_log_file_contents);
			unset($benchmark_log_file_contents);
		}

		$exit_status_pass = true;
		if(is_file(TEST_ENV_DIR . $test_identifier . "/test-exit-status"))
		{
			// If the test script writes its exit status to ~/test-exit-status, if it's non-zero the test run failed
			$exit_status = trim(file_get_contents(TEST_ENV_DIR . $test_identifier . "/test-exit-status"));
			unlink(TEST_ENV_DIR . $test_identifier . "/test-exit-status");

			if($exit_status != "0")
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
				$run_time = trim(file_get_contents(TEST_ENV_DIR . $test_identifier . "/pts-timer"));
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

		if($i > 1 && $pts_test_result->trial_run_count() > 0 && pts_read_assignment("PTS_STATS_DYNAMIC_RUN_COUNT") && $times_to_run < ($defined_times_to_run * 2))
		{
			$current_standard_deviation = pts_percent_standard_deviation($pts_test_result->get_trial_results());

			if($current_standard_deviation >= 3.5 && floor($test_run_time / 60) < pts_read_assignment("PTS_STATS_NO_DYNAMIC_ON_LENGTH"))
			{
				$times_to_run++;
				$pts_test_result->set_attribute("TIMES_TO_RUN", $times_to_run);
			}
		}

		if($times_to_run > 1 && $i < ($times_to_run - 1))
		{
			if(!($allow_cache_share && is_file($cache_share_pt2so)))
			{
				echo pts_call_test_script($test_identifier, "interim", null, $test_directory, $extra_runtime_variables);
				sleep(2); // Rest for a moment between tests
			}

			pts_module_process("__interim_test_run", $pts_test_result);
		}

		if(is_file($benchmark_log_file))
		{
			if(pts_is_assignment("TEST_RESULTS_IDENTIFIER") && (pts_string_bool(pts_read_user_config(P_OPTION_LOG_BENCHMARKFILES, "FALSE")) || pts_read_assignment("IS_PCQS_MODE") || pts_read_assignment("IS_BATCH_MODE")))
			{
				$backup_log_dir = SAVE_RESULTS_DIR . pts_read_assignment("SAVE_FILE_NAME") . "/benchmark-logs/" . pts_read_assignment("TEST_RESULTS_IDENTIFIER") . "/";
				$backup_filename = basename($benchmark_log_file);

				if(!is_dir($backup_log_dir))
				{
					mkdir($backup_log_dir, 0777, true);
				}

				copy($benchmark_log_file, $backup_log_dir . $backup_filename);
			}
			unlink($benchmark_log_file);
		}

		if(is_file(PTS_USER_DIR . "halt-testing"))
		{
			pts_release_lock($test_fp, $lock_file);
			return $pts_test_result;
		}
	}

	if($allow_cache_share && !is_file($cache_share_pt2so) && $cache_share instanceOf pts_storage_object)
	{
		$cache_share->save_to_file($cache_share_pt2so);
		unset($cache_share);
	}

	if(!($allow_cache_share && is_file($cache_share_pt2so)))
	{
		echo pts_call_test_script($test_identifier, "post", null, $test_directory, $extra_runtime_variables);
	}

	// End
	$time_test_end = time();
	$time_test_elapsed = $time_test_end - $time_test_start;

	if(is_file($test_directory . "/pts-test-note"))
	{
		pts_test_notes_manager::add_note(trim(file_get_contents($test_directory . "/pts-test-note")));
		unlink($test_directory . "pts-test-note");
	}
	if(empty($result_scale) && is_file($test_directory . "pts-results-scale"))
	{
		$result_scale = trim(file_get_contents($test_directory . "pts-results-scale"));
		unlink($test_directory . "pts-results-scale");
	}
	if(empty($result_quantifier) && is_file($test_directory . "pts-results-quantifier"))
	{
		$result_quantifier = trim(file_get_contents($test_directory . "pts-results-quantifier"));
		unlink($test_directory . "pts-results-quantifier");
	}
	if(empty($test_version) && is_file($test_directory . "pts-test-version"))
	{
		$test_version = file_get_contents($test_directory . "pts-test-version");
		unlink($test_directory . "pts-test-version");
	}
	if(empty($arguments_description))
	{
		$default_test_descriptor = $xml_parser->getXMLValue(P_TEST_SUBTITLE);

		if(!empty($default_test_descriptor))
		{
			$arguments_description = $default_test_descriptor;
		}
		else if(is_file($test_directory . "pts-test-description"))
		{
			$arguments_description = file_get_contents($test_directory . "pts-test-description");
			unlink($test_directory . "pts-test-description");
		}
		else
		{
			$arguments_description = "Phoronix Test Suite v" . PTS_VERSION;
		}
	}

	foreach(pts_env_variables() as $key => $value)
	{
		$arguments_description = str_replace("$" . $key, $value, $arguments_description);

		if($key != "VIDEO_MEMORY" && $key != "NUM_CPU_CORES" && $key != "NUM_CPU_JOBS")
		{
			$extra_arguments = str_replace("$" . $key, $value, $extra_arguments);
		}
	}

	if($test_type == "Graphics")
	{
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
			if($arguments_description != null)
			{
				$arguments_description .= " | ";
			}

			$extra_gfx_settings = implode(" - ", $extra_gfx_settings);

			if(strpos($arguments_description, $extra_gfx_settings) === false)
			{
				$arguments_description .= $extra_gfx_settings;
			}
		}
	}

	// Result Calculation
	$pts_test_result->set_attribute("TEST_DESCRIPTION", $arguments_description);
	$pts_test_result->set_attribute("TEST_VERSION", $test_version);
	$pts_test_result->set_attribute("EXTRA_ARGUMENTS", $extra_arguments);
	$pts_test_result->set_attribute("ELAPSED_TIME", $time_test_elapsed);
	$pts_test_result->set_result_format($result_format);
	$pts_test_result->set_result_proportion($result_proportion);
	$pts_test_result->set_result_scale($result_scale);
	$pts_test_result->set_result_quantifier($result_quantifier);
	$pts_test_result->calculate_end_result(); // Process results

	$display_mode->test_run_end($pts_test_result);

	pts_user_message($post_run_message);
	pts_module_process("__post_test_run", $pts_test_result);
	$report_elapsed_time = !($allow_cache_share && is_file($cache_share_pt2so)) && $pts_test_result->get_result() != 0;
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

?>
