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

class cpu_peak_freq extends phodevi_sensor
{
	const SENSOR_TYPE = 'cpu';
	const SENSOR_SENSES = 'peak-freq';
	const SENSOR_UNIT = 'Megahertz';


	function __construct($instance, $parameter)
	{
		parent::__construct($instance, $parameter);
	}
	public static function parameter_check($parameter)
	{
		return phodevi::is_linux();
	}
	public function support_check()
	{
		return phodevi::is_linux();
	}
	public function get_readable_device_name()
	{
		return 'Highest CPU Core Frequency';
	}
	public function read_sensor()
	{
		// Determine the current processor frequency
		$frequency = 0;

		if(phodevi::is_linux())
		{
			$frequency = $this->cpu_freq_linux();
		}

		return pts_math::set_precision($frequency, 0);
	}
	private function cpu_freq_linux()
	{
		$peak_frequency = -1;

		foreach(pts_file_io::glob('/sys/devices/system/cpu/*/cpufreq/scaling_cur_freq') as $scaling_cur_freq)
		{
			$scaling_cur_freq = pts_file_io::file_get_contents($scaling_cur_freq);
			if(is_numeric($scaling_cur_freq) && $scaling_cur_freq > $peak_frequency)
			{
				$peak_frequency = $scaling_cur_freq;
			}
		}

		if($peak_frequency > 1000)
		{
			$peak_frequency = round($peak_frequency / 1000);
		}

		return $peak_frequency;
	}
}

?>
