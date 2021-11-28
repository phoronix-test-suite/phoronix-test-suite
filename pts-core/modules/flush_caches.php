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

class flush_caches extends pts_module_interface
{
	const module_name = 'Flush Caches';
	const module_version = '1.0.0';
	const module_description = 'Loading this module will ensure caches (page cache, swap, etc) automatically get flushed prior to running any test.';
	const module_author = 'Phoronix Media';

	public static function module_environment_variables()
	{
		return array('PTS_FLUSH_CACHES');
	}
	public static function __run_manager_setup(&$test_run_manager)
	{
		if(!phodevi::is_linux())
		{
			echo PHP_EOL . 'The flush_caches module is currently only supported on Linux, unloading...' . PHP_EOL;
			return pts_module::MODULE_UNLOAD;
		}
		if(!phodevi::is_root())
		{
			echo PHP_EOL . 'The flush_caches module requires root access, unloading...' . PHP_EOL;
			return pts_module::MODULE_UNLOAD;
		}

		echo PHP_EOL . 'Flush_Caches module enabled...' . PHP_EOL;
	}
	public static function __pre_test_run()
	{
		self::do_flush();
	}
	public static function __interim_test_run()
	{
		self::do_flush();
	}
	public static function do_flush()
	{
		if(is_writable('/proc/sys/vm/drop_caches'))
		{
			shell_exec('sync; echo 3 > /proc/sys/vm/drop_caches');
		}
		if(pts_client::executable_in_path('swapoff'))
		{
			shell_exec('swapoff -a && swapon -a');
		}
	}
}

?>
