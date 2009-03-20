<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
	pts-functions_system_cpu.php: System functions related to the processor(s).

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

function hw_cpu_core_count()
{
	// Returns number of cores present on the system
	static $core_count = 0;

	if($core_count == 0)
	{
		$info = 0;

		if(IS_LINUX)
		{
			$processors = count(read_cpuinfo("processor"));
		}
		else if(IS_SOLARIS)
		{
			$info = count(explode("\n", trim(shell_exec("psrinfo"))));
		}
		else if(IS_BSD)
		{
			$info = read_sysctl("hw.ncpu");
		}
		else if(IS_MACOSX)
		{
			$info = read_osx_system_profiler("SPHardwareDataType", "TotalNumberOfCores");	
		}

		if(is_int($info) && $info > 0)
		{
			$core_count = $info;
		}
		else
		{
			$core_count = 1;
		}
	}

	return $core_count;
}
function hw_cpu_job_count()
{
	// Number of CPU jobs to tell the tests to use
	return hw_cpu_core_count() * 2;
}
function hw_cpu_string($append_cpu_frequency = true)
{
	// Returns the processor name / frequency information
	$info = "";

	if(IS_LINUX)
	{
		$physical_cpu_ids = read_cpuinfo("physical id");
		$physical_cpu_count = count(array_unique($physical_cpu_ids));

		$cpu_strings = read_cpuinfo("model name");
		$cpu_strings_unique = array_unique($cpu_strings);

		if($physical_cpu_count == 1 || empty($physical_cpu_count))
		{
			// Just one processor
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
		$dmi_cpu = read_sun_ddu_dmi_info("ProcessorName");

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
				$info = array_pop(read_sun_ddu_dmi_info("ProcessorManufacturer"));
			}
		}

		//TODO: Add in proper support for reading multiple CPUs, similar to the code from above
		$physical_cpu_count = count(read_sun_ddu_dmi_info("ProcessorSocketType"));
		if($physical_cpu_count > 1 && !empty($info))
		{
			// TODO: For now assuming when multiple CPUs are installed, that they are of the same type
			$info = $physical_cpu_count . " x " . $info;
		}
	}
	else if(IS_BSD)
	{
		$info = read_sysctl("hw.model");
	}
	else if(IS_MACOSX)
	{
		$info = read_osx_system_profiler("SPHardwareDataType", "ProcessorName");
	}

	if(!empty($info))
	{
		$info = pts_clean_information_string($info);

		if($append_cpu_frequency)
		{
			$cpu_core_read = 0; // for now default to the first core frequency to read

			// Append the processor frequency to string
			if(($freq = hw_cpu_default_frequency($cpu_core_read)) > 0)
			{
				if(($strip_point = strpos($info, "@")) > 0)
				{
					$info = trim(substr($info, 0, $strip_point)); // stripping out the reported freq, since the CPU could be overclocked, etc
				}

				$info .= " @ " . $freq . "GHz";
			}
		}
	}
	else
	{
		$info = "Unknown";
	}

	return $info;
}
function hw_cpu_default_frequency($cpu_core = 0)
{
	// Find out the processor frequency
	// First, the ideal way, with modern CPUs using CnQ or EIST and cpuinfo reporting the current
	if(is_file("/sys/devices/system/cpu/cpu" . $cpu_core . "/cpufreq/scaling_max_freq"))
	{
		$info = trim(file_get_contents("/sys/devices/system/cpu/cpu" . $cpu_core . "/cpufreq/scaling_max_freq"));
		$info = pts_trim_double(intval($info) / 1000000, 2);
	}
	else if(is_file("/proc/cpuinfo")) // fall back for those without cpufreq
	{
		$cpu_speeds = read_cpuinfo("cpu MHz");
		$cpu_core = (isset($cpu_speeds[$cpu_core]) ? $cpu_core : 0);
		$info = pts_trim_double($cpu_speeds[$cpu_core] / 1000, 2);
	}
	else
	{
		$info = hw_cpu_current_frequency($cpu_core);
	}

	return $info;
}
function hw_cpu_temperature()
{
	// Read the processor temperature
	$temp_c = read_sensors(array("CPU Temp", "Core 0", "Core0 Temp", "Core1 Temp"));

	if(empty($temp_c))
	{
		$temp_c = read_acpi(array(
			"/thermal_zone/THM0/temperature", 
			"/thermal_zone/TZ00/temperature"), "temperature");

		if(($end = strpos($temp_c, ' ')) > 0)
		{
			$temp_c = substr($temp_c, 0, $end);
		}
	}

	if(empty($temp_c))
	{
		$temp_c = -1;
	}

	return $temp_c;
}
function hw_cpu_power_savings_enabled()
{
	// Report string if CPU power savings feature is enabled
	$return_string = "";

	if(is_file("/sys/devices/system/cpu/cpu0/cpufreq/scaling_cur_freq") && is_file("/sys/devices/system/cpu/cpu0/cpufreq/scaling_max_freq"))
	{
		// if EIST / CnQ is disabled, the cpufreq folder shoudln't be present, but double check by comparing the min and max frequencies
		$min = trim(file_get_contents("/sys/devices/system/cpu/cpu0/cpufreq/scaling_min_freq"));
		$max = trim(file_get_contents("/sys/devices/system/cpu/cpu0/cpufreq/scaling_max_freq"));

		if($min < $max)
		{
			$cpu = hw_cpu_string();

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
function hw_cpu_current_frequency($cpu_core = 0)
{
	// Determine the current processor frequency
	// First, the ideal way, with modern CPUs using CnQ or EIST and cpuinfo reporting the current
	if(is_file("/sys/devices/system/cpu/cpu" . $cpu_core . "/cpufreq/scaling_cur_freq"))
	{
		$info = trim(file_get_contents("/sys/devices/system/cpu/cpu" . $cpu_core . "/cpufreq/scaling_cur_freq"));
		$info = pts_trim_double(intval($info) / 1000, 2);
	}
	else if(is_file("/proc/cpuinfo")) // fall back for those without cpufreq
	{
		$cpu_speeds = read_cpuinfo("cpu MHz");
		$cpu_core = (isset($cpu_speeds[$cpu_core]) ? $cpu_core : 0);
		$info = pts_trim_double(intval($cpu_speeds[$cpu_core]), 2);
	}
	else if(IS_SOLARIS)
	{
		$info = shell_exec("psrinfo -v | grep MHz");
		$info = substr($info, strrpos($info, "at") + 3);
		$info = substr($info, 0, strpos($info, "MHz"));
		$info = pts_trim_double(intval($info) / 1000, 2);
	}
	else if(IS_BSD)
	{
		$info = read_sysctl("dev.cpu.0.freq");
		$info = pts_trim_double(intval($info) / 1000, 2);
	}
	else if(IS_MACOSX)
	{
		$info = read_osx_system_profiler("SPHardwareDataType", "ProcessorSpeed");
		
		if(($cut_point = strpos($info, " ")) > 0)
		{
			$info = substr($info, 0, $cut_point);
		}

		$info = pts_trim_double($info, 2);
	}
	else
	{
		$info = 0;
	}

	return $info;
}
function hw_cpu_load_array($read_core = -1)
{
	// CPU load array
	$stat = @file_get_contents("/proc/stat");

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

	$load = array();
	for($i = 1; $i < 6; $i++)
	{
		array_push($load, $stat_break[$i]);
	}

	return $load;
}
function hw_cpu_usage($core = -1)
{
	// Determine current percentage for processor usage
	if(IS_LINUX)
	{
		$start_load = hw_cpu_load_array($core);
		sleep(1);
		$end_load = hw_cpu_load_array($core);
	
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

?>
