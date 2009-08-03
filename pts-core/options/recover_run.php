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
	public static function run($r)
	{
		if(!pts_is_test_result($r[0]) && !is_dir(SAVE_RESULTS_DIR . $r[0]))
		{
			echo "\nThe name of a test result file must be passed as an argument.\n";
			return false;
		}
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
		$test_run_manager = $pt2so_objects->read_object("test_run_manager");
		$recovered_identifier = $pt2so_objects->read_object("results_identifier");

		// Processing
		$tests_to_run = array();

		foreach($test_run_manager->get_tests_to_run() as $test_run_request)
		{
			$request_hash = $test_run_request->get_comparison_hash();

			if(($search_key = array_search($test_run_request->get_comparison_hash(), $result_file_hashes)) !== false)
			{
				$system_identifiers = $result_file_objects[$search_key]->get_identifiers();

				if(!in_array($recovered_identifier, $result_file_objects[$search_key]->get_identifiers()))
				{
					array_push($tests_to_run, $test_run_request);
				}
			}
			else
			{
				array_push($tests_to_run, $test_run_request);
			}

		}

		// TODO: add prompt to ask user whether to skip the last test that was running / where the crash likely happened

		if(count($tests_to_run) > 0)
		{
			echo pts_string_header("Proceeding To Recover Run For: " . $recovered_identifier);
		}
		else
		{
			echo "\nThere is nothing to be recovered for this test run.\n";
			return false;
		}

		pts_run_option_next("run_test", $r, array("RECOVER_RUN" => true, "RECOVER_RUN_REQUESTS" => $tests_to_run, "AUTO_TEST_RESULTS_IDENTIFIER" => $recovered_identifier));
	}
}

?>
