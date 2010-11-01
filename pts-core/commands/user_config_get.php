<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2010, Phoronix Media
	Copyright (C) 2009 - 2010, Michael Larabel

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
	public static function run($r)
	{
		if(count($r) == 0)
		{
			echo "\nYou must specify the tag to read. Enter all to read all values.\n";
			echo "Example: phoronix-test-suite user-config-get CacheDirectory\n\n";
			return false;
		}

		$defined_constants = get_defined_constants(true);
		$value_found = false;
		echo "\n";

		foreach($defined_constants["user"] as $c_name => $c_value)
		{
			if(isset($c_name[10]) && substr($c_name, 0, 9) == "P_OPTION_")
			{
				if($r[0] == "all" || $r[0] == $c_value || $r[0] == basename($c_value))
				{
					echo $c_value . ": " . pts_config::read_user_config($c_value) . "\n";
					$value_found = true;
				}
			}
		}

		if(!$value_found)
		{
			echo "No such options found in the user configuration file.\n";
		}

		echo "\n";
	}
}

?>
