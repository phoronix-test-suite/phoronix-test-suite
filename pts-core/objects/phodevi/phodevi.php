<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2024, Phoronix Media
	Copyright (C) 2009 - 2024, Michael Larabel
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

class phodevi extends phodevi_base
{
	public static $vfs = false;
	private static $device_cache = array();
	private static $smart_cache = array();
	private static $sensors = null;

	private static $operating_system = null;
	private static $graphics_detected = false;
	private static $graphics = array(
		'mesa' => false,
		'ati' => false,
		'nvidia' => false
		);
	private static $operating_systems = array(
		'linux' => false,
		'macosx' => false,
		'solaris' => false,
		'bsd' => false,
		'hurd' => false,
		'minix' => false,
		'windows' => false
		);

	// A variable that modules can use to override Phodevi caching support, etc
	public static $allow_phodevi_caching = true;
	protected static $sanitize_string = null;

	const no_caching = 1;
	const std_caching = 2;
	const smart_caching = 3;

	public static function read_name($device)
	{
		return phodevi::read_property($device, 'identifier');
	}
	public static function load_sensors()
	{
		if(!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50300)
		{
			// Phodevi sensors don't work prior to PHP 5.3
			self::$sensors = array();
			return false;
		}

		foreach(glob(dirname(__FILE__) . '/sensors/*') as $sensor_obj_file)
		{
			$sensor_obj_name = basename($sensor_obj_file, '.php');

			if(!class_exists($sensor_obj_name, false))
			{
				include($sensor_obj_file);
			}

			$type = call_user_func(array($sensor_obj_name, 'get_type'));
			$sensor = call_user_func(array($sensor_obj_name, 'get_sensor'));

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
					array_push($available_sensors, array($sensor_type, $sensor_senses, self::$sensors[$sensor_type][$sensor_senses]));
				}
			}
		}

		return $available_sensors;
	}
	public static function is_sensor_supported($sensor)
	{
		$supported = false;
		$sensors = self::query_sensors();
		foreach($sensors as $s)
		{
			if($s[0] == $sensor[0] && $s[1] == $sensor[1])
			{
				$supported = true;
				break;
			}
		}

		return $supported;
	}
	public static function query_sensors($limit_sensor_selection = false)
	{
		static $supported_sensors = null;

		if($limit_sensor_selection != false)
		{
			$selected = array();
			foreach(self::available_sensors() as $sensor)
			{
				if($limit_sensor_selection == false || (is_array($limit_sensor_selection) && in_array($sensor[2], $limit_sensor_selection)))
				{
					array_push($selected, $sensor);
				}
			}
			return $selected;
		}
		else if($supported_sensors == null)
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
	public static function read_sensor($sensor)
	{
		if($sensor instanceof phodevi_sensor)
		{
			$sensor_object = $sensor;
		}
		else
		{
			$sensor_object = new self::$sensors[$sensor[0]][$sensor[1]](null, null);
		}

		return $sensor_object->read_sensor();
	}
	public static function read_sensor_object_unit(&$sensor_object)
	{
		$sensor = array($sensor_object->get_type(), $sensor_object->get_sensor(), get_class($sensor_object));
		return self::read_sensor_unit($sensor);
	}
	public static function read_sensor_object_unit_short(&$sensor_object)
	{
		$sensor_unit = self::read_sensor_object_unit($sensor_object);

		switch($sensor_unit)
		{
			case 'Celsius':
				$sensor_unit = 'C';
				break;
			case 'Percent':
				$sensor_unit = '%';
				break;
			case 'Megabytes':
				$sensor_unit = 'MB';
				break;
			case 'Megahertz':
				$sensor_unit = 'MHz';
				break;
			case 'Volts':
				$sensor_unit = 'V';
				break;
			case 'Kilobytes/seconds':
				$sensor_unit = 'KBps';
				break;
			case 'Millivolts':
				$sensor_unit = 'mV';
				break;
		}

		return $sensor_unit;
	}
	public static function read_sensor_unit($sensor)
	{
		return call_user_func(array(self::$sensors[$sensor[0]][$sensor[1]], 'get_unit'));
	}
	public static function sensor_supported($sensor)
	{
		$sensor_object = new self::$sensors[$sensor[0]][$sensor[1]](null, null);

		return isset(self::$sensors[$sensor[0]][$sensor[1]]) && $sensor_object->support_check();
	}
	public static function sensor_object_identifier(&$sensor_object)
	{
		$sensor = array($sensor_object->get_type(), $sensor_object->get_sensor(), get_class($sensor_object));
		return self::sensor_identifier($sensor) . ($sensor_object->get_instance() != 0 ? '.' . $sensor_object->get_instance() : null);
	}
	public static function sensor_identifier($sensor)
	{
		return $sensor[0] . '.' . $sensor[1];
	}
	public static function sensor_object_name(&$sensor_object)
	{
		$sensor = array($sensor_object->get_type(), $sensor_object->get_sensor(), get_class($sensor_object));
		$name = self::sensor_name($sensor);
		$params = $sensor_object->get_readable_device_name();

		if($params !== NULL)
		{
			$name .= ' (' . $params . ')';
		}

		return $name;
	}
	public static function sensor_name($sensor)
	{
		$type = call_user_func(array(self::$sensors[$sensor[0]][$sensor[1]], 'get_type'));
		$sensor = call_user_func(array(self::$sensors[$sensor[0]][$sensor[1]], 'get_sensor'));

		if(strlen($type) < 4)
		{
			$formatted = strtoupper($type);
		}
		else
		{
			$formatted = ucwords($type);
		}

		switch($formatted)
		{
			case 'SYS':
				$formatted = 'System';
				break;
			case 'HDD':
				$formatted = 'Drive';
				break;
		}

		$formatted .= ' ';

		switch($sensor)
		{
			case 'temp':
				$formatted .= 'Temperature';
				break;
			case 'freq':
				$formatted .= 'Frequency';
				break;
			case 'memory':
				$formatted .= 'Memory Usage';
				break;
			case 'power':
				$formatted .= 'Power Consumption';
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
	public static function system_software($return_as_string = true)
	{
		return self::system_information_parse(self::available_software_components(), $return_as_string);
	}
	public static function system_centralized_view($return_as_string = true)
	{
		$core_count = phodevi::read_property('cpu', 'physical-core-count');
		$thread_count = phodevi::read_property('cpu', 'thread-count');

		$sys = array(
			'Processor' => phodevi::read_property('cpu', 'model-and-speed'),
				array(
				'Core Count' => $core_count,
				'Thread Count' => !empty($core_count) && $core_count == $thread_count ? '' : $thread_count, // don't show thread count if it's same as core count
				'Extensions' => phodevi_cpu::instruction_set_extensions(),
			//	'Virtualization' => (phodevi_cpu::virtualization_technology() ? phodevi_cpu::virtualization_technology() : ''),
				'Cache Size' => phodevi::read_property('cpu', 'cache-size-string'),
				'Microcode'=> phodevi::read_property('cpu', 'microcode-version'),
				'Core Family' => phodevi::read_property('cpu', 'core-family-name'),
				'Scaling Driver'=> phodevi::read_property('cpu', 'scaling-governor'),
				),
			'Graphics' => phodevi::read_name('gpu'),
				array(
				'Frequency' => phodevi::read_property('gpu', 'frequency'),
				'BAR1 / Visible vRAM' => phodevi::read_property('gpu', 'bar1-visible-vram'),
				'OpenGL' => phodevi::read_property('system', 'opengl-driver'),
				'Vulkan' => phodevi::read_property('system', 'vulkan-driver'),
				'OpenCL' => phodevi::read_property('system', 'opencl-driver'),
				'Display Driver' => phodevi::read_property('system', 'display-driver-string'),
				'Monitor' => phodevi::read_name('monitor'),
				'Screen' => phodevi::read_property('gpu', 'screen-resolution-string'),
				),
			'Motherboard' => phodevi::read_name('motherboard'),
				array(
				'BIOS Version' => phodevi::read_property('motherboard', 'bios-version'),
				'Chipset' => phodevi::read_name('chipset'),
				'Audio' => phodevi::read_name('audio'),
				'Network' => phodevi::read_name('network'),
				'Platform Profile'=> phodevi::read_property('system', 'platform-profile'),
				),
			'Memory' => phodevi::read_name('memory'),
				array(),
			'Disk' => phodevi::read_name('disk'),
				array(
				'File-System' => phodevi::read_property('system', 'filesystem'),
				'Mount Options' => phodevi::read_property('disk', 'mount-options-string'),
				//'Block Size' => phodevi::read_property('disk', 'block-size'),
				'Disk Scheduler' => phodevi::read_property('disk', 'scheduler'),
				'Disk Details' => phodevi::read_property('disk', 'extra-disk-details'),
				),
			'Operating System' => phodevi::read_property('system', 'operating-system'),
				array(
				'Kernel' => phodevi::read_property('system', 'kernel-string'),
				'Desktop' => phodevi::read_property('system', 'desktop-environment'),
				'Display Server' => phodevi::read_property('system', 'display-server'),
				'Compiler' => phodevi::read_property('system', 'compiler'),
				'System Layer' => phodevi::read_property('system', 'system-layer'),
				'Security' => phodevi::read_property('system', 'security-features'),
				)
			);

		if($return_as_string)
		{
			$sys_string = null;
			$tabled = array();
			foreach($sys as $key => &$in)
			{
				$space_in = 2;
				if(is_array($in))
				{
					$tabled = array();
					foreach($in as $key => $value)
					{
						if(!empty($value))
						{
							if(isset($value[64]) && strpos($value, ' + ') !== false)
							{
								$values = explode(' + ', $value);
								$tabled[] = array(pts_client::cli_just_bold($key) . ':' . str_repeat(' ', (20 - strlen($key))), array_shift($values));
								foreach($values as $value)
								{
									$tabled[] = array(pts_client::cli_just_bold(' '), '+ ' . $value);
								}
							}
							else
							{
								$tabled[] = array(pts_client::cli_just_bold($key) . ':' . str_repeat(' ', (20 - strlen($key))), $value);
								//$sys_string .= '      ' . strtoupper($key) . ':' . $value . PHP_EOL;
							}
						}
					}
				}
				else
				{
					if(!empty($in) && ($x = strpos($in, ' (')))
					{
						$in = substr($in, 0, $x);
					}

					if(!empty($tabled))
					{
						$sys_string .= pts_user_io::display_text_table($tabled, '    ', 0, 17) . PHP_EOL;
					}

					if(isset($in[80]) && strpos($in, ' + ') !== false)
					{
						$values = explode(' + ', $in);
						$sys_string .= PHP_EOL . pts_client::cli_just_bold(strtoupper($key)) . ': ' . str_repeat(' ', (24 - strlen($key))) . pts_client::cli_colored_text(array_shift($values), 'green', true);
						foreach($values as $value)
						{
							$sys_string .= PHP_EOL . str_repeat(' ', 22) . pts_client::cli_colored_text('+ ' . $value, 'green', true);
						}
						$sys_string .= PHP_EOL;
					}
					else
					{
						$sys_string .= PHP_EOL . pts_client::cli_just_bold(strtoupper($key)) . ': ' . str_repeat(' ', (24 - strlen($key))) . pts_client::cli_colored_text($in, 'green', true) . PHP_EOL;
					}
				}

			}
			if(!empty($tabled))
			{
				$sys_string .= pts_user_io::display_text_table($tabled, '    ', 0, 17) . PHP_EOL;
			}
			return $sys_string;
		}

		return $sys;
	}
	public static function system_id_string()
	{
		$extra = null;
		foreach(array('CC', 'CXX', 'CFLAGS', 'CPPFLAGS', 'CXXFLAGS', 'USE_WINE') as $env)
		{
			$val = getenv($env);

			if(!empty($val))
			{
				$extra .= $env . '=' . $val . ';';
			}
		}

		$components = array(phodevi::read_property('cpu', 'model'), phodevi::read_property('system', 'operating-system'), phodevi::read_property('system', 'compiler'), $extra);
		return base64_encode(implode('__', $components));
	}
	public static function read_property($device, $read_property, $strip_string = true)
	{
		static $properties_table = array();
		$value = false;

		if(!isset($properties_table[$device]))
		{
			$properties_table[$device] = call_user_func(array('phodevi_' . $device, 'properties'));
		}

		if(!isset($properties_table[$device][$read_property]))
		{
			echo 'NOTICE: ' . $read_property . ' does not exist for ' . $device . '.' . PHP_EOL;
		}

		if(!($properties_table[$device][$read_property] instanceof phodevi_device_property))
		{
			return $properties_table[$device][$read_property];
		}

		$cache_code = $properties_table[$device][$read_property]->cache_code();
		if($cache_code != phodevi::no_caching && phodevi::$allow_phodevi_caching && isset(self::$device_cache[$device][$read_property]))
		{
			$value = self::$device_cache[$device][$read_property];
		}
		else
		{
			$dev_function_r = pts_arrays::to_array($properties_table[$device][$read_property]->get_device_function());
			$dev_function = $dev_function_r[0];
			$function_pass = array();

			for($i = 1; $i < count($dev_function_r); $i++)
			{
				array_push($function_pass, $dev_function_r[$i]);
			}
			if(method_exists('phodevi_' . $device, $dev_function))
			{
				$value = call_user_func_array(array('phodevi_' . $device, $dev_function), $function_pass);
				if(!is_array($value) && $value != null)
				{
					if($strip_string)
					{
						$value = pts_strings::strip_string($value);
					}
					if(function_exists('preg_replace'))
					{
						$value = preg_replace('/[^(\x20-\x7F)]*/','', $value);
					}
				}

				if($cache_code != phodevi::no_caching)
				{
					self::$device_cache[$device][$read_property] = $value;

					if($cache_code == phodevi::smart_caching)
					{
						// TODO: For now just copy the smart cache to other var, but come up with better yet efficient way
						self::$smart_cache[$device][$read_property] = $value;
					}
				}
			}
		}

		if(!empty(self::$sanitize_string) && !empty($value) && is_string($value))
		{
			$read_property_lower = strtolower($read_property);
			$device_lower = strtolower($device);
			foreach(self::$sanitize_string as $limit_or_index => $sanitize)
			{
				if(!is_numeric($limit_or_index) && $limit_or_index != $read_property  && $limit_or_index != $device_lower)
				{
					// If index is set as property name, make sure reading the same property now or can skip it...
					continue;
				}
				$value = str_ireplace($sanitize, '', $value);
			}
			$value = trim($value);
		}

		return $value;
	}
	public static function read_all_properties()
	{
		$all_properties = array();
		$components = array();
		foreach(glob(__DIR__ . '/components/phodevi_*.php') as $file)
		{
			$components[] = substr(basename($file, '.php'), 8);
		}

		foreach($components as $device)
		{
			$properties = call_user_func(array('phodevi_' . $device, 'properties'));
			$all_properties[$device] = array();
			foreach($properties as $id => $property)
			{
				$dev_function_r = pts_arrays::to_array($property->get_device_function());
				$dev_function = $dev_function_r[0];
				$function_pass = array();

				for($i = 1; $i < count($dev_function_r); $i++)
				{
					array_push($function_pass, $dev_function_r[$i]);
				}
				if(method_exists('phodevi_' . $device, $dev_function))
				{
					$value = call_user_func_array(array('phodevi_' . $device, $dev_function), $function_pass);
					if(!is_array($value) && $value != null)
					{
						$value = pts_strings::strip_string($value);
						if(function_exists('preg_replace'))
						{
							$value = preg_replace('/[^(\x20-\x7F)]*/','', $value);
						}
					}
					$all_properties[$device][$id] = $value;
				}
			}
		}

		return $all_properties;
	}
	public static function set_property($device, $set_property, $pass_args = array())
	{
		$return_value = false;

		if(method_exists('phodevi_' . $device, 'set_property'))
		{
			$return_value = call_user_func(array('phodevi_' . $device, 'set_property'), $set_property, $pass_args);
		}

		return $return_value;
	}
	public static function create_vfs()
	{
		self::$vfs = new phodevi_vfs();
	}
	public static function set_sanitize_string($str)
	{
		self::$sanitize_string = array();

		// Multiple strings to remove can be delimited by a comma
		foreach(explode(',', $str) as $to_sanitize)
		{
			if(!empty($to_sanitize))
			{
				$to_sanitize = explode('=', $to_sanitize);
				if(count($to_sanitize) == 2)
				{
					// Limiting sanitize string to a particular Phodevi property
					self::$sanitize_string[strtolower($to_sanitize[0])] = $to_sanitize[1];
				}
				else
				{
					// Check all Phodevi properties
					self::$sanitize_string[] = $to_sanitize[0];
				}
			}
		}
	}
	public static function initial_setup()
	{
		phodevi::set_sanitize_string(pts_env::read('PHODEVI_SANITIZE'));

		// Operating System Detection
		$supported_operating_systems = pts_types::operating_systems();
		$uname_s = strtolower(php_uname('s'));

		foreach($supported_operating_systems as $os_check)
		{
			for($i = 0; $i < count($os_check); $i++)
			{
				if(strpos($uname_s, strtolower($os_check[$i])) !== false) // Check for OS
				{
					self::$operating_system = $os_check[0];
					self::$operating_systems[strtolower($os_check[0])] = true;
					break;
				}
			}

			if(self::$operating_system != null)
			{
				break;
			}
		}

		if(self::operating_system() == false)
		{
			self::$operating_system = 'Unknown';
		}

		self::load_sensors();
	}
	private static function detect_graphics()
	{
		if(self::$graphics_detected == true)
		{
			return;
		}

		// OpenGL / graphics detection
		$graphics_detection = array('NVIDIA', array('Mesa', 'SGI'), array('AMD'));
		$opengl_driver = phodevi::read_property('system', 'opengl-vendor') . ' ' . phodevi::read_property('system', 'opengl-driver') . ' ' . phodevi::read_property('system', 'dri-display-driver');
		$opengl_driver = trim(str_replace('Corporation', '', $opengl_driver)); // Prevents a possible false positive for ATI being in CorporATIon

		foreach($graphics_detection as $gpu_check)
		{
			if(!is_array($gpu_check))
			{
				$gpu_check = array($gpu_check);
			}

			for($i = 0; $i < count($gpu_check); $i++)
			{
				if(stripos($opengl_driver, $gpu_check[$i]) !== false) // Check for GPU
				{
					self::$graphics[(strtolower($gpu_check[0]))] = true;
					break;
				}
			}
		}

		self::$graphics_detected = true;
	}
	public static function set_device_cache($cache_array)
	{
		if(is_array($cache_array) && !empty($cache_array))
		{
			self::$smart_cache = array_merge(self::$smart_cache, $cache_array);
			self::$device_cache = array_merge(self::$device_cache, $cache_array);
		}
	}
	public static function clear_cache()
	{
		self::$smart_cache = array();
		self::$device_cache = array();
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
			if(is_array($id) && count($id) == 2)
			{
				$value = self::read_property($id[0], $id[1]);
			}
			else
			{
				$value = self::read_name($id);
			}

			if($value != -1 && !empty($value))
			{
				$info[$string] = $value;
			}
		}

		if($return_as_string)
		{
			$info_array = $info;
			$info = null;

			foreach($info_array as $type => $value)
			{
				if($info != null)
				{
					$info .= ', ';
				}

				$info .= $type . ': ' . $value;
			}
		}

		return $info;
	}
	public static function system_uptime()
	{
		// Returns the system's uptime in seconds
		$uptime = 1;

		if(phodevi::is_windows())
		{
			$uptime = trim(shell_exec('powershell -NoProfile "((get-date) - (gcim Win32_OperatingSystem).LastBootUpTime).TotalSeconds"'));
			$uptime = is_numeric($uptime) && $uptime > 1 ? round($uptime) : 1;
		}
		else if(is_file('/proc/uptime'))
		{
			$uptime = pts_strings::first_in_string(pts_file_io::file_get_contents('/proc/uptime'));
		}
		else if(($uptime_cmd = pts_client::executable_in_path('uptime')) != false)
		{
			$uptime_counter = 0;
			$uptime_output = shell_exec($uptime_cmd . ' 2>&1');
			$uptime_output = substr($uptime_output, strpos($uptime_output, ' up') + 3);
			$uptime_output = substr($uptime_output, 0, strpos($uptime_output, ' user'));
			$uptime_output = substr($uptime_output, 0, strrpos($uptime_output, ',')) . ' ';

			if(($day_end_pos = strpos($uptime_output, ' day')) !== false)
			{
				$day_output = substr($uptime_output, 0, $day_end_pos);
				$day_output = substr($day_output, strrpos($day_output, ' ') + 1);

				if(is_numeric($day_output))
				{
					$uptime_counter += $day_output * 86400;
				}
			}

			if(($mins_end_pos = strpos($uptime_output, ' mins')) !== false)
			{
				$mins_output = substr($uptime_output, 0, $mins_end_pos);
				$mins_output = substr($mins_output, strrpos($mins_output, ' ') + 1);

				if(is_numeric($mins_output))
				{
					$uptime_counter += $mins_output * 60;
				}
			}

			if(($time_split_pos = strpos($uptime_output, ':')) !== false)
			{
				$hours_output = substr($uptime_output, 0, $time_split_pos);
				$hours_output = substr($hours_output, strrpos($hours_output, ' ') + 1);
				$mins_output = substr($uptime_output, $time_split_pos + 1);
				$mins_output = substr($mins_output, 0, strpos($mins_output, ' '));

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
	public static function reboot()
	{
		$reboot_cmd = '';

		if(phodevi::is_windows())
		{
			$reboot_cmd = 'shutdown /r';
		}
		else if(pts_client::executable_in_path('systemctl'))
		{
			$reboot_cmd = 'systemctl reboot';
		}
		else if(pts_client::executable_in_path('reboot'))
		{
			$reboot_cmd = 'reboot';
		}
		else if(pts_client::executable_in_path('shutdown'))
		{
			// macOS
			$reboot_cmd = 'shutdown -r now';
		}

		if($reboot_cmd)
		{
			shell_exec($reboot_cmd);
			// Buffer in case reboot isn't immediate
			echo PHP_EOL . PHP_EOL . 'Waiting for reboot...' . PHP_EOL;
			sleep(600);
		}
	}
	public static function shutdown()
	{
		// some systems like systemctl poweroff, others just like poweroff, but not consistent one method for all systems in testing
		if(pts_client::executable_in_path('systemctl') && rand(0, 1) == 1)
		{
			// Try systemd's poweroff method first
			shell_exec('systemctl poweroff');
			sleep(5);
		}
		if(pts_client::executable_in_path('poweroff'))
		{
			shell_exec('poweroff');
			sleep(5);
		}
		if(phodevi::is_windows())
		{
			shell_exec('shutdown /s');
			sleep(5);
		}
	}
	public static function cpu_arch_compatible($check_against)
	{
		$compatible = true;
		$this_arch = phodevi::read_property('system', 'kernel-architecture');
		$check_against = pts_arrays::to_array($check_against);

		if(isset($this_arch[2]) && substr($this_arch, -2) == '86')
		{
			$this_arch = 'x86';
		}
		if(!in_array($this_arch, $check_against) && !in_array(pts_env::read('HOST_EXTRA_COMPATIBLE_ARCH'), $check_against))
		{
			$compatible = false;
		}
		if(phodevi::is_macos())
		{
			$compatible = true;
		}

		return $compatible;
	}
	public static function is_vendor_string($vendor)
	{
		return isset($vendor[2]) && pts_strings::string_only_contains($vendor, (pts_strings::CHAR_LETTER | pts_strings::CHAR_NUMERIC | pts_strings::CHAR_DECIMAL | pts_strings::CHAR_SPACE | pts_strings::CHAR_DASH)) && !pts_strings::has_in_istring($vendor, array('manufacturer', 'vendor', 'unknown', 'generic', 'warning')) && (!isset($vendor[7]) || strpos($vendor, ' ') !== false || pts_strings::times_occurred($vendor, pts_strings::CHAR_NUMERIC) == 0) && pts_strings::string_contains($vendor, pts_strings::CHAR_LETTER) && (isset($vendor[4]) || pts_strings::times_occurred($vendor, pts_strings::CHAR_LETTER) > 1) && substr($vendor, -1) != '-';
	}
	public static function is_product_string($product)
	{
		return phodevi::is_vendor_string($product) && !pts_strings::has_in_istring($product, array('VBOX', 'QEMU', 'Virtual', 'Family', '440BX', 'VMware', ' Gen', 'Core IGP'));
	}
	public static function opencl_support_detected()
	{
		$supported = true;

		if(($clinfo = pts_client::executable_in_path('clinfo')))
		{
			$clinfo = shell_exec($clinfo);
			if(strpos($clinfo, 'Number of platforms                               0') !== false)
			{
				$supported = false;
			}
		}

		return $supported;
	}
	public static function vulkan_support_detected()
	{
		static $supported = -1;

		if($supported !== -1)
		{
			return $supported;
		}
		$supported = true;

		if(($vulkaninfo = pts_client::executable_in_path('vulkaninfo 2>&1')))
		{
			$vulkaninfo = shell_exec($vulkaninfo);
			if(strpos($vulkaninfo, 'Cannot create Vulkan instance') !== false)
			{
				$supported = false;
			}
			else if(strpos($vulkaninfo, 'failed with ERROR_INITIALIZATION_FAILED') !== false)
			{
				$supported = false;
			}
		}

		return $supported;
	}
	public static function is_fake_device($str)
	{
		$string_check = array(
			'Logical Volume',
			'QEMU',
			' KVM',
			'KVM ',
			'Eng Sample',
			'Unknown',
			' virt',
			'virtual',
			'svga',
			' VM',
			'Storage ',
			'Aspeed',
			'440BX',
			'Cirrus',
			);

		foreach($string_check as $check)
		{
			if(stripos($str, $check) !== false)
			{
				return true;
			}
		}

		$without_multiplier = $str;
		if(($x = strpos($without_multiplier, ' x ')) !== false)
		{
			$without_multiplier = substr($without_multiplier, $x + 3);
		}
		if(!preg_match('~[0-9]+~', $without_multiplier))
		{
			// No numbers at all in string so probably not a real device (e.g. some Virtual "Intel" CPU or similar components generic
			return true;
		}

		return false;
	}
	public static function os_under_test($force_override = false, $force_value = null)
	{
		static $os_under_test = null;
		if($force_override && !empty($force_value))
		{
			$os_under_test = $force_value;
		}
		else if($os_under_test == null)
		{
			$os_under_test = self::operating_system();
		}

		// The operating system under test
		return $os_under_test;
	}
	public static function operating_system()
	{
		return self::$operating_system;
	}
	public static function is_linux()
	{
		return self::$operating_systems['linux'];
	}
	public static function is_minix()
	{
		return self::$operating_systems['minix'];
	}
	public static function is_solaris()
	{
		return self::$operating_systems['solaris'];
	}
	public static function is_bsd()
	{
		return self::$operating_systems['bsd'];
	}
	public static function is_macos()
	{
		return self::$operating_systems['macosx'];
	}
	public static function is_hurd()
	{
		return self::$operating_systems['hurd'];
	}
	public static function is_windows()
	{
		return self::$operating_systems['windows'];
	}
	public static function is_mesa_graphics()
	{
		self::detect_graphics();
		return self::$graphics['mesa'];
	}
	public static function is_nvidia_graphics()
	{
		self::detect_graphics();
		return self::$graphics['nvidia'];
	}
	public static function is_root()
	{
		return phodevi::read_property('system', 'username') == 'root' || is_writable('/root');
	}
	public static function is_display_server_active()
	{
		if(phodevi::read_property('system', 'system-layer') == 'Docker')
		{
			// hopefully temp workaround XXX TODO
			return false;
		}

		return getenv('DISPLAY') != false || getenv('WAYLAND_DISPLAY') != false || phodevi::is_windows() || phodevi::is_macos();
	}
}

phodevi::create_vfs();

if(PTS_IS_CLIENT)
{
	phodevi::initial_setup();
}

?>
