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

class swap_usage extends phodevi_sensor
{
	const SENSOR_TYPE = 'swap';
	const SENSOR_SENSES = 'usage';
	const SENSOR_UNIT = 'Megabytes';

	private static $page_size = -1;

	public function read_sensor()
	{
		$swap_usage = -1;

		if(phodevi::is_linux())
		{
			$swap_usage = self::swap_usage_linux();
		}

		return $swap_usage;
	}
	private function swap_usage_linux()
	{
		$proc_meminfo = explode("\n", file_get_contents('/proc/meminfo'));
		$mem = array();

		foreach($proc_meminfo as $mem_line)
		{
			$line_split = preg_split('/\s+/', $mem_line);

			if(count($line_split) == 3)
			{
				$mem[$line_split[0]] = intval($line_split[1]);
			}
		}

		$used_mem = $mem['SwapTotal:'] - $mem['SwapFree:'];

		return pts_math::set_precision($used_mem / 1024, 0);
	}
}

?>
