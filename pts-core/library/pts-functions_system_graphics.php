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
	$supported_ratios = array(1.60, 1.25, 1.33, 1.70);
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

		if(is_array($od_clocks) && count($od_clocks) >= 2) // ATI OverDrive
		{
			$core_freq = array_shift($od_clocks);
			$mem_freq = array_pop($od_clocks);
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
