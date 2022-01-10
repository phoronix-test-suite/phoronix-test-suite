<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2021, Phoronix Media
	Copyright (C) 2008 - 2021, Michael Larabel
	pts_module_interface.php: The generic Phoronix Test Suite module object that is extended by the specific modules/plug-ins

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

class pts_module
{
	const MODULE_UNLOAD = "MODULE_UNLOAD";
	const QUIT_PTS_CLIENT = "QUIT_PTS_CLIENT";

	public static function save_dir()
	{
		$prefix_dir = pts_module::module_data_path();
		pts_file_io::mkdir($prefix_dir);

		return $prefix_dir . str_replace('_', '-', self::module_name()) . '/';
	}
	public static function module_path()
	{
		return PTS_CORE_PATH . 'modules/';
	}
	public static function module_local_path()
	{
		return PTS_USER_PATH . 'modules/';
	}
	public static function module_data_path()
	{
		return PTS_USER_PATH . 'modules-data/';
	}
	public static function is_module($name)
	{
		return is_file(pts_module::module_local_path() . $name . '.php') || is_file(pts_module::module_path() . $name . '.php');
	}
	public static function module_config_save($module_name, $set_options)
	{
		// Validate the config files, update them (or write them) if needed, and other configuration file tasks
		pts_file_io::mkdir(pts_module::module_data_path() . $module_name);
		$existing_ini = is_file(pts_module::module_data_path() . $module_name . '/module-settings.ini') ? parse_ini_file(pts_module::module_data_path() . $module_name . '/module-settings.ini') : array();
		$ini_to_write = null;
		foreach(array_merge($existing_ini, $set_options) as $id => $v)
		{
			$ini_to_write .= $id . '=' . $v . PHP_EOL;
		}
		file_put_contents(pts_module::module_data_path() . $module_name . '/module-settings.ini', $ini_to_write);
	}
	public static function is_module_setup()
	{
		$module_name = self::module_name();
		$is_setup = true;

		$module_setup_options = pts_module_manager::module_call($module_name, "module_setup");

		foreach($module_setup_options as $option)
		{
			if($option instanceOf pts_module_option)
			{
				if(pts_module::read_option($option->get_identifier()) == false && $option->setup_check_needed())
				{
					$is_setup = false;
					break;
				}
			}
		}

		return $is_setup;
	}
	public static function valid_run_command($module, $command = null)
	{
		if($command == null)
		{
			if(strpos($module, '.') != false)
			{
				list($module, $command) = explode('.', $module);
			}
			else
			{
				$command = 'run';
			}
		}

		if(!pts_module_manager::is_module_attached($module))
		{
			pts_module_manager::attach_module($module);
		}

		$all_options = pts_module_manager::module_call($module, 'user_commands');
		$valid = !empty($all_options) && count($all_options) > 0 && ((isset($all_options[$command]) && method_exists($module, $all_options[$command])) || !empty($all_options));

		return $valid ? array($module, $command) : false;
	}
	public static function read_option($identifier, $default_fallback = false)
	{
		$module_name = self::module_name();
		$existing_ini = is_file(pts_module::module_data_path() . $module_name . '/module-settings.ini') ? parse_ini_file(pts_module::module_data_path() . $module_name . '/module-settings.ini') : array();
		$value = isset($existing_ini[$identifier]) ? $existing_ini[$identifier] : null;

		if($default_fallback && empty($value))
		{
			// Find the default value
			$module_options = call_user_func(array($module_name, "module_setup"));

			for($i = 0; $i < count($module_options) && $value == false; $i++)
			{
				if($module_options[$i]->get_identifier() == $identifier)
				{
					$value = $module_options[$i]->get_default_value();
				}
			}
		}

		return $value;
	}
	public static function set_option($identifier, $value)
	{
		pts_module::module_config_save(self::module_name(), array($identifier => $value));
	}
	public static function save_file($file, $contents = null, $append = false)
	{
		// Saves a file for a module

		$save_base_dir = self::save_dir();

		pts_file_io::mkdir($save_base_dir);

		if(($extra_dir = dirname($file)) != "." && !is_dir($save_base_dir . $extra_dir))
		{
			mkdir($save_base_dir . $extra_dir);
		}

		if($append)
		{
			if(is_file($save_base_dir . $file))
			{
				if(file_put_contents($save_base_dir . $file, $contents . "\n", FILE_APPEND) != false)
				{
					return $save_base_dir . $file;
				}
			}
		}
		else
		{
			if(file_put_contents($save_base_dir . $file, $contents) != false)
			{
				return $save_base_dir . $file;
			}
		}

		return false;
	}
	public static function read_file($file)
	{
		$file = self::save_dir() . $file;

		return is_file($file) ? file_get_contents($file) : false;
	}
	public static function is_file($file)
	{
		$file = self::save_dir() . $file;

		return is_file($file);
	}
	public static function remove_file($file)
	{
		$file = self::save_dir() . $file;

		return is_file($file) && unlink($file);
	}
	public static function pts_timed_function($function, $time, $parameters = null)
	{
		if(($time < 0.5 && $time != -1) || $time > 300)
		{
			return;
		}

		//TODO improve accuracy by comparing time pre- and post- loop iteration

		if(function_exists('pcntl_fork'))
		{
			$pid = pcntl_fork();

			if($pid != -1)
			{
				if($pid)
				{
					return $pid;
				}
				else
				{
					$loop_continue = true;
					/*
					ML: I think this below check can be safely removed
					$start_id = pts_unique_runtime_identifier();
					 && ($start_id == pts_unique_runtime_identifier() || $start_id == PTS_INIT_TIME)
					*/
					while(pts_test_run_manager::test_run_process_active() !== -1 && is_file(PTS_USER_LOCK) && $loop_continue)
					{
//						if ($parameters == null || !is_array($parameters))
//						{
//							$parameters = array();
//						}

						$parameter_array = pts_arrays::to_array($parameters);
						call_user_func_array(array(self::module_name(), $function), $parameter_array);

						if($time > 0)
						{
							sleep($time);
						}
						else if($time == -1)
						{
							$loop_continue = false;
						}
					}
					exit(0);
				}
			}
		}
		else
		{
			trigger_error('php-pcntl must be installed for the ' . self::module_name() . ' module.', E_USER_ERROR);
		}
	}
	private static function module_name()
	{
		$module_name = 'unknown';

		if(($current = pts_module_manager::get_current_module()) != null)
		{
			$module_name = $current;
		}
		else
		{
			$bt = debug_backtrace();

			for($i = 0; $i < count($bt) && $module_name == "unknown"; $i++)
			{
				if($bt[$i]["class"] != "pts_module")
				{
					$module_name = $bt[$i]["class"];
				}
			}
		}

		return $module_name;
	}
}

?>
