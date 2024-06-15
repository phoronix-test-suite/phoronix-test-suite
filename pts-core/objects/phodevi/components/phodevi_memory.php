<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2024, Phoronix Media
	Copyright (C) 2008 - 2024, Michael Larabel
	phodevi_memory.php: The PTS Device Interface object for system memory

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

class phodevi_memory extends phodevi_device_interface
{
	public static function properties()
	{
		return array(
			'identifier' => new phodevi_device_property('memory_string', phodevi::smart_caching),
			'capacity' => new phodevi_device_property('memory_capacity', phodevi::smart_caching)
			);
	}
	public static function memory_string()
	{
		$mem_string = null;
		$mem_prefix = null;
		$mem_size = array();
		$mem_speed = array();
		$mem_type = array();
		$mem_manufacturer = array();
		$mem_part = array();

		if(phodevi::is_macos())
		{
			$mem_size = phodevi_osx_parser::read_osx_system_profiler('SPMemoryDataType', 'Size', true, array('Empty'));
			$mem_speed = phodevi_osx_parser::read_osx_system_profiler('SPMemoryDataType', 'Speed');
			$mem_type = phodevi_osx_parser::read_osx_system_profiler('SPMemoryDataType', 'Type');
		}
		else if(phodevi::is_solaris())
		{
			$mem_size = phodevi_solaris_parser::read_sun_ddu_dmi_info('MemoryDevice*,InstalledSize');
			$mem_speed = phodevi_solaris_parser::read_sun_ddu_dmi_info('MemoryDevice*,Speed');
			$mem_type = phodevi_solaris_parser::read_sun_ddu_dmi_info('MemoryDevice*,MemoryDeviceType');

			if(is_array($mem_speed) && count($mem_speed) > 0)
			{
				$mem_speed = array_shift($mem_speed);
			}

			$mem_speed = str_replace('MHZ', 'MHz', $mem_speed);
		}
		else if(phodevi::is_windows())
		{
			$mem_size = phodevi_windows_parser::get_wmi_object_multi('CIM_PhysicalMemory', 'Capacity');
			$clock_speed = phodevi_windows_parser::get_wmi_object_multi('CIM_PhysicalMemory', 'ConfiguredClockSpeed');
			$mem_manufacturer = phodevi_windows_parser::get_wmi_object_multi('CIM_PhysicalMemory', 'Manufacturer');
			$mem_part = phodevi_windows_parser::get_wmi_object_multi('CIM_PhysicalMemory', 'PartNumber');
			if(isset($clock_speed[0]) && is_numeric($clock_speed[0]))
			{
				$clock_speed = array_shift($clock_speed);
			}

			$mem_type = null;
			$mem_speed = is_numeric($clock_speed) ? $clock_speed . 'MHz' : null;
		}
		else if(phodevi::is_linux())
		{
			if((phodevi::is_root() || is_readable('/dev/mem')) && pts_client::executable_in_path('dmidecode'))
			{
				$mem_size = phodevi_linux_parser::read_dmidecode('memory', 'Memory Device', 'Size', false, array('Not Installed', 'No Module Installed', 'Undefined', 'Not Specified'));
				$mem_speed = phodevi_linux_parser::read_dmidecode('memory', 'Memory Device', 'Configured Clock Speed', true, array('Unknown', 'Undefined', 'Not Specified'));

				if($mem_speed == false)
				{
					$mem_speed = phodevi_linux_parser::read_dmidecode('memory', 'Memory Device', 'Configured Memory Speed', true, array('Unknown', 'Undefined', 'Not Specified'));
				}

				if($mem_speed == false)
				{
					// "Speed" only reports stock frequency where "Configured Clock Speed" should report the over/underclocked memory
					$mem_speed = phodevi_linux_parser::read_dmidecode('memory', 'Memory Device', 'Speed', true, array('Unknown', 'Undefined', 'Not Specified'));
				}
				$mem_type = phodevi_linux_parser::read_dmidecode('memory', 'Memory Device', 'Type', true, array('Unknown', 'Other', 'Flash', 'Undefined', 'Not Specified'));
				$mem_manufacturer = phodevi_linux_parser::read_dmidecode('memory', 'Memory Device', 'Manufacturer', true, array('Unknown', 'Undefined', 'Not Specified'));
				$mem_part = phodevi_linux_parser::read_dmidecode('memory', 'Memory Device', 'Part Number', true, array('Unknown', 'Undefined', 'Not Specified'));
			}
			else if(pts_client::executable_in_path('udevadm'))
			{
				$memory_device_data = phodevi_linux_parser::read_udevadm_info(array('MEMORY_DEVICE', 'MEMORY_ARRAY'));
				if(isset($memory_device_data['MEMORY_ARRAY_NUM_DEVICES']) && is_numeric($memory_device_data['MEMORY_ARRAY_NUM_DEVICES']) && $memory_device_data['MEMORY_ARRAY_NUM_DEVICES'] > 0)
				{
					for($i = 0; $i < $memory_device_data['MEMORY_ARRAY_NUM_DEVICES']; $i++)
					{
						if(isset($memory_device_data['MEMORY_DEVICE_' . $i . '_SIZE']) && is_numeric($memory_device_data['MEMORY_DEVICE_' . $i . '_SIZE']) && $memory_device_data['MEMORY_DEVICE_' . $i . '_SIZE'] > 1073741824)
						{
							$mem_size[] = round($memory_device_data['MEMORY_DEVICE_' . $i . '_SIZE'] / 1073741824); // byte to GB
						}
						if(isset($memory_device_data['MEMORY_DEVICE_' . $i . '_CONFIGURED_SPEED_MTS']) && is_numeric($memory_device_data['MEMORY_DEVICE_' . $i . '_CONFIGURED_SPEED_MTS']) && $memory_device_data['MEMORY_DEVICE_' . $i . '_CONFIGURED_SPEED_MTS'] > 1000)
						{
							$mem_speed[] = $memory_device_data['MEMORY_DEVICE_' . $i . '_CONFIGURED_SPEED_MTS'] . 'MT/s';
						}
						else if(isset($memory_device_data['MEMORY_DEVICE_' . $i . '_SPEED_MTS']) && is_numeric($memory_device_data['MEMORY_DEVICE_' . $i . '_SPEED_MTS']) && $memory_device_data['MEMORY_DEVICE_' . $i . '_SPEED_MTS'] > 1000)
						{
							$mem_speed[] = $memory_device_data['MEMORY_DEVICE_' . $i . '_SPEED_MTS'] . 'MT/s';
						}

						if(isset($memory_device_data['MEMORY_DEVICE_' . $i . '_MEMORY_TYPE']))
						{
							$mem_type[] = $memory_device_data['MEMORY_DEVICE_' . $i . '_MEMORY_TYPE'];
						}
						else if(isset($memory_device_data['MEMORY_DEVICE_' . $i . '_TYPE']))
						{
							$mem_type[] = $memory_device_data['MEMORY_DEVICE_' . $i . '_TYPE'];
						}
						else if(isset($memory_device_data['MEMORY_DEVICE_' . $i . '_MEMORY_TECHNOLOGY']))
						{
							$mem_type[] = $memory_device_data['MEMORY_DEVICE_' . $i . '_MEMORY_TECHNOLOGY'];
						}

						if(isset($memory_device_data['MEMORY_DEVICE_' . $i . '_MANUFACTURER']))
						{
							$mem_manufacturer[] = $memory_device_data['MEMORY_DEVICE_' . $i . '_MANUFACTURER'];
						}
						if(isset($memory_device_data['MEMORY_DEVICE_' . $i . '_PART_NUMBER']))
						{
							$mem_part[] = $memory_device_data['MEMORY_DEVICE_' . $i . '_PART_NUMBER'];
						}
					}

				}
			}
		}

		if(is_array($mem_type) && !empty($mem_type))
		{
			$mem_type = array_pop($mem_type);
		}
		if(is_array($mem_part)&& !empty($mem_part))
		{
			$mem_part = array_pop($mem_part);
		}
		if(is_array($mem_manufacturer) && !empty($mem_manufacturer))
		{
			$mem_manufacturer = array_pop($mem_manufacturer);
		}
		if(is_array($mem_speed) && !empty($mem_speed))
		{
			$mem_speed = array_pop($mem_speed);
		}

		if($mem_size != false && (!is_array($mem_size) || count($mem_size) != 0))
		{
			for($i = 0; $i < count($mem_size); $i++)
			{
				switch(substr($mem_size[$i], -1))
				{
					case 'K':
						// looks like sometimes Solaris now reports flash chip as memory. its string ends with K
						unset($mem_size[$i]);
						unset($mem_speed[$i]);
						unset($mem_type[$i]);
						break;
					case 'M':
						// report megabytes as MB, just not M, as on Solaris
						$mem_size[$i] .= 'B';
						break;
					case 'B':
						if(strtolower(substr($mem_size[$i], -2, 1)) == 'k')
						{
							// some hardware on Linux via dmidecode reports flash chips
							unset($mem_size[$i]);
							//unset($mem_speed[$i]);
							//unset($mem_type[$i]);
						}
						break;
					default:
						if(phodevi::is_windows() && $mem_size[$i] > 1000000)
						{
							$mem_size[$i] = round($mem_size[$i] / 1048576);
						}
				}
			}

			foreach($mem_size as $i => $mem_stick)
			{
				if(!is_numeric(substr($mem_stick, 0, 3)) && stripos($mem_stick, 'GB') == false)
				{
					// If the memory size isn't at least three digits (basically 128MB+), chances are something is wrong, i.e. reporting flash chip from dmidecode, so get rid of it.
					unset($mem_size[$i]);
				}
			}

			$mem_count = count($mem_size);

			if(!empty($mem_type))
			{
				if(($cut = strpos($mem_type, ' ')) > 0)
				{
					$mem_type = substr($mem_type, 0, $cut);
				}

				if(!in_array($mem_type, array('Other')) && (pts_strings::keep_in_string($mem_type, pts_strings::CHAR_NUMERIC | pts_strings::CHAR_LETTER) == $mem_type))
				{
					$mem_prefix = $mem_type;
				}
			}
			else
			{
				$mem_prefix = null;
			}

			if(!empty($mem_speed))
			{
				if(($cut = strpos($mem_speed, ' (')) > 0)
				{
					$mem_speed = substr($mem_speed, 0, $cut);
				}

				if(!empty($mem_prefix))
				{
					$mem_prefix .= '-';
				}

				$mem_prefix .= str_replace(' ', '', $mem_speed);
			}

			// TODO: Allow a combination of both functions below, so like 2 x 2GB + 3 x 1GB DDR2-800
			if($mem_count > 1 && count(array_unique($mem_size)) > 1)
			{
				$mem_string = implode(' + ', $mem_size) . ' ' . $mem_prefix;
			}
			else
			{
				$t = str_replace(array(' MB', ' GB'), '', $mem_size[0]);
				if(($mem_count * $t) != phodevi::read_property('memory', 'capacity') && phodevi::read_property('memory', 'capacity') % $t == 0)
				{
					// This makes sure the correct number of RAM modules is reported...
					// On at least Linux with dmidecode on an AMD Opteron multi-socket setup it's only showing the data for one socket

					if(is_numeric($mem_size[0]))
					{
						$mem_mb = $mem_size[0] < 1024 ? $mem_size[0] * 1024 : $mem_size[0];
						$mem_count = ceil(phodevi::read_property('memory', 'capacity') / $mem_mb);
					}
				}

				$product_string = null;

				if(!empty($mem_manufacturer))
				{
					$mem_manufacturer = str_ireplace(array(' Technology', ' Intl'), '', $mem_manufacturer);
				}
				if(isset($mem_manufacturer[2]) && pts_strings::is_alpha(str_replace('-', '', $mem_manufacturer[0])) && stripos($mem_manufacturer, 'manufacturer') === false  && stripos($mem_manufacturer, 'part') === false && stripos($mem_manufacturer, 'module') === false && stripos($mem_manufacturer, 'dimm') === false && pts_strings::is_alpha(str_replace(array(' ', '-'), '', $mem_manufacturer)))
				{
					$product_string .= ' ' . $mem_manufacturer;
				}

				if(!empty($mem_part) && ($x = strpos($mem_part, '/')) !== false)
				{
					// Cleanup/shorten strings like KHX2133C13S4/4G
					$mem_part = substr($mem_part, 0, $x);
				}
				if(isset($mem_part[2]) && stripos($mem_part, 'part') === false && stripos($mem_part, 'module') === false && stripos($mem_part, 'dimm') === false && substr($mem_part, 0, 2) != '0x' && !isset($mem_part[24]) && pts_strings::is_alnum(str_replace(array('-', ' ', '.'), '', $mem_part)))
				{
					$product_string .= ' ' . $mem_part;
				}

				if(is_numeric($mem_size[0]) && stripos($mem_size[0], 'b') === false)
				{
					if($mem_size[0] >= 1024)
					{
						$mem_size[0] .= 'MB';
					}
					else
					{
						$mem_size[0] .= 'GB';
					}
				}

				$mem_string = $mem_count . ' x ' . $mem_size[0] . ' ' . $mem_prefix . $product_string;
			}
		}

		if(empty($mem_string))
		{
			$ram_in_mb = phodevi::read_property('memory', 'capacity');

			if($ram_in_mb != null)
			{
				$mem_string = $ram_in_mb . 'MB';

				if($ram_in_mb > 5000)
				{
					$ram_in_gb = ceil($ram_in_mb / 1024);
					if($ram_in_gb % 2 == 1)
					{
						$ram_in_gb++;
					}
					if($ram_in_gb > 100 && $ram_in_gb % 16 !== 0)
					{
						$ram_in_gb += 16 - ($ram_in_gb % 16);
					}
					if($ram_in_gb >= 6)
					{
						$mem_string = $ram_in_gb . 'GB';
					}
				}
			}
		}

		return empty($mem_string) ? '' : trim(str_replace(array('Unknown ', 'Undefined'), '', $mem_string));
	}
	public static function memory_capacity()
	{
		// Returns physical memory capacity
		if(isset(phodevi::$vfs->meminfo))
		{
			$info = phodevi::$vfs->meminfo;
			$info = substr($info, strpos($info, 'MemTotal:') + 9);
			$info = intval(trim(substr($info, 0, strpos($info, 'kB'))));
			$info = floor($info / 1024);

			if(is_numeric($info) && $info > 950)
			{
				if($info > 4608)
				{
					$info = ceil($info / 1024) * 1024;
				}
				else if($info > 1536)
				{
					$info = ceil($info / 512) * 512;
				}
				else
				{
					$info = ceil($info / 256) * 256;
				}
			}
		}
		else if(phodevi::is_solaris())
		{
			$info = shell_exec('prtconf 2>&1 | grep Memory');
			$info = substr($info, strpos($info, ':') + 2);
			$info = substr($info, 0, strpos($info, 'Megabytes'));
		}
		else if(phodevi::is_bsd())
		{
			$mem_size = phodevi_bsd_parser::read_sysctl('hw.physmem');

			if($mem_size == false)
			{
				$mem_size = phodevi_bsd_parser::read_sysctl('hw.realmem');
			}

			$info = ceil(floor($mem_size / 1048576) / 256) * 256;
		}
		else if(phodevi::is_macos())
		{
			$info = phodevi_osx_parser::read_osx_system_profiler('SPHardwareDataType', 'Memory');
			$info = explode(' ', $info);
			$info = (isset($info[1]) && $info[1] == 'GB' ? $info[0] * 1024 : $info[0]);
		}
		else if(phodevi::is_windows())
		{
			$info = phodevi_windows_parser::get_wmi_object('win32_operatingsystem', 'TotalVisibleMemorySize');
			if(is_numeric($info))
			{
				$info = ceil($info / 1000);
			}
			else
			{
				$info = null;
			}
		}
		else
		{
			$info = null;
		}

		return $info;
	}
}

?>
