<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2017, Paolo Valente <paolo.valente@linaro.org>
	Copyright (C) 2017 - 2021, Michael Larabel

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

// Possibilities:
// vm.dirty_ratio
// noatime / nobarrier
// transparent_hugepages

class pts_perf_tip_msg
{
	public $message;
	public $action;
	public $reference_url;
	public function __construct($msg, $act = null, $url = null)
	{
		$this->message = $msg;
		$this->action = $act;
		$this->reference_url = $url;
	}
	public function get_message()
	{
		return $this->message;
	}
	public function get_action()
	{
		return $this->action;
	}
	public function get_reference_url()
	{
		return $this->reference_url;
	}
}

class perf_tips extends pts_module_interface
{
	const module_name = 'Performance Tip Prompts';
	const module_version = '0.1.0';
	const module_description = 'This module alerts the user if the system configuration may not be the right one for achieving the best performance with the target benchmark(s). This initial version of the module actually cares only about the BFQ I/O scheduler and powersave governor checks.';
	const module_author = 'Paolo Valente <paolo.valente@linaro.org>';

	public static function module_info()
	{
		return 'This module alerts the user if the system configuration may not be the right one for achieving the best performance with the target benchmark(s). This initial version of the module actually cares only about the BFQ I/O scheduler: it gives a warning if BFQ is being used with an incorrect configuration in a disk benchmark, and suggests the right configuration to use. For the moment it only works for existing, throughput-based tests. It will need to be extended for responsiveness and soft real-time-latency tests.';
	}
	public static function module_environment_variables()
	{
		return array('SUPPRESS_PERF_TIPS');
	}
	public static function __run_manager_setup()
	{
		// Verify SUPPRESS_PERF_TIPS is not set and is Linux
		if(getenv('SUPPRESS_PERF_TIPS') == 1)
		{
			return pts_module::MODULE_UNLOAD; // This module doesn't have anything else to do
		}
	}
	public static function user_commands()
	{
		return array('show' => 'show_perf_tips');
	}
	public static function __pre_run_process($test_run_manager)
	{
		$test_hardware_types = array();
		foreach($test_run_manager->get_tests_to_run() as $test_run_request)
		{
			pts_arrays::unique_push($test_hardware_types, $test_run_request->test_profile->get_test_hardware_type());
		}
		self::show_perf_tips($test_hardware_types, $test_run_manager->is_interactive_mode());
	}
	public static function show_perf_tips($test_hardware_types = false, $interactive_mode = false)
	{
		$perf_tips = array();
		$show_all = false;
		if($test_hardware_types == false || !is_array($test_hardware_types))
		{
			$show_all = true;
			$test_hardware_types = array();
		}

		if($show_all || in_array('Disk', $test_hardware_types))
		{
			// BELOW ARE CHECKS TO MAKE IF WANTING TO SHOW FOR 'DISK' TESTS
			$disk_scheduler = phodevi::read_property('disk', 'scheduler');

			if($disk_scheduler == 'BFQ' || $disk_scheduler == 'BFQ-MQ' || $disk_scheduler == 'BFQ-SQ')
			{
				$mount_options = phodevi::read_property('disk', 'mount-options');
				$partition = basename($mount_options['device']);
				$device = pts_strings::keep_in_string($partition, pts_strings::CHAR_LETTER);
				$low_latency_file = '/sys/block/' . $device . '/queue/iosched/low_latency';
				$low_latency = pts_file_io::file_get_contents($low_latency_file);

				if($low_latency == 0)
					return;

				$perf_tips[] = new pts_perf_tip_msg('The BFQ I/O scheduler was detected and is being used in low-latency mode. In low-latency mode, BFQ sacrifices throughput when needed to guarantee either maximum responsiveness or low latency to isochronous I/O (the I/O of, e.g., video and audio players).', 'echo 0 > ' . $low_latency_file);
			}
		}
		if($show_all || in_array('System', $test_hardware_types) || in_array('Processor', $test_hardware_types))
		{
			// BELOW ARE CHECKS TO MAKE IF WANTING TO SHOW FOR 'Processor' OR 'System' TESTS
			$cpu_scaling_governor = phodevi::read_property('cpu', 'scaling-governor');

			// Linux: Check if scaling governor is available and if it is set to performance
			if(phodevi::is_linux() && $cpu_scaling_governor && stripos($cpu_scaling_governor, 'performance') === false)
			{
				$perf_tips[] = new pts_perf_tip_msg('The CPU scaling governor is currently not set to performance. It\'s possible to obtain greater performance if using the performance governor.', 'echo performance | tee /sys/devices/system/cpu/cpu*/cpufreq/scaling_governor', 'https://openbenchmarking.org/result/1706268-TR-CPUGOVERN32');
			}

			if(is_file('/sys/devices/system/cpu/cpufreq/boost'))
			{
				$cpufreq_boost = pts_file_io::file_get_contents('/sys/devices/system/cpu/cpufreq/boost');

				if($cpufreq_boost === '0')
				{
					$perf_tips[] = new pts_perf_tip_msg('CPUFreq Boost support is disabled on this system. Enabling boost should allow the CPU to achieve its rated boost frequencies.', 'echo 1 > /sys/devices/system/cpu/cpufreq/boost', '');
				}
			}
		}

		if(!empty($perf_tips))
		{
			foreach($perf_tips as &$tip)
			{
				pts_client::$display->display_interrupt_message($tip->get_message(), 'Performance Tip', 'green');
				if($tip->get_action() != null)
				{
					pts_client::$display->display_interrupt_message('To change behavior, run: ', null, 'gray');
					pts_client::$display->display_interrupt_message($tip->get_action(), null, 'gray');
				}
				if($tip->get_reference_url() != null)
				{
					pts_client::$display->display_interrupt_message('Reference: ' . $tip->get_reference_url(), null, 'red');
				}
				echo PHP_EOL;
			}

			if($interactive_mode)
			{
				pts_client::$display->display_interrupt_message('To stop showing performance tips, run: phoronix-test-suite unload-module perf_tips', null, 'gray');
				pts_client::$display->display_interrupt_message('Continuing in 5 seconds or press CTRL-C to stop the testing process.', null, 'green');
				sleep(5);
			}
		}
	}

}
?>
