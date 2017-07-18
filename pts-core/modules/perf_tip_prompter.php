<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2017, Paolo Valente <paolo.valente@linaro.org>

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

class perf_tip_prompter extends pts_module_interface
{
	const module_name = 'Performance Tip Prompter';
	const module_version = '0.1.0';
	const module_description = 'This module alerts the user if the system configuration may not be the right one for achieving the best performance with the target benchmark(s). This initial version of the module actually cares only about the BFQ I/O scheduler: it gives a warning if BFQ is being used with an incorrect configuration in a disk benchmark, and suggests the right configuration to use. For the moment it only works for existing, throughput-based tests. It will need to be extended for responsiveness and soft real-time-latency tests.';
	const module_author = 'Paolo Valente <paolo.valente@linaro.org>';

	public static function module_info()
	{
		return 'This module alerts the user if the system configuration may not be the right one for achieving the best performance with the target benchmark(s). This initial version of the module actually cares only about the BFQ I/O scheduler: it gives a warning if BFQ is being used with an incorrect configuration in a disk benchmark, and suggests the right configuration to use. For the moment it only works for existing, throughput-based tests. It will need to be extended for responsiveness and soft real-time-latency tests.';
	}
	public static function module_environmental_variables()
	{
		return array('SUPPRESS_PERF_TIPS');
	}
	public static function __run_manager_setup()
	{
		// Verify SUPPRESS_PERF_TIPS is not set and is Linux
		if(getenv('SUPPRESS_PERF_TIPS') == 1 || !phodevi::is_linux())
		{
			return pts_module::MODULE_UNLOAD; // This module doesn't have anything else to do
		}
	}
	public static function __pre_test_run(&$test_run_request)
	{
		$hardware = $test_run_request->test_profile->get_test_hardware_type();
		$disk_scheduler = phodevi::read_property('disk', 'scheduler');

		if($hardware == 'Disk' && ($disk_scheduler == 'BFQ' || $disk_scheduler == 'BFQ-MQ' || $disk_scheduler == 'BFQ-SQ'))
		{
			$mount_options = phodevi::read_property('disk', 'mount-options');
			$partition = basename($mount_options['device']);
			$device = pts_strings::keep_in_string($partition, pts_strings::CHAR_LETTER);
			$low_latency_file = '/sys/block/' . $device . '/queue/iosched/low_latency';
			$low_latency = shell_exec('cat ' . $low_latency_file);

			if ($low_latency == 0)
				return;

			echo PHP_EOL . "\t\t\t\tWARNING" . PHP_EOL;
			echo PHP_EOL . 'This is not a disk benchmark to measure responsiveness or latency for' . PHP_EOL;
			echo 'soft real-time applications, but BFQ is being used in low-latency mode!' . PHP_EOL;
			echo PHP_EOL . 'In low-latency mode, BFQ sacrifices throughput when needed to guarantee' . PHP_EOL;
			echo 'either maximum responsiveness or low latency to isochronous I/O (the I/O' . PHP_EOL;
			echo 'of, e.g., video and audio players).' . PHP_EOL;
			echo PHP_EOL . 'For this benchmark, please execute' . PHP_EOL;
			echo 'echo 0 > ' . $low_latency_file . PHP_EOL;
			echo '(after every switch to BFQ), or set SUPPRESS_PERF_TIPS to suppress this' . PHP_EOL;
			echo 'WARNING.' . PHP_EOL;
			echo PHP_EOL . 'Press any key to continue or CTRL-C to stop the test.';
			pts_user_io::read_user_input();
		}
	}

}
?>
