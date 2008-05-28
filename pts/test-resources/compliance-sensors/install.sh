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
array_push($sensors, read_sensors("CPU Fan"));
array_push($sensors, read_sensors("CPU Temp"));
array_push($sensors, read_sensors("Sys Temp"));

foreach($sensors as $single_sensor)
{
	if(is_numeric($sensors) && $sensors > 1)
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
