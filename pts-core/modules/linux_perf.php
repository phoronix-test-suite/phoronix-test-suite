<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2015 - 2020, Phoronix Media
	Copyright (C) 2015 - 2020, Michael Larabel

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

class linux_perf extends pts_module_interface
{
	const module_name = 'Linux Perf Framework Reporter';
	const module_version = '1.1.0';
	const module_description = 'Setting LINUX_PERF=1 will auto-load and enable this Phoronix Test Suite module. The module also depends upon running a modern Linux kernel (supporting perf) and that the perf binary is available via standard system paths. Depending upon system permissions you may be limited to using perf as root or adjusting the /proc/sys/kernel/perf_event_paranoid setting.';
	const module_author = 'Michael Larabel';

	private static $result_identifier;
	private static $successful_test_run;
	private static $std_output;
	private static $tmp_file;
    
	public static function module_environment_variables()
	{
		return array('LINUX_PERF');
	}
	public static function module_info()
	{
		return null;
	}
	public static function __run_manager_setup(&$test_run_manager)
	{
		// Verify LINUX_PERF is set, `perf` can be found, and is Linux
		if(pts_env::read('LINUX_PERF') == 0 || !pts_client::executable_in_path('perf') || !phodevi::is_linux())
		{
			return pts_module::MODULE_UNLOAD; // This module doesn't have anything else to do
		}
		if(phodevi::is_root() == false && is_file('/proc/sys/kernel/perf_event_paranoid'))
		{
			$perf_event_paranoid = pts_file_io::file_get_contents('/proc/sys/kernel/perf_event_paranoid');
			if(is_numeric($perf_event_paranoid) && $perf_event_paranoid >= 2)
			{
				echo PHP_EOL . 'ERROR: /proc/sys/kernel/perf_event_paranoid needs to be adjusted for Linux perf support.' . PHP_EOL;
				return pts_module::MODULE_UNLOAD;
			}
		}
		echo PHP_EOL . 'Linux PERF Monitoring Enabled.' . PHP_EOL . PHP_EOL;

		// This module won't be too useful if you're not saving the results to see the graphs
		$test_run_manager->force_results_save();
	}
	public static function __pre_run_process(&$test_run_manager)
	{
		// Copy the current result identifier
		self::$result_identifier = $test_run_manager->get_results_identifier();
	}
	public static function __pre_test_run(&$test_run_request)
	{
		// Set the perf command to pass in front of all tests to run
		self::$tmp_file = tempnam(sys_get_temp_dir(), 'perf');
		// -d or below is more exhaustive list
		$test_run_request->exec_binary_prepend = 'perf stat -e branches,branch-misses,cache-misses,cache-references,cycles,instructions,cs,cpu-clock,page-faults,duration_time,task-clock,L1-dcache-load-misses,L1-dcache-loads,L1-dcache-prefetches,L1-icache-load-misses,context-switches,cpu-migrations,branch-loads,branch-load-misses,dTLB-loads,dTLB-load-misses,iTLB-load-misses,iTLB-loads -o ' . self::$tmp_file . ' ';
	}
	public static function __post_test_run_success($test_run_request)
	{
		// Base the new result object/graph off of what just ran
		self::$successful_test_run = clone $test_run_request;

		// For now the current implementation is just copying the perf output for the last test run, but rather easily could be adapted to take average of all test runs, etc
		self::$std_output = file_get_contents(self::$tmp_file);
		pts_file_io::unlink(self::$tmp_file);
	}
	public static function __post_test_run_process(&$result_file)
	{
		if(self::$successful_test_run && !empty(self::$std_output))
		{
			if(($x = strpos(self::$std_output, 'Performance counter stats for')) === 0)
			{
				// No output found
				return;
			}
			self::$std_output = substr(self::$std_output, $x);

			// Items to find and report from the perf output
			$perf_stats = array(
				'page-faults' => array('Page Faults', 'Faults', 'LIB'),
				'context-switches' => array('Context Switches', 'Context Switches', 'LIB'),
				'cpu-migrations' => array('CPU Migrations', 'CPU Migrations', 'LIB'),
				'branches' => array('Branches', 'Branches', ''),
				'branch-misses' => array('Branch Misses', 'Branch Misses', 'LIB'),
				'seconds user' => array('User Time', 'Seconds', 'LIB'),
				'seconds sys' => array('Kernel/System Time', 'Seconds', 'LIB'),
				'stalled-cycles-frontend' => array('Stalled Cycles Front-End', 'Cycles Idle', 'LIB'),
				'stalled-cycles-backend' => array('Stalled Cycles Back-End', 'Cycles Idle', 'LIB'),
				'L1-dcache-loads' => array('L1d Loads', 'L1d Cache Loads', ''),
				'L1-icache-loads' => array('L1i Loads', 'L1i Cache Loads', ''),
				'L1-dcache-load-misses' => array('L1d Load Misses', 'L1 Data Cache Load Misses', 'LIB'),
				'L1-icache-load-misses' => array('L1i Load Misses', 'L1 Instruction Cache Load Misses', 'LIB'),
				'cache-misses' => array('Cache Misses', 'Cache Misses', 'LIB'),
				'branch-load-misses' => array('Branch Load Misses', 'Branch Load Misses', 'LIB'),
				'dTLB-load-misses' => array('dTLB Load Misses', 'dTLB Load Misses', 'LIB'),
				'ex_ret_mmx_fp_instr.sse_instr' => array('SSE Instructions', 'SSE Instructions', ''),
				'fp_ret_sse_avx_ops.all' => array('SSE+AVX Instructions', 'AVX Instructions', ''),
				'instructions' => array('Instructions', 'Instructions', 'LIB'),
				);

			foreach($perf_stats as $string_to_match => $data)
			{
				list($pretty_string, $units, $hib_or_lib) = $data;
				if(($x = strpos(self::$std_output, $string_to_match)) !== false)
				{
					$sout = substr(self::$std_output, 0, $x);
					$sout = str_replace(',', '', trim(substr($sout, (strrpos($sout, PHP_EOL) + 1))));

					if(is_numeric($sout) && $sout > 0)
					{
						// Assemble the new result object for the matching perf item
						$original_parent_hash = self::$successful_test_run->get_comparison_hash(true, false);
						$test_result = clone self::$successful_test_run;
						$test_result->test_profile->set_identifier(null);
						$test_result->set_parent_hash($original_parent_hash);

						// Description to show on graph
						$test_result->set_used_arguments_description($pretty_string . ' (' . $test_result->get_arguments_description() . ')');

						// Make a unique string for XML result matching
						$test_result->set_used_arguments('perf ' . $string_to_match . ' ' . $test_result->get_arguments());
						$test_result->test_profile->set_result_scale($units);
						$test_result->test_profile->set_result_proportion($hib_or_lib);
						$test_result->test_result_buffer = new pts_test_result_buffer();
						$test_result->test_result_buffer->add_test_result(self::$result_identifier, $sout);
						$test_result->set_parent_hash(self::$successful_test_run->get_comparison_hash(true, false));
						$result_file->add_result($test_result);
					}
				}
			}
		}

		// Reset to be safe
		self::$successful_test_run = null;
		self::$std_output = null;
	}
}
?>
