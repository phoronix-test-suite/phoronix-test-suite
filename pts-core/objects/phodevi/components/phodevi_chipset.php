<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2021, Phoronix Media
	Copyright (C) 2008 - 2021, Michael Larabel
	phodevi_chipset.php: The PTS Device Interface object for the system chipset

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

class phodevi_chipset extends phodevi_device_interface
{
	public static function properties()
	{
		return array(
			'identifier' => new phodevi_device_property('chipset_string', phodevi::smart_caching)
			);
	}
	public static function chipset_string()
	{
		$info = false;

		if(phodevi::is_macos())
		{
			$sb_vendor = phodevi_osx_parser::read_osx_system_profiler('SPSerialATADataType', 'Vendor');
			$sb_product = phodevi_osx_parser::read_osx_system_profiler('SPSerialATADataType', 'Product');

			if($sb_product == 'SSD')
			{
				$sb_product = null;
			}
		
			if(($cut_point = strpos($sb_product, ' ')) > 0)
			{
				$sb_product = substr($sb_product, 0, $cut_point);
			}
			
			// TODO: Can't find Northbridge
			$info = $sb_vendor . ' ' . $sb_product;
		}
		else if(phodevi::is_windows())
		{
			// TODO XXX figure out
		}
		else if(phodevi::is_solaris())
		{
			// Vendor Detection
			$vendor_possible_udis = array(
				'/org/freedesktop/Hal/devices/pci_0_0/pci_ide_3_2_0',
				'/org/freedesktop/Hal/devices/pci_0_0/pci_ide_1f_1_1',
				);

			$info = phodevi_solaris_parser::read_hal_property($vendor_possible_udis, 'info.vendor');

			// TODO: Northbridge and Southbridge Detection For Solaris
		}
		else if(phodevi::is_bsd())
		{
			$info = phodevi_bsd_parser::read_pciconf_by_class('bridge');
		}
		else if(phodevi::is_linux() || phodevi::is_hurd())
		{
			$info = phodevi_linux_parser::read_pci(array('RAM memory', 'Host bridge'));

			if(count(explode(' ', $info)) == 1)
			{
				$bridge = phodevi_linux_parser::read_pci(array('Bridge', 'PCI bridge'));

				if(!empty($bridge))
				{
					$match = false;
					$break_words = array('Ethernet', 'PCI', 'High', 'USB');

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
				$southbridge = phodevi_linux_parser::read_pci(array('ISA bridge', 'SATA controller'), false);
				$southbridge_clean = null;

				if(($start_cut = strpos($southbridge, '(')) > 0 && ($end_cut = strpos($southbridge, ')', $start_cut + 1)) > 0)
				{
					$southbridge_extract = substr($southbridge, $start_cut + 1, $end_cut - $start_cut - 1);

					if(strpos($southbridge_extract, 'rev') === false)
					{
						$southbridge_extract = explode(' ', $southbridge_extract);
						$southbridge_clean = $southbridge_extract[0];
					}
					else if(($s = strpos($southbridge, 'ICH')) > 0)
					{
						$southbridge_extract = substr($southbridge, $s);
						$southbridge_clean = substr($southbridge_extract, 0, strpos($southbridge_extract, ' '));
					}
				}
				else if(($start_cut = strpos($southbridge, 'SB')) !== false)
				{
					$southbridge_extract = substr($southbridge, $start_cut);
					$southbridge_clean = substr($southbridge_extract, 0, strpos($southbridge_extract, ' '));
				}

				if(!empty($southbridge_clean) && $southbridge_clean != 'SB')
				{
					$info .= ' + ' . $southbridge_clean;
				}
			}
		}

		return $info;
	}
}

?>
