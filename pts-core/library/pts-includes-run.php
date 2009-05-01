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

function pts_cleanup_tests_to_run($to_run_identifiers)
{
	// Clean up tests
	for($i = 0; $i < count($to_run_identifiers); $i++)
	{
		$lower_identifier = strtolower($to_run_identifiers[$i]);

		if(pts_is_test($lower_identifier))
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

	return $to_run_identifiers;
}
function pts_add_test_note($note)
{
	pts_test_note("ADD", $note);
}
function pts_test_note($process, $value = null)
{
	static $note_r;
	$return = null;

	if(empty($note_r))
	{
		$note_r = array();
	}

	switch($process)
	{
		case "ADD":
			if(!empty($value))
			{
				$value = trim($value);

				switch($value)
				{
					case "JAVA_VERSION":
						$value = phodevi::read_property("system", "java-version");

						if(empty($value))
						{
							return;
						}
						break;
					case "2D_ACCEL_METHOD":
						$value = phodevi::read_property("gpu", "2d-accel-method");

						if(!empty($value))
						{
							$value = "2D Acceleration: " . $value;
						}
						else
						{
							return;
						}
						break;
				}

				if(!in_array($value, $note_r))
				{
					array_push($note_r, $value);
				}
			}
			break;
		case "TO_STRING":
			$return = implode(". \n", $note_r);
			break;
	}

	return $return;
}
function pts_generate_test_notes($test_type)
{
	static $check_processes = null;

	if(empty($check_processes) && is_file(STATIC_DIR . "process-reporting-checks.txt"))
	{
		$word_file = trim(file_get_contents(STATIC_DIR . "process-reporting-checks.txt"));
		$processes_r = array_map("trim", explode("\n", $word_file));
		$check_processes = array();

		foreach($processes_r as $p)
		{
			$p = explode("=", $p);
			$p_title = trim($p[0]);
			$p_names = array_map("trim", explode(",", $p[1]));

			$check_processes[$p_title] = array();

			foreach($p_names as $p_name)
			{
				array_push($check_processes[$p_title], $p_name);
			}
		}
	}

	if(!IS_BSD)
	{
		pts_add_test_note(pts_process_running_string($check_processes));
	}

	// Check if Security Enhanced Linux was enforcing, permissive, or disabled
	if(is_file("/etc/sysconfig/selinux") && is_readable("/boot/grub/menu.lst"))
	{
		$selinux_file = file_get_contents("/etc/sysconfig/selinux");
		if(stripos($selinux_file, "selinux=disabled") === false)
		{
			pts_add_test_note("SELinux was enabled.");
		}
	}
	else if(is_file("/boot/grub/menu.lst") && is_readable("/boot/grub/menu.lst"))
	{
		$grub_file = file_get_contents("/boot/grub/menu.lst");
		if(stripos($grub_file, "selinux=1") !== false)
		{
			pts_add_test_note("SELinux was enabled.");
		}
	}

	// Power Saving Technologies?
	pts_add_test_note(phodevi::read_property("cpu", "power-savings-mode"));
	pts_add_test_note(phodevi::read_property("motherboard", "power-mode"));
	pts_add_test_note(phodevi::read_property("system", "virtualized-mode"));

	if($test_type == "Graphics" || $test_type == "System")
	{
		$aa_level = phodevi::read_property("gpu", "aa-level");
		$af_level = phodevi::read_property("gpu", "af-level");

		if(!empty($aa_level))
		{
			pts_add_test_note("Antialiasing: " . $aa_level);
		}
		if(!empty($af_level))
		{
			pts_add_test_note("Anisotropic Filtering: " . $af_level);
		}
	}

	return pts_test_note("TO_STRING");
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
	$fail_count = 0;
	$valid_op = true;

	foreach($tests as $test)
	{
		if(!pts_test_installed($test))
		{
			$fail_count++;
			if(pts_test_supported($test))
			{
				array_push($needs_installing, $test);
			}
		}
		else
		{
			$pass_count++;
		}
	}

	if($fail_count > 0)
	{
		$needs_installing = array_unique($needs_installing);
	
		if(count($needs_installing) > 0)
		{
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
		}

		if(pts_is_test($identifiers_o))
		{
			$valid_op = false;
		}
	}

	return $valid_op;
}
function pts_call_test_runs($tests_to_run, &$tandem_xml = "", $identifier = "")
{
	if(is_file(PTS_USER_DIR . "halt-testing"))
	{
		unlink(PTS_USER_DIR . "halt-testing");
	}

	for($i = 0; $i < count($tests_to_run); $i++)
	{
		$to_run = $tests_to_run[$i]->get_identifier();

		if(pts_is_test($to_run))
		{
			$result = pts_run_test($to_run, $tests_to_run[$i]->get_arguments(), $tests_to_run[$i]->get_arguments_description());

			if(is_file(PTS_USER_DIR . "halt-testing"))
			{
				unlink(PTS_USER_DIR . "halt-testing");
				return;
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

			if($i != (count($tests_to_run) - 1))
			{
				sleep(pts_read_user_config(P_OPTION_TEST_SLEEPTIME, 5));
			}
		}
	}
}
function pts_save_test_file($proposed_name, &$results = null, $raw_text = null)
{
	// Save the test file
	$j = 1;
	while(is_file(SAVE_RESULTS_DIR . $proposed_name . "/test-" . $j . ".xml"))
	{
		$j++;
	}

	$real_name = $proposed_name . "/test-" . $j . ".xml";

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

	if(!is_file(SAVE_RESULTS_DIR . $proposed_name . "/composite.xml"))
	{
		pts_save_result($proposed_name . "/composite.xml", file_get_contents(SAVE_RESULTS_DIR . $real_name));
	}
	else
	{
		// Merge Results
		$MERGED_RESULTS = pts_merge_test_results(file_get_contents(SAVE_RESULTS_DIR . $proposed_name . "/composite.xml"), file_get_contents(SAVE_RESULTS_DIR . $real_name));
		pts_save_result($proposed_name . "/composite.xml", $MERGED_RESULTS);
	}
	return $real_name;
}
function pts_run_test($test_identifier, $extra_arguments = "", $arguments_description = "")
{
	// Do the actual test running process
	$pts_test_result = new pts_test_result();
	$test_directory = TEST_ENV_DIR . $test_identifier . "/";

	$test_fp = fopen(($lock_file = $test_directory . "run_lock"), "w");
	if(!flock($test_fp, LOCK_EX | LOCK_NB))
	{
		echo "\nThe " . $test_identifier . " test is already running.\n\n";
		return $pts_test_result;
	}

	$xml_parser = new pts_test_tandem_XmlReader($test_identifier);
	$execute_binary = $xml_parser->getXMLValue(P_TEST_EXECUTABLE);
	$test_title = $xml_parser->getXMLValue(P_TEST_TITLE);
	$test_version = $xml_parser->getXMLValue(P_TEST_VERSION);
	$times_to_run = intval($xml_parser->getXMLValue(P_TEST_RUNCOUNT));
	$ignore_runs = array_map("trim", explode(",", $xml_parser->getXMLValue(P_TEST_IGNORERUNS)));
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
	$env_testing_size = $xml_parser->getXMLValue(P_TEST_ENVIRONMENT_TESTING_SIZE);

	if(($test_type == "Graphics" && getenv("DISPLAY") == false) || getenv("NO_" . strtoupper($test_type) . "_TESTS") != false)
	{
		return $pts_test_result;
	}

	if(!empty($env_testing_size) && ceil(disk_free_space(TEST_ENV_DIR) / 1048576) < $env_testing_size)
	{
		// Ensure enough space is available on disk during testing process
		echo "\nThere is not enough space (at " . TEST_ENV_DIR . ") for this test to run. " . $env_testing_size . " MB of space is needed.\n";
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
	
	if(empty($times_to_run) || !is_int($times_to_run))
	{
		$times_to_run = 3;
	}
	if(($force_runs = getenv("FORCE_TIMES_TO_RUN")) != false)
	{
		$times_to_run = $force_runs;
	}

	if(!empty($test_type))
	{
		$test_name = "TEST_" . strtoupper($test_type);
		pts_set_assignment_once($test_name, 1);
	}

	if(empty($execute_binary))
	{
		$execute_binary = $test_identifier;
	}

	$execute_path_check = explode(",", $execute_path);
	array_push($execute_path_check, $test_directory);

	while(count($execute_path_check) > 0)
	{
		$path_check = trim(array_pop($execute_path_check));

		if(is_file($path_check . $execute_binary) || is_link($path_check . $execute_binary))
		{
			$to_execute = $path_check;
		}	
	}

	if(!isset($to_execute) || empty($to_execute))
	{
		echo "The test executable for " . $test_identifier . " could not be found. Skipping test.\n\n";
		return $pts_test_result;
	}

	if(pts_test_needs_updated_install($test_identifier))
	{
		echo pts_string_header("NOTE: This test installation is out of date.\nFor best results, the " . $test_title . " test should be re-installed.");
		// Auto reinstall
		//require_once(PTS_LIBRARY_PATH . "pts-functions-install.php");
		//pts_install_test($test_identifier);
	}

	$pts_test_arguments = trim($default_arguments . " " . str_replace($default_arguments, "", $extra_arguments));
	$extra_runtime_variables = pts_run_additional_vars($test_identifier);
	$extra_runtime_variables["LC_ALL"] = "";
	$extra_runtime_variables["LC_CTYPE"] = "";
	$extra_runtime_variables["LC_MESSAGES"] = "";
	$extra_runtime_variables["LANG"] = "";

	// Start
	$pts_test_result->set_attribute("TEST_TITLE", $test_title);
	$pts_test_result->set_attribute("TEST_IDENTIFIER", $test_identifier);
	$pts_test_result->set_attribute("TIMES_TO_RUN", $times_to_run);
	pts_module_process("__pre_test_run", $pts_test_result);

	$time_test_start = time();
	echo pts_call_test_script($test_identifier, "pre", "\nRunning Pre-Test Scripts...\n", $test_directory, $extra_runtime_variables);

	pts_user_message($pre_run_message);

	$runtime_identifier = pts_unique_runtime_identifier();

	$execute_binary_prepend = "";

	if($root_required)
	{
		$execute_binary_prepend = TEST_LIBRARIES_DIR . "root-access.sh";
	}

	if(!empty($execute_binary_prepend))
	{
		$execute_binary_prepend .= " ";
	}

	for($i = 0; $i < $times_to_run; $i++)
	{
		$benchmark_log_file = $test_directory . $test_identifier . "-" . $runtime_identifier . "-" . ($i + 1) . ".log";

		$test_extra_runtime_variables = array_merge($extra_runtime_variables, array(
		"LOG_FILE" => $benchmark_log_file,
		"TEST_LIBRARIES_DIR" => TEST_LIBRARIES_DIR,
		"TIMER_START" => TEST_LIBRARIES_DIR . "timer-start.sh",
		"TIMER_STOP" => TEST_LIBRARIES_DIR . "timer-stop.sh",
		"TIMED_KILL" => TEST_LIBRARIES_DIR . "timed-kill.sh",
		"SYSTEM_MONITOR_START" => TEST_LIBRARIES_DIR . "system-monitoring-start.sh",
		"SYSTEM_MONITOR_STOP" => TEST_LIBRARIES_DIR . "system-monitoring-stop.sh",
		"PHP_BIN" => PHP_BIN
		));

		echo pts_string_header($test_title . " (Run " . ($i + 1) . " of " . $times_to_run . ")");
		$result_output = array();

		$test_results = pts_exec("cd " . $to_execute . " && " . $execute_binary_prepend . "./" . $execute_binary . " " . $pts_test_arguments, $test_extra_runtime_variables);

		if(!isset($test_results[10240]))
		{
			echo $test_results;
		}

		if(is_file($benchmark_log_file) && trim($test_results) == "" && filesize($benchmark_log_file) < 10240)
		{
			echo file_get_contents($benchmark_log_file);
		}

		if(!in_array(($i + 1), $ignore_runs))
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
			if(is_file($benchmark_log_file))
			{
				$test_results = "";
			}

			$test_results = pts_call_test_script($test_identifier, "parse-results", null, $test_results, $test_extra_runtime_variables_post);

			if(empty($test_results) && isset($run_time) && is_numeric($run_time))
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
				$pts_test_result->add_trial_run_result(trim($test_results));
			}
		}
		if($times_to_run > 1 && $i < ($times_to_run - 1))
		{
			echo pts_call_test_script($test_identifier, "interim", null, $test_directory, $extra_runtime_variables);
			pts_module_process("__interim_test_run", $pts_test_result);
			sleep(2); // Rest for a moment between tests
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
			return;
		}
	}

	echo pts_call_test_script($test_identifier, "post", null, $test_directory, $extra_runtime_variables);

	// End
	$time_test_end = time();

	if(is_file($test_directory . "/pts-test-note"))
	{
		pts_add_test_note(trim(file_get_contents($test_directory . "/pts-test-note")));
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
	}
	foreach(pts_env_variables() as $key => $value)
	{
		if($key != "VIDEO_MEMORY" && $key != "NUM_CPU_CORES" && $key != "NUM_CPU_JOBS")
		{
			$extra_arguments = str_replace("$" . $key, $value, $extra_arguments);
		}
	}

	$return_string = $test_title . ":\n";
	$return_string .= $arguments_description . "\n";

	if(!empty($arguments_description))
	{
		$return_string .= "\n";
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
	$pts_test_result->set_result_format($result_format);
	$pts_test_result->set_result_proportion($result_proportion);
	$pts_test_result->set_result_scale($result_scale);
	$pts_test_result->set_result_quantifier($result_quantifier);
	$pts_test_result->calculate_end_result($return_string); // Process results

	if(!empty($return_string))
	{
		echo $this_result = pts_string_header($return_string, "#");
		pts_text_save_buffer($this_result);
	}
	else
	{
		echo "\n\n";
	}

	pts_user_message($post_run_message);
	pts_module_process("__post_test_run", $pts_test_result);
	pts_test_refresh_install_xml($test_identifier, ($time_test_end - $time_test_start));

	// Remove lock
	fclose($test_fp);
	unlink($lock_file);

	if($result_format == "NO_RESULT")
	{
		$pts_test_result = false;
	}

	return $pts_test_result;
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
	$xml_writer->addXmlObject(P_INSTALL_TEST_AVG_RUNTIME, 2, $test_duration, 2);

	file_put_contents(TEST_ENV_DIR . $identifier . "/pts-install.xml", $xml_writer->getXML());
}

?>
