<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	tandem_XmlReader.php: The main code for running tests.

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

require("pts-core/functions/pts-functions.php");
require("pts-core/functions/pts-functions-run.php");
require("pts-core/functions/pts-functions-merge.php");

$TO_RUN = strtolower($argv[1]);
$TO_RUN_TYPE = pts_test_type($TO_RUN);
$MODULE_STORE = implode(";", $GLOBALS["PTS_MODULE_VAR_STORE"]);
$TEST_PROPERTIES = array();
$TEST_RAN = false;

if(IS_BATCH_MODE)
{
	array_push($TEST_PROPERTIES, "PTS_BATCH_MODE");
}

if(empty($TO_RUN))
	pts_exit("\nThe test, suite name, or saved file name must be supplied.\n");

// Make sure tests are installed
pts_verify_test_installation($TO_RUN);

if(!$TO_RUN_TYPE)
{
	if(is_file(SAVE_RESULTS_DIR . $TO_RUN . "/composite.xml"))
	{
		$SAVE_RESULTS = true;
		$TO_RUN_TYPE = "LOCAL_COMPARISON";
		$PROPOSED_FILE_NAME = $TO_RUN;
		$RES_NULL = null;
	}
	else if(is_file(pts_input_correct_results_path($TO_RUN)))
	{
		$SAVE_RESULTS = true;
		$TO_RUN_TYPE = "LOCAL_COMPARISON";
		$PROPOSED_FILE_NAME = $TO_RUN;
		$RES_NULL = null;
	}
	else if(pts_is_global_id($TO_RUN))
	{
		$SAVE_RESULTS = true;
		$TO_RUN_TYPE = "GLOBAL_COMPARISON";
		$PROPOSED_FILE_NAME = $TO_RUN;
		$RES_NULL = null;
		define("GLOBAL_COMPARISON", 1);
		pts_save_result($PROPOSED_FILE_NAME . "/composite.xml", pts_global_download_xml($TO_RUN));
	}
	else
	{
		pts_exit("\nNot Recognized: $TO_RUN \n\n");
	}
}
else
{
	echo "\n";
	if(getenv("PTS_SAVE_RESULTS") == "NO")
		$SAVE_RESULTS = FALSE;
	else if(getenv("TEST_RESULTS_NAME") != FALSE)
		$SAVE_RESULTS = TRUE;
	else
	{
		$save_option = true;

		if(is_test($TO_RUN))
		{
			$xml_parser = new pts_test_tandem_XmlReader(pts_location_test($TO_RUN));
			$result_format = $xml_parser->getXMLValue(P_TEST_RESULTFORMAT);

			if($result_format == "NO_RESULT")
				$save_option = false;
		}

		if($save_option)
			$SAVE_RESULTS = pts_bool_question("Would you like to save these test results (Y/n)?", true, "SAVE_RESULTS");
		else
			$SAVE_RESULTS = false;
	}

	if($SAVE_RESULTS)
	{
		$FILE_NAME = pts_prompt_save_file_name();
		$PROPOSED_FILE_NAME = $FILE_NAME[0];
		$CUSTOM_TITLE = $FILE_NAME[1];

		do
		{
			if(is_file(SAVE_RESULTS_DIR . $PROPOSED_FILE_NAME . "/composite.xml"))
			{
				$xml_parser = new tandem_XmlReader(SAVE_RESULTS_DIR . $PROPOSED_FILE_NAME . "/composite.xml");
				$test_suite = $xml_parser->getXMLValue(P_RESULTS_SUITE_NAME);

				if($TO_RUN_TYPE != "GLOBAL_COMPARISON")
				{
					if($test_suite != $TO_RUN)
						$is_validated = false;
					else
						$is_validated = true;
				}
				else
					$is_validated = true; //TODO: add type comparison check when doing a global comparison
			}
			else
			{
				$is_validated = true;
			}

			if(!$is_validated)
			{
				echo pts_string_header("This saved file-name is associated with a different test ($test_suite) from $TO_RUN. Enter a new name for saving the results.");
				$FILE_NAME = pts_prompt_save_file_name(FALSE);
				$PROPOSED_FILE_NAME = $FILE_NAME[0];
				$CUSTOM_TITLE = $FILE_NAME[1];
			}
		}
		while(!$is_validated);
	}
}

$RESULTS = new tandem_XmlWriter();
$RESULTS_IDENTIFIER = "";

