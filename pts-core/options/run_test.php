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
	public static function run($to_run_identifiers)
	{
		// Refresh the pts_client::$display in case we need to run in debug mode
		pts_client::init_display_mode();

		if(pts_read_assignment("IS_BATCH_MODE"))
		{
			if(pts_config::read_bool_config(P_OPTION_BATCH_CONFIGURED, "FALSE") == false && !pts_is_assignment("AUTOMATED_MODE"))
			{
				pts_client::$display->generic_error("The batch mode must first be configured.\nTo configure, run phoronix-test-suite batch-setup");
				return false;
			}
		}

		if(!is_writable(TEST_ENV_DIR))
		{
			pts_client::$display->generic_error("The test installation directory is not writable.\nLocation: " . TEST_ENV_DIR);
			return false;
		}

		$test_properties = array();

		// Cleanup tests to run
		if(pts_test_run_manager::cleanup_tests_to_run($to_run_identifiers) == false)
		{
			return false;
		}
		else if(count($to_run_identifiers) == 0)
		{
			if(!pts_read_assignment("USER_REJECTED_TEST_INSTALL_NOTICE"))
			{
				pts_client::$display->generic_error("You must enter at least one test, suite, or result identifier to run.");
			}

			return false;
		}

		// Get our objects ready
		$test_run_manager = new pts_test_run_manager();

		// Determine what to run
		$test_run_manager->determine_tests_to_run($to_run_identifiers);

		// Run the test process
		$test_run_manager->validate_tests_to_run();

		// Nothing to run
		if($test_run_manager->get_test_count() == 0)
		{
			return false;
		}

		pts_module_manager::module_process("__run_manager_setup", $test_run_manager);

		// Save results?
		$test_run_manager->save_results_prompt();

		if(isset($pre_run_message))
		{
			pts_user_io::display_interrupt_message($pre_run_message);
		}

		if($test_run_manager->do_save_results())
		{
			$test_run_manager->result_file_setup();
			$results_directory = pts_client::setup_test_result_directory($test_run_manager->get_file_name()) . '/';

			if(pts_read_assignment("IS_BATCH_MODE"))
			{
				pts_arrays::unique_push($test_properties, "PTS_BATCH_MODE");
			}
			else if(pts_read_assignment("IS_DEFAULTS_MODE"))
			{
				pts_arrays::unique_push($test_properties, "PTS_DEFAULTS_MODE");
			}

			if(!pts_is_assignment("FINISH_INCOMPLETE_RUN") && !pts_is_assignment("RECOVER_RUN") && (!pts_is_test_result($test_run_manager->get_file_name()) || $test_run_manager->result_already_contains_identifier() == false))
			{
				$test_run_manager->result_file_writer->add_result_file_meta_data($test_run_manager, $test_properties);
				$test_run_manager->result_file_writer->add_current_system_information();
				$wrote_system_xml = true;
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
		pts_module_manager::module_process("__pre_run_process", $test_run_manager);
		$test_run_manager->call_test_runs();
		pts_set_assignment("PTS_TESTING_DONE", 1);
		pts_module_manager::module_process("__post_run_process", $test_run_manager);

		if(pts_read_assignment("IS_BATCH_MODE") || pts_is_assignment("DEBUG_TEST_PROFILE") || $test_run_manager->get_test_count() > 3)
		{
			$failed_runs = $test_run_manager->get_failed_test_run_requests();

			if(count($failed_runs) > 0)
			{
				echo "\n\nThe following tests failed to properly run:\n\n";
				foreach($failed_runs as &$run_request)
				{
					echo "\t- " . $run_request->test_profile->get_identifier() . ($run_request->get_arguments_description() != null ? ": " . $run_request->get_arguments_description() : null) . "\n";
				}
				echo "\n";
			}
		}

		if($test_run_manager->do_save_results())
		{
			if(!pts_is_assignment("TEST_RAN") && !pts_is_test_result($test_run_manager->get_file_name()) && !pts_read_assignment("FINISH_INCOMPLETE_RUN") && !pts_read_assignment("PHOROMATIC_TRIGGER"))
			{
				pts_file_io::delete(SAVE_RESULTS_DIR . $test_run_manager->get_file_name());
				return false;
			}

			pts_file_io::unlink($pt2so_location);
			pts_file_io::delete(SAVE_RESULTS_DIR . $test_run_manager->get_file_name() . "/test-logs/active/", null, true);

			if($wrote_system_xml)
			{
				$test_run_manager->result_file_writer->add_test_notes(pts_test_notes_manager::generate_test_notes($test_type));
			}

			pts_module_manager::module_process("__event_results_process", $test_run_manager);
			$test_run_manager->result_file_writer->save_result_file($test_run_manager->get_file_name());
			pts_module_manager::module_process("__event_results_saved", $test_run_manager);
			//echo "\nResults Saved To: " . SAVE_RESULTS_DIR . $test_run_manager->get_file_name() . "/composite.xml\n";
			pts_set_assignment_next("PREV_SAVE_RESULTS_IDENTIFIER", $test_run_manager->get_file_name());
			pts_client::display_web_page(SAVE_RESULTS_DIR . $test_run_manager->get_file_name() . "/index.html");

			if($test_run_manager->allow_results_sharing() && !defined("NO_NETWORK_COMMUNICATION"))
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
					$tags_input = pts_global::prompt_user_result_tags($to_run_identifiers);
					$upload_url = pts_global::upload_test_result(SAVE_RESULTS_DIR . $test_run_manager->get_file_name() . "/composite.xml", $tags_input);

					if(!empty($upload_url))
					{
						echo "\nResults Uploaded To: " . $upload_url . "\n";
						pts_set_assignment_next("PREV_GLOBAL_UPLOAD_URL", $upload_url);
						pts_module_manager::module_process("__event_global_upload", $upload_url);
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
