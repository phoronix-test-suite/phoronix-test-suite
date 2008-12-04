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

class install_dependencies implements pts_option_interface
{
	public static function run($r)
	{
		pts_load_function_set("install");

		if(empty($r[0]))
		{
			echo "\nThe test or suite name to install external dependencies for must be supplied.\n";
		}
		else
		{
			if($r[0] == "phoronix-test-suite" || $r[0] == "pts" || $r[0] == "trondheim-pts")
			{
				$pts_dependencies = array("php-gd", "php-extras", "build-utilities");
				$packages_to_install = array();
				$continue_install = pts_package_generic_to_distro_name($packages_to_install, $pts_dependencies);

				if($continue_install)
				{
					pts_install_packages_on_distribution_process($packages_to_install);
				}
			}
			else
			{
				pts_install_package_on_distribution($r[0]);
			}
		}
	}
}

?>
