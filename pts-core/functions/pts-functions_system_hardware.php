<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts-functions_system_hardware.php: System-level general hardware functions

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

function main_system_hardware_string()
{
	// Returns the motherboard / system model name or number
	if(IS_MACOSX)
	{
		return read_osx_system_profiler("SPHardwareDataType", "ModelName");	
	}
	
	$vendor = read_system_hal(array("system.hardware.vendor", "system.board.vendor", "pci.subsys_vendor"));
	$product = read_system_hal(array("system.hardware.product", "system.board.product"));
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
		$fw_version = explode(" ", read_system_hal("system.firmware.version"));

		if(count($fw_version) > 1)
			$info = $fw_version[0] . " " . $fw_version[1];
	}

	if(empty($info))
	{
		$info = read_hal("pci.subsys_vendor");
	}

	return pts_clean_information_string($info);
}
function motherboard_chipset_string()
{
	// Returns motherboard chipset
	$info = read_pci("Host bridge");

	if(count(explode(" ", $info)) == 1)
	{
		$bridge = read_pci(array("Bridge", "PCI bridge"));

		if($bridge != "Unknown")
		{
			$match = false;
			$break_words = array("Ethernet", "PCI", "High", "USB");

			for($i = 0; $i < count($break_words) && !$match; $i++)
			{
				if(($pos = strpos($bridge, $break_words[$i])) > 0)
				{
					$bridge = trim(substr($bridge, 0, $pos));
					$info = $bridge;
					$match = true;
				}
			}
		}
	}

	if(!isset($bridge) || $bridge != "Unknown")
	{
		// Attempt to detect Southbridge (if applicable)
		$southbridge = read_pci(array("ISA bridge", "SATA controller"), FALSE);

		if(($start_cut = strpos($southbridge, "(")) > 0 && ($end_cut = strpos($southbridge, ")", $start_cut + 1)) > 0)
		{
			$southbridge_extract = substr($southbridge, $start_cut + 1, $end_cut - $start_cut - 1);

			if(strpos($southbridge_extract, "rev") === FALSE)
			{
				$southbridge_extract = explode(" ", $southbridge_extract);
				$southbridge_clean = $southbridge_extract[0];

				$info .= " + " . $southbridge_clean;
			}
		}
		else if(($start_cut = strpos($southbridge, "SB")) > 0)
		{
			$southbridge_extract = substr($southbridge, $start_cut);
			$southbridge_extract = substr($southbridge_extract, 0, strpos($southbridge_extract, " "));

			$info .= " + " . $southbridge_extract;
		}
	}
	
	if(IS_MACOSX)
	{
		$sb_vendor = read_osx_system_profiler("SPSerialATADataType", "Vendor");
		$sb_product = read_osx_system_profiler("SPSerialATADataType", "Product");
		
		if(($cut_point = strpos($sb_product, " ")) > 0)
			$sb_product = substr($sb_product, 0, $cut_point);
			
		// TODO: Can't find Northbridge
			
		$info = $sb_vendor . " " . $sb_product;
	}

	return $info;
}
function system_disk_total()
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
	else if(IS_MACOSX)
	{
		$info = read_osx_system_profiler("SPHardwareDataType", "Memory");
		$info = explode(" ", $info);
		
		if(isset($info[1]) && $info[1] == "GB")
			$info = $info[0] * 1024;
		else
			$info = $info[0];
	}
	else
		$info = "Unknown";

	return $info;
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
	{
		$voltage = read_sensors("VCore");
	}
	else if($type == "V3")
	{
		$voltage = read_sensors(array("V3.3", "+3.3V"));
	}
	else if($type == "V5")
	{
		$voltage = read_sensors(array("V5", "+5V"));
	}
	else if($type == "V12")
	{
		$voltage = read_sensors(array("V12", "+12V"));
	}
	else
	{
		$voltage = "";
	}

	if(empty($voltage))
		$voltage = -1;

	return $voltage;
}
function system_hdd_temperature($disk = null)
{
	// Attempt to read temperature using hddtemp
	return read_hddtemp($disk);
}
function system_power_mode()
{
	// Returns the power mode
	$power_state = read_acpi("/ac_adapter/AC/state", "state");
	$return_status = "";

	if($power_state == "off-line")
		$return_status = "This computer was running on battery power.";

	return $return_status;
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

?>
