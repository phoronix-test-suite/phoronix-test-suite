<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
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

class phodevi_cpu extends pts_device_interface
{
	public static function read_sensor($identifier)
	{
		switch($identifier)
		{
			case "temperature":
				$sensor = "cpu_temperature";
				break;
			case "current-frequency":
				$sensor = "cpu_current_frequency";
				break;
			case "usage":
				$sensor = "cpu_usage";
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
				$property = new pts_device_property("cpu_string", PHODEVI_SMART_CACHE);
				break;
			case "model":
				$property = new pts_device_property("cpu_model", PHODEVI_SMART_CACHE);
				break;
			case "default-frequency":
				$property = new pts_device_property(array("cpu_default_frequency", 0), PHODEVI_SMART_CACHE);
				break;
			case "core-count":
				$property = new pts_device_property("cpu_core_count", PHODEVI_SMART_CACHE);
				break;
			case "power-savings-mode":
				$property = new pts_device_property("cpu_power_savings_mode", PHODEVI_SMART_CACHE);
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
			if(($strip_point = strpos($model, "@")) > 0)
			{
				$model = trim(substr($model, 0, $strip_point)); // stripping out the reported freq, since the CPU could be overclocked, etc
			}

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
				$info = pts_file_get_contents("/sys/devices/system/cpu/cpu" . $cpu_core . "/cpufreq/scaling_max_freq");
				$info = pts_trim_double(intval($info) / 1000000, 2);
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
			$info = phodevi::read_sensor("cpu", "current-frequency");

			if($info > 1000)
			{
				// Convert from MHz to GHz
				$info = $info / 1000;
			}
		}

		return pts_trim_double($info);
	}
	public static function cpu_power_savings_mode()
	{
		// Report string if CPU power savings feature is enabled
		$return_string = "";

		if(is_file("/sys/devices/system/cpu/cpu0/cpufreq/scaling_min_freq") && is_file("/sys/devices/system/cpu/cpu0/cpufreq/scaling_max_freq"))
		{
			// if EIST / CnQ is disabled, the cpufreq folder shoudln't be present, but double check by comparing the min and max frequencies
			$min = pts_file_get_contents("/sys/devices/system/cpu/cpu0/cpufreq/scaling_min_freq");
			$max = pts_file_get_contents("/sys/devices/system/cpu/cpu0/cpufreq/scaling_max_freq");

			if($min < $max)
			{
				$cpu = phodevi::read_property("cpu", "model");

				if(strpos($cpu, "AMD") !== false)
				{
					$return_string = "AMD Cool n Quiet was enabled";
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
		}

		if(!empty($info))
		{
			$info = pts_clean_information_string($info);
		}
		else
		{
			$info = "Unknown";
		}

		return $info;
	}
	public static function cpu_temperature()
	{
		// Read the processor temperature
		$temp_c = -1;

		if(IS_BSD)
		{

			$cpu_temp = phodevi_bsd_parser::read_sysctl(array("dev.cpu.0.temperature", "hw.sensors.cpu0.temp0"));

			if($cpu_temp != false)
			{
				if(($end = strpos($cpu_temp, 'C')) > 0 || ($end = strpos($cpu_temp, "degC")))
				{
					$cpu_temp = substr($cpu_temp, 0, $end);
				}

				if(is_numeric($cpu_temp))
				{
					$temp_c = $cpu_temp;
				}
			}
			else
			{
				$acpi = phodevi_bsd_parser::read_sysctl("hw.acpi.thermal.tz0.temperature");

				if(($end = strpos($acpi, 'C')) > 0)
				{
					$acpi = substr($acpi, 0, $end);
				}

				if(is_numeric($acpi))
				{
					$temp_c = $acpi;
				}
			}
		}
		else if(IS_LINUX)
		{
			$sensors = phodevi_linux_parser::read_sensors(array("CPU Temp", "Core 0", "Core0 Temp", "Core1 Temp"));

			if($sensors != false && is_numeric($sensors))
			{
				$temp_c = $sensors;
			}
			else
			{
				$search_count = 0;

				foreach(pts_glob("/sys/class/thermal/thermal_zone*/temp") as $temp)
				{
					$temp = pts_file_get_contents($temp);

					if(is_numeric($temp))
					{
						$search_count++;

						if($search_count < 2)
						{
							continue;
						}

						// Assuming the system thermal sensor comes 2nd to the ACPI CPU temperature
						// It appears that way on a ThinkPad T60, but TODO find a better way to validate

						$temp_c = pts_trim_double(($temp / 1000), 2);
						break;
					}
				}
			}
		}

		return $temp_c;
	}
	public static function cpu_current_frequency($cpu_core = 0)
	{
		// Determine the current processor frequency
		$info = 0;

		if(IS_LINUX)
		{
			// First, the ideal way, with modern CPUs using CnQ or EIST and cpuinfo reporting the current
			if(is_file("/sys/devices/system/cpu/cpu" . $cpu_core . "/cpufreq/scaling_cur_freq"))
			{
				$info = pts_file_get_contents("/sys/devices/system/cpu/cpu" . $cpu_core . "/cpufreq/scaling_cur_freq");
				$info = pts_trim_double(intval($info) / 1000, 2);
			}
			else if(is_file("/proc/cpuinfo")) // fall back for those without cpufreq
			{
				$cpu_speeds = phodevi_linux_parser::read_cpuinfo("cpu MHz");

				if(isset($cpu_speeds[0]))
				{
					$cpu_core = (isset($cpu_speeds[$cpu_core]) ? $cpu_core : 0);
					$info = pts_trim_double(intval($cpu_speeds[$cpu_core]), 2);
				}
			}
		}
		else if(IS_SOLARIS)
		{
			$info = shell_exec("psrinfo -v | grep MHz");
			$info = substr($info, strrpos($info, "at") + 3);
			$info = trim(substr($info, 0, strpos($info, "MHz")));
			$info = pts_trim_double($info, 2);
		}
		else if(IS_BSD)
		{
			$info = phodevi_bsd_parser::read_sysctl("dev.cpu.0.freq");
			$info = pts_trim_double($info, 2);
		}
		else if(IS_MACOSX)
		{
			$info = phodevi_osx_parser::read_osx_system_profiler("SPHardwareDataType", "ProcessorSpeed");
		
			if(($cut_point = strpos($info, " ")) > 0)
			{
				$info = substr($info, 0, $cut_point);
			}

			$info = pts_trim_double($info, 2);
		}

		return $info;
	}
	public static function cpu_load_array($read_core = -1)
	{
		// CPU load array
		$load = array();

		if(IS_LINUX && is_file("/proc/stat"))
		{
			$stat = file_get_contents("/proc/stat");

			if($read_core > -1 && ($l = strpos($stat, "cpu" . $read_core)) !== false)
			{
				$start_line = $l;
			}
			else
			{
				$start_line = 0;
			}

			$stat = substr($stat, $start_line, strpos($stat, "\n"));
			$stat_break = explode(" ", $stat);

			for($i = 1; $i < 6; $i++)
			{
				array_push($load, $stat_break[$i]);
			}
		}
		else if(IS_BSD)
		{
			$load = explode(" ", phodevi_bsd_parser::read_sysctl("kern.cp_time"));
		}
	

		return $load;
	}
	public static function cpu_usage($core = -1)
	{
		// Determine current percentage for processor usage
		if(IS_LINUX || IS_BSD)
		{
			$start_load = phodevi_cpu::cpu_load_array($core);
			sleep(1);
			$end_load = phodevi_cpu::cpu_load_array($core);
	
			for($i = 0; $i < count($end_load); $i++)
			{
				$end_load[$i] -= $start_load[$i];
			}

			$percent = (($sum = array_sum($end_load)) == 0 ? 0 : 100 - (($end_load[(count($end_load) - 1)] * 100) / $sum));
		}
		else if(IS_SOLARIS)
		{
			// TODO: Add support for monitoring load on a per-core basis (through mpstat maybe?)
			$info = explode(" ", pts_trim_spaces(array_pop(explode("\n", trim(shell_exec("sar -u 1 1 2>&1"))))));
			$percent = $info[1];
		}
		else if(IS_MACOSX)
		{
			// CPU usage for user
			$top = shell_exec("top -n 1 -l 1 2>&1");
			$top = substr($top, strpos($top, "CPU usage: ") + 11);
			$percent = substr($top, 0, strpos($top, "%"));
		}
		else
		{
			$percent = null;
		}

		if(!is_numeric($percent) || $percent < 0 || $percent > 100)
		{
			$percent = -1;
		}

		return pts_trim_double($percent);
	}
}

?>
