<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel

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

class pts_client
{
	static $command_execution_count = 0;
	static $lock_pointers = null;

	public static function create_lock($lock_file)
	{
		if(isset(self::$lock_pointers[self::$command_execution_count][$lock_file]))
		{
			return false;
		}

		self::$lock_pointers[self::$command_execution_count][$lock_file] = fopen($lock_file, "w");
		chmod($lock_file, 0644);
		return self::$lock_pointers[self::$command_execution_count][$lock_file] != false && flock(self::$lock_pointers[self::$command_execution_count][$lock_file], LOCK_EX | LOCK_NB);
	}
	public static function release_lock($lock_file)
	{
		// Remove lock
		if(isset(self::$lock_pointers[self::$command_execution_count][$lock_file]) == false)
		{
			return false;
		}

		if(is_resource(self::$lock_pointers[self::$command_execution_count][$lock_file]))
		{
			fclose(self::$lock_pointers[self::$command_execution_count][$lock_file]);
		}

		pts_unlink(self::$lock_pointers[self::$command_execution_count][$lock_file]);
		unset(self::$lock_pointers[self::$command_execution_count][$lock_file]);
	}
	public static function check_command_for_function($option, $check_function)
	{
		$in_option = false;

		if(is_file(COMMAND_OPTIONS_DIR . $option . ".php"))
		{
			if(!class_exists($option, false))
			{
				pts_load_run_option($option);
			}

			if(method_exists($option, $check_function))
			{
				$in_option = true;
			}
		}

		return $in_option;
	}
	public static function execute_command($command, $pass_args = null, $preset_assignments = "")
	{
		if(is_file(COMMAND_OPTIONS_DIR . $command . ".php") && !class_exists($command, false))
		{
			pts_load_run_option($command);
		}

		if(is_file(COMMAND_OPTIONS_DIR . $command . ".php") && method_exists($command, "argument_checks"))
		{
			$argument_checks = call_user_func(array($command, "argument_checks"));

			foreach($argument_checks as &$argument_check)
			{
				$function_check = $argument_check->get_function_check();

				if(substr($function_check, 0, 1) == '!')
				{
					$function_check = substr($function_check, 1);
					$return_fails_on = true;
				}
				else
				{
					$return_fails_on = false;
				}

				if(!function_exists($function_check))
				{
					continue;
				}

				$return_value = call_user_func_array($function_check, array((isset($pass_args[$argument_check->get_argument_index()]) ? $pass_args[$argument_check->get_argument_index()] : null)));

				if($return_value == $return_fails_on)
				{
					echo pts_string_header($argument_check->get_error_string());
					return false;
				}
				else
				{
					if($argument_check->get_function_return_key() != null && !isset($pass_args[$argument_check->get_function_return_key()]))
					{
						$pass_args[$argument_check->get_function_return_key()] = $return_value;
					}
				}
			}
		}

		pts_assignment_manager::clear_all();
		self::$command_execution_count += 1;
		pts_set_assignment("COMMAND", $command);

		if(is_array($preset_assignments))
		{
			foreach(array_keys($preset_assignments) as $key)
			{
				pts_set_assignment_once($key, $preset_assignments[$key]);
			}
		}

		pts_module_process("__pre_option_process", $command);

		if(is_file(COMMAND_OPTIONS_DIR . $command . ".php"))
		{
			if(method_exists($command, "run"))
			{
				call_user_func(array($command, "run"), $pass_args);
			}
			else
			{
				echo "\nThere is an error in the requested command: " . $command . "\n\n";
			}
		}
		else if(pts_module_valid_user_command($command))
		{
			list($module, $module_command) = explode(".", $command);

			pts_module_manager::set_current_module($module);
			pts_module_run_user_command($module, $module_command, $pass_args);
			pts_module_manager::set_current_module(null);
		}

		pts_module_process("__post_option_process", $command);
		pts_set_assignment_next("PREV_COMMAND", $command);
		pts_assignment_manager::clear_all();
	}
	public static function terminal_width()
	{
		if(!pts_is_assignment("TERMINAL_WIDTH"))
		{
			$chars = -1;

			if(pts_client::executable_in_path("tput"))
			{
				$terminal_width = trim(shell_exec("tput cols 2>&1"));

				if(is_numeric($terminal_width) && $terminal_width > 1)
				{
					$chars = $terminal_width;
				}
			}

			pts_set_assignment("TERMINAL_WIDTH", $chars);
		}

		return pts_read_assignment("TERMINAL_WIDTH");
	}
	public static function user_hardware_software_reporting()
	{
		$hw_reporting = pts_strings::string_bool(pts_config::read_user_config(P_OPTION_HARDWARE_REPORTING, 0));
		$sw_reporting = pts_strings::string_bool(pts_config::read_user_config(P_OPTION_SOFTWARE_REPORTING, 0));

		if($hw_reporting == false && $sw_reporting == false)
		{
			return;
		}

		$hw = array();
		$sw = array();
		$pso = pts_storage_object::recover_from_file(PTS_CORE_STORAGE);

		if($hw_reporting)
		{
			$hw = array(
			"cpu" => phodevi::read_property("cpu", "model"),
			"cpu_count" => phodevi::read_property("cpu", "core-count"),
			"cpu_speed" => phodevi::read_property("cpu", "default-frequency") * 1000,
			"chipset" => phodevi::read_name("chipset"),
			"motherboard" => phodevi::read_name("motherboard"),
			"gpu" => phodevi::read_property("gpu", "model")
			);
			$hw_prev = $pso->read_object("global_reported_hw");
			$pso->add_object("global_reported_hw", $hw); 

			if(is_array($hw_prev))
			{
				$hw = array_diff_assoc($hw, $hw_prev);
			}
		}
		if($sw_reporting)
		{
			$sw = array(
			"os" => phodevi::read_property("system", "operating-system"),
			"os_architecture" => phodevi::read_property("system", "kernel-architecture"),
			"display_server" => phodevi::read_property("system", "display-server"),
			"display_driver" => pts_strings::first_in_string(phodevi::read_property("system", "display-driver-string")),
			"desktop" => pts_strings::first_in_string(phodevi::read_property("system", "desktop-environment")),
			"compiler" => phodevi::read_property("system", "compiler"),
			"file_system" => phodevi::read_property("system", "filesystem"),
			"screen_resolution" => phodevi::read_property("gpu", "screen-resolution-string")
			);
			$sw_prev = $pso->read_object("global_reported_sw");
			$pso->add_object("global_reported_sw", $sw); 

			if(is_array($sw_prev))
			{
				$sw = array_diff_assoc($sw, $sw_prev);
			}
		}

		$to_report = array_merge($hw, $sw);
		$pso->save_to_file(PTS_CORE_STORAGE);

		if(!empty($to_report))
		{
			pts_global_upload_hwsw_data($to_report);
		}				
	}
	public static function is_process_running($process)
	{
		if(IS_LINUX)
		{
			// Checks if process is running on the system
			$running = shell_exec("ps -C " . strtolower($process) . " 2>&1");
			$running = trim(str_replace(array("PID", "TTY", "TIME", "CMD"), "", $running));
		}
		else if(IS_SOLARIS)
		{
			// Checks if process is running on the system
			$ps = shell_exec("ps -ef 2>&1");
			$running = strpos($ps, " " . strtolower($process)) != false ? "TRUE" : null;
		}
		else if(pts_client::executable_in_path("ps") != false)
		{
			// Checks if process is running on the system
			$ps = shell_exec("ps -ax 2>&1");
			$running = strpos($ps, " " . strtolower($process)) != false ? "TRUE" : null;
		}
		else
		{
			$running = null;
		}

		return !empty($running);
	}
	public static function parse_value_string_double_identifier($value_string)
	{
		// i.e. with PRESET_OPTIONS="stream.run-type=Add"
		$values = array();

		foreach(explode(';', $value_string) as $preset)
		{
			if(count($preset = pts_strings::trim_explode('=', $preset)) == 2)
			{
				if(count($preset[0] = pts_strings::trim_explode('.', $preset[0])) == 2)
				{
					$values[$preset[0][0]][$preset[0][1]] = $preset[1];
				}
			}
		}

		return $values;
	}
	public static function create_temporary_file()
	{
		return tempnam(pts_client::temporary_directory(), 'PTS');
	}
	public static function temporary_directory()
	{
		if(PHP_VERSION_ID >= 50210)
		{
			$dir = sys_get_temp_dir();
		}
		else
		{
			$dir = "/tmp"; // Assume /tmp
		}

		return $dir;
	}
	public static function read_env($var)
	{
		static $vars = null;

		if(!isset($vars[$var]))
		{
			$vars[$var] = getenv($var);
		}

		return $vars[$var];
	}
	public static function pts_set_environmental_variable($name, $value)
	{
		// Sets an environmental variable
		return getenv($name) == false && putenv($name . "=" . $value);
	}
	public static function executable_in_path($executable)
	{
		static $cache = null;

		if(!isset($cache[$executable]))
		{
			$paths = explode(":", (($path = pts_client::read_env("PATH")) == false ? "/usr/bin:/usr/local/bin" : $path));
			$executable_path = false;

			foreach($paths as $path)
			{
				$path = pts_add_trailing_slash($path);

				if(is_executable($path . $executable))
				{
					$executable_path = $path . $executable;
					break;
				}
			}

			$cache[$executable] = $executable_path;
		}

		return $cache[$executable];
	}
	public static function display_web_page($URL, $alt_text = null, $default_open = false, $auto_open = false)
	{
		if(pts_read_assignment("AUTOMATED_MODE") || (pts_client::read_env("DISPLAY") == false && !IS_WINDOWS))
		{
			return;
		}

		// Launch the web browser
		$text = $alt_text == null ? "Do you want to view the results in your web browser" : $alt_text;

		if($auto_open == false)
		{
			if(!$default_open)
			{
				$view_results = pts_bool_question($text . " (y/N)?", false, "OPEN_BROWSER");
			}
			else
			{
				$view_results = pts_bool_question($text . " (Y/n)?", true, "OPEN_BROWSER");
			}
		}
		else
		{
			$view_results = true;
		}

		if($view_results)
		{
			static $browser = null;

			if($browser == null)
			{
				$config_browser = pts_config::read_user_config(P_OPTION_DEFAULT_BROWSER, null);

				if($config_browser != null && (is_executable($config_browser) || ($config_browser = pts_client::executable_in_path($config_browser))))
				{
					$browser = $config_browser;
				}
				else if(IS_WINDOWS)
				{
					$windows_browsers = array(
						'C:\Program Files (x86)\Mozilla Firefox\firefox.exe',
						'C:\Program Files\Internet Explorer\iexplore.exe'
						);

					foreach($windows_browsers as $browser_test)
					{
						if(is_executable($browser_test))
						{
							$browser = "\"$browser_test\"";
							break;
						}
					}

					if(substr($URL, 0, 1) == "\\")
					{
						$URL = "file:///C:" . str_replace('/', '\\', $URL);
					}
				}
				else
				{
					$possible_browsers = array("xdg-open", "epiphany", "firefox", "mozilla", "x-www-browser", "open");

					foreach($possible_browsers as &$b)
					{
						if(($b = pts_client::executable_in_path($b)))
						{
							$browser = $b;
							break;
						}
					}
				}
			}

			if($browser != null)
			{
				shell_exec($browser . " \"" . $URL . "\" &");
			}
			else
			{
				echo "\nNo Web Browser Was Found.\n";
			}
		}
	}
}

?>
