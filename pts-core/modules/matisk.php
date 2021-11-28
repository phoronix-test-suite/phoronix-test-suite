<?php
/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2016, Michael Larabel
	Copyright (C) 2016, Phoronix Media

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

class matisk extends pts_module_interface
{
	const module_name = 'MATISK';
	const module_version = '1.2.0';
	const module_description = 'My Automated Test Infrastructure Setup Kit';
	const module_author = 'Michael Larabel';

	// For values array template [0] = default value [1] = description
	private static $ini_struct = array(
		'workload' =>
			array(
			'save_results' => array(true, 'A boolean value of whether to save the test results.'),
			'suite' => array(null, 'A string that is an XML test suite for the Phoronix Test Suite. If running a custom collection of tests/suites, first run phoronix-test-suite build-suite.'),
			'save_name' => array(null, 'The string to save the test results as.'),
			'description' => array(null, 'The test description string.'),
			'result_identifier' => array(null, 'The test result identifier string, unless using contexts.')
			),
		'installation' =>
			array(
			'install_check' => array(true, 'Check to see that all tests/suites are installed prior to execution.'),
			'force_install' => array(false, 'Force all tests/suites to be re-installed each time prior to execution.'),
			'external_download_cache' => array(null, 'The option to specify a non-standard PTS External Dependencies download cache directory.'),
			'block_phodevi_caching' => array(false, 'If Phodevi should not be caching any hardware/software information.')
			),
		'general' =>
			array(
			'upload_to_openbenchmarking' => array(false, 'A boolean value whether to automatically upload the test result to OpenBenchmarking.org'), // Automatic upload to OpenBenchmarking?
	//		'open_browser' => false, // Automatically launch web browser to show the results?
			),
		'environment_variables' =>
			array(
			'EXAMPLE_VAR' => array('EXAMPLE', 'The environment_variables section allows key = value pairs of environment variables to be set by default.')
			),
		'set_context' =>
			array(
			'The pre_install or pre_run fields must be used when using the MATISK context testing functionality. The set_context fields must specify an executable file for setting the context of the system. Passed as the first argument to the respective file is the context string defined by the contexts section of this file. If any of the set_context scripts emit an exit code of 8, the testing process will abort immediately. If any of the set_context scripts emit an exit code of 9, the testing process will skip executing the suite on the current context.',
			'pre_install' => array(null, 'An external file to be used for setting the system context prior to test installation.'),
			'pre_run' => array(null, 'An external file to be used for setting the system context prior to test execution.'),
	//		'interim_run' => array(null, 'An external file to be used for setting the system context in between tests in the execution queue.'),
			'post_install' => array(null, 'An external file to be used for setting the system context after the test installation.'),
			'post_run' => array(null, 'An external file to be used for setting the system context after all tests have been executed.'),
			'reboot_support' => array(false, 'If any of the context scripts cause the system to reboot, set this value to true and the Phoronix Test Suite will attempt to automatically recover itself upon reboot.'),
			'context' => array(array(), 'An array of context values.'),
			'external_contexts' => array(null, 'An external file for loading a list of contexts, if not loading the list of contexts via the context array in this file. If the external file is a script it will be executed and the standard output will be used for parsing the contexts.'),
			'external_contexts_delimiter' => array('EOL', 'The delimiter for the external_contexts contexts list. Special keyword: EOL will use a line break as the delimiter and TAB will use a tab as a delimiter.'),
			'reverse_context_order' => array(false, 'A boolean value of whether to reverse the order (from bottom to top) for the execution of a list of contexts.'),
			'log_context_outputs' => array(false, 'A boolean value of whether to log the output of the set-context scripts to ~/.phoronix-test-suite/modules-data/matisk/')
			)
		);

	private static $context = null;
	private static $ini = array();
	private static $matisk_config_dir = null;
	private static $skip_test_set = false;

