<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel

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

class recover_run implements pts_option_interface
{
	public static function required_function_sets()
	{
		return array("merge");
	}
	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, "pts_is_test_result_directory", null, "No test result was found found.")
		);
	}
	public static function run($r)
	{
		if(!is_file(SAVE_RESULTS_DIR . $r[0] . "/objects.pt2so"))
		{
			if(is_file(SAVE_RESULTS_DIR . $r[0] . "/composite.xml"))
			{
				echo "\nThe test run is already complete.\n";
			}
			else
			{
				echo "\nThe test run could not be recovered.\n";
			}
			return false;
		}

		$pt2so_objects = pts_storage_object::recover_from_file(SAVE_RESULTS_DIR . $r[0] . "/objects.pt2so");

		if($pt2so_objects == null)
		{
			echo "\nThere is a compatibility problem with the test run to be recovered.\n";
			return false;
		}
		if($pt2so_objects->read_object("system_hardware") != pts_hw_string(false))
		{
			echo "\nThe system hardware does not match that of the recovered test run.\n";
			return false;
		}
		if($pt2so_objects->read_object("system_software") != pts_sw_string(false))
		{
			echo "\nThe system software does not match that of the recovered test run.\n";
			return false;
		}

		if(is_file(SAVE_RESULTS_DIR . $r[0] . "/active.xml"))
		{
			file_put_contents(SAVE_RESULTS_DIR . $r[0] . "/composite.xml", 
				pts_merge_test_results(SAVE_RESULTS_DIR . $r[0] . "/active.xml", SAVE_RESULTS_DIR . $r[0] . "/composite.xml"));
		}

		// Result file (composite.xml)
		$result_file = new pts_result_file($r[0]);
		$result_file_objects = $result_file->get_result_objects();
		$result_file_hashes = array();

		foreach($result_file_objects as $i => $result_file_merge_test)
		{
			$result_file_hashes[$i] = $result_file_merge_test->get_comparison_hash(false);
		}

		// Recovered test_run_manager
		$is_batch_mode = $pt2so_objects->read_object("batch_mode");
		$test_run_manager = $pt2so_objects->read_object("test_run_manager");
		$recovered_identifier = $test_run_manager->get_results_identifier();

		// Processing
		$tests_to_run = array();
		$test_to_run_is_empty = true;

		foreach($test_run_manager->get_tests_to_run() as $test_run_request)
		{
			if(!($test_run_request instanceOf pts_test_run_request))
			{
				// TODO: determine how to handle arrays a pts_weighted_test_run_manager here
				continue;
			}

			$request_hash = $test_run_request->get_comparison_hash();
			$add_test = false;

			if(($search_key = array_search($test_run_request->get_comparison_hash(), $result_file_hashes)) !== false)
			{
				if(!in_array($recovered_identifier, $result_file_objects[$search_key]->get_result_buffer()->get_identifiers()))
				{
					$add_test = true;
				}
			}
			else
			{
				$add_test = true;
			}

			if($add_test)
			{
				if($test_to_run_is_empty)
				{
					$test_to_run_is_empty = false;
					echo pts_string_header("Last Test Run: " . $test_run_request->get_identifier() . "\nLast Test Parameters: " . $test_run_request->get_arguments_description());
					$skip_this = pts_bool_question("Would you like to skip running this test? Enter N to re-try (Y/n).", true);

					if($skip_this)
					{
						continue;
					}
				}

				array_push($tests_to_run, $test_run_request);
			}

		}

		if(count($tests_to_run) > 0)
		{
			echo pts_string_header("Proceeding To Recover Run For: " . $recovered_identifier);
		}
		else
		{
			echo "\nThere is nothing to be recovered for this test run.\n";
			return false;
		}

		pts_run_option_next("run_test", $r, array("RECOVER_RUN" => true, "RECOVER_RUN_REQUESTS" => $tests_to_run, "AUTO_TEST_RESULTS_IDENTIFIER" => $recovered_identifier, "BATCH_MODE" => $is_batch_mode));
	}
}

?>
