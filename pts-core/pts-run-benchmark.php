<?php

/*
   Copyright (C) 2008, Michael Larabel.
   Copyright (C) 2008, Phoronix Media.

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
$BENCHMARK_RAN = false;

if(isset($argv[2]) && $argv[2] == "BATCH")
	define("PTS_BATCH_MODE", "1");

if(empty($TO_RUN))
	pts_exit("\nThe benchmark, suite name, or saved file name must be supplied.\n");

// Make sure tests are installed
pts_verify_test_installation($TO_RUN);
pts_monitor_update(); // Update sensors, etc

if(!$TO_RUN_TYPE)
{
	if(is_file(pts_input_correct_results_path($TO_RUN)))
	{
		$SAVE_RESULTS = true;
		$TO_RUN_TYPE = "LOCAL_COMPARISON";
		$PROPOSED_FILE_NAME = $TO_RUN;
		$RES_NULL = null;
	}
	else if(trim(@file_get_contents("http://www.phoronix-test-suite.com/global/profile-check.php?id=$TO_RUN")) == "REMOTE_FILE")
	{
		$SAVE_RESULTS = true;
		$TO_RUN_TYPE = "GLOBAL_COMPARISON";
		$PROPOSED_FILE_NAME = $TO_RUN;
		$RES_NULL = null;
		define("GLOBAL_COMPARISON", 1);
		pts_save_result($PROPOSED_FILE_NAME . "/composite.xml", @file_get_contents("http://www.phoronix-test-suite.com/global/pts-results-viewer.php?id=$TO_RUN"));
	}
	else
	{
		pts_exit("\n$TO_RUN is not a recognized benchmark, suite, or PTS Global ID. Exiting...\n");
	}
}
else
{
	$SAVE_RESULTS = pts_bool_question("Would you like to save these benchmark results (Y/n)?", true, "SAVE_RESULTS");

	if($SAVE_RESULTS)
	{
		do
		{
			echo "Enter a name to save these results: ";
			$PROPOSED_FILE_NAME = trim(fgets(STDIN));
		}while(empty($PROPOSED_FILE_NAME));

		$CUSTOM_TITLE = $PROPOSED_FILE_NAME;
		$PROPOSED_FILE_NAME = trim(str_replace(array(' ', '/', '&', '\''), "", strtolower($PROPOSED_FILE_NAME))); // Clean up name

		if(empty($PROPOSED_FILE_NAME))
			$PROPOSED_FILE_NAME = date("Y-m-d-Hi");
	}
}

$RESULTS = new tandem_XmlWriter();

if($SAVE_RESULTS)
	$RESULTS_IDENTIFIER = pts_prompt_results_identifier();

pts_disable_screensaver(); // Kill the screensaver

if($TO_RUN_TYPE == "BENCHMARK")
{
	$xml_parser = new tandem_XmlReader(file_get_contents(XML_PROFILE_DIR . "$TO_RUN.xml"));
	$settings_name = $xml_parser->getXMLArrayValues(P_TEST_OPTIONS_DISPLAYNAME);
	$settings_argument = $xml_parser->getXMLArrayValues(P_TEST_OPTIONS_ARGUMENTNAME);
	$settings_identifier = $xml_parser->getXMLArrayValues(P_TEST_OPTIONS_IDENTIFIER);
	$settings_menu = $xml_parser->getXMLArrayValues(P_TEST_OPTIONS_MENU_GROUP);

	$USER_ARGS = "";
	$TEXT_ARGS = "";
	for($option_count = 0; $option_count < sizeof($settings_name); $option_count++)
	{
		$this_identifier = $settings_identifier[$option_count];
		echo "\n$settings_name[$option_count]:\n";

		if(!empty($settings_menu[$option_count]))
		{
			$xml_parser = new tandem_XmlReader($settings_menu[$option_count]);
			$option_names = $xml_parser->getXMLArrayValues(S_TEST_OPTIONS_MENU_GROUP_NAME);
			$option_values = $xml_parser->getXMLArrayValues(S_TEST_OPTIONS_MENU_GROUP_VALUE);

			if(count($option_values) == 1)
			{
				$bench_choice = 1;
			}
			else
			{
				do
				{
					echo "\n";
					for($i = 0; $i < count($option_names); $i++)
					{
						echo ($i + 1) . ": " . $option_names[$i] . "\n";
					}
					echo "\nPlease Enter Your Choice: ";
					$bench_choice = strtolower(trim(fgets(STDIN)));
				}
				while($bench_choice < 1 || $bench_choice > count($option_names));
			}

			$TEXT_ARGS .= "$settings_name[$option_count]: " . $option_names[($bench_choice - 1)];
			$USER_ARGS .= $settings_argument[$option_count] . $option_values[($bench_choice - 1)] . " ";

			if($option_count < sizeof($settings_name) - 1)
				$TEXT_ARGS .= "; ";
		}
		else
		{
			echo "\nEnter Value: ";
			$value = strtolower(trim(fgets(STDIN)));
			$USER_ARGS .= $settings_argument[$option_count] . $value;
		}
	}
	unset($xml_parser);

	if($SAVE_RESULTS)
	{
		$xml_parser = new tandem_XmlReader(file_get_contents(XML_PROFILE_DIR . $TO_RUN . ".xml"));
		$test_description = $xml_parser->getXMLValue(P_TEST_DESCRIPTION);
		$test_version = $xml_parser->getXMLValue(P_TEST_PTSVERSION);
		$test_type = $xml_parser->getXMLValue(P_TEST_SOFTWARE_TYPE);
		$test_maintainer = $xml_parser->getXMLValue(P_TEST_MAINTAINER);
		unset($xml_parser);
	}
	pts_recurse_call_benchmark(array($TO_RUN), array($USER_ARGS), $SAVE_RESULTS, $RESULTS, $RESULTS_IDENTIFIER, array($TEXT_ARGS));
}
else if($TO_RUN_TYPE == "TEST_SUITE")
{
	echo pts_string_header(ucwords($TO_RUN) . " Test Suite");

	echo "\nRunning " . ucwords($TO_RUN) . " Test Suite...\n\n";

	$xml_parser = new tandem_XmlReader(file_get_contents(XML_SUITE_DIR . $TO_RUN . ".xml"));

	if($SAVE_RESULTS)
	{
		$test_description = $xml_parser->getXMLValue(P_SUITE_DESCRIPTION);
		$test_version = $xml_parser->getXMLValue(P_SUITE_VERSION);
		$test_type = $xml_parser->getXMLValue(P_SUITE_TYPE);
		$test_maintainer = $xml_parser->getXMLValue(P_SUITE_MAINTAINER);
	}

	$suite_benchmarks = $xml_parser->getXMLArrayValues(P_SUITE_TEST_NAME);
	$arguments = $xml_parser->getXMLArrayValues(P_SUITE_TEST_ARGUMENTS);
	$arguments_description = $xml_parser->getXMLArrayValues(P_SUITE_TEST_DESCRIPTION);
	unset($xml_parser);

	pts_recurse_call_benchmark($suite_benchmarks, $arguments, $SAVE_RESULTS, $RESULTS, $RESULTS_IDENTIFIER, $arguments_description);
}
else if($SAVE_RESULTS && ($TO_RUN_TYPE == "GLOBAL_COMPARISON" || $TO_RUN_TYPE == "LOCAL_COMPARISON"))
{
	echo pts_string_header("Global Comparison Against: " . $TO_RUN);

	$xml_parser = new tandem_XmlReader(file_get_contents(SAVE_RESULTS_DIR . $TO_RUN . "/composite.xml"));
	$CUSTOM_TITLE = $xml_parser->getXMLValue(P_RESULTS_SUITE_TITLE);
	$test_description = $xml_parser->getXMLValue(P_RESULTS_SUITE_DESCRIPTION);
	$test_version = $xml_parser->getXMLValue(P_RESULTS_SUITE_VERSION);
	$test_type = $xml_parser->getXMLValue(P_RESULTS_SUITE_TYPE);
	$test_maintainer = $xml_parser->getXMLValue(P_RESULTS_SUITE_MAINTAINER);
	$suite_benchmarks = $xml_parser->getXMLArrayValues(P_RESULTS_TEST_TESTNAME);
	$arguments = $xml_parser->getXMLArrayValues(P_RESULTS_TEST_ARGUMENTS);
	$arguments_description = $xml_parser->getXMLArrayValues(P_RESULTS_TEST_ATTRIBUTES);
	unset($xml_parser);

	pts_recurse_call_benchmark($suite_benchmarks, $arguments, $SAVE_RESULTS, $RESULTS, $RESULTS_IDENTIFIER, $arguments_description);
}
else
{
	pts_exit("\nUnrecognized option: $TO_RUN_TYPE\n");
}

pts_beep(2);
if($SAVE_RESULTS)
{
	$check_processes = array(
		"Compiz" => array("compiz"),
		"Firefox" => array("firefox", "mozilla-firefox", "mozilla-firefox-bin"),
		"Thunderbird" => array("thunderbird", "mozilla-thunderbird", "mozilla-thunderbird-bin")
		);

	$test_notes = pts_process_running_string($check_processes);

	if(defined("TEST_GRAPHICS"))
	{
		$aa_level = graphics_antialiasing_level();
		$af_level = graphics_anisotropic_level();

		if(!empty($aa_level) && !empty($af_level))
			$test_notes .= " \nAntialiasing: $aa_level Anisotropic Filtering: $af_level.";
	}

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
	$RESULTS->addXmlObject(P_RESULTS_SUITE_MAINTAINER, $id, $test_maintainer);

	if($BENCHMARK_RAN)
	{
		pts_save_benchmark_file($PROPOSED_FILE_NAME, $RESULTS);
		echo "Results Saved To: " . SAVE_RESULTS_DIR . $PROPOSED_FILE_NAME . "/composite.xml\n";
		display_web_browser(SAVE_RESULTS_DIR . $PROPOSED_FILE_NAME . "/composite.xml");

		$upload_results = pts_bool_question("Would you like to upload these results to PTS Global (Y/n)?", true, "UPLOAD_RESULTS");

		if($upload_results)
		{
			echo "\nTags are optional and used on PTS Global for making it easy to share, search, and organize test results. Example tags could be the type of test performed (i.e. WINE tests) or the hardware used (i.e. Dual Core SMP).\n\nEnter the tags you wish to provide (separated by commas): ";
			$tags_input = trim(preg_replace("/[^a-zA-Z0-9s, -]/", "", fgets(STDIN)));

			if(empty($tags_input))
			{
				// Auto tagging
				$tags_array = array();
				array_push($tags_array, $RESULTS_IDENTIFIER);

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

				$tags_input = implode(", ", $tags_array);
			}

			$upload_url = pts_global_upload_result(SAVE_RESULTS_DIR . $PROPOSED_FILE_NAME . "/composite.xml", $tags_input);

			if(!empty($upload_url))
			{
				echo "\nResults Uploaded To: " . $upload_url . "\n";
				display_web_browser($upload_url, "Do you want to launch PTS Global", true);
			}
			else
				echo "\nResults Failed To Upload.\n";
		}

		echo "\n";
	}
	pts_monitor_update(); // Update sensors, etc
}

?>
