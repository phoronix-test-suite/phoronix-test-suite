<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
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
	public static function save_dir()
	{
		$prefix_dir = PTS_USER_DIR . "module-files/";
		pts_mkdir($prefix_dir);

		return $prefix_dir . str_replace("_", "-", self::module_name()) . "/";
	}
	public static function is_module_setup()
	{
		$module_name = self::module_name();
		$is_setup = true;

		$module_setup_options = pts_php_module_call($module_name, "module_setup");

		foreach($module_setup_options as $option)
		{
			if($option instanceOf pts_module_option)
			{
				if(pts_module::read_option($option->get_identifier()) == false)
				{
					$is_setup = false;
					break;
				}
			}
		}

		return $is_setup;
	}
	public static function read_option($identifier, $default_fallback = false)
	{
		$module_name = self::module_name();
		$value = false;

		$module_config_parser = new tandem_XmlReader(PTS_USER_DIR . "modules-config.xml");
		$option_module = $module_config_parser->getXMLArrayValues(P_MODULE_OPTION_NAME);
		$option_identifier = $module_config_parser->getXMLArrayValues(P_MODULE_OPTION_IDENTIFIER);
		$option_value = $module_config_parser->getXMLArrayValues(P_MODULE_OPTION_VALUE);

		for($i = 0; $i < count($option_module) && $value == false; $i++)
		{
			if($option_module[$i] == $module_name && $option_identifier[$i] == $identifier)
			{
				$value = $option_value[$i];
			}
		}

		if($default_fallback && empty($value))
		{
			// Find the default value
			eval("\$module_options = " . $module_name . "::module_setup();");

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
		pts_module_config_init(array(self::module_name() . "__" . $identifier => $value));
	}
	public static function save_file($file, $contents = null, $append = false)
	{
		// Saves a file for a module

		$save_base_dir = self::save_dir();

		pts_mkdir($save_base_dir);

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
	public static function copy_file($from_file, $to_file)
	{
		// Copy a file for a module
		$save_base_dir = self::save_dir();

		pts_mkdir($save_base_dir);

		if(($extra_dir = dirname($to_file)) != "." && !is_dir($save_base_dir . $extra_dir))
		{
			mkdir($save_base_dir . $extra_dir);
		}

		if(is_file($from_file) && (!is_file($save_base_dir . $to_file) || md5_file($from_file) != md5_file($save_base_dir . $to_file)))
		{
			if(copy($from_file, $save_base_dir . $to_file))
			{
				return $save_base_dir . $to_file;
			}
		}

		return false;
	}
	public static function pts_fork_function($function)
	{
		self::pts_timed_function(-1, $function);
	}
	public static function pts_timed_function($time, $function)
	{
		if($time < 3 || $time > 300)
		{
			return;
		}

		if(function_exists("pcntl_fork"))
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
					$start_id = pts_unique_runtime_identifier();
					while(!pts_is_assignment("PTS_TESTING_DONE") && ($start_id == pts_unique_runtime_identifier() || $start_id == PTS_INIT_TIME) && is_file(PTS_USER_LOCK) && $loop_continue)
					{
						eval(self::module_name() . "::" . $function . "();");

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
			echo pts_string_header("NOTICE: php-pcntl must be installed for the " . self::module_name() . " module.");
		}
	}
	private static function module_name()
	{
		$module_name = "unknown";

		if(($current = pts_module_current()) != false)
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
