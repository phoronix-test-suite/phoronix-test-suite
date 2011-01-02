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

class user_config_get implements pts_option_interface
{
	const doc_section = 'Other';
	const doc_description = 'This option can be used for reading an XML value of the Phoronix Test Suite user configuration file.';

	public static function run($r)
	{
		if(count($r) == 0)
		{
			echo PHP_EOL . 'You must specify the tag to read. Enter all to read all values.' . PHP_EOL;
			echo PHP_EOL . 'Example: phoronix-test-suite user-config-get CacheDirectory' . PHP_EOL . PHP_EOL;
			return false;
		}

		$defined_constants = get_defined_constants(true);
		$value_found = false;
		echo PHP_EOL;

		foreach($defined_constants['user'] as $c_name => $c_value)
		{
			if(isset($c_name[10]) && substr($c_name, 0, 9) == 'P_OPTION_')
			{
				if($r[0] == 'all' || $r[0] == $c_value || $r[0] == basename($c_value))
				{
					echo $c_value . ': ' . pts_config::read_user_config($c_value) . PHP_EOL;
					$value_found = true;
				}
			}
		}

		if(!$value_found)
		{
			echo 'No such options found in the user configuration file.' . PHP_EOL;
		}

		echo PHP_EOL;
	}
}

?>
