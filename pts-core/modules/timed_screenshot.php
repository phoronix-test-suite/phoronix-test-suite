<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2016, Phoronix Media
	Copyright (C) 2008 - 2016, Michael Larabel
	timed_screenshot.php: A PTS module that takes a screenshot at a pre-defined interval.

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

class timed_screenshot extends pts_module_interface
{
	const module_name = 'Timed Screenshot';
	const module_version = '1.0.1';
	const module_description = 'This is a module that will take a screenshot of the system at a pre-defined interval. ImageMagick must be installed onto the system prior to using this module.';
	const module_author = 'Michael Larabel';

	protected static $screenshots = array();
	protected static $screenshot_interval = 10;
	protected static $existing_screenshots = array();

	public static function module_environment_variables()
	{
		return array('SCREENSHOT_INTERVAL');
	}
	public static function __startup()
	{
		// Make sure the file is removed to avoid potential problems if it was leftover from earlier run
		pts_module::remove_file('is_running');

		if(pts_client::executable_in_path('import') == false)
		{
			echo PHP_EOL . 'ImageMagick must first be installed onto this system!' . PHP_EOL;
			return pts_module::MODULE_UNLOAD;
		}

		if(($interval = pts_env::read('SCREENSHOT_INTERVAL')) > 1 && is_numeric($interval))
		{
			self::$screenshot_interval = $interval;
			return true;
		}

		return pts_module::MODULE_UNLOAD;
		self::$existing_screenshots = pts_file_io::glob(pts_module::save_dir() . 'screenshot-*.png');
	}
	public static function __shutdown()
	{
		if(!empty(self::$screenshots))
		{
			echo PHP_EOL . count(self::$screenshots) . ' screenshots recorded. They are saved in the ' . pts_module::save_dir() . ' directory.' . PHP_EOL;
		}
	}

	public static function __pre_run_process()
	{
		self::$screenshots = array();
		pts_module::pts_timed_function('take_screenshot', self::$screenshot_interval);
	}
	public static function __pre_test_run()
	{
		pts_module::save_file('is_running', 'yes');
	}
	public static function __post_test_run()
	{
		pts_module::remove_file('is_running');
		$screenshots = self::get_screenshots();

		foreach($screenshots as $screenshot)
		{
			// Compress the PNGs a bit
			pts_image::compress_png_image($screenshot, 9);
		}

		return $screenshots;
	}
	public static function take_screenshot($force = false)
	{
		if(pts_module::read_file('is_running') == 'yes' || $force)
		{
			$ss_file = pts_module::save_dir() . 'screenshot-' . date('ymd-His') . '.png';
			shell_exec('import -window root ' . $ss_file);

			if(is_file($ss_file))
			{
				self::$screenshots[] = $ss_file;
				return $ss_file;
			}
		}

		return false;
	}
	public static function get_screenshots()
	{
		if(!empty(self::$screenshots))
		{
			return self::$screenshots;
		}
		else
		{
			// Another thread is going on and thread empty so try to query the file-system for differences
			$screenshots = pts_file_io::glob(pts_module::save_dir() . 'screenshot-*.png');
			return array_diff($screenshots, self::$existing_screenshots);
		}
	}
}

?>
