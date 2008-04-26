<?php

//
// SYSTEM RELATED
//

require_once("pts-core/functions/pts-functions_system_cpu.php");
require_once("pts-core/functions/pts-functions_system_graphics.php");

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
function read_linux_sensors($attribute)
{
	$value = "";
	$sensors = shell_exec("sensors -U 2>&1");
	$sensors_lines = explode("\n", $sensors);

	for($i = 0; $i < count($sensors_lines) && $value == ""; $i++)
	{
		$line = explode(": ", $sensors_lines[$i]);
		$this_attribute = trim($line[0]);

		if($this_attribute == $attribute)
		{
			$this_remainder = trim(str_replace(array('+', '°'), ' ', $line[1]));
			$value = substr($this_remainder, 0, strpos($this_remainder, ' '));
		}
	}

	return $value;
}
function system_temperature()
{
	$temp_c = read_linux_sensors("Sys Temp");

	if(empty($temp_c))
		$temp_c = -1;

	return $temp_c;
}
function pts_record_sys_temperature()
{
	global $SYS_TEMPERATURE;
	$temp = system_temperature();

	if($temp != -1)
		array_push($SYS_TEMPERATURE, $temp);
}

?>
