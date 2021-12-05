<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2021, Phoronix Media
	Copyright (C) 2009 - 2021, Michael Larabel
	phodevi_osx_parser.php: General parsing functions specific to Mac OS X

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

class phodevi_osx_parser
{
	public static $cached_results = array();

	private static function run_command_to_lines_cached($command, $cache)
	{
		if(!$cache || !array_key_exists($command, self::$cached_results))
		{
			$info = shell_exec($command);
			if(!empty($info))
			{
				$info = trim($info);
				$info = explode("\n", $info);
			}
			self::$cached_results[$command] = $info;
		}

		return self::$cached_results[$command];
	}
	public static function read_osx_system_profiler($data_type, $object, $multiple_objects = false, $ignore_values = array(), $cache = true)
	{
		$value = ($multiple_objects ? array() : false);

		if(pts_client::executable_in_path('system_profiler'))
		{
			$lines = self::run_command_to_lines_cached('system_profiler ' . $data_type . ' 2>&1', $cache);

			if(empty($lines))
			{
				return false;
			}

			for($i = 0; $i < count($lines) && ($value == false || $multiple_objects); $i++)
			{
				$line = pts_strings::colon_explode($lines[$i]);

				if(isset($line[0]) == false)
				{
					continue;
				}

				$line_object = str_replace(' ', '', $line[0]);
		
				if(($cut_point = strpos($line_object, '(')) > 0)
				{
					$line_object = substr($line_object, 0, $cut_point);
				}
		
				if(strtolower($line_object) == strtolower($object) && isset($line[1]))
				{
					$this_value = trim($line[1]);
			
					if(!empty($this_value) && !in_array($this_value, $ignore_values))
					{
						if($multiple_objects)
						{
							array_push($value, $this_value);
						}
						else
						{
							$value = $this_value;
						}
					}
				}
			}
		}
	
		return $value;
	}
}

?>
