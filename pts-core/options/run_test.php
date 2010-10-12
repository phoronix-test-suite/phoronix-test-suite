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
	public static function run($to_run)
	{
		if(pts_test_run_manager::initial_checks($to_run) == false)
		{
			return false;
		}

		// Get our objects ready
		$test_run_manager = new pts_test_run_manager();

		// Determine what to run
		$test_run_manager->determine_tests_to_run($to_run);

		// Run the test process
		$test_run_manager->validate_tests_to_run();

		// Nothing to run
		if($test_run_manager->get_test_count() == 0)
		{
			return false;
		}

		// Save results?
		$test_run_manager->save_results_prompt();

		// Run the actual tests
		$test_run_manager->pre_process();
		$test_run_manager->call_test_runs();
		$test_run_manager->finish_process();
		echo "\n";
	}
}

?>
