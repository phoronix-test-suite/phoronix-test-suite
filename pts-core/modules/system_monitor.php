<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2015, Phoronix Media
	Copyright (C) 2008 - 2015, Michael Larabel
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

// TODO XXX: Port to new phodevi_sensor_monitor interface

class system_monitor extends pts_module_interface
{
	const module_name = 'System Monitor';
	const module_version = '3.2.0';
	const module_description = 'This module contains sensor monitoring support.';
	const module_author = 'Michael Larabel';

	private static $result_identifier = null;
	private static $to_monitor = array();
	private static $monitor_test_count = 0;

	private static $individual_test_run_request = null;
	private static $successful_test_run_request = null;
	private static $individual_test_run_offsets = null;
	private static $test_run_tries_offsets = null;

	private static $individual_monitoring = null;
	private static $per_test_run_monitoring = null;

	private static $cgroup_name = 'pts_monitor';		// default name for monitoring cgroup
	private static $cgroup_enabled_controllers = array();

	private static $test_run_try_number = null;
	private static $sensor_monitoring_frequency = 2;
	private static $test_run_timer = 0;
	private static $perf_per_watt_collection;

	public static function module_environmental_variables()
	{
		return array('MONITOR_PARAM_FILE', 'PERFORMANCE_PER_WATT', 'MONITOR_INTERVAL' );
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
		//$test_run_manager->force_results_save();
		$test_run_manager->disable_dynamic_run_count();
	}
	public static function __pre_run_process(&$test_run_manager)
	{
		self::$result_identifier = $test_run_manager->get_results_identifier();
		self::$individual_monitoring = pts_module::read_variable('MONITOR_INDIVIDUAL') !== '0';
		self::$per_test_run_monitoring = pts_module::read_variable('MONITOR_PER_RUN') === '1';		//TODO change to true?

		self::$to_monitor = array();

		// If tests will be repeated several times, this is the first try.
		self::$test_run_try_number = 0;

        $sensor_parameters = self::prepare_sensor_parameters();

//		if(pts_module::read_variable('PERFORMANCE_PER_WATT'))
//		{
//			// We need to ensure the system power consumption is being tracked to get performance-per-Watt
//			pts_arrays::unique_push($to_show, 'sys.power');
//			self::$individual_monitoring = true;
//			echo PHP_EOL . 'To Provide Performance-Per-Watt Outputs.' . PHP_EOL;
//		}

		$monitor_all = in_array('all', $sensor_parameters);
		foreach (phodevi::supported_sensors() as $sensor)
		{
			// add sensor to monitoring list if:
			// a) we want to monitor all the available sensors,
			// b) we want to monitor all the available sensors of the specified type,
			// c) parameter array contains this sensor, eg. there exists value for $sensor_parameters[sens_type][sens_name]
			// ($sensor[0] is the type, $sensor[1] is the name, $sensor[2] is the class name)
			if ($monitor_all  || (array_key_exists($sensor[0], $sensor_parameters)
				&& (array_key_exists($sensor[1], $sensor_parameters[$sensor[0]]) || array_key_exists('all', $sensor_parameters[$sensor[0]]) )))
			{
				// create objects for all specified instances of the sensor
				foreach ($sensor_parameters[$sensor[0]][$sensor[1]] as $instance => $params)
				{
					if ($sensor[0] === 'cgroup')
					{
						$cgroup_controller = call_user_func(array($sensor[2], 'get_cgroup_controller'));
						array_push(self::$cgroup_enabled_controllers, $cgroup_controller );
						self::cgroup_create(self::$cgroup_name, $cgroup_controller);
						$params['cgroup_name'] = self::$cgroup_name;
					}

					if (call_user_func(array($sensor[2], 'parameter_check'), $params) === true)
					{
						$sensor_object = new $sensor[2]($instance, $params);
						array_push(self::$to_monitor, $sensor_object);
						pts_module::save_file('logs/' . phodevi::sensor_object_identifier($sensor_object));
					}
					//TODO show information when passed parameters are incorrect
				}
			}
		}

		// create cgroups in all of the needed controllers
		foreach (self::$cgroup_enabled_controllers as $controller)
		{
			self::cgroup_create(self::$cgroup_name, $controller);
		}

		//TODO rewrite when new monitoring system is finished
//		if(in_array('i915_energy', $to_show) && is_readable('/sys/kernel/debug/dri/0/i915_energy'))
//		{
//			// For now the Intel monitoring is a special case separate from the rest
//			// of the unified sensor monitoring since we're not polling it every time but just pre/post test.
//			self::$monitor_i915_energy = true;
//		}

		if(count(self::$to_monitor) > 0)
		{
			echo PHP_EOL . 'Sensors To Be Logged:';
			foreach(self::$to_monitor as &$sensor)
			{
				echo PHP_EOL . '   - ' . phodevi::sensor_object_name($sensor);
			}
			echo PHP_EOL;

			if(pts_module::read_variable('MONITOR_INTERVAL') != null)
			{
				$proposed_interval = pts_module::read_variable('MONITOR_INTERVAL');
				if(is_numeric($proposed_interval) && $proposed_interval >= 0)
				{
					self::$sensor_monitoring_frequency = $proposed_interval;
				}
			}

			// Pad some idling sensor results at the start
			sleep((self::$sensor_monitoring_frequency * 8));
		}

		//pts_module::pts_timed_function('pts_monitor_update', self::$sensor_monitoring_frequency);
		self::pts_start_monitoring();
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
			$log_f = pts_module::read_file('logs/' . phodevi::sensor_object_identifier($sensor));
			$offset = count(explode(PHP_EOL, $log_f));
			self::$individual_test_run_offsets[phodevi::sensor_object_identifier($sensor)] = $offset;
		}

