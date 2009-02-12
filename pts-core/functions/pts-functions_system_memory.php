<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
	pts-functions_system_memory.php: Functions for reading the system's memory information

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

function hw_sys_memory_string()
{
	$mem_string = null;

	if(IS_MACOSX)
	{
		$mem_size = read_osx_system_profiler("SPMemoryDataType", "Size", true);
		$mem_speed = read_osx_system_profiler("SPMemoryDataType", "Speed");
		$mem_type = read_osx_system_profiler("SPMemoryDataType", "Type");
	}
	else if(IS_SOLARIS)
	{
		$mem_size = read_sun_ddu_dmi_info("MemoryDevice*,InstalledSize");
		$mem_speed = read_sun_ddu_dmi_info("MemoryDevice*,Speed");
		$mem_type = read_sun_ddu_dmi_info("MemoryDevice*,MemoryDeviceType");

		if(is_array($mem_speed) && count($mem_speed) > 0)
		{
			$mem_speed = array_pop($mem_speed);
		}

		$mem_speed = str_replace("MHZ", "MHz", $mem_speed);
	}
	else
	{
		$mem_size = read_dmidecode("memory", "Memory Device", "Size", false, array("Not Installed", "No Module Installed"));
		$mem_speed = read_dmidecode("memory", "Memory Device", "Speed", true, "Unknown");
		$mem_type = read_dmidecode("memory", "Memory Device", "Type", true, array("Unknown", "Other"));
	}

	if(is_array($mem_type))
	{
		$mem_type = array_pop($mem_type);
	}

	if($mem_size != false && (!is_array($mem_size) || count($mem_size) != 0))
	{
		$mem_count = count($mem_size);

		if(!empty($mem_type))
		{
			if(($cut = strpos($mem_type, " ")) > 0)
			{
				$mem_type = substr($mem_type, 0, $cut);
			}

			if(pts_remove_chars($mem_type, true, false, true) == $mem_type)
			{
				$mem_prefix = $mem_type;
			}
		}
		else
		{
			$mem_prefix = "";
		}

		if(!empty($mem_speed))
		{
			if(($cut = strpos($mem_speed, " (")) > 0)
			{
				$mem_speed = substr($mem_speed, 0, $cut);
			}

			if(!empty($mem_prefix))
			{
				$mem_prefix .= "-";
			}

			$mem_prefix .= str_replace(" ", "", $mem_speed);
		}

		if($mem_count > 1 && count(array_unique($mem_size)) > 1)
		{
			$mem_string = implode(" + ", $mem_size) . " " . $mem_prefix;
		}
		else
		{
			$mem_string = $mem_count . " x " . $mem_size[0] . " " . $mem_prefix; 
		}
	}

	if(empty($mem_string))
	{
		$mem_string = hw_sys_memory_capacity() . "MB";
	}

	return trim($mem_string);
}
function hw_sys_memory_capacity()
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
		$info = shell_exec("prtconf 2>&1 | grep Memory");
		$info = substr($info, strpos($info, ":") + 2);
		$info = substr($info, 0, strpos($info, "Megabytes"));
	}
	else if(IS_BSD)
	{
		$mem_size = read_sysctl("hw.physmem");

		if(empty($mem_size))
		{
			$mem_size = read_sysctl("hw.realmem");
		}

		$info = floor($mem_size / 1048576);
	}
	else if(IS_MACOSX)
	{
		$info = read_osx_system_profiler("SPHardwareDataType", "Memory");
		$info = explode(" ", $info);
		
		if(isset($info[1]) && $info[1] == "GB")
		{
			$info = $info[0] * 1024;
		}
		else
		{
			$info = $info[0];
		}
	}
	else
	{
		$info = "Unknown";
	}

	return $info;
}
function sw_physical_memory_usage()
{
	// Amount of physical memory being used
	return read_system_memory_usage("MEMORY");
}
function sw_total_memory_usage()
{
	// Amount of total (physical + SWAP) memory being used
	return read_system_memory_usage("TOTAL");
}
function sw_swap_memory_usage()
{
	// Amount of SWAP memory being used
	return read_system_memory_usage("SWAP");
}

?>
