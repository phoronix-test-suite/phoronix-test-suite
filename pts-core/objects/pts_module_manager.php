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

	public static function attach_module($module)
	{
		if(in_array($module, self::$modules))
		{
			return false;
		}

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
	public static function clean_module_list()
	{
		array_unique(self::$modules);

		foreach(self::$modules as $i => $module)
		{
			if(!pts_is_module($module))
			{
				unset(self::$modules[$i]);
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
