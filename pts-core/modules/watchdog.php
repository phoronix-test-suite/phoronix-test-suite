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
	private static $maximum_wait = 4;

	public static function module_environment_variables()
	{
		return array('WATCHDOG_SENSOR', 'WATCHDOG_SENSOR_THRESHOLD', 'WATCHDOG_MAXIMUM_WAIT');
	}
	public static function __run_manager_setup(&$test_run_manager)
	{
		$sensor_list = pts_strings::comma_explode(pts_env::read('WATCHDOG_SENSOR'));
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
		foreach(phodevi::query_sensors() as $sensor)
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

		$watchdog_threshold = pts_env::read('WATCHDOG_SENSOR_THRESHOLD');
		if(!is_numeric($watchdog_threshold) || $watchdog_threshold < 2)
		{
			echo PHP_EOL . 'UNLOADING WATCHDOG AS NO USEFUL DATA SET FOR WATCHDOG_SENSOR_THRESHOLD ENVIRONMENT VARIABLE' . PHP_EOL;
			return pts_module::MODULE_UNLOAD;
		}
		self::$monitor_threshold = $watchdog_threshold;

		echo PHP_EOL . pts_client::cli_just_bold('WATCHDOG ACTIVATED - TESTS WILL ABORT/DELAY IF ANY SENSOR CROSSES: ' . self::$monitor_threshold) . PHP_EOL;
		echo 'WATCHDOG MONITORING: ' . PHP_EOL;
		$monitors = array();
		foreach(self::$to_monitor as $sensor)
		{
			$monitors[] = array(strtoupper(phodevi::sensor_object_name($sensor)), phodevi::read_sensor($sensor), strtoupper(phodevi::read_sensor_object_unit($sensor)));
		}
		echo pts_user_io::display_text_table($monitors, '   ', 1) . PHP_EOL . PHP_EOL;

		$min_maximum_wait = pts_env::read('WATCHDOG_MAXIMUM_WAIT');
		if(is_numeric($min_maximum_wait) && $min_maximum_wait >= 1)
		{
			self::$maximum_wait = $min_maximum_wait;
		}
		echo PHP_EOL . pts_client::cli_just_bold('WATCHDOG WILL SLEEP SYSTEM UP TO ' . pts_strings::plural_handler(self::$maximum_wait, 'MINUTE') . ' IF/WHEN THRESHOLD BREACHED') . PHP_EOL;
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
				pts_client::$display->test_run_message(pts_client::cli_colored_text('Watchdog ' . phodevi::sensor_object_name($sensor) . ' Exceeded Threshold: ' . $val . ' ' . phodevi::read_sensor_object_unit($sensor), 'red', true));

				$freq_to_poll = 10;
				pts_client::$display->test_run_message(pts_client::cli_colored_text('Suspending testing; will wait up to ' . pts_strings::plural_handler(self::$maximum_wait, 'minute') . ' to settle.', 'red', false));
				for($i = 0; $i < (self::$maximum_wait * 60); $i += $freq_to_poll)
				{
					sleep($freq_to_poll);
					if(phodevi::read_sensor($sensor) < self::$monitor_threshold)
					{
						pts_client::$display->test_run_message(pts_client::cli_colored_text('Watchdog Restoring Process: ' . phodevi::sensor_object_name($sensor) . ': ' . phodevi::read_sensor($sensor) . ' ' . phodevi::read_sensor_object_unit($sensor), 'green', true));
						return true;
					}
				}
				pts_client::$display->test_run_message('Watchdog waited ' . pts_strings::plural_handler(self::$maximum_wait, 'minute') . ' but ' . phodevi::sensor_object_name($sensor) . ' at ' . phodevi::read_sensor($sensor) . ' ' . phodevi::read_sensor_object_unit($sensor));
				//exit;
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
