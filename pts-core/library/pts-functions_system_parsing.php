<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
	pts-functions_system_parsing.php: System functions that perform the actual system hardware/software parsing on Linux.

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

function read_acpi($point, $match)
{
	// Read ACPI - Advanced Configuration and Power Interface
	$value = false;
	$point = pts_to_array($point);

	for($i = 0; $i < count($point) && empty($value); $i++)
	{
		if(is_file("/proc/acpi" . $point[$i]))
		{
			$acpi_lines = explode("\n", file_get_contents("/proc/acpi" . $point[$i]));

			for($i = 0; $i < count($acpi_lines) && $value == false; $i++)
			{
				$line = explode(": ", $acpi_lines[$i]);
				$this_attribute = trim($line[0]);
				$this_value = (count($line) > 1 ? trim($line[1]) : null);

				if($this_attribute == $match)
				{
					$value = $this_value;
				}
			}
		}
	}

	return $value;
}
function read_hal($name, $UDI = null)
{
	// Read HAL - Hardware Abstraction Layer
	static $remove_words = null;
	$info = false;
	$name = pts_to_array($name);

	if(empty($remove_words) && is_file(STATIC_DIR . "hal-values-remove.txt"))
	{
		$word_file = trim(file_get_contents(STATIC_DIR . "hal-values-remove.txt"));
		$remove_words = array_map("trim", explode("\n", $word_file));
	}

	for($i = 0; $i < count($name) && empty($info); $i++)
	{
		if(empty($UDI))
		{
			$info = shell_exec("lshal 2>&1 | grep \"" . $name[$i] . "\"");
		}
		else
		{
			$info = shell_exec("lshal -u $UDI 2>&1 | grep \"" . $name[$i] . "\"");
		}

		if(($pos = strpos($info, $name[$i] . " = '")) !== false)
		{
			$info = substr($info, $pos + strlen($name[$i] . " = '"));
			$info = trim(substr($info, 0, strpos($info, "'")));
		}

		if(empty($info) || in_array(strtolower($info), $remove_words))
		{
			$info = false;
		}
	}

	return $info;
}
function read_system_hal($name)
{
	// Read system HAL
	$hal = read_hal($name, "/org/freedesktop/Hal/devices/computer");

	if($hal == false)
	{
		$hal = read_hal($name);
	}

	return $hal;
}
function read_sensors($attributes)
{
	// Read LM_Sensors
	$value = false;
	$sensors = shell_exec("sensors 2>&1");
	$sensors_lines = explode("\n", $sensors);
	$attributes = pts_to_array($attributes);

	for($j = 0; $j < count($attributes) && empty($value); $j++)
	{
		$attribute = $attributes[$j];
		for($i = 0; $i < count($sensors_lines) && $value == null; $i++)
		{
			$line = explode(": ", $sensors_lines[$i]);
			$this_attribute = trim($line[0]);

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

	return $value;
}
function read_pci($desc, $clean_string = true)
{
	// Read PCI bus information
	static $pci_info = null;
	$info = false;
	$desc = pts_to_array($desc);

	if(empty($pci_info))
	{
		if(!is_executable("/usr/bin/lspci") && is_executable("/sbin/lspci"))
		{
			$lspci_cmd = "/sbin/lspci";
		}
		else if(($lspci = pts_executable_in_path("lspci")) != false)
		{
			$lspci_cmd = $lspci;
		}
		else
		{
			return false;
		}

		$pci_info = shell_exec($lspci_cmd . " 2>&1");
	}

	for($i = 0; $i < count($desc) && empty($info); $i++)
	{
		if(substr($desc[$i], -1) != ":")
		{
			$desc[$i] .= ":";
		}

		if(($pos = strpos($pci_info, $desc[$i])) !== false)
		{
			$sub_pci_info = str_replace(array("[AMD]"), "", substr($pci_info, $pos + strlen($desc[$i])));
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

			if(($strlen = strlen($sub_pci_info)) >= 6 && $strlen < 96)
			{
				$info = pts_clean_information_string($sub_pci_info);
			}
		}
	}

	return $info;
}
function read_lsb($desc)
{
	// Read LSB Release information, Linux Standards Base
	static $output = null;
	$info = false;

	if($output == null)
	{
		$output = shell_exec("lsb_release -a 2>&1");
	}

	if(($pos = strrpos($output, $desc . ":")) !== false)
	{
		$info = substr($output, $pos + strlen($desc) + 1);
		$info = trim(substr($info, 0, strpos($info, "\n")));
	}

	return $info;
}
function read_sysctl($desc)
{
	// Read sysctl, used by *BSDs
	$info = false;
	$desc = pts_to_array($desc);

	for($i = 0; $i < count($desc) && empty($info); $i++)
	{
		$output = shell_exec("sysctl " . $desc[$i] . " 2>&1");

		if((($point = strpos($output, ":")) > 0 || ($point = strpos($output, "=")) > 0) && strpos($output, "unknown oid") === false)
		{
			$info = trim(substr($output, $point + 1));
		}
	}

	return $info;
}
function read_cpuinfo($attribute)
{
	// Read CPU information
	$cpuinfo_matches = array();

	if(is_file("/proc/cpuinfo"))
	{
		$cpuinfo_lines = explode("\n", file_get_contents("/proc/cpuinfo"));

		foreach($cpuinfo_lines as $line)
		{
			$line = explode(": ", $line);
			$this_attribute = trim($line[0]);
			$this_value = (count($line) > 1 ? trim($line[1]) : "");

			if($this_attribute == $attribute)
			{
				array_push($cpuinfo_matches, $this_value);
			}
		}
	}

	return $cpuinfo_matches;
}
function read_nvidia_extension($attribute)
{
	// Read NVIDIA's NV Extension
	$info = shell_exec("nvidia-settings --query " . $attribute . " 2>&1");
	$nv_info = false;

	if(($pos = strpos($info, $attribute)) > 0)
	{
		$nv_info = substr($info, strpos($info, "):") + 3);
		$nv_info = substr($nv_info, 0, strpos($nv_info, "\n"));
		$nv_info = trim(substr($nv_info, 0, strrpos($nv_info, ".")));
	}

	return $nv_info;
}
function read_xdpy_monitor_info()
{
	// Read xdpyinfo monitor information
	$info = trim(shell_exec("xdpyinfo -ext XINERAMA 2>&1 | grep head"));
	$monitor_info = array();

	foreach(explode("\n", $info) as $xdpyinfo_line)
	{
		if(!empty($xdpyinfo_line) && strpos($xdpyinfo_line, "0x0") == false)
		{
			array_push($monitor_info, $xdpyinfo_line);
		}
	}

	return $monitor_info;
}
function read_amd_graphics_adapters()
{
	// Read ATI/AMD graphics hardware using aticonfig
	$info = trim(shell_exec("aticonfig --list-adapters 2>&1"));
	$adapters = array();

	foreach(explode("\n", $info) as $line)
	{
		if(($last_point = strrpos($line, ".")) > 0)
		{
			array_push($adapters, substr($line, $last_point + 3));
		}
	}

	return $adapters;
}
function read_amd_pcsdb($attribute)
{
	// Read AMD's AMDPCSDB, AMD Persistent Configuration Store Database
	$info = shell_exec("aticonfig --get-pcs-key=" . $attribute . " 2>&1");
	$ati_info = "";

	if(($pos = strpos($info, ":")) > 0 && strpos($info, "Error") === false)
	{
		$ati_info = substr($info, $pos + 2);
		$ati_info = substr($ati_info, 0, strpos($ati_info, " "));
	}

	if(empty($ati_info))
	{
		// Using aticonfig --get-pcs-key failed, switch to the PTS direct parser of AMDPCSDB
		$ati_info = read_amd_pcsdb_direct_parser($attribute);
	}

	return $ati_info;
}
function read_amd_pcsdb_direct_parser($attribute, $find_once = false)
{
	// Read AMD's AMDPCSDB, AMD Persistent Configuration Store Database but using our own internal parser instead of relying upon aticonfig
	$amdpcsdb_file = "";
	$last_found_section_count = -1;
	$this_section_count = 0;
	$attribute_values = array();
	$attribute = explode(",", $attribute);

	if(count($attribute) == 2)
	{
		$attribute_prefix = array_reverse(explode("/", $attribute[0]));
		$attribute_key = $attribute[1];
		$is_in_prefix = false;

		if(is_file("/etc/ati/amdpcsdb"))
		{
			$amdpcsdb_file = explode("\n", file_get_contents("/etc/ati/amdpcsdb"));
		}

		for($l = 0; $l < count($amdpcsdb_file) && ($find_once == false || $last_found_section_count == -1); $l++)
		{
			$line = trim($amdpcsdb_file[$l]);

			if(substr($line, 0, 1) == "[" && substr($line, -1) == "]")
			{
				// AMDPCSDB Header
				$prefix_matches = true;
				$header = array_reverse(explode("/", substr($line, 1, -1)));

				for($i = 0; $i < count($attribute_prefix) && $i < count($header) && $prefix_matches == true; $i++)
				{
					if($attribute_prefix[$i] != $header[$i] && !pts_proximity_match($attribute_prefix[$i], $header[$i]))
					{
						$prefix_matches = false;
					}
				}

				if($prefix_matches)
				{
					$is_in_prefix = true;
					$this_section_count++;
				}
				else
				{
					$is_in_prefix = false;
				}
			}
			else if($is_in_prefix && $this_section_count != $last_found_section_count && count(($key_components = explode("=", $line))) == 2)
			{
				// AMDPCSDB Value
				if($key_components[0] == $attribute_key)
				{
					$value_type = substr($key_components[1], 0, 1);
					$value = substr($key_components[1], 1);

					switch($value_type)
					{
						case "V":
							// Value
							if(is_numeric($value) && strlen($value) < 9)
							{
								$value = dechex($value);
								$value = "0x" . str_repeat(0, 8 - strlen($value)) . strtoupper($value);						
							}
						break;
						case "R":
							// Raw
						break;
						case "S":
							// String
						break;

					}
					array_push($attribute_values, $value);
					$last_found_section_count = $this_section_count;
				}
			}
		}
	}

	if(count($attribute_values) == 0)
	{
		$attribute_values = "";
	}
	else if(count($attribute_values) == 1)
	{
		$attribute_values = $attribute_values[0];
	}

	return $attribute_values;
}
function read_ati_overdrive($attribute, $adapter = 0)
{
	// Read ATI OverDrive information using aticonfig
	// OverDrive supported in fglrx 8.52+ drivers
	$value = false;

	if($attribute == "Temperature")
	{
		$info = shell_exec("aticonfig --adapter=" . $adapter . " --od-gettemperature 2>&1");

		if(($start = strpos($info, "Temperature -")) !== false)
		{
			$info = substr($info, $start + 14);
			$value = substr($info, 0, strpos($info, " C"));
		}
	}
	else
	{
		$info = shell_exec("aticonfig --adapter=" . $adapter . " --od-getclocks 2>&1");

		if(strpos($info, "GPU") !== false)
		{
			foreach(explode("\n", $info) as $line)
			{
				$line_r = explode(":", $line);

				if(count($line_r) == 2)
				{
					$od_option = str_replace(" ", "", trim($line_r[0]));

					if($od_option == $attribute)
					{
						$od_value = pts_trim_spaces($line_r[1]);
						$od_value = str_replace(array("%"), "", $od_value);
						$od_value_r = explode(" ", $od_value);

						$value = (count($od_value_r) == 1 ? $od_value_r[0] : $od_value_r);			
					}
				}
			}
		}
	}

	return $value;
}
function read_system_memory_usage($TYPE = "TOTAL", $READ = "USED")
{
	// Reads system memory usage
	$mem = explode("\n", shell_exec("free -t -m 2>&1"));
	$grab_line = null;
	$mem_usage = -1;

	for($i = 0; $i < count($mem) && empty($grab_line); $i++)
	{
		$line_parts = explode(":", $mem[$i]);

		if(count($line_parts) == 2)
		{
			$line_type = trim($line_parts[0]);

			if($TYPE == "MEMORY" && $line_type == "Mem")
			{
				$grab_line = $line_parts[1];
			}
			else if($TYPE == "SWAP" && $line_type == "Swap")
			{
				$grab_line = $line_parts[1];
			}
			else if($TYPE == "TOTAL" && $line_type == "Total")
			{
				$grab_line = $line_parts[1];
			}
		}
	}

	if(!empty($grab_line))
	{
		$grab_line = pts_trim_spaces($grab_line);
		$mem_parts = explode(" ", $grab_line);

		if($READ == "USED")
		{
			if(count($mem_parts) >= 2 && is_numeric($mem_parts[1]))
			{
				$mem_usage = $mem_parts[1];
			}
		}
		else if($READ == "TOTAL")
		{
			if(count($mem_parts) >= 1 && is_numeric($mem_parts[0]))
			{
				$mem_usage = $mem_parts[0];
			}
		}
		else if($READ == "FREE")
		{
			if(count($mem_parts) >= 3 && is_numeric($mem_parts[2]))
			{
				$mem_usage = $mem_parts[2];
			}
		}
	}

	return $mem_usage;
}
function read_hddtemp($disk = null)
{
	// Read hard drive temperature using hddtemp
	$hdd_temperature = -1;

	if(empty($disk))
	{
		// TODO: Have it determine what disks are present and whether it's sdX or hdX, etc...
		$disk = "/dev/sda";
	}

	// For most situations this won't work since hddtemp usually requires root access
	$info = trim(shell_exec("hddtemp " . $disk . " 2>&1"));

	if(($start_pos = strrpos($info, ": ")) > 0 && ($end_pos = strrpos($info, "°")) > $start_pos)
	{
		$temperature = substr($info, ($start_pos + 2), ($end_pos - $start_pos - 2));

		if(is_numeric($temperature))
		{
			$unit = substr($info, $end_pos + 2, 1);
			if($unit == "F")
			{
				$temperature = pts_trim_double((($temperature - 32) * 5 / 9));
			}

			$hdd_temperature = $temperature;
		}
	}

	return $hdd_temperature;
}
function read_xorg_module_version($module)
{
	$module_version = false;
	if(is_file("/var/log/Xorg.0.log"))
	{
		$xorg_log = @file_get_contents("/var/log/Xorg.0.log");

		if(($module_start = strpos($xorg_log, $module)) > 0)
		{
			$xorg_log = substr($xorg_log, $module_start);
			$temp_version = substr($xorg_log, strpos($xorg_log, "module version =") + 17);
			$temp_version = substr($temp_version, 0, strpos($temp_version, "\n"));

			if(is_numeric(str_replace(".", "", $temp_version)))
			{
				$module_version = $temp_version;
			}
		}
	}

	return $module_version;
}
function read_osx_system_profiler($data_type, $object, $multiple_objects = false)
{
	$info = trim(shell_exec("system_profiler " . $data_type . " 2>&1"));
	$lines = explode("\n", $info);

	$value = ($multiple_objects ? array() : false);

	for($i = 0; $i < count($lines) && ($value == false || $multiple_objects); $i++)
	{
		$line = explode(":", $lines[$i]);
		$line_object = str_replace(" ", "", $line[0]);
		
		if(($cut_point = strpos($line_object, "(")) > 0)
		{
			$line_object = substr($line_object, 0, $cut_point);
		}
		
		if($line_object == $object && isset($line[1]))
		{
			$this_value = trim($line[1]);
			
			if(!empty($this_value))
			{
				if($multiple_objects)
				{
					array_push($value, $this_value);
				}
				else
				{
					$value = $this_value;
				}
			}
		}
	}
	
	return $value;
}
function read_dmidecode($type, $sub_type, $object, $find_once = false, $ignore = null)
{
	// Read Linux dmidecode
	$value = array();

	if(is_readable("/dev/mem"))
	{
		$ignore = pts_to_array($ignore);

		$dmidecode = shell_exec("dmidecode --type " . $type . " 2>&1");
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
					$dmidecode_r = explode(":", $dmidecode_elements[$i]);

					if(trim($dmidecode_r[0]) == $object && isset($dmidecode_r[1]) && !in_array(trim($dmidecode_r[1]), $ignore))
					{
						array_push($value, trim($dmidecode_r[1]));
						$found_in_section = true;
					}
				}
			}
		}
		while($sub_type_start !== false && $find_once == false);
	}

	if(count($value) == 0)
	{
		$value = false;
	}
	else if($find_once != false && count($value) == 1)
	{
		$value = $value[0];
	}

	return $value;
}
function read_sun_ddu_dmi_info($object)
{
	// Read Sun's Device Driver Utility for OpenSolaris
	$values = array();

	if(is_executable("/usr/ddu/bin/dmi_info"))
	{
		$info = shell_exec("/usr/ddu/bin/dmi_info 2>&1");
		$lines = explode("\n", $info);

		$objects = explode(",", $object);
		$this_section = "";

		if(count($objects) == 2)
		{
			$section = $objects[0];
			$object = $objects[1];
		}
		else
		{
			$section = "";
			$object = $objects[0];
		}

		foreach($lines as $line)
		{
			$line = explode(":", $line);
			$line_object = str_replace(" ", "", $line[0]);
			$this_value = (count($line) > 1 ? trim($line[1]) : "");

			if(empty($this_value) && !empty($section))
			{
				$this_section = $line_object;
			}

			if($line_object == $object && ($this_section == $section || pts_proximity_match($section, $this_section)) && !empty($this_value) && $this_value != "Unknown")
			{
				array_push($values, $this_value);
			}
		}
	}

	return $values;
}

?>
