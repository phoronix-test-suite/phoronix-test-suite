<?php

/*
	Phoronix Test Suite "Trondheim"
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts-functions_system.php: System-level (Linux) functions, includes the parsing of the installed system hardware/software.

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

require_once("pts-core/functions/pts-functions_system_parsing.php");
require_once("pts-core/functions/pts-functions_system_cpu.php");
require_once("pts-core/functions/pts-functions_system_graphics.php");

function pts_process_running_string($process_arr)
{
	$p = array();
	$p_string = "";

	if(!is_array($process_arr))
		$process_arr = array($process_arr);

	foreach($process_arr as $p_name => $p_process)
	{
		if(!is_array($p_process))
			$p_process = array($p_process);

		foreach($p_process as $process)
			if(pts_process_running_bool($process))
				array_push($p, $p_name);
	}

	$p = array_keys(array_flip($p));

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
function pts_user_name()
{
	if(function_exists("posix_getpwuid") && function_exists("posix_getuid"))
	{
		$userinfo = posix_getpwuid(posix_getuid());
		$username = $userinfo["name"];
	}
	else
	{
		$username = getenv("USERNAME");
	}

	return $username;
}
function pts_user_home()
{
	if(function_exists("posix_getpwuid") && function_exists("posix_getuid"))
	{
		$userinfo = posix_getpwuid(posix_getuid());
		$userhome = $userinfo["dir"];
	}
	else
	{
		$userhome = getenv("HOME");
	}

	return $userhome . '/';
}
function pts_disk_total()
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
function os_vendor()
{
	return read_lsb("Distributor ID");
}
function os_version()
{
	return read_lsb("Release");
}
function kernel_string()
{
	return trim(shell_exec("uname -r"));
}
function kernel_arch()
{
	$kernel_arch = trim(shell_exec("uname -m"));

	if($kernel_arch == "X86-64")
		$kernel_arch = "x86_64";

	return $kernel_arch;
}
function motherboard_chipset_string()
{
	return read_pci("Host bridge:");
}
function compiler_version()
{
	$info = shell_exec("gcc -dumpversion 2>&1");
	$gcc_info = "N/A";

	if(strpos($info, '.') !== FALSE)
		$gcc_info = "GCC " . trim($info);

	return $gcc_info;
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
function system_temperature()
{
	$temp_c = read_sensors("Sys Temp");

	if(empty($temp_c))
		$temp_c = read_sensors("Board Temp");

	if(empty($temp_c))
	{
		$temp_c = read_acpi("/thermal_zone/THM1/temperature", "temperature"); // if it is THM1 that is for the system, in most cases it should be

		if(($end = strpos($temp_c, ' ')) > 0)
			$temp_c = substr($temp_c, 0, $end);
	}

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
function system_line_voltage($type)
{
	if($type == "CPU")
		$voltage = read_sensors("VCore");
	else if($type == "V3")
		$voltage = read_sensors("V3.3");
	else if($type == "V5")
		$voltage = read_sensors("V5");
	else if($type == "V12")
		$voltage = read_sensors("V12");
	else
		$voltage = "";

	if(empty($voltage))
		$voltage = -1;

	return $voltage;
}
function pts_record_cpu_voltage()
{
	global $CPU_VOLTAGE;
	$voltage = system_line_voltage("CPU");

	if($voltage != -1)
		array_push($CPU_VOLTAGE, $voltage);
}
function pts_record_v3_voltage()
{
	global $V3_VOLTAGE;
	$voltage = system_line_voltage("V3");

	if($voltage != -1)
		array_push($V3_VOLTAGE, $voltage);
}
function pts_record_v5_voltage()
{
	global $V5_VOLTAGE;
	$voltage = system_line_voltage("V5");

	if($voltage != -1)
		array_push($V5_VOLTAGE, $voltage);
}
function pts_record_v12_voltage()
{
	global $V12_VOLTAGE;
	$voltage = system_line_voltage("V12");

	if($voltage != -1)
		array_push($V12_VOLTAGE, $voltage);
}
function main_system_hardware_string()
{
	$vendor = read_system_hal("system.hardware.vendor");
	$product = read_system_hal("system.hardware.product");
	$version = read_system_hal("system.hardware.version");

	if($product == "Unknown" || $product == "Not Applicable" || (strpos($version, '.') === FALSE && $version != "Unknown"))
		$product = $version;

	if($vendor == "Unknown" || $vendor == "System manufacturer" || $vendor == "To Be Filled By O.E.M." || $vendor == "Not Applicable")
	{
		$info = "Unknown";
	}
	else
	{
		$info = trim(pts_clean_information_string($vendor . " " . $product));
	}

	return $info;
}
function pts_record_battery_power()
{
	global $BATTERY_POWER;
	$state = read_acpi("/battery/BAT0/state", "charging state");
	$power = read_acpi("/battery/BAT0/state", "present rate");

	if($state == "discharging")
	{
		if(($end = strpos($power, ' ')) > 0)
			$power = substr($power, 0, $end);

		if(!empty($power))
			array_push($BATTERY_POWER, $power);
	}
}
function pts_report_power_mode()
{
	$power_state = read_acpi("/ac_adapter/AC/state", "state");
	$return_status = "";

	if($power_state == "off-line")
		$return_status = "This computer was running on battery power.";

	return $return_status;
}
function pts_report_virtualized_mode()
{
	$virtualized = "";
	$gpu = graphics_processor_string();

	if(strpos(processor_string(), "QEMU") !== FALSE)
		$virtualized = "QEMU";
	else if(strpos($gpu, "VMware") !== FALSE)
		$virtualized = "VMware";
	else if(strpos($gpu, "VirtualBox") !== FALSE)
		$virtualized = "VirtualBox";

	if(!empty($virtualized))
		$virtualized = "This system is using " . $virtualized . " virtualization.";

	return $virtualized;
}
function filesystem_type()
{
	$fs = shell_exec("stat " . BENCHMARK_ENV_DIR . " -L -f -c %T 2> /dev/null");

	if(empty($fs))
		return "Unknown";

	return $fs;
}
function pts_hw_string()
{
	$hw_string = "Processor: " . processor_string() . " (Total Cores: " . cpu_core_count() . "), ";
	$hw_string .= "Motherboard: " . main_system_hardware_string() . ", ";
	$hw_string .= "Chipset: " . motherboard_chipset_string() . ", ";
	$hw_string .= "System Memory: " . memory_mb_capacity() . "MB, ";
	$hw_string .= "Disk Space: " . pts_disk_total() . "GB, ";
	$hw_string .= "Graphics: " . graphics_processor_string() . graphics_frequency_string() . ", ";
	$hw_string .= "Screen Resolution: " . current_screen_resolution() . " ";

	return $hw_string;
}
function pts_sw_string()
{
	$sw_string = "OS: " . operating_system_release() . ", ";
	$sw_string .= "Kernel: " . kernel_string() . " (" . kernel_arch() . "), ";
	$sw_string .= "X.Org Server: " . graphics_subsystem_version() . ", ";
	$sw_string .= "OpenGL: " . opengl_version() . ", ";
	$sw_string .= "Compiler: " . compiler_version() . ", ";
	$sw_string .= "File-System: " . filesystem_type() . " ";

	return $sw_string;
}
?>
