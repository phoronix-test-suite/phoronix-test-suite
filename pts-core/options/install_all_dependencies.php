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

class install_all_dependencies implements pts_option_interface
{
	public static function run($r)
	{
		pts_load_function_set("install");

		$packages_to_install = array();
		pts_package_generic_to_distro_name($packages_to_install, "all");

		if(empty($packages_to_install) || count($packages_to_install) == 0)
		{
			echo pts_string_header("No packages found. Your operating system may not support this feature.\nSeek support from http://www.phoronix-test-suite.com/.");
		}
		else
		{
			pts_install_packages_on_distribution_process($packages_to_install);
		}
	}
}

?>
