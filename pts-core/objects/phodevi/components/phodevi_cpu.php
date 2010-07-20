<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel
	phodevi_cpu.php: The PTS Device Interface object for the CPU / processor

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

class phodevi_cpu extends phodevi_device_interface
{
	public static function read_property($identifier)
	{
		switch($identifier)
		{
			case "identifier":
				$property = new phodevi_device_property("cpu_string", PHODEVI_SMART_CACHE);
				break;
			case "model":
				$property = new phodevi_device_property("cpu_model", PHODEVI_SMART_CACHE);
				break;
			case "default-frequency":
				$property = new phodevi_device_property(array("cpu_default_frequency", 0), PHODEVI_SMART_CACHE);
				break;
			case "core-count":
				$property = new phodevi_device_property("cpu_core_count", PHODEVI_SMART_CACHE);
				break;
			case "power-savings-mode":
				$property = new phodevi_device_property("cpu_power_savings_mode", PHODEVI_SMART_CACHE);
				break;
		}

		return $property;
	}
	public static function cpu_string()
	{
		$model = phodevi::read_property("cpu", "model");

		// Append the processor frequency to string
		if(($freq = phodevi::read_property("cpu", "default-frequency")) > 0)
		{
			$model .= " @ " . $freq . "GHz";
		}

		return $model . " (Total Cores: " . phodevi::read_property("cpu", "core-count") . ")";
	}
	public static function cpu_core_count()
	{
		if(IS_LINUX)
		{
			$info = count(phodevi_linux_parser::read_cpuinfo("processor"));
		}
		else if(IS_SOLARIS)
		{
			$info = count(explode("\n", trim(shell_exec("psrinfo"))));
		}
		else if(IS_BSD)
		{
			$info = intval(phodevi_bsd_parser::read_sysctl("hw.ncpu"));
		}
		else if(IS_MACOSX)
		{
			$info = phodevi_osx_parser::read_osx_system_profiler("SPHardwareDataType", "TotalNumberOfCores");	
		}
		else if(IS_WINDOWS)
		{
			$info = getenv("NUMBER_OF_PROCESSORS");
		}
		else
		{
			$info = null;
		}

		return (is_numeric($info) && $info > 0 ? $info : 1);
	}
	public static function cpu_default_frequency($cpu_core = 0)
	{
		// Find out the processor frequency

		if(IS_LINUX)
		{
			// First, the ideal way, with modern CPUs using CnQ or EIST and cpuinfo reporting the current
			if(is_file("/sys/devices/system/cpu/cpu" . $cpu_core . "/cpufreq/scaling_max_freq"))
			{
				$info = pts_file_io::file_get_contents("/sys/devices/system/cpu/cpu" . $cpu_core . "/cpufreq/scaling_max_freq");
				$info = intval($info) / 1000000;
			}
			else if(is_file("/proc/cpuinfo")) // fall back for those without cpufreq
			{
				$cpu_speeds = phodevi_linux_parser::read_cpuinfo("cpu MHz");
				$cpu_core = (isset($cpu_speeds[$cpu_core]) ? $cpu_core : 0);
				$info = $cpu_speeds[$cpu_core] / 1000;
			}
		}
		else if(IS_WINDOWS)
		{
			$info = phodevi_windows_parser::read_cpuz("Processor 1", "Stock frequency");
			if($info != null)
			{
				if(($e = strpos($info, " MHz")) !== false)
				{
					$info = substr($info, 0, $e);
				}

				$info = $info / 1000;
			}
		}
		else
		{
			$info = phodevi::read_sensor(array("cpu", "freq"));

			if($info > 1000)
			{
				// Convert from MHz to GHz
				$info = $info / 1000;
			}
		}

		return pts_math::set_precision($info, 2);
	}
	public static function cpu_power_savings_mode()
	{
		// Report string if CPU power savings feature is enabled
		$return_string = "";

		if(IS_LINUX && is_file("/sys/devices/system/cpu/cpu0/cpufreq/scaling_min_freq") && is_file("/sys/devices/system/cpu/cpu0/cpufreq/scaling_max_freq"))
		{
			// if EIST / CnQ is disabled, the cpufreq folder shoudln't be present, but double check by comparing the min and max frequencies
			$min = pts_file_io::file_get_contents("/sys/devices/system/cpu/cpu0/cpufreq/scaling_min_freq");
			$max = pts_file_io::file_get_contents("/sys/devices/system/cpu/cpu0/cpufreq/scaling_max_freq");

			if($min < $max)
			{
				$cpu = phodevi::read_property("cpu", "model");

				if(strpos($cpu, "AMD") !== false)
				{
					$return_string = "AMD CnQ was enabled";
				}
				else if(strpos($cpu, "Intel") !== false)
				{
					$return_string = "Intel SpeedStep Technology was enabled";
				}
				else
				{
					$return_string = "The CPU was in a power-savings mode";
				}
			}
		}

		return $return_string;
	}
	public static function cpu_model()
	{
		// Returns the processor name / frequency information
		$info = "";

		if(IS_LINUX)
		{
			$physical_cpu_ids = phodevi_linux_parser::read_cpuinfo("physical id");
			$physical_cpu_count = count(array_unique($physical_cpu_ids));

			$cpu_strings = phodevi_linux_parser::read_cpuinfo(array("model name", "Processor"));
			$cpu_strings_unique = array_unique($cpu_strings);

			if($physical_cpu_count == 1 || empty($physical_cpu_count))
			{
				// Just one processor
				if(($cut = strpos($cpu_strings[0], " (")) !== false)
				{
					$cpu_strings[0] = substr($cpu_strings[0], 0, $cut);
				}

				$info = $cpu_strings[0];
			}
			else if($physical_cpu_count > 1 && count($cpu_strings_unique) == 1)
			{
				// Multiple processors, same model
				$info = $physical_cpu_count . " x " . $cpu_strings[0];
			}
			else if($physical_cpu_count > 1 && count($cpu_strings_unique) > 1)
			{
				// Multiple processors, different models
				$current_id = -1;
				$current_string = $cpu_strings[0];
				$current_count = 0;
				$cpus = array();

				for($i = 0; $i < count($physical_cpu_ids); $i++)
				{
					if($current_string != $cpu_strings[$i] || $i == (count($physical_cpu_ids) - 1))
					{
						array_push($cpus, $current_count . " x " . $current_string);

						$current_string = $cpu_strings[$i];
						$current_count = 0;
					}

					if($physical_cpu_ids[$i] != $current_id)
					{
						$current_count++;
						$current_id = $physical_cpu_ids[$i];
					}
				}
				$info = implode(", ", $cpus);
			}
		}
		else if(IS_SOLARIS)
		{
			$dmi_cpu = phodevi_solaris_parser::read_sun_ddu_dmi_info("CPUType", "-C");

			if(count($dmi_cpu) == 0)
			{
				$dmi_cpu = phodevi_solaris_parser::read_sun_ddu_dmi_info("ProcessorName");
			}

			if(count($dmi_cpu) > 0)
			{
				$info = $dmi_cpu[0];
			}
			else
			{
				$info = trim(shell_exec("dmesg 2>&1 | grep cpu0"));
				$info = trim(substr($info, strrpos($info, "cpu0:") + 6));

				if(empty($info))
				{
					$info = array_pop(phodevi_solaris_parser::read_sun_ddu_dmi_info("ProcessorManufacturer"));
				}
			}

			//TODO: Add in proper support for reading multiple CPUs, similar to the code from above
			$physical_cpu_count = count(phodevi_solaris_parser::read_sun_ddu_dmi_info("ProcessorSocketType"));
			if($physical_cpu_count > 1 && !empty($info))
			{
				// TODO: For now assuming when multiple CPUs are installed, that they are of the same type
				$info = $physical_cpu_count . " x " . $info;
			}
		}
		else if(IS_BSD)
		{
			$info = phodevi_bsd_parser::read_sysctl("hw.model");
		}
		else if(IS_MACOSX)
		{
			$info = phodevi_osx_parser::read_osx_system_profiler("SPHardwareDataType", "ProcessorName");
		}
		else if(IS_WINDOWS)
		{
			$info = phodevi_windows_parser::read_cpuz("Processor 1", "Name");

			if(!$info)
			{
				$info = getenv("PROCESSOR_IDENTIFIER");
			}
		}

		if(!empty($info))
		{
			$info = phodevi::clean_info_string($info);
		}
		else
		{
			$info = "Unknown";
		}

		if(($strip_point = strpos($info, '@')) > 0)
		{
			$info = trim(substr($info, 0, $strip_point)); // stripping out the reported freq, since the CPU could be overclocked, etc
		}

		return $info;
	}
}

?>
