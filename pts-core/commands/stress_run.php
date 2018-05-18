<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2015 - 2018, Phoronix Media
	Copyright (C) 2015 - 2018, Michael Larabel

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

class stress_run implements pts_option_interface
{
	const doc_section = 'Testing';
	const doc_description = 'This option will run the passed tests/suites in the multi-process stress-testing mode. The stress-run mode will not produce a result file but is rather intended for running multiple test profiles concurrently to stress / burn-in the system. The number of tests to run concurrently can be toggled via the PTS_CONCURRENT_TEST_RUNS environment variable and by default is set to a value of 2.';

	public static function argument_checks()
	{
		return array(
		new pts_argument_check('VARIABLE_LENGTH', array('pts_types', 'identifier_to_object'), null)
		);
	}
	public static function run($to_run)
	{
		$test_run_manager = new pts_stress_run_manager(array(
			'UploadResults' => false,
			'SaveResults' => false,
			'PromptForTestDescription' => false,
			'RunAllTestCombinations' => false,
			'PromptSaveName' => false,
			'PromptForTestIdentifier' => false,
			'OpenBrowser' => false
			));

		$tests_to_run_concurrently = 2;

		echo PHP_EOL . pts_client::cli_just_bold('STRESS-RUN ENVIRONMENT VARIABLES:') . PHP_EOL;

		if(($j = getenv('PTS_CONCURRENT_TEST_RUNS')) && is_numeric($j) && $j > 1)
		{
			$tests_to_run_concurrently = $j;
			echo PHP_EOL . 'PTS_CONCURRENT_TEST_RUNS set; running ' . $tests_to_run_concurrently . ' tests concurrently.' . PHP_EOL . PHP_EOL;
		}
		else
		{
			echo PHP_EOL . pts_client::cli_just_bold('PTS_CONCURRENT_TEST_RUNS:') . ' Set the PTS_CONCURRENT_TEST_RUNS environment variable to specify how many tests should be run concurrently during the stress-run process. If not specified, defaults to 2.' . PHP_EOL . PHP_EOL;
		}

		// Run the actual tests
		$total_loop_time = pts_client::read_env('TOTAL_LOOP_TIME');
		if($total_loop_time == 'infinite')
		{
			$total_loop_time = 'infinite';
			echo PHP_EOL . 'TOTAL_LOOP_TIME set; running tests in an infinite loop until otherwise triggered' . PHP_EOL . PHP_EOL;
		}
		else if($total_loop_time && is_numeric($total_loop_time) && $total_loop_time > 1)
		{
			echo PHP_EOL . 'TOTAL_LOOP_TIME set; running tests for ' . $total_loop_time . ' minutes' . PHP_EOL . PHP_EOL;
		}
		else
		{
			echo PHP_EOL . pts_client::cli_just_bold('TOTAL_LOOP_TIME:') . ' Set the TOTAL_LOOP_TIME environment variable if wishing to specify (in minutes) how long to run the stress-run process.' . PHP_EOL . PHP_EOL;
			$total_loop_time = false;
		}
		//pts_test_installer::standard_install($to_run);
		/*
		if(count($to_run) < $tests_to_run_concurrently)
		{
			echo PHP_EOL . 'More tests must be specified in order to run ' . $tests_to_run_concurrently . ' tests concurrently.';
			return false;
		}
		*/

		if($test_run_manager->initial_checks($to_run, 'SHORT') == false)
		{
			return false;
		}

		// Load the tests to run
		if($test_run_manager->load_tests_to_run($to_run) == false)
		{
			return false;
		}

		//$test_run_manager->pre_execution_process();
		$test_run_manager->multi_test_stress_run_execute($tests_to_run_concurrently, $total_loop_time);
	}
}

?>
