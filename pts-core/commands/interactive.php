<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2011, Phoronix Media
	Copyright (C) 2011, Michael Larabel

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
		$is_moscow = pts_bypass::os_identifier_hash() == 'b28d6a7148b34595c5b397dfcf5b12ac7932b3dc';

		if($is_moscow)
		{
			// Auto mount?
			$drives = pts_file_io::glob('/dev/sd*');
			sort($drives);

			if(count($drives) > 0 && !is_dir('/media/pts-auto-mount') && is_writable('/media/'))
			{
				$last_drive = array_pop($drives);
				echo PHP_EOL . 'Attempting to auto-mount last drive: ' . $last_drive . PHP_EOL;
				mkdir('/media/pts-auto-mount');
				exec('mount ' . $last_drive . ' /media/pts-auto-mount');
			}

			// Auto save results
			$test_results_name = phodevi::read_property('motherboard', 'serial-number');

			if($test_results_name == null)
			{
				$test_results_name = phodevi::read_name('motherboard');
			}

			putenv('TEST_RESULTS_NAME="' . str_replace(' ', null, $test_results_name) . '"');
			putenv('TEST_RESULTS_IDENTIFIER="' . $test_results_name . '"');
			putenv('TEST_RESULTS_DESCRIPTION="Tests using ' . phodevi::read_property('system', 'operating-system') . ' on ' . date('d F Y') . ' of ' . $test_results_name . '."');
		}

		pts_openbenchmarking_client::refresh_repository_lists();
		pts_client::$display->generic_heading('Interactive Benchmarking');
		$reboot_on_exit = pts_bypass::is_live_cd() && pts_client::user_home_directory() == '/root/';

		do
		{
			$options = array(
				'RUN_TEST' => 'Run A Test',
				'RUN_SUITE' => 'Run A Suite [A Collection Of Tests]',
				'RUN_SYSTEM_TEST' => 'Run Complex System Test',
				'SHOW_INFO' => 'Show System Hardware / Software Information',
				'SHOW_SENSORS' => 'Show Auto-Detected System Sensors',
				'SET_RUN_COUNT' => 'Set Test Run Repetition'
				);

			if($is_moscow)
			{
				unset($options['RUN_SUITE']);
			}

			if(count(pts_client::saved_test_results()) > 0 && count(pts_file_io::glob('/media/*')) > 0)
			{
				$options['BACKUP_RESULTS_TO_USB'] = 'Backup Results To Media Storage';
			}

			$options['EXIT'] = ($reboot_on_exit ? 'Exit & Reboot' : 'Exit');
			$response = pts_user_io::prompt_text_menu('Select Task', $options, false, true);

			switch($response)
			{
				case 'RUN_TEST':
					$supported_tests = pts_openbenchmarking_client::available_tests();
					$supported_tests = pts_types::identifiers_to_test_profile_objects($supported_tests, false, true);
					$longest_title_length = 0;

					foreach($supported_tests as $i => &$test_profile)
					{
						if($test_profile->get_title() == null)
						{
							unset($supported_tests[$i]);
						}
						if(pts_bypass::is_live_cd() && $test_profile->get_test_hardware_type() == 'Disk' && count(pts_file_io::glob('/media/*')) == 0)
						{
							// Running in a Live RAM-based environment, but no disk mounted, so don't run disk tests
							unset($supported_tests[$i]);
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

					$tests_to_run = pts_user_io::prompt_text_menu('Select Test', $supported_tests, true, true);
					foreach(explode(',', $tests_to_run) as $test_to_run)
					{
						pts_test_installer::standard_install($test_to_run);
						pts_test_run_manager::standard_run($test_to_run);
					}
					break;
				case 'RUN_SUITE':
					$possible_suites = pts_openbenchmarking_client::available_suites();

					foreach(array_map('strtolower', pts_types::subsystem_targets()) as $subsystem)
					{
						array_push($possible_suites, 'pts/' . $subsystem);
					}

					$suites_to_run = pts_user_io::prompt_text_menu('Select Suite', $possible_suites, true);
					foreach(explode(',', $suites_to_run) as $suite_to_run)
					{
						pts_test_installer::standard_install($suite_to_run);
						pts_test_run_manager::standard_run($suite_to_run);
					}
					break;
				case 'RUN_SYSTEM_TEST':
					pts_client::$display->generic_heading('System Test');
					$system_tests = array('apache', 'c-ray', 'ramspeed', 'sqlite');
					pts_test_installer::standard_install($system_tests);
					pts_test_run_manager::standard_run($system_tests, pts_c::defaults_mode);
					break;
				case 'SHOW_INFO':
					pts_client::$display->generic_heading('System Software / Hardware Information');
					echo 'Hardware:' . PHP_EOL . phodevi::system_hardware(true) . PHP_EOL . PHP_EOL;
					echo 'Software:' . PHP_EOL . phodevi::system_software(true) . PHP_EOL . PHP_EOL;
					break;
				case 'SHOW_SENSORS':
					pts_client::$display->generic_heading('Detected System Sensors');
					foreach(phodevi::supported_sensors() as $sensor)
					{
						echo phodevi::sensor_name($sensor) . ': ' . phodevi::read_sensor($sensor) . ' ' . phodevi::read_sensor_unit($sensor) . PHP_EOL;
					}
					break;
				case 'SET_RUN_COUNT':
					$run_count = pts_user_io::prompt_user_input('Set the minimum number of times each test should repeat', false);
					putenv('FORCE_MIN_TIMES_TO_RUN=' . $run_count);
					break;
				case 'BACKUP_RESULTS_TO_USB':
					pts_client::$display->generic_heading('Backing Up Test Results');
					foreach(pts_file_io::glob('/media/*') as $media_dir)
					{
						if(!is_writable($media_dir))
						{
							echo PHP_EOL . $media_dir . ' is not writable.' . PHP_EOL;
							continue;
						}

						echo PHP_EOL . 'Writing Test Results To: ' . $media_dir . PHP_EOL;
						pts_file_io::copy(PTS_SAVE_RESULTS_PATH, $media_dir . '/');
					}
					break;
			}
			echo PHP_EOL . PHP_EOL;
		}
		while($response != 'EXIT');

		if($reboot_on_exit)
		{
			exec('reboot');
		}
	}
}

?>
