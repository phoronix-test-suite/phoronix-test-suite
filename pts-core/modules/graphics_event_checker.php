<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2004-2008, Michael Larabel
	graphics_override.php: Graphics AA/AF image quality setting override module

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

class graphics_event_checker extends pts_module_interface
{
	const module_name = "Graphics Event Checker";
	const module_version = "0.0.2";
	const module_description = "This module checks a number of events prior to and and after running a test to make sure the graphics sub-system wasn't put in a sour or unintended state. For instance, it makes sure syncing to vBlank isn't forced through the driver and that a graphics test hadn't ended prematurely where it left the resolution in an incorrect mode.";
	const module_author = "Michael Larabel";

	static $start_video_resolution = array(-1, -1);
	static $start_vertical_sync = FALSE;

	public static function __pre_run_process()
	{
		if(count(read_xdpy_monitor_info()) > 1)
		{
			echo "\nThe graphics_event_checker currently doesn't support multiple monitors.\n";
			return PTS_MODULE_UNLOAD;
		}

		// Store the video resolution
		self::$start_video_resolution = xrandr_screen_resolution();

		if(IS_ATI_GRAPHICS)
		{
			$vsync_val = read_amd_pcsdb("AMDPCSROOT/SYSTEM/BUSID-*/OpenGL,VSyncControl"); // Check for vSync
			if($vsync_val == "0x00000002" || $vsync_val == "0x00000003")
				self::$start_vertical_sync = TRUE;

			$catalyst_ai_val = read_amd_pcsdb("AMDPCSROOT/SYSTEM/BUSID-*/OpenGL,CatalystAI"); // Check for Catalyst AI
			if($catalyst_ai_val == "0x00000001" || $catalyst_ai_val == "0x00000002")
				echo "\nCatalyst AI is enabled, which will use driver-specific optimizations in some tests that may offer extra performance enhancements.\n";
		}
		else if(IS_NVIDIA_GRAPHICS)
		{
			if(read_nvidia_extension("SyncToVBlank") == "1")
				self::$start_vertical_sync = TRUE;
		}

		if(self::$start_vertical_sync == TRUE)
			echo "\nYour video driver is forcing vertical sync to be enabled. This will limit the system's frame-rate performance potential in any graphical tests!\n";
	}
	public static function __interim_test_run()
	{
		self::check_video_resolution();
	}
	public static function __post_test_run()
	{
		self::check_video_resolution();
	}
	public static function __shutdown()
	{
		self::check_video_resolution();
	}


	private static function check_video_resolution()
	{
		$current_res = xrandr_screen_resolution();

		if($current_res != self::$start_video_resolution && self::$start_video_resolution != array(-1, -1))
		{
			$video_width = $current_res[0];
			$video_height = $current_res[1];

			$reset = self::$start_video_resolution;
			$reset_width = $reset[0];
			$reset_height = $reset[1];

			echo "\nThe video resolution had changed during testing and it was not properly reset! Now resetting to $reset_width x $reset_height from $video_width x $video_height.\n";
			set_video_resolution($reset_width, $reset_height);
		}
	}
}

?>
