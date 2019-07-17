<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2017 - 2019, Phoronix Media
	Copyright (C) 2017 - 2019, Michael Larabel

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

class auto_load_module implements pts_option_interface
{
	const doc_section = 'Modules';
	const doc_description = 'This option can be used for easily adding a module to the AutoLoadModules list in the Phoronix Test Suite user configuration file. That list controls what PTS modules are automatically loaded on start-up of the Phoronix Test Suite.';

	public static function run($r)
	{
		$loaded_modules = pts_strings::comma_explode(pts_config::read_user_config('PhoronixTestSuite/Options/Modules/AutoLoadModules', null));
		$available_modules = pts_module_manager::list_available_modules();
		echo PHP_EOL . 'Currently Loaded Modules: ' . PHP_EOL;
		echo pts_user_io::display_text_list($loaded_modules);
		echo PHP_EOL . 'Available Modules: ' . PHP_EOL;
		echo pts_user_io::display_text_list($available_modules);


		if(count($r) == 0)
		{
			echo PHP_EOL . 'You must specify a valid module from the list to load.' . PHP_EOL;
			echo 'Example: phoronix-test-suite auto-load-module update_checker' . PHP_EOL;
			return false;
		}

		foreach($r as $module_to_load)
		{
			if(!in_array($module_to_load, $available_modules))
			{
				echo PHP_EOL . 'Module Not Available: ' . $module_to_load . PHP_EOL;
			}
			else if(in_array($module_to_load, $loaded_modules))
			{
				echo PHP_EOL . 'Module Already Loaded: ' . $module_to_load . PHP_EOL;
			}
			else
			{
				echo PHP_EOL . 'Module To Load: ' . $module_to_load . PHP_EOL;
				array_push($loaded_modules, $module_to_load);
			}
		}

		$new_options = array('PhoronixTestSuite/Options/Modules/AutoLoadModules' => implode(', ', $loaded_modules));
		pts_config::user_config_generate($new_options);
		echo PHP_EOL . 'New user configuration file written.' . PHP_EOL . PHP_EOL;
	}
}

?>
