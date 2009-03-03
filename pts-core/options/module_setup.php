<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel

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

class module_setup implements pts_option_interface
{
	public static function run($r)
	{
		$module = strtolower($r[0]);

		if(pts_is_php_module($module))
		{
			$pre_message = "";

			if(!in_array($module, pts_attached_modules()) && !class_exists($module))
			{
				pts_load_module($module);
			}

			$module_name = pts_php_module_call($module, "module_name");
			$module_description = pts_php_module_call($module, "module_description");
			$module_setup = pts_php_module_call($module, "module_setup");

			echo pts_string_header("Module: " . $module_name);
			echo $module_description . "\n";

			if(count($module_setup) == 0)
			{
				echo "\nThere are no options available for configuring with the " . $module . " module.\n";
			}
			else
			{
				$set_options = array();
				foreach($module_setup as $module_option)
				{
					if($module_option instanceOf pts_module_option))
					{
						do
						{
							echo "\n" . $module_option->get_formatted_question();
							$input = trim(fgets(STDIN));
						}
						while(!$module_option->is_supported_value($input));

						if(empty($input))
						{
							$input = $module_option->get_default_value();
						}

						$this_input_identifier = $module_option->get_identifier();

						$set_options[$module . "__" . $this_input_identifier] = $input;
					}
				}
				pts_module_config_init($set_options);
			}

			echo "\n";
		}
		else
		{
			echo "\n" . $module . " is not a recognized or configurable module.\n";
		}
	}
}

?>
