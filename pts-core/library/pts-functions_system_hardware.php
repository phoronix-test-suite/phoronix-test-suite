<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
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

function hw_sys_motherboard_string()
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
	else if(IS_BSD)
	{
		if(($vendor = read_sysctl("hw.vendor")) != false && ($version = read_sysctl("hw.version")) != false)
		{
			$info = trim($vendor . " " . $version);
		}
	}

	if(empty($info))
	{	
		$vendor = read_system_hal(array("system.hardware.vendor", "system.board.vendor"));
		$product = read_system_hal(array("system.hardware.product", "system.board.product"));
		$version = read_system_hal(array("system.hardware.version", "smbios.system.version"));

		$info = ($vendor != false ? $vendor : "");

		if(empty($product) && (strpos($version, ".") === false && !empty($version)))
		{
			$product = $version;
		}

		if(!empty($product) && !empty($product))
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
			$pci_vendor = read_hal("pci.subsys_vendor");

			if(strpos($pci_vendor, "(") === false)
			{
				$info = $pci_vendor;
			}
		}
	}

	$info = pts_clean_information_string($info);

	if(empty($info))
	{
		$info = "Unknown";
	}

	return $info;
}
function hw_sys_chipset_string()
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

			if(!empty($bridge))
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

		if(!isset($bridge) || !empty($bridge))
		{
			// Attempt to detect Southbridge (if applicable)
			$southbridge = read_pci(array("ISA bridge", "SATA controller"), false);
			$southbridge_clean = null;

			if(($start_cut = strpos($southbridge, "(")) > 0 && ($end_cut = strpos($southbridge, ")", $start_cut + 1)) > 0)
			{
				$southbridge_extract = substr($southbridge, $start_cut + 1, $end_cut - $start_cut - 1);

				if(strpos($southbridge_extract, "rev") === false)
				{
					$southbridge_extract = explode(" ", $southbridge_extract);
					$southbridge_clean = $southbridge_extract[0];
				}
				else if(($s = strpos($southbridge, "ICH")) > 0)
				{
					$southbridge_extract = substr($southbridge, $s);
					$southbridge_clean = substr($southbridge_extract, 0, strpos($southbridge_extract, " "));
				}
			}
			else if(($start_cut = strpos($southbridge, "SB")) > 0)
			{
				$southbridge_extract = substr($southbridge, $start_cut);
				$southbridge_clean = substr($southbridge_extract, 0, strpos($southbridge_extract, " "));
			}

			if(!empty($southbridge_clean))
			{
				$info .= " + " . $southbridge_clean;
			}
		}

		if(empty($info))
		{
			$info = "Unknown";
		}
	}

	return $info;
}
function hw_sys_hdd_string()
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
	else if(IS_BSD)
	{
		$i = 0;

		do
		{
			$disk = read_sysctl("dev.ad." . $i . ".%desc");

			if($disk != false)
			{
				array_push($disks, $disk);
			}
			$i++;
		}
		while($disk != false);
	}
	else if(IS_LINUX)
	{
		$disks_formatted = array();
		$disks = array();

		foreach(glob("/sys/block/sd*") as $sdx)
		{
			if(is_file($sdx . "/device/model") && is_file($sdx . "/size"))
			{
				$disk_size = file_get_contents($sdx . "/size");
				$disk_model = trim(file_get_contents($sdx . "/device/model"));

				$disk_size = round($disk_size * 512 / 1000000000);

				if($disk_size > 0)
				{
					array_push($disks_formatted, $disk_size . "GB " . $disk_model);
				}
			}
		}

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

				$disk = ($times_found > 1 ? $times_found . " x "  : "") . $disks_formatted[$i];
				array_push($disks, $disk);
			}
		}
	}

	$disks = (count($disks) == 0 ? hw_sys_hdd_total() . "GB" : implode(" + ", $disks));

	return $disks;
}
function hw_sys_hdd_total()
{
	// Returns amoung of disk space
	return ceil(disk_total_space("/") / 1073741824);
}
function hw_sys_temperature()
{
	// Reads the system's temperature
	$temp_c = -1;

	if(IS_BSD)
	{
		$acpi = read_sysctl("hw.acpi.thermal.tz1.temperature");

		if(($end = strpos($acpi, 'C')) > 0)
		{
			$acpi = substr($acpi, 0, $end);

			if(is_numeric($acpi))
			{
				$temp_c = $acpi;
			}
		}
	}
	else if(IS_LINUX)
	{
		$sensors = read_sensors(array("Sys Temp", "Board Temp"));

		if(!$sensors != false && is_numeric($sensors))
		{
			$temp_c = $sensors;
		}
		else
		{
			$acpi = read_acpi(array(
				"/thermal_zone/THM1/temperature", 
				"/thermal_zone/TZ01/temperature"), "temperature");

			if(($end = strpos($acpi, ' ')) > 0)
			{
				$temp_c = substr($acpi, 0, $end);
			}
		}
	}

	return $temp_c;
}
function hw_sys_line_voltage($type)
{
	// Reads the system's line voltages

	switch($type)
	{
		case "CPU":
			$voltage = read_sensors("VCore");
			break;
		case "V3":
			$voltage = read_sensors(array("V3.3", "+3.3V"));
			break;
		case "V5":
			$voltage = read_sensors(array("V5", "+5V"));
			break;
		case "V12":
			$voltage = read_sensors(array("V12", "+12V"));
			break;
		default:
			$voltage = null;
			break;
	}

	return ($voltage == null ? -1 : $voltage);
}
function hw_sys_hdd_temperature($disk = null)
{
	// Attempt to read temperature using hddtemp
	return read_hddtemp($disk);
}
function hw_sys_power_mode()
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
function hw_sys_power_consumption_rate()
{
	// Returns power consumption rate in mW
	$battery = array("/battery/BAT0/state", "/battery/BAT1/state");
	$state = read_acpi($battery, "charging state");
	$power = read_acpi($battery, "present rate");
	$voltage = read_acpi($battery, "present voltage");
	$rate = -1;

	if($state == "discharging")
	{
		$power_unit = substr($power, strrpos($power, " ") + 1);
		$power = substr($power, 0, strpos($power, " "));

		if($power_unit == "mA")
		{
			$voltage_unit = substr($voltage, strrpos($voltage, " ") + 1);
			$voltage = substr($voltage, 0, strpos($voltage, " "));

			if($voltage_unit == "mV")
			{
				$rate = round(($power * $voltage) / 1000);
			}				
		}
		else if($power_unit == "mW")
		{
			$rate = $power;
		}
	}

	return $rate;
}

?>
