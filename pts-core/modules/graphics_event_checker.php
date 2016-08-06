<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2016, Phoronix Media
	Copyright (C) 2008 - 2016, Michael Larabel
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
	const module_name = 'Graphics Event Checker';
	const module_version = '1.0.0';
	const module_description = 'This module checks a number of events prior to and and after running a test to make sure the graphics sub-system was not put in a sour or unintended state by the application. For instance, it makes sure syncing to vBlank is not forced through the driver and that a graphics test has not left the display in an unintended mode.';
	const module_author = 'Michael Larabel';

	private static $start_video_resolution = array(-1, -1);
	private static $driver_forced_vsync = false;

	// GPU Errors
	static $error_pointer = 0;
	static $error_count = 0; // Number of GPU errors that were detected
	static $error_analysis = array(); // Array of error break down. For each array index is for a test where an error happened, it's TEST_NAME => ERROR_COUNT

	public static function __startup()
	{
	/*
		// Right now Phodevi is just using xrandr to set display modes, so if that's not present, this module will be useless
		if(!pts_client::executable_in_path('xrandr'))
		{
			return pts_module::MODULE_UNLOAD;
		}
	*/
		if(count(phodevi_parser::read_xdpy_monitor_info()) > 1)
		{
			// No multi-monitor support right now
			return pts_module::MODULE_UNLOAD;
		}
	}
	public static function __pre_run_process()
	{
		self::$error_count = 0;
		self::$error_pointer = 0;
		self::$error_analysis = array();

		// Store the video resolution
		// Access the xrandr resolution directly to ensure it's not polling the FB size or one of the KMS modes
		self::$start_video_resolution = phodevi_gpu::gpu_xrandr_resolution();

		if(phodevi::is_linux() && phodevi::is_ati_graphics())
		{
			$vsync_val = phodevi_linux_parser::read_amd_pcsdb('AMDPCSROOT/SYSTEM/BUSID-*/OpenGL,VSyncControl'); // Check for vSync
			if($vsync_val == '0x00000002' || $vsync_val == '0x00000003')
			{
				self::$driver_forced_vsync = true;
			}

			//$catalyst_ai_val = phodevi_linux_parser::read_amd_pcsdb('AMDPCSROOT/SYSTEM/BUSID-*/OpenGL,CatalystAI'); // Check for Catalyst AI
			//if($catalyst_ai_val == '0x00000001' || $catalyst_ai_val == '0x00000002')
			//	echo '\nCatalyst AI is enabled, which will use driver-specific optimizations in some tests that may offer extra performance enhancements.\n';
		}
		else if(phodevi::is_nvidia_graphics())
		{
			self::$error_pointer = self::nvidia_gpu_error_count(); // Set the error pointer

			if(phodevi_parser::read_nvidia_extension('SyncToVBlank') == '1')
			{
				shell_exec('nvidia-settings -a SyncToVBlank=0 2>&1');
				self::$driver_forced_vsync = true;
			}
		}

		if(self::$driver_forced_vsync == true)
		{
		//	echo '\nYour video driver is forcing vertical sync to be enabled. This will limit the system's frame-rate performance potential in any graphical tests!\n';
		}

		// vblank_mode=0 has long been set within pts-core, but put it here too just since there's these other checks here
		putenv('vblank_mode=0');
	}
	public static function __post_test_run($test_result)
	{
		if($test_result->test_profile->get_test_hardware_type() != 'Graphics')
		{
			return;
		}

		// Check for video resolution changes
		// Access the xrandr resolution directly to ensure it's not polling the FB size or one of the KMS modes
		$current_res = phodevi_gpu::gpu_xrandr_resolution();

		if($current_res != self::$start_video_resolution && self::$start_video_resolution != array(-1, -1))
		{
			$video_width = $current_res[0];
			$video_height = $current_res[1];

			$reset = self::$start_video_resolution;
			$reset_width = $reset[0];
			$reset_height = $reset[1];

		//	echo '\nThe video resolution had changed during testing and it was not properly reset! Now resetting to $reset_width x $reset_height from $video_width x $video_height.\n';
			phodevi::set_property('gpu', 'screen-resolution', array($reset_width, $reset_height));
			// Sleep for three seconds to allow time for display to settle after mode-set
			sleep(3);
		}

		if(phodevi::is_nvidia_graphics())
		{
			$current_error_position = self::nvidia_gpu_error_count();

			if($current_error_position > self::$error_pointer && $test_result instanceof pts_test_result)
			{
				// GPU Error(s) Happened During The Test
				$this_test = $test_result->test_profile->get_identifier();
				$this_error_count = $current_error_position - self::$error_pointer;

				if(isset(self::$error_analysis[$this_test]))
				{
					$this_error_count += self::$error_analysis[$this_test];
				}

				self::$error_analysis[$this_test] = $this_error_count; // Tally up errors for this test
				self::$error_count += $this_error_count; // Add to total error count
				self::$error_pointer = $current_error_position; // Reset the pointer
			}
		}
	}
	public static function __post_option_process()
	{
		if(self::$error_count > 0)
		{
			$error_breakdown = PHP_EOL;
			foreach(self::$error_analysis as $test => $error_count)
			{
				$error_breakdown .= PHP_EOL . $test . ': ' . $error_count;
			}

			echo PHP_EOL . 'GPU Errors: ' . $error_count . $error_breakdown . PHP_EOL;
		}

		if(self::$driver_forced_vsync && phodevi::is_nvidia_graphics())
		{
			shell_exec('nvidia-settings -a SyncToVBlank=1 2>&1');
		}
	}
	protected static function nvidia_gpu_error_count()
	{
		$count = phodevi_parser::read_nvidia_extension('GPUErrors');
		return $count == null || !is_numeric($count) ? 0 : $count;
	}
}

?>