		// Just to pad in some idling into the run process
		sleep(self::$sensor_monitoring_frequency);

		self::$test_run_timer = time();
	}

	public static function __test_running($test_process)
	{
		foreach (self::$cgroup_enabled_controllers as $controller)
		{
			//that needs higher PHP version
			$parent_pid = proc_get_status($test_process)['pid'];
			file_put_contents('/sys/fs/cgroup/' . $controller . '/' . self::$cgroup_name .'/tasks', $parent_pid);
		}

	}

	public static function __interim_test_run()
	{
		if (self::$per_test_run_monitoring)
		{
			self::save_try_offset();
		}
	}

	public static function __post_test_run_success($test_run_request)
	{
		if (self::$per_test_run_monitoring)
		{
			self::save_try_offset();
		}
		self::$successful_test_run_request = clone $test_run_request;
	}
	public static function __post_test_run_process(&$result_file)
	{
		if((self::$individual_monitoring == false || count(self::$to_monitor) == 0))
		{
			return;
		}

		// The self::$test_run_timer to contain how long each individual test run lasted, should anything else past this point want to use the info...
		self::$test_run_timer = time() - self::$test_run_timer;

		// Let the system return to brief idling..
		//sleep(self::$sensor_monitoring_frequency * 8);

		if(pts_module::read_variable('PERFORMANCE_PER_WATT'))
		{
			$sensor = array('sys', 'power');
			$sensor_results = self::parse_monitor_log('logs/' . phodevi::sensor_identifier($sensor), self::$individual_test_run_offsets[phodevi::sensor_identifier($sensor)]);

			if(count($sensor_results) > 2 && self::$successful_test_run_request)
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
					$test_result->test_result_buffer = new pts_test_result_buffer();

					if($test_result->test_profile->get_result_proportion() == 'HIB')
					{
						$test_result->test_profile->set_result_scale($test_result->test_profile->get_result_scale() . ' Per Watt');
						$test_result->test_result_buffer->add_test_result(self::$result_identifier, pts_math::set_precision($test_result->active->get_result() / $watt_average));
						$result_file->add_result($test_result);
					}
					else if($test_result->test_profile->get_result_proportion() == 'LIB')
					{
						$test_result->test_profile->set_result_proportion('HIB');
						$test_result->test_profile->set_result_scale('Performance Per Watt');
						$test_result->test_result_buffer->add_test_result(self::$result_identifier, pts_math::set_precision((1 / $test_result->active->get_result()) / $watt_average));
						$result_file->add_result($test_result);
					}
					array_push(self::$perf_per_watt_collection, $test_result->active->get_result());
				}
			}
		}

		foreach(self::$to_monitor as $sensor)
		{
			$result_buffer = new pts_test_result_buffer();

			if (self::$per_test_run_monitoring)
			{
				foreach (self::$test_run_tries_offsets as $try_number => $try_offsets)	//TODO change to array_keys
				{
					if ($try_number === 0)
					{
						$start_offset = self::$individual_test_run_offsets[phodevi::sensor_object_identifier($sensor)];
					}
					else
					{
						$start_offset = self::$test_run_tries_offsets[$try_number - 1][phodevi::sensor_object_identifier($sensor)];
					}
					$end_offset = self::$test_run_tries_offsets[$try_number][phodevi::sensor_object_identifier($sensor)];

					$sensor_results = self::parse_monitor_log('logs/' . phodevi::sensor_object_identifier($sensor),
										$start_offset, $end_offset);

					if(count($sensor_results) > 2)
					{
						$result_identifier = self::$result_identifier . " (try " . ($try_number + 1) . ")";
						$result_value = implode(',', $sensor_results);
						$result_buffer->add_test_result($result_identifier, $result_value, $result_value);
					}
				}
			}
			else
			{
				$sensor_results = self::parse_monitor_log('logs/' . phodevi::sensor_object_identifier($sensor),
									self::$individual_test_run_offsets[phodevi::sensor_object_identifier($sensor)]);
			}

			//TODO result count checks should probably be done before cloning the test_result

			// Copy the value each time as if you are directly writing the original data, each succeeding time in the loop the used arguments gets borked
			$test_result = clone self::$individual_test_run_request;

			$test_result->test_profile->set_identifier(null);
			$test_result->test_profile->set_result_proportion('LIB');
			$test_result->test_profile->set_display_format('LINE_GRAPH');
			$test_result->test_profile->set_result_scale(phodevi::read_sensor_object_unit($sensor));
			$test_result->set_used_arguments_description(phodevi::sensor_object_name($sensor) . ' Monitor');
			$test_result->set_used_arguments(phodevi::sensor_object_name($sensor) . ' ' . $test_result->get_arguments());
			$test_result->test_result_buffer = $result_buffer;

			if (self::$per_test_run_monitoring && $result_buffer->get_count() > 1)
			{
				$test_result->set_used_arguments_description(phodevi::sensor_object_name($sensor) . ' Per Test Try Monitor');
			}
			elseif(count($sensor_results) > 2)
			{
				$test_result->test_result_buffer->add_test_result(self::$result_identifier, implode(',', $sensor_results), implode(',', $sensor_results));
			}

			$result_file->add_result($test_result);

			self::$individual_test_run_offsets[phodevi::sensor_object_identifier($sensor)] = array();
			//TODO reset self::$test_run_tries_offsets
		}

		self::$successful_test_run_request = null;
		self::$individual_test_run_request = null;
		self::$monitor_test_count++;

		// Let the system rest before jumping to next test...
		sleep((self::$sensor_monitoring_frequency * 6));
	}
	public static function __event_results_process(&$test_run_manager)
	{
		if(count(self::$perf_per_watt_collection) > 2)
		{
			// Performance per watt overall
			$avg = array_sum(self::$perf_per_watt_collection) / count(self::$perf_per_watt_collection);
			$test_profile = new pts_test_profile();
			$test_result = new pts_test_result($test_profile);
			$test_result->test_profile->set_test_title('Meta Performance Per Watt');
			$test_result->test_profile->set_identifier(null);
			$test_result->test_profile->set_version(null);
			$test_result->test_profile->set_result_proportion(null);
			$test_result->test_profile->set_display_format('BAR_GRAPH');
			$test_result->test_profile->set_result_scale('Performance Per Watt');
			$test_result->test_profile->set_result_proportion('HIB');
			$test_result->set_used_arguments_description('Performance Per Watt');
			$test_result->set_used_arguments('Per-Per-Watt');
			$test_result->test_result_buffer = new pts_test_result_buffer();
			$test_result->test_result_buffer->add_test_result(self::$result_identifier, pts_math::set_precision($avg));
			$test_run_manager->result_file->add_result($test_result);
		}

		echo PHP_EOL . 'Finishing System Sensor Monitoring Process' . PHP_EOL;
		//sleep((self::$sensor_monitoring_frequency * 4));
		foreach(self::$to_monitor as $sensor)
		{
			$sensor_results = self::parse_monitor_log('logs/' . phodevi::sensor_object_identifier($sensor));
			pts_module::remove_file('logs/' . phodevi::sensor_object_identifier($sensor));

			if(count($sensor_results) > 2 && self::$monitor_test_count > 1)
			{
				$test_profile = new pts_test_profile();
				$test_result = new pts_test_result($test_profile);

				$test_result->test_profile->set_test_title(phodevi::sensor_object_name($sensor) . ' Monitor');
				$test_result->test_profile->set_identifier(null);
				$test_result->test_profile->set_version(null);
				$test_result->test_profile->set_result_proportion(null);
				$test_result->test_profile->set_display_format('LINE_GRAPH');
				$test_result->test_profile->set_result_scale(phodevi::read_sensor_object_unit($sensor));
				$test_result->set_used_arguments_description('Phoronix Test Suite System Monitoring');
				$test_result->set_used_arguments(phodevi::sensor_identifier($sensor));
				$test_result->test_result_buffer = new pts_test_result_buffer();
				$test_result->test_result_buffer->add_test_result(self::$result_identifier, implode(',', $sensor_results), implode(',', $sensor_results), implode(',', $sensor_results), implode(',', $sensor_results));
				$test_run_manager->result_file->add_result($test_result);
			}
		}
	}

	public static function __post_run_process()
	{
		foreach (self::$cgroup_enabled_controllers as $controller)
		{
			self::cgroup_remove(self::$cgroup_name, $controller);
		}
	}

	private static function pts_start_monitoring()
	{
		foreach(self::$to_monitor as $sensor)
		{
			$pid = pts_module::pts_timed_function('pts_monitor_update', self::$sensor_monitoring_frequency, $sensor);
		}
	}

	// Updates single sensor.
	public static function pts_monitor_update($sensor)
	{
		$sensor_value = phodevi::read_sensor($sensor);

		if ($sensor_value != -1 && pts_module::is_file('logs/' . phodevi::sensor_object_identifier($sensor)))
		{
			pts_module::save_file('logs/' . phodevi::sensor_object_identifier($sensor), $sensor_value, true);
		}
	}

	private static function parse_monitor_log($log_file, $start_offset = 0, $end_offset = -1)
	{
		$log_f = pts_module::read_file($log_file);
		$line_breaks = explode(PHP_EOL, $log_f);
		$results = array();

		for($i = 0; $i < $start_offset && isset($line_breaks[$i]); $i++)
		{
			unset($line_breaks[$i]);
		}

		foreach($line_breaks as $line_number => $line)
		{
			if ($end_offset != -1 && $line_number >= $end_offset)
			{
				break;
			}

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
		//TODO needs complete rewrite

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

	// parse JSON file containing parameters of monitored sensors
	private static function prepare_sensor_parameters()
	{
		if (!is_file(pts_module::read_variable('MONITOR_PARAM_FILE')))
		{
				return null;
		}

		$parameters = array();
		$sensor_param_file = pts_module::read_variable('MONITOR_PARAM_FILE');
		$json_array = json_decode(file_get_contents($sensor_param_file), true);

		//TODO add exception handling
		foreach ($json_array['sensors'] as $json_sensor)
		{
				foreach($json_sensor['instances'] as $instance => $json_parameters)
				{
						$parameters[$json_sensor['type']][$json_sensor['sensor']][$instance] = $json_parameters;
				}
		}

		return $parameters;
	}

	private static function cgroup_create($cgroup_name, $cgroup_controller)
	{
		//TODO if we allow custom cgroup names, we will have to add cgroup
		//name checking ("../../../etc" isn't a sane name)

		$sudo_cmd = PTS_CORE_STATIC_PATH . 'root-access.sh ';
		$cgroup_path = '/sys/fs/cgroup/' . $cgroup_controller . '/' . $cgroup_name;
		$return_val = null;

		if (!is_dir($cgroup_path))	// cgroup filesystem doesn't allow to create regular files anyway
		{
			$mkdir_cmd = 'mkdir ' . $cgroup_path;
			$return_val = exec($sudo_cmd . $mkdir_cmd);
		}
		if ($return_val === null && is_dir($cgroup_path))	// mkdir produced no output
		{
			$current_user = exec('whoami');
			$chmod_cmd = 'chown ' . $current_user . ' ' . $cgroup_path . '/tasks';
			exec($sudo_cmd . $chmod_cmd);
		}
	}

	private static function cgroup_remove($cgroup_name, $cgroup_controller)
	{
		$sudo_cmd = PTS_CORE_STATIC_PATH . 'root-access.sh ';
		$cgroup_path = '/sys/fs/cgroup/' . $cgroup_controller . '/' . $cgroup_name;

		if (!is_dir($cgroup_path))	// cgroup filesystem doesn't allow to create regular files anyway
		{
			$rmdir_cmd = 'rmdir ' . $cgroup_path;
			shell_exec($sudo_cmd . $rmdir_cmd);
		}

		//TODO should probably return some result
	}

	private static function save_try_offset()
	{
		foreach (self::$to_monitor as $sensor)
		{
			$log_f = pts_module::read_file('logs/' . phodevi::sensor_object_identifier($sensor));
			$offset = count(explode(PHP_EOL, $log_f));
			self::$test_run_tries_offsets[self::$test_run_try_number][phodevi::sensor_object_identifier($sensor)] = $offset;
		}

		self::$test_run_try_number++;
	}

}

?>
