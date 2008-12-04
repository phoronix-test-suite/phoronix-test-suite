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

class install_all implements pts_option_interface
{
	public static function run($r)
	{
		pts_load_function_set("install");

		if(pts_read_assignment("COMMAND") == "force-install-all")
		{
			pts_set_assignment("PTS_FORCE_INSTALL", 1);
		}

		pts_module_process("__pre_install_process");
		foreach(pts_available_tests_array() as $test)
		{
			// Any external dependencies?
			pts_install_package_on_distribution($test);

			// Install tests
			pts_start_install($test);
		}
		pts_module_process("__post_install_process");
	}
}

?>
