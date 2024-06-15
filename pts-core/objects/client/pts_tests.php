<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2022, Phoronix Media
	Copyright (C) 2008 - 2022, Michael Larabel

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

class pts_tests
{
	public static $extra_env_vars = null;
	protected static $override_test_script_execution_handler = false;

	public static function add_extra_env_var($name, $value)
	{
		self::$extra_env_vars[$name] = $value;
	}
	public static function clear_extra_env_vars()
	{
		self::$extra_env_vars = array();
	}
	public static function remove_installed_test(&$test_profile)
	{
		pts_file_io::delete($test_profile->get_install_dir(), array('pts-install.json'), true);

		if($test_profile->test_installation)
		{
			$test_profile->test_installation->set_install_status('REMOVED');
			$test_profile->test_installation->save_test_install_metadata();
		}
	}
	public static function tests_installations_with_metadata()
	{
		$tests = array();
		$repo = '*';
		$install_root_path = pts_client::test_install_root_path();
		$install_root_path_length = strlen($install_root_path);
		foreach(pts_file_io::glob($install_root_path . $repo . '/*/pts-install.json') as $identifier_path)
		{
			$test_identifier = substr(dirname($identifier_path), $install_root_path_length);
			$tests[] = new pts_test_profile($test_identifier);
		}

		return $tests;
	}
	public static function installed_tests($return_objects = false)
	{
		$cleaned_tests = array();
		foreach(pts_tests::tests_installations_with_metadata() as $test_profile)
		{
			if($test_profile->test_installation && $test_profile->test_installation->is_installed())
			{
				if($return_objects)
				{
					$cleaned_tests[] = $test_profile;
				}
				else
				{
					$cleaned_tests[] = $test_profile->get_identifier();
				}
			}
		}

		return $cleaned_tests;
	}
	public static function tests_failed_install()
	{
		$failed_tests = array();
		foreach(pts_tests::tests_installations_with_metadata() as $test_profile)
		{
			if($test_profile->test_installation && $test_profile->test_installation->get_install_status() == 'INSTALL_FAILED')
			{
				$failed_tests[] = $test_profile;
			}
		}

		return $failed_tests;
	}
	public static function partially_installed_tests()
	{
		$cleaned_tests = array();
		$repo = '*';
		$install_root_path = pts_client::test_install_root_path();
		$install_root_path_length = strlen($install_root_path);
		foreach(pts_file_io::glob($install_root_path . $repo . '/*') as $identifier_path)
		{
			$cleaned_tests[] = substr($identifier_path, $install_root_path_length);
		}

		return $cleaned_tests;
	}
	public static function local_tests()
	{
		$local_tests = array();
		foreach(pts_file_io::glob(PTS_TEST_PROFILE_PATH . 'local/*/test-definition.xml') as $path)
		{
			$local_tests[] = 'local/' . basename(dirname($path));
		}

		return $local_tests;
	}
	public static function scan_for_error($log_file, $strip_string)
	{
		$error = null;

		if(empty($log_file))
		{
			return $error;
		}

		foreach(array('fatal error', 'error while loading', 'undefined reference', 'cannot find -l', 'error:', 'returned 1 exit status', 'you must install', 'not found', 'child process excited with status', 'error opening archive', 'failed to load', 'fatal', 'illegal argument', 'is required to build', 'or higher is required', ': No such file or directory', 'not enough slots', 'mpirun noticed that process', 'permission denied', 'connection refused', 'MPI_ABORT was invoked', 'mpirun was unable to launch', 'error adding symbols:', 'not set and cannot find', 'please set JAVA', '/usr/bin/which: no ') as $error_string)
		{
			$lf = $log_file;
			if(($e = strripos($lf, $error_string)) !== false)
			{
				if(($line_end = strpos($lf, PHP_EOL, $e)) !== false)
				{
					$lf = substr($lf, 0, $line_end);
				}

				if(($line_start_e = strrpos($lf, PHP_EOL)) !== false)
				{
					$lf = substr($lf, ($line_start_e + 1));
				}

				$lf = str_replace(array(PTS_TEST_PROFILE_PATH, $strip_string), '', $lf);

				if(isset($lf[8]) && substr($lf, -7) == 'error: ')
				{
					continue;
				}

				if(isset($lf[8]) && !isset($lf[255]) && strpos($lf, PHP_EOL) === false)
				{
					$error = $lf;
					break;
				}
			}
		}

		if($error == null)
		{
			foreach(explode(PHP_EOL, $log_file) as $log_line)
			{
				if((stripos($log_line, 'checking ') !== false || stripos($log_line, 'looking for ') !== false) && stripos($log_line, 'missing') !== false)
				{
					$error = trim($log_line);
				}
			}
		}

		if($error == null && ($s = strrpos($log_file, PHP_EOL)) !== false && stripos($log_file, 'found', $s) !== false && stripos($log_file, 'no', ($s - 1)) !== false)
		{
			// See if the last line of the log is e.g. 'No OpenCL Environment Found', 'FFFFF Not Found', Etc
			$last_line = trim(substr($log_file, $s));
			if(isset($last_line[8]) && !isset($last_line[255]))
			{
				$error = $last_line;
			}
		}

		return $error;
	}
	public static function pretty_error_string($error)
	{
		if(($t = strpos($error, '.h: No such file')) !== false)
		{
			$pretty_error = substr($error, strrpos($error, ' ', (0 - (strlen($error) - $t))));
			$pretty_error = substr($pretty_error, 0, strpos($pretty_error, ':'));

			if(isset($pretty_error[2]))
			{
				$error = 'Missing Header File: ' . trim($pretty_error);
			}
		}
		else if(($t = strpos($error, 'configure: error: ')) !== false)
		{
			$pretty_error = substr($error, ($t + strlen('configure: error: ')));

			if(($t = strpos($pretty_error, 'not found.')) !== false)
			{
				$pretty_error = substr($pretty_error, 0, ($t + strlen('not found.')));
			}

			$error = $pretty_error;
		}
		else if(($t = strpos($error, ': not found')) !== false)
		{
			$pretty_error = substr($error, 0, $t);
			$pretty_error = substr($pretty_error, (strrpos($pretty_error, ' ') + 1));
			$error = 'Missing Command: ' . $pretty_error;
		}

		if(($x = strpos($error, 'See docs')) !== false)
		{
			$error = substr($error, 0, $x);
		}

		return trim($error);
	}
	public static function scan_for_file_missing_from_error($error)
	{
		$reverse_dep_look_for_files = array();
		if(($e = strpos($error, 'cannot find -l')) !== false)
		{
			// Missing library
			$lib_needed = trim(substr($error, $e + strlen('cannot find -l')));

			if($lib_needed)
			{
				$reverse_dep_look_for_files = array('lib' . $lib_needed . '.so', $lib_needed);
			}
		}
		else if(($e = stripos($error, 'Missing Header File:')) !== false)
		{
			// Missing library
			$lib_needed = trim(substr($error, $e + strlen('Missing Header File:')));

			if($lib_needed)
			{
				$reverse_dep_look_for_files[] = $lib_needed;
			}
		}
		else if(($e = stripos($error, ' for ')) !== false && ($ex = stripos($error, ' not found')) !== false)
		{
			// Missing library
			$lib_needed = trim(substr($error, 0, $e));

			if($lib_needed)
			{
				$reverse_dep_look_for_files[] = $lib_needed;
			}
		}
		else if(($e = stripos($error, ': Command not found')) !== false)
		{
			// Missing library
			$lib_needed = ' ' . substr($error, 0, $e);
			$lib_needed = trim(substr($lib_needed, strrpos($lib_needed, ' ') + 1));

			if($lib_needed)
			{
				$reverse_dep_look_for_files[] = $lib_needed;
			}
		}
		else if(stripos($error, 'fatal error') !== false && ($e = stripos($error, ': No such file or directory')) !== false)
		{
			// Missing library
			$lib_needed = ' ' . substr($error, 0, $e);
			$lib_needed = trim(substr($lib_needed, strrpos($lib_needed, ' ') + 1));

			if($lib_needed)
			{
				$reverse_dep_look_for_files[] = $lib_needed;
			}
		}
		else if(($e = stripos($error, ' is required')) !== false)
		{
			// Missing library
			$lib_needed = ' ' . substr($error, 0, $e);
			$lib_needed = trim(substr($lib_needed, strrpos($lib_needed, ' ') + 1));

			if($lib_needed)
			{
				$reverse_dep_look_for_files[] = $lib_needed;
			}
		}

		return $reverse_dep_look_for_files;
	}
	public static function extra_environment_variables(&$test_profile)
	{
		$extra_vars = array();

		if(is_array(self::$extra_env_vars))
		{
			$extra_vars = self::$extra_env_vars;
		}

		$extra_vars['HOME'] = $test_profile->get_install_dir();
		$extra_vars['DEBUG_HOME'] = $test_profile->get_install_dir();
		$extra_vars['PATH'] = "\$PATH";
		$extra_vars['LC_ALL'] = '';
		$extra_vars['LC_NUMERIC'] = '';
		$extra_vars['LC_CTYPE'] = '';
		$extra_vars['LC_MESSAGES'] = '';
		$extra_vars['LANG'] = 'en_US.utf8';
		$extra_vars['PHP_BIN'] = PHP_BIN;
		$extra_vars['RANDOM_AVAILABLE_PORT'] = pts_network::find_available_port();

		// Safe-guards to try to ensure more accurate testing
		$extra_vars['vblank_mode'] = '0'; // Avoid sync to vblank with the open-source drivers
		$extra_vars['MESA_VK_WSI_PRESENT_MODE'] = 'immediate'; // https://cgit.freedesktop.org/mesa/mesa/commit/?id=a182adfd83ad00e326153b00a725a014e0359bf0
		$extra_vars['__GL_SYNC_TO_VBLANK'] = '0'; // Avoid sync to vblank with the NVIDIA binary drivers
		$extra_vars['CCACHE_DISABLE'] = '1'; // Should avoid ccache being used in compiler tests
		$extra_vars['OMPI_ALLOW_RUN_AS_ROOT'] = '1'; // Tests with mpirun should use --allow-run-as-root but otherwise this fallback
		$extra_vars['OMPI_ALLOW_RUN_AS_ROOT_CONFIRM'] = '1'; // Tests with mpirun should use --allow-run-as-root but otherwise this fallback

		foreach($test_profile->extended_test_profiles() as $i => $this_test_profile)
		{
			if($i == 0)
			{
				$extra_vars['TEST_EXTENDS'] = $this_test_profile->get_install_dir();
			}

			if(is_dir($this_test_profile->get_install_dir()))
			{
				$extra_vars['PATH'] = $this_test_profile->get_install_dir() . pts_client::get_path_separator() . $extra_vars['PATH'];
				$extra_vars['TEST_' . strtoupper(str_replace('-', '_', $this_test_profile->get_identifier_base_name()))] = $this_test_profile->get_install_dir();
			}
		}

		return $extra_vars;
	}
	public static function call_test_script($test_profile, $script_name, $print_string = null, $pass_argument = null, $extra_vars_append = null, $use_ctp = true, $no_prompts = false)
	{
		$extra_vars = pts_tests::extra_environment_variables($test_profile);

		if(isset($extra_vars_append['PATH']))
		{
			// Special case variable where you likely want the two merged rather than overwriting
			$extra_vars['PATH'] = $extra_vars_append['PATH'] . (substr($extra_vars_append['PATH'], -1) != pts_client::get_path_separator() ? pts_client::get_path_separator() : null) . $extra_vars['PATH'];
			unset($extra_vars_append['PATH']);
		}

		if(is_array($extra_vars_append))
		{
			$extra_vars = array_merge($extra_vars, $extra_vars_append);
		}

		$result = null;
		$test_directory = $test_profile->get_install_dir();
		pts_file_io::mkdir($test_directory, 0777, true);
		$os_postfix = '_' . strtolower(phodevi::os_under_test());
		$test_profiles = array($test_profile);

		if($use_ctp)
		{
			$test_profiles = array_merge($test_profiles, $test_profile->extended_test_profiles());
		}

		if(phodevi::is_windows() && is_executable('C:\cygwin64\bin\bash.exe'))
		{
			$sh = 'C:\cygwin64\bin\bash.exe';
			$extra_vars['PATH'] = $extra_vars['PATH'] . ';C:\cygwin64\bin';
		}
		else if(pts_client::executable_in_path('bash'))
		{
			$sh = 'bash';
		}
		else
		{
			$sh = 'sh';
		}

		foreach($test_profiles as &$this_test_profile)
		{
			$test_resources_location = $this_test_profile->get_resource_dir();

			if(is_file(($run_file = $test_resources_location . $script_name . $os_postfix . '.sh')) || is_file(($run_file = $test_resources_location . $script_name . '.sh')))
			{
				if(!empty($print_string))
				{
					pts_client::$display->test_run_message($print_string);
				}

				if(self::$override_test_script_execution_handler && is_callable(self::$override_test_script_execution_handler))
				{
					$this_result = call_user_func(self::$override_test_script_execution_handler, $test_directory, $sh, $run_file, $pass_argument, $extra_vars, $this_test_profile);
				}

				// if override_test_script_execution_handler returned -1, fallback to using normal script handler
				if(!isset($this_result) || $this_result == '-1')
				{
					if(phodevi::is_windows())
					{
						$host_env = $_SERVER;
						unset($host_env['argv']);
						$descriptorspec = array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w'));
						$test_process = proc_open($sh . ' "' . $run_file . '" ' . $pass_argument . (phodevi::is_windows() && false ? '' : ' 2>&1'), $descriptorspec, $pipes, $test_directory, array_merge($host_env, pts_client::environment_variables(), $extra_vars));

						if(is_resource($test_process))
						{
							//echo proc_get_status($test_process)['pid'];
							$this_result = stream_get_contents($pipes[1]);
							fclose($pipes[1]);
							fclose($pipes[2]);
							$return_value = proc_close($test_process);
						}
					}
					else
					{
						if($script_name == 'install' && $no_prompts == false && $test_profile->is_root_install_required() && !phodevi::is_root())
						{
							$sh .= ' ' . PTS_CORE_STATIC_PATH . 'root-access.sh';
						}
						$this_result = pts_client::shell_exec('cd ' .  $test_directory . (phodevi::is_windows() ? '; ' : ' && ') . $sh . ' ' . $run_file . ' ' . $pass_argument . (phodevi::is_windows() ? '' : ' 2>&1'), $extra_vars);
					}
				}

				if($this_result && trim($this_result) != null)
				{
					$result = $this_result;
				}
			}
		}

		return $result;
	}
	public static function override_script_test_execution_handler($to_call)
	{
		if(is_callable($to_call))
		{
			self::$override_test_script_execution_handler = $to_call;
			return true;
		}
		return false;
	}
	public static function recently_saved_results($extra_space = null)
	{
		$recent_results = pts_tests::test_results_by_date();

		if(count($recent_results) > 0)
		{
			$recent_results = array_slice($recent_results, 0, 5, true);
			$res_length = strlen(pts_strings::find_longest_string($recent_results)) + 2;
			$current_time = time();

			foreach($recent_results as $m_time => &$recent_result)
			{
				$days = floor(($current_time - $m_time) / 86400);
				$recent_result = sprintf('%-' . $res_length . 'ls [%-ls]', $recent_result, ($days == 0 ? 'Today' : pts_strings::days_ago_format_string($days) . ' old'));
			}
			echo PHP_EOL . $extra_space . pts_client::cli_just_bold('Recently Saved Test Results:') . PHP_EOL;
			echo pts_user_io::display_text_list($recent_results, $extra_space . '   ') . PHP_EOL;
			return true;
		}

		return false;
	}
	public static function test_results_by_date()
	{
		$results = array();
		foreach(pts_file_io::glob(PTS_SAVE_RESULTS_PATH . '*/composite.xml') as $composite)
		{
			$results[filemtime($composite)] = basename(dirname($composite));
		}
		krsort($results);

		return $results;
	}
	public static function search_test_results($query, $search = 'RESULTS')
	{
		$matches = array();

		foreach(self::test_results_by_date() as $file)
		{
			$result_file = new pts_result_file($file);

			if(($search == 'ALL' || $search == 'INFO') && (stripos($result_file->get_title(), $query) !== false || stripos($result_file->get_description(), $query) !== false))
			{
				$matches[] = $file;
				continue;
			}

			if($search == 'ALL' || $search == 'SYSTEM_INFO')
			{
				$matched = false;
				foreach($result_file->get_systems() as $s)
				{
					if(stripos($s->get_software(), $query) !== false || stripos($s->get_identifier(), $query) !== false || stripos($s->get_hardware(), $query) !== false)
					{
						$matches[] = $file;
						$matched = true;
						break;
					}
				}

				if($matched)
				{
					continue;
				}
			}

			if($search == 'ALL' || $search == 'RESULTS')
			{
				$matched = false;
				$result_titles = array();
				foreach($result_file->get_result_objects() as $result_object)
				{
					$result_identifier = $result_object->test_profile->get_title();

					if(stripos($result_identifier, $query) !== false)
					{
						$matches[] = $file;
						$matched = true;
						break;
					}
				}

				if($matched)
				{
					continue;
				}
			}

		}

		return $matches;
	}
}

?>
