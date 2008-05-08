<?php

function graphics_frequency_string()
{
	$freq = graphics_processor_frequency();
	$freq_string = $freq[0] . '/' . $freq[1];

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
function pts_record_gpu_temperature()
{
	global $GPU_TEMPERATURE;
	$temp = graphics_processor_temperature();

	if($temp != -1)
		array_push($GPU_TEMPERATURE, $temp);
}
function graphics_processor_temperature()
{
	$temp_c = read_nvidia_extension("GPUCoreTemp");

	if(empty($temp_c))
		$temp_c = -1;

	return $temp_c;
}
function graphics_antialiasing_level()
{
	$aa_level = "";

	$nvidia_fsaa = read_nvidia_extension("FSAA");

	if(!empty($nvidia_fsaa))
	{
		switch($nvidia_fsaa)
		{
			case 1:
				$aa_level = "2x Bilinear";
				break;
			case 5:
				$aa_level = "4x Bilinear";
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
	return $aa_level;
}
function graphics_anisotropic_level()
{
	$aa_level = "";

	$nvidia_fsaa = read_nvidia_extension("LogAniso");

	if(!empty($nvidia_fsaa))
	{
		switch($nvidia_fsaa)
		{
			case 1:
				$aa_level = "2x";
				break;
			case 2:
				$aa_level = "4x";
				break;
			case 3:
				$aa_level = "8x";
				break;
			case 4:
				$aa_level = "16x";
				break;
		}
	}
	return $aa_level;
}
function xrandr_screen_resolution()
{
	$info = shell_exec("xrandr 2>&1");

	if(($pos = strrpos($info, "*")) != FALSE)
	{
		$info = substr($info, 0, $pos);
		$info = trim(substr($info, strrpos($info, "\n")));
		$info = substr($info, 0, strpos($info, " "));
		$info = explode("x", $info);

		if(count($info) != 2 && (!is_int($info[0]) || !is_int($info[1])))
			$info = "";
	}

	if($pos == FALSE || empty($info))
	{
		if(($nvidia = read_nvidia_extension("FrontendResolution")) != "")
		{
			$info = explode(',', $nvidia);
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

	$freq = read_nvidia_extension("GPUDefault3DClockFreqs");

	if(!empty($freq)) // NVIDIA GPU
	{
		$freq = explode(',', $freq);
		$core_freq = $freq[0];
		$mem_freq = $freq[1];
	}

	return array($core_freq, $mem_freq);
}
function graphics_processor_frequency()
{
	$core_freq = 0;
	$mem_freq = 0;

	$freq = read_nvidia_extension("GPUCurrentClockFreqs");

	if(!empty($freq)) // NVIDIA GPU
	{
		$freq = explode(',', $freq);
		$core_freq = $freq[0];
		$mem_freq = $freq[1];
	}

	return array($core_freq, $mem_freq);
}
function graphics_processor_string()
{
	$info = shell_exec("glxinfo | grep renderer 2>&1");

	if(($pos = strpos($info, "renderer string:")) > 0)
	{
		$info = substr($info, $pos + 16);
		$info = trim(substr($info, 0, strpos($info, "\n")));
	}
	else
		$info = "";

	if(empty($info) || strpos($info, "Mesa GLX") !== FALSE || strpos($info, "Mesa DRI") !== FALSE)
		$info = read_pci("VGA compatible controller:");

	return $info;
}
function graphics_subsystem_version()
{
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

	if(($NVIDIA = read_nvidia_extension("VideoRam")) > 0) // NVIDIA blob
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

	return $video_ram;
}
function opengl_version()
{
	$info = shell_exec("glxinfo | grep version");

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
		if(is_file("/proc/dri/0/name"))
		{
			$driver_info = file_get_contents("/proc/dri/0/name");
			$driver_info = substr($driver_info, 0, strpos($driver_info, ' '));
			$info .= " ($driver_info)";
		}

	return $info;
}

?>
