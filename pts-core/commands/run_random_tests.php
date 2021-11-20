<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2014 - 2020, Phoronix Media
	Copyright (C) 2014 - 2020, Michael Larabel

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
		$allow_new_dependencies_to_be_installed = $allow_new_tests_to_be_installed ? pts_user_io::prompt_bool_input('Allow new test external dependencies to be installed', false) : false;
		$limit_test_subsystem = pts_user_io::prompt_bool_input('Limit tests to a given subsystem', false);
		$limit_test_subsystem = $limit_test_subsystem ? pts_user_io::prompt_text_menu('Select subsystem(s) to test', pts_types::subsystem_targets(), true) : array();
		$upload_to_openbenchmarking = pts_user_io::prompt_bool_input('Auto-upload test results to OpenBenchmarking.org', true);

		while(1)
		{
			$to_test = array();

			if($limit_test_subsystem)
			{
				foreach($limit_test_subsystem as $test_type)
				{
					$tests = pts_openbenchmarking_client::popular_tests(-1, $test_type);
					$to_test = array_merge($to_test, $tests);
				}

				if(empty($to_test))
				{
					pts_client::$display->generic_sub_heading('No tests could be found to run.');
					return false;
				}
				shuffle($to_test);
				$to_test = array_slice($to_test, 0, rand(1, 12));
			}
			else if(rand(1, 6) == 2)
			{
				$ob_ids = pts_openbenchmarking_client::popular_openbenchmarking_results();
				$ob_type = rand(0, 1) == 1 ? 'recent_popular_results' : 'recent_results';

				if(isset($ob_ids[$ob_type]) && !empty($ob_ids[$ob_type]))
				{
					shuffle($ob_ids[$ob_type]);
					$to_test = array(array_pop($ob_ids[$ob_type]));
				}
			}

			if(empty($to_test))
			{
				// Randomly pick some installed tests
				$installed_tests = pts_tests::installed_tests();

				if($installed_tests > 3)
				{
					shuffle($installed_tests);
					$to_test = array_slice($installed_tests, 0, rand(1, 8));
				}

				if(!isset($to_test[2]) && $allow_new_tests_to_be_installed)
				{
					$available_tests = pts_openbenchmarking::available_tests();
					shuffle($available_tests);
					$to_test = array_merge($to_test, array_slice($available_tests, 0, rand(1, 10)));
				}
			}

			if(empty($to_test))
			{
				pts_client::$display->generic_sub_heading('No tests could be found to run.');
				return false;
			}

			echo PHP_EOL;
			pts_client::$display->generic_sub_heading('Tests To Run: ' . implode(', ', $to_test));

			// QUERY FROM OB
			$random_titles = array(
				phodevi::read_property('cpu', 'model') . ' Benchmarks',
				phodevi::read_property('system', 'operating-system') . ' Benchmarks',
				phodevi::read_property('system', 'operating-system') . ' Performance',
				phodevi::read_property('cpu', 'model') . ' Performance',
				phodevi::read_property('cpu', 'model') . ' + ' . phodevi::read_property('gpu', 'model') . ' + ' . phodevi::read_property('motherboard', 'identifier'),
				phodevi::read_property('motherboard', 'identifier') . ' On ' . phodevi::read_property('system', 'operating-system'),
				phodevi::read_property('cpu', 'model') . ' On ' . phodevi::read_property('system', 'operating-system'),
				phodevi::read_property('system', 'kernel') . ' + ' . phodevi::read_property('system', 'operating-system') . ' Tests');
			shuffle($random_titles);
			$title = array_pop($random_titles);

			if($limit_test_subsystem)
			{
				$subsystems_to_test = $limit_test_subsystem;
				$subsystems_to_avoid = array_diff(pts_types::subsystem_targets(), $subsystems_to_test);
				pts_env::set('SKIP_TESTING_SUBSYSTEMS', implode(',', $subsystems_to_avoid));
			}

			if($allow_new_tests_to_be_installed)
			{
				pts_test_installer::standard_install($to_test, false, true, $allow_new_dependencies_to_be_installed);
			}

			$batch_mode_settings = array(
				'UploadResults' => false,
				'SaveResults' => true,
				'PromptForTestDescription' => false,
				'RunAllTestCombinations' => false,
				'PromptSaveName' => false,
				'PromptForTestIdentifier' => false,
				'OpenBrowser' => false
				);

			if($upload_to_openbenchmarking)
			{
				$batch_mode_settings['UploadResults'] = true;
				pts_openbenchmarking_client::override_client_setting('UploadSystemLogsByDefault', true);
			}

			$test_run_manager = new pts_test_run_manager($batch_mode_settings, 2);
			$test_run_manager->set_batch_mode($batch_mode_settings);
			if($test_run_manager->initial_checks($to_test) != false)
			{
				if($test_run_manager->load_tests_to_run($to_test))
				{
					// SETUP
					$test_run_manager->auto_save_results($title, null, 'Various open-source benchmarks by the ' . pts_core::program_title() . '.', true);
					$test_run_manager->auto_generate_results_identifier();
					echo PHP_EOL;
					pts_client::$display->generic_sub_heading(pts_client::cli_just_bold('Result File: ') . $test_run_manager->get_file_name());
					pts_client::$display->generic_sub_heading(pts_client::cli_just_bold('Result Identifier: ') . $test_run_manager->get_results_identifier());

					// BENCHMARK
					$test_run_manager->pre_execution_process();
					$test_run_manager->call_test_runs();
					$test_run_manager->post_execution_process();
					pts_results::remove_saved_result_file($test_run_manager->get_file_name());
				}
			}

			echo PHP_EOL;
			sleep(30);
		}
	}
}

?>
