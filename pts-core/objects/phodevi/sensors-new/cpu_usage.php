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
	const CPU_SUMMARY = -1;

	private $cpu_to_monitor;

	function __construct($instance, $parameter_array)
	{
		parent::__construct($instance, $parameter_array);
		$cpu_number = self::CPU_SUMMARY;

		if ($parameter_array != null && array_key_exists(self::PRIMARY_PARAM_NAME, $parameter_array))
		{
			$cpu_number = $parameter_array['cpu_number'];
		}
		//core number correctness check
		if ($cpu_number === "summary")
		{
			$this->cpu_to_monitor = self::CPU_SUMMARY;
		}
		elseif ($cpu_number >= 0 || $cpu_number < phodevi_cpu::cpu_core_count())
		{
			$this->cpu_to_monitor = intval($cpu_number);
		}
	}

	public static function parameter_check($parameter_array)
	{
		if ($parameter_array === null)
		{
			return true;
		}

		if (is_array($parameter_array) && array_key_exists('cpu_number', $parameter_array))
		{
			$cpu_number = $parameter_array['cpu_number'];

			if (in_array($cpu_number, self::get_supported_devices() ) )
			{
				return true;
			}
		}

		return false;
	}

	public function get_readable_params()
	{
		if ($this->cpu_to_monitor === self::CPU_SUMMARY)
		{
			return 'Summary';
		}
		else
		{
			return 'CPU' . $this->cpu_to_monitor;
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

	public function support_check()
	{
		$test = $this->read_sensor();
		return is_numeric($test) && $test != -1;
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

			if($this->cpu_to_monitor > -1 && ($l = strpos($stat, 'cpu' . $this->cpu_to_monitor)) !== false)
			{
				$start_line = $l;
			}
			else
			{
				$start_line = 0;
			}

			$stat = substr($stat, $start_line, strpos($stat, "\n"));
			$stat_break = preg_split('/\s+/', $stat);

			for($i = 1; $i < 10; $i++)
			{
				array_push($load, $stat_break[$i]);
			}
		}

		return $load;
	}
}

?>
