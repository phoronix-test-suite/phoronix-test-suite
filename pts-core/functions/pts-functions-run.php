<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts-functions-run.php: Functions needed for running tests/suites.

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

function pts_prompt_results_identifier($current_identifiers = null)
{
	// Prompt for a results identifier
	$RESULTS_IDENTIFIER = null;
	$show_identifiers = array();

	if(!IS_BATCH_MODE || pts_read_user_config(P_OPTION_BATCH_PROMPTIDENTIFIER, "TRUE") == "TRUE")
	{
		if(is_array($current_identifiers) && count($current_identifiers) > 0)
		{
			foreach($current_identifiers as $identifier)
			{
				if(is_array($identifier))
				{
					foreach($identifier as $identifier_2)
						array_push($show_identifiers, $identifier_2);
				}
				else
					array_push($show_identifiers, $identifier);
			}

			$show_identifiers = array_unique($show_identifiers);
			sort($show_identifiers);

			echo "\nCurrent Test Identifiers:\n";
			foreach($show_identifiers as $identifier)
				echo "- " . $identifier . "\n";
			echo "\n";
		}

		$times_tried = 0;
		do
		{
			if($times_tried == 0 && ($env_identifier = getenv("TEST_RESULTS_IDENTIFIER")) != FALSE)
			{
				$RESULTS_IDENTIFIER = $env_identifier;
				echo "Test Identifier: " . $RESULTS_IDENTIFIER . "\n";
			}
			else
			{
				echo "Enter a unique name for this test run: ";
				$RESULTS_IDENTIFIER = trim(str_replace(array("/"), "", fgets(STDIN)));
			}
			$times_tried++;
		}
		while(empty($RESULTS_IDENTIFIER) || in_array($RESULTS_IDENTIFIER, $show_identifiers));
	}

	if(empty($RESULTS_IDENTIFIER))
		$RESULTS_IDENTIFIER = date("Y-m-d H:i");
	else
		$RESULTS_IDENTIFIER = pts_swap_user_variables($RESULTS_IDENTIFIER);

	if(!defined("TEST_RESULTS_IDENTIFIER"))
		define("TEST_RESULTS_IDENTIFIER", $RESULTS_IDENTIFIER);

	return $RESULTS_IDENTIFIER;
}
function pts_swap_user_variables($user_str)
{
	if(strpos($user_str, "$") !== FALSE)
	{
		$supported_variables = array(
		"VIDEO_RESOLUTION" => current_screen_resolution(),
		"VIDEO_CARD" => graphics_processor_string(),
		"VIDEO_DRIVER" => opengl_version(),
		"OPERATING_SYSTEM" => pts_vendor_identifier(),
		"PROCESSOR" => processor_string(),
		"MOTHERBOARD" => main_system_hardware_string(),
		"CHIPSET" => motherboard_chipset_string(),
		"KERNEL_VERSION" => kernel_string(),
		"COMPILER" => compiler_version()
		);

		foreach($supported_variables as $key => $value)
			$user_str = str_replace("$" . $key, $value, $user_str);
	}

	return $user_str;
}
function pts_prompt_save_file_name($check_env = true)
{
	// Prompt to save a file when running a test
	if($check_env && ($save_name = getenv("TEST_RESULTS_NAME")) != FALSE)
	{
		$CUSTOM_TITLE = $save_name;
		$PROPOSED_FILE_NAME = pts_input_string_to_identifier($save_name);
		echo "Saving Results To: " . $PROPOSED_FILE_NAME . "\n";
	}
	else
	{
		if(!IS_BATCH_MODE || pts_read_user_config(P_OPTION_BATCH_PROMPTSAVENAME, "FALSE") == "TRUE")
		{
			$is_reserved_word = false;

			do
			{
				if($is_reserved_word)
				{
					echo "\n\nThe name of the saved file cannot be the same as a test/suite: " . $PROPOSED_FILE_NAME . "\n";
					$is_reserved_word = false;
				}

				echo "Enter a name to save these results: ";
				$PROPOSED_FILE_NAME = trim(fgets(STDIN));
				$CUSTOM_TITLE = $PROPOSED_FILE_NAME;
				$PROPOSED_FILE_NAME = pts_input_string_to_identifier($PROPOSED_FILE_NAME);

				if(is_test($PROPOSED_FILE_NAME) || is_suite($PROPOSED_FILE_NAME))
					$is_reserved_word = true;
			}
			while(empty($PROPOSED_FILE_NAME) || $is_reserved_word);
		}
		else
			$PROPOSED_FILE_NAME = "";
	}

	if(!isset($PROPOSED_FILE_NAME) || empty($PROPOSED_FILE_NAME))
	{
		$PROPOSED_FILE_NAME = date("Y-m-d-Hi");
	}
	if(!isset($PROPOSED_FILE_NAME) || empty($CUSTOM_TITLE))
	{
		$CUSTOM_TITLE = $PROPOSED_FILE_NAME;
	}

	return array($PROPOSED_FILE_NAME, $CUSTOM_TITLE);
}
function pts_promt_user_tags($default_tags = "")
{
	$tags_input = "";

	if(!IS_BATCH_MODE)
	{
		echo "\nTags are optional and used on Phoronix Global for making it easy to share, search, and organize test results. Example tags could be the type of test performed (i.e. WINE tests) or the hardware used (i.e. Dual Core SMP).\n\nEnter the tags you wish to provide (separated by commas): ";
		$tags_input .= trim(preg_replace("/[^a-zA-Z0-9s, -]/", "", fgets(STDIN)));
	}

	if(empty($tags_input))
	{
		if(!is_array($default_tags) && !empty($default_tags))
			$default_tags = array($default_tags);

		$tags_input = pts_global_auto_tags($default_tags);
	}

	return $tags_input;
}
function pts_generate_test_notes()
{
	$check_processes = array(
		"Compiz" => array("compiz", "compiz.real"),
		"Firefox" => array("firefox", "mozilla-firefox", "mozilla-firefox-bin", "firefox-bin"),
		"Thunderbird" => array("thunderbird", "mozilla-thunderbird", "mozilla-thunderbird-bin", "thunderbird-bin"),
		"BOINC" => array("boinc", "boinc_client")
		);

	$test_notes = pts_process_running_string($check_processes);

	// Power Saving Technologies?
	$cpu_savings = pts_processor_power_savings_enabled();
	if(!empty($cpu_savings))
	{
		$test_notes .= " \n" . $cpu_savings;
	}

	$cpu_mode = pts_report_power_mode();
	if(!empty($cpu_mode))
	{
		$test_notes .= " \n" . $cpu_mode;
	}

	$virtualized = pts_report_virtualized_mode();
	if(!empty($virtualized))
		$test_notes .= " \n" . $virtualized;

	if($test_type == "Graphics" || $test_type == "System")
	{
		$aa_level = graphics_antialiasing_level();
		$af_level = graphics_anisotropic_level();

		if(!empty($aa_level))
			$test_notes .= " \nAntialiasing: $aa_level.";
		if(!empty($af_level))
			$test_notes .= " \nAnisotropic Filtering: $af_level.";
	}

	return $test_notes;
}
function pts_input_string_to_identifier($input)
{
	$input = pts_swap_user_variables($input);
	$input = trim(str_replace(array(' ', '/', '&', '\''), "", strtolower($input)));

	return $input;
}
function pts_verify_test_installation($TO_RUN)
{
	// Verify a test is installed
	$tests = pts_contained_tests($TO_RUN);
	$needs_installing = array();

	foreach($tests as $test)
	{
		if(!is_file(TEST_ENV_DIR . $test . "/pts-install.xml"))
		{
			if(!pts_test_architecture_supported($test) || !pts_test_platform_supported($test))
				array_push($needs_installing, $test);
		}
		else
		{
			if(!defined("TEST_INSTALL_PASS"))
				define("TEST_INSTALL_PASS", true);
		}
	}

	if(count($needs_installing) > 0)
	{
		$needs_installing = array_unique($needs_installing);
	
		if(count($needs_installing) == 1)
		{
			echo pts_string_header($needs_installing[0] . " isn't installed on this system.\nTo install this test, run: phoronix-test-suite install " . $needs_installing[0]);
		}
		else
		{
			$message = "Multiple tests need to be installed before proceeding:\n\n";
			foreach($needs_installing as $single_package)
				$message .= "- " . $single_package . "\n";

			$message .= "\nTo install these tests, run: phoronix-test-suite install " . $TO_RUN;

			echo pts_string_header($message);
		}

		if(!defined("TEST_INSTALL_PASS") || getenv("SILENT_INSTALL") == FALSE)
			pts_exit();
	}
}
function pts_recurse_call_tests($tests_to_run, $arguments_array, $save_results = false, &$tandem_xml = "", $results_identifier = "", $arguments_description = "")
{
	// Call the tests
	if(!defined("PTS_RECURSE_CALL"))
	{
		pts_module_process("__pre_run_process");
		define("PTS_RECURSE_CALL", 1);
	}

	for($i = 0; $i < count($tests_to_run); $i++)
	{
		if(is_suite($tests_to_run[$i]))
		{
			$xml_parser = new tandem_XmlReader(pts_location_suite($tests_to_run[$i]));

			$tests_in_suite = $xml_parser->getXMLArrayValues(P_SUITE_TEST_NAME);
			$sub_arguments = $xml_parser->getXMLArrayValues(P_SUITE_TEST_ARGUMENTS);
			$sub_arguments_description = $xml_parser->getXMLArrayValues(P_SUITE_TEST_DESCRIPTION);

			pts_recurse_call_tests($tests_in_suite, $sub_arguments, $save_results, $tandem_xml, $results_identifier, $sub_arguments_description);
		}
		else if(is_test($tests_to_run[$i]))
		{
			$test_result = pts_run_test($tests_to_run[$i], $arguments_array[$i], $arguments_description[$i]);
			$GLOBALS["TEST_IDENTIFIER"] = null;
			// test_result[0] == the main result

			if($save_results && count($test_result) > 0 && ((is_numeric($test_result[0]) && $test_result[0] > 0) || (!is_numeric($test_result[0]) && strlen($test_result[0]) > 2)))
				pts_record_test_result($tandem_xml, $tests_to_run[$i], $arguments_array[$i], $results_identifier, $test_result, $arguments_description[$i], pts_request_new_id());

			if($i != (count($tests_to_run) - 1))
				sleep(pts_read_user_config(P_OPTION_TEST_SLEEPTIME, 5));
		}
	}
}
function pts_record_test_result(&$tandem_xml, $test, $arguments, $identifier, $result, $description, $tandem_id = 128)
{
	// Do the actual recording of the test result and other relevant information for the given test
	$test_result = $result[0];

	if((is_numeric($test_result) && $test_result > 0) || (!is_numeric($test_result) && strlen($test_result) > 2))
	{
		$xml_parser = new pts_test_tandem_XmlReader(pts_location_test($test));
		$test_title = $xml_parser->getXMLValue(P_TEST_TITLE);
		$test_version = $xml_parser->getXMLValue(P_TEST_VERSION);
		$result_scale = $xml_parser->getXMLValue(P_TEST_SCALE);
		$result_format = $xml_parser->getXMLValue(P_TEST_RESULTFORMAT);
		$proportion = $xml_parser->getXMLValue(P_TEST_PROPORTION);

		if(empty($description))
		{
			$default_test_descriptor = $xml_parser->getXMLValue(P_TEST_SUBTITLE);

			if(!empty($default_test_descriptor))
				$description = $default_test_descriptor;
			else if(is_file(TEST_ENV_DIR . $test . "/pts-test-description"))
				$description = @file_get_contents(TEST_ENV_DIR . $test . "/pts-test-description");
			else
				$description = "Phoronix Test Suite v" . PTS_VERSION;
		}
		if(empty($test_version) && is_file(TEST_ENV_DIR . $test . "/pts-test-version"))
			$test_version = @file_get_contents(TEST_ENV_DIR . $test . "/pts-test-version");
		if(empty($result_scale) && is_file(TEST_ENV_DIR . $test . "/pts-results-scale"))
			$result_scale = trim(@file_get_contents(TEST_ENV_DIR . $test . "/pts-results-scale"));
		if(empty($result_format))
			$result_format = "BAR_GRAPH";

		unset($xml_parser);
		$pts_vars = pts_env_variables();

		foreach($pts_vars as $key => $value)
			$description = str_replace("$" . $key, $value, $description);

		foreach($pts_vars as $key => $value)
			if($key != "VIDEO_MEMORY" && $key != "NUM_CPU_CORES" && $key != "NUM_CPU_JOBS")
				$arguments = str_replace("$" . $key, $value, $arguments);

		$tandem_xml->addXmlObject(P_RESULTS_TEST_TITLE, $tandem_id, $test_title);
		$tandem_xml->addXmlObject(P_RESULTS_TEST_VERSION, $tandem_id, $test_version);
		$tandem_xml->addXmlObject(P_RESULTS_TEST_ATTRIBUTES, $tandem_id, $description);
		$tandem_xml->addXmlObject(P_RESULTS_TEST_SCALE, $tandem_id, $result_scale);
		$tandem_xml->addXmlObject(P_RESULTS_TEST_PROPORTION, $tandem_id, $proportion);
		$tandem_xml->addXmlObject(P_RESULTS_TEST_RESULTFORMAT, $tandem_id, $result_format);
		$tandem_xml->addXmlObject(P_RESULTS_TEST_TESTNAME, $tandem_id, $test);
		$tandem_xml->addXmlObject(P_RESULTS_TEST_ARGUMENTS, $tandem_id, $arguments);
		$tandem_xml->addXmlObject(P_RESULTS_RESULTS_GROUP_IDENTIFIER, $tandem_id, $identifier, 5);
		$tandem_xml->addXmlObject(P_RESULTS_RESULTS_GROUP_VALUE, $tandem_id, $test_result, 5);

		$GLOBALS["TEST_RAN"] = true;
	}
}
function pts_save_test_file($PROPOSED_FILE_NAME, &$RESULTS = null, $RAW_TEXT = null)
{
	// Save the test file
	$j = 1;
	while(is_file(SAVE_RESULTS_DIR . $PROPOSED_FILE_NAME . "/test-" . $j . ".xml"))
		$j++;

	$REAL_FILE_NAME = $PROPOSED_FILE_NAME . "/test-" . $j . ".xml";

	if($RESULTS != null)
		$R_FILE = $RESULTS->getXML();
	else if($RAW_TEXT != null)
		$R_FILE = $RAW_TEXT;
	else
		return false;

	pts_save_result($REAL_FILE_NAME, $R_FILE);

	if(!is_file(SAVE_RESULTS_DIR . $PROPOSED_FILE_NAME . "/composite.xml"))
	{
		pts_save_result($PROPOSED_FILE_NAME . "/composite.xml", file_get_contents(SAVE_RESULTS_DIR . $REAL_FILE_NAME));
	}
	else
	{
		// Merge Results
		$MERGED_RESULTS = pts_merge_test_results(file_get_contents(SAVE_RESULTS_DIR . $PROPOSED_FILE_NAME . "/composite.xml"), file_get_contents(SAVE_RESULTS_DIR . $REAL_FILE_NAME));
		pts_save_result($PROPOSED_FILE_NAME . "/composite.xml", $MERGED_RESULTS);
	}
	return $REAL_FILE_NAME;
}
function pts_run_test($test_identifier, $extra_arguments = "", $arguments_description = "")
{
	// Do the actual test running process
	if(pts_process_active($test_identifier))
	{
		echo "\nThis test (" . $test_identifier . ") is already running... Please wait until the first instance is finished.\n";
		return 0;
	}
	pts_process_register($test_identifier);
	$test_directory = TEST_ENV_DIR . $test_identifier . "/";
	$GLOBALS["TEST_IDENTIFIER"] = $test_identifier;
	pts_module_process("__pre_test_run");

	$xml_parser = new pts_test_tandem_XmlReader(pts_location_test($test_identifier));
	$execute_binary = $xml_parser->getXMLValue(P_TEST_EXECUTABLE);
	$test_title = $xml_parser->getXMLValue(P_TEST_TITLE);
	$times_to_run = intval($xml_parser->getXMLValue(P_TEST_RUNCOUNT));
	$ignore_first_run = $xml_parser->getXMLValue(P_TEST_IGNOREFIRSTRUN);
	$pre_run_message = $xml_parser->getXMLValue(P_TEST_PRERUNMSG);
	$post_run_message = $xml_parser->getXMLValue(P_TEST_POSTRUNMSG);
	$result_scale = $xml_parser->getXMLValue(P_TEST_SCALE);
	$result_format = $xml_parser->getXMLValue(P_TEST_RESULTFORMAT);
	$result_quantifier = $xml_parser->getXMLValue(P_TEST_QUANTIFIER);
	$arg_identifier = $xml_parser->getXMLArrayValues(P_TEST_OPTIONS_IDENTIFIER);
	$execute_path = $xml_parser->getXMLValue(P_TEST_POSSIBLEPATHS);
	$default_arguments = $xml_parser->getXMLValue(P_TEST_DEFAULTARGUMENTS);
	$test_type = $xml_parser->getXMLValue(P_TEST_HARDWARE_TYPE);

	if(($test_type == "Graphics" && getenv("DISPLAY") == FALSE) || getenv("NO_" . strtoupper($test_type) . "_TESTS") != FALSE)
		return array(0);

	if(empty($times_to_run) || !is_int($times_to_run))
		$times_to_run = 1;

	if(strlen($result_format) > 6 && substr($result_format, 0, 6) == "MULTI_") // Currently tests that output multiple results in one run can only be run once
		$times_to_run = 1;

	if(empty($execute_binary))
		$execute_binary = $test_identifier;

	if(!empty($test_type))
	{
		$test_name = "TEST_" . strtoupper($test_type);

		if(!defined($test_name))
			define($test_name, 1);
	}

	if(empty($result_quantifier))
	{
		if(is_file($test_directory . "pts-result-quantifier"))
			$result_quantifier = @trim(file_get_contents($test_directory . "pts-result-quantifier"));
	}

	if(is_file($test_directory . $execute_binary) || is_link($test_directory . $execute_binary))
	{
		$to_execute = $test_directory;
	}
	else
	{
		foreach(explode(',', $execute_path) as $execute_path_check)
		{
			$execute_path_check = trim($execute_path_check);

			 if(is_file($execute_path_check . $execute_binary) || is_link($execute_path_check . $execute_binary))
				$to_execute = $execute_path_check;
		}
	}

	if(!isset($to_execute) || empty($to_execute))
	{
		echo "The test executable for " . $test_identifier . " could not be found. Skipping test.\n\n";
		return;
	}

	if(pts_test_needs_updated_install($test_identifier))
	{
		echo pts_string_header("NOTE: This test installation is out of date.\nFor best results, the " . $test_title . " test should be re-installed.");
		// Auto reinstall
		//require_once("pts-core/functions/pts-functions-run.php");
		//pts_install_test($test_identifier);
	}

	$pts_test_arguments = trim($default_arguments . " " . str_replace($default_arguments, "", $extra_arguments));
	$TEST_RESULTS_ARRAY = array();

	if(is_file(pts_location_test_resources($test_identifier) . "pre.sh"))
	{
		echo "\nRunning Pre-Test Scripts...\n";
		pts_exec("sh " . pts_location_test_resources($test_identifier) . "/pre.sh " . $test_directory, pts_run_additional_vars($test_identifier));
	}
	if(is_file(pts_location_test_resources($test_identifier) . "/pre.php"))
	{
		echo "\nRunning Pre-Test Scripts...\n";
		pts_exec(PHP_BIN . " " . pts_location_test_resources($test_identifier) . "/pre.php " . $test_directory, pts_run_additional_vars($test_identifier));
	}

	pts_user_message($pre_run_message);

	pts_debug_message("cd $to_execute && ./$execute_binary $pts_test_arguments");
	for($i = 0; $i < $times_to_run; $i++)
	{
		echo pts_string_header($test_title . " (Run " . ($i + 1) . " of " . $times_to_run . ")");
		$result_output = array();

		echo $test_results = pts_exec("cd " . $to_execute . " && ./" . $execute_binary . " " . $pts_test_arguments, pts_run_additional_vars($test_identifier));

		if(!($i == 0 && pts_string_bool($ignore_first_run) && $times_to_run > 1))
		{
			if(is_file(pts_location_test_resources($test_identifier) . "parse-results.php"))
			{
				$test_results = pts_exec("cd " .  $test_directory . " && " . PHP_BIN . " " . pts_location_test_resources($test_identifier) . "parse-results.php \"" . $test_results . "\"", pts_run_additional_vars($test_identifier));
			}

			if(!empty($test_results))
			{
				array_push($TEST_RESULTS_ARRAY, $test_results);
			}
		}
		if($times_to_run > 1 && $i < ($times_to_run - 1))
		{
			pts_module_process("__interim_test_run");
			sleep(1); // Rest for a moment between tests
		}
	}

	if(is_file(pts_location_test_resources($test_identifier) . "post.sh"))
	{
		pts_exec("sh " . pts_location_test_resources($test_identifier) . "post.sh " . $test_directory, pts_run_additional_vars($test_identifier));
	}
	if(is_file(pts_location_test_resources($test_identifier) . "post.php"))
	{
		pts_exec(PHP_BIN . " " . pts_location_test_resources($test_identifier) . "post.php " . $test_directory, pts_run_additional_vars($test_identifier));
	}

	// End
	if(empty($result_scale) && is_file($test_directory . "pts-results-scale"))
			$result_scale = trim(@file_get_contents($test_directory . "pts-results-scale"));

	foreach(pts_env_variables() as $key => $value)
		$arguments_description = str_replace("$" . $key, $value, $arguments_description);

	$RETURN_STRING = $test_title . ":\n";
	$RETURN_STRING .= $arguments_description . "\n";

	if(!empty($arguments_description))
		$RETURN_STRING .= "\n";

	if($result_format == "PASS_FAIL" || $result_format == "MULTI_PASS_FAIL")
	{
		$RETURN_STRING .= "(" . $result_scale . ")\n";
		$END_RESULT = -1;
		$i = 1;

		if(count($TEST_RESULTS_ARRAY) == 1)
			$END_RESULT = $TEST_RESULTS_ARRAY[0];
		else
		{
			foreach($TEST_RESULTS_ARRAY as $result)
			{
				if($result == "FALSE" || $result == "0" || $result == "FAIL")
				{
					$this_result = "FAIL";

					if($END_RESULT == -1 || $END_RESULT == "PASS")
					{
						$END_RESULT = "FAIL";
					}
				}
				else
				{
					$this_result = "PASS";

					if($END_RESULT == -1)
					{
						$END_RESULT = "PASS";
					}
				}

				$RETURN_STRING .= "Trial $i: " . $this_result . "\n";
				$i++;
			}
		}

		$RETURN_STRING .= "\nFinal: " . $END_RESULT . "\n";
	}
	else if($result_format == "NO_RESULT")
	{
		$RETURN_STRING = null;
		$END_RESULT = 0;
	}
	else
	{
		// Result is of a normal numerical type

		if($result_quantifier == "MAX")
		{
			$max_value = $TEST_RESULTS_ARRAY[0];
			foreach($TEST_RESULTS_ARRAY as $result)
			{
				if($result > $max_value)
					$max_value = $result;

				$RETURN_STRING .= $result . " " . $result_scale . "\n";
			}
			$RETURN_STRING .= "\nMaximum: " . $max_value . " " . $result_scale;
			$END_RESULT = $max_value;
		}
		else if($result_quantifier == "MIN")
		{
			$min_value = $TEST_RESULTS_ARRAY[0];
			foreach($TEST_RESULTS_ARRAY as $result)
			{
				if($result < $min_value)
					$min_value = $result;

				$RETURN_STRING .= $result . " " . $result_scale . "\n";
			}
			$RETURN_STRING .= "\nMinimum: " . $min_value . " " . $result_scale;
			$END_RESULT = $min_value;
		}
		else
		{
			// assume AVG
			$TOTAL_RESULT = 0;
			foreach($TEST_RESULTS_ARRAY as $result)
			{
				$TOTAL_RESULT += trim($result);
				$RETURN_STRING .= $result . " " . $result_scale . "\n";
			}

			if(count($TEST_RESULTS_ARRAY) > 0)
				$END_RESULT = pts_trim_double($TOTAL_RESULT / count($TEST_RESULTS_ARRAY), 2);
			else
				$END_RESULT = pts_trim_double($TOTAL_RESULT, 2);

			$RETURN_STRING .= "\nAverage: " . $END_RESULT . " " . $result_scale;
		}
	}

	if(!isset($GLOBALS["TEST_RESULTS_TEXT"]))
		$GLOBALS["TEST_RESULTS_TEXT"] = "";

	if(!empty($RETURN_STRING))
	{
		echo $this_result = pts_string_header($RETURN_STRING, "#");
		$GLOBALS["TEST_RESULTS_TEXT"] .= $this_result;
	}
	else
		echo "\n\n";

	pts_user_message($post_run_message);

	pts_process_remove($test_identifier);
	pts_module_process("__post_test_run");
	pts_test_refresh_install_xml($test_identifier);

	// 0 = main end result
	return array($END_RESULT);
}
function pts_global_auto_tags($extra_attr = NULL)
{
	// Generate automatic tags for the system, used for Phoronix Global
	$tags_array = array();

	if(!empty($extra_attr) && is_array($extra_attr))
		foreach($extra_attr as $attribute)
			array_push($tags_array, $attribute);

	switch(cpu_core_count())
	{
		case 1:
			array_push($tags_array, "Single Core");
			break;
		case 2:
			array_push($tags_array, "Dual Core");
			break;
		case 4:
			array_push($tags_array, "Quad Core");
			break;
		case 8:
			array_push($tags_array, "Octal Core");
			break;
	}

	$cpu_type = processor_string();
	if(strpos($cpu_type, "Intel") !== false)
		array_push($tags_array, "Intel");
	else if(strpos($cpu_type, "AMD") !== false)
		array_push($tags_array, "AMD");
	else if(strpos($cpu_type, "VIA") !== false)
		array_push($tags_array, "VIA");

	$gpu_type = graphics_processor_string();
	if(strpos($cpu_type, "ATI") !== false)
		array_push($tags_array, "ATI");
	else if(strpos($cpu_type, "NVIDIA") !== false)
		array_push($tags_array, "NVIDIA");

	if(kernel_arch() == "x86_64")
		array_push($tags_array, "64-bit Linux");

	$os = os_vendor();
	if($os != "Unknown")
		array_push($tags_array, $os);

	return implode(", ", $tags_array);
}
function pts_all_combos(&$return_arr, $current_string, $options, $counter, $delimiter = " ")
{
	// In batch mode, find all possible combinations for test options
	if(count($options) <= $counter)
	{
		array_push($return_arr, trim($current_string));
	}
	else
        {
		foreach($options[$counter] as $single_option)
		{
			$new_current_string = $current_string;

			if(strlen($new_current_string) > 0)
				$new_current_string .= $delimiter;

			$new_current_string .= $single_option;

			pts_all_combos($return_arr, $new_current_string, $options, $counter + 1, $delimiter);
		}
	}
}
function pts_auto_process_test_option($identifier, &$option_names, &$option_values)
{
	// Some test items have options that are dynamically built
	if(count($option_names) == 1 && count($option_values) == 1)
	{
		switch($identifier)
		{
			case "auto-resolution":
				$available_video_modes = xrandr_available_modes();
				$format_name = $option_names[0];
				$format_value = $option_values[0];
				$option_names = array();
				$option_values = array();

				foreach($available_video_modes as $video_mode)
				{
					$this_name = str_replace("\$VIDEO_WIDTH", $video_mode[0], $format_name);
					$this_name = str_replace("\$VIDEO_HEIGHT", $video_mode[1], $this_name);

					$this_value = str_replace("\$VIDEO_WIDTH", $video_mode[0], $format_value);
					$this_value = str_replace("\$VIDEO_HEIGHT", $video_mode[1], $this_value);

					array_push($option_names, $this_name);
					array_push($option_values, $this_value);
				}
			break;
		}
	}
}
function pts_test_options($identifier)
{
	$xml_parser = new pts_test_tandem_XmlReader(pts_location_test($identifier));
	$settings_name = $xml_parser->getXMLArrayValues(P_TEST_OPTIONS_DISPLAYNAME);
	$settings_argument = $xml_parser->getXMLArrayValues(P_TEST_OPTIONS_ARGUMENTNAME);
	$settings_identifier = $xml_parser->getXMLArrayValues(P_TEST_OPTIONS_IDENTIFIER);
	$settings_menu = $xml_parser->getXMLArrayValues(P_TEST_OPTIONS_MENU_GROUP);

	$test_options = array();

	for($option_count = 0; $option_count < count($settings_name); $option_count++)
	{
		if(!empty($settings_menu[$option_count]))
		{
			$xml_parser = new tandem_XmlReader($settings_menu[$option_count]);
			$option_names = $xml_parser->getXMLArrayValues(S_TEST_OPTIONS_MENU_GROUP_NAME);
			$option_values = $xml_parser->getXMLArrayValues(S_TEST_OPTIONS_MENU_GROUP_VALUE);
			pts_auto_process_test_option($settings_identifier[$option_count], $option_names, $option_values);

			$user_option = new pts_test_option($settings_identifier[$option_count], $settings_name[$option_count]);
			$user_option->set_option_prefix($settings_argument[$option_count]);

			for($i = 0; $i < count($option_names) && $i < count($option_values); $i++)
			{
				$user_option->add_option($option_names[$i], $option_values[$i]);
			}

			array_push($test_options, $user_option);
		}
	}

	return $test_options;
}

?>
