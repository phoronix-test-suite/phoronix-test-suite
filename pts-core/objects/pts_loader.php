<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010, Phoronix Media
	Copyright (C) 2010, Michael Larabel
	pts_loader.php: A generic class containing functions to load various pts-core components

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

class pts_loader
{
	public static function load_run_option($option)
	{
		if(is_file(COMMAND_OPTIONS_DIR . $option . ".php"))
		{
			if(!class_exists($option, false))
			{
				include(COMMAND_OPTIONS_DIR . $option . ".php");
			}

			if(method_exists($option, "required_function_sets"))
			{
				$required_function_sets = call_user_func(array($option, "required_function_sets"));

				foreach($required_function_sets as $to_load)
				{
					pts_loader::load_function_set($to_load);
				}
			}
		}
	}
	public static function load_function_set($title)
	{
		$includes_file = PTS_LIBRARY_PATH . "pts-includes-" . $title . ".php";
		$functions_file = PTS_LIBRARY_PATH . "pts-functions_" . $title . ".php";

		return (is_file($includes_file) && include_once($includes_file)) || (is_file($functions_file) && include_once($functions_file));
	}
	public static function load_definitions($definition_file)
	{
		static $loaded_definition_files = null;

		if(isset($loaded_definition_files[$definition_file]))
		{
			return true;
		}

		$loaded_definition_files[$definition_file] = true;
		$definition_file = PTS_CORE_PATH . "definitions/" . $definition_file;

		if(!is_file($definition_file))
		{
			return false;
		}

		$xml_reader = new tandem_XmlReader($definition_file);
		$definitions_names = $xml_reader->getXMLArrayValues("PhoronixTestSuite/Definitions/Define/Name");
		$definitions_values = $xml_reader->getXMLArrayValues("PhoronixTestSuite/Definitions/Define/Value");

		for($i = 0; $i < count($definitions_names); $i++)
		{
			define($definitions_names[$i], $definitions_values[$i]);
		}

		return true;
	}
}

?>
