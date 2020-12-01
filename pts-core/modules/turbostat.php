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

class turbostat extends pts_module_interface
{
	const module_name = 'Linux Turbostat Dumper';
	const module_version = '1.0.0';
	const module_description = 'Setting TURBOSTAT_LOG_DIR=_DIR_ will auto-load and enable this Phoronix Test Suite module. The module will -- if turbostat is installed on the system and the user is root -- allow dumping of the TurboStat data to the specified directly on a per-test basis. This allows easily collecting of turbostat logs for each test being run.';
	const module_author = 'Michael Larabel';

	private static $turbostat_log_dir;

	public static function module_environmental_variables()
	{
		return array('TURBOSTAT_LOG_DIR');
	}
	public static function module_info()
	{
		return null;
	}
	public static function __run_manager_setup(&$test_run_manager)
	{
		$dump_dir = getenv('TURBOSTAT_LOG_DIR');
		if(empty($dump_dir))
		{
			return pts_module::MODULE_UNLOAD; // This module doesn't have anything else to do
		}
		if(!pts_client::executable_in_path('turbostat'))
		{
			echo PHP_EOL . pts_client::cli_just_bold('turbostat not found in PATH.') . PHP_EOL;
			return pts_module::MODULE_UNLOAD;
		}
		if(!is_dir($dump_dir) || !is_writable($dump_dir))
		{
			echo PHP_EOL . pts_client::cli_just_bold('TURBOSTAT_LOG_DIR must be pointed to an existing, writable directory.') . PHP_EOL;
			return pts_module::MODULE_UNLOAD;
		}
		if(!phodevi::is_root())
		{
			echo PHP_EOL . pts_client::cli_just_bold('turbostat requires root access.') . PHP_EOL;
			return pts_module::MODULE_UNLOAD;
		}
		self::$turbostat_log_dir = $dump_dir . '/';
		echo PHP_EOL . 'Linux TurboStats Dumping Enabled To ' . self::$turbostat_log_dir . '.' . PHP_EOL . PHP_EOL;
	}
	public static function __pre_test_run(&$test_run_request)
	{
		$test_run_request->exec_binary_prepend = 'turbostat -o ' . self::$turbostat_log_dir . str_replace(array(' ', '/', '.', ':'), '_', trim($test_run_request->test_profile->get_identifier() . ' ' . $test_run_request->get_arguments_description())) . '.log ';
	}
}
?>
