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
	protected static $commands_to_run = array();

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
	public static function run_next($command, $pass_args = null, $set_assignments = null)
	{
		return array_push(self::$commands_to_run, new pts_command_run($command, $pass_args, $set_assignments));
	}
	public static function next_in_run_queue()
	{
		return array_shift(self::$commands_to_run);
	}
	public static function add_assignment_to_next_in_run_queue($assignment, $value = true)
	{
		if(($next_option = array_shift(self::$commands_to_run)) != null)
		{
			$next_option->add_preset_assignment($assignment, $value);
			array_unshift(self::$commands_to_run, $next_option);
		}
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
	public static function module_framework_init()
	{
		// Process initially called when PTS starts up

		// Check for modules to auto-load from the configuration file
		$load_modules = pts_config::read_user_config(P_OPTION_LOAD_MODULES, null);

		if(!empty($load_modules))
		{
			foreach(pts_strings::comma_explode($load_modules) as $module)
			{
				$module_r = pts_strings::trim_explode('=', $module);

				if(count($module_r) == 2)
				{
					// TODO: end up hooking this into pts_module::read_variable() rather than using the real env
					pts_client::set_environmental_variable($module_r[0], $module_r[1]);
				}
				else
				{
					pts_module_manager::attach_module($module);
				}
			}
		}

		// Check for modules to load manually in PTS_MODULES
		if(($load_modules = pts_client::read_env("PTS_MODULES")) !== false)
		{
			foreach(pts_strings::comma_explode($load_modules) as $module)
			{
				if(!pts_module_manager::is_module_attached($module))
				{
					pts_module_manager::attach_module($module);
				}
			}
		}

		// Detect modules to load automatically
		pts_module_manager::detect_modules_to_load();

		// Clean-up modules list
		pts_module_manager::clean_module_list();

		// Reset counter
		pts_module_manager::set_current_module(null);

		// Load the modules
		$module_store_list = array();
		foreach(pts_module_manager::attached_modules() as $module)
		{
			eval("\$module_store_vars = " . $module . "::\$module_store_vars;");

			if(is_array($module_store_vars))
			{
				foreach($module_store_vars as $store_var)
				{
					if(!in_array($store_var, $module_store_list))
					{
						array_push($module_store_list, $store_var);
					}
				}
			}
		}

		// Should any of the module options be saved to the results?
		foreach($module_store_list as $var)
		{
			$var_value = pts_client::read_env($var);

			if(!empty($var_value))
			{
				pts_module_manager::var_store_add($var, $var_value);
			}
		}

		pts_module_manager::module_process("__startup");
		define("PTS_STARTUP_TASK_PERFORMED", true);
		register_shutdown_function(array("pts_module_manager", "module_process"), "__shutdown");
	}
	public static function environmental_variables()
	{
		// The PTS environmental variables passed during the testing process, etc
		static $env_variables = null;

		if($env_variables == null)
		{
			$env_variables = array(
			"PTS_VERSION" => PTS_VERSION,
			"PTS_CODENAME" => PTS_CODENAME,
			"PTS_DIR" => PTS_PATH,
			"PHP_BIN" => PHP_BIN,
			"NUM_CPU_CORES" => phodevi::read_property("cpu", "core-count"),
			"NUM_CPU_JOBS" => (phodevi::read_property("cpu", "core-count") * 2),
			"SYS_MEMORY" => phodevi::read_property("memory", "capacity"),
			"VIDEO_MEMORY" => phodevi::read_property("gpu", "memory-capacity"),
			"VIDEO_WIDTH" => pts_arrays::first_element(phodevi::read_property("gpu", "screen-resolution")),
			"VIDEO_HEIGHT" => pts_arrays::last_element(phodevi::read_property("gpu", "screen-resolution")),
			"VIDEO_MONITOR_COUNT" => phodevi::read_property("monitor", "count"),
			"VIDEO_MONITOR_LAYOUT" => phodevi::read_property("monitor", "layout"),
			"VIDEO_MONITOR_SIZES" => phodevi::read_property("monitor", "modes"),
			"OPERATING_SYSTEM" => phodevi::read_property("system", "vendor-identifier"),
			"OS_VERSION" => phodevi::read_property("system", "os-version"),
			"OS_ARCH" => phodevi::read_property("system", "kernel-architecture"),
			"OS_TYPE" => OPERATING_SYSTEM,
			"THIS_RUN_TIME" => PTS_INIT_TIME,
			"DEBUG_REAL_HOME" => pts_client::user_home_directory()
			);

			if(!pts_client::executable_in_path("cc") && pts_client::executable_in_path("gcc"))
			{
				// This helps some test profiles build correctly if they don't do a cc check internally
				$env_variables["CC"] = "gcc";
			}
		}

		return $env_variables;
	}
	public static function user_run_save_variables()
	{
		static $runtime_variables = null;

		if($runtime_variables == null)
		{
			$runtime_variables = array(
			"VIDEO_RESOLUTION" => phodevi::read_property("gpu", "screen-resolution-string"),
			"VIDEO_CARD" => phodevi::read_name("gpu"),
			"VIDEO_DRIVER" => phodevi::read_property("system", "display-driver-string"),
			"OPERATING_SYSTEM" => phodevi::read_property("system", "operating-system"),
			"PROCESSOR" => phodevi::read_name("cpu"),
			"MOTHERBOARD" => phodevi::read_name("motherboard"),
			"CHIPSET" => phodevi::read_name("chipset"),
			"KERNEL_VERSION" => phodevi::read_property("system", "kernel"),
			"COMPILER" => phodevi::read_property("system", "compiler"),
			"HOSTNAME" => phodevi::read_property("system", "hostname")
			);
		}

		return $runtime_variables;
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
		if(empty($global_gsid) || pts_global::is_valid_gsid_format($global_gsid) == false)
		{
			// Global System ID for anonymous uploads, etc
			$global_gsid = pts_global::request_gsid();
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
	public static function setup_test_result_directory($save_to)
	{
		$save_to_dir = SAVE_RESULTS_DIR . $save_to;

		if(strpos(basename($save_to_dir), '.'))
		{
			$save_to_dir = dirname($save_to_dir);
		}

		if($save_to_dir != ".")
		{
			pts_file_io::mkdir($save_to_dir);
		}

		file_put_contents($save_to_dir . "/index.html", "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\"><html><head><title>Phoronix Test Suite</title><meta http-equiv=\"REFRESH\" content=\"0;url=composite.xml\"></HEAD><BODY></BODY></HTML>");

		return $save_to_dir;
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
		return ($pts_user = pts_global::account_user_name()) != "Default User" && !empty($pts_user) ? $pts_user : phodevi::read_property("system", "username");
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
		// TODO: possibly do something like posix_getpid() != pts_client::$startup_pid in case shutdown function is called from a child process

		// Generate Phodevi Smart Cache
		if(pts_client::read_env("NO_PHODEVI_CACHE") != 1)
		{
			if(pts_config::read_bool_config(P_OPTION_PHODEVI_CACHE, "TRUE"))
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
		return pts_config::read_bool_config(P_OPTION_USAGE_REPORTING, 0);
	}
	public static function test_support_check($identifier)
	{
		$test_supported = true;
		$test_profile = new pts_test_profile($identifier);

		if($test_profile->is_test_architecture_supported() == false)
		{
			pts_client::$display->test_run_error($identifier . " is not supported on this architecture: " . phodevi::read_property("system", "kernel-architecture"));
			$test_supported = false;
		}
		else if($test_profile->is_test_platform_supported() == false)
		{
			pts_client::$display->test_run_error($identifier . " is not supported by this operating system: " . OPERATING_SYSTEM);
			$test_supported = false;
		}
		else if($test_profile->is_core_version_supported() == false)
		{
			pts_client::$display->test_run_error($identifier . " is not supported by this version of the Phoronix Test Suite: " . PTS_VERSION);
			$test_supported = false;
		}

		return $test_supported;
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
	public static function save_test_result($save_to = null, $save_results = null, $render_graphs = true, $result_identifier = null)
	{
		// Saves PTS result file
		if(substr($save_to, -4) != ".xml")
		{
			$save_to .= ".xml";
		}

		$save_to_dir = pts_client::setup_test_result_directory($save_to);
	
		if($save_to == null || $save_results == null)
		{
			$bool = false;
		}
		else
		{
			$save_name = basename($save_to, ".xml");

			if($save_name == "composite" && $render_graphs)
			{
				pts_render::generate_result_file_graphs($save_results, $save_to_dir);
			}

			$bool = file_put_contents(SAVE_RESULTS_DIR . $save_to, $save_results);

			if($result_identifier != null && (pts_config::read_bool_config(P_OPTION_LOG_VSYSDETAILS, "TRUE") || pts_read_assignment("IS_PCQS_MODE") || pts_read_assignment("IS_BATCH_MODE") || pts_is_assignment("PHOROMATIC_TITLE")))
			{
				// Save verbose system information here
				pts_file_io::mkdir(($system_log_dir = $save_to_dir . "/system-logs/" . $result_identifier), 0777, true);

				// Backup system files
				// TODO: move out these files/commands to log out to respective Phodevi components so only what's relevant will be logged
				$system_log_files = array("/var/log/Xorg.0.log", "/proc/cpuinfo", "/proc/modules", "/etc/X11/xorg.conf");

				foreach($system_log_files as $file)
				{
					if(is_file($file))
					{
						// copy() can't be used in this case since it will result in a blank file for /proc/ file-system
						file_put_contents($system_log_dir . "/" . basename($file), file_get_contents($file));
					}
				}

				// Generate logs from system commands to backup
				$system_log_commands = array("lspci -vvnn", "sensors", "dmesg", "glxinfo", "system_profiler", "dpkg --list");

				foreach($system_log_commands as $command_string)
				{
					$command = explode(' ', $command_string);

					if(($command_bin = pts_client::executable_in_path($command[0])))
					{
						$cmd_output = shell_exec("cd " . dirname($command_bin) . " && ./" . $command_string . " 2>&1");
						file_put_contents($system_log_dir . "/" . $command[0], $cmd_output);
					}
				}
			}
		}

		return $bool;
	}
	public static function regenerate_graphs($result_file_identifier, $full_process_string = false)
	{
		$save_to_dir = pts_client::setup_test_result_directory($result_file_identifier);
		$generated_graphs = pts_render::generate_result_file_graphs($result_file_identifier, $save_to_dir);
		$generated = count($generated_graphs) > 0;

		if($generated && $full_process_string)
		{
			echo "\n" . $full_process_string . "\n";
			pts_set_assignment_next("PREV_SAVE_RESULTS_IDENTIFIER", $result_file_identifier);
			pts_client::display_web_page(SAVE_RESULTS_DIR . $result_file_identifier . "/index.html");
		}

		return $generated;
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
				$method_check = false;

				if(is_array($function_check) && count($function_check) == 2)
				{
					$method_check = $function_check[0];
					$function_check = $function_check[1];
				}

				if(substr($function_check, 0, 1) == '!')
				{
					$function_check = substr($function_check, 1);
					$return_fails_on = true;
				}
				else
				{
					$return_fails_on = false;
				}

				
				if($method_check != false)
				{
					if(!method_exists($method_check, $function_check))
					{
						echo "\nMethod check fails.\n";
						continue;
					}

					$function_check = array($method_check, $function_check);
				}
				else if(!function_exists($function_check))
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

		pts_module_manager::module_process("__pre_option_process", $command);

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
		else if(pts_module::valid_run_command($command))
		{
			list($module, $module_command) = explode(".", $command);

			pts_module_manager::set_current_module($module);
			pts_module_manager::run_command($module, $module_command, $pass_args);
			pts_module_manager::set_current_module(null);
		}

		pts_module_manager::module_process("__post_option_process", $command);
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
			else if(IS_WINDOWS)
			{
				// Need a better way to handle this
				$chars = 80;
			}

			pts_set_assignment("TERMINAL_WIDTH", $chars);
		}

		return pts_read_assignment("TERMINAL_WIDTH");
	}
	public static function user_hardware_software_reporting()
	{
		$hw_reporting = pts_config::read_bool_config(P_OPTION_HARDWARE_REPORTING, "FALSE");
		$sw_reporting = pts_config::read_bool_config(P_OPTION_SOFTWARE_REPORTING, "FALSE");

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
			pts_global::upload_hwsw_data($to_report);
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
		// Convert pts_client::environmental_variables() into shell export variable syntax
		$var_string = "";
		$extra_vars = ($extra_vars == null ? pts_client::environmental_variables() : array_merge(pts_client::environmental_variables(), $extra_vars));

		foreach(array_keys($extra_vars) as $key)
		{
			$var_string .= "export " . $key . "=" . $extra_vars[$key] . ";";
		}

		$var_string .= " ";

		return shell_exec($var_string . $exec);
	}
	public static function executable_in_path($executable)
	{
		static $cache = null;

		if(!isset($cache[$executable]))
		{
			$paths = pts_strings::colon_explode((($path = pts_client::read_env("PATH")) == false ? "/usr/bin:/usr/local/bin" : $path));
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
					$possible_browsers = array("epiphany", "firefox", "mozilla", "x-www-browser", "open");

					if(pts_client::executable_in_path("kfmclient") == false)
					{
						// Konqueror is bad with XSL, so if it looks like we are using KDE, don't use XDG
						array_unshift($possible_browsers, "xdg-open");
					}

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
		pts_suites::supported_suites();
	}
	public static function cache_test_calls()
	{
		pts_tests::supported_tests();
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
		pts_result_comparisons::process_reference_comparison_hashes(pts_client::generic_reference_system_comparison_ids(), array(), $original_test_hashes, $reference_tests, true);
	}
	public static function cache_generic_reference_systems_results()
	{
		$reference_cache_dir = is_dir("/var/cache/phoronix-test-suite/reference-comparisons/") ? "/var/cache/phoronix-test-suite/reference-comparisons/" : false;

		foreach(pts_client::generic_reference_system_comparison_ids() as $comparison_id)
		{
			if(!pts_is_test_result($comparison_id))
			{
				if($reference_cache_dir && is_readable($reference_cache_dir . $comparison_id . ".xml"))
				{
					// A cache is already available locally (likely from a PTS Live OS)
					pts_client::save_test_result($comparison_id . "/composite.xml", file_get_contents($reference_cache_dir . $comparison_id . ".xml"), false);
				}
				else
				{
					// Fetch from Phoronix Global
					pts_global::clone_global_result($comparison_id, false);
				}
			}
		}
	}
	public static function remove_saved_result_file($identifier)
	{
		pts_file_io::delete(SAVE_RESULTS_DIR . $identifier, null, true);
	}
	public static function saved_test_results()
	{
		$results = array();
		$ignore_ids = pts_client::generic_reference_system_comparison_ids();

		foreach(pts_file_io::glob(SAVE_RESULTS_DIR . "*/composite.xml") as $result_file)
		{
			$identifier = pts_extract_identifier_from_path($result_file);

			if(!in_array($identifier, $ignore_ids))
			{
				array_push($results, $identifier);
			}
		}

		return $results;
	}
	public static function generic_reference_system_comparison_ids()
	{
		static $comparison_ids = null;

		if($comparison_ids == null)
		{
			$comparison_ids = pts_strings::trim_explode("\n", pts_file_io::file_get_contents(STATIC_DIR . "lists/reference-system-comparisons.list"));

			foreach(explode(' ', pts_config::read_user_config(P_OPTION_EXTRA_REFERENCE_SYSTEMS, null)) as $reference_check)
			{
				if(pts_global::is_global_id($reference_check))
				{
					array_push($comparison_ids, $reference_check);
				}
			}
		}

		return $comparison_ids;
	}
}

?>
