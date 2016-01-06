<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2016, Phoronix Media
	Copyright (C) 2009 - 2016, Michael Larabel

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

class dump_possible_options implements pts_option_interface
{
	public static function run($r)
	{
		$options = array();

		foreach(pts_file_io::glob(PTS_COMMAND_PATH . '*.php') as $option_php)
		{
			$name = str_replace('_', '-', basename($option_php, '.php'));

			if(!in_array(pts_strings::first_in_string($name, '-'), array('dump', 'debug', 'task')))
			{
				$options[] = $name;
			}
		}

		$is_true = isset($r[0]) && $r[0] == 'TRUE';
		echo implode($is_true ? ' ' : PHP_EOL, $options) . ($is_true ? null : PHP_EOL);
	}
}

?>
