<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2016, Phoronix Media
	Copyright (C) 2016, Michael Larabel

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

class watchdog extends pts_module_interface
{
	const module_name = 'System Event Watchdog';
	const module_version = '1.0.0';
	const module_description = 'This module has support for stopping/interrupting tests if various system issues occur, like a temperature sensor exceeds a defined threshold.';
	const module_author = 'Michael Larabel';

	private static $to_monitor = null;
	private static $monitor_threshold = 0;

	public static function module_environmental_variables()
	{
		return array('WATCHDOG_SENSOR', 'WATCHDOG_SENSOR_THRESHOLD');
	}

	public static function __startup()
	{
		$sensor_list = pts_strings::comma_explode(pts_module::read_variable('WATCHDOG_SENSOR'));
		$to_monitor = array();
		// A LOT OF THIS CODE IN THIS FUNCTION PORTED OVER FROM system_monitor MODULE
		foreach($sensor_list as $sensor)
		{
			$sensor_split = pts_strings::trim_explode('.', $sensor);
			$type = &$sensor_split[0];
			$name = &$sensor_split[1];
			$parameter = &$sensor_split[2];

			if(empty($to_monitor[$type][$name]))
			{
				$to_monitor[$type][$name] = array();
			}

			if($parameter !== NULL)
			{
				$to_monitor[$type][$name][] = $parameter;
			}
		}
		foreach(phodevi::supported_sensors() as $sensor)
		{
			if(array_key_exists($sensor[0], $to_monitor) && array_key_exists($sensor[1], $to_monitor[$sensor[0]]))
			{
				$supported_devices = call_user_func(array($sensor[2], 'get_supported_devices'));
				$instance_no = 0;

				if($supported_devices === NULL)
				{
					self::create_single_sensor_instance($sensor, 0, NULL);
				}
				else
				{
					foreach($supported_devices as $device)
					{
						self::create_single_sensor_instance($sensor, $instance_no++, $device);
					}
				}
			}
		}
		// END OF PORTED CODE FROM system_monitor

		if(empty(self::$to_monitor))
		{
			echo PHP_EOL . 'UNLOADING WATCHDOG AS NO SENSORS TO MONITOR' . PHP_EOL;
			return pts_module::MODULE_UNLOAD;
		}

		$watchdog_threshold = getenv('WATCHDOG_SENSOR_THRESHOLD');
		if(!is_numeric($watchdog_threshold) || $watchdog_threshold < 2)
		{
			echo PHP_EOL . 'UNLOADING WATCHDOG AS NO USEFUL DATA SET FOR WATCHDOG_SENSOR_THRESHOLD ENVIRONMENT VARIABLE' . PHP_EOL;
			return pts_module::MODULE_UNLOAD;
		}
		self::$monitor_threshold = $watchdog_threshold;

		echo PHP_EOL . 'WATCHDOG ACTIVATED - TESTS WILL ABORT IF ANY SENSOR CROSSES: ' . self::$monitor_threshold . PHP_EOL;
		echo 'WATCHDOG MONITORING: ' . PHP_EOL;
		$monitors = array();
		foreach(self::$to_monitor as $sensor)
		{
			$monitors[] = array(strtoupper(phodevi::sensor_object_name($sensor)), phodevi::read_sensor($sensor), strtoupper(phodevi::read_sensor_object_unit($sensor)));
		}
		echo pts_user_io::display_text_table($monitors, '   ', 1) . PHP_EOL . PHP_EOL;
	}

	public static function __pre_run_process()
	{
		self::check_watchdog();
	}
	public static function __pre_test_run()
	{
		self::check_watchdog();
	}
	public static function __interim_test_run()
	{
		self::check_watchdog();
	}
	protected static function check_watchdog()
	{
		foreach(self::$to_monitor as $sensor)
		{
			$val = phodevi::read_sensor($sensor);
			if($val > self::$monitor_threshold)
			{
				echo PHP_EOL . PHP_EOL . 'WATCHDOG: ' . strtoupper(phodevi::sensor_object_name($sensor)) . ' EXCEEDED THRESHOLD: ' . $val . ' ' . strtoupper(phodevi::read_sensor_object_unit($sensor)) . PHP_EOL;

				$minutes_to_wait = 3;
				$freq_to_poll = 5;
				echo 'SUSPENDING TESTING; WILL WAIT UP TO ' . $minutes_to_wait . ' MINUTES TO SEE IF VALUE LOWERED. ' . PHP_EOL . PHP_EOL;
				for($i = 0; $i < ($minutes_to_wait * 60); $i += $freq_to_poll)
				{
					sleep($freq_to_poll);
					if(phodevi::read_sensor($sensor) < self::$monitor_threshold)
					{
						echo PHP_EOL . 'WATCHDOG RESTORING PROCESS: ' . strtoupper(phodevi::sensor_object_name($sensor)) . ': ' . phodevi::read_sensor($sensor) . ' ' . strtoupper(phodevi::read_sensor_object_unit($sensor)) . PHP_EOL;
						return true;
					}
				}
				echo 'WATCHDOG: EXITING PROGRAM' . PHP_EOL;
				exit;
			}
		}
	}
	private static function create_single_sensor_instance($sensor, $instance, $param)
	{
		if(call_user_func(array($sensor[2], 'parameter_check'), $param) === true)
		{
			$sensor_object = new $sensor[2]($instance, $param);
			self::$to_monitor[] = $sensor_object;
		}
	}
}

?>
