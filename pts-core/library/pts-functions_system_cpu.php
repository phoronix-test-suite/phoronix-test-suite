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

function hw_cpu_temperature()
{
	// Read the processor temperature
	$temp_c = -1;

	if(IS_BSD)
	{

		$cpu_temp = read_sysctl("dev.cpu.0.temperature");

		if($cpu_temp != false)
		{
			if(($end = strpos($cpu_temp, 'C')) > 0)
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
			$acpi = read_sysctl("hw.acpi.thermal.tz0.temperature");

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
		$sensors = read_sensors(array("CPU Temp", "Core 0", "Core0 Temp", "Core1 Temp"));

		if(!$sensors != false && is_numeric($sensors))
		{
			$temp_c = $sensors;
		}
		else
		{
			$acpi = read_acpi(array(
				"/thermal_zone/THM0/temperature", 
				"/thermal_zone/TZ00/temperature"), "temperature");

			if(($end = strpos($acpi, ' ')) > 0)
			{
				$temp_c = substr($acpi, 0, $end);
			}
		}
	}

	return $temp_c;
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
