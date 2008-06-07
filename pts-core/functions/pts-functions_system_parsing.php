<?php

/*
	Phoronix Test Suite "Trondheim"
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts-functions_system_parsing.php: System functions that perform the actual system hardware/software parsing on Linux.
*/

function read_acpi($point, $match)
{
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
	if(empty($UDI))
		$info = shell_exec("lshal | grep \"$name\"");
	else
		$info = shell_exec("lshal -u $UDI | grep \"$name\"");

	if(($pos = strpos($info, $name . " = '")) === FALSE)
	{
		$info = "Unknown";
	}
	else
	{
		$info = substr($info, $pos + strlen($name . " = '"));
		$info = trim(substr($info, 0, strpos($info, "'")));
	}

	if(empty($info) || $info == "empty" || $info == "Unknow" || $info == "System Manufacturer" || $info == "System Version")
		$info = "Unknown";

	return $info;
}
function read_system_hal($name)
{
	return read_hal($name, "/org/freedesktop/Hal/devices/computer");
}
function read_sensors($attribute)
{
	$value = "";
	$sensors = shell_exec("sensors 2>&1");
	$sensors_lines = explode("\n", $sensors);

	for($i = 0; $i < count($sensors_lines) && $value == ""; $i++)
	{
		$line = explode(": ", $sensors_lines[$i]);
		$this_attribute = trim($line[0]);

		if($this_attribute == $attribute)
		{
			$this_remainder = trim(str_replace(array('+', '°'), ' ', $line[1]));
			$value = substr($this_remainder, 0, strpos($this_remainder, ' '));
		}
	}

	return $value;
}
function read_pci($desc)
{
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
		else
			$info = pts_clean_information_string($info);
	}

	return $info;
}
function read_lsb($desc)
{
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
function read_cpuinfo($attribute)
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
function read_nvidia_extension($attribute)
{
	$info = shell_exec("nvidia-settings --query $attribute 2>&1");
	$nv_info = "";

	if(($pos = strpos($info, $attribute)) > 0)
	{
		$nv_info = substr($info, strpos($info, "):") + 3);
		$nv_info = substr($nv_info, 0, strpos($nv_info, "\n"));
		$nv_info = trim(substr($nv_info, 0, strrpos($nv_info, ".")));
	}

	return $nv_info;
}
function read_ati_extension($attribute)
{
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
			if(($pos = strpos($info, "Engine")) > 0)
			{
				$core_info = substr($info, strpos($info, "-") + 2);
				$core_info = substr($core_info, 0, strpos($core_info, " "));
				$mem_info = substr($info, strpos($info, "Memory Clock"));
				$mem_info = substr($mem_info, strpos($mem_info, "-") + 2);
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
	return $ati_info;
}
?>
