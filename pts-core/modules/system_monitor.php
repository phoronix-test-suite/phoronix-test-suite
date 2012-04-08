<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2012, Phoronix Media
	Copyright (C) 2008 - 2012, Michael Larabel
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
	const module_version = '3.1.0';
	const module_description = 'This module contains sensor monitoring support.';
	const module_author = 'Michael Larabel';

	static $result_identifier = null;
	static $to_monitor = array();
	static $monitor_test_count = 0;

	static $individual_test_run_request = null;
	static $successful_test_run_request = null;
	static $individual_test_run_offsets = null;
	static $individual_monitoring = null;

	public static function module_environmental_variables()
	{
		return array('MONITOR', 'PERFORMANCE_PER_WATT');
	}
	public static function module_info()
	{
		$info = null;

		$info .= PHP_EOL . 'Monitoring these sensors are as easy as running your normal Phoronix Test Suite commands but at the beginning of the command add: MONITOR=<selected sensors> (example: MONITOR=cpu.temp,cpu.voltage phoronix-test-suite benchmark universe). If the PERFORMANCE_PER_WATT environment variable is set, a performance per Watt graph will also be added, assuming the system\'s power consumption can be monitored. Below are all of the sensors supported by this version of the Phoronix Test Suite.' . PHP_EOL . PHP_EOL;
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
		self::$individual_monitoring = pts_module::read_variable('MONITOR_INDIVIDUAL') !== '0';
		self::$to_monitor = array();
		$to_show = pts_strings::comma_explode(pts_module::read_variable('MONITOR'));
		$monitor_all = in_array('all', $to_show);

		if(pts_module::read_variable('PERFORMANCE_PER_WATT'))
		{
			// We need to ensure the system power consumption is being tracked to get performance-per-Watt
			pts_arrays::unique_push($to_show, 'sys.power');
			self::$individual_monitoring = true;
		}

		foreach(phodevi::supported_sensors() as $sensor)
		{
			if($monitor_all || in_array(phodevi::sensor_identifier($sensor), $to_show) || in_array('all.' . $sensor[0], $to_show))
			{
				array_push(self::$to_monitor, $sensor);
				pts_module::save_file('logs/' . phodevi::sensor_identifier($sensor));
			}
		}

		if(count(self::$to_monitor) > 0)
		{
			echo PHP_EOL . 'Sensors To Be Logged:';
			foreach(self::$to_monitor as &$sensor)
			{
				echo PHP_EOL . '   - ' . phodevi::sensor_name($sensor);
			}
			echo PHP_EOL;
		}
		sleep(4);

		pts_module::pts_timed_function('pts_monitor_update', 2);
	}
	public static function __pre_test_run($test_run_request)
	{
		if(self::$individual_monitoring == false)
		{
			return;
		}

		self::$individual_test_run_request = clone $test_run_request;

		foreach(self::$to_monitor as $sensor)
		{
			$log_f = pts_module::read_file('logs/' . phodevi::sensor_identifier($sensor));
			$offset = count(explode(PHP_EOL, $log_f));
			self::$individual_test_run_offsets[phodevi::sensor_identifier($sensor)] = $offset;
		}
	}
	public static function __post_test_run_success($test_run_request)
	{
		self::$successful_test_run_request = clone $test_run_request;
	}
	public static function __post_test_run_process(&$result_file_writer)
	{
		if(self::$individual_monitoring == false || count(self::$to_monitor) == 0)
		{
			return;
		}

		if(pts_module::read_variable('PERFORMANCE_PER_WATT'))
		{
			$sensor = array('sys', 'power');
			$sensor_results = self::parse_monitor_log('logs/' . phodevi::sensor_identifier($sensor), self::$individual_test_run_offsets[phodevi::sensor_identifier($sensor)]);

			if(count($sensor_results) > 2)
			{
				// Copy the value each time as if you are directly writing the original data, each succeeding time in the loop the used arguments gets borked
				$test_result = clone self::$successful_test_run_request;
				$process_perf_per_watt = true;

				$watt_average = array_sum($sensor_results) / count($sensor_results);
				switch(phodevi::read_sensor_unit($sensor))
				{
					case 'Milliwatts':
						$watt_average = $watt_average / 1000;
					case 'Watts':
						break;
					default:
						$process_perf_per_watt = false;
				}

				if($process_perf_per_watt && $watt_average > 0 && $test_result->test_profile->get_display_format() == 'BAR_GRAPH')
				{
					$test_result->test_profile->set_identifier(null);
					//$test_result->set_used_arguments_description(phodevi::sensor_name('sys.power') . ' Monitor');
					//$test_result->set_used_arguments(phodevi::sensor_name('sys.power') . ' ' . $test_result->get_arguments());

					if($test_result->test_profile->get_result_proportion() == 'HIB')
					{
						$test_result->test_profile->set_result_scale($test_result->test_profile->get_result_scale() . ' Per Watt');

						foreach($test_result->test_result_buffer->buffer_items as &$buffer_item)
						{
							$buffer_item->reset_result_value(($buffer_item->get_result_value() / $watt_average));
							$buffer_item->reset_raw_value(null);
						}

						$result_file_writer->add_result_from_result_object_with_value($test_result);
					}
					else if($test_result->test_profile->get_result_proportion() == 'LIB')
					{
						$test_result->test_profile->set_result_proportion('HIB');
						$test_result->test_profile->set_result_scale('Performance Per Watt');

						foreach($test_result->test_result_buffer->buffer_items as &$buffer_item)
						{
							$buffer_item->reset_result_value(1 / ($buffer_item->get_result_value() / $watt_average));
							$buffer_item->reset_raw_value(null);
						}

						$result_file_writer->add_result_from_result_object_with_value($test_result);
					}
				}
			}
		}

		foreach(self::$to_monitor as $sensor)
		{
			$sensor_results = self::parse_monitor_log('logs/' . phodevi::sensor_identifier($sensor), self::$individual_test_run_offsets[phodevi::sensor_identifier($sensor)]);

			if(count($sensor_results) > 2)
			{
				// Copy the value each time as if you are directly writing the original data, each succeeding time in the loop the used arguments gets borked
				$test_result = clone self::$individual_test_run_request;

				$test_result->test_profile->set_identifier(null);
				$test_result->test_profile->set_result_proportion('LIB');
				$test_result->test_profile->set_display_format('LINE_GRAPH');
				$test_result->test_profile->set_result_scale(phodevi::read_sensor_unit($sensor));
				$test_result->set_used_arguments_description(phodevi::sensor_name($sensor) . ' Monitor');
				$test_result->set_used_arguments(phodevi::sensor_name($sensor) . ' ' . $test_result->get_arguments());

				$result_file_writer->add_result_from_result_object_with_value_string($test_result, implode(',', $sensor_results), implode(',', $sensor_results));
			}
			self::$individual_test_run_offsets[phodevi::sensor_identifier($sensor)] = array();
		}

		self::$successful_test_run_request = null;
		self::$individual_test_run_request = null;
		self::$monitor_test_count++;
	}
	public static function __event_results_process(&$test_run_manager)
	{
		foreach(self::$to_monitor as $sensor)
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
		$line_breaks = explode(PHP_EOL, $log_f);
		$results = array();

		for($i = 0; $i < $start_offset && isset($line_breaks[$i]); $i++)
		{
			unset($line_breaks[$i]);
		}

		foreach($line_breaks as $line)
		{
			$line = trim($line);

			if(!empty($line) && $line >= 0)
			{
				array_push($results, $line);
			}
		}

		if(count($results) > 0 && max($results) == 0)
		{
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
