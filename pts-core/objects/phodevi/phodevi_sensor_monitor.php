<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2016, Phoronix Media
	Copyright (C) 2008 - 2016, Michael Larabel

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
			$this->sensors_to_monitor = $to_monitor;
			$this->sensor_storage_dir = $recover_dir;
		}
		else
		{
			$this->sensor_storage_dir = pts_client::create_temporary_directory('sensors');

			$monitor_all = in_array('all', $to_monitor);
			$this->sensors_to_monitor = array();
			foreach(phodevi::query_sensors() as $sensor)
			{
				if($monitor_all || in_array(phodevi::sensor_identifier($sensor), $to_monitor) || in_array('all.' . $sensor[0], $to_monitor))
				{
					array_push($this->sensors_to_monitor, $sensor);
					file_put_contents($this->sensor_storage_dir . phodevi::sensor_identifier($sensor), null);
				}
			}
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
				if(in_array(phodevi::sensor_identifier($sensor), $match) || in_array('all.' . $sensor[0], $match))
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

		foreach($this->sensors_to_monitor as $sensor)
		{
			$sensor_value = phodevi::read_sensor($sensor);

			if($sensor_value != -1 && is_file($this->sensor_storage_dir . phodevi::sensor_identifier($sensor)))
			{
				file_put_contents($this->sensor_storage_dir . phodevi::sensor_identifier($sensor), $sensor_value . PHP_EOL,  FILE_APPEND);
			}
		}
	}
	private function read_sensor_data($sensor, $offset = 0)
	{
		$log_f = file_get_contents($this->sensor_storage_dir . phodevi::sensor_identifier($sensor));
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
	public function read_sensor_results($sensor, $offset = 0)
	{
		$results = $this->read_sensor_data($sensor, $offset);

		if(empty($results))
		{
			return false;
		}

		return array('id' => phodevi::sensor_identifier($sensor), 'name' => phodevi::sensor_name($sensor), 'results' => $results, 'unit' => phodevi::read_sensor_unit($sensor));
	}
}

?>
