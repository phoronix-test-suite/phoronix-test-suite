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

class memory_usage extends phodevi_sensor
{
	const SENSOR_TYPE = 'memory';
	const SENSOR_SENSES = 'usage';
	const SENSOR_UNIT = 'Megabytes';

	private static $page_size = -1;

	public function read_sensor()
	{
		return $this->mem_usage();
	}
	private function mem_usage()
	{
		if(phodevi::is_linux())
		{
			return self::mem_usage_linux();
		}
		else if(phodevi::is_macos())
		{
			return self::mem_usage_bsd('MEMORY', 'USED');
		}
		else if(phodevi::is_windows())
		{
			$ps = trim(shell_exec('powershell -NoProfile "((Get-WmiObject Win32_OperatingSystem).TotalVisibleMemorySize - (Get-WmiObject Win32_OperatingSystem).FreePhysicalMemory)"'));
			return round($ps / 1024);
		}
	}
	private function mem_usage_linux()
	{
		$proc_meminfo = explode("\n", file_get_contents('/proc/meminfo'));
		$mem = array();

		foreach ($proc_meminfo as $mem_line)
		{
			$line_split = preg_split('/\s+/', $mem_line);

			if(count($line_split) == 3)
			{
				$mem[$line_split[0]] = intval($line_split[1]);
			}
		}

		$used_mem = $mem['MemTotal:'] - $mem['MemFree:'] - $mem['Buffers:']
				- $mem['Cached:'] - $mem['Slab:'];

		return pts_math::set_precision($used_mem / 1024, 0);
	}
	private function mem_usage_bsd($TYPE = 'TOTAL', $READ = 'USED')
	{
		$vmstats = explode("\n", shell_exec('vm_stat 2>&1'));
		// buffers_and_cache
		foreach($vmstats as $vmstat_line)
		{
			$line_parts = pts_strings::colon_explode($vmstat_line);

			if(self::$page_size == -1)
			{
				strtok($vmstat_line, ':');
				$tok = strtok(' ');
				while (self::$page_size == -1)
				{
					if(is_numeric($tok))
					{
						self::$page_size = $tok;
					} 
					else
					{
						$tok = strtok(' ');
					}
				}
				continue;
			}
			//$line_parts[1] = pts_strings::trim_spaces($line_parts[1]);
			$line_type = strtok($vmstat_line, ':');
			$line_value = strtok(' .');
			if($TYPE == 'MEMORY')
			{
				if($line_type == 'Pages active' && $READ == 'USED')
				{
					$mem_usage = $line_value / (1048576 / self::$page_size);
					break;
				}
				if($line_type == 'Pages free' && $READ == 'FREE')
				{
					$mem_usage = $line_value / (1048576 / self::$page_size);
					break;
				}
			}
		}
		return pts_math::set_precision($mem_usage);
	}
}

?>
