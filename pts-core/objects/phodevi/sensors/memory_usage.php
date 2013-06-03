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

class memory_usage implements phodevi_sensor
{
	private static $page_size = -1;

	public static function get_type()
	{
		return 'memory';
	}
	public static function get_sensor()
	{
		return 'usage';
	}
	public static function get_unit()
	{
		return 'Megabytes';
	}
	public static function support_check()
	{
		$test = self::read_sensor();
		return is_numeric($test) && $test != -1;
	}
	public static function read_sensor()
	{
		return memory_usage::mem_usage('MEMORY', 'USED');
	}
	public static function mem_usage($TYPE = 'TOTAL', $READ = 'USED')
	{
		// Reads system memory usage
		$mem_usage = -1;

		if(pts_client::executable_in_path('free') != false)
		{
			$mem = explode("\n", shell_exec('free -t -m 2>&1'));
			$grab_line = null;
			$buffers_and_cache = 0;

			for($i = 0; $i < count($mem); $i++)
			{
				$line_parts = pts_strings::colon_explode($mem[$i]);

				if(count($line_parts) == 2)
				{
					$line_type = $line_parts[0];

					if($TYPE == 'MEMORY' && $line_type == 'Mem')
					{
						$grab_line = $line_parts[1];
					}
					else if($TYPE == 'SWAP' && $line_type == 'Swap')
					{
						$grab_line = $line_parts[1];
					}
					else if($TYPE == 'TOTAL' && $line_type == 'Total')
					{
						$grab_line = $line_parts[1];
					}
					else if($line_type == '-/+ buffers/cache' && $TYPE != 'SWAP')
					{
						$buffers_and_cache = pts_arrays::first_element(explode(' ', pts_strings::trim_spaces($line_parts[1])));						
					}
				}
			}

			if(!empty($grab_line))
			{
				$grab_line = pts_strings::trim_spaces($grab_line);
				$mem_parts = explode(' ', $grab_line);

				if($READ == 'USED')
				{
					if(count($mem_parts) >= 2 && is_numeric($mem_parts[1]))
					{
						$mem_usage = $mem_parts[1] - $buffers_and_cache;
					}
				}
				else if($READ == 'TOTAL')
				{
					if(count($mem_parts) >= 1 && is_numeric($mem_parts[0]))
					{
						$mem_usage = $mem_parts[0];
					}
				}
				else if($READ == 'FREE')
				{
					if(count($mem_parts) >= 3 && is_numeric($mem_parts[2]))
					{
						$mem_usage = $mem_parts[2];
					}
				}
			}
		}
		else if(pts_client::executable_in_path('vm_stat') != false)
		{
			$vmstats = explode("\n", shell_exec('vm_stat 2>&1'));
			$grab_line = null;
			// buffers_and_cache
			foreach($vmstats as $vmstat_line)
			{
				$line_parts = pts_strings::colon_explode($vmstat_line);

				if(self::$page_size == -1)
				{
					strtok($vmstat_line, ':');
					$tok = strtok(' ');
					while(self::$page_size == -1)
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
			$mem_usage = pts_math::set_precision($mem_usage);
		}

		return $mem_usage;
	}
}

?>
