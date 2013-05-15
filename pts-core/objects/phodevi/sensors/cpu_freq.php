<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2013, Phoronix Media
	Copyright (C) 2009 - 2013, Michael Larabel

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

class cpu_freq implements phodevi_sensor
{
	public static function get_type()
	{
		return 'cpu';
	}
	public static function get_sensor()
	{
		return 'freq';
	}
	public static function get_unit()
	{
		return 'Megahertz';
	}
	public static function support_check()
	{
		$test = self::read_sensor();
		return is_numeric($test) && $test != -1;
	}
	public static function read_sensor()
	{
		// Determine the current processor frequency
		$cpu_core = 0; // TODO: for now just monitoring the first core
		$info = 0;

		if(phodevi::is_linux())
		{
			// First, the ideal way, with modern CPUs using CnQ or EIST and cpuinfo reporting the current
			if(is_file('/sys/devices/system/cpu/cpu' . $cpu_core . '/cpufreq/scaling_cur_freq'))
			{
				$info = pts_file_io::file_get_contents('/sys/devices/system/cpu/cpu' . $cpu_core . '/cpufreq/scaling_cur_freq');
				$info = intval($info) / 1000;
			}
			else if(is_file('/proc/cpuinfo')) // fall back for those without cpufreq
			{
				$cpu_speeds = phodevi_linux_parser::read_cpuinfo('cpu MHz');

				if(isset($cpu_speeds[0]))
				{
					$cpu_core = (isset($cpu_speeds[$cpu_core]) ? $cpu_core : 0);
					$info = intval($cpu_speeds[$cpu_core]);
				}
			}
		}
		else if(phodevi::is_solaris())
		{
			$info = shell_exec('psrinfo -v | grep MHz');
			$info = substr($info, strrpos($info, 'at') + 3);
			$info = trim(substr($info, 0, strpos($info, 'MHz')));
		}
		else if(phodevi::is_bsd())
		{
			$info = phodevi_bsd_parser::read_sysctl('dev.cpu.0.freq');
		}
		else if(phodevi::is_macosx())
		{
			$info = phodevi_osx_parser::read_osx_system_profiler('SPHardwareDataType', 'ProcessorSpeed');
		
			if(($cut_point = strpos($info, ' ')) > 0)
			{
				$info = substr($info, 0, $cut_point);
				$info = str_replace(',', '.', $info);
			}
		}

		return pts_math::set_precision($info, 2);
	}
}

?>
