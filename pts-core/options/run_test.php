<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel

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

class run_test implements pts_option_interface
{
	public static function run($to_run_identifiers)
	{
		pts_load_function_set("run");
		pts_load_function_set("merge");

		// Check for batch mode
		if(getenv("PTS_BATCH_MODE") != false)
		{
			pts_set_assignment("IS_BATCH_MODE", true);
		}
		if(getenv("PTS_DEFAULTS_MODE") != false)
		{
			pts_set_assignment("IS_DEFAULTS_MODE", true);
		}

		if(pts_read_assignment("IS_BATCH_MODE") != false && pts_read_user_config(P_OPTION_BATCH_CONFIGURED, "FALSE") == "FALSE")
		{
			echo pts_string_header("The batch mode must first be configured\nRun: phoronix-test-suite batch-setup");
			return false;
		}
		
		$MODULE_STORE = pts_module_store_var("TO_STRING");
		$TEST_PROPERTIES = array();
		$types_of_tests = array();

		if(count($to_run_identifiers) == 0 || empty($to_run_identifiers[0]))
		{
			echo pts_string_header("The test, suite, or saved identifier must be supplied.");
			return false;
		}

		$start_identifier_count = count($to_run_identifiers);
		for($i = 0; $i < $start_identifier_count; $i++)
		{
			// Clean up tests
			$lower_identifier = strtolower($to_run_identifiers[$i]);

			if(pts_is_test($lower_identifier))
			{
				$xml_parser = new pts_test_tandem_XmlReader(pts_location_test($lower_identifier));
				$test_title = $xml_parser->getXMLValue(P_TEST_TITLE);

				if(empty($test_title))
				{
					echo pts_string_header($lower_identifier . " is not a test.");
					unset($to_run_identifiers[$i]);
					continue;
				}
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
			else if(IS_SCTP_MODE)
			{
				$to_run_identifiers[$i] = basename($to_run_identifiers[$i]);
			}
		}

		$unique_test_names = count(array_unique($to_run_identifiers));
		$test_names_r = array();
		$test_arguments_r = array();
		$test_arguments_description_r = array();

		foreach($to_run_identifiers as $to_run)
		{
			$to_run = strtolower($to_run);
			$to_run_type = pts_test_type($to_run);
			pts_set_assignment_once("TO_RUN", $to_run);

			if(!$to_run_type)
			{
				if(is_file(SAVE_RESULTS_DIR . $to_run . "/composite.xml"))
				{
					$to_run_type = "LOCAL_COMPARISON";
				}
				else if(is_file(pts_input_correct_results_path($to_run)))
				{
					$to_run_type = "LOCAL_COMPARISON";
				}
				else if(pts_is_global_id($to_run))
				{
					$to_run_type = "GLOBAL_COMPARISON";
					pts_set_assignment_once("GLOBAL_COMPARISON", true);
					pts_clone_from_global($to_run);
				}
				else
				{
					echo pts_string_header("Not Recognized: " . $to_run);
					continue;
				}

				pts_set_assignment_once("AUTO_SAVE_NAME", $to_run);
			}
			else
			{
				if(IS_SCTP_MODE)
				{
					pts_set_assignment_once("AUTO_SAVE_NAME", $to_run);
					pts_set_assignment_once("AUTO_SAVE_NAME", $to_run);
					$to_run_type = "SCTP_COMPARISON";
				}
				else
				{
					if(pts_is_test($to_run) && !pts_is_assignment("RUN_CONTAINS_A_NO_RESULT_TYPE"))
					{
						$xml_parser = new pts_test_tandem_XmlReader(pts_location_test($to_run));
						$result_format = $xml_parser->getXMLValue(P_TEST_RESULTFORMAT);

						if($result_format == "NO_RESULT")
						{
							pts_set_assignment("RUN_CONTAINS_A_NO_RESULT_TYPE", true);
						}
					}
				}
			}

			if(pts_is_test($to_run))
			{
				if(pts_read_assignment("IS_BATCH_MODE") != false)
				{
					$option_output = pts_generate_batch_run_options($to_run);

					$TEST_RUN = array();
					$TEST_ARGS = $option_output[0];
					$TEST_ARGS_DESCRIPTION = $option_output[1];

					for($i = 0; $i < count($TEST_ARGS); $i++)
					{
						array_push($TEST_RUN, $to_run);
					}
				}
				else if(pts_read_assignment("IS_DEFAULTS_MODE") == true)
				{
					$option_output = pts_defaults_test_options($to_run);

					$TEST_RUN = array();
					$TEST_ARGS = $option_output[0];
					$TEST_ARGS_DESCRIPTION = $option_output[1];

					for($i = 0; $i < count($TEST_ARGS); $i++)
					{
						array_push($TEST_RUN, $to_run);
					}
				}
				else
				{
					$option_output = pts_prompt_test_options($to_run);

					$TEST_RUN = array($to_run);
					$TEST_ARGS = array($option_output[0]);
					$TEST_ARGS_DESCRIPTION = array($option_output[1]);
				}

				if($unique_test_names == 1)
				{
					$xml_parser = new pts_test_tandem_XmlReader(pts_location_test($to_run));
					$test_description = $xml_parser->getXMLValue(P_TEST_DESCRIPTION);
					$test_version = $xml_parser->getXMLValue(P_TEST_PTSVERSION);
					$test_type = $xml_parser->getXMLValue(P_TEST_HARDWARE_TYPE);
					unset($xml_parser);
				}
			}
			else if(pts_is_suite($to_run))
			{
				echo pts_string_header("Test Suite: " . $to_run);

				$xml_parser = new tandem_XmlReader(pts_location_suite($to_run));

				if($unique_test_names == 1)
				{
					$test_description = $xml_parser->getXMLValue(P_SUITE_DESCRIPTION);
					$test_version = $xml_parser->getXMLValue(P_SUITE_VERSION);
					$test_type = $xml_parser->getXMLValue(P_SUITE_TYPE);
				}

				$TEST_RUN = $xml_parser->getXMLArrayValues(P_SUITE_TEST_NAME);
				$TEST_ARGS = $xml_parser->getXMLArrayValues(P_SUITE_TEST_ARGUMENTS);
				$TEST_ARGS_DESCRIPTION = $xml_parser->getXMLArrayValues(P_SUITE_TEST_DESCRIPTION);

				$PRE_RUN_MESSAGE = $xml_parser->getXMLValue(P_SUITE_PRERUNMSG);
				$POST_RUN_MESSAGE = $xml_parser->getXMLValue(P_SUITE_POSTRUNMSG);
				$SUITE_RUN_MODE = $xml_parser->getXMLValue(P_SUITE_RUNMODE);

				if($SUITE_RUN_MODE == "PCQS")
				{
					pts_set_assignment_once("IS_PCQS_MODE", true);
				}

				unset($xml_parser);
			}
			else if($to_run_type == "GLOBAL_COMPARISON" || $to_run_type == "LOCAL_COMPARISON")
			{
				echo pts_string_header("Comparison: " . $to_run);

				$xml_parser = new tandem_XmlReader(SAVE_RESULTS_DIR . $to_run . "/composite.xml");
				$custom_title = $xml_parser->getXMLValue(P_RESULTS_SUITE_TITLE);
				$test_description = $xml_parser->getXMLValue(P_RESULTS_SUITE_DESCRIPTION);
				$test_extensions = $xml_parser->getXMLValue(P_RESULTS_SUITE_EXTENSIONS);
				$test_previous_properties = $xml_parser->getXMLValue(P_RESULTS_SUITE_PROPERTIES);
				$test_version = $xml_parser->getXMLValue(P_RESULTS_SUITE_VERSION);
				$test_type = $xml_parser->getXMLValue(P_RESULTS_SUITE_TYPE);
				$TEST_RUN = $xml_parser->getXMLArrayValues(P_RESULTS_TEST_TESTNAME);
				$TEST_ARGS = $xml_parser->getXMLArrayValues(P_RESULTS_TEST_ARGUMENTS);
				$TEST_ARGS_DESCRIPTION = $xml_parser->getXMLArrayValues(P_RESULTS_TEST_ATTRIBUTES);

				pts_set_assignment_once("AUTO_SAVE_NAME", $to_run);

				foreach(explode(";", $test_previous_properties) as $test_prop)
				{
					if(!in_array($test_prop, $TEST_PROPERTIES))
					{
						array_push($TEST_PROPERTIES, $test_prop);
					}
				}

				pts_module_process_extensions($test_extensions, $MODULE_STORE);
			}
			else
			{
				echo pts_string_header("\nUnrecognized option: " . $to_run . "\n");
				continue;
			}

			for($i = 0; $i < count($TEST_ARGS) && $i < count($TEST_RUN); $i++)
			{
				array_push($test_names_r, $TEST_RUN[$i]);

				$argument = $TEST_ARGS[$i];
				array_push($test_arguments_r, $argument);

				if(isset($TEST_ARGS_DESCRIPTION[$i]))
				{
					$argument = $TEST_ARGS_DESCRIPTION[$i];
				}
				array_push($test_arguments_description_r, $argument);
			}
		}

		if(count($to_run_identifiers) == 0 || count($test_names_r) == 0)
		{
			return false;
		}
		$unique_test_names = count(array_unique($to_run_identifiers));

		if($unique_test_names > 1)
		{
			pts_set_assignment("MULTI_TYPE_RUN", true);
		}

		$xml_results_writer = new tandem_XmlWriter();

		echo "\n";
		$save_results = false;
		if(!pts_read_assignment("RUN_CONTAINS_A_NO_RESULT_TYPE") || $unique_test_names > 1)
		{
			if(pts_is_assignment("AUTO_SAVE_NAME") || getenv("TEST_RESULTS_NAME") != false)
			{
				$save_results = true;
			}
			else
			{
				$save_results = pts_bool_question("Would you like to save these test results (Y/n)?", true, "SAVE_RESULTS");
			}

			if($save_results)
			{
				if($unique_test_names == 1 && ($ra = pts_read_assignment("AUTO_SAVE_NAME")) != false)
				{
					$auto_name = $ra;
				}
				else
				{
					$auto_name = null;
				}

				// Prompt Save File Name
				$file_name_result = pts_prompt_save_file_name($auto_name, $TEST_RUN[0]);
				$PROPOSED_FILE_NAME = $file_name_result[0];
				$CUSTOM_TITLE = $file_name_result[1];

				// Prompt Identifiers
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
				{
					$result_identifiers = array();
				}

				$results_identifier = pts_prompt_results_identifier($result_identifiers);
				pts_set_assignment("SAVE_FILE_NAME", $PROPOSED_FILE_NAME);

				// Prompt Description

				if(pts_read_assignment("IS_BATCH_MODE") == false || pts_read_user_config(P_OPTION_BATCH_PROMPTDESCRIPTION, "FALSE") == "TRUE")
				{
					if($unique_test_names > 1)
					{
						$unique_tests_r = array_unique($to_run_identifiers);
						$last = array_pop($unique_tests_r);
						array_push($unique_tests_r, "and " . $last);

						$test_description = "Running ";

						if($unique_test_names == 2)
						{
							$test_description .= implode(" ", $unique_tests_r);
						}
						else
						{
							$test_description .= implode(", ", $unique_tests_r);
						}
						$test_description .= ".";
					}
					if(empty($test_description))
					{
						$test_description = "N/A";
					}

					echo pts_string_header("If you wish, enter a new description below.\nPress ENTER to proceed without changes.", "#");
					echo "Current Description: " . $test_description;
					echo "\n\nNew Description: ";
					$new_test_description = trim(fgets(STDIN));

					if(!empty($new_test_description))
					{
						$test_description = $new_test_description;
					}
				}

				if($unique_test_names > 1)
				{
					$test_version = "1.0.0";
					$test_type = "System";
				}
			}
		}

		if(!$save_results)
		{
			$results_identifier = "";
			$save_results = false;
			pts_set_assignment("SAVE_FILE_NAME", null);
		}

		// Run the test process

		if(isset($PRE_RUN_MESSAGE))
		{
			pts_user_message($PRE_RUN_MESSAGE);
		}

		pts_recurse_call_tests($test_names_r, $test_arguments_r, $save_results, $xml_results_writer, $results_identifier, $test_arguments_description_r);

		if(isset($POST_RUN_MESSAGE))
		{
			pts_user_message($POST_RUN_MESSAGE);
		}

		pts_set_assignment("PTS_TESTING_DONE", 1);
		pts_module_process("__post_run_process", $test_names_r);

		if($save_results)
		{
			if(pts_read_assignment("IS_BATCH_MODE") != false)
			{
				array_push($TEST_PROPERTIES, "PTS_BATCH_MODE");
			}

			$test_notes = pts_generate_test_notes($test_type);

			$id = pts_request_new_id();
			$xml_results_writer->setXslBinding("pts-results-viewer.xsl");
			$xml_results_writer->addXmlObject(P_RESULTS_SYSTEM_HARDWARE, $id, pts_hw_string());
			$xml_results_writer->addXmlObject(P_RESULTS_SYSTEM_SOFTWARE, $id, pts_sw_string());
			$xml_results_writer->addXmlObject(P_RESULTS_SYSTEM_AUTHOR, $id, pts_current_user());
			$xml_results_writer->addXmlObject(P_RESULTS_SYSTEM_DATE, $id, date("F j, Y h:i A"));
			$xml_results_writer->addXmlObject(P_RESULTS_SYSTEM_NOTES, $id, trim($test_notes));
			$xml_results_writer->addXmlObject(P_RESULTS_SYSTEM_PTSVERSION, $id, PTS_VERSION);
			$xml_results_writer->addXmlObject(P_RESULTS_SYSTEM_IDENTIFIERS, $id, $results_identifier);

			$id = pts_request_new_id();
			$xml_results_writer->addXmlObject(P_RESULTS_SUITE_TITLE, $id, $CUSTOM_TITLE);
			$xml_results_writer->addXmlObject(P_RESULTS_SUITE_NAME, $id, pts_read_assignment("TO_RUN"));
			$xml_results_writer->addXmlObject(P_RESULTS_SUITE_VERSION, $id, $test_version);
			$xml_results_writer->addXmlObject(P_RESULTS_SUITE_DESCRIPTION, $id, $test_description);
			$xml_results_writer->addXmlObject(P_RESULTS_SUITE_TYPE, $id, $test_type);
			$xml_results_writer->addXmlObject(P_RESULTS_SUITE_EXTENSIONS, $id, $MODULE_STORE);
			$xml_results_writer->addXmlObject(P_RESULTS_SUITE_PROPERTIES, $id, implode(";", $TEST_PROPERTIES));

			if(pts_read_assignment("TEST_RAN") == true)
			{
				pts_save_test_file($PROPOSED_FILE_NAME, $xml_results_writer);
				echo "Results Saved To: " . SAVE_RESULTS_DIR . $PROPOSED_FILE_NAME . "/composite.xml\n";
				pts_display_web_browser(SAVE_RESULTS_DIR . $PROPOSED_FILE_NAME . "/index.html");

				$upload_results = pts_bool_question("Would you like to upload these results to Phoronix Global (Y/n)?", true, "UPLOAD_RESULTS");

				if($upload_results)
				{
					$tags_input = pts_promt_user_tags(array($results_identifier));
					$upload_url = pts_global_upload_result(SAVE_RESULTS_DIR . $PROPOSED_FILE_NAME . "/composite.xml", $tags_input);

					if(!empty($upload_url))
					{
						echo "\nResults Uploaded To: " . $upload_url . "\n";
						pts_module_process("__event_global_upload", $upload_url);
						pts_display_web_browser("\"" . $upload_url . "\"", "Do you want to launch Phoronix Global", true);
					}
					else
					{
						echo "\nResults Failed To Upload.\n";
					}
				}
				echo "\n";
			}
		}
	}
}

?>
