<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2011, Phoronix Media
	Copyright (C) 2008 - 2011, Michael Larabel
	system_monitor.php: System sensor monitoring module for PTS

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

class system_monitor extends pts_module_interface
{
	const module_name = 'System Monitor';
	const module_version = '3.0.0';
	const module_description = 'This module contains sensor monitoring support.';
	const module_author = 'Michael Larabel';

	static $result_identifier = null;
	static $to_monitor = array();
	static $monitor_test_count = 0;

	static $individual_test_run_request = null;
	static $individual_test_run_offsets = null;
	static $individual_monitoring = null;

	public static function module_info()
	{
		$info = null;

		$info .= PHP_EOL . 'Monitoring these sensors are as easy as running your normal Phoronix Test Suite commands but at the beginning of the command add: MONITOR=<selected sensors> (example: MONITOR=cpu.temp,cpu.voltage phoronix-test-suite benchmark universe). Below are all of the sensors supported by this version of the Phoronix Test Suite.' . PHP_EOL . PHP_EOL;
		$info .= 'Supported Options:' . PHP_EOL . PHP_EOL;

		foreach(self::monitor_arguments() as $arg)
		{
			$info .= '  - ' . $arg . PHP_EOL;
		}

		return $info;
	}

	//
	// General Functions
	//

	public static function __run_manager_setup(&$test_run_manager)
	{
		$test_run_manager->force_results_save();
	}

	public static function __pre_run_process(&$test_run_manager)
	{
		self::$result_identifier = $test_run_manager->get_results_identifier();
		self::$individual_monitoring = pts_module::read_variable('MONITOR_INDIVIDUAL') == '1';
		self::$individual_monitoring = true;
		self::$to_monitor = array();
		$to_show = pts_strings::comma_explode(pts_module::read_variable('MONITOR'));
		$monitor_all = in_array('all', $to_show);

		foreach(phodevi::supported_sensors() as $sensor)
		{
			if($monitor_all || in_array(phodevi::sensor_identifier($sensor), $to_show) || in_array('all.' . $sensor[0], $to_show))
			{
				array_push(self::$to_monitor, $sensor);
				pts_module::save_file('logs/' . phodevi::sensor_identifier($sensor));
			}
		}

		pts_module::pts_timed_function('pts_monitor_update', 3);
	}
	public static function __pre_test_run(&$test_run_request)
	{
		if(self::$individual_monitoring == false)
		{
			return;
		}

		self::$individual_test_run_request = $test_run_request;

		foreach(self::$to_monitor as $id_point => $sensor)
		{
			$log_f = pts_module::read_file('logs/' . phodevi::sensor_identifier($sensor));
			$offset = count(explode("\n", $log_f));
			self::$individual_test_run_offsets[$id_point] = $offset;
		}
	}
	public static function __post_test_run_process(&$result_file_writer)
	{
		if(self::$individual_monitoring == false || count(self::$to_monitor) == 0)
		{
			return;
		}

		foreach(self::$to_monitor as $id_point => $sensor)
		{
			$sensor_results = self::parse_monitor_log('logs/' . phodevi::sensor_identifier($sensor), self::$individual_test_run_offsets[$id_point]);

			// Copy the value each time as if you are directly writing the original data, each succeeding time in the loop the used arguments gets borked
			$test_result = self::$individual_test_run_request;

			if(count($sensor_results) > 2)
			{
				$test_result->test_profile->set_identifier(null);
				$test_result->test_profile->set_result_proportion('LIB');
				$test_result->test_profile->set_display_format('LINE_GRAPH');
				$test_result->test_profile->set_result_scale(phodevi::read_sensor_unit($sensor));
				$test_result->set_used_arguments_description(phodevi::sensor_name($sensor) . ' Monitor');
				$test_result->set_used_arguments(phodevi::sensor_name($sensor) . ' ' . $test_result->get_arguments());

				$result_file_writer->add_result_from_result_object_with_value_string($test_result, implode(',', $sensor_results), implode(',', $sensor_results));
			}
		}

		self::$individual_test_run_request = null;
		self::$individual_test_run_offsets[$id_point] = array();
		self::$monitor_test_count++;
	}
	public static function __event_results_process(&$test_run_manager)
	{
		foreach(self::$to_monitor as $id_point => $sensor)
		{
			$sensor_results = self::parse_monitor_log('logs/' . phodevi::sensor_identifier($sensor));
			pts_module::remove_file('logs/' . phodevi::sensor_identifier($sensor));

			if(count($sensor_results) > 2 && self::$monitor_test_count > 1)
			{
				$test_profile = new pts_test_profile();
				$test_result = new pts_test_result($test_profile);

				$test_result->test_profile->set_test_title(phodevi::sensor_name($sensor) . ' Monitor');
				$test_result->test_profile->set_identifier(null);
				$test_result->test_profile->set_version(null);
				$test_result->test_profile->set_result_proportion(null);
				$test_result->test_profile->set_display_format('LINE_GRAPH');
				$test_result->test_profile->set_result_scale(phodevi::read_sensor_unit($sensor));
				$test_result->set_used_arguments_description('Phoronix Test Suite System Monitoring');
				$test_result->set_used_arguments(phodevi::sensor_identifier($sensor));
				$test_run_manager->result_file_writer->add_result_from_result_object_with_value_string($test_result, implode(',', $sensor_results), implode(',', $sensor_results));
			}
		}
	}
	public static function pts_monitor_update()
	{
		foreach(self::$to_monitor as $sensor)
		{
			$sensor_value = phodevi::read_sensor($sensor);

			if($sensor_value != -1 && pts_module::is_file('logs/' . phodevi::sensor_identifier($sensor)))
			{
				pts_module::save_file('logs/' . phodevi::sensor_identifier($sensor), $sensor_value, true);
			}
		}
	}
	private static function parse_monitor_log($log_file, $start_offset = 0)
	{
		$log_f = pts_module::read_file($log_file);
		$line_breaks = explode("\n", $log_f);
		$contains_a_non_zero = false;
		$results = array();

		for($i = 0; $i < $start_offset && isset($line_breaks[$i]); $i++)
		{
			unset($line_breaks[$i]);
		}

		foreach($line_breaks as $line)
		{
			$line = trim($line);

			if(!empty($line))
			{
				array_push($results, $line);

				if(!$contains_a_non_zero && $line != 0)
				{
					$contains_a_non_zero = true;
				}
			}
		}

		if(!$contains_a_non_zero)
		{
			// Sensor likely not doing anything if ALL of its readings are 0
			$results = array();
		}

		return $results;
	}
	private static function monitor_arguments()
	{
		$args = array('all');

		foreach(phodevi::available_sensors() as $sensor)
		{
			if(!in_array('all.' . $sensor[0], $args))
			{
				array_push($args, 'all.' . $sensor[0]);
			}

			array_push($args, phodevi::sensor_identifier($sensor));
		}

		return $args;
	}
}

?>
