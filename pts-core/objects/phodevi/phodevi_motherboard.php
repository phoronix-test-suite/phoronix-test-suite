<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
	phodevi_motherboard.php: The PTS Device Interface object for the motherboard

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

class phodevi_motherboard extends pts_device_interface
{
	public static function read_property($identifier)
	{
		switch($identifier)
		{
			case "identifier":
				$property = new pts_device_property("motherboard_string", PHODEVI_SMART_CACHE);
				break;
			case "power-mode":
				$property = new pts_device_property("power_mode", PHODEVI_SMART_CACHE);
				break;
			default:
				$property = new pts_device_property(null, false);
				break;
		}

		return $property;
	}
	public static function power_mode()
	{
		// Returns the power mode
		$power_state = phodevi_parser::read_acpi("/ac_adapter/AC/state", "state");
		$return_status = "";

		if($power_state == "off-line")
		{
			$return_status = "This computer was running on battery power";
		}

		return $return_status;
	}
	public static function motherboard_string()
	{
		// Returns the motherboard / system model name or number
		$info = "";

		if(IS_MACOSX)
		{
			$info = phodevi_parser::read_osx_system_profiler("SPHardwareDataType", "ModelName");
		}
		else if(IS_SOLARIS)
		{
			$manufacturer = phodevi_parser::read_sun_ddu_dmi_info(array("MotherBoardInformation,Manufacturer", "SystemInformation,Manufacturer"));
			$product = phodevi_parser::read_sun_ddu_dmi_info(array("MotherBoardInformation,Product", "SystemInformation,Product", "SystemInformation,Model"));

			if(count($manufacturer) == 1 && count($product) == 1)
			{
				$info = $manufacturer[0] . " " . $product[0];
			}
		}
		else if(IS_BSD)
		{
			if(($vendor = phodevi_parser::read_sysctl("hw.vendor")) != false)
			{
				if(($version = phodevi_parser::read_sysctl("hw.version")) != false || ($version = phodevi_parser::read_sysctl("hw.product")) != false)
				{
					$info = trim($vendor . " " . $version);
				}
				else
				{
					$info = $vendor;
				}
			}
			else if(($acpi = phodevi_parser::read_sysctl("dev.acpi.0.%desc")) != false)
			{
				$info = trim($acpi);
			}
		}

		if(empty($info))
		{	
			$vendor = phodevi_parser::read_system_hal(array("system.hardware.vendor", "system.board.vendor"));
			$product = phodevi_parser::read_system_hal(array("system.hardware.product", "system.board.product"));
			$version = phodevi_parser::read_system_hal(array("system.hardware.version", "smbios.system.version"));

			$info = null;

			if(empty($product) && (strpos($version, ".") === false && !empty($version)))
			{
				$product = $version;
			}

			if(!empty($product))
			{
				if($vendor != false && strpos($product, $vendor . " ") === false)
				{
					$info .= $vendor . " ";
				}

				$info .= $product;
			}

			if(empty($info))
			{
				$fw_version = explode(" ", phodevi_parser::read_system_hal("system.firmware.version"));

				if(count($fw_version) > 1)
				{
					$info = $fw_version[0] . " " . $fw_version[1];
				}
			}

			if(empty($info))
			{
				$pci_vendor = phodevi_parser::read_hal("pci.subsys_vendor");

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
}

?>
