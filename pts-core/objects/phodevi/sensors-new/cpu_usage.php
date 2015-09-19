<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2011, Phoronix Media
	Copyright (C) 2009 - 2011, Michael Larabel

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

class cpu_usage extends phodevi_sensor
{
	const SENSOR_TYPE = 'cpu';
	const SENSOR_SENSES = 'usage';
	const SENSOR_UNIT = 'Percent';
	const PRIMARY_PARAM_NAME = 'cpu_number';

	const PROC_STAT_IDLE_COL = 3;		//CPU idle time - it's the third number in the line (starting from 0)

	private $cpu_to_monitor;

	function __construct($instance, $parameter)
	{
		parent::__construct($instance, $parameter);

		if ($parameter === NULL)
		{
			$this->cpu_to_monitor = "summary";
		}
		else
		{
			$this->cpu_to_monitor = $parameter;
		}
	}

	public static function parameter_check($parameter)
	{
		if ($parameter === null || in_array($parameter, self::get_supported_devices() ) )
		{
			return true;
		}

		return false;
	}

	public function get_readable_device_name()
	{
		if ($this->cpu_to_monitor === "summary")
		{
			return 'Summary';
		}
		else
		{
			return strtoupper($this->cpu_to_monitor);
		}
	}

	public static function get_supported_devices()
	{
		$cpu_list = shell_exec("cat /proc/stat | grep cpu | awk '{print $1}'");
		$cpu_array = explode("\n", $cpu_list);

		$supported = array_slice($cpu_array, 1, count($cpu_array) - 2);
		array_push($supported, 'summary');

		return $supported;
	}

	public function read_sensor()
	{
		// Determine current percentage for core usage
		// Default core to read is the first one (number 0)
		if(phodevi::is_linux() || phodevi::is_bsd())
		{
			$start_load = self::cpu_load_array($this->cpu_to_monitor);
			//sleep(1);
			usleep(500000);
			$end_load = self::cpu_load_array($this->cpu_to_monitor);

			for($i = 0; $i < count($end_load); $i++)
			{
				$end_load[$i] -= $start_load[$i];
			}

			$percent = (($sum = array_sum($end_load)) == 0 ? 0 : 100 - (($end_load[self::PROC_STAT_IDLE_COL] * 100) / $sum));
		}
		else
		{
			$percent = null;
		}

		if(!is_numeric($percent) || $percent < 0 || $percent > 100)
		{
			$percent = -1;
		}

		return pts_math::set_precision($percent, 2);
	}
	private function cpu_load_array()
	{
		// CPU load array
		$load = array();

		if(phodevi::is_linux() && is_file('/proc/stat'))
		{
			$stat = file_get_contents('/proc/stat');

			if ($this->cpu_to_monitor === "summary")
			{
				$start_line = 0;
			}
			elseif(($l = strpos($stat, $this->cpu_to_monitor)) !== false)
			{
				$start_line = $l;
			}
			else
			{
				return -1;
			}

			$stat_line = substr($stat, $start_line, strpos($stat, "\n"));
			$stat_break = preg_split('/\s+/', $stat_line);

			for($i = 1; $i < 10; $i++)
			{
				array_push($load, $stat_break[$i]);
			}
		}

		return $load;
	}
}

?>
