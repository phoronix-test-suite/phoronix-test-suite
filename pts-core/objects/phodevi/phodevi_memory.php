<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
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

class phodevi_memory extends pts_device_interface
{
	public static function read_sensor($identifier)
	{
		switch($identifier)
		{
			case "physical-usage":
				$sensor = array("memory_usage", "MEMORY");
				break;
			case "swap-usage":
				$sensor = array("memory_usage", "SWAP");
				break;
			case "total-usage":
				$sensor = array("memory_usage", "TOTAL");
				break;
			default:
				$sensor = false;
				break;
		}

		return $sensor;
	}
	public static function read_property($identifier)
	{
		switch($identifier)
		{
			case "identifier":
				$property = new pts_device_property("memory_string", PHODEVI_SMART_CACHE);
				break;
			case "capacity":
				$property = new pts_device_property("memory_capacity", PHODEVI_SMART_CACHE);
				break;
		}

		return $property;
	}
	public static function memory_string()
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
			$mem_speed = read_sun_ddu_dmi_info("MemoryDevice*,Speed");
			$mem_type = read_sun_ddu_dmi_info("MemoryDevice*,MemoryDeviceType");

			if(is_array($mem_speed) && count($mem_speed) > 0)
			{
				$mem_speed = array_pop($mem_speed);
			}

			$mem_speed = str_replace("MHZ", "MHz", $mem_speed);
		}
		else
		{
			$mem_size = read_dmidecode("memory", "Memory Device", "Size", false, array("Not Installed", "No Module Installed"));
			$mem_speed = read_dmidecode("memory", "Memory Device", "Speed", true, "Unknown");
			$mem_type = read_dmidecode("memory", "Memory Device", "Type", true, array("Unknown", "Other"));
		}

		if(is_array($mem_type))
		{
			$mem_type = array_pop($mem_type);
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

				if(pts_remove_chars($mem_type, true, false, true) == $mem_type)
				{
					$mem_prefix = $mem_type;
				}
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

				if(!empty($mem_prefix))
				{
					$mem_prefix .= "-";
				}

				$mem_prefix .= str_replace(" ", "", $mem_speed);
			}

			// TODO: Allow a combination of both functions below, so like 2 x 2GB + 3 x 1GB DDR2-800
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
			$mem_string = phodevi::read_property("memory", "capacity") . "MB";
		}

		return trim($mem_string);
	}
	public static function memory_capacity()
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
			$info = shell_exec("prtconf 2>&1 | grep Memory");
			$info = substr($info, strpos($info, ":") + 2);
			$info = substr($info, 0, strpos($info, "Megabytes"));
		}
		else if(IS_BSD)
		{
			$mem_size = read_sysctl("hw.physmem");

			if(empty($mem_size))
			{
				$mem_size = read_sysctl("hw.realmem");
			}

			$info = floor($mem_size / 1048576);
		}
		else if(IS_MACOSX)
		{
			$info = read_osx_system_profiler("SPHardwareDataType", "Memory");
			$info = explode(" ", $info);
			$info = (isset($info[1]) && $info[1] == "GB" ? $info[0] * 1024 : $info[0]);
		}
		else
		{
			$info = "Unknown";
		}

		return $info;
	}
	function memory_usage($TYPE = "TOTAL", $READ = "USED")
	{
		// Reads system memory usage
		$mem_usage = -1;

		if(pts_executable_in_path("free") != false)
		{
			$mem = explode("\n", shell_exec("free -t -m 2>&1"));
			$grab_line = null;

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
		}

		return $mem_usage;
	}
}

?>
