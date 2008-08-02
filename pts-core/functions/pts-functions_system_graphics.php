<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
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

function graphics_frequency_string()
{
	$freq = graphics_processor_frequency();
	$freq_string = $freq[0] . "/" . $freq[1];

	if($freq_string == "0/0")
	{
		$freq_string = "";
	}
	else
	{
		$freq_string = " (" . $freq_string . "MHz)";
	}

	return $freq_string;
}
function graphics_processor_temperature()
{
	$temp_c = -1;

	if(IS_NVIDIA_GRAPHICS)
	{
		$temp_c = read_nvidia_extension("GPUCoreTemp");
	}
	else if(IS_ATI_GRAPHICS)
	{
		$temp_c = read_ati_extension("CoreTemperature");
	}

	if(empty($temp_c) || !is_numeric($temp_c))
		$temp_c = -1;

	return $temp_c;
}
function graphics_antialiasing_level()
{
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

		if(!empty($ati_fsaa))
		{
			switch($ati_fsaa)
			{
				case "0x00000002":
					$aa_level = "2x";
					break;
				case "0x00000004":
					$aa_level = "4x";
					break;
				case "0x00000008":
					$aa_level = "8x";
					break;
			}
		}
	}

	return $aa_level;
}
function graphics_anisotropic_level()
{
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
function set_nvidia_extension($attribute, $value)
{
	if(!IS_NVIDIA_GRAPHICS)
		return;

	$info = shell_exec("nvidia-settings --assign " . $attribute . "=" . $value);
}
function set_amd_pcsdb($attribute, $value)
{
	if(!IS_ATI_GRAPHICS)
		return;

	if(!empty($value))
	{
		$DISPLAY = substr(getenv("DISPLAY"), 1, 1);
		
		$info = shell_exec("DISPLAY=:" . $DISPLAY . " aticonfig --set-pcs-val=" . $attribute . "," . $value . "  2>&1");
	}
}
function xrandr_screen_resolution()
{
	$info = shell_exec("xrandr 2>&1 | grep \"*\"");

	if(strpos($info, "*") !== FALSE)
	{
		$res = explode("x", $info);
		$res[0] = trim($res[0]);
		$res[1] = trim($res[1]);

		$res[0] = substr($res[0], strpos($res[0], " "));
		$res[1] = substr($res[1], 0, strpos($res[1], " "));

		if(is_numeric($res[0]) && is_numeric($res[1]))
		{
			$info = array();
			array_push($info, trim($res[0]), trim($res[1]));
		}
		else
			$info = "";
	}

	if(empty($info))
	{
		if(IS_NVIDIA_GRAPHICS && ($nvidia = read_nvidia_extension("FrontendResolution")) != "")
		{
			$info = explode(",", $nvidia);
		}
		else
			$info = array("Unknown", "Unknown");
	}

	return $info;
}
function current_screen_resolution()
{
	if(($width = current_screen_width()) != "Unknown" && ($height = current_screen_height()) != "Unknown")
		$resolution = $width . "x" . $height;
	else
		$resolution = "Unknown";

	return $resolution;
}
function current_screen_width()
{
	$resolution = xrandr_screen_resolution();
	return $resolution[0];
}
function current_screen_height()
{
	$resolution = xrandr_screen_resolution();
	return $resolution[1];
}
function graphics_processor_stock_frequency()
{
	$core_freq = 0;
	$mem_freq = 0;

	if(IS_NVIDIA_GRAPHICS) // NVIDIA GPU
	{
		$nv_freq = read_nvidia_extension("GPUDefault3DClockFreqs");

		$nv_freq = explode(',', $nv_freq);
		$core_freq = $nv_freq[0];
		$mem_freq = $nv_freq[1];
	}
	else if(IS_ATI_GRAPHICS) // ATI GPU
	{
		$ati_freq = read_ati_extension("Stock3DFrequencies");

		$ati_freq = explode(',', $ati_freq);
		$core_freq = $ati_freq[0];
		$mem_freq = $ati_freq[1];
	}

	if(!is_numeric($core_freq))
		$core_freq = 0;
	if(!is_numeric($mem_freq))
		$mem_freq = 0;

	return array($core_freq, $mem_freq);
}
function graphics_processor_frequency()
{
	$core_freq = 0;
	$mem_freq = 0;

	if(IS_NVIDIA_GRAPHICS) // NVIDIA GPU
	{
		$nv_freq = read_nvidia_extension("GPUCurrentClockFreqs");

		$nv_freq = explode(',', $nv_freq);
		$core_freq = $nv_freq[0];
		$mem_freq = $nv_freq[1];
	}
	else if(IS_ATI_GRAPHICS) // ATI GPU
	{
		$ati_freq = read_ati_extension("Current3DFrequencies");

		$ati_freq = explode(',', $ati_freq);
		$core_freq = $ati_freq[0];
		$mem_freq = $ati_freq[1];
	}

	if(!is_numeric($core_freq))
		$core_freq = 0;
	if(!is_numeric($mem_freq))
		$mem_freq = 0;

	return array($core_freq, $mem_freq);
}
function graphics_processor_string()
{
	$info = shell_exec("glxinfo 2>&1 | grep renderer");

	if(($pos = strpos($info, "renderer string:")) > 0)
	{
		$info = substr($info, $pos + 16);
		$info = trim(substr($info, 0, strpos($info, "\n")));
	}
	else
		$info = "";

	if(empty($info) || strpos($info, "Mesa ") !== FALSE)
	{
		$info_pci = read_pci("VGA compatible controller:");

		if(!empty($info_pci) && $info_pci != "Unknown")
			$info = $info_pci;
	}

	if($info == "Unknown")
	{
		$log_parse = shell_exec("cat /var/log/Xorg.0.log | grep Chipset");
		$log_parse = substr($log_parse, strpos($log_parse, "Chipset") + 8);
		$log_parse = substr($log_parse, 0, strpos($log_parse, "found"));

		if(strpos($log_parse, "ATI") !== FALSE || strpos($log_parse, "NVIDIA") !== FALSE || strpos($log_parse, "VIA") !== FALSE || strpos($log_parse, "Intel") !== FALSE)
			$info = $log_parse;
	}

	if(IS_BSD && $info == "Unknown")
	{
		$info = read_sysctl("dev.drm.0.%desc");

		if($info == "Unknown")
			$info = read_sysctl("dev.agp.0.%desc");
	}

	$info = pts_clean_information_string($info);

	return $info;
}
function graphics_subsystem_version()
{
	if(IS_SOLARIS)
		$info = shell_exec("X :0 -version 2>&1");
	else
		$info = shell_exec("X -version 2>&1");

	$pos = strrpos($info, "Release Date");
	$info = trim(substr($info, 0, $pos));

	if($pos === FALSE)
	{
		$info = "Unknown";
	}
	else if(($pos = strrpos($info, "(")) === FALSE)
	{
		$info = trim(substr($info, strrpos($info, " ")));
	}
	else
	{
		$info = trim(substr($info, strrpos($info, "Server") + 6));
	}

	return $info;
}
function graphics_memory_capacity()
{
	$video_ram = 128;

	if(($vram = getenv("VIDEO_MEMORY")) != FALSE && is_numeric($vram) && $vram > 128)
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
			// Attempt ATI (Binary Driver) Video RAM detection
			// fglrx driver reports video memory to: (--) fglrx(0): VideoRAM: XXXXXX kByte, Type: DDR

			$info = shell_exec("cat /var/log/Xorg.0.log | grep VideoRAM");
			if(($pos = strpos($info, "VideoRAM:")) > 0)
			{
				$info = substr($info, $pos + 10);
				$info = substr($info, 0, strpos($info, ' '));
				$video_ram = intval($info) / 1024;
			}
		}
	}

	if(IS_BSD)
		$video_ram = 128;

	return $video_ram;
}
function opengl_version()
{
	$info = shell_exec("glxinfo 2>&1 | grep version");

	if(($pos = strpos($info, "OpenGL version string:")) === FALSE)
	{
		$info = "N/A";
	}
	else
	{
		$info = substr($info, $pos + 23);
		$info = trim(substr($info, 0, strpos($info, "\n")));
		$info = str_replace(array(" Release"), "", $info);
	}

	if(str_replace(array("NVIDIA", "ATI", "AMD", "Radeon", "Intel"), "", $info) == $info)
	{
		if(is_file("/proc/dri/0/name"))
		{
			$driver_info = file_get_contents("/proc/dri/0/name");
			$driver_info = substr($driver_info, 0, strpos($driver_info, ' '));
			$info .= " ($driver_info)";
		}
	}

	return $info;
}
function graphics_gpu_usage()
{
	$gpu_usage = 0;

	if(IS_ATI_GRAPHICS)
		$gpu_usage = read_ati_extension("GPUActivity");

	return $gpu_usage;
}

?>
