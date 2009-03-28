<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
	pts-functions_system_graphics.php: System functions related to graphics.

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

define("DEFAULT_VIDEO_RAM_CAPACITY", 128);

function hw_gpu_temperature()
{
	// Report graphics processor temperature
	if(IS_NVIDIA_GRAPHICS)
	{
		$temp_c = read_nvidia_extension("GPUCoreTemp");
	}
	else if(IS_ATI_GRAPHICS)
	{
		$temp_c = read_ati_overdrive("Temperature");
	}
	else
	{
		$temp_c = false;
	}

	return (is_numeric($temp_c) ? $temp_c : -1);
}
function hw_gpu_monitor_count()
{
	// Report number of connected/enabled monitors
	$monitor_count = 0;

	// First try reading number of monitors from xdpyinfo
	$monitor_count = count(read_xdpy_monitor_info());

	if($monitor_count == 0)
	{
		// Fallback support for ATI and NVIDIA if read_xdpy_monitor_info() fails
		if(IS_NVIDIA_GRAPHICS)
		{
			$enabled_displays = read_nvidia_extension("EnabledDisplays");

			switch($enabled_displays)
			{
				case "0x00010000":
					$monitor_count = 1;
					break;
				case "0x00010001":
					$monitor_count = 2;
					break;
				default:
					$monitor_count = 1;
					break;
			}
		}
		else if(IS_ATI_GRAPHICS)
		{
			$amdpcsdb_enabled_monitors = read_amd_pcsdb("SYSTEM/BUSID-*/DDX,EnableMonitor");
			$amdpcsdb_enabled_monitors = pts_to_array($amdpcsdb_enabled_monitors);

			foreach($amdpcsdb_enabled_monitors as $enabled_monitor)
			{
				foreach(explode(",", $enabled_monitor) as $monitor_connection)
				{
					$monitor_count++;
				}
			}
		}
		else
		{
			$monitor_count = 1;
		}
	}

	return $monitor_count;
}
function hw_gpu_monitor_layout()
{
	// Determine layout for multiple monitors
	$monitor_layout = array("CENTER");

	if(hw_gpu_monitor_count() > 1)
	{
		$xdpy_monitors = read_xdpy_monitor_info();
		$hit_0_0 = false;
		for($i = 0; $i < count($xdpy_monitors); $i++)
		{
			$monitor_position = explode("@", $xdpy_monitors[$i]);
			$monitor_position = trim($monitor_position[1]);
			$monitor_position_x = substr($monitor_position, 0, strpos($monitor_position, ","));
			$monitor_position_y = substr($monitor_position, strpos($monitor_position, ",") + 1);

			if($monitor_position == "0,0")
			{
				$hit_0_0 = true;
			}
			else if($monitor_position_x > 0 && $monitor_position_y == 0)
			{
				array_push($monitor_layout, ($hit_0_0 ? "RIGHT" : "LEFT"));
			}
			else if($monitor_position_x == 0 && $monitor_position_y > 0)
			{
				array_push($monitor_layout, ($hit_0_0 ? "LOWER" : "UPPER"));
			}
		}

		if(count($monitor_layout) == 1)
		{
			// Something went wrong with xdpy information, go to fallback support
			if(IS_ATI_GRAPHICS)
			{
				$amdpcsdb_monitor_layout = read_amd_pcsdb("SYSTEM/BUSID-*/DDX,DesktopSetup");
				$amdpcsdb_monitor_layout = pts_to_array($amdpcsdb_monitor_layout);

				foreach($amdpcsdb_monitor_layout as $card_monitor_configuration)
				{
					switch($card_monitor_configuration)
					{
						case "horizontal":
							array_push($monitor_layout, "RIGHT");
							break;
						case "horizontal,reverse":
							array_push($monitor_layout, "LEFT");
							break;
						case "vertical":
							array_push($monitor_layout, "ABOVE");
							break;
						case "vertical,reverse":
							array_push($monitor_layout, "BELOW");
							break;
					}
				}
			}
		}
	}

	return implode(",", $monitor_layout);
}
function hw_gpu_monitor_modes()
{
	// Determine resolutions for each monitor
	$resolutions = array();

	if(hw_gpu_monitor_count() == 1)
	{
		array_push($resolutions, hw_gpu_current_mode());
	}
	else
	{
		foreach(read_xdpy_monitor_info() as $monitor_line)
		{
			$this_resolution = substr($monitor_line, strpos($monitor_line, ":") + 2);
			$this_resolution = substr($this_resolution, 0, strpos($this_resolution, " "));
			array_push($resolutions, $this_resolution);
		}
	}

	return implode(",", $resolutions);
}
function hw_gpu_aa_level()
{
	// Determine AA level if over-rode
	$aa_level = "";

	if(IS_NVIDIA_GRAPHICS)
	{
		$nvidia_fsaa = read_nvidia_extension("FSAA");

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
	else if(IS_ATI_GRAPHICS)
	{
		$ati_fsaa = read_amd_pcsdb("OpenGL,AntiAliasSamples");
		$ati_fsaa_filter = read_amd_pcsdb("OpenGL,AAF");

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
function hw_gpu_af_level()
{
	// Determine AF level if over-rode
	$af_level = "";

	if(IS_NVIDIA_GRAPHICS)
	{
		$nvidia_af = read_nvidia_extension("LogAniso");

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
	else if(IS_ATI_GRAPHICS)
	{
		$ati_af = read_amd_pcsdb("OpenGL,AnisoDegree");

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
function hw_gpu_set_resolution($width, $height)
{
	shell_exec("xrandr -s " . $width . "x" . $height . " 2>&1");

	return hw_gpu_screen_resolution() == array($width, $height); // Check if video resolution set worked
}
function hw_gpu_set_nvidia_extension($attribute, $value)
{
	// Sets an object in NVIDIA's NV Extension
	if(IS_NVIDIA_GRAPHICS)
	{
		shell_exec("nvidia-settings --assign " . $attribute . "=" . $value . " 2>&1");
	}
}
function hw_gpu_set_amd_pcsdb($attribute, $value)
{
	// Sets a value for AMD's PCSDB, Persistent Configuration Store Database
	if(IS_ATI_GRAPHICS && !empty($value))
	{
		$DISPLAY = substr(getenv("DISPLAY"), 1, 1);
		$info = shell_exec("DISPLAY=:" . $DISPLAY . " aticonfig --set-pcs-val=" . $attribute . "," . $value . "  2>&1");
	}
}
function hw_gpu_available_modes()
{
	// XRandR available modes
	$available_modes = array();
	$supported_ratios = array(1.60, 1.25, 1.33);
	$ignore_modes = array(array(832, 624), array(1152, 864), array(1792, 1344), array(1800, 1440), array(1856, 1392), array(2048, 1536));

	$info = shell_exec("xrandr 2>&1");
	$xrandr_lines = array_reverse(explode("\n", $info));

	foreach($xrandr_lines as $xrandr_mode)
	{
		if(($cut_point = strpos($xrandr_mode, "(")) > 0)
		{
			$xrandr_mode = substr($xrandr_mode, 0, $cut_point);
		}

		$res = array_map("trim", explode("x", $xrandr_mode));

		if(count($res) == 2)
		{
			$res[0] = substr($res[0], strrpos($res[0], " "));
			$res[1] = substr($res[1], 0, strpos($res[1], " "));

			if(is_numeric($res[0]) && is_numeric($res[1]) && $res[0] >= 800 && $res[1] >= 600)
			{
				$ratio = pts_trim_double($res[0] / $res[1], 2);
				$this_mode = array($res[0], $res[1]);

				if(in_array($ratio, $supported_ratios) && !in_array($this_mode, $ignore_modes))
				{
					array_push($available_modes, $this_mode);
				}
			}
		}
	}

	if(count($available_modes) < 2)
	{
		$stock_modes = array(array(800, 600), array(1024, 768), array(1280, 1024), array(1280, 960), 
		array(1400, 1050), array(1680, 1050), array(1600, 1200), array(1920, 1080), array(2560, 1600));
		$available_modes = array();

		$current_resolution = hw_gpu_screen_resolution();

		for($i = 0; $i < count($stock_modes); $i++)
		{
			if($stock_modes[$i][0] <= $current_resolution[0] && $stock_modes[$i][1] <= $current_resolution[1])
			{
				array_push($available_modes, $stock_modes[$i]);
			}
		}
	}
	else
	{
		// Sort available modes in order
		$modes = $available_modes;
		$mode_pixel_counts = array();
		$sorted_modes = array();

		foreach($modes as $this_mode)
		{
			if(count($this_mode) == 2)
			{
				array_push($mode_pixel_counts, $this_mode[0] * $this_mode[1]);
			}
			else
			{
				unset($this_mode);
			}
		}

		sort($mode_pixel_counts);

		for($i = 0; $i < count($mode_pixel_counts); $i++)
		{
			$hit = false;
			for($j = 0; $j < count($modes) && !$hit; $j++)
			{
				if($modes[$j] != null && ($modes[$j][0] * $modes[$j][1]) == $mode_pixel_counts[$i])
				{
					array_push($sorted_modes, $modes[$j]);
					$modes[$j] = null;
					$hit = true;
				}
			}
		}

		$available_modes = $sorted_modes;
	}

	return $available_modes;
}
function hw_gpu_xrandr_mode()
{
	// Find the current screen resolution using XRandR
	$info = shell_exec("xrandr 2>&1 | grep \"*\"");

	if(strpos($info, "*") !== false)
	{
		$res = array_map("trim", explode("x", $info));
		$res[0] = substr($res[0], strrpos($res[0], " "));
		$res[1] = substr($res[1], 0, strpos($res[1], " "));

		$info = (is_numeric($res[0]) && is_numeric($res[1]) ? array($res[0], $res[1]) : null);
	}
	else
	{
		$info = null;
	}

	if($info == null)
	{
		if(IS_NVIDIA_GRAPHICS && ($nvidia = read_nvidia_extension("FrontendResolution")) != "")
		{
			$info = explode(",", $nvidia);
		}
		else
		{
			$info = array(-1, -1);
		}
	}

	return $info;
}
function hw_gpu_current_mode()
{
	// Return the current screen resolution
	if(($width = hw_gpu_screen_width()) != -1 && ($height = hw_gpu_screen_height()) != -1)
	{
		$resolution = $width . "x" . $height;
	}
	else
	{
		$resolution = "Unknown";
	}

	return $resolution;
}
function hw_gpu_enabled_monitors()
{
	if(IS_MACOSX)
	{
		$system_profiler = shell_exec("system_profiler SPDisplaysDataType 2>&1");
		$system_profiler = substr($system_profiler, strrpos($system_profiler, "Displays:"));
		$system_profiler = substr($system_profiler, strpos($system_profiler, "\n"));
		$monitor = trim(substr($system_profiler, 0, strpos($system_profiler, ":")));
	}
	else
	{
		$log_parse = shell_exec("cat /var/log/Xorg.0.log 2>&1 | grep \"Monitor name\"");
		$log_parse = substr($log_parse, strpos($log_parse, "Monitor name:") + 14);
		$monitor = trim(substr($log_parse, 0, strpos($log_parse, "\n")));
	}

	return (empty($monitor) ? false : $monitor);
}
function hw_gpu_screen_resolution()
{
	if(IS_MACOSX)
	{
		$resolution = array();
		$info = read_osx_system_profiler("SPDisplaysDataType", "Resolution");
		$info = array_map("trim", explode(" ", $info));
		$resolution[0] = $info[0];
		$resolution[1] = $info[2];
	}
	else
	{
		$resolution = hw_gpu_xrandr_mode();
	}

	return $resolution;
}
function hw_gpu_screen_width()
{
	// Current screen width
	return array_shift(hw_gpu_screen_resolution());
}
function hw_gpu_screen_height()
{
	// Current screen height	
	return array_pop(hw_gpu_screen_resolution());
}
function hw_gpu_stock_frequency()
{
	// Graphics processor stock frequency
	$core_freq = 0;
	$mem_freq = 0;

	if(IS_NVIDIA_GRAPHICS) // NVIDIA GPU
	{
		$nv_freq = read_nvidia_extension("GPUDefault3DClockFreqs");

		$nv_freq = explode(",", $nv_freq);
		$core_freq = $nv_freq[0];
		$mem_freq = $nv_freq[1];
	}
	else if(IS_ATI_GRAPHICS) // ATI GPU
	{
		$od_clocks = read_ati_overdrive("CurrentPeak");

		if(is_array($od_clocks) && count($od_clocks) == 2) // ATI OverDrive
		{
			$core_freq = $od_clocks[0];
			$mem_freq = $od_clocks[1];
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
function hw_gpu_current_frequency($show_memory = true)
{
	// Graphics processor real/current frequency
	$core_freq = 0;
	$mem_freq = 0;

	if(IS_NVIDIA_GRAPHICS) // NVIDIA GPU
	{
		$nv_freq = read_nvidia_extension("GPUCurrentClockFreqs");

		$nv_freq = explode(",", $nv_freq);
		$core_freq = $nv_freq[0];
		$mem_freq = $nv_freq[1];
	}
	else if(IS_ATI_GRAPHICS) // ATI GPU
	{
		$od_clocks = read_ati_overdrive("CurrentClocks");

		if(is_array($od_clocks) && count($od_clocks) == 2) // ATI OverDrive
		{
			$core_freq = $od_clocks[0];
			$mem_freq = $od_clocks[1];
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

	return ($show_memory ? array($core_freq, $mem_freq) : $core_freq);
}
function hw_gpu_string($append_frequency = false)
{
	// Report graphics processor string
	$info = shell_exec("glxinfo 2>&1 | grep renderer");
	$video_ram = hw_gpu_memory_size();

	if(($pos = strpos($info, "renderer string:")) > 0)
	{
		$info = substr($info, $pos + 16);
		$info = trim(substr($info, 0, strpos($info, "\n")));
	}
	else
	{
		$info = "";
	}

	if(IS_ATI_GRAPHICS)
	{
		$crossfire_status = read_amd_pcsdb("SYSTEM/Crossfire/chain/*,Enable");
		$crossfire_status = pts_to_array($crossfire_status);
		$crossfire_card_count = 0;

		for($i = 0; $i < count($crossfire_status); $i++)
		{
			if($crossfire_status[$i] == "0x00000001")
			{
				$crossfire_card_count += 2; // For now assume each chain is 2 cards, but proper way would be NumSlaves + 1
			}
		}			

		$adapters = read_amd_graphics_adapters();

		if(count($adapters) > 0)
		{
			$video_ram = ($video_ram > DEFAULT_VIDEO_RAM_CAPACITY ? " " . $video_ram . "MB" : "");

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
		$sli_mode = read_nvidia_extension("SLIMode");

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

		$info = $info;
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
			$info_pci = read_pci("VGA compatible controller", false);

			if(!empty($info_pci))
			{
				$info = $info_pci;
			}

			if(($start_pos = strpos($info, " DRI ")) > 0)
			{
				$info = substr($info, $start_pos + 5);
			}
		}
	}

	if(IS_BSD && $info == "Unknown")
	{
		$info = read_sysctl("dev.drm.0.%desc");

		if(empty($info))
		{
			$info = read_sysctl("dev.agp.0.%desc");
		}
		if(empty($info))
		{
			$info = "Unknown";
		}
	}

	if(IS_NVIDIA_GRAPHICS && $video_ram > DEFAULT_VIDEO_RAM_CAPACITY && strpos($info, $video_ram) == false)
	{
		$info .= " " . $video_ram . "MB";
	}
	
	$clean_phrases = array("OpenGL Engine");
	$info = str_replace($clean_phrases, "", $info);
	$info = pts_clean_information_string($info);
	
	if(IS_MACOSX)
	{
		$info .= " " . $video_ram . "MB";
	}

	if($append_frequency)
	{
		$freq = (IS_ATI_GRAPHICS ? hw_gpu_stock_frequency() : hw_gpu_current_frequency());
		$freq_string = $freq[0] . "/" . $freq[1];

		$info .= ($freq_string == "0/0" ? "" : " (" . $freq_string . "MHz)");
	}

	return $info;
}
function hw_gpu_memory_size()
{
	// Graphics memory capacity
	$video_ram = DEFAULT_VIDEO_RAM_CAPACITY;

	if(($vram = getenv("VIDEO_MEMORY")) != false && is_numeric($vram) && $vram > DEFAULT_VIDEO_RAM_CAPACITY)
	{
		$video_ram = $vram;
	}
	else
	{
		if(IS_NVIDIA_GRAPHICS && ($NVIDIA = read_nvidia_extension("VideoRam")) > 0) // NVIDIA blob
		{
			$video_ram = $NVIDIA / 1024;
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

			if(($pos = strpos($info, "RAM:")) > 0 || ($pos = strpos($info, "Ram:")) > 0)
			{
				$info = substr($info, $pos + 5);
				$info = substr($info, 0, strpos($info, " "));

				if($info > 65535)
				{
					$video_ram = intval($info) / 1024;
				}
			}
		}
		else if(IS_MACOSX)
		{
			$info = read_osx_system_profiler("SPDisplaysDataType", "VRAM");
			$info = explode(" ", $info);
			$video_ram = $info[0];
			
			if($info[1] == "GB")
			{
				$video_ram *= 1024;
			}
		}
	}

	return $video_ram;
}
function hw_gpu_2d_accel_method()
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
function hw_gpu_core_usage()
{
	// Determine GPU usage
	$gpu_usage = -1;

	if(IS_ATI_GRAPHICS)
	{
		$gpu_usage = read_ati_overdrive("GPUload");
	}

	return $gpu_usage;
}

?>
