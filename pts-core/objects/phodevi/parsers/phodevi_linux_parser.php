<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2024, Phoronix Media
	Copyright (C) 2008 - 2024, Michael Larabel
	phodevi_linux_parser.php: General parsing functions specific to Linux

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

class phodevi_linux_parser
{
	public static function read_ipmitool_sensor($sensors, $default_value = false)
	{
		$value = $default_value;
		$ipmitool = shell_exec('ipmitool sdr list 2>&1');

		foreach(pts_arrays::to_array($sensors) as $sensor)
		{
			$hit = stripos($ipmitool, $sensor);

			if($hit !== false)
			{
				$trimmed = substr($ipmitool, ($hit + strlen($sensor)));
				$trimmed = substr($trimmed, 0, strpos($trimmed, PHP_EOL));
				$trimmed = explode('|', $trimmed);

				if(count($trimmed) == 3)
				{
					$value = explode(' ', trim($trimmed[1]));
					$value = $value[0];
					break;
				}
			}
		}

		return $value;
	}
	public static function read_ipmitool_dcmi_power()
	{
		$value = false;
		$ipmitool = shell_exec('ipmitool dcmi power reading 2>&1');

		$sensor = 'Instantaneous power reading:';
		$hit = stripos($ipmitool, $sensor);

		if($hit !== false)
		{
			$trimmed = substr($ipmitool, ($hit + strlen($sensor)));
			$trimmed = substr($trimmed, 0, strpos($trimmed, PHP_EOL));
			$trimmed = trim($trimmed);
			$trimmed = explode(' ', $trimmed);

			if(count($trimmed) == 2)
			{
				$value = $trimmed[0];
			}
		}

		return $value;
	}
	public static function read_sysfs_node($search, $type = 'NUMERIC', $node_dir_check = null, $find_position = 1, $return_node = false)
	{
		static $sysfs_file_cache = null;
		$arg_hash = crc32(serialize(func_get_args()));

		if(!isset($sysfs_file_cache[$arg_hash]))
		{
			$find_count = 0;

			foreach(pts_file_io::glob($search) as $sysfs_file)
			{
				if(is_array($node_dir_check))
				{
					$skip_to_next = false;
					$sysfs_dir = dirname($sysfs_file) . '/';

					foreach($node_dir_check as $node_check => $value_check)
					{
						if(!is_file($sysfs_dir . $node_check))
						{
							$skip_to_next = true;
							break;
						}
						else if($value_check !== true)
						{
							$value_check_value = pts_file_io::file_get_contents($sysfs_dir . $node_check);

							foreach(explode(',', $value_check) as $check)
							{
								if(isset($check[0]) && $check[0] == '!')
								{
									if($value_check_value == substr($check, 1))
									{
										$skip_to_next = true;
										break;
									}
								}
								else if($value_check_value != $check)
								{
									$skip_to_next = true;
									break;
								}
							}
						}

						if($skip_to_next)
						{
							break;
						}
					}

					if($skip_to_next)
					{
						continue;
					}
				}

				$sysfs_value = pts_file_io::file_get_contents($sysfs_file);

				switch($type)
				{
					case 'NUMERIC':
						if(is_numeric($sysfs_value))
						{
							$sysfs_file_cache[$arg_hash] = $sysfs_file;
						}
						break;
					case 'POSITIVE_NUMERIC':
						if(is_numeric($sysfs_value) && $sysfs_value >= 0)
						{
							$sysfs_file_cache[$arg_hash] = $sysfs_file;
						}
						break;
					case 'NOT_EMPTY':
						if(!empty($sysfs_value))
						{
							$sysfs_file_cache[$arg_hash] = $sysfs_file;
						}
						break;
					case 'NO_CHECK':
						$sysfs_file_cache[$arg_hash] = $sysfs_file;
						break;
				}

				$find_count++;
				if($find_count < $find_position)
				{
					unset($sysfs_file_cache[$arg_hash]);
				}

				if(isset($sysfs_file_cache[$arg_hash]))
				{
					break;
				}
			}

			if(!isset($sysfs_file_cache[$arg_hash]))
			{
				$sysfs_file_cache[$arg_hash] = false;
			}
		}

		if($return_node)
		{
			return $sysfs_file_cache[$arg_hash] == false ? -1 : $sysfs_file;
		}

		return $sysfs_file_cache[$arg_hash] == false ? -1 : pts_file_io::file_get_contents($sysfs_file_cache[$arg_hash]);
	}
	public static function read_udevadm_info($data_r, $node = '/sys/devices/virtual/dmi/id')
	{
		$device_data = array();

		if(pts_client::executable_in_path('udevadm'))
		{
			$udev_output = shell_exec('udevadm info ' . $node . ' 2>/dev/null | grep -e ' . implode(' -e ', $data_r));
			if(!empty($udev_output))
			{
				foreach(explode(PHP_EOL, $udev_output) as $line)
				{
					$line = explode('=', str_replace(array('E: ', '<OUT OF SPEC>', '00000000', 'Not Specified', 'Unknown', 'None'), '', $line));
					if(count($line) == 2 && !empty($line[0]) && !empty($line[1]))
					{
						$device_data[$line[0]] = $line[1];
					}
				}
			}
		}
		return $device_data;
	}
	public static function read_dmidecode($type, $sub_type, $object, $find_once = false, $ignore = null)
	{
		// Read Linux dmidecode
		$value = array();

		if((phodevi::is_root() || is_readable('/dev/mem')) && pts_client::executable_in_path('dmidecode'))
		{
			$ignore = array_map('strtolower', pts_arrays::to_array($ignore));

			$dmidecode = shell_exec('dmidecode --type ' . $type . ' 2>&1');
			$sub_type = "\n" . $sub_type . "\n";

			do
			{
				$sub_type_start = strpos($dmidecode, $sub_type);

				if($sub_type_start !== false)
				{
					$dmidecode = substr($dmidecode, ($sub_type_start + strlen($sub_type)));
					$dmidecode_section = substr($dmidecode, 0, strpos($dmidecode, "\n\n"));
					$dmidecode = substr($dmidecode, strlen($dmidecode_section));
					$dmidecode_elements = explode("\n", $dmidecode_section);

					$found_in_section = false;
					for($i = 0; $i < count($dmidecode_elements) && $found_in_section == false; $i++)
					{
						$dmidecode_r = pts_strings::colon_explode($dmidecode_elements[$i]);

						if($dmidecode_r[0] == $object && isset($dmidecode_r[1]) && !in_array(strtolower($dmidecode_r[1]), $ignore))
						{
							array_push($value, $dmidecode_r[1]);
							$found_in_section = true;
						}
					}
				}
			}
			while($sub_type_start !== false && ($find_once == false || $found_in_section == false));
		}

		if(count($value) == 0)
		{
			$value = false;
		}
		else if($find_once && count($value) == 1)
		{
			$value = $value[0];
		}

		return $value;
	}
	public static function read_sys_disk_speed($path, $to_read)
	{
		$delta_mb = -1; // in MB/s
		$measure_time = 1000000; // microseconds

		if(is_file($path))
		{
			switch($to_read)
			{
				case 'WRITE':
					$sector = 6;
					break;
				case 'READ':
					$sector = 2;
					break;
				default:
					return $delta_mb;
					break;
			}

			$start_stat = pts_strings::trim_spaces(file_get_contents($path));
			usleep($measure_time);
			$end_stat = pts_strings::trim_spaces(file_get_contents($path));

			$start_stat = explode(' ', $start_stat);
			$end_stat = explode(' ', $end_stat);

			$delta_sectors = $end_stat[$sector] - $start_stat[$sector];

			// TODO check sector size instead of hardcoding it
			$delta_mb = $delta_sectors * 512 / 1048576;
			$speed = $delta_mb * 1000000 / $measure_time;
		}

		return pts_math::set_precision($speed, 2);
	}
	public static function read_sys_dmi($identifier)
	{
		$dmi = false;

		if(is_dir('/sys/class/dmi/id/'))
		{
			$ignore_words = phodevi_parser::hardware_values_to_remove();

			foreach(pts_arrays::to_array($identifier) as $id)
			{
				if(is_readable('/sys/class/dmi/id/' . $id))
				{
					$dmi_file = pts_file_io::file_get_contents('/sys/class/dmi/id/' . $id);

					if(!empty($dmi_file) && !in_array(strtolower($dmi_file), $ignore_words))
					{
						$dmi = $dmi_file;
						break;
					}
				}
			}
		}

		return $dmi;
	}
	public static function read_cpuinfo($attribute, $cpuinfo = false)
	{
		// Read CPU information
		$cpuinfo_matches = array();

		if($cpuinfo == false)
		{
			if(is_file('/proc/cpuinfo'))
			{
				$cpuinfo = file_get_contents('/proc/cpuinfo');
			}
			else
			{
				return $cpuinfo_matches;
			}
		}

		foreach(pts_arrays::to_array($attribute) as $attribute_check)
		{
			$cpuinfo_lines = explode("\n", $cpuinfo);

			foreach($cpuinfo_lines as $line)
			{
				$line = pts_strings::trim_explode(': ', $line, 2);

				if(!isset($line[0]))
				{
					continue;
				}

				$this_attribute = $line[0];
				$this_value = (count($line) > 1 ? $line[1] : null);

				if($this_attribute == $attribute_check)
				{
					array_push($cpuinfo_matches, $this_value);
				}
			}

			if(count($cpuinfo_matches) != 0)
			{
				break;
			}
		}

		return $cpuinfo_matches;
	}
	public static function systemctl_active($service)
	{
		$active = false;
		if(pts_client::executable_in_path('systemctl'))
		{
			$is_active = trim(shell_exec('systemctl is-active ' . $service . ' 2>/dev/null'));
			if($is_active == 'active')
			{
				$active = true;
			}
		}
		return $active;
	}
	public static function cpuinfo_to_array($cpuinfo = null)
	{
		if($cpuinfo == null && is_file('/proc/cpuinfo'))
		{
			$cpuinfo = file_get_contents('/proc/cpuinfo');
		}
		if(empty($cpuinfo))
		{
			return array();
		}
		
		$cpuinfo_lines = explode("\n", $cpuinfo);
		$cpuinfo_r = array();

		foreach(explode("\n", $cpuinfo) as $line)
		{
			$line = pts_strings::trim_explode(': ', $line, 2);
			if(!isset($line[0]))
			{
				continue;
			}

			$this_attribute = $line[0];
			$this_value = trim(count($line) > 1 ? $line[1] : '');
			if(in_array($this_attribute, array('flags', 'bugs', 'power management')))
			{
				$this_value = explode(' ', $this_value);
			}
			$cpuinfo_r[$this_attribute] = $this_value;
		}
		
		return $cpuinfo_r;
	}
	public static function read_cpuinfo_single($attribute, $cpuinfo = false)
	{
		$cpuinfo = self::read_cpuinfo($attribute, $cpuinfo);
		if(!empty($cpuinfo))
		{
			return array_pop($cpuinfo);
		}
		return null;
	}
	public static function read_lsb_distributor_id()
	{
		$vendor = phodevi_linux_parser::read_lsb('Distributor ID');

		// Quirks for derivative distributions that don't know how to handle themselves properly
		if($vendor == 'MandrivaLinux' && phodevi_linux_parser::read_lsb('Description') == 'PCLinuxOS')
		{
			// PC Linux OS only stores its info in /etc/pclinuxos-release
			$vendor = false;
		}

		return $vendor;
	}
	public static function read_lsb($desc)
	{
		// Read LSB Release information, Linux Standards Base
		$info = false;

		if(pts_client::executable_in_path('lsb_release'))
		{
			static $output = null;

			if($output == null)
			{
				$output = shell_exec('lsb_release -a 2>&1');
			}

			if(($pos = strrpos($output, $desc . ':')) !== false)
			{
				$info = substr($output, $pos + strlen($desc) + 1);
				$info = trim(substr($info, 0, strpos($info, "\n")));
			}

			if(strtolower($info) == 'n/a')
			{
				$info = false;
			}
		}

		return $info;
	}
	public static function read_acpi($point, $match)
	{
		// Read ACPI - Advanced Configuration and Power Interface
		$value = false;
		$point = pts_arrays::to_array($point);

		for($i = 0; $i < count($point) && empty($value); $i++)
		{
			if(is_file('/proc/acpi' . $point[$i]))
			{
				$acpi_lines = explode("\n", file_get_contents('/proc/acpi' . $point[$i]));

				for($i = 0; $i < count($acpi_lines) && $value == false; $i++)
				{
					$line = pts_strings::trim_explode(': ', $acpi_lines[$i]);

					if(!isset($line[0]))
					{
						continue;
					}

					$this_attribute = $line[0];
					$this_value = (count($line) > 1 ? $line[1] : null);

					if($this_attribute == $match)
					{
						$value = $this_value;
					}
				}
			}
		}

		return $value;
	}
	public static function read_pci_subsystem_value($desc)
	{
		$lspci = shell_exec('lspci -v 2> /dev/null');
		$subsystem = null;

		if(empty($lspci))
		{
			return null;
		}

		foreach(pts_arrays::to_array($desc) as $check)
		{
			if(($hit = strpos($lspci, $check)) !== false)
			{
				$lspci = substr($lspci, $hit);

				if(($hit = strpos($lspci, 'Subsystem: ')) !== false)
				{
					$lspci = substr($lspci, ($hit + strlen('Subsystem: ')));
					$lspci = substr($lspci, 0, strpos($lspci, PHP_EOL));

					$vendors = array(
						'Sapphire Technology' => 'Sapphire',
						'PC Partner' => 'Sapphire',
						'Micro-Star International' => 'MSI',
						'XFX' => 'XFX',
						'ASUS' => 'ASUS',
						'Gigabyte' => 'Gigabyte',
						'Elitegroup' => 'ECS',
						'eVga' => 'eVGA',
						'Hightech Information System' => 'HIS',
						'Zotac' => 'Zotac'
						);

					foreach($vendors as $vendor => $clean_vendor)
					{
						if(stripos($lspci, $vendor) !== false)
						{
							$subsystem = $clean_vendor;
							break;
						}
					}
				}
			}
		}

		return $subsystem;
	}
	public static function read_pci($desc, $clean_string = true)
	{
		// Read PCI bus information
		static $pci_info = null;
		$info = false;
		$desc = pts_arrays::to_array($desc);

		if($pci_info == null)
		{
			if(!is_executable('/usr/bin/lspci') && is_executable('/sbin/lspci'))
			{
				$lspci_cmd = '/sbin/lspci';
			}
			else if(($lspci = pts_client::executable_in_path('lspci')))
			{
				$lspci_cmd = $lspci;
			}
			else
			{
				return false;
			}

			$pci_info = shell_exec($lspci_cmd . ' 2> /dev/null');
		}
		if(empty($pci_info))
		{
			return false;
		}

		for($i = 0; $i < count($desc) && empty($info); $i++)
		{
			if(substr($desc[$i], -1) != ':')
			{
				$desc[$i] .= ':';
			}

			if(($pos = strpos($pci_info, $desc[$i])) !== false)
			{
				$sub_pci_info = str_replace(array('[AMD]', '[AMD/ATI]', ' Limited'), '', substr($pci_info, $pos + strlen($desc[$i])));
				$EOL = strpos($sub_pci_info, "\n");

				if($clean_string)
				{
					if(($temp = strpos($sub_pci_info, '/')) < $EOL && $temp > 0)
					{
						if(($temp = strpos($sub_pci_info, ' ', ($temp + 2))) < $EOL && $temp > 0)
						{
							$EOL = $temp;
						}
					}

					if(($temp = strpos($sub_pci_info, '(')) < $EOL && $temp > 0)
					{
						$EOL = $temp;
					}

					if(($temp = strpos($sub_pci_info, '[')) < $EOL && $temp > 0)
					{
						$EOL = $temp;
					}
				}

				$sub_pci_info = trim(substr($sub_pci_info, 0, $EOL));

				if(($strlen = strlen($sub_pci_info)) >= 6 && $strlen < 128)
				{
					$info = pts_strings::strip_string($sub_pci_info);
				}
			}
		}

		return $info;
	}
	public static function read_pci_multi($desc, $clean_string = true)
	{
		// Read PCI bus information
		static $pci_info = null;
		$info = array();
		$desc = pts_arrays::to_array($desc);

		if($pci_info == null)
		{
			if(!is_executable('/usr/bin/lspci') && is_executable('/sbin/lspci'))
			{
				$lspci_cmd = '/sbin/lspci';
			}
			else if(($lspci = pts_client::executable_in_path('lspci')))
			{
				$lspci_cmd = $lspci;
			}
			else
			{
				return false;
			}

			$pci_info = shell_exec($lspci_cmd . ' 2> /dev/null');
		}
		
		if(empty($pci_info))
		{
			return false;
		}

		for($i = 0; $i < count($desc); $i++)
		{
			if(substr($desc[$i], -1) != ':')
			{
				$desc[$i] .= ':';
			}
			$pos = 0;
			while(($pos = strpos($pci_info, $desc[$i], $pos)) !== false)
			{
				$pos += strlen($desc[$i]);
				$sub_pci_info = str_replace(array('[AMD]', '[AMD/ATI]', ' Limited', ' Connection', ' Gigabit', ' Wireless', '(1)', '(2)', '(3)', '(4)', '(5)', '(6)', '(7)', '(8)', '(9)'), '', substr($pci_info, $pos));
				$EOL = strpos($sub_pci_info, "\n");

				if($clean_string)
				{
					if(($temp = strpos($sub_pci_info, '/')) < $EOL && $temp > 0)
					{
						if(($temp = strpos($sub_pci_info, ' ', ($temp + 2))) < $EOL && $temp > 0)
						{
							$EOL = $temp;
						}
					}

					if(($temp = strpos($sub_pci_info, '(')) < $EOL && $temp > 0)
					{
						$EOL = $temp;
					}

					if(($temp = strpos($sub_pci_info, '[')) < $EOL && $temp > 0)
					{
						$EOL = $temp;
					}
				}

				$sub_pci_info = trim(substr($sub_pci_info, 0, $EOL));

				if(($strlen = strlen($sub_pci_info)) >= 6 && $strlen < 128)
				{
					$info[] = pts_strings::strip_string($sub_pci_info);
				}
			}
		}

		return $info;
	}
	public static function read_sensors($attributes)
	{
		// Read LM_Sensors
		$value = false;

		if(isset(phodevi::$vfs->sensors))
		{
			$sensors = phodevi::$vfs->sensors;
			$sensors_lines = explode("\n", $sensors);
			$attributes = pts_arrays::to_array($attributes);

			for($j = 0; $j < count($attributes) && empty($value); $j++)
			{
				$attribute = $attributes[$j];
				for($i = 0; $i < count($sensors_lines) && $value == false; $i++)
				{
					$line = pts_strings::trim_explode(': ', $sensors_lines[$i]);

					if(!isset($line[0]))
					{
						continue;
					}

					$this_attribute = $line[0];

					if($this_attribute == $attribute)
					{
						$this_remainder = trim(str_replace(array('+', '°'), ' ', $line[1]));
						$this_value = substr($this_remainder, 0, strpos($this_remainder, ' '));

						if(is_numeric($this_value) && $this_value > 0)
						{
							$value = $this_value;
						}
					}
				}
			}
		}

		return $value;
	}
}

?>
