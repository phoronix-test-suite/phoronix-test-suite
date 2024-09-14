<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2024, Phoronix Media
	Copyright (C) 2008 - 2024, Michael Larabel

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
	public static $display = false;
	public static $pts_logger = false;
	public static $web_result_viewer_active = false;
	public static $web_result_viewer_access_key = false;
	public static $has_used_modern_result_viewer = false;
	public static $last_browser_launch_time = 0;
	public static $last_browser_duration = 0;
	public static $last_result_view_url = null;
	public static $skip_log_file_type_checks = false;
	protected static $lock_pointers = null;
	protected static $phoromatic_servers = array();
	protected static $debug_mode = false;
	protected static $override_pts_env_vars = array();
	protected static $sent_command = null;
	protected static $time_pts_last_launch = null;
	private static $current_command = null;
	private static $forked_pids = array();
	private static $download_speed_average_count = -1;
	private static $download_speed_average_speed = -1;

	public static function create_lock($lock_file)
	{
		if(isset(self::$lock_pointers[$lock_file]) || is_writable(dirname($lock_file)) == false)
		{
			return false;
		}
		else if(disk_free_space(dirname($lock_file)) < 1024)
		{
			echo PHP_EOL . 'Lock creation failed due to lack of disk space.' . PHP_EOL;
			return false;
		}

		self::$lock_pointers[$lock_file] = fopen($lock_file, 'w');
		chmod($lock_file, 0644);
		return self::$lock_pointers[$lock_file] != false && (phodevi::is_windows() || flock(self::$lock_pointers[$lock_file], LOCK_EX | LOCK_NB));
	}
	public static function current_time()
	{
		$current_time = time();

		// Calculate time based on offset from last OpenBenchmarking.org check and then the time since that file was modified
		$repo_index = pts_openbenchmarking::read_repository_index('pts');
		if($repo_index && isset($repo_index['main']['generated']))
		{
			$repo_time = $repo_index['main']['generated'];
			if(($index = pts_openbenchmarking::is_repository('pts')))
			{
				$last_modified = filemtime($index);
				$time_since_modify = time() - $last_modified;
			}

			$calculated_time = $repo_time + $time_since_modify;
			if($calculated_time > $current_time)
			{
				$current_time = $calculated_time;
			}
		}

		// Fallback to checking time since the PTS release to see if date computed is behind that
		if(($fallback_time = strtotime(PTS_RELEASE_DATE . ' ' . date('H:i:s'))) > $current_time)
		{
			$current_time = $fallback_time;
		}

		return $current_time;
	}
	public static function possible_sub_commands()
	{
		static $options = null;

		if(empty($options))
		{
			$options = array();
			foreach(pts_file_io::glob(PTS_COMMAND_PATH . '*.php') as $option_php)
			{
				$name = str_replace('_', '-', basename($option_php, '.php'));
				if(!in_array(pts_strings::first_in_string($name, '-'), array('dump', 'debug', 'task')))
				{
					$options[] = $name;
				}
			}
		}

		return $options;
	}
	public static function is_locked($lock_file)
	{
		$fp = fopen($lock_file, 'w');
		$is_locked = $fp && !flock($fp, LOCK_EX | LOCK_NB);
		$fp && fclose($fp);

		return $is_locked;
	}
	public static function release_lock($lock_file)
	{
		// Remove lock
		if(isset(self::$lock_pointers[$lock_file]) == false)
		{
			pts_file_io::unlink($lock_file);
			return false;
		}

		if(is_resource(self::$lock_pointers[$lock_file]))
		{
			fclose(self::$lock_pointers[$lock_file]);
		}

		pts_file_io::unlink($lock_file);
		unset(self::$lock_pointers[$lock_file]);
	}
	public static function write_to_lock($lock_file, $contents)
	{
		if(isset(self::$lock_pointers[$lock_file]) && is_resource(self::$lock_pointers[$lock_file]))
		{
			fwrite(self::$lock_pointers[$lock_file], $contents);
			fflush(self::$lock_pointers[$lock_file]);
		}
	}
	public static function init()
	{
		pts_core::init();
		pts_define('PTS_COMMAND_PATH', PTS_CORE_PATH . 'commands/');

		if(defined('QUICK_START') && QUICK_START)
		{
			return true;
		}


		if(function_exists('cli_set_process_title') && PHP_OS == 'Linux')
		{
			cli_set_process_title('Phoronix Test Suite');
		}

		pts_define('PHP_BIN', getenv('PHP_BIN'));

		$dir_init = array(PTS_USER_PATH);
		foreach($dir_init as $dir)
		{
			pts_file_io::mkdir($dir);
		}

		if(PTS_IS_CLIENT)
		{
			pts_network::client_startup();
		}

		self::core_storage_init_process();
		$p = pts_config::read_path_config('PhoronixTestSuite/Options/Installation/EnvironmentDirectory', '~/.phoronix-test-suite/installed-tests/');
		if(phodevi::is_windows())
		{
			$p = str_replace('/', DIRECTORY_SEPARATOR, $p);
		}
		pts_define('PTS_TEST_INSTALL_DEFAULT_PATH', $p);

		pts_define('PTS_SAVE_RESULTS_PATH', pts_config::read_path_config('PhoronixTestSuite/Options/Testing/ResultsDirectory', '~/.phoronix-test-suite/test-results/'));
		self::extended_init_process();

		$openbenchmarking = pts_storage_object::read_from_file(PTS_CORE_STORAGE, 'openbenchmarking');
		$openbenchmarking_account_settings = pts_storage_object::read_from_file(PTS_CORE_STORAGE, 'openbenchmarking_account_settings');

		if($openbenchmarking != null)
		{
			// OpenBenchmarking.org Account
			pts_openbenchmarking_client::init_account($openbenchmarking, $openbenchmarking_account_settings);
		}

		return true;
	}
	private static function extended_init_process()
	{
		// Extended Initalization Process
		$directory_check = array(
			PTS_TEST_INSTALL_DEFAULT_PATH,
			PTS_SAVE_RESULTS_PATH,
			pts_module::module_local_path(),
			pts_module::module_data_path(),
			PTS_DOWNLOAD_CACHE_PATH,
			PTS_OPENBENCHMARKING_SCRATCH_PATH,
			PTS_TEST_PROFILE_PATH,
			PTS_TEST_SUITE_PATH,
			PTS_TEST_PROFILE_PATH . 'local/',
			PTS_TEST_SUITE_PATH . 'local/'
			);
		
		$tp_pts_dir_not = is_dir(PTS_TEST_PROFILE_PATH . 'pts/');

		foreach($directory_check as $dir)
		{
			pts_file_io::mkdir($dir);
		}

		// Copy files (without overwrite) from internal OB program cache if present, to help those without Internet
		if(!phodevi::is_windows() && (FIRST_RUN_ON_PTS_UPGRADE || !$tp_pts_dir_not))
		{
			if(PTS_INTERNAL_OB_CACHE && is_dir(PTS_INTERNAL_OB_CACHE . 'test-profiles'))
			{
				pts_file_io::copy(PTS_INTERNAL_OB_CACHE . 'test-profiles', PTS_TEST_PROFILE_PATH, true);
			}
			if(PTS_INTERNAL_OB_CACHE && is_dir(PTS_INTERNAL_OB_CACHE . 'test-suites'))
			{
				pts_file_io::copy(PTS_INTERNAL_OB_CACHE . 'test-suites', PTS_TEST_SUITE_PATH, true);
			}
			if(PTS_INTERNAL_OB_CACHE && is_dir(PTS_INTERNAL_OB_CACHE . 'openbenchmarking.org'))
			{
				pts_file_io::copy(PTS_INTERNAL_OB_CACHE . 'openbenchmarking.org', PTS_OPENBENCHMARKING_SCRATCH_PATH, true);

				if(!pts_network::internet_support_available())
				{
					// Only overwrite the OB index files if it's newer
					foreach(pts_file_io::glob(PTS_INTERNAL_OB_CACHE . 'openbenchmarking.org/*.index') as $cache_index_file)
					{
						$index_file_name = basename($cache_index_file);
						if(is_file(PTS_OPENBENCHMARKING_SCRATCH_PATH . $index_file_name))
						{
							$current_version = pts_openbenchmarking::get_generated_time_from_index(PTS_OPENBENCHMARKING_SCRATCH_PATH . $index_file_name);
							$cached_version = pts_openbenchmarking::get_generated_time_from_index($cache_index_file);
							if($cached_version > $current_version)
							{
								copy($cache_index_file, PTS_OPENBENCHMARKING_SCRATCH_PATH . $index_file_name);
							}
						}
					}
				}
			}
		}

		pts_client::init_display_mode();
	}
	public static function module_framework_init()
	{
		// Process initially called when PTS starts up
		// Check for modules to auto-load from the configuration file
		$load_modules = pts_config::read_user_config('PhoronixTestSuite/Options/Modules/AutoLoadModules', null);

		if(!empty($load_modules))
		{
			foreach(pts_strings::comma_explode($load_modules) as $module)
			{
				$module_r = pts_strings::trim_explode('=', $module);

				if(count($module_r) == 2)
				{
					putenv($module_r[0] . '=' . $module_r[1]);
					pts_env::set($module_r[0], $module_r[1]);
				}
				else
				{
					pts_module_manager::attach_module($module);
				}
			}
		}

		// Check for modules to load manually in PTS_MODULES
		if(($load_modules = pts_env::read('PTS_MODULES')) !== false)
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
			$class_vars = get_class_vars($module);
			$module_store_vars = isset($class_vars['module_store_vars']) ? $class_vars['module_store_vars'] : null;

			if(is_array($module_store_vars))
			{
				foreach($module_store_vars as $store_var)
				{
					if(!in_array($store_var, $module_store_list))
					{
						$module_store_list[] = $store_var;
					}
				}
			}
		}

		// Should any of the module options be saved to the results?
		foreach($module_store_list as $var)
		{
			$var_value = pts_env::read($var);

			if(!empty($var_value))
			{
				pts_module_manager::var_store_add($var, $var_value);
			}
		}

		pts_module_manager::module_process('__startup');
		pts_define('PTS_STARTUP_TASK_PERFORMED', true);
		register_shutdown_function(array('pts_module_manager', 'module_process'), '__shutdown');
	}
	public static function environment_variables()
	{
		// The PTS environment variables passed during the testing process, etc
		static $env_variables = null;

		if($env_variables == null)
		{
			$physical_cores = phodevi::read_property('cpu', 'physical-core-count');
			$i = 0;
			do
			{
				$i++;
				$nearest_cube = pow($i, 3);
			}
			while($physical_cores >= pow(($i + 1), 3));

			$tpc = phodevi::read_property('cpu', 'core-count') / $physical_cores;
			if($tpc < 1 || !is_integer($tpc))
			{
				$tpc = 1;
			}

			$env_variables = array(
			'PTS_VERSION' => PTS_VERSION,
			'PTS_DIR' => PTS_PATH,
			'PTS_LAUNCHER' => getenv('PTS_LAUNCHER'),
			'PHP_BIN' => PHP_BIN,
			'NUM_CPU_CORES' => phodevi::read_property('cpu', 'core-count'),
			'OMP_NUM_THREADS' => phodevi::read_property('cpu', 'core-count'),
			'NUM_CPU_PHYSICAL_CORES' => $physical_cores,
			'NUM_CPU_PHYSICAL_CORES_CUBE' => $nearest_cube,
			'CPU_THREADS_PER_CORE' => $tpc,
			'NUM_CPU_NODES' => phodevi::read_property('cpu', 'node-count'),
			'NUM_CPU_JOBS' => (phodevi::read_property('cpu', 'core-count') * 2),
			'SYS_MEMORY' => phodevi::read_property('memory', 'capacity'),
			'VIDEO_MEMORY' => phodevi::read_property('gpu', 'memory-capacity'),
			'VIDEO_WIDTH' => pts_arrays::first_element(phodevi::read_property('gpu', 'screen-resolution')),
			'VIDEO_HEIGHT' => pts_arrays::last_element(phodevi::read_property('gpu', 'screen-resolution')),
			'VIDEO_MONITOR_COUNT' => phodevi::read_property('monitor', 'count'),
			'VIDEO_MONITOR_LAYOUT' => phodevi::read_property('monitor', 'layout'),
			'VIDEO_MONITOR_SIZES' => phodevi::read_property('monitor', 'modes'),
			'OPERATING_SYSTEM' => phodevi::read_property('system', 'vendor-identifier'),
			'OS_VERSION' => phodevi::read_property('system', 'os-version'),
			'OS_ARCH' => phodevi::read_property('system', 'kernel-architecture'),
			'OS_TYPE' => phodevi::os_under_test(),
			'CPU_FAMILY' => (($family = phodevi::read_property('cpu', 'core-family-name')) != '' ? str_replace(' ', '', strtolower($family)) : ''),
			'THIS_RUN_TIME' => PTS_INIT_TIME,
			'DEBUG_REAL_HOME' => pts_core::user_home_directory(),
			'DEBUG_PATH' => pts_client::get_path(),
			'SYSTEM_TYPE_ID' => phodevi_base::determine_system_type(phodevi::system_hardware(), phodevi::system_software()),
			'SYSTEM_TYPE' => phodevi_base::system_type_to_string(phodevi_base::determine_system_type(phodevi::system_hardware(), phodevi::system_software())),
			'TERMINAL_WIDTH' => pts_client::terminal_width(),
			'C_CXX_FLAGS_DEFAULT' => '-O3 -march=native', // mostly for future use
			'GPU_DEVICE_ID' => phodevi::read_property('gpu', 'device-id'),
			//'PATH' => pts_client::get_path()
			);

			if(!pts_client::executable_in_path('cc') && getenv('CC') == false)
			{
				// This helps some test profiles build correctly if they don't do a cc check internally
				if(pts_client::executable_in_path('gcc'))
				{
					$env_variables['CC'] = 'gcc';
				}
				else if(pts_client::executable_in_path('clang'))
				{
					$env_variables['CC'] = 'clang';
				}
			}
		}

		return array_merge($env_variables, self::$override_pts_env_vars);
	}
	public static function override_pts_env_var($name, $value)
	{
		self::$override_pts_env_vars[$name] = $value;
	}
	public static function unset_pts_env_var_override($name)
	{
		if(isset(self::$override_pts_env_vars[$name]))
		{
			unset(self::$override_pts_env_vars[$name]);
		}
	}
	public static function test_install_root_path()
	{
		$env_override_install_path = pts_env::read('PTS_TEST_INSTALL_ROOT_PATH');
		if($env_override_install_path != false && is_dir($env_override_install_path) && is_writable($env_override_install_path))
		{
			return $env_override_install_path;
		}
		else
		{
			if(!defined('PTS_TEST_INSTALL_DEFAULT_PATH'))
			{
				$p = pts_config::read_path_config('PhoronixTestSuite/Options/Installation/EnvironmentDirectory', '~/.phoronix-test-suite/installed-tests/');
				if(phodevi::is_windows())
				{
					$p = str_replace('/', DIRECTORY_SEPARATOR, $p);
				}

				pts_define('PTS_TEST_INSTALL_DEFAULT_PATH', $p);
			}

			return PTS_TEST_INSTALL_DEFAULT_PATH;
		}
	}
	public static function download_cache_path()
	{
		return pts_strings::add_trailing_slash(pts_config::read_path_config('PhoronixTestSuite/Options/Installation/CacheDirectory', PTS_DOWNLOAD_CACHE_PATH));
	}
	public static function supports_colored_text_output()
	{
		static $supports = -1;

		if($supports == -1)
		{
			if(pts_env::read('NO_COLOR'))
			{
				$supported = false;
			}
			else
			{
				// early init process check with PTS_IS_DAEMONIZED_SERVER_PROCESS guard
				$config_color_option = !defined('PTS_IS_DAEMONIZED_SERVER_PROCESS') ? 'AUTO' : pts_config::read_user_config('PhoronixTestSuite/Options/General/ColoredConsole', 'AUTO');

				switch(strtoupper($config_color_option))
				{
					case 'TRUE':
						$supported = true;
						break;
					case 'FALSE':
						$supported = false;
						break;
					case 'AUTO':
					default:
						$supported = (function_exists('posix_isatty') && defined('STDOUT') && posix_isatty(STDOUT)) || (PTS_IS_CLIENT && (getenv('LS_COLORS') || getenv('CLICOLOR'))) || (phodevi::is_windows() && strstr(phodevi::read_property('system', 'operating-system'), 'Windows 8') === false && strstr(phodevi::read_property('system', 'operating-system'), 'Windows 7') === false);
						break;
				}
			}
			$supports = $supported;
		}

		return $supports;
	}
	public static function hex_color_to_string($hex)
	{
		$colors = array();
		list($colors['red'], $colors['green'], $colors['blue']) = sscanf($hex, "#%02x%02x%02x");
		if($colors['red'] > 240 && $colors['green'] > 210)
		{
			return 'yellow'; // this works for Arm color, etc
		}
		return array_search(max($colors), $colors);
	}
	public static function cli_colored_text($str, $color, $bold = false)
	{
		if(!self::supports_colored_text_output() || empty($color))
		{
			return $str;
		}

		$attribute = ($bold ? '1' : '0');
		$colors = array(
			'black' => $attribute . ';30',
			'gray' => '1;30', // gray not bold doesn't look good in all consoles
			'blue' => $attribute . ';34',
			'magenta' => $attribute . ';35',
			'green' => $attribute . ';32',
			'yellow' => $attribute . ';33',
			'red' => $attribute . ';31',
			'cyan' => $attribute . ';36',
			'white' => $attribute . ';37',
			);

		if(!isset($colors[$color]))
		{
			return $str;
		}

		return "\033[" . $colors[$color] . 'm' . $str . "\033[0m";
	}
	public static function cli_just_bold($str)
	{
		if(!self::supports_colored_text_output())
		{
			return $str;
		}

		return "\033[1m$str\033[0m";
	}
	public static function cli_just_italic($str)
	{
		if(!self::supports_colored_text_output())
		{
			return $str;
		}

		return "\e[3m$str\e[0m";
	}
	public static function cli_just_underline($str)
	{
		if(!self::supports_colored_text_output())
		{
			return $str;
		}

		return "\e[4m$str\e[0m";
	}
	public static function save_test_result($save_to = null, $save_results = null, $render_graphs = true, $result_identifier = null)
	{
		// Saves PTS result file
		if(substr($save_to, -4) != '.xml')
		{
			$save_to .= '.xml';
		}
		$save_to = str_replace(PTS_SAVE_RESULTS_PATH, '', $save_to);

		$save_to_dir = pts_client::setup_test_result_directory($save_to);

		if($save_to == null || $save_results == null)
		{
			$bool = false;
		}
		else
		{
			$save_name = basename($save_to, '.xml');

			$bool = file_put_contents(PTS_SAVE_RESULTS_PATH . $save_to, $save_results);

			if($result_identifier != null && pts_config::read_bool_config('PhoronixTestSuite/Options/Testing/SaveSystemLogs', 'TRUE'))
			{
				// Save verbose system information here
				//$system_log_dir = $result_file->get_system_log_dir($result_identifier, false);
				$system_log_dir = $save_to_dir . '/system-logs/' . pts_strings::simplify_string_for_file_handling($result_identifier) . '/';
				pts_file_io::mkdir($system_log_dir, 0777, true);

				// Backup system files
				// TODO: move out these files/commands to log out to respective Phodevi components so only what's relevant will be logged
				$system_log_files = array(
					'/var/log/Xorg.0.log',
					'/proc/cpuinfo',
					'/proc/meminfo',
					'/proc/modules',
					'/proc/mounts',
					'/proc/cmdline',
					'/proc/version',
					'/proc/mdstat',
					'/proc/lock_stat',
					'/etc/X11/xorg.conf',
					'/sys/kernel/debug/dri/0/radeon_pm_info',
					'/sys/kernel/debug/dri/0/i915_capabilities',
					'/sys/kernel/debug/dri/0/i915_cur_delayinfo',
					'/sys/kernel/debug/dri/0/i915_drpc_info',
					'/sys/devices/system/cpu/cpu0/cpufreq/scaling_available_frequencies',
					);

				/*
				if(phodevi::is_linux())
				{
					// the kernel config file might just be too large to upload for now
					$system_log_files[] = '/boot/config-' . php_uname('r');
				}
				*/

				foreach($system_log_files as $file)
				{
					if(is_file($file) && is_readable($file) && filesize($file) < 1000000)
					{
						// copy() can't be used in this case since it will result in a blank file for /proc/ file-system
						$file_contents = file_get_contents($file);
						$file_contents = pts_strings::remove_line_timestamps($file_contents);
						file_put_contents($system_log_dir . basename($file), $file_contents);
					}
				}

				$kconfig = null;
				if(is_file('/proc/config.gz') && pts_client::executable_in_path('zcat'))
				{
					$kconfig = shell_exec('zcat /proc/config.gz');
				}
				else if(pts_client::executable_in_path('uname') && ($uname_r = trim(shell_exec('uname -r 2>&1'))) && is_file('/boot/config-' . $uname_r))
				{
					$kconfig = file_get_contents('/boot/config-' . $uname_r);
				}
				if($kconfig != null)
				{
					$kconfig = phodevi_vfs::cleanse_and_shorten_kernel_config($kconfig);
					file_put_contents($system_log_dir . 'config', $kconfig);
				}

				// Generate logs from system commands to backup
				$system_log_commands = array(
					'lspci -mmkvvvnn',
					'lscpu',
					'cc -v',
				//	'lsusb',
					'lsmod',
					'sensors',
					'dmesg',
					'vdpauinfo',
					'cpufreq-info',
					'glxinfo',
					'clinfo',
					'vulkaninfo',
					'rocminfo',
					// 'udisks --dump',
					//'upower --dump',
					'dmidecode',
					'hwloc-ls',
					'numactl -H',
					);

				if(!phodevi::is_windows())
				{
					// uname is provided by Cygwin but executing it messes up terminal color/bold formatting afterwards (unescaped chars?)
					$system_log_commands[] = 'uname -a';
				}
				if(phodevi::is_linux() && phodevi::read_property('system', 'filesystem') == 'ext4' && phodevi::is_root())
				{
					$system_log_commands[] = 'dumpe2fs -h ' . phodevi::read_property('disk', 'device-providing-storage');
				}

				if(phodevi::is_bsd())
				{
					$system_log_commands[] = 'sysctl -a';
					$system_log_commands[] = 'kenv';
				}

				foreach($system_log_commands as $command_string)
				{
					$command = explode(' ', $command_string);

					if(($command_bin = pts_client::executable_in_path($command[0])))
					{
						$command_string = ($x = strpos($command_string, ' ')) !== false ? substr($command_string, $x + 1) : ' ';
						if(phodevi::is_windows())
						{
							$cmd_output = shell_exec('cd "' . dirname($command_bin) . '" && ' . basename($command_bin) . ' ' . $command_string . ' 2>&1');
						}
						else
						{
							$cmd_output = shell_exec('cd "' . dirname($command_bin) . '" && ./' . basename($command_bin) . ' ' . $command_string . ' 2>&1');
						}

						if(empty($cmd_output) || strlen($cmd_output) > 900000)
						{
							// Don't preserve really large logs, likely filled with lots of junk
							$cmd_output = null;
							continue;
						}
						if(strpos($cmd_output, 'read kernel buffer failed: Operation not permitted') !== false || strpos($cmd_output, 'Error: unable to open display') !== false || strpos($cmd_output, 'not recognized as an internal or external command') !== false|| strpos($cmd_output, 'permission denied') !== false)
						{
							continue;
						}

						// Try to filter out any serial numbers, etc.
						phodevi_vfs::cleanse_file($cmd_output, $command[0]);
						$cmd_output = pts_strings::remove_line_timestamps($cmd_output);

						file_put_contents($system_log_dir . $command[0], $cmd_output);
					}
				}

				// Dump some common / important environment variables
				$environment_variables = array(
					'PATH' => null,
					'CFLAGS' => null,
					'CXXFLAGS' => null,
					'LD_LIBRARY_PATH' => null,
					'CC' => null,
					'CXX' => null,
					'LIBGL_DRIVERS_PATH' => null
					);

				foreach($environment_variables as $variable => &$value)
				{
					$v = getenv($variable);

					if($v != null)
					{
						$value = $v;
					}
					else
					{
						unset($environment_variables[$variable]);
					}
				}

				if(!empty($environment_variables))
				{
					$variable_dump = null;
					foreach($environment_variables as $variable => $value)
					{
						$variable_dump .= $variable . '=' . $value . PHP_EOL;
					}
					file_put_contents($system_log_dir . 'environment-variables', $variable_dump);
				}

				if(($extra_logs_dir = pts_env::read('PTS_EXTRA_SYSTEM_LOGS_DIR')) != false && is_dir($extra_logs_dir))
				{
					// Allow extra arbitrary system logs to be collected within PTS_EXTRA_SYSTEM_LOGS_DIR
					if(self::$skip_log_file_type_checks)
					{
						// If not bound to just text file backups, just recursive copy directory
						pts_client::$pts_logger && pts_client::$pts_logger->log('Recursively backing up PTS_EXTRA_SYSTEM_LOGS_DIR: ' . $extra_logs_dir);
						pts_file_io::copy($extra_logs_dir, $system_log_dir, true);
					}
					else
					{
						pts_client::$pts_logger && pts_client::$pts_logger->log('Backing up PTS_EXTRA_SYSTEM_LOGS_DIR: ' . $extra_logs_dir);
						foreach(pts_file_io::glob($extra_logs_dir . '/*') as $extra_log)
						{
							$extra_log_basename = basename($extra_log);

							// Don't overwrite existing auto-generated system log files + also ensure log file is text and not binary etc payload (unless override)
							if(!is_file($system_log_dir . $extra_log_basename))
							{
								if(self::$skip_log_file_type_checks || pts_file_io::is_text_file($extra_log))
								{
									copy($extra_log, $system_log_dir . $extra_log_basename);
									pts_client::$pts_logger && pts_client::$pts_logger->log('Backing up: ' . $extra_log);
								}
								else
								{
									pts_client::$pts_logger && pts_client::$pts_logger->log('Ignoring log file due to non-text file / no override: ' . $extra_log);
								}
							}
						}
					}
				}

				pts_module_manager::module_process('__post_test_run_system_logs', $system_log_dir);
			}
		}

		return $bool;
	}
	public static function init_display_mode($prefer_display_mode = false)
	{
		if(PTS_IS_WEB_CLIENT && !defined('PHOROMATIC_SERVER'))
		{
			self::$display = new pts_web_display_mode();
			return;
		}

		if(pts_env::read('PTS_DISPLAY_MODE') != false)
		{
			$env_mode = pts_env::read('PTS_DISPLAY_MODE');
		}
		else if(pts_client::is_debug_mode())
		{
			$env_mode = 'BASIC';
		}
		else if(!empty($prefer_display_mode))
		{
			$env_mode = $prefer_display_mode;
		}
		else
		{
			$env_mode = pts_config::read_user_config('PhoronixTestSuite/Options/General/DefaultDisplayMode', 'DEFAULT');
		}

		switch(strtoupper($env_mode))
		{
			case 'BASIC':
				self::$display = new pts_basic_display_mode();
				break;
			case 'BATCH':
			case 'CONCISE':
				self::$display = new pts_concise_display_mode();
				break;
			case 'SHORT':
				self::$display = new pts_short_display_mode();
				break;
			case 'DEFAULT':
			default:
				self::$display = new pts_concise_display_mode();
				break;
		}
		pts_logger::update_log_cli_output_state();
	}
	public static function program_requirement_checks($only_show_required = false, $always_report = false)
	{
		$extension_checks = pts_needed_extensions();
		$exts = array();
		$do_exit = false;
		foreach($extension_checks as $extension)
		{
			if($extension[1] == false || $always_report)
			{
				if($extension[0] == 1 && $extension[1] == false)
				{
					$do_exit = true;
				}
				else if($extension[0] != 1)
				{
					if(($only_show_required || PTS_IS_DAEMONIZED_SERVER_PROCESS))
					{
						continue;
					}
				}

				$exts[] = array($extension[1] == false ? pts_client::cli_colored_text('MISSING', 'red', true) : pts_client::cli_colored_text('PRESENT', 'green') . '  ', pts_client::cli_just_bold($extension[2]) . '  ', ($extension[0] == 1 ? pts_client::cli_just_underline('REQUIRED') : pts_client::cli_just_italic('OPTIONAL')) . ' - ', $extension[3]);
			}
		}
		if(!empty($exts))
		{
			if($only_show_required)
			{
				echo  pts_client::cli_just_italic('PHP extensions required to run the Phoronix Test Suite:') . PHP_EOL;
			}
			else if($always_report)
			{
				echo  pts_client::cli_just_italic('Recommended PHP extensions for the Phoronix Test Suite:') . PHP_EOL;
			}
			echo pts_user_io::display_text_table($exts) . PHP_EOL . PHP_EOL;
		}

		if($do_exit)
		{
			exit;
		}
	}
	private static function core_storage_init_process()
	{
		$pso = pts_storage_object::recover_from_file(PTS_CORE_STORAGE);

		if($pso == false)
		{
			$pso = new pts_storage_object(true, true);
		}

		// OpenBenchmarking.org - GSID
		$global_gsid = $pso->read_object('global_system_id');
		$global_gsid_e = $pso->read_object('global_system_id_e');
		$global_gsid_p = $pso->read_object('global_system_id_p');

		if(empty($global_gsid) || pts_openbenchmarking::is_valid_gsid_format($global_gsid) == false)
		{
			// Global System ID for anonymous uploads, etc
			$requested_gsid = true;
			$global_gsid = pts_openbenchmarking_client::request_gsid();

			if(is_array($global_gsid))
			{
				$pso->add_object('global_system_id', $global_gsid['gsid']); // GSID
				$pso->add_object('global_system_id_p', $global_gsid['gsid_p']); // GSID_P
				$pso->add_object('global_system_id_e', $global_gsid['gsid_e']); // GSID_E
				pts_define('PTS_GSID', $global_gsid['gsid']);
				pts_define('PTS_GSID_E', $global_gsid['gsid_e']);
			}
		}
		else if(pts_openbenchmarking::is_valid_gsid_e_format($global_gsid_e) == false || pts_openbenchmarking::is_valid_gsid_p_format($global_gsid_p) == false)
		{
			pts_define('PTS_GSID', $global_gsid);
			$requested_gsid = false;
			$global_gsid = pts_openbenchmarking_client::retrieve_gsid();

			if(is_array($global_gsid))
			{
				$pso->add_object('global_system_id_p', $global_gsid['gsid_p']); // GSID_P
				$pso->add_object('global_system_id_e', $global_gsid['gsid_e']); // GSID_E
				pts_define('PTS_GSID_E', $global_gsid['gsid_e']);
			}
		}
		else
		{
			pts_define('PTS_GSID', $global_gsid);
			pts_define('PTS_GSID_E', $global_gsid_e);
			$requested_gsid = false;
		}

		$machine_self_id = $pso->read_object('machine_self_id');
		if(empty($machine_self_id))
		{
			$ns = md5('phoronix-test-suite');
			$binary_ns = null;

			for($i = 0; $i < strlen($ns); $i += 2)
			{
				$binary_ns .= chr(hexdec($ns[$i] . $ns[$i + 1]));
			}

			$msi_hash = sha1($binary_ns . uniqid(PTS_CORE_VERSION, true) . getenv('USERNAME') . getenv('USER') . getenv('HOSTNAME') . phodevi::read_property('network', 'ip'));

			$machine_self_id = sprintf('%08s-%04s-%04x-%04x-%12s', substr($msi_hash, 0, 8), substr($msi_hash, 8, 4), (hexdec(substr($msi_hash, 12, 4)) & 0x0fff) | 0x5000, (hexdec(substr($msi_hash, 16, 4)) & 0x3fff) | 0x8000, substr($msi_hash, 20, 12));
			// machine_self_id is self-generated unique name for Phoromatic/OB purposes in UUIDv5 format
			$pso->add_object('machine_self_id', $machine_self_id);
		}
		pts_define('PTS_MACHINE_SELF_ID', $machine_self_id);

		// Last Run Processing
		$last_core_version = $pso->read_object('last_core_version');
		pts_define('FIRST_RUN_ON_PTS_UPGRADE', ($last_core_version != PTS_CORE_VERSION));

		if(FIRST_RUN_ON_PTS_UPGRADE || ($pso->read_object('last_php_version') != PTS_PHP_VERSION))
		{
			// Report any missing/recommended extensions
			self::program_requirement_checks();
		}

		if(FIRST_RUN_ON_PTS_UPGRADE)
		{
			if($requested_gsid == false && pts_network::internet_support_available())
			{
				pts_openbenchmarking_client::update_gsid();
			}

			$pso->add_object('environment_variables_for_modules', pts_module_manager::modules_environment_variables());
			$pso->add_object('command_alias_list', pts_documentation::client_commands_aliases());
		}
		$pso->add_object('last_core_version', PTS_CORE_VERSION); // PTS version last run
		$pso->add_object('last_php_version', PTS_PHP_VERSION); // PHP version last run

		//$last_pts_version = $pso->read_object('last_pts_version');
		// do something here with $last_pts_version if you want that information
		$pso->add_object('last_pts_version', PTS_VERSION); // PTS version last run

		// Last Run Processing
		$last_run = $pso->read_object('last_run_time');
		pts_define('TIME_PTS_LAUNCHED', time());
		pts_define('IS_FIRST_RUN_TODAY', (substr($last_run, 0, 10) != date('Y-m-d', TIME_PTS_LAUNCHED)));
		$pso->add_object('last_run_time', date('Y-m-d H:i:s', TIME_PTS_LAUNCHED)); // Time PTS was last run
		self::$time_pts_last_launch = strtotime($last_run);
		pts_define('TIME_SINCE_LAST_RUN', ceil((TIME_PTS_LAUNCHED - self::$time_pts_last_launch) / 60)); // TIME_SINCE_LAST_RUN is in minutes

		// Phodevi Cache Handling
		$phodevi_cache = $pso->read_object('phodevi_smart_cache');

		if($phodevi_cache instanceof phodevi_cache && pts_env::read('NO_PHODEVI_CACHE') == false)
		{
			$phodevi_cache = $phodevi_cache->restore_cache(PTS_USER_PATH, PTS_CORE_VERSION);
			phodevi::set_device_cache($phodevi_cache);
		}

		// Archive to disk
		$pso->save_to_file(PTS_CORE_STORAGE);
	}
	public static function get_time_pts_last_started()
	{
		return self::$time_pts_last_launch;
	}
	public static function register_phoromatic_server($server_ip, $http_port)
	{
		self::$phoromatic_servers[] = array('ip' => $server_ip, 'http_port' => $http_port);
	}
	public static function available_phoromatic_servers()
	{
		if(defined('PHOROMATIC_SERVER') && PHOROMATIC_SERVER)
		{
			return array();
		}

		static $last_phoromatic_scan = 0;
		static $phoromatic_servers = false;

		// Cache the Phoromatic Server information for 2 minutes since could be expensive to compute
		if($phoromatic_servers === false || $last_phoromatic_scan < (time() - 120))
		{
			$last_phoromatic_scan = time();
			$phoromatic_servers = array();
			$possible_servers = pts_network::find_zeroconf_phoromatic_servers(true);

			foreach(self::$phoromatic_servers as $server)
			{
				if(phodevi::read_property('network', 'ip') != $server['ip'])
				{
					$possible_servers[] = array($server['ip'], $server['http_port']);
				}
			}

			$user_config_phoromatic_servers = pts_config::read_user_config('PhoronixTestSuite/Options/General/PhoromaticServers', '');
			foreach(explode(',', $user_config_phoromatic_servers) as $static_server)
			{
				$static_server = explode(':', $static_server);
				if(count($static_server) == 2)
				{
					$possible_servers[] = array($static_server[0], $static_server[1]);
				}
			}

			if(is_file(PTS_USER_PATH . 'phoromatic-servers'))
			{
				$phoromatic_servers_file = pts_file_io::file_get_contents(PTS_USER_PATH . 'phoromatic-servers');
				foreach(explode(PHP_EOL, $phoromatic_servers_file) as $ps_file_line)
				{
					$ps_file_line = explode(':', trim($ps_file_line));
					if(count($ps_file_line) == 2 && ip2long($ps_file_line[0]) !== false && is_numeric($ps_file_line) && $ps_file_line > 100)
					{
						$possible_servers[] = array($ps_file_line[0], $ps_file_line[1]);
					}
				}
			}

			// OpenBenchmarking.org relay zero conf
			if(pts_network::internet_support_available())
			{
				$ob_relay = pts_openbenchmarking::possible_phoromatic_servers();
				foreach($ob_relay as $s)
				{
					$local_ip = phodevi::read_property('network', 'ip');
					$local_ip_segments = explode('.', $local_ip);
					$s_segments = explode('.', $s[0]);

					if($s_segments[0] == $local_ip_segments[0] && $s_segments[1] == $local_ip_segments[1])
					{
						$possible_servers[] = array($s[0], $s[1]);
					}
				}
			}

			foreach($possible_servers as $possible_server)
			{
				// possible_server[0] is the Phoromatic Server IP
				// possible_server[1] is the Phoromatic Server HTTP PORT

				if(in_array($possible_server[0], array_keys($phoromatic_servers)) || phodevi::read_property('network', 'ip') ==  $possible_server[0])
				{
					continue;
				}

				// First see if server is HTTPS accessible
				foreach(array('https', 'http') as $protocol)
				{
					$server_response = pts_network::http_get_contents($protocol . '://' . $possible_server[0] . ':' . $possible_server[1] . '/server.php', false, false, false, false, 4);
					if(stripos($server_response, 'Phoromatic') !== false)
					{
						trigger_error('Phoromatic / Caching Server Detected At: ' . $possible_server[0] . ':' . $possible_server[1], E_USER_NOTICE);
						$phoromatic_servers[$possible_server[0]] = array('ip' => $possible_server[0], 'http_port' => $possible_server[1], 'protocol' => $protocol);
						break;
					}
				}
			}
		}

		return $phoromatic_servers;
	}
	public static function swap_variables($user_str, $replace_call)
	{
		if(is_array($replace_call))
		{
			if(count($replace_call) != 2 || method_exists($replace_call[0], $replace_call[1]) == false)
			{
				echo PHP_EOL . 'Var Swap With Method Failed.' . PHP_EOL;
				return $user_str;
			}
		}
		else if(!function_exists($replace_call))
		{
			echo PHP_EOL . 'Var Swap With Function Failed.' . PHP_EOL;
			return $user_str;
		}

		$offset = 0;
		$replace_call_return = false;

		while($offset < strlen($user_str) && ($s = strpos($user_str, '$', $offset)) !== false)
		{
			$s++;
			$var_name = substr($user_str, $s, (($e = strpos($user_str, ' ', $s)) == false ? strlen($user_str) : $e) - $s);

			if($replace_call_return === false)
			{
				$replace_call_return = call_user_func($replace_call);
			}

			$var_replacement = isset($replace_call_return[$var_name]) ? $replace_call_return[$var_name] : null;

			if($var_replacement != null)
			{
				$user_str = str_replace('$' . $var_name, $var_replacement, $user_str);
			}
			else
			{
				// echo "\nVariable Swap For $var_name Failed.\n";
			}

			$offset = $s + strlen($var_replacement);
		}

		return $user_str;
	}
	public static function setup_test_result_directory($save_to)
	{
		$save_to_dir = PTS_SAVE_RESULTS_PATH . $save_to;

		if(strpos(basename($save_to_dir), '.'))
		{
			$save_to_dir = dirname($save_to_dir);
		}

		if($save_to_dir != '.')
		{
			pts_file_io::mkdir($save_to_dir);
		}

		return $save_to_dir;
	}
	public static function current_user()
	{
		// Current system user
		return ($pts_user = pts_openbenchmarking_client::user_name()) != null ? $pts_user : phodevi::read_property('system', 'username');
	}
	public static function process_shutdown_tasks()
	{
		// TODO: possibly do something like posix_getpid() != pts_client::$startup_pid in case shutdown function is called from a child process
		// Generate Phodevi Smart Cache
		if(pts_env::read('NO_PHODEVI_CACHE') == false)
		{
			if(pts_config::read_bool_config('PhoronixTestSuite/Options/General/UsePhodeviCache', 'TRUE'))
			{
				pts_storage_object::set_in_file(PTS_CORE_STORAGE, 'phodevi_smart_cache', phodevi::get_phodevi_cache_object(PTS_USER_PATH, PTS_CORE_VERSION));
			}
			else
			{
				pts_storage_object::set_in_file(PTS_CORE_STORAGE, 'phodevi_smart_cache', null);
			}
		}

		if(is_array(self::$lock_pointers))
		{
			foreach(array_keys(self::$lock_pointers) as $lock_file)
			{
				self::release_lock($lock_file);
			}
		}

		foreach(self::$forked_pids as $pid)
		{
			if(is_dir('/proc/' . $pid) && function_exists('posix_kill'))
			{
				posix_kill($pid, SIGKILL);
			}
		}
	}
	public static function kill_process_with_children_processes($pid)
	{
		if(is_dir('/proc/' . $pid) && is_file('/proc/' . $pid . '/task/' . $pid . '/children'))
		{
			$child_processes = pts_strings::trim_explode(' ', file_get_contents('/proc/' . $pid . '/task/' . $pid . '/children'));

			foreach($child_processes as $p)
			{
				if(!empty($p) && is_dir('/proc/' . $p))
				{
					self::kill_process_with_children_processes($p);
				}
			}
		}
		if(!empty($pid) && is_dir('/proc/' . $pid))
		{
			if(function_exists('posix_kill'))
			{
				posix_kill($pid, SIGKILL);
			}
			else
			{
				shell_exec('kill -9 ' . $pid);
			}
			sleep(1);
		}
	}
	public static function do_anonymous_usage_reporting()
	{
		return pts_config::read_bool_config('PhoronixTestSuite/Options/OpenBenchmarking/AnonymousUsageReporting', 0);
	}
	public static function check_command_for_function($option, $check_function)
	{
		$in_option = false;

		if(is_file(PTS_COMMAND_PATH . $option . '.php'))
		{
			if(!class_exists($option, false) && is_file(PTS_COMMAND_PATH . $option . '.php'))
			{
				include(PTS_COMMAND_PATH . $option . '.php');
			}

			if(method_exists($option, $check_function))
			{
				$in_option = true;
			}
		}

		return $in_option;
	}
	public static function execute_command($command, $pass_args = null)
	{
		if(!class_exists($command, false) && is_file(PTS_COMMAND_PATH . $command . '.php'))
		{
			include(PTS_COMMAND_PATH . $command . '.php');
		}

		if(is_file(PTS_COMMAND_PATH . $command . '.php') && method_exists($command, 'argument_checks'))
		{
			$argument_checks = call_user_func(array($command, 'argument_checks'));

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
						echo PHP_EOL . 'Method check fails.' . PHP_EOL;
						continue;
					}

					$function_check = array($method_check, $function_check);
				}
				else if(!function_exists($function_check))
				{
					continue;
				}

				// VARIABLE_LENGTH_MAYBE when handling is optional or VARIABLE_LENGTH
				if(($maybe = ($argument_check->get_argument_index() === 'VARIABLE_LENGTH_MAYBE')) || $argument_check->get_argument_index() == 'VARIABLE_LENGTH')
				{
					$return_value = $maybe ? true : null;

					foreach($pass_args as $arg)
					{
						$return_value = call_user_func_array($function_check, array($arg));

						if($return_value == true)
						{
							break;
						}
					}

				}
				else
				{
					$return_value = call_user_func_array($function_check, array((isset($pass_args[$argument_check->get_argument_index()]) ? $pass_args[$argument_check->get_argument_index()] : null)));
				}

				if($return_value == $return_fails_on)
				{
					$command_alias = defined($command . '::doc_use_alias') ? constant($command . '::doc_use_alias') : $command;

					if((isset($pass_args[$argument_check->get_argument_index()]) && !empty($pass_args[$argument_check->get_argument_index()])) || ($argument_check->get_argument_index() == 'VARIABLE_LENGTH' && !empty($pass_args)))
					{
						trigger_error('Invalid Argument: ' . implode(' ', $pass_args), E_USER_ERROR);
					}
					else
					{
						trigger_error('Phoronix Test Suite Argument Missing', E_USER_ERROR);
					}

					echo PHP_EOL . pts_client::cli_just_bold('CORRECT SYNTAX:') . PHP_EOL . 'phoronix-test-suite ' . str_replace('_', '-', $command_alias) . ' ' . pts_client::cli_just_italic(implode(' ', $argument_checks)) . PHP_EOL . PHP_EOL;
					pts_client::invalid_command_helper($pass_args, $argument_checks);

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

		pts_module_manager::module_process('__pre_option_process', $command);

		if(is_file(PTS_COMMAND_PATH . $command . '.php'))
		{
			self::$current_command = $command;

			if(method_exists($command, 'run'))
			{
				call_user_func(array($command, 'run'), $pass_args);
			}
			else
			{
				echo PHP_EOL . 'There is an error in the requested command: ' . $command . PHP_EOL . PHP_EOL;
			}
		}
		else if(($t = pts_module::valid_run_command($command)) != false)
		{
			list($module, $module_command) = $t;
			pts_module_manager::set_current_module($module);
			pts_module_manager::run_command($module, $module_command, $pass_args);
			pts_module_manager::set_current_module(null);
		}
		echo PHP_EOL;

		pts_module_manager::module_process('__post_option_process', $command);
	}
	public static function invalid_command_helper($passed_args, &$argument_checks)
	{
		$supports_passing_a_test = false;
		foreach($argument_checks as $check)
		{
			if($check->get_function_check_type() == 'Test' || strpos($check->get_function_check_type(), 'Test |') !== false)
			{
				$supports_passing_a_test = true;
			}
		}

		$showed_recent_results = pts_tests::recently_saved_results();

		if($supports_passing_a_test)
		{
			$tests_to_show = array_keys(pts_openbenchmarking_client::new_and_recently_updated_tests(30, 31, true));
			$tests_to_show_title = 'New Tests';

			if(count($tests_to_show) < 3)
			{
				$tests_to_show = array_keys(pts_openbenchmarking_client::new_and_recently_updated_tests(60, 31));
				$tests_to_show_title = 'New + Updated Tests';
			}

			if(count($tests_to_show) < 3)
			{
				$tests_to_show = array_keys(pts_openbenchmarking_client::most_popular_tests(20));
				$tests_to_show_title = 'Popular Tests';
			}

			if(count($tests_to_show) > 3)
			{
				$longest_test = strlen(pts_strings::find_longest_string($tests_to_show)) + 3;
				$terminal_width = pts_client::terminal_width();
				$tests_per_line = floor($terminal_width / $longest_test);
				shuffle($tests_to_show);
				$tests_to_show = array_slice($tests_to_show, 0, min(count($tests_to_show), $tests_per_line * 3 - 1));

				echo pts_client::cli_just_bold($tests_to_show_title . ':') . PHP_EOL;
				$i = 0;
				foreach($tests_to_show as $test)
				{
					if($i % $tests_per_line == 0)
					{
						echo '   ';
					}
					echo $test;

					$i++;
					if($i % $tests_per_line == 0 || $i == count($tests_to_show))
					{
						echo PHP_EOL;
					}
					else
					{
						echo str_repeat(' ', $longest_test - strlen($test));
					}
				}
			}
		}

		// Disable this for now to cutdown on server resources and since not too useful
		// same info can be gathered from `phoronix-test-suite openbenchmarking-uploads`
		/*
		if(count($result_uploads = pts_openbenchmarking::result_uploads_from_this_ip()) > 0)
		{
			echo PHP_EOL . pts_client::cli_just_bold('Recent OpenBenchmarking.org Results From This IP:') . PHP_EOL;
			$t = array();
			foreach($result_uploads as $id => $title)
			{
				$t[] = array(pts_client::cli_colored_text($id, 'gray', true), $title);

				if(count($t) == 5)
				{
					break;
				}
			}
			echo pts_user_io::display_text_table($t, '   ') . PHP_EOL;
		}
		*/
		echo PHP_EOL;

		$similar_tests = array();
		if(!empty($passed_args))
		{
			foreach(pts_arrays::to_array($passed_args) as $passed_arg)
			{
				$arg_soundex = soundex($passed_arg);
				$arg_save_identifier_like = pts_test_run_manager::clean_save_name($passed_arg);

				foreach(pts_openbenchmarking::linked_repositories() as $repo)
				{
					$repo_index = pts_openbenchmarking::read_repository_index($repo);

					foreach(array('tests', 'suites') as $type)
					{
						if(isset($repo_index[$type]) && is_array($repo_index[$type]))
						{
							foreach(array_keys($repo_index[$type]) as $identifier)
							{
								if(soundex($identifier) == $arg_soundex)
								{
									pts_arrays::unique_push($similar_tests, array($identifier, ' [' . ucwords(substr($type, 0, -1)) . ']'));
								}
								else if(isset($passed_arg[3]) && strpos($identifier, $passed_arg) !== false)
								{
									pts_arrays::unique_push($similar_tests, array($identifier, ' [' . ucwords(substr($type, 0, -1)) . ']'));
								}
							}
						}
					}
				}

				foreach(pts_results::saved_test_results() as $result)
				{
					if(soundex($result) == $arg_soundex || (isset($passed_arg[3]) && strpos($identifier, $arg_save_identifier_like) !== false))
					{
						pts_arrays::unique_push($similar_tests, array($result, ' [Test Result]'));
					}
				}

				if(strpos($passed_arg, '-') !== false)
				{
					$possible_identifier = str_replace('-', '', $passed_arg);
					if(pts_test_profile::is_test_profile($possible_identifier))
					{
						pts_arrays::unique_push($similar_tests, array($possible_identifier, ' [Test]'));
					}
				}
				if($passed_arg != ($possible_identifier = strtolower($passed_arg)))
				{
					if(pts_test_profile::is_test_profile($possible_identifier))
					{
						pts_arrays::unique_push($similar_tests, array($possible_identifier, ' [Test]'));
					}
				}
			}
		}
		if(count($similar_tests) > 0)
		{
			echo pts_client::cli_just_bold('Possible Suggestions:') . PHP_EOL;
			//$similar_tests = array_unique($similar_tests);
			if(isset($similar_tests[10]))
			{
				// lots of tests... trim it down
				shuffle($similar_tests);
				$similar_tests = array_slice($similar_tests, 0, 10);
			}
			echo pts_user_io::display_text_table($similar_tests, '- ') . PHP_EOL . PHP_EOL;
		}

		if($showed_recent_results == false)
		{
			echo 'See available tests to run by visiting OpenBenchmarking.org or running:' . PHP_EOL . PHP_EOL;
			echo '    phoronix-test-suite list-tests' . PHP_EOL . PHP_EOL;
			echo 'Tests can be installed by running:' . PHP_EOL . PHP_EOL;
			echo '    phoronix-test-suite install <test-name>' . PHP_EOL . PHP_EOL;
		}
	}
	public static function get_sent_command()
	{
		return self::$sent_command;
	}
	public static function handle_sent_command(&$sent_command, &$argv, &$argc)
	{
		self::$sent_command = $sent_command;
		if(is_file(PTS_PATH . 'pts-core/commands/' . $sent_command . '.php') == false)
		{
			$replaced = false;

			if(pts_module::valid_run_command($sent_command))
			{
				$replaced = true;
			}
			else if(isset($argv[1]) && strpos($argv[1], '.openbenchmarking') !== false && is_readable($argv[1]))
			{
				$sent_command = 'openbenchmarking_launcher';
				$argv[2] = $argv[1];
				$argc = 3;
				$replaced = true;
			}
			else
			{
				$aliases = pts_storage_object::read_from_file(PTS_CORE_STORAGE, 'command_alias_list');
				if($aliases == null)
				{
					$aliases = pts_documentation::client_commands_aliases();
				}

				if(isset($aliases[$sent_command]))
				{
					$sent_command = $aliases[$sent_command];
					$replaced = true;
				}
			}

			if($replaced == false)
			{
				// Show help command, since there are no valid commands
				$sent_command = 'help';
			}
		}
		else
		{
			$replaced = true;
		}

		return $replaced;
	}
	public static function current_command()
	{
		return self::$current_command;
	}
	public static function terminal_width()
	{
		static $terminal_width = null;

		// Cache the call on Windows as the powershell command is slow...
		if(!phodevi::is_windows() || $terminal_width == null)
		{
			$terminal_width = 80;

			if(phodevi::is_windows())
			{
				// Powershell defaults to 120
				$terminal_width = trim(shell_exec('powershell -NoProfile "(get-host).UI.RawUI.MaxWindowSize.width"'));
			}
			else if(($tw = pts_env::read('TERMINAL_WIDTH')) != false && is_numeric($tw) >= 20)
			{
				$terminal_width = $tw;
			}
			else if(pts_client::executable_in_path('stty'))
			{
				$terminal_width = explode(' ', trim(shell_exec('stty size 2>&1')));

				if(count($terminal_width) == 2 && is_numeric($terminal_width[1]) && $terminal_width[1] >= 40)
				{
					$terminal_width = $terminal_width[1];
				}
				else
				{
					$terminal_width = 80;
				}
			}
			else if(pts_client::executable_in_path('tput'))
			{
				$terminal_width = trim(shell_exec('tput cols 2>&1'));

				if(is_numeric($terminal_width) && $terminal_width > 1)
				{
					$terminal_width = $terminal_width;
				}
				else
				{
					$terminal_width = 80;
				}
			}
		}

		return $terminal_width;
	}
	public static function terminal_height()
	{
		static $terminal_height = null;

		if($terminal_height == null)
		{
			$terminal_height = 12;

			if(phodevi::is_windows())
			{
				$terminal_height = trim(shell_exec('powershell -NoProfile "(get-host).UI.RawUI.MaxWindowSize.height"'));
			}
			else if(pts_client::executable_in_path('stty'))
			{
				$th = explode(' ', trim(shell_exec('stty size 2>&1')));

				if(count($th) == 2 && is_numeric($th[0]) && $th[0] >= 1)
				{
					$terminal_height = $th[1];
				}
			}
			else if(pts_client::executable_in_path('tput'))
			{
				$th = trim(shell_exec('tput lines 2>&1'));

				if(is_numeric($th) && $th > 1)
				{
					$terminal_height = $th;
				}
			}
		}

		return $terminal_height;
	}
	public static function is_process_running($process)
	{
		$running = null;
		if(phodevi::is_linux() && pts_client::executable_in_path('ps'))
		{
			// Checks if process is running on the system
			$ps = shell_exec('ps -C ' . strtolower($process) . ' 2>&1');
			if(strpos($ps, 'unrecognized option') === false)
			{
				$running = trim(str_replace(array('PID', 'TTY', 'TIME', 'CMD'), '', $ps));
			}
		}
		else if(phodevi::is_solaris())
		{
			// Checks if process is running on the system
			$ps = shell_exec('ps -ef 2>&1');
			$running = strpos($ps, ' ' . strtolower($process)) != false ? 'TRUE' : null;
		}
		else if(pts_client::executable_in_path('ps') != false)
		{
			// Checks if process is running on the system
			$ps = shell_exec('ps -ax 2>&1');
			if(strpos($ps, 'unrecognized option') === false)
			{
				$running = strpos($ps, strtolower($process)) != false ? 'TRUE' : null;
			}
		}

		return !empty($running);
	}
	public static function parse_value_string_double_identifier($value_string)
	{
		// i.e. with PRESET_OPTIONS='stream.run-type=Add'
		$values = array();

		foreach(explode(';', $value_string) as $preset)
		{
			if(count($preset = pts_strings::trim_explode('=', $preset)) == 2)
			{
				$dot = strrpos($preset[0], '.');
				if($dot !== false && ($test = substr($preset[0], 0, $dot)) != null && ($option = substr($preset[0], ($dot + 1))) != null)
				{
					$values[$test][$option] = $preset[1];
				}
			}
		}

		return $values;
	}
	public static function create_temporary_file($file_extension = null)
	{
		$temp_file = tempnam(pts_client::temporary_directory(), 'PTS');

		if($file_extension)
		{
			$extended_file = pts_client::temporary_directory() . DIRECTORY_SEPARATOR . basename($temp_file) . $file_extension;

			if(rename($temp_file, $extended_file))
			{
				$temp_file = $extended_file;
			}
		}

		return $temp_file;
	}
	public static function create_temporary_directory($prefix = null, $large_file_support = false)
	{
		$tmpdir = pts_client::temporary_directory();
		if($large_file_support && disk_free_space(PTS_USER_PATH) > disk_free_space($tmpdir))
		{
			$tmpdir = PTS_USER_PATH . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR;
			pts_file_io::mkdir($tmpdir);
		}

		do
		{
			$randname = '/pts-' . $prefix . rand(0, 9999);
		}
		while(is_dir($tmpdir . $randname));

		mkdir($tmpdir . $randname);

		return $tmpdir . $randname . '/';
	}
	public static function temporary_directory()
	{
		return sys_get_temp_dir();
	}
	public static function shell_exec($exec, $extra_vars = null)
	{
		// Same as shell_exec() but with the PTS env variables added in
		// Convert pts_client::environment_variables() into shell export variable syntax

		$var_string = '';
		$extra_vars = ($extra_vars == null ? pts_client::environment_variables() : array_merge(pts_client::environment_variables(), $extra_vars));

		foreach(array_keys($extra_vars) as $key)
		{
			if(phodevi::is_windows())
			{
				$v = str_replace('"', '', trim($extra_vars[$key]));
				if(substr($v, -1) == '\\')
				{ echo $v;
					$v = substr($v, 0, -1);
				}
				$var_string .= 'setx ' . $key . ' "' . $v . '";';
			}
			else
			{
				if(!empty($extra_vars[$key]))
				{
					$extra_vars[$key] = str_replace(' ', '\ ', trim($extra_vars[$key]));
				}
				$var_string .= 'export ' . $key . '="' . $extra_vars[$key] . '";';
			}
		}
		$var_string .= ' ';

		return shell_exec($var_string . $exec);
	}
	public static function get_path()
	{
		$path = getenv('PATH');
		if(empty($path) || $path == ':')
		{
			if(phodevi::is_windows())
			{
				$path = 'C:\Windows\system32;C:\Windows;C:\Windows\System32\Wbem;C:\Windows\System32\WindowsPowerShell\v1.0\;C:\Users\\' . getenv('USERNAME') . '\AppData\Local\Microsoft\WindowsApps;';
			}
			else
			{
				$path = '/bin:/usr/sbin:/usr/bin:/usr/local/bin:/usr/pkg/bin:/usr/games';
			}
		}
		if(phodevi::is_windows())
		{
			$username = getenv('USERNAME');
			$possible_paths_to_add = array('C:\Users\\' . $username . '\AppData\Local\Programs\Python\Python36-32',
				'C:\Users\\' . $username . '\AppData\Local\Programs\Python\Python37',
				'C:\Users\\' . $username . '\AppData\Local\Programs\Python\Python38',
				'C:\Users\\' . $username . '\AppData\Local\Programs\Python\Python39',
				'C:\Users\\' . $username . '\AppData\Local\Programs\Python\Python310',
				'C:\Users\\' . $username . '\AppData\Local\Programs\Python\Python311',
				'C:\Python27',
				'C:\Go\bin',
				'C:\Strawberry\perl\bin',
				pts_file_io::glob('C:\*\NVIDIA*\NVSMI'), // NVIDIA SMI
				pts_file_io::glob('C:\*\R\R-*\bin'),
				pts_file_io::glob('C:\*\Java\jdk-*\bin'), pts_file_io::glob('C:\*\ojdkbuild\java-*\bin'), pts_file_io::glob('C:\*\Java\jre-*\bin'),
				'C:\cygwin64\bin',
				pts_file_io::glob('C:\Program*\LLVM\bin'),
				pts_file_io::glob('C:\Program*\CMake\bin'),
				pts_file_io::glob('C:\Program*\WinRAR')
				);
			foreach($possible_paths_to_add as $path_check)
			{
				if(is_array($path_check))
				{
					// if it's an array it came from glob so no need to re-check if is_dir()
					foreach($path_check as $sub_check)
					{
						if(strpos($path, $sub_check) == false)
						{
							$path .= ';' . $sub_check;
						}
					}
				}
				else if(is_dir($path_check) && strpos($path, $path_check) == false)
				{
					$path .= ';' . $path_check;
				}
			}
		}
		else
		{
			// Fedora OpenMPI path not often in PATH by default
			$ds = array('/usr/lib64/openmpi/bin',
				'/usr/local/mpi/openmpi/bin',
				'/usr/lib64/mpi/gcc/openmpi/bin'
				);
			foreach($ds as $d)
			{
				if(is_dir($d) && strpos($path, $d) === false)
				{
					$path .= ':' . $d;
				}
			}
			foreach(pts_file_io::glob('/usr/lib*/mpi/*/*/bin/') as $mpi_bin_path)
			{
				// openSUSE has e.g. /usr/lib64/mpi/gcc/openmpi4/bin for mpicxx that otherwise is not appearing in default PATH on modern Tumbleweed
				$path .= ':' . $mpi_bin_path;
			}
		}

		return $path;
	}
	public static function get_path_separator()
	{
		return phodevi::is_windows() ? ';' : ':';
	}
	public static function executable_in_path($executable, $ignore_paths_with = false)
	{
		static $cache = null;

		if(!isset($cache[$executable]) || empty($cache[$executable]) || $ignore_paths_with)
		{
			$path = pts_client::get_path();
			$paths = pts_strings::trim_explode(pts_client::get_path_separator(), $path);
			$executable_path = false;

			foreach($paths as $path)
			{
				$path = pts_strings::add_trailing_slash($path);

				if(is_executable($path . $executable))
				{
					if($ignore_paths_with && stripos($path, $ignore_paths_with) !== false)
					{
						continue;
					}

					$executable_path = $path . $executable;
					break;
				}
			}

			if($ignore_paths_with)
			{
				// Don't cache calls using the $ignore_paths_with parameter
				return $executable_path;
			}

			$cache[$executable] = $executable_path;
		}
		if($cache[$executable] == false && phodevi::is_windows() && substr($executable, -4) != '.exe')
		{
			// See if there is .exe match most likely, e.g. Java, Python, etc.
			$cache[$executable] = pts_client::executable_in_path($executable . '.exe');
		}

		return $cache[$executable];
	}
	public static function test_for_result_viewer_connection($port)
	{
		if(is_numeric($port))
		{
			$dynamic_urls_to_try = array();

			// Due to the way PHP web server is handled depending upon if remote allowed,
			// both URLs need to be tried
			$dynamic_urls_to_try[] = 'http://localhost:' . $port;
			$dynamic_urls_to_try[] = 'http://127.0.0.1:' . $port;

			foreach($dynamic_urls_to_try as $base_url)
			{
				if(pts_network::http_get_contents($base_url . '/index.php?PTS', false, false, false, false, 3) == 'PTS')
				{
					return $base_url;
				}
			}
		}

		return false;
	}
	public static function display_result_view($result_file, $auto_open = false, $prompt_text = null)
	{
		if(defined('PHOROMATIC_PROCESS'))
		{
			return false;
		}

		if(!is_object($result_file))
		{
			$result_file = new pts_result_file($result_file);
		}

		if(!phodevi::is_display_server_active())
		{
			$prompt_text = !empty($prompt_text) ? $prompt_text : 'Do you want to view the test results?';
			$txt_results = $auto_open || pts_user_io::prompt_bool_input($prompt_text, true);
			if($txt_results)
			{
				echo pts_result_file_output::result_file_to_text($result_file, pts_client::terminal_width());
			}
		}
		else
		{
			if(TIME_PTS_LAUNCHED > (time() - 6))
			{
				// Avoid a race condition on start-up if the dynamic result viewer PHP server isn't yet active
				// and running a command like 'refresh-graphs' where you may be viewing a result right away
				sleep(5);
			}

			$ports_to_try = array();
			if(pts_client::$web_result_viewer_active)
			{
				$ports_to_try[] = pts_client::$web_result_viewer_active;
			}
			if(($restored_port = pts_storage_object::read_from_file(PTS_CORE_STORAGE, 'last_web_result_viewer_active_port')) != false && is_numeric($restored_port) && $restored_port > 20)
			{
				$ports_to_try[] = $restored_port;
			}

			foreach($ports_to_try as $try_port)
			{
				if(($base_url = pts_client::test_for_result_viewer_connection($try_port)))
				{
					pts_client::$has_used_modern_result_viewer = true;
					pts_client::$last_result_view_url = $base_url . '/result/' . $result_file->get_identifier();
					$length_browser_open = pts_client::display_web_page(pts_client::$last_result_view_url, $prompt_text, true, $auto_open);
					return true;
				}
			}

			// Failed to start/find the dynamic result viewer...
			trigger_error('Dynamic result viewer not running or inaccessible', E_USER_WARNING);
			$prompt_text = !empty($prompt_text) ? $prompt_text : 'Do you want to view the text-based test results?';
			$txt_results = $auto_open || pts_user_io::prompt_bool_input($prompt_text, true);
			if($txt_results)
			{
				echo pts_result_file_output::result_file_to_text($result_file, pts_client::terminal_width());
			}
		}
	}
	public static function display_web_page($URL, $alt_text = null, $default_open = true, $auto_open = false)
	{
		if(!phodevi::is_display_server_active() || defined('PHOROMATIC_PROCESS'))
		{
			return -1;
		}

		if($auto_open == false)
		{
			$view_results = pts_user_io::prompt_bool_input(($alt_text == null ? 'Do you want to view the results in your web browser' : $alt_text), $default_open);
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
				$config_browser = pts_config::read_user_config('PhoronixTestSuite/Options/General/DefaultBrowser', null);

				if($config_browser != null && (is_executable($config_browser) || ($config_browser = pts_client::executable_in_path($config_browser))))
				{
					$browser = $config_browser;
				}
				else if(phodevi::is_windows())
				{
					$windows_browsers = array(
						'C:\Program Files (x86)\Mozilla Firefox\firefox.exe',
						'C:\Program Files (x86)\Google\Chrome\Application\chrome.exe',
						//'C:\Program Files\internet explorer\iexplore.exe'
						);

					foreach($windows_browsers as $browser_test)
					{
						if(is_executable($browser_test))
						{
							$browser = $browser_test;
							break;
						}
					}

					if(!empty($browser))
					{
						$browser = escapeshellarg($browser);
					}

					if(substr($URL, 0, 1) == '\\')
					{
						$URL = 'file:///C:' . str_replace('/', '\\', $URL);
					}
					else if(substr($URL, 0, 2) == 'C:')
					{
						$URL = 'file:///' . str_replace('//', '/', str_replace('\\', '/', $URL));
					}


					pts_client::$last_browser_launch_time = time();
					$launch_time = microtime(true);
					if(empty($browser))
					{
						// should allow the browser to be opened in Edge
						shell_exec('start ' . $URL . '');
					}
					else
					{
						shell_exec($browser . ' "' . $URL . '"');
					}
					pts_client::$last_browser_duration = microtime(true) - $launch_time;
					return -1;
				}
				else
				{
					$possible_browsers = array('x-www-browser', 'firefox', 'mozilla', 'google-chrome', 'chromium', 'iceweasel', 'konqueror', 'epiphany', 'midori', 'epiphany-browser', 'epiphany', 'falkon', 'qupzilla', 'open', 'xdg-open');

					// First try to see if a browser is already running and use that
					foreach($possible_browsers as &$b)
					{
						if(pts_client::is_process_running($b) && ($b = pts_client::executable_in_path($b)))
						{
							$browser = $b;
							break;
						}
					}

					// Otherwise just find any browser available in PATH
					if($browser == null)
					{
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
			}

			if($browser != null)
			{
				$launch_time = microtime(true);
				pts_client::$last_browser_launch_time = time();
				shell_exec($browser . ' "' . $URL . '" 2> /dev/null &');

				// return how long the browser was opened, useful for trying to see if launched to an existing open browser or process stayed while viewing results
				pts_client::$last_browser_duration = microtime(true) - $launch_time;
				return pts_client::$last_browser_duration;
			}
			else
			{
				echo PHP_EOL . 'No Web Browser Found.' . PHP_EOL;
			}
		}

		return -1;
	}
	public static function timed_function($function, $function_parameters, $time, $continue_while_true_function = false, $continue_while_true_function_parameters = null)
	{
		if(($time < 0.5 && $time != -1) || $time > 300)
		{
			return;
		}
		if($continue_while_true_function && $continue_while_true_function_parameters === null)
		{
			$continue_while_true_function_parameters = array();
		}

		if(function_exists('pcntl_fork') && function_exists('posix_setsid'))
		{
			$current_pid = function_exists('posix_getpid') ? posix_getpid() : -1;
			$pid = pcntl_fork();

			if($pid == -1)
			{
				trigger_error('Could not fork ' . $function . '.', E_USER_ERROR);
			}
			else if($pid)
			{
				self::$forked_pids[] = $pid;
			}
			else
			{
				posix_setsid();
				$loop_continue = true;
				while($loop_continue && is_file(PTS_USER_LOCK) && ($continue_while_true_function === true || ($loop_continue = call_user_func_array($continue_while_true_function, $continue_while_true_function_parameters))))
				{
					call_user_func_array($function, $function_parameters);

					if($time > 0)
					{
						sleep($time);
					}
					else if($time == -1)
					{
						$loop_continue = false;
					}
					if($current_pid != -1 && !is_dir('/proc/' . $current_pid))
					{
						exit;
					}
					clearstatcache();
				}
				if(function_exists('posix_kill'))
				{
					posix_kill(posix_getpid(), SIGINT);
				}
				exit(0);
			}
		}
		else
		{
			if(is_array($function))
			{
				$function = implode(':', $function);
			}

			trigger_error('php-pcntl and php-posix must be installed for calling ' . $function . '.', E_USER_ERROR);
		}
	}
	public static function fork($fork_function, $fork_function_parameters = null)
	{
		if(!is_array($fork_function_parameters))
		{
			$fork_function_parameters = array($fork_function_parameters);
		}

		if(function_exists('pcntl_fork'))
		{
			$current_pid = function_exists('posix_getpid') ? posix_getpid() : -1;
			$pid = pcntl_fork();

			if($pid == -1)
			{
				trigger_error('Could not fork ' . $fork_function . '.', E_USER_ERROR);
			}
			else if($pid)
			{
				// PARENT
				self::$forked_pids[] = $pid;
				return true;
			}
			else
			{
				// CHILD
				// posix_setsid();
				call_user_func_array($fork_function, $fork_function_parameters);
				if(function_exists('posix_kill'))
				{
					posix_kill(posix_getpid(), SIGINT);
				}
				exit(0);
			}
		}
		else
		{
			// No PCNTL Support
			call_user_func_array($fork_function, $fork_function_parameters);
		}

		return false;
	}
	public static function code_error_handler($error_code, $error_string, $error_file, $error_line)
	{
		/*if(!(error_reporting() & $error_code))
		{
			return;
		}*/

		switch($error_code)
		{
			case E_USER_ERROR:
				$error_type = 'PROBLEM';
				if(pts_client::is_debug_mode() == false)
				{
					$error_file = null;
					$error_line = 0;
				}
				break;
			case E_USER_NOTICE:
				if(pts_client::is_debug_mode() == false)
				{
					return;
				}
				$error_type = 'NOTICE';
				break;
			case E_USER_WARNING:
				$error_type = 'NOTICE'; // Yes, report warnings as a notice
				if(pts_client::is_debug_mode() == false)
				{
					$error_file = null;
					$error_line = 0;
				}
				break;
			case E_ERROR:
			case E_PARSE:
				$error_type = 'ERROR';
				break;
			case E_WARNING:
			case E_NOTICE:
				$error_type = 'NOTICE';
				if(($s = strpos($error_string, 'Undefined ')) !== false && ($x = strpos($error_string, ': ', $s)) !== false)
				{
					$error_string = 'Undefined: ' . substr($error_string, ($x + 2));
				}
				else if(strpos($error_string, 'Unable to find the socket transport') !== false || strpos($error_string, 'SSL: Connection reset') !== false)
				{
					$error_string = 'PHP OpenSSL support is needed to handle HTTPS downloads.';
					$error_file = null;
					$error_line = null;
				}
				else
				{
					$ignore_errors = array(
						'Using empty file',
						'Name or service not known',
						'HTTP request failed',
						'fopen',
						'fsockopen',
						'file_get_contents',
						'failed to connect',
						'unable to connect',
						'SSL operation',
						'directory not empty',
						'_lock', // likely multi-process issue, etc for unlinking lock
						);

					foreach($ignore_errors as $error_check)
					{
						if(stripos($error_string, $error_check) !== false)
						{
							return;
						}
					}
				}
				break;
			default:
				$error_type = $error_code;
				break;
		}

		if(pts_client::$pts_logger != false)
		{
			pts_client::$pts_logger->report_error($error_type, $error_string, $error_file, $error_line);
		}

		if(pts_client::$display != false)
		{
			pts_client::$display->triggered_system_error($error_type, $error_string, $error_file, $error_line);
		}
		else
		{
			echo PHP_EOL . $error_string;

			if($error_file != null && $error_line != null)
			{
				echo ' in ' . $error_file . ':' . $error_line;
			}

			echo PHP_EOL;
		}

		if($error_type == 'ERROR')
		{
			exit(1);
		}
	}
	public static function set_debug_mode($dmode)
	{
		self::$debug_mode = ($dmode == true);
	}
	public static function is_debug_mode()
	{
		// debug mode for tests
		return self::$debug_mode == true;
	}
	public static function update_download_speed_average($download_size, $elapsed_time)
	{
		if(self::$download_speed_average_count == -1)
		{
			self::load_download_speed_averages();
		}

		$download_speed = floor($download_size / $elapsed_time); // bytes per second

		if(self::$download_speed_average_count > 0 && self::$download_speed_average_speed > 0)
		{
			// bytes per second
			self::$download_speed_average_speed = floor(((self::$download_speed_average_speed * self::$download_speed_average_count) + $download_speed) / (self::$download_speed_average_count + 1));
			self::$download_speed_average_count++;
		}
		else
		{
			self::$download_speed_average_speed = $download_speed;
			self::$download_speed_average_count = 1;
		}
	}
	public static function get_average_download_speed()
	{
		if(self::$download_speed_average_count == -1)
		{
			self::load_download_speed_averages();
		}

		return self::$download_speed_average_speed;
	}
	private static function load_download_speed_averages()
	{
		self::$download_speed_average_count = pts_storage_object::read_from_file(PTS_CORE_STORAGE, 'download_average_count');
		self::$download_speed_average_speed = pts_storage_object::read_from_file(PTS_CORE_STORAGE, 'download_average_speed');
	}
	public static function save_download_speed_averages()
	{
		pts_storage_object::set_in_file(PTS_CORE_STORAGE, 'download_average_count', self::$download_speed_average_count);
		pts_storage_object::set_in_file(PTS_CORE_STORAGE, 'download_average_speed', self::$download_speed_average_speed);
	}
	public static function save_output_handler($output, $title, $file_extension)
	{
		if(($output_file = pts_env::read('OUTPUT_FILE')) == false)
		{
			if(file_exists($title))
			{
				$title = 'test-result';
			}
			if(($output_dir = pts_env::read('OUTPUT_DIR')) == false || !is_dir($output_dir))
			{
				$output_dir = pts_core::user_home_directory();
			}
			if(empty($title))
			{
				$title = time();
			}

			$attempts = 1;
			do
			{
				$output_file = $output_dir . (substr($output_dir, -1) != '/' ? '/' : '') . $title . ($attempts == 1 ? '' : '-' . $attempts) . '.' . $file_extension;
				$attempts++;
			}
			while(is_file($output_file));
		}

		$success = file_put_contents($output_file, $output);

		if($success)
		{
			echo PHP_EOL . pts_client::cli_just_bold('Saved Output To: ') . $output_file . PHP_EOL;
			return $success;
		}
		else
		{
			echo PHP_EOL . pts_client::cli_colored_text('Result Save Failed: ', 'red') . $output_file . PHP_EOL;
			return false;
		}
	}
}

// Some extra magic
set_error_handler(array('pts_client', 'code_error_handler'));

if(PTS_IS_CLIENT && (PTS_IS_DEV_BUILD || pts_client::is_debug_mode()))
{
	// Enable more verbose error reporting only when PTS is in development with milestone (alpha/beta) releases but no release candidate (r) or gold versions
	error_reporting(E_ALL | E_NOTICE | E_STRICT);
}

?>