if($SAVE_RESULTS)
{
	if(is_file(SAVE_RESULTS_DIR . $PROPOSED_FILE_NAME . "/composite.xml"))
	{
		$xml_parser = new tandem_XmlReader(SAVE_RESULTS_DIR . $PROPOSED_FILE_NAME . "/composite.xml");
		$raw_results = $xml_parser->getXMLArrayValues(P_RESULTS_RESULTS_GROUP);
		$result_identifiers = array();

		for($i = 0; $i < count($raw_results); $i++)
		{
			$results_xml = new tandem_XmlReader($raw_results[$i]);
			array_push($result_identifiers, $results_xml->getXMLArrayValues(S_RESULTS_RESULTS_GROUP_IDENTIFIER));
		}
	}
	else
		$result_identifiers = array();

	$RESULTS_IDENTIFIER = pts_prompt_results_identifier($result_identifiers);
}

if(is_test($TO_RUN))
{
	$xml_parser = new pts_test_tandem_XmlReader(pts_location_test($TO_RUN));
	$test_title = $xml_parser->getXMLValue(P_TEST_TITLE);
	$settings_name = $xml_parser->getXMLArrayValues(P_TEST_OPTIONS_DISPLAYNAME);
	$settings_argument = $xml_parser->getXMLArrayValues(P_TEST_OPTIONS_ARGUMENTNAME);
	$settings_identifier = $xml_parser->getXMLArrayValues(P_TEST_OPTIONS_IDENTIFIER);
	$settings_menu = $xml_parser->getXMLArrayValues(P_TEST_OPTIONS_MENU_GROUP);
	$test_title_shown = false;

	if(!IS_BATCH_MODE)
	{
		$USER_ARGS = "";
		$TEXT_ARGS = "";
		for($option_count = 0; $option_count < count($settings_name); $option_count++)
		{
			$this_identifier = $settings_identifier[$option_count];

			if(!empty($settings_menu[$option_count]))
			{
				$xml_parser = new tandem_XmlReader($settings_menu[$option_count]);
				$option_names = $xml_parser->getXMLArrayValues(S_TEST_OPTIONS_MENU_GROUP_NAME);
				$option_values = $xml_parser->getXMLArrayValues(S_TEST_OPTIONS_MENU_GROUP_VALUE);
				pts_auto_process_test_option($this_identifier, $option_names, $option_values);

				if(count($option_values) == 1)
				{
					$bench_choice = 1;
				}
				else
				{
					if(!$test_title_shown)
					{
						echo pts_string_header("Test Configuration: " . $test_title);
						$test_title_shown = true;
					}
					else
						echo "\n";

					echo $settings_name[$option_count] . ":\n";
					do
					{
						echo "\n";
						for($i = 0; $i < count($option_names); $i++)
						{
							echo ($i + 1) . ": " . $option_names[$i] . "\n";
						}
						echo "\nPlease Enter Your Choice: ";
						$bench_choice = trim(fgets(STDIN));
					}
					while(($bench_choice < 1 || $bench_choice > count($option_names)) && !in_array($bench_choice, $option_names));

					if(!is_numeric($bench_choice) && in_array($bench_choice, $option_names))
						for($i = 0; $i < count($option_names); $i++)
							if($option_names[$i] == $bench_choice)
								$bench_choice = ($i + 1);
				}
				$option_display_name = $option_names[($bench_choice - 1)];

				if(($cut_point = strpos($option_display_name, '(')) > 1 && strpos($option_display_name, ')') > $cut_point)
					$option_display_name = substr($option_display_name, 0, $cut_point);

				if(count($settings_name) > 1)
					$TEXT_ARGS .= $settings_name[$option_count] . ": ";

				$TEXT_ARGS .= $option_display_name;

				if($option_count < count($settings_name) - 1)
					$TEXT_ARGS .= " - ";

				$USER_ARGS .= $settings_argument[$option_count] . $option_values[($bench_choice - 1)] . " ";
			}
			else
			{
				if(!$test_title_shown)
				{
					echo pts_string_header("Test Configuration: " . $test_title);
					$test_title_shown = true;
				}
				else
					echo "\n";

				do
				{
					echo $settings_name[$option_count] . "\n" . "Enter Value: ";
					$value = strtolower(trim(fgets(STDIN)));
				}
				while(empty($value));

				$USER_ARGS .= $settings_argument[$option_count] . $value;
			}
		}
		$TEST_RUN = array($TO_RUN);
		$TEST_ARGS = array($USER_ARGS);
		$TEST_ARGS_DESCRIPTION = array($TEXT_ARGS);
	}
	else
	{
		// Batch mode for single test
		$batch_all_args_real = array();
		$batch_all_args_description = array();
		$description_separate = " ";

		for($option_count = 0; $option_count < count($settings_name); $option_count++)
		{
			$this_identifier = $settings_identifier[$option_count];
			if(!empty($settings_menu[$option_count]))
			{
				$xml_parser = new tandem_XmlReader($settings_menu[$option_count]);
				$option_names = $xml_parser->getXMLArrayValues(S_TEST_OPTIONS_MENU_GROUP_NAME);
				$option_values = $xml_parser->getXMLArrayValues(S_TEST_OPTIONS_MENU_GROUP_VALUE);
				pts_auto_process_test_option($this_identifier, $option_names, $option_values);

				$option_args = array();
				$option_args_description = array();

				for($i = 0; $i < count($option_names) && $i < count($option_values); $i++)
				{
					// A bit redundant processing, but will ensure against malformed XML problems and extra stuff added
					$this_arg = $settings_argument[$option_count] . $option_values[$i];
					$this_arg_description = $settings_name[$option_count] . ": " . $option_names[$i];

					if(($cut_point = strpos($this_arg_description, '(')) > 1 && strpos($this_arg_description, ')') > $cut_point)
						$this_arg_description = substr($this_arg_description, 0, $cut_point);

					array_push($option_args, $this_arg);
					array_push($option_args_description, $this_arg_description);
				}

				if($i > 1)
					$description_separate = " - ";

				array_push($batch_all_args_real, $option_args);
				array_push($batch_all_args_description, $option_args_description);
			}
		}

		$TEST_ARGS = array();
		pts_all_combos($TEST_ARGS, "", $batch_all_args_real, 0);

		$TEST_ARGS_DESCRIPTION = array();
		pts_all_combos($TEST_ARGS_DESCRIPTION, "", $batch_all_args_description, 0, $description_separate);

		$TEST_RUN = array();
		for($i = 0; $i < count($TEST_ARGS); $i++) // needed at this time to fill up the array same size as the number of options present
			array_push($TEST_RUN, $TO_RUN);
	}

	if($SAVE_RESULTS)
	{
		$xml_parser = new pts_test_tandem_XmlReader(pts_location_test($TO_RUN));
		$test_description = $xml_parser->getXMLValue(P_TEST_DESCRIPTION);
		$test_version = $xml_parser->getXMLValue(P_TEST_PTSVERSION);
		$test_type = $xml_parser->getXMLValue(P_TEST_HARDWARE_TYPE);
		unset($xml_parser);
	}
}
else if(is_suite($TO_RUN))
{
	echo pts_string_header("Test Suite: " . $TO_RUN);

	$xml_parser = new tandem_XmlReader(pts_location_suite($TO_RUN));

	if($SAVE_RESULTS)
	{
		$test_description = $xml_parser->getXMLValue(P_SUITE_DESCRIPTION);
		$test_version = $xml_parser->getXMLValue(P_SUITE_VERSION);
		$test_type = $xml_parser->getXMLValue(P_SUITE_TYPE);
	}

	$TEST_RUN = $xml_parser->getXMLArrayValues(P_SUITE_TEST_NAME);
	$TEST_ARGS = $xml_parser->getXMLArrayValues(P_SUITE_TEST_ARGUMENTS);
	$TEST_ARGS_DESCRIPTION = $xml_parser->getXMLArrayValues(P_SUITE_TEST_DESCRIPTION);
	unset($xml_parser);
}
else if($SAVE_RESULTS && ($TO_RUN_TYPE == "GLOBAL_COMPARISON" || $TO_RUN_TYPE == "LOCAL_COMPARISON"))
{
	echo pts_string_header("PTS Comparison: " . $TO_RUN);

	$xml_parser = new tandem_XmlReader(SAVE_RESULTS_DIR . $TO_RUN . "/composite.xml");
	$CUSTOM_TITLE = $xml_parser->getXMLValue(P_RESULTS_SUITE_TITLE);
	$test_description = $xml_parser->getXMLValue(P_RESULTS_SUITE_DESCRIPTION);
	$test_extensions = $xml_parser->getXMLValue(P_RESULTS_SUITE_EXTENSIONS);
	$test_previous_properties = $xml_parser->getXMLValue(P_RESULTS_SUITE_PROPERTIES);
	$test_version = $xml_parser->getXMLValue(P_RESULTS_SUITE_VERSION);
	$test_type = $xml_parser->getXMLValue(P_RESULTS_SUITE_TYPE);
	$TEST_RUN = $xml_parser->getXMLArrayValues(P_RESULTS_TEST_TESTNAME);
	$TEST_ARGS = $xml_parser->getXMLArrayValues(P_RESULTS_TEST_ARGUMENTS);
	$TEST_ARGS_DESCRIPTION = $xml_parser->getXMLArrayValues(P_RESULTS_TEST_ATTRIBUTES);
	unset($xml_parser);

	foreach(explode(";", $test_previous_properties) as $test_prop)
		if(!in_array($test_prop, $TEST_PROPERTIES))
			array_push($TEST_PROPERTIES, $test_prop);

	pts_module_process_extensions($test_extensions);
}
else
{
	pts_exit("\nUnrecognized option: " . $TO_RUN_TYPE . "\n");
}

