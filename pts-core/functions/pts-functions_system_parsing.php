<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
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
	$value = "";

	if(is_file("/proc/acpi" . $point))
	{
		$acpi_lines = explode("\n", file_get_contents("/proc/acpi" . $point));

		for($i = 0; $i < count($acpi_lines) && $value == ""; $i++)
		{
			$line = explode(": ", $acpi_lines[$i]);
			$this_attribute = trim($line[0]);

			if(count($line) > 1)
				$this_value = trim($line[1]);
			else
				$this_value = "";

			if($this_attribute == $match)
				$value = $this_value;
		}
	}

	return $value;
}
function read_hal($name, $UDI = NULL)
{
	// Read HAL - Hardware Abstraction Layer
	if(empty($UDI))
		$info = shell_exec("lshal 2>&1 | grep \"" . $name . "\"");
	else
		$info = shell_exec("lshal -u $UDI 2>&1 | grep \"" . $name . "\"");

	if(($pos = strpos($info, $name . " = '")) === FALSE)
	{
		$info = "Unknown";
	}
	else
	{
		$info = substr($info, $pos + strlen($name . " = '"));
		$info = trim(substr($info, 0, strpos($info, "'")));
	}

	$remove_words = array("empty", "unknow", "system manufacturer", "system version", "to be filled by o.e.m.", "not applicable");
	if(empty($info) || in_array(strtolower($info), $remove_words))
		$info = "Unknown";

	return $info;
}
function read_system_hal($name)
{
	// Read system HAL
	$hal = read_hal($name, "/org/freedesktop/Hal/devices/computer");

	if($hal == "Unknown")
		$hal = read_hal($name);

	return $hal;
}
function read_sensors($attributes)
{
	// Read LM_Sensors
	$value = "";
	$sensors = shell_exec("sensors 2>&1");
	$sensors_lines = explode("\n", $sensors);

	if(!is_array($attributes))
		$attributes = array($attributes);

	for($j = 0; $j < count($attributes) && empty($value); $j++)
	{
		$attribute = $attributes[$j];
		for($i = 0; $i < count($sensors_lines) && empty($value); $i++)
		{
			$line = explode(": ", $sensors_lines[$i]);
			$this_attribute = trim($line[0]);

			if($this_attribute == $attribute)
			{
				$this_remainder = trim(str_replace(array('+', '°'), ' ', $line[1]));
				$this_value = substr($this_remainder, 0, strpos($this_remainder, ' '));

				if(is_numeric($this_value))
					$value = $this_value;
			}
		}
	}

	return $value;
}
function read_pci($desc, $clean_string = true)
{
	// Read PCI bus information
	$info = shell_exec("lspci 2>&1");

	if(($pos = strpos($info, $desc)) === FALSE)
	{
		$info = "Unknown";
	}
	else
	{
		$info = substr($info, $pos + strlen($desc));
		$EOL = strpos($info, "\n");

		if(($temp = strpos($info, '/')) < $EOL && $temp > 0)
			if(($temp = strpos($info, ' ', ($temp + 2))) < $EOL && $temp > 0)
				$EOL = $temp;

		if(($temp = strpos($info, '(')) < $EOL && $temp > 0)
			$EOL = $temp;

		if(($temp = strpos($info, '[')) < $EOL && $temp > 0)
			$EOL = $temp;

		$info = trim(substr($info, 0, $EOL));

		if(($strlen = strlen($info)) < 6 || $strlen > 96)
			$info = "N/A";
		else if($clean_string)
			$info = pts_clean_information_string($info);
	}

	return $info;
}
function read_lsb($desc)
{
	// Read LSB Release information, Linux Standards Base
	$info = shell_exec("lsb_release -a 2>&1");

	if(($pos = strrpos($info, $desc . ':')) === FALSE)
	{
		$info = "Unknown";
	}
	else
	{
		$info = substr($info, $pos + strlen($desc) + 1);
		$info = trim(substr($info, 0, strpos($info, "\n")));
	}

	return $info;
}
function read_sysctl($desc)
{
	// Read sysctl, used by *BSDs
	$info = shell_exec("sysctl $desc 2>&1");

	if(strpos($info, $desc . ":") !== FALSE)
	{
		$info = trim(substr($info, strlen($desc) + 2));
	}
	else
	{
		$info = "Unknown";
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

			if(count($line) > 1)
				$this_value = trim($line[1]);
			else
				$this_value = "";

			if($this_attribute == $attribute)
				array_push($cpuinfo_matches, $this_value);
		}
	}

	return $cpuinfo_matches;
}
function read_nvidia_extension($attribute)
{
	// Read NVIDIA's NV Extension
	$info = shell_exec("nvidia-settings --query " . $attribute . " 2>&1");
	$nv_info = NULL;

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
		if(!empty($xdpyinfo_line) && strpos($xdpyinfo_line, "0x0") == FALSE)
			array_push($monitor_info, $xdpyinfo_line);

	return $monitor_info;
}
function read_amd_graphics_adapters()
{
	// Read ATI/AMD graphics hardware using aticonfig
	$info = trim(shell_exec("aticonfig --list-adapters 2>&1"));
	$adapters = array();

	foreach(explode("\n", $info) as $line)
		if(($last_point = strrpos($line, ".")) > 0)
			array_push($adapters, substr($line, $last_point + 3));

	return $adapters;
}
function read_amd_pcsdb($attribute)
{
	// Read AMD's AMDPCSDB, AMD Persistent Configuration Store Database
	$info = shell_exec("aticonfig --get-pcs-key=" . $attribute . " 2>&1");
	$ati_info = "";

	if(($pos = strpos($info, ":")) > 0 && strpos($info, "Error") === FALSE)
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
function amd_pcsdb_parser($attribute, $find_once = false)
{
	return read_amd_pcsdb_direct_parser($attribute, $find_once);
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
			$amdpcsdb_file = explode("\n", file_get_contents("/etc/ati/amdpcsdb"));

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
					$is_in_prefix = false;
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
		$attribute_values = "";
	else if(count($attribute_values) == 1)
		$attribute_values = $attribute_values[0];

	return $attribute_values;
}
function read_ati_extension($attribute)
{
	// Read ATI fake extension
	$ati_info = "";

	//mangler to get correct info out of aticonfig
	if("CoreTemperature" == $attribute)
	{
			$info = shell_exec("aticonfig --pplib-cmd \"get temperature 0\" 2>&1");
			if(($pos = strpos($info, "thermal")) > 0)
			{
				$ati_info = substr($info, strpos($info, "is") + 3);
				$ati_info = substr($ati_info, 0, strpos($ati_info, "\n"));
				$ati_info = trim(substr($ati_info, 0, strrpos($ati_info, ".")));
			}
	}
	else if("Stock3DFrequencies" == $attribute)
	{
			$info = shell_exec("aticonfig --pplib-cmd \"get clock\" 2>&1");
			if(($pos = strpos($info, "ngine")) > 0)
			{
				$core_info = substr($info, strpos($info, "-") + 1);
				$core_info = substr($core_info, 0, strpos($core_info, " "));
				$mem_info = substr($info, strpos($info, "Memory Clock"));
				$mem_info = substr($mem_info, strpos($mem_info, "-") + 1);
				$mem_info = trim(substr($mem_info, 0, strpos($mem_info, " ")));
				$ati_info = $core_info . "," . $mem_info;
			}
	}
	else if("Current3DFrequencies" == $attribute)
	{
			$info = shell_exec("aticonfig --pplib-cmd \"get activity\" 2>&1");
			if(($pos = strpos($info, "Activity")) > 0)
			{
				$core_info = substr($info, strpos($info, "Core Clock:") + 12);
				$core_info = substr($core_info, 0, strpos($core_info, "\n"));
				$core_info = trim(substr($core_info, 0, strrpos($core_info, "MHZ")));
				$mem_info = substr($info, strpos($info, "Memory Clock:") + 14);
				$mem_info = substr($mem_info, 0, strpos($mem_info, "\n"));
				$mem_info = trim(substr($mem_info, 0, strrpos($mem_info, "MHZ")));
				$ati_info = $core_info . "," . $mem_info;
			}
	}
	else if("GPUActivity" == $attribute)
	{
		$info = shell_exec("aticonfig --pplib-cmd \"get activity\" 2>&1");
		if(($pos = strpos($info, "Activity")) > 0)
		{
			$activity_info = substr($info, strpos($info, "Activity:") + 9);
			$activity_info = trim(substr($activity_info, 0, strpos($activity_info, "percent\n")));
			$ati_info = $activity_info;
		}
	}

	return $ati_info;
}
function read_ati_overdrive($attribute, $adapter = 0)
{
	// Read ATI OverDrive information using aticonfig
	// OverDrive supported in fglrx 8.52+ drivers
	$value = -1;

	if($attribute == "Temperature")
	{
		$info = shell_exec("aticonfig --adapter=" . $adapter . " --od-gettemperature 2>&1");

		if(($start = strpos($info, "Temperature -")) !== FALSE)
		{
			$info = substr($info, $start + 14);
			$value = substr($info, 0, strpos($info, " C"));
		}
	}
	else
	{
		$info = shell_exec("aticonfig --adapter=" . $adapter . " --od-getclocks 2>&1");

		if(strpos($info, "GPU") !== FALSE)
		{
			foreach(explode("\n", $info) as $line)
			{
				$line_r = explode(":", $line);

				if(count($line_r) == 2)
				{
					$od_option = str_replace(" ", "", trim($line_r[0]));

					if($od_option == $attribute)
					{
						$od_value = trim($line_r[1]);
						$od_value = preg_replace("/\s+/", " ", $od_value);
						$od_value = str_replace(array("%"), "", $od_value);

						$od_value_r = explode(" ", $od_value);

						if(count($od_value_r) == 1)
							$value = $od_value_r[0];
						else
							$value = $od_value_r;												
					}
				}
			}
		}
	}

	return $value;
}

?>
