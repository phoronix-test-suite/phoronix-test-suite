<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
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

function cpu_core_count()
{
	// Returns number of cores present on the system
	if(IS_LINUX)
	{
		$processors = read_cpuinfo("processor");
		$info = count($processors);
	}
	else if(IS_SOLARIS)
	{
		$info = trim(shell_exec("psrinfo"));
		$info = explode("\n", $info);
		$info = count($info);
	}
	else if(IS_BSD)
	{
		$info = read_sysctl("hw.ncpu");
	}
	else
	{
		$processors = 1;
	}

	return $info;
}
function cpu_job_count()
{
	// Number of CPU jobs to tell the tests to use
	return cpu_core_count() * 2;
}
function processor_string()
{
	// Returns the processor name / frequency information
	$info = "";

	if(is_file("/proc/cpuinfo"))
	{
		$physical_cpu_ids = read_cpuinfo("physical id");
		$physical_cpu_count = count(array_unique($physical_cpu_ids));

		$cpu_strings = read_cpuinfo("model name");
		$cpu_strings_unique = array_unique($cpu_strings);

		if($physical_cpu_count == 1 || empty($physical_cpu_count))
		{
			// Just one processor
			$info = append_processor_frequency(pts_clean_information_string($cpu_strings[0]));
		}
		else if($physical_cpu_count > 1 && count($cpu_strings_unique) == 1)
		{
			// Multiple processors, same model
			$info = $physical_cpu_count . " x " . append_processor_frequency(pts_clean_information_string($cpu_strings[0]));
		}
		else if($physical_cpu_count > 1 && count($cpu_strings_unique) > 1)
		{
			// Multiple processors, different models
			$current_id = -1;
			$current_string = $cpu_strings[0];
			$current_count = 0;

			for($i = 0; $i < count($physical_cpu_ids); $i++)
			{
				if($current_string != $cpu_strings[$i] || $i == (count($physical_cpu_ids) - 1))
				{
					$info .= $current_count . " x " . append_processor_frequency(pts_clean_information_string($current_string), $i);

					$current_string = $cpu_strings[$i];
					$current_count = 0;
				}

				if($physical_cpu_ids[$i] != $current_id)
				{
					$current_count++;
					$current_id = $physical_cpu_ids[$i];
				}
			}
		}
	}

	if(empty($info))
	{
		if(IS_SOLARIS)
		{
			$info = trim(shell_exec("dmesg 2>&1 | grep cpu0"));
			$info = substr($info, strrpos($info, "cpu0:") + 6);
			$info = append_processor_frequency(pts_clean_information_string($info), 0);
		}
		else if(IS_BSD)
		{
			$info = read_sysctl("hw.model");
			$info = append_processor_frequency(pts_clean_information_string($info), 0);
		}
		else
			$info = "Unknown";
	}

	return $info;
}
function append_processor_frequency($cpu_string, $cpu_core = 0)
{
	// Append the processor frequency to a string
	if(($freq = processor_frequency($cpu_core)) > 0)
	{
		if(($strip_point = strpos($cpu_string, '@')) > 0)
			$cpu_string = trim(substr($cpu_string, 0, $strip_point)); // stripping out the reported freq, since the CPU could be overclocked, etc

		$cpu_string .= " @ " . $freq . "GHz";
	}

	return $cpu_string;
}
function processor_frequency($cpu_core = 0)
{
	// Find out the processor frequency
	if(is_file("/sys/devices/system/cpu/cpu" . $cpu_core . "/cpufreq/scaling_max_freq")) // The ideal way, with modern CPUs using CnQ or EIST and cpuinfo reporting the current
	{
		$info = trim(file_get_contents("/sys/devices/system/cpu/cpu" . $cpu_core . "/cpufreq/scaling_max_freq"));
		$info = pts_trim_double(intval($info) / 1000000, 2);
	}
	else if(is_file("/proc/cpuinfo")) // fall back for those without cpufreq
	{
		$cpu_speeds = read_cpuinfo("cpu MHz");

		if(count($cpu_speeds) > $cpu_core)
			$info = $cpu_speeds[$cpu_core];
		else
			$info = $cpu_speeds[0];

		$info = pts_trim_double(intval($info) / 1000, 2);
	}
	else
		$info = current_processor_frequency($cpu_core);

	return $info;
}
function processor_temperature()
{
	// Read the processor temperature
	$temp_c = read_sensors(array("CPU Temp", "Core 0"));

	if(empty($temp_c))
	{
		$temp_c = read_acpi("/thermal_zone/THM0/temperature", "temperature"); // if it is THM0 that is for the CPU, in most cases it should be

		if(($end = strpos($temp_c, ' ')) > 0)
			$temp_c = substr($temp_c, 0, $end);
	}

	if(empty($temp_c))
		$temp_c = -1;

	return $temp_c;
}
function pts_processor_power_savings_enabled()
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
			$cpu = processor_string();

			if(strpos($cpu, "AMD") !== FALSE)
				$return_string = "AMD Cool n Quiet was enabled.";
			else if(strpos($cpu, "Intel") !== FALSE)
				$return_string = "Intel SpeedStep Technology was enabled.";
			else
				$return_string = "The CPU was in a power-savings mode.";
		}
	}
	return $return_string;
}
function current_processor_frequency($cpu_core = 0)
{
	// Determine the current processor frequency
	if(is_file("/sys/devices/system/cpu/cpu" . $cpu_core . "/cpufreq/scaling_cur_freq")) // The ideal way, with modern CPUs using CnQ or EIST and cpuinfo reporting the current
	{
		$info = trim(file_get_contents("/sys/devices/system/cpu/cpu" . $cpu_core . "/cpufreq/scaling_cur_freq"));
		$info = pts_trim_double(intval($info) / 1000, 2);
	}
	else if(is_file("/proc/cpuinfo")) // fall back for those without cpufreq
	{
		$cpu_speeds = read_cpuinfo("cpu MHz");

		if(count($cpu_speeds) > $cpu_core)
			$info = $cpu_speeds[$cpu_core];
		else
			$info = $cpu_speeds[0];

		$info = pts_trim_double(intval($info), 2);
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
	else
		$info = 0;

	return $info;
}
function cpu_load_array()
{
	// CPU load array
	$stat = @file_get_contents("/proc/stat");
	$stat = substr($stat, 0, strpos($stat, "\n"));
	$stat_break = explode(" ", $stat);

	$load = array();
	for($i = 1; $i < 6; $i++)
		array_push($load, $stat_break[$i]);

	return $load;
}
function current_processor_usage()
{
	// Determine current percentage for processor usage
	$start_load = cpu_load_array();
	sleep(1);
	$end_load = cpu_load_array();
	
	for($i = 0; $i < count($end_load); $i++)
	{
		$end_load[$i] -= $start_load[$i];
	}

	if(array_sum($end_load) == 0)
		$percent = 0;
	else
		$percent = 100 - (($end_load[(count($end_load) - 1)] * 100) / array_sum($end_load));

	if(!is_numeric($percent) || $percent < 0 || $percent > 100)
		$percent = -1;

	return pts_trim_double($percent);
}

?>
