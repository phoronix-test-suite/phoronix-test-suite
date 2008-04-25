<?php


//
// SYSTEM RELATED
//

function pts_process_running_string($process_arr)
{
	$p = array();
	$p_string = "";

	if(!is_array($process_arr))
		$process_arr = array($process_arr);

	foreach($process_arr as $process)
		if(pts_process_running_bool($process))
			array_push($p, $process);

	if(($p_count = count($p)) > 0)
	{
		for($i = 0; $i < $p_count; $i++)
		{
			$p_string .= $p[$i];

			if($i != ($p_count - 1) && $p_count > 2)
				$p_string .= ",";
			$p_string .= " ";

			if($i == ($p_count - 2))
				$p_string .= "and ";
		}

		if($p_count == 1)
			$p_string .= "was";
		else
			$p_string .= "were";

		$p_string .= " running on this system. ";
	}

	return $p_string;
}
function pts_process_running_bool($process)
{
	$running = shell_exec("ps -C " . strtolower($process));
	$running = trim(str_replace(array("PID", "TTY", "TIME", "CMD"), "", $running));

	if(!empty($running))
		$running = true;
	else
		$running = false;

	return $running;
}
function pts_posix_username()
{
	$userinfo = posix_getpwuid(posix_getuid());
	return $userinfo["name"];
}
function pts_posix_userhome()
{
	$userinfo = posix_getpwuid(posix_getuid());
	return $userinfo["dir"] . '/';
}
function pts_posix_disk_total()
{
	return ceil(disk_total_space("/") / 1073741824);
}
function cpu_core_count()
{
	if(is_file("/proc/cpuinfo"))
	{
		$info = file_get_contents("/proc/cpuinfo");
		$info = substr($info, strrpos($info, "\nprocessor"));
		$info = trim(substr($info, strpos($info, ":") + 1, strpos($info, "\n") - strpos($info, ":")));
	}
	else
		$info = 0;

	return intval($info) + 1;
}
function cpu_job_count()
{
	return cpu_core_count() + 1;
}
function processor_string()
{
	if(is_file("/proc/cpuinfo"))
	{
		$info = file_get_contents("/proc/cpuinfo");
		$info = substr($info, strpos($info, "model name"));
		$info = trim(substr($info, strpos($info, ":") + 1, strpos($info, "\n") - strpos($info, ":")));
		$info = pts_clean_information_string($info);
	}
	else
		$info = "Unknown";

	if(($freq = processor_frequency()) > 0)
	{
		if(($strip_point = strpos($info, '@')) > 0)
			$info = trim(substr($info, 0, $strip_point)); // stripping out the reported freq, since the CPU could be overclocked

		$info .= " @ " . $freq . "GHz";
	}

	return $info;
}
function processor_frequency()
{

	if(is_file("/sys/devices/system/cpu/cpu0/cpufreq/scaling_max_freq")) // The ideal way, with modern CPUs using CnQ or EIST and cpuinfo reporting the current
	{
		$info = trim(file_get_contents("/sys/devices/system/cpu/cpu0/cpufreq/scaling_max_freq"));
		$info = pts_trim_double(intval($info) / 1000000, 2);
	}
	else if(is_file("/proc/cpuinfo")) // fall back for those without cpufreq
	{
		$info = file_get_contents("/proc/cpuinfo");
		$info = substr($info, strpos($info, "\ncpu MHz"));
		$info = trim(substr($info, strpos($info, ":") + 1, strpos($info, "\n") - strpos($info, ":")));
		$info = pts_trim_double(intval($info) / 1000, 2);
	}
	else
		$info = 0;

	return $info;
}
function memory_mb_capacity()
{
	if(is_file("/proc/meminfo"))
	{
		$info = file_get_contents("/proc/meminfo");
		$info = substr($info, strpos($info, "MemTotal:") + 9);
		$info = intval(trim(substr($info, 0, strpos($info, "kB"))));
		$info = floor($info / 1024);
	}
	else
		$info = "Unknown";

	return $info;
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
	}

	if($pos == FALSE || $info == "*0x" || empty($info))
		$info = array("Unknown", "Unknown");

	return $info;
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
function current_screen_resolution()
{
	if(($width = current_screen_width()) != "Unknown" && ($height = current_screen_height()) != "Unknown")
		$resolution = $width . "x" . $height;
	else
		$resolution = "Unknown";

	return $resolution;
}
function parse_lsb_output($desc)
{

	$info = shell_exec("lsb_release -a 2>&1");

	if(($pos = strrpos($info, $desc . ':')) === FALSE)
	{
		$info = "Unknown";
	}
	else
	{
		$info = substr($info, $pos + strlen($desc) + 1);
		$info = trim(substr($info, 0, strpos($info, "\n")));
	}

	return $info;
}
function os_vendor()
{
	return parse_lsb_output("Distributor ID");
}
function os_version()
{
	return parse_lsb_output("Release");
}
function kernel_string()
{
	return trim(shell_exec("uname -r"));
}
function kernel_arch()
{
	return trim(shell_exec("uname -m"));
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
function motherboard_chipset_string()
{
	return parse_lspci_output("Host bridge:");
}
function parse_lspci_output($desc)
{
	$info = shell_exec("lspci 2>&1");

	if(($pos = strpos($info, $desc)) === FALSE)
	{
		$info = "Unknown";
	}
	else
	{
		$info = substr($info, $pos + strlen($desc));
		$EOL = strpos($info, "\n");

		if(($temp = strpos($info, '/')) < $EOL && $temp > 0)
			if(($temp = strpos($info, ' ', ($temp + 2))) < $EOL && $temp > 0)
				$EOL = $temp;

		if(($temp = strpos($info, '(')) < $EOL && $temp > 0)
			$EOL = $temp;

		if(($temp = strpos($info, '[')) < $EOL && $temp > 0)
			$EOL = $temp;

		$info = trim(substr($info, 0, $EOL));

		if(($strlen = strlen($info)) < 6 || $strlen > 96)
			$info = "N/A";
		else
			$info = pts_clean_information_string($info);
	}

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
function compiler_version()
{
	$info = shell_exec("gcc -v 2>&1");

	if(($pos = strpos($info, "gcc version")) === FALSE)
	{
		$info = "N/A";
	}
	else
	{
		$info = substr($info, $pos + 11);
		$info = trim(substr($info, 0, strpos($info, " ", strpos($info, "."))));
		$info = "GCC " . $info;
	}

	return $info;
}
function operating_system_release()
{
	$vendor = os_vendor();
	$version = os_version();

	if($vendor == "Unknown" && $version == "Unknown")
	{
		$os = "Unknown";

		// Try to detect distro for those not supplying lsb_release
		$files = glob("/etc/*-version");
		if(count($files) > 0)
		{
			$file = file_get_contents($files[0]);
			$os = substr($file, 0, strpos($file, "\n"));
		}

		if($os == "Unknown")
		{
			$files = glob("/etc/*-release");
			if(count($files) > 0)
			{
				$file = file_get_contents($files[0]);
				$os = substr($file, 0, strpos($file, "\n"));
			}
		}
	}
	else
		$os = $vendor . " " . $version;

	return $os;
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
