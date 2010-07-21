<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel

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

class list_possible_dependencies implements pts_option_interface
{
	public static function required_function_sets()
	{
		return array("install_dependencies");
	}
	public static function run($r)
	{
		pts_client::$display->generic_heading(count(pts_external_dependency_generic_packages()) . " External Dependencies Available");
		$dependencies = array_map("pts_external_dependency_generic_title", pts_external_dependency_generic_packages());
		sort($dependencies);

		echo pts_user_io::display_text_list($dependencies);
		echo "\n";
	}
}

?>
