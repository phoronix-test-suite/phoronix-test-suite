<?php

function xrandr_screen_resolution()
{
	$info = shell_exec("xrandr 2>&1");

	if(($pos = strrpos($info, "*")) != FALSE)
	{
		$info = substr($info, 0, $pos);
		$info = trim(substr($info, strrpos($info, "\n")));
		$info = substr($info, 0, strpos($info, " "));
		$info = explode("x", $info);
	}

	if($pos == FALSE || $info == "*0x" || empty($info))
		$info = array("Unknown", "Unknown");

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
		$info = parse_lspci_output("VGA compatible controller:");

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
	// Attempt NVIDIA (Binary Driver) Video RAM detection
	$info = shell_exec("nvidia-settings --query [gpu:0]/VideoRam 2>&1");
	$video_ram = 128;

	if(($pos = strpos($info, "VideoRam")) > 0)
	{
		$info = trim(substr($info, strpos($info, "):") + 3));
		$info = trim(substr($info, 0, strpos($info, "\n"))); // Double check in case the blob drops period or makes other change
		$info = trim(substr($info, 0, strpos($info, ".")));
		$video_ram = intval($info) / 1024;
	}
	else if(is_file("/var/log/Xorg.0.log"))
	{
		// Attempt ATI (Binary Driver) Video RAM detection
		$info = shell_exec("cat /var/log/Xorg.0.log | grep VideoRAM");
		// fglrx driver reports video memory to: (--) fglrx(0): VideoRAM: XXXXXX kByte, Type: DDRX
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
