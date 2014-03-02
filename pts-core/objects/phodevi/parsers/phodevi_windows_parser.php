<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2011, Phoronix Media
	Copyright (C) 2009 - 2011, Michael Larabel
	phodevi_windows_parser.php: General parsing functions specific to the Windows OS

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

class phodevi_windows_parser
{
	public static function read_cpuz($section, $name, $match_multiple = false)
	{
		$return = $match_multiple ? array() : false;

		if(is_executable('C:\Program Files\CPUID\CPU-Z\cpuz.exe'))
		{
			static $cpuz_log = null;

			if($cpuz_log == null)
			{
				shell_exec('"C:\Program Files\CPUID\CPU-Z\cpuz.exe" -txt=' . PTS_USER_PATH . 'cpuz');

				if(is_file(PTS_USER_PATH . 'cpuz.txt'))
				{
					$cpuz_log = file_get_contents(PTS_USER_PATH . 'cpuz.txt');
					unlink(PTS_USER_PATH . 'cpuz.txt');
				}
			}

			$s = 0;

			while(($match_multiple || $s == 0) && isset($cpuz_log[($s + 1)]) && ($s = strpos($cpuz_log, "\n" . $section, ($s + 1))) !== false)
			{
				$cpuz_section = substr($cpuz_log, $s);

				if(($name != null && ($c = strpos($cpuz_section, '	' . $name)) !== false) || ($c = 0) == 0)
				{
					if($name == null)
					{
						$name = $section;
					}

					$cpuz_section = substr($cpuz_section, $c, (strpos($cpuz_section, "\r\n", $c) - $c));
					$return_match = substr($cpuz_section, strpos($cpuz_section, $name) + strlen($name));

					if(($e = strpos($return_match, '(')) !== false)
					{
						$return_match = substr($return_match, 0, $e);
					}

					$return_match = trim($return_match);

					if($match_multiple)
					{
						array_push($return, $return_match);
					}
					else
					{
						$return = $return_match;
					}
				}
			}
		}

		return $return;
	}
}

?>
