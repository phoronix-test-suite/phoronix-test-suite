<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2010, Phoronix Media
	Copyright (C) 2009 - 2010, Michael Larabel

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

class pts_module_manager
{
	private static $modules = array();
	private static $var_storage = array();
	private static $module_process = array();
	private static $current_module = null;

	//
	// Module Handling
	//

	public static function load_module($module)
	{
		// Load the actual file needed that contains the module
		if(is_file(PTS_CORE_PATH . "definitions/module-" . $module . ".xml"))
		{
			pts_loader::load_definitions("module-" . $module . ".xml");
		}

		return (is_file(MODULE_DIR . $module . ".php") && include_once(MODULE_DIR . $module . ".php")) || (is_file(MODULE_LOCAL_DIR . $module . ".php") && include_once(MODULE_LOCAL_DIR . $module . ".php"));
	}
	public static function module_call($module, $process, &$object_pass = null)
	{
		if(!class_exists($module))
		{
			return false;
		}

		if(method_exists($module, $process))
		{
			$module_val = call_user_func(array($module, $process), $object_pass);
		}
		else
		{
			eval("\$module_val = " . $module . "::" . $process . ";");
		}

		return $module_val;
	}
	public static function module_process($process, &$object_pass = null, $select_modules = false)
	{
		// Run a module process on all registered modules
		foreach(pts_module_manager::attached_modules($process, $select_modules) as $module)
		{
			pts_module_manager::set_current_module($module);

			$module_response = pts_module_manager::module_call($module, $process, $object_pass);

			switch($module_response)
			{
				case pts_module::MODULE_UNLOAD:
					// Unload the PTS module
					pts_module_manager::detach_module($module);
					break;
				case pts_module::QUIT_PTS_CLIENT:
					// Stop the Phoronix Test Suite immediately
					pts_client::exit_client();
					break;
			}
		}
		pts_module_manager::set_current_module(null);
	}
	public static function process_extensions_string($extensions)
	{
		// Process extensions for modules
		if(!empty($extensions))
		{
			foreach(explode(';', $extensions) as $ev)
			{
				list($var, $value) = pts_strings::trim_explode('=', $ev);
				pts_client::set_environmental_variable($var, $value);
				pts_module_maanager::var_store_add($var, $value);
			}

			pts_module_manager::detect_modules_to_load();
		}
	}
	public static function run_command($module, $command, $arguments = null)
	{
		$all_options = pts_module_manager::module_call($module, "user_commands");
	
		if(isset($all_options[$command]) && method_exists($module, $all_options[$command]))
		{
			pts_module_manager::module_call($module, $all_options[$command], $arguments);
		}
		else
		{
			// Not a valid command, list available options for the module 
			// or help or list_options was called
			$all_options = pts_module_manager::module_call($module, "user_commands");

			echo "\nUser commands for the " . $module . " module:\n\n";

			foreach($all_options as $option)
			{
				echo "- " . $module . "." . str_replace('_', '-', $option) . "\n";
			}
			echo "\n";
		}
	}
	public static function attach_module($module)
	{
		if(pts_module::is_module($module) == false || in_array($module, self::$modules))
		{
			return false;
		}

		pts_module_manager::load_module($module);

		array_push(self::$modules, $module);

		if(class_exists($module))
		{
			foreach(get_class_methods($module) as $module_method)
			{
				if(substr($module_method, 0, 2) == "__")
				{
					if(!isset(self::$module_process[$module_method]))
					{
						self::$module_process[$module_method] = array();
					}

					array_push(self::$module_process[$module_method], $module);
				}
			}
		}

		if(defined("PTS_STARTUP_TASK_PERFORMED"))
		{
			$pass_by_ref_null = null;
			pts_module_manager::module_process("__startup", $pass_by_ref_null, $module);
		}
	}
	public static function detach_module($module)
	{
		if(self::is_module_attached($module))
		{
			unset(self::$modules[$module]);
		}
	}
	public static function attached_modules($process_name = null, $select_modules = false)
	{
		if($process_name == null)
		{
			$attached = self::$modules;
		}
		else if(isset(self::$module_process[$process_name]))
		{
			$attached = self::$module_process[$process_name];
		}
		else
		{
			$attached = array();
		}

		if($select_modules != false)
		{
			$all_attached = $attached;
			$attached = array();

			foreach(pts_arrays::to_array($select_modules) as $check_module)
			{
				if(in_array($check_module, $all_attached))
				{
					array_push($attached, $check_module);
				}
			}
		}

		return $attached;
	}
	public static function is_module_attached($module)
	{
		return isset(self::$modules[$module]);
	}
	public static function available_modules()
	{
		$modules = array_merge(pts_file_io::glob(MODULE_DIR . "*.php"), pts_file_io::glob(MODULE_LOCAL_DIR . "*.php"));
		$module_names = array();

		foreach($modules as $module)
		{
			array_push($module_names, basename($module, ".php"));
		}

		asort($module_names);

		return $module_names;
	}
	public static function clean_module_list()
	{
		array_unique(self::$modules);

		foreach(self::$modules as $i => $module)
		{
			if(pts_module::is_module($module) == false)
			{
				unset(self::$modules[$i]);
			}
		}
	}

	public static function detect_modules_to_load()
	{
		// Auto detect modules to load
		foreach(explode("\n", pts_file_io::file_get_contents(STATIC_DIR . "lists/module-variables.list")) as $module_var)
		{
			$module_var = pts_strings::trim_explode("=", $module_var);

			if(count($module_var) == 2)
			{
				list($env_var, $module) = $module_var;

				if(!pts_module_manager::is_module_attached($module) && ($e = pts_client::read_env($env_var)) != false && !empty($e))
				{
					pts_module_manager::attach_module($module);
				}
			}
		}
	}

	//
	// Variable Storage
	//

	public static function var_store_add($var, $value)
	{
		if(!in_array($var . "=" . $value, self::$var_storage))
		{
			array_push(self::$var_storage, $var . "=" . $value);
		}
	}
	public static function var_store_string()
	{
		return implode(";", self::$var_storage);
	}

	//
	// Current Module
	//

	public static function set_current_module($module = null)
	{
		self::$current_module = $module;
	}
	public static function get_current_module()
	{
		return self::$current_module;
	}
}

?>
