<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2020, Phoronix Media
	Copyright (C) 2020, Michael Larabel

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

class test_timeout extends pts_module_interface
{
	const module_name = 'Test Timeout';
	const module_version = '1.0.-';
	const module_description = 'This module allows killing a test if it exceeds a defined threshold, such as if the test is hung, etc. TEST_TIMEOUT_AFTER= environment variable can be used for controlling the behavior. When this variable is set, the value will can be set to "auto" or a positive integer. The value indicates the number of minutes until a test run should be aborted, such as for a safeguard against hung/deadlocked processes or other issues. Setting this to a high number as a backup would be recommended for fending off possible hangs / stalls in the testing process if the test does not quit on its own for whatever reason. If the value is "auto", it will quit if the time of a test run exceeds 3x the average time it normally takes the particular test to complete its run.';
	const module_author = 'Michael Larabel';

	protected static $timeout_after_mins = 'auto';
	protected static $current_test_estimated_run_time;
	public static function module_environmental_variables()
	{
		return array('TEST_TIMEOUT_AFTER');
	}
	public static function __startup()
	{
		if(!function_exists('pcntl_fork') || !phodevi::is_linux())
		{
			return pts_module::MODULE_UNLOAD;
		}
		// Make sure the file is removed to avoid potential problems if it was leftover from earlier run
		pts_module::save_file('test_timeout', '');

		if(($timeout = pts_module::read_variable('TEST_TIMEOUT_AFTER')))
		{
			if((is_numeric($timeout) && $timeout > 0) || $timeout == 'auto')
			{
				self::$timeout_after_mins = $timeout;
			}
		}
	}
	public static function __pre_run_process(&$test_run_manager)
	{
		pts_module::save_file('test_timeout', '');
		pts_module::pts_timed_function('check_for_processes_to_kill', 60);
	}
	public static function __pre_test_run(&$test_run_request)
	{
		self::$current_test_estimated_run_time = $test_run_request->test_profile->get_estimated_run_time();
	}
	public static function check_for_processes_to_kill()
	{
		if(!pts_module::is_file('test_timeout'))
		{
			pts_module_manager::$stop_manager = true;
			exit(0);
		}
		$process_file = pts_strings::trim_explode(' ', pts_module::read_file('test_timeout'));

		if(isset($process_file[1]) && is_numeric($process_file[1]) && time() >= $process_file[1])
		{
			echo PHP_EOL . pts_client::cli_colored_text('        Killing test due to test timer elapsed (possible hung process or other abnormality).', 'red', true);
			pts_client::kill_process_with_children_processes($process_file[0]);
			file_put_contents(PTS_USER_PATH . 'skip-test', '');
		}
		
	}
	public static function __post_run_process()
	{
		pts_module::remove_file('test_timeout');
	}
	public static function __shutdown()
	{
		pts_module::remove_file('test_timeout');
	}
	public static function __test_running(&$test_process)
	{
		$p = proc_get_status($test_process);
		if(!isset($p['pid']))
		{
			return;
		}
		$pid = $p['pid'];
		if(self::$timeout_after_mins == 'auto')
		{
			if(self::$current_test_estimated_run_time > 0)
			{
				$time_to_allow = self::$current_test_estimated_run_time * 3;
			}
			else
			{
				pts_module::save_file('test_timeout', '');
				return;
			}
		}
		else if(is_numeric(self::$timeout_after_mins))
		{
			$time_to_allow = (60 * self::$timeout_after_mins);
		}
		else
		{
			pts_module::save_file('test_timeout', '');
			return;
		}
		
		pts_module::save_file('test_timeout', $pid . ' ' . (time() + $time_to_allow));
	}
}

?>
