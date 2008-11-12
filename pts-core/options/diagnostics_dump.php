<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel

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

class diagnostics_dump
{
	public static function run()
	{
		echo pts_string_header("Phoronix Test Suite v" . PTS_VERSION . " (" . PTS_CODENAME . ")\n" . "Diagnostics Dump");
		$pts_defined_constants = get_defined_constants(true);
		foreach($pts_defined_constants["user"] as $constant => $constant_value)
		{
			if(substr($constant, 0, 2) != "P_" && substr($constant, 0, 3) != "IS_")
			{
				echo $constant . " = " . $constant_value . "\n";
			}
		}

		echo "\nEnd-User Run-Time Variables:\n";
		foreach(pts_user_runtime_variables() as $var => $var_value)
		{
			echo $var . " = " . $var_value . "\n";
		}
		echo "\nEnvironmental Variables (accessible via test scripts):\n";
		foreach(pts_env_variables() as $var => $var_value)
		{
			echo $var . " = " . $var_value . "\n";
		}
		echo "\n";
	}
}

?>
