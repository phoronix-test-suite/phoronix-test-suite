<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel
	phodevi.php: The object for interacting with the PTS device framework

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

define("PHODEVI_AVOID_CACHE", 0); // No caching
define("PHODEVI_STAND_CACHE", 1); // Standard caching
define("PHODEVI_SMART_CACHE", 2); // Smart caching
define("PHODEVI_PATH", dirname(__FILE__) . '/');

class phodevi
{
	private static $device_cache = null;
	private static $smart_cache = null;
	private static $sensors = null;

	public static function read_name($device)
	{
		return phodevi::read_property($device, "identifier");
	}
	public static function available_hardware_devices()
	{
		return array(
		"Processor" => "cpu",
		"Motherboard" => "motherboard",
		"Chipset" => "chipset",
		"Memory" => "memory",
		"Disk" => "disk",
		"Graphics" => "gpu",
		"Audio" => "audio",
		"Monitor" => "monitor"
		);
	}
	public static function load_sensors()
	{
		foreach(glob(PHODEVI_PATH . "sensors/*") as $sensor_obj_file)
		{
			$sensor_obj_name = basename($sensor_obj_file, ".php");

			if(!class_exists($sensor_obj_name, false))
			{
				include($sensor_obj_file);
			}

			$type = call_user_func(array($sensor_obj_name, "get_type"));
			$sensor = call_user_func(array($sensor_obj_name, "get_sensor"));

			if($type != null && $sensor != null)
			{
				self::$sensors[$type][$sensor] = $sensor_obj_name;
			}
		}
	}
	public static function available_sensors()
	{
		static $available_sensors = null;

		if($available_sensors == null)
		{
			$available_sensors = array();

			foreach(self::$sensors as $sensor_type => &$sensor)
			{
				foreach(array_keys($sensor) as $sensor_senses)
				{
					array_push($available_sensors, array($sensor_type, $sensor_senses));
				}
			}
		}

		return $available_sensors;
	}
	public static function supported_sensors()
	{
		static $supported_sensors = null;

		if($supported_sensors == null)
		{
			$supported_sensors = array();

			foreach(self::available_sensors() as $sensor)
			{
				if(self::sensor_supported($sensor))
				{
					array_push($supported_sensors, $sensor);
				}
			}
		}

		return $supported_sensors;
	}
	public static function unsupported_sensors()
	{
		static $unsupported_sensors = null;

		if($unsupported_sensors == null)
		{
			$unsupported_sensors = array();
			$supported_sensors = self::supported_sensors();

			foreach(self::available_sensors() as $sensor)
			{
				if(!in_array($sensor, $supported_sensors))
				{
					array_push($unsupported_sensors, $sensor);
				}
			}
		}

		return $unsupported_sensors;
	}
	public static function read_sensor($sensor)
	{
		$value = false;

		if(isset(self::$sensors[$sensor[0]][$sensor[1]]))
		{
			$value = call_user_func(array(self::$sensors[$sensor[0]][$sensor[1]], "read_sensor"));
		}

		return $value;
	}
	public static function read_sensor_unit($sensor)
	{
		return call_user_func(array(self::$sensors[$sensor[0]][$sensor[1]], "get_unit"));
	}
	public static function sensor_supported($sensor)
	{
		return isset(self::$sensors[$sensor[0]][$sensor[1]]) && call_user_func(array(self::$sensors[$sensor[0]][$sensor[1]], "support_check"));
	}
	public static function sensor_identifier($sensor)
	{
		return $sensor[0] . '.' . $sensor[1];
	}
	public static function sensor_name($sensor)
	{
		$type = call_user_func(array(self::$sensors[$sensor[0]][$sensor[1]], "get_type"));
		$sensor = call_user_func(array(self::$sensors[$sensor[0]][$sensor[1]], "get_sensor"));

		if(strlen($type) < 4)
		{
			$formatted = strtoupper($type);
		}
		else
		{
			$formatted = ucwords($type);
		}

		$formatted .= ' ';

		switch($sensor)
		{
			case "temp":
				$formatted .= "Temperature";
				break;
			case "freq":
				$formatted .= "Frequency";
				break;
			case "memory":
				$formatted .= "Memory Usage";
				break;
			default:
				$formatted .= ucwords(str_replace('-', ' ', $sensor));
				break;
		}

		return $formatted;
	}
	public static function system_hardware($return_as_string = true)
	{
		return self::system_information_parse(self::available_hardware_devices(), $return_as_string);
	}
	public static function read_device_notes($device)
	{
		$devices = phodevi::available_hardware_devices();

		if(isset($devices[$device]))
		{
			$notes_r = call_user_func(array("phodevi_" . $devices[$device], "device_notes"));
		}
		else
		{
			$notes_r = array();
		}

		return is_array($notes_r) ? $notes_r : array();
	}
	public static function read_special_settings_string($device)
	{
		$devices = phodevi::available_hardware_devices();

		if(isset($devices[$device]))
		{
			$settings_special = call_user_func(array("phodevi_" . $devices[$device], "special_settings_string"));
		}
		else
		{
			$settings_special = null;
		}

		return $settings_special;
	}
	public static function read_property($device, $read_property)
	{
		$value = false;

		if(method_exists("phodevi_" . $device, "read_property"))
		{
			$property = call_user_func(array("phodevi_" . $device, "read_property"), $read_property);

			if(!($property instanceOf phodevi_device_property))
			{
				return false;
			}

			$cache_code = $property->cache_code();

			if($cache_code != PHODEVI_AVOID_CACHE && isset(self::$device_cache[$device][$read_property]))
			{
				$value = self::$device_cache[$device][$read_property];
			}
			else
			{
				$dev_function_r = pts_to_array($property->get_device_function());
				$dev_function = $dev_function_r[0];
				$function_pass = array();

				for($i = 1; $i < count($dev_function_r); $i++)
				{
					array_push($function_pass, $dev_function_r[$i]);
				}

				if(method_exists("phodevi_" . $device, $dev_function))
				{
					$value = call_user_func_array(array("phodevi_" . $device, $dev_function), $function_pass);

					if($cache_code != PHODEVI_AVOID_CACHE)
					{
						self::$device_cache[$device][$read_property] = $value;

						if($cache_code == PHODEVI_SMART_CACHE)
						{
							// TODO: For now just copy the smart cache to other var, but come up with better yet efficient way
							self::$smart_cache[$device][$read_property] = $value;
						}
					}
				}
			}
		}

		return $value;
	}
	public static function set_property($device, $set_property, $pass_args = array())
	{
		$return_value = false;

		if(method_exists("phodevi_" . $device, "set_property"))
		{
			$return_value = call_user_func(array("phodevi_" . $device, "set_property"), $set_property, $pass_args);
		}

		return $return_value;
	}
	public static function initial_setup()
	{
		// Operating System Detection
		$supported_operating_systems = array("Linux", array("Solaris", "Sun"), array("BSD", "DragonFly"), array("MacOSX", "Darwin"), "Windows");
		$uname_s = strtolower(php_uname("s"));

		foreach($supported_operating_systems as $os_check)
		{
			if(!is_array($os_check))
			{
				$os_check = array($os_check);
			}

			$is_os = false;
			$os_title = $os_check[0];

			for($i = 0; $i < count($os_check) && !$is_os; $i++)
			{
				if(strpos($uname_s, strtolower($os_check[$i])) !== false) // Check for OS
				{
					define("OPERATING_SYSTEM", $os_title);
					define("IS_" . strtoupper($os_title), true);
					$is_os = true;
				}
			}

			if(!$is_os)
			{
				define("IS_" . strtoupper($os_title), false);
			}
		}

		if(!defined("OPERATING_SYSTEM"))
		{
			define("OPERATING_SYSTEM", "Unknown");
			define("IS_UNKNOWN", true);
		}
		else
		{
			define("IS_UNKNOWN", false);
		}

		define("OS_PREFIX", strtolower(OPERATING_SYSTEM) . "_");

		switch(OPERATING_SYSTEM)
		{
			case "BSD":
				define("BSD_LINUX_COMPATIBLE", pts_executable_in_path("kldstat") && strpos(shell_exec("kldstat -n linux 2>&1"), "linux.ko") != false);
				break;
		}

		// OpenGL / graphics detection
		$graphics_detection = array("NVIDIA", array("ATI", "fglrx"), array("Mesa", "SGI"));
		$opengl_driver = phodevi::read_property("system", "opengl-vendor") . " " . phodevi::read_property("system", "opengl-driver") . " " . phodevi::read_property("system", "dri-display-driver");
		$opengl_driver = trim(str_replace("Corporation", "", $opengl_driver)); // Prevents a possible false positive for ATI being in CorporATIon
		$found_gpu_match = false;

		foreach($graphics_detection as $gpu_check)
		{
			if(!is_array($gpu_check))
			{
				$gpu_check = array($gpu_check);
			}

			$is_this = false;
			$gpu_title = $gpu_check[0];

			for($i = 0; $i < count($gpu_check) && !$is_this; $i++)
			{
				if(stripos($opengl_driver, $gpu_check[$i]) !== false) // Check for GPU
				{
					define("IS_" . strtoupper($gpu_title) . "_GRAPHICS", true);
					$is_this = true;
					$found_gpu_match = true;
				}
			}

			if(!$is_this)
			{
				define("IS_" . strtoupper($gpu_title) . "_GRAPHICS", false);
			}
		}

		define("IS_UNKNOWN_GRAPHICS", ($found_gpu_match == false));
		self::load_sensors();
	}
	public static function set_device_cache($cache_array)
	{
		if(is_array($cache_array) && !empty($cache_array))
		{
			self::$smart_cache = array_merge(self::$smart_cache, $cache_array);
			self::$device_cache = array_merge(self::$device_cache, $cache_array);
		}
	}
	public static function get_phodevi_cache_object($store_dir, $client_version = 0)
	{
		return new phodevi_cache(self::$smart_cache, $store_dir, $client_version);
	}
	protected static function system_information_parse($component_array, $return_as_string = true)
	{
		// Returns string of hardware information
		$info = array();

		foreach($component_array as $string => $id)
		{
			$value = self::read_name($id);

			if($value != -1 && !empty($value))
			{
				$info[$string] = $value;
			}
		}

		if($return_as_string)
		{
			$info_array = $info;
			$info = "";

			foreach($info_array as $type => $value)
			{
				if($info != "")
				{
					$info .= ", ";
				}

				$info .= $type . ": " . $value;
			}
		}

		return $info;
	}
	public static function system_uptime()
	{
		// Returns the system's uptime in seconds
		$uptime = 1;

		if(is_file("/proc/uptime"))
		{
			$uptime = pts_strings::first_in_string(pts_file_get_contents("/proc/uptime"));
		}
		else if(($uptime_cmd = pts_executable_in_path("uptime")) != false)
		{
			$uptime_counter = 0;
			$uptime_output = shell_exec($uptime_cmd . " 2>&1");
			$uptime_output = substr($uptime_output, strpos($uptime_output, " up") + 3);
			$uptime_output = substr($uptime_output, 0, strpos($uptime_output, " user"));
			$uptime_output = substr($uptime_output, 0, strrpos($uptime_output, ",")) . " ";

			if(($day_end_pos = strpos($uptime_output, " day")) !== false)
			{
				$day_output = substr($uptime_output, 0, $day_end_pos);
				$day_output = substr($day_output, strrpos($day_output, " ") + 1);

				if(is_numeric($day_output))
				{
					$uptime_counter += $day_output * 86400;
				}
			}

			if(($mins_end_pos = strpos($uptime_output, " mins")) !== false)
			{
				$mins_output = substr($uptime_output, 0, $day_end_pos);
				$mins_output = substr($mins_output, strrpos($mins_output, " ") + 1);

				if(is_numeric($mins_output))
				{
					$uptime_counter += $mins_output * 60;
				}
			}

			if(($time_split_pos = strpos($uptime_output, ":")) !== false)
			{
				$hours_output = substr($uptime_output, 0, $time_split_pos);
				$hours_output = substr($hours_output, strrpos($hours_output, " ") + 1);
				$mins_output = substr($uptime_output, $time_split_pos + 1);
				$mins_output = substr($mins_output, 0, strpos($mins_output, " "));

				if(is_numeric($hours_output))
				{
					$uptime_counter += $hours_output * 3600;
				}
				if(is_numeric($mins_output))
				{
					$uptime_counter += $mins_output * 60;
				}
			}

			if(is_numeric($uptime_counter) && $uptime_counter > 0)
			{
				$uptime = $uptime_counter;
			}
		}

		return intval($uptime);
	}
}

?>
