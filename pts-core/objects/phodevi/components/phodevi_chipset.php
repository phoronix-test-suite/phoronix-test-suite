<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
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

class phodevi_chipset extends pts_device_interface
{
	public static function read_property($identifier)
	{
		switch($identifier)
		{
			case "identifier":
				$property = new pts_device_property("chipset_string", PHODEVI_SMART_CACHE);
				break;
		}

		return $property;
	}
	public static function chipset_string()
	{
		if(IS_MACOSX)
		{
			$sb_vendor = phodevi_osx_parser::read_osx_system_profiler("SPSerialATADataType", "Vendor");
			$sb_product = phodevi_osx_parser::read_osx_system_profiler("SPSerialATADataType", "Product");
		
			if(($cut_point = strpos($sb_product, " ")) > 0)
			{
				$sb_product = substr($sb_product, 0, $cut_point);
			}
			
			// TODO: Can't find Northbridge
			$info = $sb_vendor . " " . $sb_product;
		}
		else if(IS_WINDOWS)
		{
			$info = phodevi_windows_parser::read_cpuz("Northbridge", null);

			if($info != null)
			{
				if(($e = strpos($info, "rev")) !== false)
				{
					$info = substr($info, 0, $e);
				}

				$info = trim($info);
			}
		}
		else if(IS_SOLARIS)
		{
			// TODO:
			$info = null;
		}
		else if(IS_LINUX)
		{
			$info = phodevi_linux_parser::read_pci(array("RAM memory", "Host bridge"));

			if(count(explode(" ", $info)) == 1)
			{
				$bridge = phodevi_linux_parser::read_pci(array("Bridge", "PCI bridge"));

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
				$southbridge = phodevi_linux_parser::read_pci(array("ISA bridge", "SATA controller"), false);
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
}

?>
