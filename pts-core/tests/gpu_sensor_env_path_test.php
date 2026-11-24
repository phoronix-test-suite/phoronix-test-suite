<?php

/*
	Phoronix Test Suite
	Copyright (C) 2026, Phoronix Media
	Copyright (C) 2026, Michael Larabel

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.
*/

require_once(dirname(__DIR__) . '/objects/pts_env.php');
require_once(dirname(__DIR__) . '/objects/pts_file_io.php');
require_once(dirname(__DIR__) . '/objects/pts_math.php');
require_once(dirname(__DIR__) . '/objects/phodevi/phodevi_sensor.php');
require_once(dirname(__DIR__) . '/objects/phodevi/sensors/gpu_freq.php');
require_once(dirname(__DIR__) . '/objects/phodevi/sensors/gpu_memory_usage.php');
require_once(dirname(__DIR__) . '/objects/phodevi/sensors/gpu_power.php');
require_once(dirname(__DIR__) . '/objects/phodevi/sensors/gpu_temp.php');
require_once(dirname(__DIR__) . '/objects/phodevi/sensors/gpu_usage.php');
require_once(dirname(__DIR__) . '/objects/phodevi/sensors/gpu_voltage.php');

function pts_gpu_sensor_env_path_test_cleanup($test_dir, $env_vars)
{
	foreach($env_vars as $env_var)
	{
		putenv($env_var);
	}
	if($test_dir != false && is_dir($test_dir))
	{
		foreach(pts_file_io::glob($test_dir . DIRECTORY_SEPARATOR . '*') as $file)
		{
			if(is_file($file))
			{
				unlink($file);
			}
		}
		rmdir($test_dir);
	}
}

function pts_gpu_sensor_env_path_test_fail($message, $test_dir, $env_vars)
{
	pts_gpu_sensor_env_path_test_cleanup($test_dir, $env_vars);
	echo 'gpu_sensor_env_path_test failed: ' . $message . PHP_EOL;
	exit(1);
}

$env_vars = array(
	'PTS_GPU_FREQ_INPUT_PATH',
	'PTS_GPU_MEMORY_USAGE_INPUT_PATH',
	'PTS_GPU_POWER_INPUT_PATH',
	'PTS_GPU_TEMP_INPUT_PATH',
	'PTS_GPU_USAGE_INPUT_PATH',
	'PTS_GPU_VOLTAGE_INPUT_PATH',
	);

$test_dir = tempnam(sys_get_temp_dir(), 'pts-gpu-sensors-');
if($test_dir == false || !unlink($test_dir) || !mkdir($test_dir))
{
	pts_gpu_sensor_env_path_test_fail('unable to create temporary directory', $test_dir, $env_vars);
}

$files = array(
	'freq' => '0: 300Mhz' . PHP_EOL . '1: 1234Mhz *' . PHP_EOL,
	'memory' => '256000000' . PHP_EOL,
	'power' => '10000000' . PHP_EOL,
	'temp' => '47500' . PHP_EOL,
	'usage' => '73' . PHP_EOL,
	'voltage' => '950' . PHP_EOL,
	);

foreach($files as $name => $contents)
{
	if(file_put_contents($test_dir . DIRECTORY_SEPARATOR . $name, $contents) === false)
	{
		pts_gpu_sensor_env_path_test_fail('unable to create synthetic ' . $name . ' input', $test_dir, $env_vars);
	}
}

putenv('PTS_GPU_FREQ_INPUT_PATH=' . $test_dir . DIRECTORY_SEPARATOR . 'freq');
putenv('PTS_GPU_MEMORY_USAGE_INPUT_PATH=' . $test_dir . DIRECTORY_SEPARATOR . 'memory');
putenv('PTS_GPU_POWER_INPUT_PATH=' . $test_dir . DIRECTORY_SEPARATOR . 'power');
putenv('PTS_GPU_TEMP_INPUT_PATH=' . $test_dir . DIRECTORY_SEPARATOR . 'temp');
putenv('PTS_GPU_USAGE_INPUT_PATH=' . $test_dir . DIRECTORY_SEPARATOR . 'usage');
putenv('PTS_GPU_VOLTAGE_INPUT_PATH=' . $test_dir . DIRECTORY_SEPARATOR . 'voltage');

$checks = array(
	'gpu.freq' => array(new gpu_freq(0, null), 1234, 'Megahertz'),
	'gpu.memory-usage' => array(new gpu_memory_usage(0, null), 256, 'Megabytes'),
	'gpu.power' => array(new gpu_power(0, null), 10, 'Watts'),
	'gpu.temp' => array(new gpu_temp(0, null), 47.5, 'Celsius'),
	'gpu.usage' => array(new gpu_usage(0, null), 73, 'Percent'),
	'gpu.voltage' => array(new gpu_voltage(0, null), 950, 'Millivolts'),
	);

foreach($checks as $name => $check)
{
	list($sensor, $expected_value, $expected_unit) = $check;
	$value = $sensor->read_sensor();

	if($value != $expected_value)
	{
		pts_gpu_sensor_env_path_test_fail($name . ' expected ' . $expected_value . ' but read ' . $value, $test_dir, $env_vars);
	}
	if($sensor->get_unit() != $expected_unit)
	{
		pts_gpu_sensor_env_path_test_fail($name . ' expected unit ' . $expected_unit . ' but read ' . $sensor->get_unit(), $test_dir, $env_vars);
	}
	if(!$sensor->support_check())
	{
		pts_gpu_sensor_env_path_test_fail($name . ' expected support_check() to pass with override path', $test_dir, $env_vars);
	}
}

pts_gpu_sensor_env_path_test_cleanup($test_dir, $env_vars);
echo 'gpu_sensor_env_path_test passed' . PHP_EOL;

?>
