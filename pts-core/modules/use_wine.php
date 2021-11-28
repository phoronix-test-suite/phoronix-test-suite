<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2018, Phoronix Media
	Copyright (C) 2018, Michael Larabel

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

class use_wine extends pts_module_interface
{
	const module_name = 'Utilize Wine On Linux Benchmarking';
	const module_version = '1.0.0';
	const module_description = 'This module when activated via the USE_WINE environment variable on Linux systems will override the test profile OS target to Windows and attempt to run the (Windows) tests under Wine, if installed on the system. USE_WINE can be either set to the name of the desired wine command or the absolute path to the wine binary you wish to use for benchmarking.';
	const module_author = 'Michael Larabel';

	protected static $wine_bin = false;
	protected static $original_os_under_test = null;
	public static function module_environment_variables()
	{
		return array('USE_WINE');
	}
	public static function __startup()
	{
		$use_wine = getenv('USE_WINE');
		if(is_executable($use_wine) || ($use_wine = pts_client::executable_in_path($use_wine)) !== false)
		{
			echo pts_client::cli_just_bold('Using Wine For Benchmarks: ') . $use_wine . PHP_EOL;
		}
		else
		{
			return pts_module::MODULE_UNLOAD;
		}

		echo pts_client::cli_just_bold('Wine Version: ') . phodevi::read_property('system', 'wine-version') . PHP_EOL;

		$overrode = pts_tests::override_script_test_execution_handler(array('use_wine', 'test_script_handler'));

		if(!$overrode)
		{
			echo 'Failed to override the test script handler.' . PHP_EOL;
			return pts_module::MODULE_UNLOAD;
		}

		// Override the operating system string that is queried by test download/installation code for determining the OS-specific code paths in test profiles...
		self::$original_os_under_test = phodevi::os_under_test();
		phodevi::os_under_test(true, 'Windows');

		// Set $wine_bin to the Wine binary specified via USE_WINE
		self::$wine_bin = $use_wine;
	}
	public static function __pre_test_install(&$test_install_request)
	{
		// Restore the os_under_test back to the original OS type so it will use its native test script if it's explicitly using Wine...
		if(in_array('wine', $test_install_request->test_profile->get_external_dependencies()))
		{
			phodevi::os_under_test(true, self::$original_os_under_test);
		}
	}
	public static function __post_test_install(&$test_install_request)
	{
		// Reset the Wine override
		if(in_array('wine', $test_install_request->test_profile->get_external_dependencies()))
		{
			phodevi::os_under_test(true, 'Windows');
		}
	}
	public static function __pre_test_run(&$test_run_request)
	{
		// Restore the os_under_test back to the original OS type so it will use its native test script if it's explicitly using Wine...
		if(in_array('wine', $test_run_request->test_profile->get_external_dependencies()))
		{
			phodevi::os_under_test(true, self::$original_os_under_test);
		}
	}
	public static function __post_test_run(&$test_run_request)
	{
		// Reset the Wine override
		if(in_array('wine', $test_run_request->test_profile->get_external_dependencies()))
		{
			phodevi::os_under_test(true, 'Windows');
		}
	}
	public static function test_script_handler($test_directory, $shell, $run_file, $pass_argument, $extra_vars, $test_profile)
	{
		// Rather than conventional PTS code paths, whenever a pre/install/post/interim test profile script is to be executed, this function will now be called by PTS for its execution

		if(in_array('wine', $test_profile->get_external_dependencies()))
		{
			// The test is a wine-focused test already, so don't try to further re-customize it...
			return -1;
		}

		// Let's make a temporary file in test_directory so we can modify the script before execution....
		$new_run_file = dirname($run_file) . '/' . 'wine_' . basename($run_file);
		touch($new_run_file);

		if(!is_file($run_file))
		{
			echo 'Problem finding: ' . $run_file . PHP_EOL;
			return false;
		}

		// Let's go through the intended run file line by line and make intended modifications to what's needed for making Wine happy... As we go, write it to the new temporary file
		$new_script = '';
		foreach(explode(PHP_EOL, pts_file_io::file_get_contents($run_file)) as $line)
		{
			if($line == null)
			{
				continue;
			}

			$words_in_line = pts_strings::trim_explode(' ', $line);
			if($words_in_line[0] == 'wine')
			{
				// if 'wine' is already found in the test profile, assume test is already customized for Wine usage

				// reset $new_script to original script
				$new_script = pts_file_io::file_get_contents($run_file);
				break;
			}

			// Replace /cygdrive/c/ with $WINEPREFIX/drive_c/
			if (stripos($line, '/cygdrive/c/') !== false)
			{
				if (getenv("WINEPREFIX") !== false)
				{
					$line = str_replace('/cygdrive/c/', '$WINEPREFIX/drive_c/', $line);
				}
				else
				{
					$line = str_replace('/cygdrive/c/', '$HOME/.wine/drive_c/', $line);
				}
			}

			if($words_in_line[0] == 'msiexec' || $words_in_line[0] == 'msiexec.exe')
			{
				// At least in my tests with unigine-valley, calling its msiexec line didn't work and just silently failed... so let's turn that into running the Wine command.
				$line = str_replace(array('msiexec.exe', 'msiexec'), self::$wine_bin, $line);

				// Remove potential garbage from line...
				$line = str_replace(array('/package', '/passive'), '', $line);
			}
			else if($words_in_line[0] == 'cmd')
			{
				$line = self::$wine_bin . ' ' . $line;
				$line = str_replace('cmd /c', '', $line);
			}
			else if($words_in_line[0] == 'cd' && stripos($line, 'C:\\') !== false)
			{
				// Try to map a Windows C:\ path into correct directory for Wine...

				// Trim off the "cd " portion of line
				$cd_dir = substr($line, 3);

				// Get rid of quotes for now... maybe have to change this...
				$cd_dir = str_replace('"', '', $cd_dir);

				// Map the drive. Using "dosdevices/c:" would be nicer, but the colon makes
				// this an escaping pain.
				if (getenv("WINEPREFIX") !== false)
				{
					$cd_dir = str_replace('C:\\', '$WINEPREFIX/drive_c/', $cd_dir);
				}
				else
				{
					$cd_dir = str_replace('C:\\', '$HOME/.wine/drive_c/', $cd_dir);
				}

				$cd_dir = str_replace('\\ ', ' ', $cd_dir);
				$cd_dir = str_replace('\\(', '(', $cd_dir);
				$cd_dir = str_replace('\\)', ')', $cd_dir);

				// Switch over remaining \ to /
				$cd_dir = str_replace('\\', '/', $cd_dir);

				// escape necessary characters for path
				$cd_dir = str_replace(' ', '\\ ', $cd_dir);
				$cd_dir = str_replace(')', '\\)', $cd_dir);
				$cd_dir = str_replace('(', '\\(', $cd_dir);

				$line = 'cd ' . $cd_dir;
			}
			else if(stripos($words_in_line[0], '.exe') !== false || stripos($words_in_line[0], '.msi') !== false)
			{
				// Append wine to start of string that appears to be calling an EXE/MSI executable...
				$line = self::$wine_bin . ' ' . $line;
				$line = str_replace('cmd /c', '', $line);
			}
			else if($words_in_line[0] == './\\$@')
			{
				$line = self::$wine_bin . ' ' . $line;
			}

			$new_script .= $line . PHP_EOL;
		}

		// Write out the new temporary test script file...
		file_put_contents($new_run_file, $new_script);
		shell_exec('chmod +x ' . $new_run_file);

		// Do the actual execution of the script... Albeit the script we modified, not the original test profile script
		//echo 'cd ' .  $test_directory . ' && ' . $shell . ' ' . $new_run_file . ' ' . $pass_argument . ' 2>&1';
		$this_result = pts_client::shell_exec('cd ' .  $test_directory . ' && ' . $shell . ' ' . $new_run_file . ' ' . $pass_argument . ' 2>&1', $extra_vars);

		// Remove the temporary script...
		unlink($new_run_file);

		// Return the result back to pts-core
		return $this_result;
	}
}

?>
