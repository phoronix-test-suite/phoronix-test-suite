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
		$hw_reporting = pts_string_bool(pts_config::read_user_config(P_OPTION_HARDWARE_REPORTING, 0));
		$sw_reporting = pts_string_bool(pts_config::read_user_config(P_OPTION_SOFTWARE_REPORTING, 0));

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
