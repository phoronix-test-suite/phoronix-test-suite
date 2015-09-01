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

class cpu_usage_per_core extends phodevi_sensor
{
	const SENSOR_TYPE = 'cpu';
	const SENSOR_SENSES = 'usage-per-core';
	const SENSOR_UNIT = 'Percent';

	const PROC_STAT_IDLE_COL = 3;		//CPU idle time - it's the third number in the line (starting from 0)
	const CPU_SUMMARY = -1;
	
	private $core_to_monitor;

	function __construct($instance, $parameter_array)
	{
		parent::__construct($instance, $parameter_array);
		$cpu_number = self::CPU_SUMMARY;

		if ($parameter_array != null && array_key_exists('core_number', $parameter_array))
		{
			$cpu_number = $parameter_array['core_number'];
		}
		//core number correctness check
		if ($cpu_number === "summary")
		{
			$this->core_to_monitor = self::CPU_SUMMARY;
		} 
		elseif ($cpu_number >= 0 || $cpu_number < phodevi_cpu::cpu_core_count())
		{
			$this->core_to_monitor = intval($cpu_number);
		}
	}
	
	public static function parameter_check($parameter_array)
	{
		if ($parameter_array === null)
		{
			return true;
		}
		
		if (is_array($parameter_array) && array_key_exists('core_number', $parameter_array))
		{
			$cpu_number = $parameter_array['core_number'];
			
			if ($cpu_number === "summary")
			{
				return true;
			}
			
			if (phodevi::is_linux())
			{
				$cpu_count = intval(shell_exec("nproc"));
				
				if (is_numeric($cpu_number) && $cpu_number >= 0 && $cpu_number < $cpu_count)
				{
					return true;
				}
			}
		}
		
		return false;
	}

	public function get_readable_params()
	{
		if ($this->core_to_monitor === self::CPU_SUMMARY)
		{
			return 'Summary';
		}
		else
		{
			return 'Core: ' . $this->core_to_monitor;
		}
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
			$start_load = self::cpu_load_array($this->core_to_monitor);
			sleep(1);
			$end_load = self::cpu_load_array($this->core_to_monitor);

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

			if($this->core_to_monitor > -1 && ($l = strpos($stat, 'cpu' . $this->core_to_monitor)) !== false)
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
