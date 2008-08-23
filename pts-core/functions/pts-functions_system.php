<?php

/*
	Phoronix Test Suite
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
	// Format a nice string that shows processes running
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
	// Checks if process is running on the system
	$running = shell_exec("ps -C " . strtolower($process) . " 2>&1");
	$running = trim(str_replace(array("PID", "TTY", "TIME", "CMD"), "", $running));

	if(!empty($running))
		$running = true;
	else
		$running = false;

	return $running;
}
function pts_user_name()
{
	// Gets the system user's name
	if(function_exists("posix_getpwuid") && function_exists("posix_getuid"))
	{
		$userinfo = posix_getpwuid(posix_getuid());
		$username = $userinfo["name"];
	}
	else
	{
		$username = trim(getenv("USERNAME"));
	}

	return $username;
}
function pts_user_home()
{
	// Gets the system user's home directory
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
	// Returns amoung of disk space
	return ceil(disk_total_space("/") / 1073741824);
}
function memory_mb_capacity()
{
	// Returns physical memory capacity
	if(is_file("/proc/meminfo"))
	{
		$info = file_get_contents("/proc/meminfo");
		$info = substr($info, strpos($info, "MemTotal:") + 9);
		$info = intval(trim(substr($info, 0, strpos($info, "kB"))));
		$info = floor($info / 1024);
	}
	else if(IS_SOLARIS)
	{
		$info = shell_exec("prtconf | grep Memory");
		$info = substr($info, strpos($info, ":") + 2);
		$info = substr($info, 0, strpos($info, "Megabytes"));
	}
	else if(IS_BSD)
	{
		$info = floor(read_sysctl("hw.realmem") / 1048576);
	}
	else
		$info = "Unknown";

	return $info;
}
function os_vendor()
{
	// Returns OS vendor
	return read_lsb("Distributor ID");
}
function os_version()
{
	// Returns OS version
	return read_lsb("Release");
}
function kernel_string()
{
	// Returns kernel
	return trim(shell_exec("uname -r"));
}
function kernel_arch()
{
	// Find out the kernel archiecture
	$kernel_arch = trim(shell_exec("uname -m"));

	if($kernel_arch == "X86-64")
		$kernel_arch = "x86_64";
	else if($kernel_arch == "i86pc")
		$kernel_arch = "i686";

	return $kernel_arch;
}
function motherboard_chipset_string()
{
	// Returns motherboard chipset
	return read_pci("Host bridge:");
}
function compiler_version()
{
	// Returns version of the compiler (if present)
	$info = shell_exec("gcc -dumpversion 2>&1");
	$gcc_info = "N/A";

	if(strpos($info, '.') !== FALSE)
		$gcc_info = "GCC " . trim($info);

	return $gcc_info;
}
function operating_system_release()
{
	// Determine the operating system release
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
			else
			{
				if(is_file("/etc/release"))
				{
					$file = file_get_contents("/etc/release");
					$os = substr($file, 0, strpos($file, "\n"));
				}
			}
		}

		if($os == "Unknown")
			$os = shell_exec("uname -s");
	}
	else
		$os = $vendor . " " . $version;

	if(($break_point = strpos($os, ":")) > 0)
		$os = substr($os, $break_point + 1);

	$os = trim($os);

	return $os;
}
function pts_vendor_identifier()
{
	// Returns the vendor identifier used with the External Dependencies and other distro-specific features
	$vendor = os_vendor();

	if($vendor == "Unknown")
	{
		$vendor = operating_system_release();

		if(($spos = strpos($vendor, " ")) > 1)
			$vendor = substr($vendor, 0, $spos);
	}

	return strtolower($vendor);
}
function system_temperature()
{
	// Reads the system's temperature
	$temp_c = read_sensors(array("Sys Temp", "Board Temp"));

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
function system_line_voltage($type)
{
	// Reads the system's line voltages
	if($type == "CPU")
		$voltage = read_sensors("VCore");
	else if($type == "V3")
		$voltage = read_sensors(array("V3.3", "+3.3V"));
	else if($type == "V5")
		$voltage = read_sensors(array("V5", "+5V"));
	else if($type == "V12")
		$voltage = read_sensors(array("V12", "+12V"));
	else
		$voltage = "";

	if(empty($voltage))
		$voltage = -1;

	return $voltage;
}
function main_system_hardware_string()
{
	// Returns the motherboard
	$vendor = read_system_hal("system.hardware.vendor");
	$product = read_system_hal("system.hardware.product");
	$version = read_system_hal("system.hardware.version");

	if($vendor != "Unknown")
		$info = $vendor;
	else
		$info = "";

	if($product == "Unknown" || empty($product) || (strpos($version, ".") === FALSE && $version != "Unknown"))
	{
		$product = $version;
	}

	if(!empty($product) && $product != "Unknown")
	{
		$info .= " " . $product;
	}

	if(empty($info))
	{
		$info = read_hal("pci.subsys_vendor");
	}

	return pts_clean_information_string($info);
}
function pts_report_power_mode()
{
	// Returns the power mode
	$power_state = read_acpi("/ac_adapter/AC/state", "state");
	$return_status = "";

	if($power_state == "off-line")
		$return_status = "This computer was running on battery power.";

	return $return_status;
}
function pts_report_virtualized_mode()
{
	// Reports if system is running virtualized
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
	// Determine file-system type
	$fs = shell_exec("stat " . TEST_ENV_DIR . " -L -f -c %T 2> /dev/null");

	if(empty($fs) || IS_BSD)
		return "Unknown";

	return $fs;
}
function read_physical_memory_usage()
{
	// Amount of physical memory being used
	return read_system_memory_usage("MEMORY");
}
function read_total_memory_usage()
{
	// Amount of total (physical + SWAP) memory being used
	return read_system_memory_usage("TOTAL");
}
function read_swap_usage()
{
	// Amount of SWAP memory being used
	return read_system_memory_usage("SWAP");
}
function read_system_memory_usage($TYPE = "TOTAL", $READ = "USED")
{
	// Reads system memory usage
	$mem = explode("\n", shell_exec("free -t -m 2>&1"));
	$grab_line = null;
	$mem_usage = -1;

	for($i = 0; $i < count($mem) && empty($grab_line); $i++)
	{
		$line_parts = explode(":", $mem[$i]);

		if(count($line_parts) == 2)
		{
			$line_type = trim($line_parts[0]);

			if($TYPE == "MEMORY" && $line_type == "Mem")
				$grab_line = $line_parts[1];
			else if($TYPE == "SWAP" && $line_type == "Swap")
				$grab_line = $line_parts[1];
			else if($TYPE == "TOTAL" && $line_type == "Total")
				$grab_line = $line_parts[1];
		}
	}

	if(!empty($grab_line))
	{
		$grab_line = trim(preg_replace("/\s+/", " ", $grab_line));
		$mem_parts = explode(" ", $grab_line);

		if($READ == "USED")
		{
			if(count($mem_parts) >= 2 && is_numeric($mem_parts[1]))
				$mem_usage = $mem_parts[1];
		}
		else if($READ == "TOTAL")
		{
			if(count($mem_parts) >= 1 && is_numeric($mem_parts[0]))
				$mem_usage = $mem_parts[0];
		}
		else if($READ == "FREE")
		{
			if(count($mem_parts) >= 3 && is_numeric($mem_parts[2]))
				$mem_usage = $mem_parts[2];
		}
	}

	return $mem_usage;
}
function pts_hw_string()
{
	// Returns string of hardware information
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
	// Returns string of software information
	$sw_string = "OS: " . operating_system_release() . ", ";
	$sw_string .= "Kernel: " . kernel_string() . " (" . kernel_arch() . "), ";
	$sw_string .= "X.Org Server: " . graphics_subsystem_version() . ", ";
	$sw_string .= "OpenGL: " . opengl_version() . ", ";
	$sw_string .= "Compiler: " . compiler_version() . ", ";
	$sw_string .= "File-System: " . filesystem_type() . " ";

	return $sw_string;
}
?>
