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
	public static function required_function_sets()
	{
		return array("install");
	}
	public static function run($r)
	{
		$r = array_map("strtolower", $r);

		if(empty($r[0]))
		{
			echo "\nThe test or suite name to install external dependencies for must be supplied.\n";
		}
		else
		{
			pts_install_package_on_distribution($r[0]);
		}
	}
}

?>
