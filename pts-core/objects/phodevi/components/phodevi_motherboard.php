<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel
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

class phodevi_motherboard extends phodevi_device_interface
{
	public static function read_property($identifier)
	{
		switch($identifier)
		{
			case 'identifier':
				$property = new phodevi_device_property('motherboard_string', PHODEVI_SMART_CACHE);
				break;
			case 'power-mode':
				$property = new phodevi_device_property('power_mode', PHODEVI_SMART_CACHE);
				break;
			default:
				$property = new phodevi_device_property(null, false);
				break;
		}

		return $property;
	}
	public static function power_mode()
	{
		// Returns the power mode
		$return_status = null;

		if(IS_LINUX)
		{
			$sysfs_checked = false;

			foreach(pts_file_io::glob('/sys/class/power_supply/AC*/online') as $online)
			{
				if(pts_file_io::file_get_contents($online) == '0')
				{
					$return_status = 'This computer was running on battery power';
					break;
				}
				$sysfs_checked = true;
			}

			if(!$sysfs_checked)
			{
				// There likely was no sysfs power_supply support for that power adapter
				$power_state = phodevi_linux_parser::read_acpi('/ac_adapter/AC/state', 'state');

				if($power_state == 'off-line')
				{
					$return_status = 'This computer was running on battery power';
				}
			}
		}

		return $return_status;
	}
	public static function motherboard_string()
	{
		// Returns the motherboard / system model name or number
		$info = null;

		if(IS_MACOSX)
		{
			$info = phodevi_osx_parser::read_osx_system_profiler('SPHardwareDataType', 'ModelName');
		}
		else if(IS_SOLARIS)
		{
			$manufacturer = phodevi_solaris_parser::read_sun_ddu_dmi_info(array('MotherBoardInformation,Manufacturer', 'SystemInformation,Manufacturer'));
			$product = phodevi_solaris_parser::read_sun_ddu_dmi_info(array('MotherBoardInformation,Product', 'SystemInformation,Product', 'SystemInformation,Model'));

			if(count($manufacturer) == 1 && count($product) == 1)
			{
				$info = $manufacturer[0] . ' ' . $product[0];
			}
		}
		else if(IS_BSD)
		{
			if(($vendor = phodevi_bsd_parser::read_sysctl('hw.vendor')) != false && ($version = phodevi_bsd_parser::read_sysctl(array('hw.version', 'hw.product'))) != false)
			{
				$info = trim($vendor . ' ' . $version);
			}
			else if(($acpi = phodevi_bsd_parser::read_sysctl('dev.acpi.0.%desc')) != false)
			{
				$info = trim($acpi);
			}
		}
		else if(IS_LINUX)
		{
			$vendor = phodevi_linux_parser::read_sys_dmi('board_vendor');
			$name = phodevi_linux_parser::read_sys_dmi('board_name');
			$version = phodevi_linux_parser::read_sys_dmi('board_version');

			if($vendor != false && $name != false)
			{
				$info = strpos($name, $vendor . ' ') === false ? $vendor . ' ' : null;
				$info .= $name;

				if($version != false && strpos($info, $version) === false && strlen(pts_strings::remove_from_string($version, pts_strings::CHAR_NUMERIC | pts_strings::CHAR_DECIMAL)) == 0)
				{
					$info .= (substr($version, 0, 1) == 'v' ? ' ' : ' v') . $version;
				}
			}

			if(empty($info))
			{
				// Read motherboard from HAL
				$vendor = phodevi_linux_parser::read_system_hal(array('system.hardware.vendor', 'system.board.vendor'));
				$product = phodevi_linux_parser::read_system_hal(array('system.hardware.product', 'system.board.product'));
				$version = phodevi_linux_parser::read_system_hal(array('system.hardware.version', 'smbios.system.version'));

				$info = null;

				if(empty($product) && (strpos($version, '.') === false && !empty($version)))
				{
					$product = $version;
				}

				if(!empty($product))
				{
					if($vendor != false && strpos($product, $vendor . ' ') === false)
					{
						$info .= $vendor . ' ';
					}

					$info .= $product;
				}

				if($info == null)
				{
					$fw_version = explode(' ', phodevi_linux_parser::read_system_hal('system.firmware.version'));

					if(count($fw_version) > 1)
					{
						$info = $fw_version[0] . ' ' . $fw_version[1];
					}
				}

				if($info == null)
				{
					$pci_vendor = phodevi_linux_parser::read_hal('pci.subsys_vendor');

					if(strpos($pci_vendor, '(') === false)
					{
						$info = $pci_vendor;
					}
				}

				if($info == null)
				{
					$hw_string = phodevi_linux_parser::read_cpuinfo('Hardware');

					if(count($hw_string) == 1)
					{
						$info = $hw_string[0];
					}
				}
			}

			if(empty($info))
			{
				$info = phodevi_linux_parser::read_sys_dmi('product_name');
			}
		}
		else if(IS_WINDOWS)
		{
			$info = phodevi_windows_parser::read_cpuz('Mainboard Model', null);
		}

		if(empty($info))
		{
			$info = 'Unknown';
		}

		return $info;
	}
}

?>
