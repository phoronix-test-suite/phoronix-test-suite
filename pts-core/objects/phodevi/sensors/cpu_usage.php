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

class cpu_usage implements phodevi_sensor
{
	public static function get_type()
	{
		return 'cpu';
	}
	public static function get_sensor()
	{
		return 'usage';
	}
	public static function get_unit()
	{
		return 'Percent';
	}
	public static function support_check()
	{
		$test = self::read_sensor();
		return is_numeric($test) && $test != -1;
	}
	public static function read_sensor()
	{
		// Determine current percentage for processor usage
		if(phodevi::is_linux() || phodevi::is_bsd())
		{
			$start_load = self::cpu_load_array(-1);
			sleep(1);
			$end_load = self::cpu_load_array(-1);
	
			for($i = 0; $i < count($end_load); $i++)
			{
				$end_load[$i] -= $start_load[$i];
			}

			$percent = (($sum = array_sum($end_load)) == 0 ? 0 : 100 - (($end_load[(count($end_load) - 1)] * 100) / $sum));
		}
		else if(phodevi::is_solaris())
		{
			// TODO: Add support for monitoring load on a per-core basis (through mpstat maybe?)
			$info = explode(' ', pts_strings::trim_spaces(pts_arrays::last_element(explode("\n", trim(shell_exec('sar -u 1 1 2>&1'))))));
			$percent = $info[1] + $info[2];
		}
		else if(phodevi::is_macosx())
		{
			// CPU usage for user
			$top = shell_exec('top -n 1 -l 1 2>&1');
			$top = substr($top, strpos($top, 'CPU usage: ') + 11);
			$percent = substr($top, 0, strpos($top, '%'));
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
	private static function cpu_load_array($read_core = -1)
	{
		// CPU load array
		$load = array();

		if(phodevi::is_linux() && is_file('/proc/stat'))
		{
			$stat = file_get_contents('/proc/stat');

			if($read_core > -1 && ($l = strpos($stat, 'cpu' . $read_core)) !== false)
			{
				$start_line = $l;
			}
			else
			{
				$start_line = 0;
			}

			$stat = substr($stat, $start_line, strpos($stat, "\n"));
			$stat_break = explode(' ', $stat);

			for($i = 1; $i < 6; $i++)
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
