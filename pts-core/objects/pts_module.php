<?php

/*
	Phoronix Test Suite "Trondheim"
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2004-2008, Michael Larabel
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
		$prefix_dir = PTS_USER_DIR . "local/";

		if(!is_dir($prefix_dir))
			mkdir($prefix_dir);

		return $prefix_dir . self::module_name() . "/";
	}
	public static function save_file($file, $contents = NULL)
	{
		// Saves a file for a module

		$save_base_dir = self::save_dir();

		if(!is_dir($save_base_dir))
			mkdir($save_base_dir);

		if(($extra_dir = dirname($file)) != "." && !is_dir($save_base_dir . $extra_dir))
			mkdir($save_base_dir . $extra_dir);

		if(!empty($contents))
		{
			if(file_put_contents($save_base_dir . $file, $contents) != FALSE)
				return $save_base_dir . $file;
		}

		return FALSE;
	}
	public static function copy_file($from_file, $to_file)
	{
		// Copy a file for a module

		$save_base_dir = self::save_dir();

		if(!is_dir($save_base_dir))
			mkdir($save_base_dir);

		if(($extra_dir = dirname($to_file)) != "." && !is_dir($save_base_dir . $extra_dir))
			mkdir($save_base_dir . $extra_dir);


		if(is_file($from_file) && (!is_file($save_base_dir . $to_file) || md5_file($from_file) != md5_file($save_base_dir . $to_file)))
			if(copy($from_file, $save_base_dir . $to_file))
				return $save_base_dir . $to_file;

		return FALSE;
	}
	private static function module_name()
	{
		$module_name = "unknown";

		if($GLOBALS["PTS_MODULE_CURRENT"] != FALSE)
		{
			$module_name = $GLOBALS["PTS_MODULE_CURRENT"];
		}
		else
		{
			$bt = debug_backtrace();

			for($i = 0; $i < count($bt) && $module_name == "unknown"; $i++)
				if($bt[$i]["class"] != "pts_module")
					$module_name = $bt[$i]["class"];
		}

		return $module_name;
	}
}

?>
