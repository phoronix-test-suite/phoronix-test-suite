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
		pts_client::$display->generic_heading('Interactive Benchmarking');
		// bd1022e368accf93823d9d3716fa5da3 is the K Moscow client
		$reboot_on_exit = phodevi::read_property('system', 'vendor-identifier') == 'bd1022e368accf93823d9d3716fa5da3' && pts_client::user_home_directory() == '/root/';

		do
		{
			$options = array(
				'RUN_TEST' => 'Run A Test',
				'RUN_SUITE' => 'Run A Suite [A Collection Of Tests]',
				'SHOW_INFO' => 'Show System Hardware / Software Information',
				'SHOW_SENSORS' => 'Show Auto-Detected System Sensors',
				);

			if(count(pts_client::saved_test_results()) > 0 && count(pts_file_io::glob('/media/*')) > 0)
			{
				$options['BACKUP_RESULTS_TO_USB'] = 'Backup Results To Media Storage';
			}

			$options['EXIT'] = ($reboot_on_exit ? 'Exit & Reboot' : 'Exit');
			$response = pts_user_io::prompt_text_menu('Select Task', $options, false, true);

			switch($response)
			{
				case 'RUN_TEST':
					$possible_tests = pts_openbenchmarking_client::available_tests();
					$tests_to_run = pts_user_io::prompt_text_menu('Select Test', $possible_tests, true);
					foreach(explode(',', $tests_to_run) as $test_to_run)
					{
						pts_test_installer::standard_install($test_to_run);
						pts_test_run_manager::standard_run($test_to_run);
					}
					break;
				case 'RUN_SUITE':
					$possible_suites = pts_openbenchmarking_client::available_suites();
					$suites_to_run = pts_user_io::prompt_text_menu('Select Suite', $possible_suites, true);
					foreach(explode(',', $suites_to_run) as $suite_to_run)
					{
						pts_test_installer::standard_install($suite_to_run);
						pts_test_run_manager::standard_run($suite_to_run);
					}
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
