#!/bin/sh

cat > acpi-checks << 'EOT'
<?php
function read_delimit($contents, $match)
{
	$value = "";
	$cpuinfo_lines = explode("\n", $contents);

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
	return $value;
}

$acpi_results = array();

$acpi_battery = "FAIL";
foreach(glob("/proc/acpi/video/*/*/brightness") as $brightness_file)
	if($acpi_battery == "FAIL")
	{
		$brightness_file_contents = file_get_contents($brightness_file);
		if(strpos($brightness_file_contents, "levels") !== FALSE && strpos($brightness_file_contents, "current") !== FALSE)
			$acpi_battery = "PASS";
	}
array_push($acpi_results, $acpi_battery);

$acpi_cpupm = "FAIL";
foreach(glob("/proc/acpi/processor/*/info") as $cpu_info_file)
	if(read_delimit(file_get_contents($cpu_info_file), "power management") == "yes")
		$acpi_cpupm = "PASS";
array_push($acpi_results, $acpi_cpupm);

$acpi_thermal_zone = "FAIL";
foreach(glob("/proc/acpi/thermal_zone/*/temperature") as $temperature_file)
	if($acpi_thermal_zone == "FAIL")
		if(strlen(read_delimit(file_get_contents($temperature_file), "temperature")) > 1)
			$acpi_thermal_zone = "PASS";
array_push($acpi_results, $acpi_thermal_zone);

$acpi_suspend = "FAIL";
if(is_file("/proc/acpi/sleep") && strpos(($sleep_file = file_get_contents("/proc/acpi/sleep")), "S3") > 0 && strpos($sleep_file, "S4") > 0)
	$acpi_suspend = "PASS";
array_push($acpi_results, $acpi_suspend);

echo implode(",", $acpi_results) . "\n";

?>
EOT

cat > compliance-acpi << 'EOT'
#!/bin/sh
php acpi-checks > $LOG_FILE
EOT
chmod +x compliance-acpi
