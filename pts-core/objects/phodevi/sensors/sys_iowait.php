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

class sys_iowait extends phodevi_sensor
{
	const SENSOR_TYPE = 'sys';
	const SENSOR_SENSES = 'iowait';
	const SENSOR_UNIT = 'Percent';
	const INSTANT_MEASUREMENT = false;

	public function read_sensor()
	{
		$iowait = -1;

		if(phodevi::is_linux())
		{
			$iowait = $this->sys_iowait_linux();
		}

		return $iowait;
	}
	private function sys_iowait_linux()
	{
		$iowait = -1;

		if(is_file('/proc/stat'))
		{
			$start_stat = pts_file_io::file_get_contents('/proc/stat');
			sleep(1);
			$end_stat = pts_file_io::file_get_contents('/proc/stat');

			$start_stat = explode(' ', substr($start_stat, 0, strpos($start_stat, "\n")));
			$end_stat = explode(' ', substr($end_stat, 0, strpos($end_stat, "\n")));

			for($i = 2, $diff_cpu_total = 0; $i < 9; $i++)
			{
				$diff_cpu_total += $end_stat[$i] - $start_stat[$i];
			}

			$diff_iowait = $end_stat[6] - $start_stat[6];

			$iowait = pts_math::set_precision(1000 * $diff_iowait / $diff_cpu_total / 10, 2);
		}

		return $iowait;
	}
}

?>
