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

class module_info implements pts_option_interface
{
	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, "pts_is_module", null, "No module found.")
		);
	}
	public static function run($args)
	{
		$module = strtolower($args[0]);

		if(is_file(($path = MODULE_DIR . $module . ".php")) || is_file(($path = MODULE_DIR . $module . ".sh"))) // TODO: come up with better way to avoid this
		{
			$module = new pts_user_module_details($path);
			echo $module->info_string();
			echo "\n";
		}
	}
}

?>
