<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2014, Phoronix Media
	Copyright (C) 2008 - 2014, Michael Larabel

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

class phodevi_sensor_monitor
{
	private $sensors_to_monitor;
	private $sensor_storage_dir;

	public function __construct($to_monitor, $recover_dir = false)
	{
		if($recover_dir != false && is_dir($recover_dir) && is_array($to_monitor))
		{
			$this->process_sensor_list($to_monitor);
			$this->sensor_storage_dir = $recover_dir;
		}
		else
		{
			$this->sensor_storage_dir = pts_client::create_temporary_directory('sensors');
			$this->sensors_to_monitor = array();
			$to_monitor['all'] = array();

			$this->process_sensor_list($to_monitor);
		}
	}
	public function details()
	{
		return array($this->sensors_to_monitor, $this->sensor_storage_dir);
	}
	public function sensors_logging($match = null)
	{
		if($match == null || $match == 'all')
		{
			return $this->sensors_to_monitor;
		}
		else
		{
			$share = array();
			$match = explode(',', $match);
			foreach($this->sensors_to_monitor as $sensor)
			{
				if(in_array(phodevi::sensor_object_identifier($sensor), $match) || in_array('all.' . $sensor[0], $match))
				{
					array_push($share, $sensor);
				}
			}

			return $share;
		}
	}
	public function sensor_logging_start()
	{
		$this->sensor_logging_update();
		pts_client::timed_function(array($this, 'sensor_logging_update'), array(), 1, array($this, 'sensor_logging_continue'), array());
	}
	public function sensor_logging_stop()
	{
		file_put_contents($this->sensor_storage_dir . 'STOP', 'STOP');
	}
	public function cleanup()
	{
		pts_file_io::delete($this->sensor_storage_dir, null, true);
	}
	public function sensor_logging_continue()
	{
		return !is_file($this->sensor_storage_dir . 'STOP');
	}
	public function sensor_logging_update()
	{
		if(!$this->sensor_logging_continue())
		{
			return false;
		}

		foreach($this->sensors_to_monitor as &$sensor)
		{
			$sensor_value = phodevi::read_sensor($sensor);

			if($sensor_value != -1 && is_file($this->sensor_storage_dir . phodevi::sensor_object_identifier($sensor)))
			{
				file_put_contents($this->sensor_storage_dir . phodevi::sensor_object_identifier($sensor), $sensor_value . PHP_EOL,  FILE_APPEND);
			}
		}
	}
	private function read_sensor_data(&$sensor, $offset = 0)
	{
		$log_f = file_get_contents($this->sensor_storage_dir . phodevi::sensor_object_identifier($sensor));
		$lines = explode(PHP_EOL, $log_f);

		if($offset != 0)
		{
			$lines = array_slice($lines, $offset);
		}

		foreach($lines as $i => $line)
		{
			if(!is_numeric($line) || $line < 0)
			{
				unset($lines[$i]);
			}
		}

		return array_values($lines);
	}
	public function read_sensor_results(&$sensor, $offset = 0)
	{
		$results = $this->read_sensor_data($sensor, $offset);

		if(empty($results))
		{
			return false;
		}

		return array('id' => phodevi::sensor_object_identifier($sensor), 'name' => phodevi::sensor_object_name($sensor), 'results' => $results, 'unit' => phodevi::read_sensor_object_unit($sensor));
	}

	// Create sensor objects basing on the sensor parameter array.
	private function process_sensor_list(&$sensor_parameters)
	{
		$monitor_all = array_key_exists('all', $sensor_parameters);
		foreach (phodevi::supported_sensors() as $sensor)
		{
			// instantiate sensor class if:
			// a) we want to monitor all the available sensors,
			// b) we want to monitor all the available sensors of the specified type,
			// c) sensor type and name was passed in an environmental variable

			// ($sensor[0] is the type, $sensor[1] is the name, $sensor[2] is the class name)

			$sensor_type_exists = array_key_exists($sensor[0], $sensor_parameters);
			$sensor_name_exists = $sensor_type_exists && array_key_exists($sensor[1], $sensor_parameters[$sensor[0]]);
			$monitor_all_of_this_type = $sensor_type_exists && array_key_exists('all', $sensor_parameters[$sensor[0]]);
			$monitor_all_of_this_sensor = $sensor_type_exists && $sensor_name_exists
					&& in_array('all', $sensor_parameters[$sensor[0]][$sensor[1]]);
			$is_cgroup_sensor = $sensor[0] === 'cgroup';

			if (($monitor_all && !$is_cgroup_sensor) || $monitor_all_of_this_type || $sensor_name_exists )
			{
				// in some cases we want to create objects representing every possible device supported by the sensor
				$create_all = $monitor_all || $monitor_all_of_this_type || $monitor_all_of_this_sensor;
				$this->create_sensor_instances($sensor, $sensor_parameters, $create_all);
			}
		}

		if (count($this->sensors_to_monitor) == 0)
		{
			throw new Exception('nothing to monitor');
		}
	}

	private function create_sensor_instances(&$sensor, &$sensor_parameters, $create_all)
	{
		if ($create_all)
		{
			$this->create_all_sensor_instances($sensor);
			return;
		}

		$sensor_instances = $sensor_parameters[$sensor[0]][$sensor[1]];

		// If no instances specified, create one with default parameters.
		if (empty($sensor_instances) )
		{
			$this->create_single_sensor_instance($sensor, 0, NULL);
			return;
		}
		// Create objects for all specified instances of the sensor.
		foreach ($sensor_instances as $instance => $param)
		{
			$this->create_single_sensor_instance($sensor, $instance, $param);
		}
	}

	// Create instances for all of the devices supported by specified sensor.
	private function create_all_sensor_instances(&$sensor)
	{
		$supported_devices = call_user_func(array($sensor[2], 'get_supported_devices'));
		$instance_no = 0;

		if ($supported_devices === NULL)
		{
			$this->create_single_sensor_instance($sensor, 0, NULL);
			return;
		}

		foreach ($supported_devices as $device)
		{
			$this->create_single_sensor_instance($sensor, $instance_no++, $device);
		}
	}

	// Create sensor object if parameters passed to it are correct.
	private function create_single_sensor_instance($sensor, $instance, $param)
	{
		if (call_user_func(array($sensor[2], 'parameter_check'), $param) === true)
		{
			$sensor_object = new $sensor[2]($instance, $param);
			array_push($this->sensors_to_monitor, $sensor_object);
			file_put_contents($this->sensor_storage_dir . phodevi::sensor_object_identifier($sensor_object), null);
		}
	}
}

?>
