<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel
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
	const module_name = "Timed Screenshot";
	const module_version = "0.1.0";
	const module_description = "This is a module that will take a screenshot of the system at a pre-defined interval. ImageMagick must be installed onto the system prior to using this module.";
	const module_author = "Michael Larabel";

	static $screenshot_count = 0;
	static $screenshot_interval = 15;

	public static function __startup()
	{
		pts_module::remove_file("is_running");
		$PATH = pts_client::read_env("PATH");
		$found = false;

		foreach(explode(":", $PATH) as $single_path)
			if(is_file($single_path . "/import"))
				$found = true;

		if(!$found)
		{
			echo "\nImageMagick must first be installed onto this system!\n";
			return PTS_MODULE_UNLOAD;
		}

		if(($interval = pts_module_variable("SCREENSHOT_INTERVAL")) > 0 && is_numeric($interval))
			self::$screenshot_interval = $interval;
	}
	public static function __shutdown()
	{
		if(self::$screenshot_count > 0)
			echo "\n" . self::$screenshot_count . " screenshots recorded. They are saved in the " . pts_module::save_dir() . " directory.\n";
	}

	public static function __pre_run_process()
	{
		pts_module::pts_timed_function(self::$screenshot_interval, "take_screenshot");
	}
	public static function __pre_test_run()
	{
		pts_module::save_file("is_running", "yes");
	}
	public static function __post_test_run()
	{
		pts_module::remove_file("is_running");
	}
	public static function take_screenshot()
	{
		if(pts_module::read_file("is_running") == "yes")
		{
			shell_exec("import -window root " . pts_module::save_dir() . "screenshot-" . self::$screenshot_count . ".png");
			self::$screenshot_count++;
		}
	}
}

?>
