<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2020, Phoronix Media
	Copyright (C) 2009 - 2020, Michael Larabel

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
	public static $stop_manager = false;

	//
	// Module Handling
	//

	public static function load_module($module)
	{
		// Load the actual file needed that contains the module
		return (is_file(pts_module::module_path() . $module . '.php') && include_once(pts_module::module_path() . $module . '.php')) || (is_file(pts_module::module_local_path() . $module . '.php') && include_once(pts_module::module_local_path() . $module . '.php'));
	}
	public static function list_available_modules()
	{
		$modules = array();

		foreach(array_merge(pts_file_io::glob(pts_module::module_path() . '*.php'), pts_file_io::glob(pts_module::module_local_path() . '*.php')) as $module)
		{
			$modules[] = basename($module, '.php');
		}

		return $modules;
	}
	public static function modules_environment_variables()
	{
		$module_env_vars = array();
		foreach(pts_module_manager::available_modules() as $module)
		{
			pts_module_manager::load_module($module);
			$vars = pts_module_manager::module_call($module, 'module_environment_variables');

			if(is_array($vars))
			{
				foreach($vars as $var)
				{
					if(!isset($module_env_vars[$var]))
					{
						$module_env_vars[$var] = array($module);
					}
					else
					{
						$module_env_vars[$var][] = $module;
					}
				}
			}
		}

		return $module_env_vars;
	}
	public static function module_call($module, $process, &$object_pass = null)
	{
		if(!class_exists($module))
		{
			return false;
		}

		if(method_exists($module, $process))
		{
			$module_val = call_user_func_array(array($module, $process), array(&$object_pass));
		}
		else
		{
			$class_vars = get_class_vars($module);
			$module_val = isset($class_vars[$process]) ? $class_vars[$process] : false;

			if($module_val == null && defined($module . '::' . $process))
			{
				eval('$module_val = ' . $module . '::' . $process . ';');
			}
		}

		return $module_val;
	}
	public static function module_process($process, &$object_pass = null, $select_modules = false)
	{
		if(self::$stop_manager)
		{
			return;
		}
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
					exit(0);
			}
		}
		pts_module_manager::set_current_module(null);
	}
	public static function process_environment_variables_string_to_set($env_var_string)
	{
		if(!empty($env_var_string))
		{
			foreach(explode(';', $env_var_string) as $ev)
			{
				if(strpos($ev, '=') != false)
				{
					list($var, $value) = pts_strings::trim_explode('=', $ev);
					putenv($var . '=' . $value);
					if(pts_env::read($var) == false)
					{
						pts_env::set($var, $value);
					}
					pts_module_manager::var_store_add($var, $value);
				}
			}

			pts_module_manager::detect_modules_to_load();
		}
	}
	public static function run_command($module, $command, $arguments = null)
	{
		$all_options = pts_module_manager::module_call($module, 'user_commands');

		if(isset($all_options[$command]) && method_exists($module, $all_options[$command]))
		{
			pts_module_manager::module_call($module, $all_options[$command], $arguments);
		}
		else
		{
			// Not a valid command, list available options for the module
			// or help or list_options was called
			$all_options = pts_module_manager::module_call($module, 'user_commands');

			echo PHP_EOL . 'User commands for the ' . $module . ' module:' . PHP_EOL . PHP_EOL;

			foreach($all_options as $option => $func)
			{
				echo '- ' . $module . '.' . str_replace('_', '-', $option) . PHP_EOL;
			}
			echo PHP_EOL;
		}
	}
	public static function attach_module($module)
	{
		if(pts_module::is_module($module) == false || in_array($module, self::$modules))
		{
			return false;
		}

		pts_module_manager::load_module($module);

		self::$modules[] = $module;

		if(class_exists($module))
		{
			foreach(get_class_methods($module) as $module_method)
			{
				if(substr($module_method, 0, 2) == '__')
				{
					if(!isset(self::$module_process[$module_method]))
					{
						self::$module_process[$module_method] = array();
					}

					self::$module_process[$module_method][] = $module;
				}
			}
		}

		if(defined('PTS_STARTUP_TASK_PERFORMED'))
		{
			$pass_by_ref_null = null;
			pts_module_manager::module_process('__startup', $pass_by_ref_null, $module);
		}
	}
	public static function detach_module($module)
	{
		if(self::is_module_attached($module))
		{
			$key_to_unset = array_search($module, self::$modules);
			unset(self::$modules[$key_to_unset]); 

			if(class_exists($module))
			{
				foreach(get_class_methods($module) as $module_method)
				{
					if(substr($module_method, 0, 2) == '__' && isset(self::$module_process[$module_method]))
					{
						$key_to_unset = array_search($module, self::$module_process[$module_method]);
						if($key_to_unset !== false)
						{
							unset(self::$module_process[$module_method][$key_to_unset]);
						}
					}
				}
			}
		}
	}
	public static function detach_extra_modules($limit_modules_list)
	{
		$current_modules = pts_module_manager::attached_modules();
		if(!empty($current_modules) && $current_modules != $limit_modules_list)
		{
			foreach($current_modules as $cm)
			{
				if(empty($limit_modules_list) || !in_array($cm, $limit_modules_list))
				{
					pts_module_manager::detach_module($cm);
				}
			}
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
					$attached[] = $check_module;
				}
			}
		}

		return $attached;
	}
	public static function is_module_attached($module)
	{
		return in_array($module, self::$modules);
	}
	public static function available_modules($only_system_modules = false)
	{
		if($only_system_modules)
		{
			$modules = pts_file_io::glob(pts_module::module_path() . '*.php');
		}
		else
		{
			$modules = array_merge(pts_file_io::glob(pts_module::module_path() . '*.php'), pts_file_io::glob(pts_module::module_local_path() . '*.php'));
		}
		$module_names = array();

		foreach($modules as $module)
		{
			$module_names[] = basename($module, '.php');
		}

		asort($module_names);

		return $module_names;
	}
	public static function clean_module_list()
	{
		self::$modules = array_unique(self::$modules);

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
		$env_vars = pts_storage_object::read_from_file(PTS_CORE_STORAGE, 'environment_variables_for_modules');

		if($env_vars == false)
		{
			$env_vars = pts_module_manager::modules_environment_variables();
		}

		foreach($env_vars as $env_var => $modules)
		{
			if(($e = pts_env::read($env_var)) != false && !empty($e))
			{
				foreach($modules as $module)
				{
					if(!pts_module_manager::is_module_attached($module))
					{
						pts_module_manager::attach_module($module);
					}
				}
			}
		}
	}

	//
	// Variable Storage
	//

	public static function var_store_add($var, $value)
	{
		if(!in_array($var . '=' . $value, self::$var_storage))
		{
			self::$var_storage[] = $var . '=' . $value;
		}
	}
	public static function var_store_string()
	{
		return implode(';', self::$var_storage);
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
