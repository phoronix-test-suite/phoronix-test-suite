<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel
	phodevi_gpu.php: The PTS Device Interface object for the graphics processor

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

class phodevi_gpu extends phodevi_device_interface
{
	public static function read_property($identifier)
	{
		switch($identifier)
		{
			case "identifier":
				$property = new phodevi_device_property("gpu_string", PHODEVI_SMART_CACHE);
				break;
			case "model":
				$property = new phodevi_device_property("gpu_model", PHODEVI_SMART_CACHE);
				break;
			case "frequency":
				$property = new phodevi_device_property("gpu_frequency_string", PHODEVI_SMART_CACHE);
				break;
			case "stock-frequency":
				$property = new phodevi_device_property("gpu_stock_frequency", PHODEVI_SMART_CACHE);
				break;
			case "2d-accel-method":
				$property = new phodevi_device_property("gpu_2d_accel_method", PHODEVI_STAND_CACHE);
				break;
			case "memory-capacity":
				$property = new phodevi_device_property("gpu_memory_size", PHODEVI_SMART_CACHE);
				break;
			case "aa-level":
				$property = new phodevi_device_property("gpu_aa_level", PHODEVI_AVOID_CACHE);
				break;
			case "af-level":
				$property = new phodevi_device_property("gpu_af_level", PHODEVI_AVOID_CACHE);
				break;
			case "available-modes":
				$property = new phodevi_device_property("gpu_available_modes", PHODEVI_SMART_CACHE);
				break;
			case "screen-resolution":
				$property = new phodevi_device_property("gpu_screen_resolution", PHODEVI_SMART_CACHE);
				break;
			case "screen-resolution-string":
				$property = new phodevi_device_property("gpu_screen_resolution_string", PHODEVI_SMART_CACHE);
				break;
		}

		return $property;
	}
	public static function set_property($identifier, $args)
	{
		switch($identifier)
		{
			case "screen-resolution":
				$property = self::gpu_set_resolution($args);
				break;
		}

		return $property;
	}
	public static function special_settings_string()
	{
		$special_string = null;
		$extra_gfx_settings = array();
		$aa_level = phodevi::read_property("gpu", "aa-level");
		$af_level = phodevi::read_property("gpu", "af-level");

		if($aa_level)
		{
			array_push($extra_gfx_settings, "AA: " . $aa_level);
		}
		if($af_level)
		{
			array_push($extra_gfx_settings, "AF: " . $af_level);
		}

		if(count($extra_gfx_settings) > 0)
		{
			$special_string = implode(" - ", $extra_gfx_settings);
		}

		return $special_string;
	}
	public static function gpu_set_resolution($args)
	{
		if(count($args) != 2 || IS_WINDOWS || IS_MACOSX)
		{
			return false;
		}

		$width = $args[0];
		$height = $args[1];

		shell_exec("xrandr -s " . $width . "x" . $height . " 2>&1");

		return phodevi::read_property("gpu", "screen-resolution") == array($width, $height); // Check if video resolution set worked
	}
	public static function gpu_aa_level()
	{
		// Determine AA level if over-rode
		$aa_level = false;

		if(IS_NVIDIA_GRAPHICS)
		{
			$nvidia_fsaa = phodevi_parser::read_nvidia_extension("FSAA");

			switch($nvidia_fsaa)
			{
				case 1:
					$aa_level = "2x Bilinear";
					break;
				case 5:
					$aa_level = "4x Bilinear";
					break;
				case 7:
					$aa_level = "8x";
					break;
				case 8:
					$aa_level = "16x";
					break;
				case 10:
					$aa_level = "8xQ";
					break;
				case 12:
					$aa_level = "16xQ";
					break;
			}
		}
		else if(IS_ATI_GRAPHICS && IS_LINUX)
		{
			$ati_fsaa = phodevi_linux_parser::read_amd_pcsdb("OpenGL,AntiAliasSamples");
			$ati_fsaa_filter = phodevi_linux_parser::read_amd_pcsdb("OpenGL,AAF");

			if(!empty($ati_fsaa))
			{
				if($ati_fsaa_filter == "0x00000000")
				{
					// Filter: Box
					switch($ati_fsaa)
					{
						case "0x00000002":
							$aa_level = "2x Box";
							break;
						case "0x00000004":
							$aa_level = "4x Box";
							break;
						case "0x00000008":
							$aa_level = "8x Box";
							break;
					}
				}
				else if($ati_fsaa_filter == "0x00000001")
				{
					// Filter: Narrow-tent
					switch($ati_fsaa)
					{
						case "0x00000002":
							$aa_level = "4x Narrow-tent";
							break;
						case "0x00000004":
							$aa_level = "8x Narrow-tent";
							break;
						case "0x00000008":
							$aa_level = "12x Narrow-tent";
							break;
					}
				}
				else if($ati_fsaa_filter == "0x00000002")
				{
					// Filter: Wide-tent
					switch($ati_fsaa)
					{
						case "0x00000002":
							$aa_level = "6x Wide-tent";
							break;
						case "0x00000004":
							$aa_level = "8x Wide-tent";
							break;
						case "0x00000008":
							$aa_level = "16x Wide-tent";
							break;
					}

				}
				else if($ati_fsaa_filter == "0x00000003")
				{
					// Filter: Edge-detect
					switch($ati_fsaa)
					{
						case "0x00000004":
							$aa_level = "12x Edge-detect";
							break;
						case "0x00000008":
							$aa_level = "24x Edge-detect";
							break;
					}
				}
			}
		}

		return $aa_level;
	}
	public static function gpu_af_level()
	{
		// Determine AF level if over-rode
		$af_level = false;

		if(IS_NVIDIA_GRAPHICS)
		{
			$nvidia_af = phodevi_parser::read_nvidia_extension("LogAniso");

			switch($nvidia_af)
			{
				case 1:
					$af_level = "2x";
					break;
				case 2:
					$af_level = "4x";
					break;
				case 3:
					$af_level = "8x";
					break;
				case 4:
					$af_level = "16x";
					break;
			}
		}
		else if(IS_ATI_GRAPHICS && IS_LINUX)
		{
			$ati_af = phodevi_linux_parser::read_amd_pcsdb("OpenGL,AnisoDegree");

			if(!empty($ati_af))
			{
				switch($ati_af)
				{
					case "0x00000002":
						$af_level = "2x";
						break;
					case "0x00000004":
						$af_level = "4x";
						break;
					case "0x00000008":
						$af_level = "8x";
						break;
					case "0x00000010":
						$af_level = "16x";
						break;
				}
			}
		}

		return $af_level;
	}
	public static function gpu_screen_resolution()
	{
		$resolution = false;

		if((($default_mode = getenv("DEFAULT_VIDEO_MODE")) != false))
		{
			$default_mode = explode('x', $default_mode);

			if(count($default_mode) == 2 && is_numeric($default_mode[0]) && is_numeric($default_mode[1]))
			{
				return $default_mode;
			}
		}

		if(IS_MACOSX)
		{
			$info = pts_trim_explode(' ', phodevi_osx_parser::read_osx_system_profiler("SPDisplaysDataType", "Resolution"));
			$resolution = array();
			$resolution[0] = $info[0];
			$resolution[1] = $info[2];
		}
		else if(IS_LINUX || IS_BSD || IS_SOLARIS)
		{
			if(IS_LINUX)
			{
				// Before calling xrandr first try to get the resolution through KMS path
				foreach(pts_glob("/sys/class/drm/card*/*/modes") as $connector_path)
				{
					$connector_path = dirname($connector_path) . '/';

					if(is_file($connector_path . "enabled") && pts_file_get_contents($connector_path . "enabled") == "enabled")
					{
						$mode = pts_first_element_in_array(explode("\n", pts_file_get_contents($connector_path . "modes")));
						$info = pts_trim_explode('x', $mode);

						if(count($info) == 2)
						{
							$resolution = $info;
							break;
						}
					}
				}
			}

			if($resolution == false && IS_NVIDIA_GRAPHICS)
			{
				// Way to find resolution through NVIDIA's NV-CONTROL extension
				if(($frontend_res = phodevi_parser::read_nvidia_extension("FrontendResolution")) != false)
				{
					$resolution = explode(',', $frontend_res);
				}
			}

			if($resolution == false && pts_executable_in_path("xrandr"))
			{
				// Read resolution from xrandr
				$info = shell_exec("xrandr 2>&1 | grep \"*\"");

				if(strpos($info, "*") !== false)
				{
					$res = pts_trim_explode("x", $info);
					$res[0] = substr($res[0], strrpos($res[0], " "));
					$res[1] = substr($res[1], 0, strpos($res[1], " "));
					$res = array_map("trim", $res);

					if(is_numeric($res[0]) && is_numeric($res[1]))
					{
						$resolution = array($res[0], $res[1]);
					}
				}
			}

			if($resolution == false)
			{
				// Fallback to reading resolution from xdpyinfo
				foreach(phodevi_parser::read_xdpy_monitor_info() as $monitor_line)
				{
					$this_resolution = substr($monitor_line, strpos($monitor_line, ': ') + 2);
					$this_resolution = substr($this_resolution, 0, strpos($this_resolution, ' '));
					$this_resolution = explode('x', $this_resolution);

					if(count($this_resolution) == 2 && is_numeric($this_resolution[0]) && is_numeric($this_resolution[1]))
					{
						$resolution = $this_resolution;
						break;
					}
				}
			}
		}

		return $resolution == false ? array(-1, -1) : $resolution;
	}
	public static function gpu_screen_resolution_string()
	{
		// Return the current screen resolution
		$resolution = implode("x", phodevi::read_property("gpu", "screen-resolution"));

		if($resolution == "-1x-1")
		{
			$resolution = "Unknown";
		}

		return $resolution;
	}
	public static function gpu_available_modes()
	{
		// XRandR available modes
		$current_resolution = phodevi::read_property("gpu", "screen-resolution");
		$current_pixel_count = $current_resolution[0] * $current_resolution[1];
		$available_modes = array();
		$supported_ratios = array(1.60, 1.25, 1.33, 1.70, 1.71, 1.78);
		$ignore_modes = array(
			array(640, 400),
			array(720, 480), array(832, 624),
			array(960, 540), array(960, 600),
			array(896, 672), array(928, 696),
			array(960, 720), array(1152, 864),
			array(1280, 720), array(1360, 768),
			array(1776, 1000), array(1792, 1344),
			array(1800, 1440), array(1856, 1392),
			array(2048, 1536)
			);

		if($override_check = (($override_modes = getenv("OVERRIDE_VIDEO_MODES")) != false))
		{
			$override_modes = pts_trim_explode(',', $override_modes);

			for($i = 0; $i < count($override_modes); $i++)
			{
				$override_modes[$i] = explode('x', $override_modes[$i]);
			}
		}

		// Attempt reading available modes from xrandr
		if(pts_executable_in_path("xrandr") && !IS_MACOSX) // MacOSX has xrandr but currently on at least my setup will emit a Bus Error when called
		{
			$xrandr_lines = array_reverse(explode("\n", shell_exec("xrandr 2>&1")));

			foreach($xrandr_lines as $xrandr_mode)
			{
				if(($cut_point = strpos($xrandr_mode, '(')) > 0)
				{
					$xrandr_mode = substr($xrandr_mode, 0, $cut_point);
				}

				$res = pts_trim_explode('x', $xrandr_mode);

				if(count($res) == 2)
				{
					$res[0] = substr($res[0], strrpos($res[0], " "));
					$res[1] = substr($res[1], 0, strpos($res[1], " "));

					if(is_numeric($res[0]) && is_numeric($res[1]))
					{
						array_push($available_modes, array($res[0], $res[1]));
					}
				}
			}
		}

		if(count($available_modes) <= 2)
		{
			// Fallback to providing stock modes
			$stock_modes = array(
				array(800, 600), array(1024, 768),
				array(1280, 1024), array(1400, 1050), 
				array(1680, 1050), array(1600, 1200),
				array(1920, 1080), array(2560, 1600));
			$available_modes = array();

			for($i = 0; $i < count($stock_modes); $i++)
			{
				if($stock_modes[$i][0] <= $current_resolution[0] && $stock_modes[$i][1] <= $current_resolution[1])
				{
					array_push($available_modes, $stock_modes[$i]);
				}
			}
		}

		foreach($available_modes as $mode_index => $mode)
		{
			$this_ratio = pts_math::set_precision($mode[0] / $mode[1], 2);

			if($override_check && !in_array($mode, $override_modes))
			{
				// Using override modes and this mode is not present
				unset($available_modes[$mode_index]);
			}
			else if($current_pixel_count > 614400 && ($mode[0] * $mode[1]) < 480000)
			{
				// For displays larger than 1024 x 600, drop modes below 800 x 600
				unset($available_modes[$mode_index]);
			}
			else if($current_pixel_count > 480000 && !in_array($this_ratio, $supported_ratios))
			{
				// For displays larger than 800 x 600, ensure reading from a supported ratio
				unset($available_modes[$mode_index]);
			}
			else if(in_array($mode, $ignore_modes))
			{
				// Mode is to be ignored
				unset($available_modes[$mode_index]);
			}
		}

		// Sort available modes in order
		$unsorted_modes = $available_modes;
		$available_modes = array();
		$mode_pixel_counts = array();

		foreach($unsorted_modes as $this_mode)
		{
			if(count($this_mode) == 2)
			{
				array_push($mode_pixel_counts, $this_mode[0] * $this_mode[1]);
			}
		}

		// Sort resolutions by true pixel count resolution
		sort($mode_pixel_counts);
		foreach($mode_pixel_counts as &$mode_pixel_count)
		{
			foreach($unsorted_modes as $mode_index => $mode)
			{
				if($mode[0] * $mode[1] == $mode_pixel_count)
				{
					array_push($available_modes, $mode);
					unset($unsorted_modes[$mode_index]);
					break;
				}
			}
		}

		if(count($available_modes) == 0 && $override_check)
		{
			// Write in the non-standard modes that were overrode
			foreach($override_modes as $mode)
			{
				if(is_array($mode) && count($mode) == 2)
				{
					array_push($available_modes, $mode);
				}
			}
		}

		return $available_modes;
	}
	public static function gpu_2d_accel_method()
	{
		$accel_method = "";

		if(is_file("/var/log/Xorg.0.log"))
		{
			$x_log = file_get_contents("/var/log/Xorg.0.log");

			if(strpos($x_log, "Using EXA") > 0)
			{
				$accel_method = "EXA";
			}
			else if(strpos($x_log, "Using UXA") > 0)
			{
				$accel_method = "UXA";
			}
			else if(strpos($x_log, "Using XFree86") > 0)
			{
				$accel_method = "XAA";
			}
		}

		return $accel_method;
	}
	public static function gpu_memory_size()
	{
		// Graphics memory capacity
		$video_ram = 64; // Assume 64MB of video RAM at least

		if(($vram = getenv("VIDEO_MEMORY")) != false && is_numeric($vram) && $vram > $video_ram)
		{
			$video_ram = $vram;
		}
		else
		{
			if(IS_NVIDIA_GRAPHICS && ($NVIDIA = phodevi_parser::read_nvidia_extension("VideoRam")) > 0) // NVIDIA blob
			{
				$video_ram = $NVIDIA / 1024;
			}
			else if(IS_MACOSX)
			{
				$info = phodevi_osx_parser::read_osx_system_profiler("SPDisplaysDataType", "VRAM");
				$info = explode(" ", $info);
				$video_ram = $info[0];
			
				if($info[1] == "GB")
				{
					$video_ram *= 1024;
				}
			}
			else if(is_file("/var/log/Xorg.0.log"))
			{
				// Attempt Video RAM detection using X log
				// fglrx driver reports video memory to: (--) fglrx(0): VideoRAM: XXXXXX kByte, Type: DDR
				// xf86-video-ati, xf86-video-intel, and xf86-video-radeonhd also report their memory information in a similar format

				$info = shell_exec("cat /var/log/Xorg.0.log | grep -i VideoRAM");

				if(empty($info))
				{
					$info = shell_exec("cat /var/log/Xorg.0.log | grep \"Video RAM\"");
				}

				if(($pos = strpos($info, "RAM:") + 5) > 5 || ($pos = strpos($info, "Ram:") + 5) > 5 || ($pos = strpos($info, "RAM=") + 4) > 4)
				{
					$info = substr($info, $pos);
					$info = substr($info, 0, strpos($info, " "));

					if(!is_numeric($info) && ($cut = strpos($info, ",")))
					{
						$info = substr($info, 0, $cut);
					}

					if($info > 65535)
					{
						$video_ram = intval($info) / 1024;
					}
				}
			}
		}

		return $video_ram;
	}
	public static function gpu_string()
	{
		return phodevi::read_property("gpu", "model") . phodevi::read_property("gpu", "frequency");
	}
	public static function gpu_frequency_string()
	{
		$freq = phodevi::read_property("gpu", "stock-frequency");
		$freq_string = null;

		if($freq[0] != 0)
		{
			$freq_string = $freq[0];

			if($freq[1] != 0)
			{
				$freq_string .= "/" . $freq[1];
			}

			$freq_string .= "MHz";
		}

		return ($freq_string != null ? " (" . $freq_string . ")" : null);
	}
	public static function gpu_stock_frequency()
	{
		// Graphics processor stock frequency
		$core_freq = 0;
		$mem_freq = 0;

		if(IS_NVIDIA_GRAPHICS) // NVIDIA GPU
		{
			list($core_freq, $mem_freq) = explode(",", phodevi_parser::read_nvidia_extension("GPUDefault3DClockFreqs"));
		}
		else if(IS_ATI_GRAPHICS && IS_LINUX) // ATI GPU
		{
			$od_clocks = phodevi_linux_parser::read_ati_overdrive("CurrentPeak");

			if(is_array($od_clocks) && count($od_clocks) >= 2) // ATI OverDrive
			{
				list($core_freq, $mem_freq) = $od_clocks;
			}
		}
		else if(IS_MESA_GRAPHICS)
		{
			switch(phodevi::read_property("system", "display-driver"))
			{
				case "radeon":
					if(is_file("/sys/kernel/debug/dri/0/radeon_pm_info"))
					{
						// radeon_pm_info should be present with Linux 2.6.34+
						foreach(pts_trim_explode("\n", pts_file_get_contents("/sys/kernel/debug/dri/0/radeon_pm_info")) as $pm_line)
						{
							list($descriptor, $value) = pts_trim_explode(':', $pm_line);

							switch($descriptor)
							{
								case "default engine clock":
									$core_freq = pts_first_element_in_array(explode(' ', $value)) / 1000;
									break;
								case "default memory clock":
									$mem_freq = pts_first_element_in_array(explode(' ', $value)) / 1000;
									break;
							}
						}
					}
					else
					{
						// Old ugly way of handling the clock information
						$log_parse = shell_exec("cat /var/log/Xorg.0.log 2>&1 | grep \" Clock: \"");

						$core_freq = substr($log_parse, strpos($log_parse, "Default Engine Clock: ") + 22);
						$core_freq = substr($core_freq, 0, strpos($core_freq, "\n"));
						$core_freq = is_numeric($core_freq) ? $core_freq / 1000 : 0;

						$mem_freq = substr($log_parse, strpos($log_parse, "Default Memory Clock: ") + 22);
						$mem_freq = substr($mem_freq, 0, strpos($mem_freq, "\n"));
						$mem_freq = is_numeric($mem_freq) ? $mem_freq / 1000 : 0;
					}				
					break;
			}
		}

		if(!is_numeric($core_freq))
		{
			$core_freq = 0;
		}
		if(!is_numeric($mem_freq))
		{
			$mem_freq = 0;
		}

		return array($core_freq, $mem_freq);
	}
	public static function gpu_model()
	{
		// Report graphics processor string
		$info = pts_executable_in_path("glxinfo") != false ? shell_exec("glxinfo 2>&1 | grep renderer") : null;
		$video_ram = phodevi::read_property("gpu", "memory-capacity");

		if(($pos = strpos($info, "renderer string:")) > 0)
		{
			$info = substr($info, $pos + 16);
			$info = trim(substr($info, 0, strpos($info, "\n")));
		}
		else
		{
			$info = null;
		}

		if(IS_ATI_GRAPHICS && IS_LINUX)
		{
			$crossfire_status = phodevi_linux_parser::read_amd_pcsdb("SYSTEM/Crossfire/chain/*,Enable");
			$crossfire_status = pts_to_array($crossfire_status);
			$crossfire_card_count = 0;

			for($i = 0; $i < count($crossfire_status); $i++)
			{
				if($crossfire_status[$i] == "0x00000001")
				{
					$crossfire_card_count += 2; // For now assume each chain is 2 cards, but proper way would be NumSlaves + 1
				}
			}			

			$adapters = phodevi_linux_parser::read_amd_graphics_adapters();

			if(count($adapters) > 0)
			{
				$video_ram = $video_ram > 64 ? " " . $video_ram . "MB" : null; // assume more than 64MB of vRAM

				if($crossfire_card_count > 1 && $crossfire_card_count <= count($adapters))
				{
					$unique_adapters = array_unique($adapters);

					if(count($unique_adapters) == 1)
					{
						if(strpos($adapters[0], "X2") > 0 && $crossfire_card_count > 1)
						{
							$crossfire_card_count -= 1;
						}

						$info = $crossfire_card_count . " x " . $adapters[0] . $video_ram . " CrossFire";
					}
					else
					{
						$info = implode(", ", $unique_adapters) . " CrossFire";
					}
				}
				else
				{
					$info = $adapters[0] . $video_ram;
				}
			}
		}
		else if(IS_NVIDIA_GRAPHICS)
		{
			$sli_mode = phodevi_parser::read_nvidia_extension("SLIMode");

			if(!empty($sli_mode) && $sli_mode != "Off")
			{
				$info .= " SLI";
			}
		}

		if(IS_SOLARIS)
		{
			if(($cut = strpos($info, "DRI ")) !== false)
			{
				$info = substr($info, ($cut + 4));
			}
			if(($cut = strpos($info, " Chipset")) !== false)
			{
				$info = substr($info, 0, $cut);
			}
		}
		else if(IS_BSD)
		{
			$drm_info = phodevi_bsd_parser::read_sysctl("dev.drm.0.%desc");

			if(!$drm_info)
			{
				$drm_info = phodevi_bsd_parser::read_sysctl("dev.nvidia.0.%desc");

				if($drm_info && stripos($drm_info, "NVIDIA") === false)
				{
					$drm_info = "NVIDIA " . $drm_info;
				}
			}

			if(!$drm_info)
			{
				$agp_info = phodevi_bsd_parser::read_sysctl("dev.agp.0.%desc");

				if($agp_info != false)
				{
					$info = $agp_info;
				}
			}
			else
			{
				$info = $drm_info;
			}

			if($info == null && is_readable("/var/log/Xorg.0.log"))
			{
				$xorg_log = file_get_contents("/var/log/Xorg.0.log");

				if(($e = strpos($xorg_log, " at 01@00:00:0")) !== false)
				{
					$xorg_log = substr($xorg_log, 0, $e);
					$info = substr($xorg_log, strrpos($xorg_log, "Found ") + 6);
				}
			}
		}
		else if(IS_WINDOWS)
		{
			$info = phodevi_windows_parser::read_cpuz("Display Adapters", "Name");
		}
	
		if(empty($info) || strpos($info, "Mesa ") !== false || $info == "Software Rasterizer")
		{
			$log_parse = shell_exec("cat /var/log/Xorg.0.log 2>&1 | grep Chipset");
			$log_parse = substr($log_parse, strpos($log_parse, "Chipset") + 8);
			$log_parse = substr($log_parse, 0, strpos($log_parse, "found"));

			if(strpos($log_parse, "(--)") === false && strlen(str_replace(array("ATI", "NVIDIA", "VIA", "Intel"), "", $log_parse)) != strlen($log_parse))
			{
				$info = $log_parse;
			}
			else
			{
				if(IS_LINUX)
				{
					$info_pci = phodevi_linux_parser::read_pci("VGA compatible controller", false);

					if(!empty($info_pci))
					{
						$info = $info_pci;
					}
				}

				if(($start_pos = strpos($info, " DRI ")) > 0)
				{
					$info = substr($info, $start_pos + 5);
				}

				if(substr($info, -1) == ")" && ($open_p = strrpos($info, "(")) != false)
				{
					$end_check = strpos($info, " ", $open_p);
					$to_check = substr($info, ($open_p + 1), ($end_check - $open_p - 1));

					// Don't report card revision from PCI info
					if($to_check == "rev")
					{
						$info = substr($info, 0, $open_p - 1);
					}
				}
			}
		}

		if($video_ram > 64 && strpos($info, $video_ram) == false) // assume more than 64MB of vRAM
		{
			$info .= " " . $video_ram . "MB";
		}
	
		$clean_phrases = array("OpenGL Engine");
		$info = str_replace($clean_phrases, "", $info);
		$info = pts_clean_information_string($info);

		return $info;
	}
}

?>
