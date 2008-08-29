#!/bin/sh

cat > sensors-check << 'EOT'
<?php
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

$sensors = array();
$sensor_results = array();

array_push($sensors, read_sensors("VCore"));

$v3 = read_sensors("+3.3V");
if(empty($v3))
	$v3 = read_sensors("V3.3");
array_push($sensors, $v3);

$v5 = read_sensors("+5V");
if(empty($v5))
	$v5 = read_sensors("V5");
array_push($sensors, $v5);

$v12 = read_sensors("+12V");
if(empty($v12))
	$v12 = read_sensors("V12");
array_push($sensors, $v12);

$cpu_fan = read_sensors("CPU Fan");
if(empty($cpu_fan))
	$cpu_fan = read_sensors("fan1");
array_push($sensors, $cpu_fan);

$cpu_temp = read_sensors("CPU Temp");
if(empty($cpu_temp))
	$cpu_temp = read_sensors("temp1");
array_push($sensors, $cpu_temp);

$sys_temp = read_sensors("Sys Temp");
if(empty($sys_temp))
	$sys_temp = read_sensors("Board Temp");
if(empty($sys_temp))
	$sys_temp = read_sensors("temp2");
array_push($sensors, $sys_temp);

foreach($sensors as $single_sensor)
{
	if(is_numeric($single_sensor) && $single_sensor > 1)
		array_push($sensor_results, "PASS");
	else
		array_push($sensor_results, "FAIL");
}

echo implode(",", $sensor_results) . "\n";

?>
EOT

cat > compliance-sensors << 'EOT'
#!/bin/sh
php sensors-check
EOT
chmod +x compliance-sensors
