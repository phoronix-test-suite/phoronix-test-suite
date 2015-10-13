<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2015, Phoronix Media
	Copyright (C) 2015, Michael Larabel

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
	const module_version = '0.1.0';
	const module_description = 'Setting LINUX_PERF=1 will auto-load and enable this Phoronix Test Suite module. The module also depends upon running a modern Linux kernel (supporting perf) and that the perf binary is available via standard system paths.';
	const module_author = 'Michael Larabel';

	private static $result_identifier;
	private static $successful_test_run;
	private static $std_output;

	public static function module_environmental_variables()
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
		if(getenv('LINUX_PERF') == 0 || !pts_client::executable_in_path('perf') || !phodevi::is_linux())
		{
			return pts_module::MODULE_UNLOAD; // This module doesn't have anything else to do
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
		$test_run_request->exec_binary_prepend = 'perf stat ';
	}
	public static function __post_test_run_success($test_run_request)
	{
		// Base the new result object/graph off of what just ran
		self::$successful_test_run = clone $test_run_request;

		// For now the current implementation is just copying the perf output for the last test run, but rather easily could be adapted to take average of all test runs, etc
		self::$std_output = $test_run_request->test_result_standard_output;
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
				'task-clock' => 'Task Clock',
				'context-switches' => 'Context Switches',
				'page-faults' => 'Page Faults',
				'branches' => 'Branches',
				'branch-misses' => 'Branch Misses'
				);

			foreach($perf_stats as $string_to_match => $pretty_string)
			{
				if(($x = strpos(self::$std_output, $string_to_match)) !== false)
				{
					$sout = substr(self::$std_output, 0, $x);
					$sout = trim(substr($sout, (strrpos($sout, PHP_EOL) + 1)));

					if(is_numeric($sout))
					{
						// Assemble the new result object for the matching perf item
						$test_result = clone self::$successful_test_run;
						$test_result->test_profile->set_identifier(null);

						// Description to show on graph
						$test_result->set_used_arguments_description('Perf ' . $pretty_string . ' - ' . $test_result->get_arguments_description());

						// Make a unique string for XML result matching
						$test_result->set_used_arguments('perf ' . $string_to_match . ' ' . $test_result->get_arguments());
						$test_result->test_profile->set_result_scale(' ');
						//$test_result->test_profile->set_result_proportion(' ');
						$test_result->test_result_buffer = new pts_test_result_buffer();
						$test_result->test_result_buffer->add_test_result(self::$result_identifier, $sout);
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
