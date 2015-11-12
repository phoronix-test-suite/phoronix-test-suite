<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2015, Phoronix Media
	Copyright (C) 2009 - 2015, Michael Larabel

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

class cgroup_cpu_usage extends phodevi_sensor
{
	const SENSOR_TYPE = 'cgroup';
	const SENSOR_SENSES = 'cpu-usage';
	const SENSOR_UNIT = 'Percent';
	const INSTANT_MEASUREMENT = false;

	const proc_stat_column_count = 10;
	const cgroup_cpuacct_stat_load_column = 1;
	const cgroup_cpuacct_stat_line_count = 2;

	private $cgroup_stat_path;

	function __construct($instance, $cgroup_name)
	{
		parent::__construct($instance, $cgroup_name);

		$this->cgroup_stat_path = '/sys/fs/cgroup/cpu,cpuacct/' . $cgroup_name . '/cpuacct.stat' ;
	}
	public static function parameter_check($cgroup_name)
	{
		if($cgroup_name === null)
		{
			return false;
		}

		$cgroup_stat_path = '/sys/fs/cgroup/cpu,cpuacct/' . $cgroup_name . '/cpuacct.stat' ;

		if(phodevi::is_linux() && is_readable($cgroup_stat_path))
		{
			return true;
		}

		return false;
	}
	public function get_readable_device_name()
	{
		return 'Summary';
	}
	public static function get_cgroup_controller()
	{
		return 'cpu,cpuacct';
	}
	public function support_check()
	{
		return phodevi::is_linux() && is_dir('/sys/fs/cgroup/cpu,cpuacct/');
	}
	public function read_sensor()
	{
		$start_sys_jiffies = self::cpu_jiffies_count();
		$start_cgroup_jiffies = self::cgroup_cpu_jiffies_count();

		usleep(500000);

		$end_sys_jiffies = self::cpu_jiffies_count();
		$end_cgroup_jiffies = self::cgroup_cpu_jiffies_count();

		$diff_sys_jiffies = $end_sys_jiffies - $start_sys_jiffies;
		$diff_cgroup_jiffies = $end_cgroup_jiffies - $start_cgroup_jiffies;

		$percent = ($diff_sys_jiffies == 0 ? 0 : ($diff_cgroup_jiffies / $diff_sys_jiffies) * 100 );

		if(!is_numeric($percent) || $percent < 0 || $percent > 100)
		{
			$percent = -1;
		}

		return pts_math::set_precision($percent, 2);
	}
	private function cpu_jiffies_count()
	{
		// CPU load array
		$load = array();

		if(is_readable('/proc/stat'))
		{
			$stat = file_get_contents('/proc/stat');
			$start_line = 0;

			$stat = substr($stat, $start_line, strpos($stat, "\n"));
			$stat_break = preg_split('/\s+/', $stat);

			for($i = 1; $i < self::proc_stat_column_count; $i++)
			{
				array_push($load, $stat_break[$i]);
			}
		}

		return array_sum($load);
	}
	private function cgroup_cpu_jiffies_count()
	{
		$load = array();

		if(is_readable($this->cgroup_stat_path))
		{
			$stat = file_get_contents($this->cgroup_stat_path);

			foreach (explode(PHP_EOL, $stat) as $line )
			{
				if($line === '')
				{
					break;
				}

				$line_break = preg_split('/\s+/', $line);
				array_push($load, $line_break[self::cgroup_cpuacct_stat_load_column]);
			}
		}

		return array_sum($load);
	}
}

?>
