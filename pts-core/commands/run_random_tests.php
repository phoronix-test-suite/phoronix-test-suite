<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2014, Phoronix Media
	Copyright (C) 2014, Michael Larabel

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

class run_random_tests implements pts_option_interface
{
	const doc_section = 'Testing';
	const doc_description = 'This option will query OpenBenchmarking.org to run random benchmarks and result comparisons on the system. This test can be used for simply supplying interesting results from your system onto OpenBenchmarking.org, stressing your system with random workloads, seeding new OpenBenchmarking.org results, etc. Basic options are provided at start-up for tuning the randomness of the testing when running this command.';

	public static function run($r)
	{
		pts_client::$display->generic_heading('Random Test Execution');
		$allow_new_tests_to_be_installed = pts_user_io::prompt_bool_input('Allow new tests to be installed', true);
		$allow_new_dependencies_to_be_installed = $allow_new_tests_to_be_installed ? pts_user_io::prompt_bool_input('Allow new test dependencies to be installed', true) : false;
		$limit_test_subsystem = pts_user_io::prompt_bool_input('Limit tests to a given subsystem', false);
		$limit_test_subsystem = $limit_test_subsystem ? pts_user_io::prompt_text_menu('Select subsystem(s) to test', pts_types::subsystem_targets(), true) : false;
		$upload_to_openbenchmarking = pts_user_io::prompt_bool_input('Auto-upload test results to OpenBenchmarking.org', true);

		while(1)
		{
			$to_test = array();

			if(empty($to_test))
			{
				// Randomly pick some installed tests
				$installed_tests = pts_tests::installed_tests();

				if($installed_tests > 2)
				{
					shuffle($installed_tests);
					$to_test = array_slice($installed_tests, 0, rand(1, 6));
				}
			}

			if(!isset($to_test[2]) && $allow_new_tests_to_be_installed)
			{
				$available_tests = pts_openbenchmarking::available_tests();
				shuffle($available_tests);
				$to_test = array_rand($available_tests, 0, rand(1, 4));
			}

			echo PHP_EOL . 'TO RUN: ' . implode(', ', $to_test) . PHP_EOL . PHP_EOL;

			// QUERY FROM OB
			$random_titles = array(phodevi::read_property('cpu', 'model') . ' Benchmarks', phodevi::read_property('system', 'operating-system') . ' Benchmarks', phodevi::read_property('system', 'operating-system') . ' Performance', phodevi::read_property('cpu', 'model') . ' Performance');
			$title = array_rand($random_titles);
			$id = phodevi::read_property('cpu', 'model') . ' - ' . phodevi::read_property('gpu', 'model') . ' - ' . phodevi::read_property('motherboard', 'identifier');
			if($limit_test_subsystem)
			{
				$subsystems_to_test = explode(',', $limit_test_subsystem);
				$subsystems_to_avoid = array_diff(pts_types::subsystem_targets(), $subsystems_to_test);
				pts_client::pts_set_environment_variable('SKIP_TESTING_SUBSYSTEMS', implode(',', $subsystems_to_avoid));
			}

			$test_flags = pts_c::auto_mode | pts_c::defaults_mode;
			if($allow_new_tests_to_be_installed)
			{
				if($allow_new_dependencies_to_be_installed == false)
				{
					$test_flags |= pts_c::skip_tests_with_missing_dependencies;
				}

				pts_test_installer::standard_install($to_test, $test_flags);
			}

			if(pts_test_run_manager::initial_checks($to_test, $test_flags) != false)
			{
				$test_run_manager = new pts_test_run_manager($test_flags);
				if($test_run_manager->load_tests_to_run($to_test))
				{
					// SETUP
					if($upload_to_openbenchmarking)
					{
						$test_run_manager->auto_upload_to_openbenchmarking();
					}

					$test_run_manager->auto_save_results($title, $id, 'Automated open-source benchmarks by the Phoronix Test Suite.', true);

					// BENCHMARK
					$test_run_manager->pre_execution_process();
					$test_run_manager->call_test_runs();
					$test_run_manager->post_execution_process();
				}
			}

			sleep(60);
		}
	}
}

?>
