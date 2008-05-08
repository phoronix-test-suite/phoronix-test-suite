<?php

function read_acpi_value($point, $match)
{
	$value = "";

	if(is_file("/proc/acpi" . $point))
	{
		$cpuinfo_lines = explode("\n", file_get_contents("/proc/acpi" . $point));

		for($i = 0; $i < count($cpuinfo_lines) && $value == ""; $i++)
		{
			$line = explode(": ", $cpuinfo_lines[$i]);
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
function lshal_extract($name, $UDI = NULL)
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

	if($info == "empty")
		$info = "Unknown";

	return $info;
}
function lshal_system_extract($name)
{
	return lshal_extract($name, "/org/freedesktop/Hal/devices/computer");
}
function read_linux_sensors($attribute)
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
function parse_lspci_output($desc)
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
function parse_lsb_output($desc)
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

?>
