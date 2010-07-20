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

class run_test implements pts_option_interface
{
	public static function required_function_sets()
	{
		return array("run", "execution");
	}
	public static function run($to_run_identifiers)
	{
		if(pts_read_assignment("IS_BATCH_MODE"))
		{
			if(pts_config::read_bool_config(P_OPTION_BATCH_CONFIGURED, "FALSE") == false && !pts_is_assignment("AUTOMATED_MODE"))
			{
				echo pts_string_header("The batch mode must first be configured.\nRun: phoronix-test-suite batch-setup");
				return false;
			}
		}
		if(count($to_run_identifiers) == 0 || empty($to_run_identifiers[0]))
		{
			echo pts_string_header("A test, suite, or saved identifier must be supplied.");
			return false;
		}
		if(!is_writable(TEST_ENV_DIR))
		{
			echo "\nERROR: The test installation directory is not writable.\nLocation: " . TEST_ENV_DIR . "\n";
			return false;
		}

		$test_properties = array();

		// Cleanup tests to run
		if(pts_cleanup_tests_to_run($to_run_identifiers) == false)
		{
			return false;
		}
		else if(count($to_run_identifiers) == 0)
		{
			if(!pts_read_assignment("USER_REJECTED_TEST_INSTALL_NOTICE"))
			{
				echo pts_string_header("You must enter at least one test, suite, or result identifier to run.");
			}

			return false;
		}

		$unique_test_count = count(array_unique($to_run_identifiers));
		$test_run_manager = new pts_test_run_manager();

		foreach($to_run_identifiers as $to_run)
		{
			$to_run = strtolower($to_run);

			if(!pts_is_test_result($to_run) && pts_is_global_id($to_run))
			{
				pts_clone_from_global($to_run);
			}

			if(pts_is_test($to_run))
			{
				if(!pts_is_assignment("RUN_CONTAINS_A_NO_RESULT_TYPE"))
				{
					if(pts_test_read_xml($to_run, P_TEST_RESULTFORMAT) == "NO_RESULT")
					{
						pts_set_assignment("RUN_CONTAINS_A_NO_RESULT_TYPE", true);
					}
					if(pts_test_read_xml($to_run, P_TEST_AUTO_SAVE_RESULTS) == "TRUE")
					{
						pts_set_assignment("TEST_PROFILE_REQUESTS_SAVE", true);
					}
				}

				if(pts_read_assignment("IS_BATCH_MODE") && pts_config::read_bool_config(P_OPTION_BATCH_TESTALLOPTIONS, "TRUE"))
				{
					list($test_arguments, $test_arguments_description) = pts_generate_batch_run_options($to_run);
				}
				else if(pts_read_assignment("IS_DEFAULTS_MODE"))
				{
					list($test_arguments, $test_arguments_description) = pts_defaults_test_options($to_run);
				}
				else
				{
					list($test_arguments, $test_arguments_description) = pts_prompt_test_options($to_run);
				}

				$test_run_manager->add_single_test_run($to_run, $test_arguments, $test_arguments_description);

				if($unique_test_count == 1)
				{
					$xml_parser = new pts_test_tandem_XmlReader($to_run);
					$test_description = $xml_parser->getXMLValue(P_TEST_DESCRIPTION);
					$test_version = $xml_parser->getXMLValue(P_TEST_PTSVERSION);
					$test_type = $xml_parser->getXMLValue(P_TEST_HARDWARE_TYPE);
				}
			}
			else if(pts_is_suite($to_run))
			{
				echo pts_string_header("Test Suite: " . $to_run);

				$xml_parser = new pts_suite_tandem_XmlReader($to_run);

				if($unique_test_count == 1)
				{
					$test_description = $xml_parser->getXMLValue(P_SUITE_DESCRIPTION);
					$test_version = $xml_parser->getXMLValue(P_SUITE_VERSION);
					$test_type = $xml_parser->getXMLValue(P_SUITE_TYPE);
				}

				$pre_run_message = $xml_parser->getXMLValue(P_SUITE_PRERUNMSG);
				$post_run_message = $xml_parser->getXMLValue(P_SUITE_POSTRUNMSG);
				$suite_run_mode = $xml_parser->getXMLValue(P_SUITE_RUNMODE);

				if($suite_run_mode == "PCQS")
				{
					pts_set_assignment_once("IS_PCQS_MODE", true);
				}

				$test_run_manager->add_suite_run($to_run);
			}
			else if(pts_is_test_result($to_run))
			{
				echo pts_string_header("Comparison: " . $to_run);

				$xml_parser = new pts_results_tandem_XmlReader($to_run);
				$test_description = $xml_parser->getXMLValue(P_RESULTS_SUITE_DESCRIPTION);
				$test_extensions = $xml_parser->getXMLValue(P_RESULTS_SUITE_EXTENSIONS);
				$test_previous_properties = $xml_parser->getXMLValue(P_RESULTS_SUITE_PROPERTIES);
				$test_version = $xml_parser->getXMLValue(P_RESULTS_SUITE_VERSION);
				$test_type = $xml_parser->getXMLValue(P_RESULTS_SUITE_TYPE);
				$test_run = $xml_parser->getXMLArrayValues(P_RESULTS_TEST_TESTNAME);
				$test_args = $xml_parser->getXMLArrayValues(P_RESULTS_TEST_ARGUMENTS);
				$test_args_description = $xml_parser->getXMLArrayValues(P_RESULTS_TEST_ATTRIBUTES);
				$test_override_options = array();

				pts_set_assignment("IS_TEST_RESULT", true);
				pts_set_assignment("AUTO_SAVE_NAME", $to_run);

				foreach(explode(";", $test_previous_properties) as $test_prop)
				{
					pts_arrays::unique_push($test_properties, $test_prop);
				}

				pts_module_process_extensions($test_extensions);

				if(pts_is_assignment("FINISH_INCOMPLETE_RUN"))
				{
					$all_test_runs = $test_run;
					$all_test_args = $test_args;
					$all_test_args_description = $test_args_description;
					$test_run = array();
					$test_args = array();
					$test_args_description = array();

					$tests_to_complete = pts_read_assignment("TESTS_TO_COMPLETE");

					foreach($tests_to_complete as $test_pos)
					{
						array_push($test_run, $all_test_runs[$test_pos]);
						array_push($test_args, $all_test_args[$test_pos]);
						array_push($test_args_description, $all_test_args_description[$test_pos]);
					}

					pts_clear_empty_tests($test_run, $test_args, $test_args_description);
				}
				else if(pts_is_assignment("RECOVER_RUN"))
				{
					$test_run = array();
					$test_args = array();
					$test_args_description = array();

					foreach(pts_read_assignment("RECOVER_RUN_REQUESTS") as $test_run_request)
					{
						array_push($test_run, $test_run_request->get_identifier());
						array_push($test_args, $test_run_request->get_arguments());
						array_push($test_args_description, $test_run_request->get_arguments_description());
						array_push($test_override_options, $test_run_request->get_override_options());
					}
				}
				else
				{
					pts_clear_empty_tests($test_run, $test_args, $test_args_description);
				}

				$test_run_manager->add_multi_test_run($test_run, $test_args, $test_args_description, $test_override_options);
			}
			else
			{
				echo pts_string_header("Not Recognized: " . $to_run);
				continue;
			}
		}

		// Run the test process
		pts_validate_test_installations_to_run($test_run_manager);

		if($test_run_manager->get_test_count() == 0)
		{
			return false;
		}

		$xml_results_writer = new pts_results_tandem_XmlWriter();

		echo "\n";
		$file_name = false;
		$save_results = false;
		if(!pts_read_assignment("RUN_CONTAINS_A_NO_RESULT_TYPE") || $unique_test_count > 1 || pts_read_assignment("FORCE_SAVE_RESULTS"))
		{
			if(pts_is_assignment("DO_NOT_SAVE_RESULTS"))
			{
				$save_results = false;
			}
			else if(pts_read_assignment("TEST_PROFILE_REQUESTS_SAVE") || pts_is_assignment("AUTO_SAVE_NAME") || pts_read_assignment("FORCE_SAVE_RESULTS") || pts_client::read_env("TEST_RESULTS_NAME"))
			{
				$save_results = true;
			}
			else
			{
				$save_results = pts_user_io::prompt_bool_input("Would you like to save these test results", true, "SAVE_RESULTS");
			}

			if($save_results)
			{
				if(($unique_test_count == 1 || pts_is_assignment("AUTOMATED_MODE")) && ($asn = pts_read_assignment("AUTO_SAVE_NAME")))
				{
					$auto_name = $asn;
				}
				else
				{
					$auto_name = true;
				}

				// Prompt Save File Name
				list($file_name, $file_name_title) = pts_prompt_save_file_name($test_run_manager, $auto_name);

				// Prompt Identifier
				pts_prompt_results_identifier($test_run_manager);

				if($unique_test_count > 1 || !isset($test_description))
				{
					$unique_tests_r = array_unique($to_run_identifiers);
					$last = array_pop($unique_tests_r);
					array_push($unique_tests_r, "and " . $last);

					$test_description = "Running " . implode(($unique_test_count == 2 ? " and " : ", "), $unique_tests_r) . ".";
				}

				// Prompt Description
				if(!pts_is_assignment("AUTOMATED_MODE") && !pts_read_assignment("FINISH_INCOMPLETE_RUN") && !pts_is_assignment("RECOVER_RUN") && (pts_read_assignment("IS_BATCH_MODE") == false || pts_config::read_bool_config(P_OPTION_BATCH_PROMPTDESCRIPTION, "FALSE")))
				{
					if(empty($test_description))
					{
						$test_description = "N/A";
					}

					echo pts_string_header("If you wish, enter a new description below.\nPress ENTER to proceed without changes.", "#");
					echo "Current Description: " . $test_description . "\n\nNew Description: ";
					$new_test_description = pts_user_io::read_user_input();

					if(!empty($new_test_description))
					{
						$test_description = $new_test_description;
					}
				}

				if($unique_test_count > 1)
				{
					$test_version = "1.0.0";
					$test_type = "System";
				}
			}
		}

		if($test_run_manager->get_tests_to_run_count() == 0)
		{
			return false;
		}

		if(isset($pre_run_message))
		{
			pts_user_io::display_interrupt_message($pre_run_message);
		}

		if($save_results)
		{
			$results_directory = pts_setup_result_directory($file_name) . "/";

			if(pts_read_assignment("IS_BATCH_MODE"))
			{
				pts_arrays::unique_push($test_properties, "PTS_BATCH_MODE");
			}
			else if(pts_read_assignment("IS_DEFAULTS_MODE"))
			{
				pts_arrays::unique_push($test_properties, "PTS_DEFAULTS_MODE");
			}

			if(!pts_is_assignment("FINISH_INCOMPLETE_RUN") && !pts_is_assignment("RECOVER_RUN") && (!pts_is_test_result($file_name) || !pts_test_result_contains_result_identifier($file_name, $test_run_manager->get_results_identifier())))
			{
				$xml_results_writer->setXslBinding("pts-results-viewer.xsl");
				$xml_results_writer->addXmlObject(P_RESULTS_SYSTEM_HARDWARE, 0, phodevi::system_hardware(true));
				$xml_results_writer->addXmlObject(P_RESULTS_SYSTEM_SOFTWARE, 0, phodevi::system_software(true));
				$xml_results_writer->addXmlObject(P_RESULTS_SYSTEM_AUTHOR, 0, pts_client::current_user());
				$xml_results_writer->addXmlObject(P_RESULTS_SYSTEM_DATE, 0, date("Y-m-d H:i:s"));
				//$xml_results_writer->addXmlObject(P_RESULTS_SYSTEM_NOTES, 0, pts_test_notes_manager::generate_test_notes($test_type));
				$xml_results_writer->addXmlObject(P_RESULTS_SYSTEM_PTSVERSION, 0, PTS_VERSION);
				$xml_results_writer->addXmlObject(P_RESULTS_SYSTEM_IDENTIFIERS, 0, $test_run_manager->get_results_identifier());
				$wrote_system_xml = true;

				$id = $xml_results_writer->request_unique_id();
				$xml_results_writer->addXmlObject(P_RESULTS_SUITE_TITLE, 1, $file_name_title);
				$xml_results_writer->addXmlObject(P_RESULTS_SUITE_NAME, 1, (count($to_run_identifiers) == 1 ? pts_arrays::first_element($to_run_identifiers) : "custom"));
				$xml_results_writer->addXmlObject(P_RESULTS_SUITE_VERSION, 1, $test_version);
				$xml_results_writer->addXmlObject(P_RESULTS_SUITE_DESCRIPTION, 1, $test_description);
				$xml_results_writer->addXmlObject(P_RESULTS_SUITE_TYPE, 1, $test_type);
				$xml_results_writer->addXmlObject(P_RESULTS_SUITE_EXTENSIONS, 1, pts_module_manager::var_store_string());
				$xml_results_writer->addXmlObject(P_RESULTS_SUITE_PROPERTIES, 1, implode(";", $test_properties));
			}
			else
			{
				$wrote_system_xml = false;
			}

			$pso = new pts_storage_object(true, false);
			$pso->add_object("test_run_manager", $test_run_manager);
			$pso->add_object("batch_mode", pts_read_assignment("IS_BATCH_MODE"));
			$pso->add_object("system_hardware", phodevi::system_hardware(false));
			$pso->add_object("system_software", phodevi::system_software(false));

			$pt2so_location = $results_directory . "objects.pt2so";
			$pso->save_to_file($pt2so_location);
			unset($pso);
		}

		// Create a lock
		$lock_path = pts_client::temporary_directory() . "/phoronix-test-suite.active";
		pts_client::create_lock($lock_path);

		// Run the actual tests
		pts_module_process("__pre_run_process", $test_run_manager);
		pts_set_assignment("PTS_STATS_DYNAMIC_RUN_COUNT", pts_strings::string_bool(pts_config::read_user_config(P_OPTION_STATS_DYNAMIC_RUN_COUNT, "TRUE")));
		pts_set_assignment("PTS_STATS_NO_ON_LENGTH", pts_config::read_user_config(P_OPTION_STATS_NO_DYNAMIC_ON_LENGTH, "20"));
		pts_set_assignment("PTS_STATS_STD_DEV_THRESHOLD", pts_config::read_user_config(P_OPTION_STATS_STD_DEVIATION_THRESHOLD, "3.50"));
		pts_set_assignment("PTS_STATS_EXPORT_TO", pts_config::read_user_config(P_OPTION_STATS_EXPORT_RESULTS_TO, null));
		pts_call_test_runs($test_run_manager, $xml_results_writer);
		pts_set_assignment("PTS_TESTING_DONE", 1);
		pts_module_process("__post_run_process", $test_run_manager);

		if(isset($post_run_message))
		{
			pts_user_io::display_interrupt_message($post_run_message);
		}

		if(pts_read_assignment("IS_BATCH_MODE") || pts_is_assignment("DEBUG_TEST_PROFILE") || $test_run_manager->get_tests_to_run_count() > 3)
		{
			$failed_runs = $test_run_manager->get_failed_test_run_requests();

			if(count($failed_runs) > 0)
			{
				echo "\n\nThe following tests failed to properly run:\n\n";
				foreach($failed_runs as &$run_request)
				{
					echo "\t- " . $run_request->get_identifier() . ($run_request->get_arguments_description() != null ? ": " . $run_request->get_arguments_description() : null) . "\n";
				}
				echo "\n";
			}
		}

		if($save_results)
		{
			if(!pts_is_assignment("TEST_RAN") && !pts_read_assignment("FORCE_SAVE_RESULTS") && !pts_read_assignment("IS_TEST_RESULT") && !pts_read_assignment("FINISH_INCOMPLETE_RUN") && !pts_read_assignment("PHOROMATIC_TRIGGER"))
			{
				pts_file_io::delete(SAVE_RESULTS_DIR . $file_name);
				return false;
			}

			pts_file_io::unlink($pt2so_location);
			pts_file_io::delete(SAVE_RESULTS_DIR . $test_run_manager->get_file_name() . "/test-logs/active/", null, true);

			if($wrote_system_xml)
			{
				$xml_results_writer->addXmlObject(P_RESULTS_SYSTEM_NOTES, 0, pts_test_notes_manager::generate_test_notes($test_type), 0);
			}

			pts_module_process("__event_results_process", $xml_results_writer);
			pts_save_test_file($xml_results_writer, $file_name);
			pts_module_process("__event_results_saved", $file_name);
			echo "\nResults Saved To: " . SAVE_RESULTS_DIR . $file_name . "/composite.xml\n";
			pts_set_assignment_next("PREV_SAVE_RESULTS_IDENTIFIER", $file_name);
			pts_client::display_web_page(SAVE_RESULTS_DIR . $file_name . "/index.html");

			if(!pts_read_assignment("BLOCK_GLOBAL_UPLOADS") && !defined("NO_NETWORK_COMMUNICATION"))
			{
				if(pts_is_assignment("AUTOMATED_MODE"))
				{
					$upload_results = pts_read_assignment("AUTO_UPLOAD_TO_GLOBAL");
				}
				else
				{
					$upload_results = pts_user_io::prompt_bool_input("Would you like to upload these results to Phoronix Global", true, "UPLOAD_RESULTS");
				}

				if($upload_results)
				{
					$tags_input = pts_prompt_user_tags($to_run_identifiers);
					$upload_url = pts_global_upload_result(SAVE_RESULTS_DIR . $file_name . "/composite.xml", $tags_input);

					if(!empty($upload_url))
					{
						echo "\nResults Uploaded To: " . $upload_url . "\n";
						pts_set_assignment_next("PREV_GLOBAL_UPLOAD_URL", $upload_url);
						pts_module_process("__event_global_upload", $upload_url);
						pts_client::display_web_page($upload_url, "Do you want to launch Phoronix Global", true);
					}
					else
					{
						echo "\nResults Failed To Upload.\n";
					}
				}
			}
		}
		echo "\n";

		pts_client::release_lock($lock_path);
		pts_set_assignment_next("PREV_TEST_IDENTIFIER", $test_run_manager->get_tests_to_run_identifiers());
	}
}

?>
