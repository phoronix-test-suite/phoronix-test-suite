<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2024, Phoronix Media
	Copyright (C) 2008 - 2024, Michael Larabel
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
	const module_version = '4.0.0';
	const module_description = 'This module contains sensor monitoring support.';
	const module_author = 'Michael Larabel';

	private static $result_identifier = null;
	private static $to_monitor = array();
	private static $monitor_pids = array();
	private static $monitor_test_count = 0;
	private static $individual_test_run_request = null;
	private static $successful_test_run_request = null;
	private static $cgroup_name = 'pts_monitor';			// default name for monitoring cgroup
	private static $cgroup_enabled_controllers = array();
	private static $sensor_monitoring_frequency = 1;
	private static $perf_per_sensor_collection;
	private static $perf_per_sensor = false;
	public static $module_store_vars = array('MONITOR', 'PERFORMANCE_PER_WATT');

	public static function module_environment_variables()
	{
		return array('MONITOR', 'PERFORMANCE_PER_WATT', 'PERFORMANCE_PER_SENSOR', 'MONITOR_INTERVAL');
	}
	public static function module_info()
	{
		$info = null;
		$info .= PHP_EOL . 'Monitoring these sensors is as easy as running your normal Phoronix Test Suite commands but at the beginning of the command add: MONITOR=<selected sensors>.  For example, this will monitor the CPU temperature and voltage during tests: '
            . PHP_EOL . PHP_EOL . '    MONITOR=cpu.temp,cpu.voltage phoronix-test-suite benchmark universe'
            . PHP_EOL . PHP_EOL . 'For some of the sensors there is an ability to monitor specific device, e.g. cpu.usage.cpu0 or hdd.read-speed.sda. If the PERFORMANCE_PER_WATT environment variable is set, a performance per Watt graph will also be added, assuming the system\'s power consumption can be monitored. PERFORMANCE_PER_SENSOR= will allow similar behavior but for arbitrary sensors. Below are all of the sensors supported by this version of the Phoronix Test Suite.' 
            . PHP_EOL . PHP_EOL . 'Supported Options:' . PHP_EOL . PHP_EOL;

		foreach(self::monitor_arguments() as $arg)
		{
			$info .= '  - ' . $arg . PHP_EOL;
		}

        $info .= PHP_EOL . 'NOTE: Use the "phoronix-test-suite system-sensors" command to see what sensors are available for monitoring on the system.' . PHP_EOL;

		return $info;
	}

	//
	// General Functions
	//

	public static function __run_manager_setup(&$test_run_manager)
	{
		$test_run_manager->force_results_save();
		//$test_run_manager->disable_dynamic_run_count();
	}
	public static function __pre_run_process(&$test_run_manager)
	{
		self::$result_identifier = $test_run_manager->get_results_identifier();
		self::$to_monitor = array();

		try
		{
			$sensor_parameters = self::prepare_sensor_parameters();
			self::enable_perf_per_sensor($sensor_parameters);
			self::process_sensor_list($sensor_parameters);
			self::create_monitoring_cgroups();
			self::print_monitored_sensors();
			self::set_monitoring_interval();
		}
		catch(Exception $e)
		{
			echo PHP_EOL . 'Unloading system monitor: ' . $e->getMessage();
			sleep(5);
			return pts_module::MODULE_UNLOAD;
		}

		pts_env::set('FORCE_MIN_DURATION_PER_TEST', 1); // force each test to run at least one minute to ensure sufficient samples
	}
	public static function __pre_test_run($test_run_request)
	{
		self::$individual_test_run_request = clone $test_run_request;
		self::pts_start_monitoring();
		self::pause_monitoring();
	}
	public static function __calling_test_script($test_run_request)
	{
		self::chop_paused_monitoring_data();
	}
	public static function __interim_test_run()
	{
		self::pause_monitoring();
	}
	protected static function pause_monitoring()
	{
		$module_dir = pts_module::save_dir();
		foreach(self::$to_monitor as $sensor)
		{
			pts_module::save_file('logs/' . phodevi::sensor_object_identifier($sensor), 'pause', true);
		}
	}
	protected static function chop_paused_monitoring_data()
	{
		// Unpause (chop of excess) monitoring
		foreach(pts_file_io::glob(pts_module::save_dir() . 'logs/*') as $log_file)
		{
			$log_contents = file_get_contents($log_file);
			if(($x = strpos($log_file, 'pause')) !== false)
			{
				$log_contents = substr($log_contents, 0, $x);
				file_put_contents($log_file, $log_contents);
			}
		}
	}
	public static function __test_running($test_process)
	{
		// Put the tested application into proper cgroups as soon as it starts.
		foreach(self::$cgroup_enabled_controllers as $controller)
		{
			$proc_status = proc_get_status($test_process);
			$parent_pid = $proc_status['pid'];
			file_put_contents('/sys/fs/cgroup/' . $controller . '/' . self::$cgroup_name .'/tasks', $parent_pid);
		}
	}
	public static function __post_test_run_success($test_run_request)
	{
		self::$successful_test_run_request = clone $test_run_request;
	}
	public static function __post_test_run()
	{
		self::pts_stop_monitoring();
		self::chop_paused_monitoring_data();
	}
	public static function __post_test_run_process(&$result_file)
	{
		if(!empty(self::$perf_per_sensor))
		{
			self::process_perf_per_sensor($result_file);
		}

		if(self::$successful_test_run_request)
		{
			foreach(self::$to_monitor as $sensor)
			{
				self::process_test_run_results($sensor, $result_file);
			}
		}

		self::$successful_test_run_request = null;
		self::$individual_test_run_request = null;
		self::$monitor_test_count++;

		// Let the system rest before jumping to next test...
		sleep((self::$sensor_monitoring_frequency * 6));
	}
	public static function __event_results_process(&$test_run_manager)
	{
		//self::process_perf_per_sensor_collection($test_run_manager, true);
		echo PHP_EOL . 'Finishing System Sensor Monitoring Process' . PHP_EOL;
		echo PHP_EOL;
		//self::pts_stop_monitoring();
	}
	public static function __post_run_process()
	{
		foreach(self::$cgroup_enabled_controllers as $controller)
		{
			self::cgroup_remove(self::$cgroup_name, $controller);
		}
	}
	private static function pts_start_monitoring()
	{
		$instant_sensors = array();

		foreach(self::$to_monitor as $sensor)
		{
			pts_module::save_file('logs/' . phodevi::sensor_object_identifier($sensor));
			$is_instant = $sensor->is_instant();

			if($is_instant === false)
			{
				$pid = pts_module::pts_timed_function('pts_monitor_update', self::$sensor_monitoring_frequency, array(array($sensor)));
				self::$monitor_pids[] = $pid;
			}
			else
			{
				$instant_sensors[] = $sensor;
			}
		}

		if(!empty($instant_sensors))
		{
			$pid = pts_module::pts_timed_function('pts_monitor_update', self::$sensor_monitoring_frequency, array($instant_sensors));
			self::$monitor_pids[] = $pid;
		}
	}
	private static function pts_stop_monitoring()
	{
		foreach(self::$monitor_pids as $pid)
		{
			if(function_exists('posix_kill') && defined('SIGTERM'))
			{
				posix_kill($pid, SIGTERM);
			}
			else if(pts_client::executable_in_path('kill'))
			{
				shell_exec('kill ' . $pid . ' > /dev/null 2>&1');
			}
			else
			{
				// TODO XXX
				continue;
			}

			if(function_exists('pcntl_waitpid'))
			{
				pcntl_waitpid($pid, $status);
			}
		}
		self::$monitor_pids = array();
	}

	// Reads value of a single sensor, checks its correctness and saves it to the monitor log.
	public static function pts_monitor_update($sensor_list)
	{
		foreach($sensor_list as $sensor)
		{
			$sensor_value = phodevi::read_sensor($sensor);

			if($sensor_value != -1) //  && pts_module::is_file('logs/' . phodevi::sensor_object_identifier($sensor))
			{
				pts_module::save_file('logs/' . phodevi::sensor_object_identifier($sensor), $sensor_value, true);
			}
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
			if($end_offset != -1 && $line_number >= $end_offset)
			{
				break;
			}

			$line = trim($line);

			if(!empty($line) && $line >= 0 && is_numeric($line))
			{
				$results[] = $line;
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
			$supported_devices = call_user_func(array($sensor[2], 'get_supported_devices'));

			if(!in_array('all.' . $sensor[0], $args))
			{
				$args[] = 'all.' . $sensor[0];
			}

			$args[] = phodevi::sensor_identifier($sensor);

			if($supported_devices !== NULL)
			{
				$args[] = 'all.' . phodevi::sensor_identifier($sensor);
				foreach($supported_devices as $device)
				{
					$args[] = phodevi::sensor_identifier($sensor) . '.' . $device;
				}
			}

		}

		return $args;
	}

	// Parse environment variable containing parameters of monitored sensors.
	private static function prepare_sensor_parameters()
	{
		$sensor_list = pts_strings::comma_explode(pts_env::read('MONITOR'));

		$to_monitor = array();

		foreach($sensor_list as $sensor)
		{
			$sensor_split = pts_strings::trim_explode('.', $sensor);
			if(!isset($sensor_split[1]))
			{
				continue;
			}

			// Set 'all' from the beginning (eg. all.cpu.frequency) as the last
			// element (cpu.frequency.all). As sensor parameters are also supported
			// now, it's handy to mark that we want to include all sensors of specified
			// type (cpu.all) or just all supported parameters of specified sensor
			// (cpu.frequency.all).
			if($sensor_split[0] === 'all')
			{
				$sensor_split[] = 'all';
				array_shift($sensor_split);
			}

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

		return $to_monitor;
	}

	private static function enable_perf_per_sensor(&$sensor_parameters)
	{
		self::$perf_per_sensor = array();
		if(pts_env::read('PERFORMANCE_PER_WATT'))
		{
			// We need to ensure the system power consumption is being tracked to get performance-per-Watt
			self::$perf_per_sensor[] = array('sys', 'power');
			echo PHP_EOL . 'Provide Performance-Per-Watt Metrics Using: ' . pts_client::cli_just_italic('sys.power')  . PHP_EOL;
		}
		if(pts_env::read('PERFORMANCE_PER_SENSOR'))
		{
			// We need to ensure the system power consumption is being tracked to get performance-per-(arbitrary sensor)
			foreach(explode(',', pts_env::read('PERFORMANCE_PER_SENSOR')) as $s)
			{
				$per_sensor = explode('.', $s);
				if(count($per_sensor) == 2)
				{
					self::$perf_per_sensor[] = $per_sensor;
					echo PHP_EOL . 'Providing Performance-Per-Sensor Metrics Using: ' . pts_client::cli_just_italic($per_sensor[0] . '.' . $per_sensor[1]) . PHP_EOL;
				}
			}
		}

		if(empty(self::$perf_per_sensor))
		{
			return false;
		}

		foreach(self::$perf_per_sensor as $i => $s)
		{
			if(empty($sensor_parameters[$s[0]][$s[1]]))
			{
				$sensor_parameters[$s[0]][$s[1]] = array();
			}

			self::$perf_per_sensor_collection[$i] = array();
		}
	}

	// Create sensor objects basing on the sensor parameter array.
	private static function process_sensor_list(&$sensor_parameters)
	{
		$monitor_all = array_key_exists('all', $sensor_parameters);
		foreach(phodevi::query_sensors() as $sensor)
		{
			// instantiate sensor class if:
			// a) we want to monitor all the available sensors,
			// b) we want to monitor all the available sensors of the specified type,
			// c) sensor type and name was passed in an environment variable

			// ($sensor[0] is the type, $sensor[1] is the name, $sensor[2] is the class name)

			$sensor_type_exists = array_key_exists($sensor[0], $sensor_parameters);
			$sensor_name_exists = $sensor_type_exists && array_key_exists($sensor[1], $sensor_parameters[$sensor[0]]);
			$monitor_all_of_this_type = $sensor_type_exists && array_key_exists('all', $sensor_parameters[$sensor[0]]);
			$monitor_all_of_this_sensor = $sensor_type_exists && $sensor_name_exists && in_array('all', $sensor_parameters[$sensor[0]][$sensor[1]]);
			$is_cgroup_sensor = $sensor[0] === 'cgroup';

			if(($monitor_all && !$is_cgroup_sensor) || $monitor_all_of_this_type || $sensor_name_exists )
			{
				// in some cases we want to create objects representing every possible device supported by the sensor
				$create_all = $monitor_all || $monitor_all_of_this_type || $monitor_all_of_this_sensor;
				self::create_sensor_instances($sensor, $sensor_parameters, $create_all);
			}
		}

		if(count(self::$to_monitor) == 0)
		{
			throw new Exception('No Supported Sensors Selected To Monitor');
			sleep(2);
		}
	}
	private static function create_sensor_instances(&$sensor, &$sensor_parameters, $create_all)
	{
		if($create_all)
		{
			self::create_all_sensor_instances($sensor);
			return;
		}

		$sensor_instances = $sensor_parameters[$sensor[0]][$sensor[1]];

		// If no instances specified, create one with default parameters.
		if(empty($sensor_instances) )
		{
			self::create_single_sensor_instance($sensor, 0, NULL);
			return;
		}
		// Create objects for all specified instances of the sensor.
		foreach($sensor_instances as $instance => $param)
		{
			self::create_single_sensor_instance($sensor, $instance, $param);
		}
	}

	// Create instances for all of the devices supported by specified sensor.
	private static function create_all_sensor_instances(&$sensor)
	{
		$supported_devices = call_user_func(array($sensor[2], 'get_supported_devices'));
		$instance_no = 0;

		if($supported_devices === NULL)
		{
			self::create_single_sensor_instance($sensor, 0, NULL);
			return;
		}

		foreach($supported_devices as $device)
		{
			self::create_single_sensor_instance($sensor, $instance_no++, $device);
		}
	}

	// Create sensor object if parameters passed to it are correct.
	private static function create_single_sensor_instance($sensor, $instance, $param)
	{
		if($sensor[0] === 'cgroup')
		{
			$cgroup_controller = call_user_func(array($sensor[2], 'get_cgroup_controller'));
			pts_arrays::unique_push(self::$cgroup_enabled_controllers, $cgroup_controller );
			self::cgroup_create(self::$cgroup_name, $cgroup_controller);
			$param = self::$cgroup_name;
		}

		if(call_user_func(array($sensor[2], 'parameter_check'), $param) === true)
		{
			self::$to_monitor[] = new $sensor[2]($instance, $param);
		}
	}

	// Create cgroups in all of the needed controllers.
	private static function create_monitoring_cgroups()
	{
		foreach(self::$cgroup_enabled_controllers as $controller)
		{
			self::cgroup_create(self::$cgroup_name, $controller);
		}
	}

	private static function print_monitored_sensors()
	{
		echo PHP_EOL . 'Sensors To Be Logged:';
		foreach(self::$to_monitor as &$sensor)
		{
			echo PHP_EOL . '   - ' . phodevi::sensor_object_name($sensor);
		}
		echo PHP_EOL;

	}
	private static function set_monitoring_interval()
	{
		if(pts_env::read('MONITOR_INTERVAL') != null)
		{
			$proposed_interval = pts_env::read('MONITOR_INTERVAL');
			if(is_numeric($proposed_interval) && $proposed_interval >= 0.5)
			{
				self::$sensor_monitoring_frequency = $proposed_interval;
			}
		}
	}
	private static function cgroup_create($cgroup_name, $cgroup_controller)
	{
		//TODO if we allow custom cgroup names, we will have to add cgroup
		//name checking ("../../../etc" isn't a sane name)

		$sudo_cmd = PTS_CORE_STATIC_PATH . 'root-access.sh ';
		$cgroup_path = '/sys/fs/cgroup/' . $cgroup_controller . '/' . $cgroup_name;
		$return_val = null;

		if(!is_dir($cgroup_path))	// cgroup filesystem doesn't allow to create regular files anyway
		{
			$current_user = exec('whoami');
			$mkdir_cmd = 'mkdir ' . $cgroup_path;
			$chmod_cmd = 'chown ' . $current_user . ' ' . $cgroup_path . '/tasks';
			$command = $sudo_cmd . '"' . $mkdir_cmd . ' && ' . $chmod_cmd . '"';
			exec($command);
		}

		if(!is_writable($cgroup_path . '/tasks'))
		{
			throw new Exception('could not create cgroups');
		}

	}
	private static function cgroup_remove($cgroup_name, $cgroup_controller)
	{
		$sudo_cmd = PTS_CORE_STATIC_PATH . 'root-access.sh ';
		$cgroup_path = '/sys/fs/cgroup/' . $cgroup_controller . '/' . $cgroup_name;

		if(is_dir($cgroup_path))	// cgroup filesystem doesn't allow to create regular files anyway
		{
			$rmdir_cmd = 'rmdir ' . $cgroup_path;
			shell_exec($sudo_cmd . $rmdir_cmd);
		}

		//TODO should probably return some result
	}
	private static function process_perf_per_sensor(&$result_file)
	{
		foreach(self::$perf_per_sensor as $i => $s)
		{
			$sensor_results = self::parse_monitor_log('logs/' . phodevi::sensor_identifier($s));

			if(count($sensor_results) > 2 && self::$successful_test_run_request)
			{
				// Copy the value each time as if you are directly writing the original data, each succeeding time in the loop the used arguments gets borked
				$original_parent_hash = self::$successful_test_run_request->get_comparison_hash(true, false);
				$test_result = clone self::$successful_test_run_request;
				$unit = 'Watt';

				$res_average = pts_math::arithmetic_mean($sensor_results);
				switch(phodevi::read_sensor_unit($s))
				{
					case 'Milliwatts':
						$res_average = $res_average / 1000;
					case 'Watts':
						break;
					default:
						$unit = phodevi::read_sensor_unit($s);
				}

				if(!empty($unit) && $res_average > 0 && $test_result->test_profile->get_display_format() == 'BAR_GRAPH')
				{
					$test_result->test_profile->set_identifier(null);
					//$test_result->set_used_arguments_description(phodevi::sensor_name('sys.power') . ' Monitor');
					//$test_result->set_used_arguments(phodevi::sensor_name('sys.power') . ' ' . $test_result->get_arguments());
					$test_result->test_result_buffer = new pts_test_result_buffer();
					$test_result->set_parent_hash($original_parent_hash);

					if($test_result->test_profile->get_result_proportion() == 'HIB')
					{
						$test_result->test_profile->set_result_scale($test_result->test_profile->get_result_scale() . ' Per ' . $unit);
						$test_result->test_result_buffer->add_test_result(self::$result_identifier, round($test_result->active->get_result() / $res_average, 3));
						$ro = $result_file->add_result_return_object($test_result);
					}
					else if($test_result->test_profile->get_result_proportion() == 'LIB')
					{
						return; // with below code not rendering nicely
						$test_result->test_profile->set_result_proportion('HIB');
						$test_result->test_profile->set_result_scale('Performance Per ' . $unit);
						$test_result->test_result_buffer->add_test_result(self::$result_identifier, round((1 / $test_result->active->get_result()) / $res_average, 3));
						$ro = $result_file->add_result_return_object($test_result);
					}
					if($ro)
					{
						pts_client::$display->test_run_success_inline($ro);
					}
					self::$perf_per_sensor_collection[$i][] = $test_result->active->get_result();
				}
			}
		}
	}

	// Saves average of perf-per-watt results to the result file.
	private static function process_perf_per_sensor_collection(&$test_run_manager, $print_inline = false)
	{
		foreach(self::$perf_per_sensor as $i => $s)
		{
			if(is_array(self::$perf_per_sensor_collection[$i]) && count(self::$perf_per_sensor_collection[$i]) > 2)
			{
				// Performance per watt/sensor overall
				$unit = phodevi::read_sensor_unit($s);
				$avg = pts_math::geometric_mean(self::$perf_per_sensor_collection[$i]);
				$test_profile = new pts_test_profile();
				$test_result = new pts_test_result($test_profile);
				$test_result->test_profile->set_test_title('Meta Performance Per ' . $unit);
				$test_result->test_profile->set_identifier(null);
				$test_result->test_profile->set_version(null);
				$test_result->test_profile->set_result_proportion(null);
				$test_result->test_profile->set_display_format('BAR_GRAPH');
				$test_result->test_profile->set_result_scale('Performance Per ' . $unit);
				$test_result->test_profile->set_result_proportion('HIB');
				$test_result->set_used_arguments_description('Performance Per ' . $unit);
				$test_result->set_used_arguments('Per-Per-' . $unit);
				$test_result->test_result_buffer = new pts_test_result_buffer();
				$test_result->test_result_buffer->add_test_result(self::$result_identifier, pts_math::set_precision($avg, 4));
				$test_run_manager->result_file->add_result($test_result);
				if($print_inline && $test_result)
				{
					pts_client::$display->test_run_success_inline($test_result);
				}
			}
		}
	}
	private static function process_test_run_results(&$sensor, &$result_file)
	{
		$result_buffer = new pts_test_result_buffer();
		$sensor_results = self::parse_monitor_log('logs/' . phodevi::sensor_object_identifier($sensor));

		if(count($sensor_results) > 6)
		{
			$result_buffer->add_test_result(self::$result_identifier, implode(',', $sensor_results), implode(',', $sensor_results));
		}

		if($result_buffer->get_count() == 0)
		{
			return;
		}

		self::write_test_run_results($result_buffer, $result_file, $sensor);
	}
	private static function write_test_run_results(&$result_buffer, &$result_file, &$sensor)
	{
		// TODO result count checks should probably be done before cloning the test_result
		// Copy the value each time as if you are directly writing the original data, each succeeding time in the loop the used arguments gets borked
		if(!is_object(self::$individual_test_run_request))
			return;

		$test_result = clone self::$individual_test_run_request;
		$test_result->set_parent_hash(self::$successful_test_run_request->get_comparison_hash(true, false));

		if(pts_module_manager::is_module_attached('matisk'))
		{
			// TODO find some better way than adding a number to distinguish the results between the MATISK runs
			$arguments_description = phodevi::sensor_object_name($sensor) . ' Monitor (test ' . count($result_file->get_systems()) . ')';
			$arguments_try_description = phodevi::sensor_object_name($sensor) . ' Per Test Try Monitor (test ' . count($result_file->get_systems()) . ')';
		}
		else
		{
			$arguments_description = phodevi::sensor_object_name($sensor) . ' Monitor';
			$arguments_try_description = phodevi::sensor_object_name($sensor) . ' Per Test Try Monitor';
		}

		$scale = phodevi::read_sensor_object_unit($sensor);
		$test_result->test_profile->set_identifier(null);
		$test_result->test_profile->set_result_proportion(stripos($scale, 'hertz') === false && stripos($scale, 'hz') === false ? 'LIB' : 'HIB');
		$test_result->test_profile->set_display_format('LINE_GRAPH');
		$test_result->test_profile->set_result_scale($scale);
		$test_result->set_used_arguments_description($arguments_description);
		$test_result->set_used_arguments(phodevi::sensor_object_name($sensor) . ' ' . $test_result->get_arguments());
		$test_result->test_result_buffer = $result_buffer;

		$ro = $result_file->add_result_return_object($test_result);
		if($ro)
		{
			pts_client::$display->test_run_success_inline($ro);
		}
	}
	// Generates summary result (covering all test runs) for specified sensor and adds it to the result file.
	private static function process_summary_results(&$sensor, &$test_run_manager)
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
			$test_result->set_used_arguments(phodevi::sensor_object_identifier($sensor));
			$test_result->test_result_buffer = new pts_test_result_buffer();
			$test_result->test_result_buffer->add_test_result(self::$result_identifier, implode(',', $sensor_results), implode(',', $sensor_results), implode(',', $sensor_results), implode(',', $sensor_results));
			$ro = $test_run_manager->result_file->add_result_return_object($test_result);
			if($ro)
			{
				pts_client::$display->test_run_success_inline($ro);
			}
		}
	}
}

?>
