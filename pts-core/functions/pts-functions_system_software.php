<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts-functions_system_software.php: System-level level functions

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

function pts_report_virtualized_mode()
{
	// Reports if system is running virtualized
	$virtualized = "";
	$gpu = graphics_processor_string();

	if(strpos(processor_string(), "QEMU") !== FALSE)
		$virtualized = "QEMU";
	else if(strpos($gpu, "VMware") !== FALSE)
		$virtualized = "VMware";
	else if(strpos($gpu, "VirtualBox") !== FALSE || strpos(main_system_hardware_string(), "VirtualBox") !== FALSE)
		$virtualized = "VirtualBox";

	if(!empty($virtualized))
		$virtualized = "This system was using " . $virtualized . " virtualization.";

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
function compiler_version()
{
	// Returns version of the compiler (if present)
	$info = shell_exec("gcc -dumpversion 2>&1");
	$gcc_info = "N/A";

	if(strpos($info, '.') !== FALSE)
		$gcc_info = "GCC " . trim($info);

	return $gcc_info;
}
function kernel_arch()
{
	// Find out the kernel archiecture
	$kernel_arch = trim(shell_exec("uname -m 2>&1"));

	if($kernel_arch == "X86-64")
		$kernel_arch = "x86_64";
	else if($kernel_arch == "i86pc")
		$kernel_arch = "i686";

	return $kernel_arch;
}
function kernel_string()
{
	// Returns kernel
	return trim(shell_exec("uname -r 2>&1"));
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
			$os = shell_exec("uname -s 2>&1");
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
function pts_system_user_name()
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
?>
