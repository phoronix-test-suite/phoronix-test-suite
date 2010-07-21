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
	public static $display;
	protected static $command_execution_count = 0;
	protected static $lock_pointers = null;

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
	public static function get_command_exection_count()
	{
		return self::$command_execution_count;
	}
	public static function run_next($command, $pass_args = null, $set_assignments = "")
	{
		return pts_command_execution_manager::add_to_queue($command, $pass_args, $set_assignments);
	}
	public static function init()
	{
		pts_define_directories(); // Define directories

		if(QUICK_START)
		{
			return true;
		}

		self::basic_init_process(); // Initalize common / needed PTS start-up work
		self::core_storage_init_process();

		pts_config::init_files();
		define("TEST_ENV_DIR", pts_client::parse_home_directory(pts_config::read_user_config(P_OPTION_TEST_ENVIRONMENT, "~/.phoronix-test-suite/installed-tests/")));
		define("SAVE_RESULTS_DIR", pts_client::parse_home_directory(pts_config::read_user_config(P_OPTION_RESULTS_DIRECTORY, "~/.phoronix-test-suite/test-results/")));
		self::extended_init_process();

		return true;
	}
	private static function basic_init_process()
	{
		// Initialize The Phoronix Test Suite

		// PTS Defines
		define("PHP_BIN", pts_client::read_env("PHP_BIN"));
		define("PTS_INIT_TIME", time());

		if(!defined("PHP_VERSION_ID"))
		{
			// PHP_VERSION_ID is only available in PHP 5.2.6 and later
			$php_version = explode('.', PHP_VERSION);
			define("PHP_VERSION_ID", ($php_version[0] * 10000 + $php_version[1] * 100 + $php_version[2]));
		}

		$dir_init = array(PTS_USER_DIR);
		foreach($dir_init as $dir)
		{
			pts_file_io::mkdir($dir);
		}

		phodevi::initial_setup();

		//define("IS_PTS_LIVE", phodevi::read_property("system", "username") == "ptslive");
	}
	public static function init_display_mode()
	{
		switch((($env_mode = pts_read_assignment("DISPLAY_MODE")) != false || ($env_mode = pts_client::read_env("PTS_DISPLAY_MODE")) != false ? $env_mode : pts_config::read_user_config(P_OPTION_DISPLAY_MODE, "DEFAULT")))
		{
			case "BASIC":
				self::$display = new pts_basic_display_mode();
				break;
			case "BATCH":
			case "CONCISE":
				self::$display = new pts_concise_display_mode();
				break;
			case "DEFAULT":
			default:
				self::$display = new pts_concise_display_mode();
				break;
		}
	}
	private static function extended_init_process()
	{
		// Extended Initalization Process
		$directory_check = array(TEST_ENV_DIR, SAVE_RESULTS_DIR, XML_SUITE_LOCAL_DIR, 
		TEST_RESOURCE_LOCAL_DIR, XML_PROFILE_LOCAL_DIR, MODULE_LOCAL_DIR, MODULE_DATA_DIR, DEFAULT_DOWNLOAD_CACHE_DIR);

		foreach($directory_check as $dir)
		{
			pts_file_io::mkdir($dir);
		}

		// Setup PTS Results Viewer
		pts_file_io::mkdir(SAVE_RESULTS_DIR . "pts-results-viewer");

		foreach(pts_file_io::glob(RESULTS_VIEWER_DIR . "*.*") as $result_viewer_file)
		{
			copy($result_viewer_file, SAVE_RESULTS_DIR . "pts-results-viewer/" . basename($result_viewer_file));
		}

		copy(STATIC_DIR . "images/pts-106x55.png", SAVE_RESULTS_DIR . "pts-results-viewer/pts-106x55.png");

		// Setup ~/.phoronix-test-suite/xsl/
		pts_file_io::mkdir(PTS_USER_DIR . "xsl/");
		copy(STATIC_DIR . "xsl/pts-test-installation-viewer.xsl", PTS_USER_DIR . "xsl/" . "pts-test-installation-viewer.xsl");
		copy(STATIC_DIR . "xsl/pts-user-config-viewer.xsl", PTS_USER_DIR . "xsl/" . "pts-user-config-viewer.xsl");
		copy(STATIC_DIR . "images/pts-308x160.png", PTS_USER_DIR . "xsl/" . "pts-logo.png");

		// Load the defintions now since if you run "phoronix-test-suite run TEST It will fail" since test-profile.xml is not
		// defined when using pts_test_read_xml() the first time
		pts_loader::load_definitions("test-profile.xml");
		pts_loader::load_definitions("test-suite.xml");	
		pts_loader::load_definitions("test-installation.xml");
		pts_loader::load_definitions("module-settings.xml");

		// Compatibility for importing old module configuration settings from pre PTS 2.6 into new structures
		if(is_file(PTS_USER_DIR . "modules-config.xml"))
		{
			pts_compatibility::pts_convert_pre_pts_26_module_settings();
		}

		pts_client::init_display_mode();
	}
	private static function core_storage_init_process()
	{
		$pso = pts_storage_object::recover_from_file(PTS_CORE_STORAGE);

		if($pso == false)
		{
			$pso = new pts_storage_object(true, true);
		}

		// Last Run Processing
		//$last_core_version = $pso->read_object("last_core_version");
		// do something here with $last_core_version if you want that information
		$pso->add_object("last_core_version", PTS_CORE_VERSION); // PTS version last run

		//$last_pts_version = $pso->read_object("last_pts_version");
		// do something here with $last_pts_version if you want that information
		$pso->add_object("last_pts_version", PTS_VERSION); // PTS version last run

		// Last Run Processing
		$last_run = $pso->read_object("last_run_time");
		define("IS_FIRST_RUN_TODAY", (substr($last_run, 0, 10) != date("Y-m-d")));

		$pso->add_object("last_run_time", date("Y-m-d H:i:s")); // Time PTS was last run

		// Phoronix Global - GSID
		$global_gsid = $pso->read_object("global_system_id");
		if(empty($global_gsid) || !pts_global_gsid_valid($global_gsid))
		{
			// Global System ID for anonymous uploads, etc
			$global_gsid = pts_global_request_gsid();
		}

		define("PTS_GSID", $global_gsid);
		$pso->add_object("global_system_id", $global_gsid); // GSID

		// User Agreement Checking
		$agreement_cs = $pso->read_object("user_agreement_cs");

		$pso->add_object("user_agreement_cs", $agreement_cs); // User agreement check-sum

		// Phodevi Cache Handling
		$phodevi_cache = $pso->read_object("phodevi_smart_cache");

		if($phodevi_cache instanceOf phodevi_cache && pts_client::read_env("NO_PHODEVI_CACHE") != 1)
		{
			$phodevi_cache = $phodevi_cache->restore_cache(PTS_USER_DIR, PTS_CORE_VERSION);
			phodevi::set_device_cache($phodevi_cache);
		}

		// Archive to disk
		$pso->save_to_file(PTS_CORE_STORAGE);
	}
	public static function user_agreement_check($command)
	{
		$pso = pts_storage_object::recover_from_file(PTS_CORE_STORAGE);
		$config_md5 = $pso->read_object("user_agreement_cs");
		$current_md5 = md5_file(PTS_PATH . "pts-core/user-agreement.txt");

		if($config_md5 != $current_md5 || pts_config::read_user_config(P_OPTION_USAGE_REPORTING, "UNKNOWN") == "UNKNOWN")
		{
			$prompt_in_method = pts_client::check_command_for_function($command, "pts_user_agreement_prompt");
			$user_agreement = file_get_contents(PTS_PATH . "pts-core/user-agreement.txt");

			if($prompt_in_method)
			{
				$user_agreement_return = call_user_func(array($command, "pts_user_agreement_prompt"), $user_agreement);

				if(is_array($user_agreement_return))
				{
					if(count($user_agreement_return) == 3)
					{
						list($agree, $usage_reporting, $hwsw_reporting) = $user_agreement_return;
					}
					else
					{
						$agree = array_shift($user_agreement_return);
						$usage_reporting = -1;
						$hwsw_reporting = -1;
					}
				}
				else
				{
					$agree = $user_agreement_return;
					$usage_reporting = -1;
					$hwsw_reporting = -1;
				}
			}

			if($prompt_in_method == false || $usage_reporting == -1 || $hwsw_reporting == -1)
			{
				pts_client::$display->generic_heading("User Agreement");
				echo wordwrap($user_agreement, 65);
				$agree = pts_user_io::prompt_bool_input("Do you agree to these terms and wish to proceed", true);
				$usage_reporting = $agree ? pts_user_io::prompt_bool_input("Enable anonymous usage / statistics reporting", true) : -1;
				$hwsw_reporting = $agree ? pts_user_io::prompt_bool_input("Enable anonymous statistical reporting of installed software / hardware", true) : -1;
			}

			if($agree)
			{
				echo "\n";
				$pso->add_object("user_agreement_cs", $current_md5);
				$pso->save_to_file(PTS_CORE_STORAGE);
			}
			else
			{
				pts_client::exit_client("In order to run the Phoronix Test Suite, you must agree to the listed terms.");
			}

			pts_config::user_config_generate(array(
				P_OPTION_USAGE_REPORTING => pts_config::bool_to_string($usage_reporting),
				P_OPTION_HARDWARE_REPORTING => pts_config::bool_to_string($hwsw_reporting),
				P_OPTION_SOFTWARE_REPORTING => pts_config::bool_to_string($hwsw_reporting)
				));
		}
	}
	public static function remove_installed_test($identifier)
	{
		pts_file_io::delete(TEST_ENV_DIR . $identifier, null, true);
	}
	public static function exit_client($string = null, $exit_status = 0)
	{
		// Exit the Phoronix Test Suite client
		define("PTS_EXIT", 1);

		if($string != null)
		{
			echo "\n" . $string . "\n";
		}

		exit($exit_status);
	}
	public static function current_user()
	{
		// Current system user
		return ($pts_user = pts_global_user_name()) != "Default User" && !empty($pts_user) ? $pts_user : phodevi::read_property("system", "username");
	}
	public static function user_home_directory()
	{
		// Gets the system user's home directory
		static $userhome = null;

		if($userhome == null)
		{
			if(function_exists("posix_getpwuid") && function_exists("posix_getuid"))
			{
				$userinfo = posix_getpwuid(posix_getuid());
				$userhome = $userinfo["dir"];
			}
			else if(($home = pts_client::read_env("HOME")) || ($home = pts_client::read_env("HOMEPATH")))
			{
				$userhome = $home;
			}
			else
			{
				echo "\nERROR: Can't find home directory!\n";
				$userhome = null;
			}

			$userhome = pts_strings::add_trailing_slash($userhome);
		}

		return $userhome;
	}
	public static function parse_home_directory($path)
	{
		// Find home directory if needed
		if(strpos($path, "~/") !== false)
		{
			$path = str_replace("~/", pts_client::user_home_directory(), $path);
		}

		return pts_strings::add_trailing_slash($path);
	}
	public static function process_shutdown_tasks()
	{
		// Generate Phodevi Smart Cache
		if(pts_client::read_env("NO_PHODEVI_CACHE") != 1)
		{
			if(pts_strings::string_bool(pts_config::read_user_config(P_OPTION_PHODEVI_CACHE, "TRUE")))
			{
				pts_storage_object::set_in_file(PTS_CORE_STORAGE, "phodevi_smart_cache", phodevi::get_phodevi_cache_object(PTS_USER_DIR, PTS_CORE_VERSION));
			}
			else
			{
				pts_storage_object::set_in_file(PTS_CORE_STORAGE, "phodevi_smart_cache", null);
			}
		}
	}
	public static function do_anonymous_usage_reporting()
	{
		return pts_strings::string_bool(pts_config::read_user_config(P_OPTION_USAGE_REPORTING, 0));
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

		pts_file_io::unlink($lock_file);
		unset(self::$lock_pointers[self::$command_execution_count][$lock_file]);
	}
	public static function check_command_for_function($option, $check_function)
	{
		$in_option = false;

		if(is_file(COMMAND_OPTIONS_DIR . $option . ".php"))
		{
			if(!class_exists($option, false))
			{
				pts_loader::load_run_option($option);
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
			pts_loader::load_run_option($command);
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
					pts_client::$display->generic_error($argument_check->get_error_string());
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
	public static function shell_exec($exec, $extra_vars = null)
	{
		// Same as shell_exec() but with the PTS env variables added in
		return shell_exec(pts_variables_export_string($extra_vars) . $exec);
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
				$path = pts_strings::add_trailing_slash($path);

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
			$view_results = pts_user_io::prompt_bool_input($text, $default_open, "OPEN_BROWSER");
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
	public static function cache_suite_calls()
	{
		pts_supported_suites_array();
		pts_suite_name_to_identifier(-1);
	}
	public static function cache_test_calls()
	{
		pts_tests::supported_tests();
		pts_test_name_to_identifier(-1);
	}
	public static function cache_hardware_calls()
	{
		phodevi::system_hardware(true);
		phodevi::supported_sensors();
		phodevi::unsupported_sensors();
	}
	public static function cache_software_calls()
	{
		phodevi::system_software(true);
	}
	public static function cache_generic_reference_systems()
	{
		$original_test_hashes = array();
		$reference_tests = array();
		pts_result_comparisons::process_reference_comparison_hashes(pts_generic_reference_system_comparison_ids(), array(), $original_test_hashes, $reference_tests, true);
	}
	public static function cache_generic_reference_systems_results()
	{
		$reference_cache_dir = is_dir("/var/cache/phoronix-test-suite/reference-comparisons/") ? "/var/cache/phoronix-test-suite/reference-comparisons/" : false;

		foreach(pts_generic_reference_system_comparison_ids() as $comparison_id)
		{
			if(!pts_is_test_result($comparison_id))
			{
				if($reference_cache_dir && is_readable($reference_cache_dir . $comparison_id . ".xml"))
				{
					// A cache is already available locally (likely from a PTS Live OS)
					pts_save_result($comparison_id . "/composite.xml", file_get_contents($reference_cache_dir . $comparison_id . ".xml"), false);
				}
				else
				{
					// Fetch from Phoronix Global
					pts_clone_from_global($comparison_id, false);
				}
			}
		}
	}
}

?>
