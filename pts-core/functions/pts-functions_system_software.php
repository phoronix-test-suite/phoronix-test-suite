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

function pts_vendor_identifier()
{
	// Returns the vendor identifier used with the External Dependencies and other distro-specific features
	$vendor = sw_os_vendor();

	if($vendor == "Unknown")
	{
		$vendor = sw_os_release();

		if(($spos = strpos($vendor, " ")) > 1)
		{
			$vendor = substr($vendor, 0, $spos);
		}
	}

	return strtolower($vendor);
}
function sw_os_virtualized_mode()
{
	// Reports if system is running virtualized
	$virtualized = "";
	$gpu = hw_gpu_string();

	if(strpos(hw_cpu_string(), "QEMU") !== false)
	{
		$virtualized = "QEMU";
	}
	else if(strpos($gpu, "VMware") !== false)
	{
		$virtualized = "VMware";
	}
	else if(strpos($gpu, "VirtualBox") !== false || strpos(hw_sys_motherboard_string(), "VirtualBox") !== false)
	{
		$virtualized = "VirtualBox";
	}

	if(!empty($virtualized))
	{
		$virtualized = "This system was using " . $virtualized . " virtualization";
	}

	return $virtualized;
}
function sw_os_filesystem()
{
	// Determine file-system type
	$fs = shell_exec("stat " . TEST_ENV_DIR . " -L -f -c %T 2> /dev/null");
	
	if(IS_MACOSX)
	{
		$fs = read_osx_system_profiler("SPSerialATADataType", "FileSystem");
	}

	if(empty($fs) || IS_BSD)
	{
		$fs = "Unknown";
	}

	return $fs;
}
function sw_os_hostname()
{
	$hostname = "Unknown";

	if(is_executable("/bin/hostname"))
	{
		$hostname = trim(shell_exec("/bin/hostname 2>&1"));
	}

	return $hostname;
}
function sw_os_compiler()
{
	// Returns version of the compiler (if present)
	$info = shell_exec("gcc -dumpversion 2>&1");
	$gcc_info = "N/A";

	if(strpos($info, '.') !== false)
	{
		$gcc_info = "GCC " . trim($info);
	}

	return $gcc_info;
}
function sw_os_architecture()
{
	// Find out the kernel archiecture
	$kernel_arch = trim(shell_exec("uname -m 2>&1"));

	if($kernel_arch == "X86-64")
	{
		$kernel_arch = "x86_64";
	}
	else if($kernel_arch == "i86pc")
	{
		$kernel_arch = "i686";
	}

	return $kernel_arch;
}
function sw_os_kernel()
{
	// Returns kernel
	return trim(shell_exec("uname -r 2>&1"));
}
function sw_os_vendor()
{
	// Returns OS vendor
	return read_lsb("Distributor ID");
}
function sw_os_version()
{
	// Returns OS version
	$os_version = read_lsb("Release");
	
	if(IS_MACOSX)
	{
		$os = read_osx_system_profiler("SPSoftwareDataType", "SystemVersion");
		
		$start_pos = strpos($os, ".");
		$end_pos = strrpos($os, ".");
		$start_pos = strrpos(substr($os, 0, $start_pos), " ");
		$end_pos = strpos($os, " ", $end_pos);
		
		$os_version = substr($os, $start_pos + 1, $end_pos - $start_pos);
	}
	
	
	return $os_version;
}
function sw_os_release()
{
	// Determine the operating system release
	$vendor = sw_os_vendor();
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
		{
			$os = shell_exec("uname -s 2>&1");
		}
	}
	else
	{
		$os = $vendor . " " . $version;
	}

	if(($break_point = strpos($os, ":")) > 0)
	{
		$os = substr($os, $break_point + 1);
	}
		
	if(IS_MACOSX)
	{
		$os = read_osx_system_profiler("SPSoftwareDataType", "SystemVersion");
		
		if(($cut_point = strpos($os, "(")) > 0)
		{
			$os = substr($os, 0, $cut_point);
		}
	}

	$os = trim($os);

	return $os;
}
function sw_os_opengl()
{
	// OpenGL version
	$info = shell_exec("glxinfo 2>&1 | grep version");

	if(($pos = strpos($info, "OpenGL version string:")) === false)
	{
		$info = "N/A";
	}
	else
	{
		$info = substr($info, $pos + 23);
		$info = trim(substr($info, 0, strpos($info, "\n")));
		$info = str_replace(array(" Release"), "", $info);
	}

	return $info;
}
function sw_xorg_ddx_driver_info()
{
	$ddx_info = "";

	if(is_file("/proc/dri/0/name"))
	{
		$driver_info = file_get_contents("/proc/dri/0/name");
		$driver_name = substr($driver_info, 0, strpos($driver_info, " "));

		if($driver_name == "i915")
		{
			$driver_name = "intel";
		}

		$driver_version = read_xorg_module_version($driver_name . "_drv");

		if(!empty($driver_version))
		{
			$ddx_info = $driver_name . " " . $driver_version;
		}
	}
	else if(IS_MESA_GRAPHICS && stripos(hw_gpu_string(), "NVIDIA") !== false)
	{
		// xf86-video-nv is an open-source driver but currently doesn't support DRI
		$nv_driver_version = read_xorg_module_version("nv_drv.so");

		if(!empty($nv_driver_version))
		{
			$ddx_info = "nv " . $nv_driver_version;
		}
	}

	return $ddx_info;
}
function sw_os_graphics_subsystem()
{
	// Find graphics subsystem version
	if(IS_SOLARIS)
	{
		$info = shell_exec("X :0 -version 2>&1");
	}
	else
	{
		$info = shell_exec("X -version 2>&1");
	}

	$pos = strrpos($info, "Release Date");
	
	if($pos == false)
	{
		$pos = strrpos($info, "Build Date");
	}
	
	$info = trim(substr($info, 0, $pos));

	if($pos === false)
	{
		$info = "Unknown";
	}
	else if(($pos = strrpos($info, "(")) === false)
	{
		$info = trim(substr($info, strrpos($info, " ")));
	}
	else
	{
		$info = trim(substr($info, strrpos($info, "Server") + 6));
	}

	return $info;
}
function sw_os_username()
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

?>
