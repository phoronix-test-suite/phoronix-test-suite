<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
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
	const module_version = "0.1.1";
	const module_description = "This module checks a number of events prior to and and after running a test to make sure the graphics sub-system was not put in a sour or unintended state. For instance, it makes sure syncing to vBlank is not forced through the driver and that a graphics test had not ended prematurely where it left the resolution in an incorrect mode.";
	const module_author = "Michael Larabel";

	static $start_video_resolution = array(-1, -1);
	static $start_vertical_sync = FALSE;

	// GPU Errors (Currently NVIDIA-only)
	static $error_pointer = 0;
	static $error_count = 0; // Number of GPU errors that were detected
	static $error_analysis = array(); // Array of error break down. For each array index is for a test where an error happened, it's TEST_NAME => ERROR_COUNT

	public static function __pre_run_process()
	{
		if(count(read_xdpy_monitor_info()) > 1)
		{
			echo "\nThe graphics_event_checker currently does not support multiple monitors.\n";
			return PTS_MODULE_UNLOAD;
		}

		// Store the video resolution
		self::$start_video_resolution = phodevi::read_property("gpu", "screen-resolution");

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
			self::$error_pointer = self::nvidia_gpu_error_count(); // Set the error pointer

			if(read_nvidia_extension("SyncToVBlank") == "1")
				self::$start_vertical_sync = TRUE;
		}

		if(self::$start_vertical_sync == TRUE)
			echo "\nYour video driver is forcing vertical sync to be enabled. This will limit the system's frame-rate performance potential in any graphical tests!\n";
	}
	public static function __interim_test_run($pts_test_result)
	{
		self::check_video_events($pts_test_result);
	}
	public static function __post_test_run($pts_test_result)
	{
		self::check_video_events($pts_test_result);
	}
	public static function __post_option_process()
	{
		if(self::$error_count > 0)
		{
			$error_breakdown = "\n";
			foreach(self::$error_analysis as $test => $error_count)
				$error_breakdown .= "\n" . $test . ": " . $error_count;

			echo pts_string_header("GPU Errors: " . $error_count . $error_breakdown);
		}
	}

	private static function check_video_events($pts_test_result = "")
	{
		// Check for video resolution changes
		self::check_video_resolution();

		if(IS_NVIDIA_GRAPHICS)
		{
			$current_error_position = self::nvidia_gpu_error_count();

			if($current_error_position > self::$error_pointer && is_object($pts_test_result))
			{
				// GPU Error(s) Happened During The Test
				$this_test = $pts_test_result->get_attribute("TEST_IDENTIFIER");
				$this_error_count = $current_error_position - self::$error_pointer;

				if(isset(self::$error_analysis[$this_test]))
					$this_error_count += self::$error_analysis[$this_test];

				self::$error_analysis[$this_test] = $this_error_count; // Tally up errors for this test
				self::$error_count += $this_error_count; // Add to total error count
				self::$error_pointer = $current_error_position; // Reset the pointer
			}
		}
	}
	private static function check_video_resolution()
	{
		$current_res = phodevi::read_property("gpu", "screen-resolution");

		if($current_res != self::$start_video_resolution && self::$start_video_resolution != array(-1, -1))
		{
			$video_width = $current_res[0];
			$video_height = $current_res[1];

			$reset = self::$start_video_resolution;
			$reset_width = $reset[0];
			$reset_height = $reset[1];

			echo "\nThe video resolution had changed during testing and it was not properly reset! Now resetting to $reset_width x $reset_height from $video_width x $video_height.\n";
			phodevi::set_property("gpu", "screen-resolution", array($reset_width, $reset_height));
		}
	}
	protected static function nvidia_gpu_error_count()
	{
		$count = read_nvidia_extension("GPUErrors");

		if($count == null || !is_numeric($count))
			$count = 0;

		return $count;
	}
}

?>
