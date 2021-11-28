<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2017, Phoronix Media
	Copyright (C) 2017, Michael Larabel

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

class report_test_run_times extends pts_module_interface
{
	const module_name = 'Report Test Time Graphs';
	const module_version = '1.1.0';
	const module_description = 'Setting the RUN_TIMES_ARE_A_BENCHMARK=1 environment variable will automatically create additional graphs for each test run plotting the run-time needed for each test being executed. Setting the INSTALL_TIMES_ARE_A_BENCHMARK=1 environment variable will automatically create additional graphs for each test run plotting the time required for the test installation. Setting the INSTALL_SIZES_ARE_A_BENCHMARK=1 environment variable will automatically create additional graphs for each test run plotting the size of the installed test directory.';
	const module_author = 'Michael Larabel';

	private static $successful_test_run_request = null;
	private static $result_identifier;

	public static function module_environment_variables()
	{
		return array('RUN_TIMES_ARE_A_BENCHMARK', 'INSTALL_TIMES_ARE_A_BENCHMARK', 'INSTALL_SIZES_ARE_A_BENCHMARK');
	}
	public static function module_info()
	{
		return null;
	}
	public static function __run_manager_setup(&$test_run_manager)
	{
		$is_being_used = false;

		if(getenv('RUN_TIMES_ARE_A_BENCHMARK') != false)
		{
			echo PHP_EOL . 'The Phoronix Test Suite will generate graphs of test run-times.' . PHP_EOL;
			$is_being_used = true;
		}
		if(getenv('INSTALL_TIMES_ARE_A_BENCHMARK') != false)
		{
			echo PHP_EOL . 'The Phoronix Test Suite will generate graphs of test install times.' . PHP_EOL;
			$is_being_used = true;
		}
		if(getenv('INSTALL_SIZES_ARE_A_BENCHMARK') != false)
		{
			echo PHP_EOL . 'The Phoronix Test Suite will generate graphs of test installation directory sizes.' . PHP_EOL;
			$is_being_used = true;
		}

		if(!$is_being_used)
		{
			return pts_module::MODULE_UNLOAD; // This module doesn't have anything else to do
		}

		$test_run_manager->force_results_save();
	}
	public static function __pre_run_process(&$test_run_manager)
	{
		self::$result_identifier = $test_run_manager->get_results_identifier();
	}
	public static function __post_test_run($test_run_request)
	{
		self::$successful_test_run_request = clone $test_run_request;
	}
	public static function __post_test_run_process(&$result_file)
	{
		if(getenv('RUN_TIMES_ARE_A_BENCHMARK') && self::$successful_test_run_request && !empty(self::$successful_test_run_request->test_run_times))
		{
			$result = round(pts_math::arithmetic_mean(self::$successful_test_run_request->test_run_times), 2);
			if($result > 0)
			{
				// This copy isn't needed but it's shorter and from port from system_monitor where there can be multiple items tracked
				$test_result = clone self::$successful_test_run_request;
				$test_result->test_profile->set_identifier(null);
				$test_result->set_used_arguments_description('Test Run Time' . ($test_result->get_arguments_description() != null ? ' - ' . $test_result->get_arguments_description() : null ));
				$test_result->set_used_arguments('test run time ' . $test_result->get_arguments());
				$test_result->test_profile->set_result_scale('Seconds');
				$test_result->test_profile->set_result_proportion('LIB');
				$test_result->test_result_buffer = new pts_test_result_buffer();
				$test_result->test_result_buffer->add_test_result(self::$result_identifier, $result, implode(':', self::$successful_test_run_request->test_run_times));
				$result_file->add_result($test_result);
			}
		}
		if(getenv('INSTALL_TIMES_ARE_A_BENCHMARK') && self::$successful_test_run_request && isset(self::$successful_test_run_request->test_profile->test_installation) && self::$successful_test_run_request->test_profile->test_installation)
		{
			$install_time = round(self::$successful_test_run_request->test_profile->test_installation->get_latest_install_time(), 3);

			if($install_time > 0)
			{
				// This copy isn't needed but it's shorter and from port from system_monitor where there can be multiple items tracked
				$test_result = clone self::$successful_test_run_request;
				$test_result->test_profile->set_identifier(null);
				$test_result->set_used_arguments_description('Test Install Time');
				$test_result->set_used_arguments('test install time ');
				$test_result->test_profile->set_result_scale('Seconds');
				$test_result->test_profile->set_result_proportion('LIB');
				$test_result->test_result_buffer = new pts_test_result_buffer();
				$test_result->test_result_buffer->add_test_result(self::$result_identifier, $install_time);
				$result_file->add_result($test_result);
			}
		}
		if(getenv('INSTALL_SIZES_ARE_A_BENCHMARK') && self::$successful_test_run_request && isset(self::$successful_test_run_request->test_profile->test_installation) && self::$successful_test_run_request->test_profile->test_installation)
		{
			$install_size = self::$successful_test_run_request->test_profile->test_installation->get_install_size();

			if($install_size > 0)
			{
				// This copy isn't needed but it's shorter and from port from system_monitor where there can be multiple items tracked
				$test_result = clone self::$successful_test_run_request;
				$test_result->test_profile->set_identifier(null);
				$test_result->set_used_arguments_description('Test Install Size');
				$test_result->set_used_arguments('test install size ');
				$test_result->test_profile->set_result_scale('Bytes');
				$test_result->test_profile->set_result_proportion('LIB');
				$test_result->test_result_buffer = new pts_test_result_buffer();
				$test_result->test_result_buffer->add_test_result(self::$result_identifier, $install_size);
				$result_file->add_result($test_result);
			}
		}
		self::$successful_test_run_request = null;
	}
}
?>