	public static function module_info()
	{
		return null;
	}
	public static function user_commands()
	{
		return array('run' => 'run_matisk', 'template' => 'template');
	}
	public static function template()
	{
		echo PHP_EOL . '; Sample INI Configuration Template For Phoronix Test Suite MATISK' . PHP_EOL . '; http://www.phoronix-test-suite.com/' . PHP_EOL . PHP_EOL;

		foreach(self::$ini_struct as $section => $items)
		{
			echo PHP_EOL . '[' . $section . ']' . PHP_EOL;

			foreach($items as $key => $r)
			{
				if(!is_array($r))
				{
					echo PHP_EOL . '; ' . wordwrap($r, 80, PHP_EOL . '; ', true) . PHP_EOL . PHP_EOL;
					continue;
				}
				list($default_value, $description) = $r;

				if($description != null)
				{
					echo '; ' . wordwrap($description, 80, PHP_EOL . '; ', true);

					if($default_value !== null && $default_value != array())
					{
						echo ' The default value is ';

						if($default_value === true || $default_value === false)
						{
							echo $default_value === true ? 'TRUE' : 'FALSE';
						}
						else
						{
							echo $default_value;
						}

						echo '.';
					}

					echo PHP_EOL;
				}

				if(is_array($default_value))
				{
					$default_value = isset($default_value[0]) ? $default_value[0] : null;
					echo $key . '[] = ' . $default_value . PHP_EOL;
					echo $key . '[] = ' . $default_value;
				}
				else
				{
					echo $key . ' = ';

					if($default_value === true || $default_value === false)
					{
						echo $default_value === true ? 'TRUE' : 'FALSE';
					}
					else
					{
						echo $default_value;
					}
				}

				echo PHP_EOL . PHP_EOL;
			}
		}
	}
	private static function find_file($file)
	{
		if(is_file($file))
		{
			$file = $file;
		}
		else if(is_file(self::$matisk_config_dir . $file))
		{
			$file = self::$matisk_config_dir . $file;
		}
		else
		{
			$file = false;
		}

		return $file;
	}
	public static function run_matisk($args)
	{
		echo PHP_EOL . 'MATISK For The Phoronix Test Suite' . PHP_EOL;

		if(!isset($args[0]) || !is_file($args[0]))
		{
			echo PHP_EOL . 'You must specify a MATISK INI file to load.' . PHP_EOL . PHP_EOL;
			return false;
		}
		self::$matisk_config_dir = dirname($args[0]) . '/';
		pts_file_io::mkdir(pts_module::save_dir());

		$ini = parse_ini_file($args[0], true);

		foreach(self::$ini_struct as $section => $items)
		{
			foreach($items as $key => $r)
			{
				if(is_array($r) && !isset($ini[$section][$key]))
				{
					$ini[$section][$key] = $r[0];
				}
			}
		}

		// Checks
		if(pts_test_suite::is_suite($ini['workload']['suite']) == false)
		{
			// See if the XML suite-definition was just tossed into the same directory
			if(($xml_file = self::find_file($ini['workload']['suite'] . '.xml')) !== false)
			{
				pts_file_io::mkdir(PTS_TEST_SUITE_PATH . 'local/' . $ini['workload']['suite']);
				copy($xml_file, PTS_TEST_SUITE_PATH . 'local/' . $ini['workload']['suite'] . '/suite-definition.xml');
			}

			if(pts_test_suite::is_suite($ini['workload']['suite']) == false)
			{
				echo PHP_EOL . 'A test suite must be specified to execute. If a suite needs to be constructed, run: ' . PHP_EOL . 'phoronix-test-suite build-suite' . PHP_EOL . PHP_EOL;
				return false;
			}
		}

		if($ini['set_context']['external_contexts'] != null)
		{
			switch($ini['set_context']['external_contexts_delimiter'])
			{
				case 'EOL':
				case '':
					$ini['set_context']['external_contexts_delimiter'] = PHP_EOL;
					break;
				case 'TAB':
					$ini['set_context']['external_contexts_delimiter'] = "\t";
					break;
			}

			if(($ff = self::find_file($ini['set_context']['external_contexts'])))
			{
				if(is_executable($ff))
				{
					$ini['set_context']['context'] = shell_exec($ff . ' 2> /dev/null');
				}
				else
				{
					$ini['set_context']['context'] = file_get_contents($ff);
				}
			}
			else
			{
				// Hopefully it's a command to execute then...
				$ini['set_context']['context'] = shell_exec($ini['set_context']['external_contexts'] . ' 2> /dev/null');
			}

			$ini['set_context']['context'] = explode($ini['set_context']['external_contexts_delimiter'], $ini['set_context']['context']);
		}
		else if($ini['set_context']['context'] != null && !is_array($ini['set_context']['context']))
		{
			$ini['set_context']['context'] = array($ini['set_context']['context']);
		}

		if(is_array($ini['set_context']['context']) && count($ini['set_context']['context']) > 0)
		{
			foreach($ini['set_context']['context'] as $i => $context)
			{
				if($context == null)
				{
					unset($ini['set_context']['context'][$i]);
				}
			}

			// Context testing
			if(count($ini['set_context']['context']) > 0 && $ini['set_context']['pre_run'] == null && $ini['set_context']['pre_install'] == null)
			{
				echo PHP_EOL . 'The pre_run or pre_install set_context fields must be set in order to set the system\'s context.' . PHP_EOL;
				return false;
			}

			if($ini['set_context']['reverse_context_order'])
			{
				$ini['set_context']['context'] = array_reverse($ini['set_context']['context']);
			}
		}

		if(pts_strings::string_bool($ini['workload']['save_results']))
		{
			if($ini['workload']['save_name'] == null)
			{
				echo PHP_EOL . 'The save_name field cannot be left empty when saving the test results.' . PHP_EOL;
				return false;
			}
			/*
			if($ini['workload']['result_identifier'] == null)
			{
				echo PHP_EOL . 'The result_identifier field cannot be left empty when saving the test results.' . PHP_EOL;
				return false;
			}
			*/
		}

		if(!empty($ini['environment_variables']) && is_array($ini['environment_variables']))
		{
			foreach($ini['environment_variables'] as $key => $value)
			{
				putenv(trim($key) . '=' . trim($value));
			}
		}

		if(empty($ini['set_context']['context']))
		{
			$ini['set_context']['context'] = array($ini['workload']['result_identifier']);
		}

		if(pts_strings::string_bool($ini['set_context']['log_context_outputs']))
		{
			pts_file_io::mkdir(pts_module::save_dir() . $ini['workload']['save_name']);
		}

		$spent_context_file = pts_module::save_dir() . $ini['workload']['save_name'] . '.spent-contexts';
		if(!is_file($spent_context_file))
		{
			touch($spent_context_file);
		}
		else
		{
			// If recovering from an existing run, don't rerun contexts that were already executed
			$spent_contexts = pts_file_io::file_get_contents($spent_context_file);
			$spent_contexts = explode(PHP_EOL, $spent_contexts);

			foreach($spent_contexts as $sc)
			{
				if(($key = array_search($sc, $ini['set_context']['context'])) !== false)
				{
					unset($ini['set_context']['context'][$key]);
				}
			}
		}

		if($ini['set_context']['reboot_support'] && phodevi::is_linux())
		{
			// In case a set-context involves a reboot, auto-recover
			$xdg_config_home = is_dir('/etc/xdg/autostart') && is_writable('/etc/xdg/autostart') ? '/etc/xdg/autostart' : getenv('XDG_CONFIG_HOME');

			if($xdg_config_home == false)
			{
				$xdg_config_home = pts_core::user_home_directory() . '.config';
			}

			if($xdg_config_home != false && is_dir($xdg_config_home))
			{
				$autostart_dir = $xdg_config_home . '/autostart/';
				pts_file_io::mkdir($xdg_config_home . '/autostart/');
			}
			file_put_contents($xdg_config_home . '/autostart/phoronix-test-suite-matisk.desktop', '
[Desktop Entry]
Name=Phoronix Test Suite Matisk Recovery
GenericName=Phoronix Test Suite
Comment=Matisk Auto-Recovery Support
Exec=gnome-terminal -e \'phoronix-test-suite matisk ' . $args[0] . '\'
Icon=phoronix-test-suite
Type=Application
Encoding=UTF-8
Categories=System;Monitor;');
		}

		if($ini['installation']['block_phodevi_caching'])
		{
			// Block Phodevi caching if changing out system components and there is a chance one of the strings of changed contexts might be cached (e.g. OpenGL user-space driver)
			phodevi::$allow_phodevi_caching = false;
		}

		if(phodevi::system_uptime() < 60)
		{
			echo PHP_EOL . 'Sleeping 45 seconds while waiting for the system to settle...' . PHP_EOL;
			sleep(45);
		}

		self::$ini = $ini;
		$total_context_count = count(self::$ini['set_context']['context']);
		while(($context = array_shift(self::$ini['set_context']['context'])) !== null)
		{
			echo PHP_EOL . ($total_context_count - count(self::$ini['set_context']['context'])) . ' of ' . $total_context_count . ' in test execution queue [' . $context . ']' . PHP_EOL . PHP_EOL;
			self::$context = $context;

			if(pts_strings::string_bool(self::$ini['installation']['install_check']) || $ini['set_context']['pre_install'] != null)
			{
				self::process_user_config_external_hook_process('pre_install');
				$force_install = false;
				$no_prompts = true;
				if(pts_strings::string_bool(self::$ini['installation']['force_install']))
				{
					$force_install = true;
				}

				if(self::$ini['installation']['external_download_cache'] != null)
				{
					pts_test_install_manager::add_external_download_cache(self::$ini['installation']['external_download_cache']);
				}

				// Do the actual test installation
				pts_test_installer::standard_install(self::$ini['workload']['suite'], $force_install, $no_prompts);
				self::process_user_config_external_hook_process('post_install');
			}

			$batch_mode = false;
			$auto_mode = true;
			$test_run_manager = new pts_test_run_manager($batch_mode, $auto_mode);
			if($test_run_manager->initial_checks(self::$ini['workload']['suite']) == false)
			{
				return false;
			}

			if(self::$skip_test_set == false)
			{
				self::process_user_config_external_hook_process('pre_run');

				// Load the tests to run
				if($test_run_manager->load_tests_to_run(self::$ini['workload']['suite']) == false)
				{
					return false;
				}

				// Save results?
				$result_identifier = $ini['workload']['result_identifier'];
				if($result_identifier == null)
				{
					$result_identifier = '$MATISK_CONTEXT';
				}
				// Allow $MATIISK_CONTEXT as a valid user variable to pass it...
				$result_identifier = str_replace('$MATISK_CONTEXT', self::$context, $result_identifier);

				$test_run_manager->set_save_name(self::$ini['workload']['save_name']);
				$test_run_manager->set_results_identifier($result_identifier);
				$test_run_manager->set_description(self::$ini['workload']['description']);

				// Don't upload results unless it's the last in queue where the context count is now 0
				$test_run_manager->auto_upload_to_openbenchmarking((count(self::$ini['set_context']['context']) == 0 && self::$ini['general']['upload_to_openbenchmarking']));

				// Run the actual tests
				$test_run_manager->pre_execution_process();
				$test_run_manager->call_test_runs();
				$test_run_manager->post_execution_process();
			}

			self::$skip_test_set = false;
			file_put_contents($spent_context_file, self::$context . PHP_EOL, FILE_APPEND);
			pts_file_io::unlink(pts_module::save_dir() . self::$context . '.last-call');
			self::process_user_config_external_hook_process('post_run');
		}

		unlink($spent_context_file);
		isset($xdg_config_home) && pts_file_io::unlink($xdg_config_home . '/autostart/phoronix-test-suite-matisk.desktop');
	}
	protected static function process_user_config_external_hook_process($process)
	{
		// Check to not run the same process
		$last_call_file = pts_module::save_dir() . self::$context . '.last-call';
		if(is_file($last_call_file))
		{
			$check = pts_file_io::file_get_contents($last_call_file);

			if($process == $check)
			{
				unlink($last_call_file);
				return false;
			}
		}
		$process != 'post_run' && file_put_contents($last_call_file, $process);

		if(self::$ini['set_context'][$process])
		{
			$command = self::find_file(self::$ini['set_context'][$process]) ? self::find_file(self::$ini['set_context'][$process]) : self::$ini['set_context'][$process];
			$descriptor_spec = array(
				0 => array('pipe', 'r'),
				1 => array('pipe', 'w'),
				2 => array('pipe', 'w')
				);

			$env_vars = null;
			pts_client::$display->test_run_instance_error('Running ' . $process . ' set-context script.');
			if(is_executable($command))
			{
				// Pass the context as the first argument to the string
				$command .= ' ' . self::$context;
			}
			else
			{
				// Else find $MATISK_CONTEXT in the command string
				$command = str_replace('$MATISK_CONTEXT', self::$context, $command);
			}

			$proc = proc_open($command, $descriptor_spec, $pipes, null, $env_vars);
			echo $std_output = stream_get_contents($pipes[1]);
			$return_value = proc_close($proc);

			if(pts_strings::string_bool(self::$ini['set_context']['log_context_outputs']))
			{
				file_put_contents(pts_module::save_dir() . self::$ini['workload']['save_name'] . '/' . self::$context . '-' . $process . '.txt', $std_output);
			}

			switch($return_value)
			{
				case 8:
					exit(0);
				case 9:
					self::$skip_test_set = true;
					break;
			}
		}
	}
}

?>
