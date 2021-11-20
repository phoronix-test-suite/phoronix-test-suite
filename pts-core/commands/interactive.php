<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2011 - 2020, Phoronix Media
	Copyright (C) 2011 - 2020, Michael Larabel

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

class interactive implements pts_option_interface
{
	const doc_section = 'System';
	const doc_description = 'A simple text-driven interactive interface to the Phoronix Test Suite.';

	public static function run($r)
	{
		pts_openbenchmarking::refresh_repository_lists();
		pts_client::$display->generic_heading('Interactive Benchmarking');
		echo phodevi::system_centralized_view();
		echo PHP_EOL . (phodevi::read_property('motherboard', 'serial-number') != null ? PHP_EOL . 'System Serial Number: ' . phodevi::read_property('motherboard', 'serial-number') : null) . PHP_EOL . PHP_EOL;
		$reboot_on_exit = false;

		do
		{
			$only_show_available_cached_tests = pts_network::internet_support_available() == false;
			$options = array(
				'RUN_TEST' => 'Run A Test / Benchmark',
				'RUN_SUITE' => 'Run A Suite      [A Collection Of Tests]',
				'RUN_STRESS_TEST' => 'Run A Stress Test      [Concurrent Benchmarks For Heavy System Load]',
				'SHOW_INFO' => 'Show System Hardware / Software Information',
				'SHOW_SENSORS' => 'Show Available System Sensors',
				'LIST_TESTS' => 'List Available Tests',
				'LIST_RECOMMENDED_TESTS' => 'List Recommended Tests',
			//	'SET_RUN_COUNT' => 'Set Test Run Repetition',
				'SEARCH' => 'Search Tests / Suites / Results'
				);

			if(pts_results::saved_test_results_count() > 0)
			{
				$options['BACKUP_RESULTS_TO_USB'] = 'Backup Results To Media Storage';
			}

			$options['EXIT'] = ($reboot_on_exit ? 'Exit & Reboot' : 'Exit');
			$response = pts_user_io::prompt_text_menu('Select Task', $options, false, true);

			switch($response)
			{
				case 'RUN_TEST':
					$supported_tests = pts_openbenchmarking::available_tests(!$only_show_available_cached_tests, false, false, false, $only_show_available_cached_tests);
					$supported_tests = pts_types::identifiers_to_test_profile_objects($supported_tests, false, true);
					$longest_title_length = 0;

					foreach($supported_tests as $i => &$test_profile)
					{
						if($test_profile->get_title() == null || $test_profile->get_license() == 'Retail')
						{
							unset($supported_tests[$i]);
							continue;
						}
						if(!pts_test_run_manager::test_profile_system_compatibility_check($test_profile))
						{
							unset($supported_tests[$i]);
							continue;
						}

						$longest_title_length = max($longest_title_length, strlen($test_profile->get_title()));
					}

					$t = array();
					foreach($supported_tests as $i => &$test_profile)
					{
						if($test_profile instanceof pts_test_profile)
						{
							$t[$test_profile->get_identifier()] = sprintf('%-' . ($longest_title_length + 1) . 'ls - %-10ls', $test_profile->get_title(), $test_profile->get_test_hardware_type());
						}
					}
					$supported_tests = $t;
					asort($supported_tests);

					$tests_to_run = pts_user_io::prompt_text_menu('Select Test(s)', $supported_tests, true, true);
					pts_test_installer::standard_install($tests_to_run);
					$run_manager = new pts_test_run_manager(false, 2);
					$run_manager->standard_run($tests_to_run);
					if($run_manager != false)
					{
						pts_client::display_result_view($run_manager->result_file, false);
					}
					break;
				case 'RUN_STRESS_TEST':
					$supported_tests = pts_openbenchmarking::available_tests(!$only_show_available_cached_tests, false, false, false, $only_show_available_cached_tests);
					$supported_tests = pts_types::identifiers_to_test_profile_objects($supported_tests, false, true);
					$longest_title_length = 0;

					foreach($supported_tests as $i => &$test_profile)
					{
						if($test_profile->get_title() == null || $test_profile->get_license() == 'Retail')
						{
							unset($supported_tests[$i]);
							continue;
						}
						if(!pts_test_run_manager::test_profile_system_compatibility_check($test_profile))
						{
							unset($supported_tests[$i]);
							continue;
						}

						$longest_title_length = max($longest_title_length, strlen($test_profile->get_title()));
					}

					$t = array();
					foreach($supported_tests as $i => &$test_profile)
					{
						if($test_profile instanceof pts_test_profile)
						{
							$t[$test_profile->get_identifier()] = sprintf('%-' . ($longest_title_length + 1) . 'ls - %-10ls', $test_profile->get_title(), $test_profile->get_test_hardware_type());
						}
					}
					$supported_tests = $t;
					asort($supported_tests);

					$tests_to_run = pts_user_io::prompt_text_menu('Select Test(s)', $supported_tests, true, true);
					$concurrent_runs = pts_user_io::prompt_user_input('Number of tests to run concurrently');
					pts_env::set('PTS_CONCURRENT_TEST_RUNS', trim($concurrent_runs));
					$minutes_loop_time = pts_user_io::prompt_user_input('Number of minutes to stress run');
					pts_env::set('TOTAL_LOOP_TIME', trim($minutes_loop_time));

					pts_test_installer::standard_install($tests_to_run);
					pts_client::execute_command('stress_run', $tests_to_run);
					break;
				case 'RUN_SUITE':
					$possible_suites = pts_openbenchmarking::available_suites();

					foreach(array_map('strtolower', pts_types::subsystem_targets()) as $subsystem)
					{
						$possible_suites[] = 'pts/' . $subsystem;
					}

					$suites_to_run = pts_user_io::prompt_text_menu('Select Suite', $possible_suites, true);
					foreach($suites_to_run as $suite_to_run)
					{
						pts_test_installer::standard_install($suite_to_run);
						$run_manager = new pts_test_run_manager(false, 2);
						$run_manager->standard_run($suite_to_run);
					}
					break;
				case 'SELECT_DRIVE_MOUNT':
					self::select_drive_mount();
					break;
				case 'SEARCH':
					pts_client::execute_command('search');
					break;
				case 'SHOW_INFO':
					pts_client::execute_command('system_info');
					break;
				case 'SHOW_SENSORS':
					pts_client::execute_command('system_sensors');
					break;
				case 'LIST_TESTS':
					pts_client::execute_command('list_available_tests');
					break;
				case 'LIST_RECOMMENDED_TESTS':
					pts_client::execute_command('list_recommended_tests');
					break;
				case 'SET_RUN_COUNT':
					$run_count = pts_user_io::prompt_user_input('Set the minimum number of times each test should repeat', false);
					pts_env::set('FORCE_TIMES_TO_RUN', trim($run_count));
					break;
				case 'BACKUP_RESULTS_TO_USB':
					pts_client::$display->generic_heading('Backing Up Test Results');
					$writable_backup_locations = array();
					foreach(array_merge(pts_file_io::glob('/media/*'), pts_file_io::glob('/run/media/*/*')) as $media_dir)
					{
						if(is_writable($media_dir))
						{
							$writable_backup_locations[] = $media_dir;
						}
					}

					$backup_location = pts_user_io::prompt_text_menu('Select Backup Location', $writable_backup_locations);
					$backup_location .= '/phoronix-test-suite-test-results/';
					pts_file_io::mkdir($backup_location);
					echo PHP_EOL . pts_client::cli_just_bold('Writing Test Results To: ') . $backup_location . PHP_EOL;
					pts_file_io::copy(PTS_SAVE_RESULTS_PATH, $backup_location . '/');
					break;
			}
			echo PHP_EOL . PHP_EOL;
		}
		while($response != 'EXIT');

		if($reboot_on_exit)
		{
			if(is_dir('/media/pts-auto-mount'))
			{
				pts_file_io::delete('/media/pts-auto-mount/pts', null, true);
				exec('umount /media/pts-auto-mount 2>&1');
			}

			phodevi::reboot();
		}
	}
	private static function select_drive_mount()
	{
		$drives = pts_file_io::glob('/dev/sd*');

		if(count($drives) == 0)
		{
			echo PHP_EOL . 'No Disk Drives Found' . PHP_EOL . PHP_EOL;
		}
		else
		{
			$drives[] = 'No HDD';
			$to_mount = pts_user_io::prompt_text_menu('Select Drive / Partition To Mount', $drives);

			if($to_mount != 'No HDD')
			{
				echo PHP_EOL . 'Attempting to mount: ' . $to_mount . PHP_EOL;
				exec('umount /media/pts-auto-mount 2>&1');
				pts_file_io::delete('/media/pts-auto-mount', null, true);
				pts_file_io::mkdir('/media/pts-auto-mount');
				echo exec('mount ' . $to_mount . ' /media/pts-auto-mount');
				pts_env::set('PTS_TEST_INSTALL_ROOT_PATH', '/media/pts-auto-mount/');
			}
			else
			{
				if(is_dir('/media/pts-auto-mount'))
				{
					exec('umount /media/pts-auto-mount');
					@rmdir('/media/pts-auto-mount');
				}

				pts_env::remove('PTS_TEST_INSTALL_ROOT_PATH');
			}
		}
	}
}

?>
