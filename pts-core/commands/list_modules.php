<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2024, Phoronix Media
	Copyright (C) 2008 - 2024, Michael Larabel

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

class list_modules implements pts_option_interface
{
	const doc_section = 'Modules';
	const doc_description = 'This option will list all of the available Phoronix Test Suite modules on this system.';

	public static function run($r)
	{
		pts_client::$display->generic_heading(count(pts_module_manager::available_modules()) . ' Modules');
		$modules = array();
		foreach(pts_module_manager::available_modules() as $module)
		{
			pts_module_manager::load_module($module);
			$modules[] = array($module, pts_client::cli_just_bold(pts_module_manager::module_call($module, 'module_name')));
		}
		echo pts_user_io::display_text_table($modules) . PHP_EOL;
		echo PHP_EOL;
	}
}

?>
