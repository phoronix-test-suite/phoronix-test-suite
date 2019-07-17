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

class unload_module implements pts_option_interface
{
	const doc_section = 'Modules';
	const doc_description = 'This option can be used for easily removing a module from the AutoLoadModules list in the Phoronix Test Suite user configuration file. That list controls what modules are automatically loaded on start-up of the Phoronix Test Suite.';

	public static function run($r)
	{
		$loaded_modules = pts_strings::comma_explode(pts_config::read_user_config('PhoronixTestSuite/Options/Modules/AutoLoadModules', null));
		echo PHP_EOL . 'Currently Loaded Modules: ' . PHP_EOL;
		echo pts_user_io::display_text_list($loaded_modules);


		if(count($r) == 0 || !in_array($r[0], $loaded_modules))
		{
			echo PHP_EOL . 'You must specify a valid module from the list to unload.' . PHP_EOL;
			echo 'Example: phoronix-test-suite unload-module update_checker' . PHP_EOL;
			return false;
		}

		foreach($r as $module_to_unload)
		{
			if(($x = array_search($module_to_unload, $loaded_modules)) !== false)
			{
				echo PHP_EOL . 'Unloading Module: ' . $module_to_unload . PHP_EOL;
				unset($loaded_modules[$x]);
			}
			else
			{
				echo PHP_EOL . 'UNKNOWN: ' . $module_to_unload . PHP_EOL;
			}
		}

		$new_options = array('PhoronixTestSuite/Options/Modules/AutoLoadModules' => implode(', ', $loaded_modules));
		pts_config::user_config_generate($new_options);
		echo PHP_EOL . 'New user configuration file written.' . PHP_EOL . PHP_EOL;
	}
}

?>
