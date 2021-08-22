<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2021, Phoronix Media
	Copyright (C) 2009 - 2021, Michael Larabel

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
	const INSTANT_MEASUREMENT = false;

	const PROC_STAT_IDLE_COL = 3;		//CPU idle time - it's the third number in the line (starting from 0)

	private $cpu_to_monitor;

	function __construct($instance, $parameter)
	{
		parent::__construct($instance, $parameter);

		if($parameter === NULL)
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
		if($parameter === null || in_array($parameter, self::get_supported_devices() ) )
		{
			return true;
		}

		return false;
	}
	public function get_readable_device_name()
	{
		if($this->cpu_to_monitor === "summary")
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
		if(phodevi::is_linux())
		{
			$cpu_list = shell_exec("cat /proc/stat | grep cpu | awk '{print $1}'");
			$cpu_array = explode("\n", $cpu_list);
			$supported = array_slice($cpu_array, 1, count($cpu_array) - 2);
			array_push($supported, 'summary');
			return $supported;
		}

		// Currently per-CPU monitoring is supported on Linux only.
		return null;
	}
	public function read_sensor()
	{
		// Determine current percentage for core usage
		// Default core to read is the first one (number 0)
		if(phodevi::is_linux() || phodevi::is_bsd())
		{
			$percent = $this->cpu_usage_linux_bsd();
		}
		else if(phodevi::is_solaris())
		{
			$percent = $this->cpu_usage_solaris();
		}
		else if(phodevi::is_macos())
		{
			$percent = $this->cpu_usage_macosx();
		}
		else if(phodevi::is_windows())
		{
			$percent = phodevi_windows_parser::get_wmi_object('Win32_processor', 'LoadPercentage');
		}

		if(!isset($percent) || !is_numeric($percent) || $percent < 0 || $percent > 100)
		{
			$percent = -1;
		}

		return pts_math::set_precision($percent, 2);
	}
	private function cpu_usage_linux_bsd()
	{
		$cpu_stat_first = null;
		$cpu_stat_second = null;
		if(is_file('/proc/stat'))
		{
			$cpu_stat_first = file_get_contents('/proc/stat');
			usleep(500000);
			$cpu_stat_second = file_get_contents('/proc/stat');

		}
		$start_load = self::cpu_load_array($cpu_stat_first);
		if($cpu_stat_second == null)
		{
			usleep(500000);
		}
		$end_load = self::cpu_load_array($cpu_stat_second);

		for($i = 0; $i < count($end_load); $i++)
		{
			$end_load[$i] -= $start_load[$i];
		}

		$percent = (($sum = array_sum($end_load)) == 0 ? 0 : 100 - (($end_load[self::PROC_STAT_IDLE_COL] * 100) / $sum));
		return $percent;
	}
	private function cpu_usage_solaris()
	{
		//TODO test this on Solaris
		//TODO: Add support for monitoring load on a per-core basis (through mpstat maybe?)
		$info = explode(' ', pts_strings::trim_spaces(pts_arrays::last_element(explode("\n", trim(shell_exec('sar -u 1 1 2>&1'))))));
		$percent = $info[1] + $info[2];

		return $percent;
	}
	private function cpu_usage_macosx()
	{
		//TODO test this on OSX
		// CPU usage for user
		$top = shell_exec('top -n 1 -l 1 2>&1');
		$usage = substr($top, strpos($top, 'CPU usage: ') + 11);
		$percent = substr($usage, 0, strpos($usage, '%'));

		return $percent;
	}
	private function cpu_load_array($override_stat = null)
	{
		// CPU load array
		$load = array();

		if(phodevi::is_linux() && is_file('/proc/stat'))
		{
			$stat = $override_stat != null ? $override_stat : file_get_contents('/proc/stat');

			if($this->cpu_to_monitor === 'summary')
			{
				$start_line = 0;
			}
			else if(($l = strpos($stat, $this->cpu_to_monitor)) !== false)
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
		else if(phodevi::is_bsd())
		{
			$load = explode(' ', phodevi_bsd_parser::read_sysctl('kern.cp_time'));
		}

		return $load;
	}
}

?>
