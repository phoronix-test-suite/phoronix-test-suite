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
	$info = "";
	if(IS_MACOSX)
	{
		$info = read_osx_system_profiler("SPHardwareDataType", "ModelName");
	}
	else if(IS_SOLARIS)
	{
		$manufacturer = read_sun_ddu_dmi_info("MotherBoardInformation,Manufacturer");
		$product = read_sun_ddu_dmi_info("MotherBoardInformation,Product");

		if(count($manufacturer) == 1 && count($product) == 1)
		{
			$info = $manufacturer[0] . " " . $product[0];
		}
	}

	if(empty($info))
	{	
		$vendor = read_system_hal(array("system.hardware.vendor", "system.board.vendor"));
		$product = read_system_hal(array("system.hardware.product", "system.board.product"));
		$version = read_system_hal(array("system.hardware.version", "smbios.system.version"));

		if($vendor != "Unknown")
		{
			$info = $vendor;
		}
		else
		{
			$info = "";
		}

		if($product == "Unknown" || empty($product) || (strpos($version, ".") === false && $version != "Unknown"))
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
			{
				$info = $fw_version[0] . " " . $fw_version[1];
			}
		}

		if(empty($info))
		{
			$info = read_hal("pci.subsys_vendor");
		}
	}

	return pts_clean_information_string($info);
}
function motherboard_chipset_string()
{
	// Returns motherboard chipset
	if(IS_MACOSX)
	{
		$sb_vendor = read_osx_system_profiler("SPSerialATADataType", "Vendor");
		$sb_product = read_osx_system_profiler("SPSerialATADataType", "Product");
		
		if(($cut_point = strpos($sb_product, " ")) > 0)
		{
			$sb_product = substr($sb_product, 0, $cut_point);
		}
			
		// TODO: Can't find Northbridge
		$info = $sb_vendor . " " . $sb_product;
	}
	else
	{
		$info = read_pci(array("RAM memory", "Host bridge"));

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
			$southbridge = read_pci(array("ISA bridge", "SATA controller"), false);

			if(($start_cut = strpos($southbridge, "(")) > 0 && ($end_cut = strpos($southbridge, ")", $start_cut + 1)) > 0)
			{
				$southbridge_extract = substr($southbridge, $start_cut + 1, $end_cut - $start_cut - 1);

				if(strpos($southbridge_extract, "rev") === false)
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
	}

	return $info;
}
function system_hard_disks()
{
	$disks = array();
	if(IS_MACOSX)
	{
		// TODO: Support reading non-SATA drives and more than one drive
		$capacity = read_osx_system_profiler("SPSerialATADataType", "Capacity");
		$model = read_osx_system_profiler("SPSerialATADataType", "Model");

		if(!empty($capacity) && !empty($model))
		{
			$disks = array($capacity . " " . $model);
		}
	}
	else
	{
		$dmesg_ata = shell_exec("dmesg 2>&1 | grep ATA");
		$dmesg_sectors = shell_exec("dmesg 2>&1 | grep \"hardware sectors\"");
		$disks_identifiers = array();
		$disks_capacities = array();

		do
		{
			// Calculate disk names
			$search_disk_strings = array("ATA-6", "ATA-7", "ATA-8");
			$start_point = -1;

			for($i = 0; $i < count($search_disk_strings) && $start_point == -1; $i++)
			{
				if(($tmp_pointer = strpos($dmesg_ata, $search_disk_strings[$i])) > 0)
				{
					$start_point = $tmp_pointer + strlen($search_disk_strings[$i]) + 1;
				}
			}

			if($start_point != -1)
			{
				$dmesg_ata = substr($dmesg_ata, $start_point);
				$dmesg_line = substr($dmesg_ata, 0, strpos($dmesg_ata, "\n"));

				$cut_points = array(",");
				$cut_point_used = false;
				for($i = 0; $i < count($cut_points) && $cut_point_used == false; $i++)
				{
					if(($tmp_pointer = strpos($dmesg_line, $cut_points[$i])) > 0)
					{
						$dmesg_line = substr($dmesg_line, 0, $tmp_pointer);
					}
				}

				array_push($disks, trim($dmesg_line));
			}
		}
		while($start_point != -1);

		foreach(explode("\n", $dmesg_sectors) as $sector_line)
		{
			// Calculate disk sizes
			if(($start_bracket = strrpos($sector_line, "[")) > 0 && ($end_bracket = strrpos($sector_line, "]")) > $start_bracket)
			{
				$identifier = substr($sector_line, $start_bracket + 1, ($end_bracket - $start_bracket - 1));

				if(count(glob("/dev/" . $identifier)) == 1 && !in_array($identifier, $disks_identifiers))
				{
					// Disk is still present on system
					if(($start_size = strrpos($sector_line, "(")) > 0 && ($end_size = strrpos($sector_line, ")")) > $start_size)
					{
						$disk_r = explode(" ", substr($sector_line, $start_size + 1, ($end_size - $start_size - 1)));

						if(is_numeric($disk_r[0]))
						{
							$disk_size = $disk_r[0];

							if($disk_r[1] == "MB")
							{
								$disk_size /= 1024;
							}

							if($disk_size > 10 && $disk_size % 10 != 0)
							{
								$disk_size *= 1.01;
								$mod = $disk_size % 10;
								$disk_size += (10 - $mod);

								if($disk_size % 100 == 10)
								{
									$disk_size -= 10;
								}
								if($disk_size % 100 == 90)
								{
									$disk_size += 10;
								}
							}

							$disk_size = pts_trim_double($disk_size, 0);
							array_push($disks_capacities, $disk_size);
						}
					}
					array_push($disks_identifiers, $identifier);
				}
			}
		}

		$disks_formatted = array();
		for($i = 0; $i < count($disks) && $i < count($disks_capacities); $i++)
		{
			array_push($disks_formatted, $disks_capacities[$i] . "GB " . $disks[$i]);
		}

		$disks = array();
		for($i = 0; $i < count($disks_formatted); $i++)
		{
			if(!empty($disks_formatted[$i]))
			{
				$times_found = 1;

				for($j = ($i + 1); $j < count($disks_formatted); $j++)
				{
					if($disks_formatted[$i] == $disks_formatted[$j])
					{
						$times_found++;
						$disks_formatted[$j] = "";
					}
				}

				if($times_found > 1)
				{
					$disk = $times_found . " x " . $disks_formatted[$i];
				}
				else
				{
					$disk = $disks_formatted[$i];
				}
				array_push($disks, $disk);
			}
		}
	}

	if(count($disks) == 0)
	{
		$disks = system_disk_total() . "GB";
	}
	else
	{
		$disks = implode(" + ", $disks);
	}

	return $disks;
}
function system_disk_total()
{
	// Returns amoung of disk space
	return ceil(disk_total_space("/") / 1073741824);
}
function system_memory_string()
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
		$mem_type = read_sun_ddu_dmi_info("MemoryDevice*,MemoryDeviceType");
		$mem_speed = false;
	}
	else
	{
		$mem_size = read_dmidecode("memory", "Memory Device", "Size", false, array("Not Installed", "No Module Installed"));
		$mem_speed = read_dmidecode("memory", "Memory Device", "Speed", true, "Unknown");
		$mem_type = read_dmidecode("memory", "Memory Device", "Type", true, "Unknown");
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

			$mem_prefix = $mem_type;
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

			$mem_prefix .= "-" . str_replace(" ", "", $mem_speed);
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
		$mem_string = memory_mb_capacity() . "MB";
	}

	return trim($mem_string);
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
function system_temperature()
{
	// Reads the system's temperature
	$temp_c = read_sensors(array("Sys Temp", "Board Temp"));

	if(empty($temp_c))
	{
		$temp_c = read_acpi("/thermal_zone/THM1/temperature", "temperature"); // if it is THM1 that is for the system, in most cases it should be

		if(($end = strpos($temp_c, ' ')) > 0)
		{
			$temp_c = substr($temp_c, 0, $end);
		}
	}

	if(empty($temp_c))
	{
		$temp_c = -1;
	}

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
	{
		$voltage = -1;
	}

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
	{
		$return_status = "This computer was running on battery power";
	}

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