if($SAVE_RESULTS && (!IS_BATCH_MODE || pts_read_user_config(P_OPTION_BATCH_PROMPTDESCRIPTION, "FALSE") == "TRUE"))
{
	if(empty($test_description))
		$test_description = "N/A";

	echo pts_string_header("If you wish, enter a new description below.\nPress ENTER to proceed without changes.", "#");
	echo "Current Description: " . $test_description;
	echo "\n\nNew Description: ";
	$new_test_description = trim(fgets(STDIN));

	if(!empty($new_test_description))
		$test_description = $new_test_description;
}

// Run the test process
pts_recurse_call_tests($TEST_RUN, $TEST_ARGS, $SAVE_RESULTS, $RESULTS, $RESULTS_IDENTIFIER, $TEST_ARGS_DESCRIPTION);

define("PTS_TESTING_DONE", 1);
pts_module_process("__post_run_process");

if($SAVE_RESULTS)
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

		if(!empty($aa_level) && !empty($af_level))
			$test_notes .= " \nAntialiasing: $aa_level Anisotropic Filtering: $af_level.";
	}

	$id = pts_request_new_id();
	$RESULTS->setXslBinding("pts-results-viewer.xsl");
	$RESULTS->addXmlObject(P_RESULTS_SYSTEM_HARDWARE, $id, pts_hw_string());
	$RESULTS->addXmlObject(P_RESULTS_SYSTEM_SOFTWARE, $id, pts_sw_string());
	$RESULTS->addXmlObject(P_RESULTS_SYSTEM_AUTHOR, $id, pts_current_user());
	$RESULTS->addXmlObject(P_RESULTS_SYSTEM_DATE, $id, date("F j, Y h:i A"));
	$RESULTS->addXmlObject(P_RESULTS_SYSTEM_NOTES, $id, trim($test_notes));
	$RESULTS->addXmlObject(P_RESULTS_SYSTEM_PTSVERSION, $id, PTS_VERSION);
	$RESULTS->addXmlObject(P_RESULTS_SYSTEM_IDENTIFIERS, $id, $RESULTS_IDENTIFIER);

	$id = pts_request_new_id();
	$RESULTS->addXmlObject(P_RESULTS_SUITE_TITLE, $id, $CUSTOM_TITLE);
	$RESULTS->addXmlObject(P_RESULTS_SUITE_NAME, $id, $TO_RUN);
	$RESULTS->addXmlObject(P_RESULTS_SUITE_VERSION, $id, $test_version);
	$RESULTS->addXmlObject(P_RESULTS_SUITE_DESCRIPTION, $id, $test_description);
	$RESULTS->addXmlObject(P_RESULTS_SUITE_TYPE, $id, $test_type);
	$RESULTS->addXmlObject(P_RESULTS_SUITE_EXTENSIONS, $id, $MODULE_STORE);
	$RESULTS->addXmlObject(P_RESULTS_SUITE_PROPERTIES, $id, implode(";", $TEST_PROPERTIES));

	if($TEST_RAN)
	{
		pts_save_test_file($PROPOSED_FILE_NAME, $RESULTS);
		echo "Results Saved To: " . SAVE_RESULTS_DIR . $PROPOSED_FILE_NAME . "/composite.xml\n";
		display_web_browser(SAVE_RESULTS_DIR . $PROPOSED_FILE_NAME . "/composite.xml");

		$upload_results = pts_bool_question("Would you like to upload these results to Phoronix Global (Y/n)?", true, "UPLOAD_RESULTS");

		if($upload_results)
		{
			$tags_input = "";

			if(!IS_BATCH_MODE)
			{
				echo "\nTags are optional and used on Phoronix Global for making it easy to share, search, and organize test results. Example tags could be the type of test performed (i.e. WINE tests) or the hardware used (i.e. Dual Core SMP).\n\nEnter the tags you wish to provide (separated by commas): ";
				$tags_input .= trim(preg_replace("/[^a-zA-Z0-9s, -]/", "", fgets(STDIN)));
			}

			if(empty($tags_input))
				$tags_input = pts_global_auto_tags(array($RESULTS_IDENTIFIER));

			$upload_url = pts_global_upload_result(SAVE_RESULTS_DIR . $PROPOSED_FILE_NAME . "/composite.xml", $tags_input);

			if(!empty($upload_url))
			{
				echo "\nResults Uploaded To: " . $upload_url . "\n";
				display_web_browser($upload_url, "Do you want to launch Phoronix Global", true);
			}
			else
				echo "\nResults Failed To Upload.\n";
		}
		echo "\n";
	}
}

?>
