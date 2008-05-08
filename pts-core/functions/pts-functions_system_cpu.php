<?php

function read_cpuinfo_values($attribute)
{
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
function cpu_core_count()
{
	$processors = read_cpuinfo_values("processor");
	$info = count($processors); // or could do array_pop($processors) + 1

	return $info;
}
function cpu_job_count()
{
	return cpu_core_count() + 1;
}
function processor_string()
{
	$info = "";

	if(is_file("/proc/cpuinfo"))
	{
		$physical_cpu_ids = read_cpuinfo_values("physical id");
		$physical_cpu_count = array_pop($physical_cpu_ids) + 1;

		$cpu_strings = read_cpuinfo_values("model name");
		$cpu_strings_unique = array_unique($cpu_strings);

		if($physical_cpu_count == 1)
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
		$info = "Unknown";

	return $info;
}
function append_processor_frequency($cpu_string, $cpu_core = 0)
{
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

	if(is_file("/sys/devices/system/cpu/cpu" . $cpu_core . "/cpufreq/scaling_max_freq")) // The ideal way, with modern CPUs using CnQ or EIST and cpuinfo reporting the current
	{
		$info = trim(file_get_contents("/sys/devices/system/cpu/cpu" . $cpu_core . "/cpufreq/scaling_max_freq"));
		$info = pts_trim_double(intval($info) / 1000000, 2);
	}
	else if(is_file("/proc/cpuinfo")) // fall back for those without cpufreq
	{
		$cpu_speeds = read_cpuinfo_values("cpu MHz");

		if(count($cpu_speeds) > $cpu_core)
			$info = $cpu_speeds[$cpu_core];
		else
			$info = $cpu_speeds[0];

		$info = pts_trim_double(intval($info) / 1000, 2);
	}
	else
		$info = 0;

	return $info;
}
function processor_temperature()
{
	$temp_c = read_linux_sensors("CPU Temp");

	if(empty($temp_c))
	{
		$temp_c = read_acpi_value("/thermal_zone/THM0/temperature", "temperature"); // if it is THM0 that is for the CPU, in most cases it should be

		if(($end = strpos($temp_c, ' ')) > 0)
			$temp_c = substr($temp_c, 0, $end);
	}

	if(empty($temp_c))
		$temp_c = -1;

	return $temp_c;
}
function pts_record_cpu_temperature()
{
	global $CPU_TEMPERATURE;
	$temp = processor_temperature();

	if($temp != -1)
		array_push($CPU_TEMPERATURE, $temp);
}
function pts_processor_power_savings_enabled()
{
	$return_string = "";

	if(is_file("/sys/devices/system/cpu/cpu0/cpufreq/scaling_cur_freq") && is_file("/sys/devices/system/cpu/cpu0/cpufreq/scaling_max_freq"))
	{
		sleep(1); // try to get it to drop power levels
		$cur = trim(file_get_contents("/sys/devices/system/cpu/cpu0/cpufreq/scaling_cur_freq"));
		$max = trim(file_get_contents("/sys/devices/system/cpu/cpu0/cpufreq/scaling_max_freq"));

		if($cur < $max) // TODO: improved test, since the CPU could already be maxed from other processes running
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
?>
