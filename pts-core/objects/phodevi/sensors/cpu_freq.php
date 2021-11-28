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

class cpu_freq extends phodevi_sensor
{
	const SENSOR_TYPE = 'cpu';
	const SENSOR_SENSES = 'freq';
	const SENSOR_UNIT = 'Megahertz';

	private $cpu_to_monitor = 'cpu0';


	function __construct($instance, $parameter)
	{
		parent::__construct($instance, $parameter);

		if($parameter !== NULL)
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
		if(self::get_supported_devices() == null)
		{
			return NULL;
		}

		return strtoupper($this->cpu_to_monitor);
	}
	public static function get_supported_devices()
	{
		if(phodevi::is_linux())
		{
			$cpu_list = shell_exec("cat /proc/stat | grep cpu | awk '{print $1}'");
			$cpu_array = explode("\n", $cpu_list);
			$supported = array_slice($cpu_array, 1, count($cpu_array) - 2);

			return $supported;
		}

		// Per-CPU frequency monitoring is currently supported on Linux only.
		return NULL;
	}
	public function read_sensor()
	{
		// Determine the current processor frequency
		$frequency = 0;

		if(phodevi::is_linux())
		{
			$frequency = $this->cpu_freq_linux();
		}
		else if(phodevi::is_solaris())
		{
			$frequency = $this->cpu_freq_solaris();
		}
		else if(phodevi::is_bsd())
		{
			$frequency = $this->cpu_freq_bsd();
		}
		else if(phodevi::is_macos())
		{
			$frequency = $this->cpu_freq_macosx();
		}
		else if(phodevi::is_windows())
		{
			return false;
		}

		return pts_math::set_precision($frequency, 2);
	}
	private function cpu_freq_linux()
	{
		$frequency = -1;

		// First, the ideal way, with modern CPUs using CnQ or EIST and cpuinfo reporting the current frequency.
		if(is_file('/sys/devices/system/cpu/' . $this->cpu_to_monitor . '/cpufreq/scaling_cur_freq'))
		{
			$frequency = pts_file_io::file_get_contents('/sys/devices/system/cpu/' . $this->cpu_to_monitor . '/cpufreq/scaling_cur_freq');
			$frequency = intval($frequency) / 1000;
		}
		else if(is_file('/proc/cpuinfo')) // fall back for those without cpufreq
		{
			$cpu_speeds = phodevi_linux_parser::read_cpuinfo('cpu MHz');
			$cpu_number = intval(substr($this->cpu_to_monitor, 3) ); //cut 'cpu' from the beginning

			if(!isset($cpu_speeds[$cpu_number]))
			{
				return -1;
			}

			$frequency = intval($cpu_speeds[$cpu_number]);
		}

		return $frequency;
	}
	private function cpu_freq_solaris()
	{
		//TODO test this one on Solaris
		$info = shell_exec('psrinfo -v | grep MHz');
		$line = substr($info, strrpos($info, 'at') + 3);
		$freq = trim(substr($line, 0, strpos($line, 'MHz')));

		return $freq;
	}
	private function cpu_freq_bsd()
	{
		//TODO test on BSD and implement per-core monitoring
		$freq = phodevi_bsd_parser::read_sysctl('dev.cpu.0.freq');
		if(is_numeric($freq) && $freq != '')
			return $freq;
	}
	private function cpu_freq_macosx()
	{
		$info = phodevi_osx_parser::read_osx_system_profiler('SPHardwareDataType', 'ProcessorSpeed', false, array(), false);

		$frequency = 0;
		if(($cut_point = strpos($info, ' ')) > 0)
		{
			$cut_str = substr($info, 0, $cut_point);
			$frequency = str_replace(',', '.', $cut_str);
		}

		if($frequency < 100)
		{
			$frequency *= 1000;
		}

		return pts_math::set_precision($frequency, 2);
	}
}

?>
