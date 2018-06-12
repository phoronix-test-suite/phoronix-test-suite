<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2018, Phoronix Media
	Copyright (C) 2008 - 2018, Michael Larabel

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
	public static function installed_tests()
	{
		$cleaned_tests = array();
		$repo = '*';
		$install_root_path = pts_client::test_install_root_path();
		$install_root_path_length = strlen($install_root_path);
		foreach(pts_file_io::glob($install_root_path . $repo . '/*/pts-install.xml') as $identifier_path)
		{
			$cleaned_tests[] = substr(dirname($identifier_path), $install_root_path_length);
		}

		return $cleaned_tests;
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
	public static function local_suites()
	{
		$local_suites = array();
		foreach(pts_file_io::glob(PTS_TEST_SUITE_PATH . 'local/*/suite-definition.xml') as $path)
		{
			$local_suites[] = 'local/' . basename(dirname($path));
		}

		return $local_suites;
	}
	public static function scan_for_error($log_file, $strip_string)
	{
		$error = null;

		foreach(array('fatal error', 'error:', 'error while loading', 'undefined reference', 'returned 1 exit status', 'not found', 'child process excited with status', 'error opening archive', 'failed to load', 'fatal', 'illegal argument') as $error_string)
		{
			if(($e = strripos($log_file, $error_string)) !== false)
			{
				if(($line_end = strpos($log_file, PHP_EOL, $e)) !== false)
				{
					$log_file = substr($log_file, 0, $line_end);
				}

				if(($line_start_e = strrpos($log_file, PHP_EOL)) !== false)
				{
					$log_file = substr($log_file, ($line_start_e + 1));
				}

				$log_file = str_replace(array(PTS_TEST_PROFILE_PATH, $strip_string), null, $log_file);

				if(isset($log_file[8]) && !isset($log_file[144]) && strpos($log_file, PHP_EOL) === false)
				{
					$error = $log_file;
				}
			}
		}

		if($error == null && ($s = strrpos($log_file, PHP_EOL)) !== false && stripos($log_file, 'found', $s) !== false && stripos($log_file, 'no', ($s - 1)) !== false)
		{
			// See if the last line of the log is e.g. 'No OpenCL Environment Found', 'FFFFF Not Found', Etc
			$last_line = trim(substr($log_file, $s));
			if(isset($last_line[8]) && !isset($last_line[144]))
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
	public static function extra_environmental_variables(&$test_profile)
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
		$extra_vars['LANG'] = '';
		$extra_vars['PHP_BIN'] = PHP_BIN;

		// Safe-guards to try to ensure more accurate testing
		$extra_vars['vblank_mode'] = '0'; // Avoid sync to vblank with the open-source drivers
		$extra_vars['__GL_SYNC_TO_VBLANK'] = '0'; // Avoid sync to vblank with the NVIDIA binary drivers
		$extra_vars['CCACHE_DISABLE'] = '1'; // Should avoid ccache being used in compiler tests

		foreach($test_profile->extended_test_profiles() as $i => $this_test_profile)
		{
			if($i == 0)
			{
				$extra_vars['TEST_EXTENDS'] = $this_test_profile->get_install_dir();
			}

			if(is_dir($this_test_profile->get_install_dir()))
			{
				$extra_vars['PATH'] = $this_test_profile->get_install_dir() . ':' . $extra_vars['PATH'];
				$extra_vars['TEST_' . strtoupper(str_replace('-', '_', $this_test_profile->get_identifier_base_name()))] = $this_test_profile->get_install_dir();
			}
		}

		return $extra_vars;
	}
	public static function call_test_script($test_profile, $script_name, $print_string = null, $pass_argument = null, $extra_vars_append = null, $use_ctp = true)
	{
		$extra_vars = pts_tests::extra_environmental_variables($test_profile);

		if(isset($extra_vars_append['PATH']))
		{
			// Special case variable where you likely want the two merged rather than overwriting
			$extra_vars['PATH'] = $extra_vars_append['PATH'] . (substr($extra_vars_append['PATH'], -1) != ':' ? ':' : null) . $extra_vars['PATH'];
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

		$use_phoroscript = phodevi::is_windows();
		if(phodevi::is_windows() && is_executable('C:\cygwin64\bin\bash.exe'))
		{
			$sh = 'C:\cygwin64\bin\bash.exe';
			$use_phoroscript = false;
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
					if($use_phoroscript || pts_client::read_env('USE_PHOROSCRIPT_INTERPRETER') != false)
					{
						echo PHP_EOL . 'Falling back to experimental PhoroScript code path...' . PHP_EOL;
						$phoroscript = new pts_phoroscript_interpreter($run_file, $extra_vars, $test_directory);
						$phoroscript->execute_script($pass_argument);
						$this_result = null;
					}
					else if(phodevi::is_windows())
					{
						$host_env = $_SERVER;
						unset($host_env['argv']);
						$descriptorspec = array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w'));
						$test_process = proc_open($sh . ' ' . $run_file . ' ' . $pass_argument . (phodevi::is_windows() && false ? '' : ' 2>&1'), $descriptorspec, $pipes, $test_directory, array_merge($host_env, pts_client::environmental_variables(), $extra_vars));

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
						$this_result = pts_client::shell_exec('cd ' .  $test_directory . (phodevi::is_windows() ? '; ' : ' && ') . $sh . ' ' . $run_file . ' ' . $pass_argument . (phodevi::is_windows() ? '' : ' 2>&1'), $extra_vars);
					}
				}

				if(trim($this_result) != null)
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
	public static function update_test_install_xml(&$test_profile, $this_duration = 0, $is_install = false, $compiler_data = null, $install_footnote = null)
	{
		// Refresh/generate an install XML for pts-install.xml
		if($test_profile->test_installation == false)
		{
			$test_profile->test_installation = new pts_installed_test($test_profile);
		}
		$xml_writer = new nye_XmlWriter('file://' . PTS_USER_PATH . 'xsl/' . 'pts-test-installation-viewer.xsl');

		$test_duration = $test_profile->test_installation->get_average_run_time();
		if(!is_numeric($test_duration) && !$is_install)
		{
			$test_duration = $this_duration;
		}
		if(!$is_install && is_numeric($this_duration) && $this_duration > 0)
		{
			$test_duration = ceil((($test_duration * $test_profile->test_installation->get_run_count()) + $this_duration) / ($test_profile->test_installation->get_run_count() + 1));
		}

		$compiler_data = $is_install ? $compiler_data : $test_profile->test_installation->get_compiler_data();
		$install_footnote = $is_install ? $install_footnote : $test_profile->test_installation->get_install_footnote();
		$test_version = $is_install ? $test_profile->get_test_profile_version() : $test_profile->test_installation->get_installed_version();
		$test_checksum = $is_install ? $test_profile->get_installer_checksum() : $test_profile->test_installation->get_installed_checksum();
		$sys_identifier = $is_install ? phodevi::system_id_string() : $test_profile->test_installation->get_installed_system_identifier();
		$install_time = $is_install ? date('Y-m-d H:i:s') : $test_profile->test_installation->get_install_date_time();
		$install_time_length = $is_install ? $this_duration : $test_profile->test_installation->get_latest_install_time();
		$latest_run_time = $is_install || $this_duration == 0 ? $test_profile->test_installation->get_latest_run_time() : $this_duration;

		$times_run = $test_profile->test_installation->get_run_count();

		if($is_install)
		{
			$last_run = $latest_run_time;

			if(empty($last_run))
			{
				$last_run = '0000-00-00 00:00:00';
			}
		}
		else
		{
			$last_run = date('Y-m-d H:i:s');
			$times_run++;
		}

		$xml_writer->addXmlNode('PhoronixTestSuite/TestInstallation/Environment/Identifier', $test_profile->get_identifier());
		$xml_writer->addXmlNode('PhoronixTestSuite/TestInstallation/Environment/Version', $test_version);
		$xml_writer->addXmlNode('PhoronixTestSuite/TestInstallation/Environment/CheckSum', $test_checksum);
		$xml_writer->addXmlNode('PhoronixTestSuite/TestInstallation/Environment/CompilerData', json_encode($compiler_data));
		$xml_writer->addXmlNode('PhoronixTestSuite/TestInstallation/Environment/InstallFootnote', $install_footnote);
		$xml_writer->addXmlNode('PhoronixTestSuite/TestInstallation/Environment/SystemIdentifier', $sys_identifier);
		$xml_writer->addXmlNode('PhoronixTestSuite/TestInstallation/History/InstallTime', $install_time);
		$xml_writer->addXmlNode('PhoronixTestSuite/TestInstallation/History/InstallTimeLength', $install_time_length);
		$xml_writer->addXmlNode('PhoronixTestSuite/TestInstallation/History/LastRunTime', $last_run);
		$xml_writer->addXmlNode('PhoronixTestSuite/TestInstallation/History/TimesRun', $times_run);
		$xml_writer->addXmlNode('PhoronixTestSuite/TestInstallation/History/AverageRunTime', $test_duration);
		$xml_writer->addXmlNode('PhoronixTestSuite/TestInstallation/History/LatestRunTime', $latest_run_time);

		$xml_writer->saveXMLFile($test_profile->get_install_dir() . 'pts-install.xml');
	}
	public static function invalid_command_helper($passed_args)
	{
		$showed_recent_results = self::recently_saved_results();

		if(count($result_uploads = pts_openbenchmarking::result_uploads_from_this_ip()) > 0)
		{
			echo PHP_EOL . pts_client::cli_just_bold('Recent OpenBenchmarking.org Results From This IP:') . PHP_EOL;
			$t = array();
			foreach($result_uploads as $id => $title)
			{
				$t[] = array(pts_client::cli_colored_text($id, 'gray', true), $title);

				if(count($t) == 6)
				{
					break;
				}
			}
			echo pts_user_io::display_text_table($t, '- ') . PHP_EOL . PHP_EOL;
		}

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

				foreach(pts_client::saved_test_results() as $result)
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
			if(isset($similar_tests[12]))
			{
				// lots of tests... trim it down
				$similar_tests = array_rand($similar_tests, 12);
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
	public static function recently_saved_results()
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
			echo PHP_EOL . pts_client::cli_just_bold('Recently Saved Test Results:') . PHP_EOL;
			echo pts_user_io::display_text_list($recent_results) . PHP_EOL;
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
