<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2017, Phoronix Media
	Copyright (C) 2010 - 2017, Michael Larabel

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

class pts_test_installer
{
	protected static function test_install_error($test_run_manager, &$test_run_request, $error_msg)
	{
		$error_obj = array($test_run_manager, $test_run_request, $error_msg);
		pts_module_manager::module_process('__event_run_error', $error_obj);
		pts_client::$display->test_install_error($error_msg);
	}
	public static function standard_install($items_to_install, $force_install = false, $no_prompts = false, $skip_tests_with_missing_dependencies = false)
	{
		// Refresh the pts_client::$display in case we need to run in debug mode
		if(pts_client::$display == false || !(pts_client::$display instanceof pts_websocket_display_mode))
		{
			pts_client::init_display_mode();
		}

		// Create a lock
		$lock_path = pts_client::temporary_directory() . '/phoronix-test-suite.active';
		pts_client::create_lock($lock_path);

		// Get the test profiles
		$unknown_tests = array();
		$test_profiles = pts_types::identifiers_to_test_profile_objects($items_to_install, true, true, $unknown_tests);

		// Any external dependencies?
		pts_external_dependencies::install_dependencies($test_profiles, $no_prompts, $skip_tests_with_missing_dependencies);

		// Install tests
		if(!is_writable(pts_client::test_install_root_path()))
		{
			trigger_error('The test installation directory is not writable.' . PHP_EOL . 'Location: ' . pts_client::test_install_root_path(), E_USER_ERROR);
			return false;
		}

		pts_test_installer::start_install($test_profiles, $unknown_tests, $force_install, $no_prompts);
		pts_client::release_lock($lock_path);

		return $test_profiles;
	}
	public static function start_install(&$test_profiles, &$unknown_tests = null, $force_install = false, $no_prompts = false)
	{
		if(count($test_profiles) == 0)
		{
			trigger_error('No Tests Found For Installation.', E_USER_ERROR);
			return false;
		}

		// Setup the install manager and add the tests
		$test_install_manager = new pts_test_install_manager();
		$install_table = array();

		foreach($test_profiles as &$test_profile)
		{
			if($test_profile->get_identifier() == null)
			{
				continue;
			}

			if($test_profile->needs_updated_install() || $force_install)
			{
				if($test_profile->is_supported(false) == false)
				{
					$install_table[] = array('Not Supported:', $test_profile->get_identifier());
				}
				else if($test_install_manager->add_test_profile($test_profile) != false)
				{
					$install_table[] = array('To Install:', $test_profile->get_identifier());
				}
			}
			else
			{
					$install_table[] = array('Installed:', $test_profile->get_identifier());
			}
		}

		if($unknown_tests)
		{
			foreach($unknown_tests as $unknown)
			{
				$install_table[] = array('Unknown:', $unknown);
			}
		}

		foreach($install_table as $line)
		{
			pts_client::$display->generic_sub_heading(pts_client::cli_just_bold($line[0]) . (str_repeat(' ', (15 - strlen($line[0])))) . $line[1]);
		}

		if($test_install_manager->tests_to_install_count() == 0)
		{
			return true;
		}

		// Let the pts_test_install_manager make some estimations, etc...
		echo PHP_EOL;
		$test_install_manager->generate_download_file_lists();
		$test_install_manager->check_download_caches_for_files();
		pts_client::$display->test_install_process($test_install_manager);

		// Begin the install process
		pts_module_manager::module_process('__pre_install_process', $test_install_manager);
		$failed_installs = array();
		$test_profiles = array();
		while(($test_install_request = $test_install_manager->next_in_install_queue()) != false)
		{
			pts_client::$display->test_install_start($test_install_request->test_profile->get_identifier());
			$test_install_request->special_environment_vars['INSTALL_FOOTNOTE'] = $test_install_request->test_profile->get_install_dir() . 'install-footnote';
			$installed = pts_test_installer::install_test_process($test_install_request, $no_prompts);
			$compiler_data = pts_test_installer::end_compiler_mask($test_install_request);

			if($installed)
			{
				if(pts_client::do_anonymous_usage_reporting() && $test_install_request->install_time_duration > 0)
				{
					// If anonymous usage reporting enabled, report install time to OpenBenchmarking.org
					pts_openbenchmarking_client::upload_usage_data('test_install', array($test_install_request, $test_install_request->install_time_duration));
				}

				$install_footnote = null;
				if(is_file($test_install_request->special_environment_vars['INSTALL_FOOTNOTE']))
				{
					$install_footnote = pts_file_io::file_get_contents($test_install_request->special_environment_vars['INSTALL_FOOTNOTE']);
				}

				pts_tests::update_test_install_xml($test_install_request->test_profile, $test_install_request->install_time_duration, true, $compiler_data, $install_footnote);
				$test_profiles[] = $test_install_request->test_profile;
			}
			else
			{
				$failed_installs[] = $test_install_request;
			}

			pts_file_io::unlink($test_install_request->special_environment_vars['INSTALL_FOOTNOTE']);
		}
		pts_module_manager::module_process('__post_install_process', $test_install_manager);
		pts_download_speed_manager::save_data();

		if(count($failed_installs) > 1)
		{
			echo PHP_EOL . 'The following tests failed to install:' . PHP_EOL . PHP_EOL;
			foreach($failed_installs as &$install_request)
			{
				echo '  - ' . $install_request->test_profile . PHP_EOL;

				// If many tests are being installed, show the error messages reported in order to reduce scrolling...
				if($install_request->install_error && isset($failed_installs[5]))
				{
					echo '    [' . $install_request->install_error . ']' . PHP_EOL;
				}
			}
		}
	}
	public static function only_download_test_files(&$test_profiles, $to_dir = null)
	{
		// Setup the install manager and add the tests
		$test_install_manager = new pts_test_install_manager();

		foreach($test_profiles as &$test_profile)
		{
			if($test_install_manager->add_test_profile($test_profile) != false)
			{
				pts_client::$display->generic_sub_heading('To Download Files: ' . $test_profile->get_identifier());
			}
		}

		if($test_install_manager->tests_to_install_count() == 0)
		{
			return true;
		}

		// Let the pts_test_install_manager make some estimations, etc...
		$test_install_manager->generate_download_file_lists();
		$test_install_manager->check_download_caches_for_files();

		// Begin the download process
		while(($test_install_request = $test_install_manager->next_in_install_queue()) != false)
		{
			//pts_client::$display->test_install_start($test_install_request->test_profile->get_identifier());
			pts_test_installer::download_test_files($test_install_request, $to_dir);
		}
	}
	protected static function download_test_files(&$test_install_request, $download_location = false, $no_prompts = false)
	{
		// Download needed files for a test
		if($test_install_request->get_download_object_count() == 0)
		{
			return true;
		}

		$identifier = $test_install_request->test_profile->get_identifier();
		pts_client::$display->test_install_downloads($test_install_request);

		if($download_location == false)
		{
			$download_location = $test_install_request->test_profile->get_install_dir();
		}

		pts_file_io::mkdir($download_location);
		$module_pass = array($identifier, $test_install_request->get_download_objects());
		pts_module_manager::module_process('__pre_test_download', $module_pass);

		foreach($test_install_request->get_download_objects() as $download_package)
		{
			$package_filename = $download_package->get_filename();
			$download_destination = $download_location . $package_filename;
			$download_destination_temp = $download_destination . '.pts';

			if($download_package->get_download_location_type() == null)
			{
				// Attempt a possible last-minute look-aside copy cache in case a previous test in the install queue downloaded this file already
				$lookaside_copy = pts_test_install_manager::file_lookaside_test_installations($download_package);
				if($lookaside_copy)
				{
					if($download_package->get_filesize() == 0)
					{
						$download_package->set_filesize(filesize($lookaside_copy));
					}

					$download_package->set_download_location('LOOKASIDE_DOWNLOAD_CACHE', array($lookaside_copy));
				}
			}

			switch($download_package->get_download_location_type())
			{
				case 'IN_DESTINATION_DIR':
					pts_client::$display->test_install_download_file('FILE_FOUND', $download_package);
					continue;
				case 'REMOTE_DOWNLOAD_CACHE':
					$download_tries = 0;
					do
					{
						foreach($download_package->get_download_location_path() as $remote_download_cache_file)
						{
							pts_client::$display->test_install_download_file('DOWNLOAD_FROM_CACHE', $download_package);
							pts_network::download_file($remote_download_cache_file, $download_destination_temp);

							if(!is_file($download_destination_temp) || filesize($download_destination_temp) == 0)
							{
								self::test_install_error(null, $test_install_request, 'The file failed to download from the cache.');
								pts_file_io::unlink($download_destination_temp);
								break;
							}
							else if($download_package->check_file_hash($download_destination_temp))
							{
								rename($download_destination_temp, $download_destination);
								break;
							}
							else
							{
								self::test_install_error(null, $test_install_request, 'The check-sum of the downloaded file failed.');
								pts_file_io::unlink($download_destination_temp);
							}
						}
						$download_tries++;
					}
					while(!is_file($download_destination) && $download_tries < 2);

					if(is_file($download_destination))
					{
						continue;
					}
				case 'MAIN_DOWNLOAD_CACHE':
				case 'LOCAL_DOWNLOAD_CACHE':
				case 'LOOKASIDE_DOWNLOAD_CACHE':
					$download_cache_file = pts_arrays::last_element($download_package->get_download_location_path());

					if(is_file($download_cache_file))
					{
						if((pts_config::read_bool_config('PhoronixTestSuite/Options/Installation/SymLinkFilesFromCache', 'FALSE') && $download_package->get_download_location_type() != 'LOOKASIDE_DOWNLOAD_CACHE'))
						{
							// For look-aside copies never symlink (unless a pre-packaged LiveCD) in case the other test ends up being un-installed
							// SymLinkFilesFromCache is disabled by default
							pts_client::$display->test_install_download_file('LINK_FROM_CACHE', $download_package);
							symlink($download_cache_file, $download_destination);
						}
						else
						{
							// File is to be copied
							// Try up to two times to copy a file
							$attempted_copies = 0;

							do
							{
								pts_client::$display->test_install_download_file('COPY_FROM_CACHE', $download_package);
								// $context = stream_context_create();
								// stream_context_set_params($context, array('notification' => array('pts_network', 'stream_status_callback')));
								// TODO: get the context working correctly for this copy()
								copy($download_cache_file, $download_destination_temp);
								pts_client::$display->test_install_progress_completed();

								// Verify that the file was copied fine
								if($download_package->check_file_hash($download_destination_temp))
								{
									rename($download_destination_temp, $download_destination);
									break;
								}
								else
								{
									self::test_install_error(null, $test_install_request, 'The check-sum of the copied file failed.');
									pts_file_io::unlink($download_destination_temp);
								}

								$attempted_copies++;
							}
							while($attempted_copies < 2);
						}

						if(is_file($download_destination))
						{
							continue;
						}
					}
				default:
					$package_urls = $download_package->get_download_url_array();

					// Download the file
					if(!is_file($download_destination) && count($package_urls) > 0 && $package_urls[0] != null)
					{
						$fail_count = 0;

						do
						{
							if(pts_network::internet_support_available())
							{
								if(!$no_prompts && pts_config::read_bool_config('PhoronixTestSuite/Options/Installation/PromptForDownloadMirror', 'FALSE') && count($package_urls) > 1)
								{
									// Prompt user to select mirror
									do
									{
										echo PHP_EOL . 'Available Download Mirrors:' . PHP_EOL . PHP_EOL;
										$url = pts_user_io::prompt_text_menu('Select Preferred Mirror', $package_urls, false);
									}
									while(pts_strings::is_url($url) == false);
								}
								else
								{
									// Auto-select mirror
									shuffle($package_urls);
									do
									{
										$url = array_pop($package_urls);
									}
									while(pts_strings::is_url($url) == false && !empty($package_urls));
								}

								pts_client::$display->test_install_download_file('DOWNLOAD', $download_package);
								$download_start = time();
								pts_network::download_file($url, $download_destination_temp);
								$download_end = time();
							}
							else
							{
								self::test_install_error(null, $test_install_request, 'Internet support is needed and it\'s disabled or not available.');
								return false;
							}

							if($download_package->check_file_hash($download_destination_temp))
							{
								// Download worked
								if(is_file($download_destination_temp))
								{
									rename($download_destination_temp, $download_destination);
								}

								if($download_package->get_filesize() > 0 && $download_end != $download_start)
								{
									pts_download_speed_manager::update_download_speed_average($download_package->get_filesize(), ($download_end - $download_start));
								}
							}
							else
							{
								// Download failed
								if(is_file($download_destination_temp) && filesize($download_destination_temp) < 500 && (stripos(file_get_contents($download_destination_temp), 'not found') !== false || strpos(file_get_contents($download_destination_temp), 404) !== false))
								{
									self::test_install_error(null, $test_install_request, 'File Not Found: ' . $url);
									$md5_failed = false;
								}
								else if(is_file($download_destination_temp) && filesize($download_destination_temp) > 0)
								{
									self::test_install_error(null, $test_install_request, 'Checksum Failed: ' . $url);
									$md5_failed = true;
								}
								else
								{
									self::test_install_error(null, $test_install_request, 'Download Failed: ' . $url);
									$md5_failed = false;
								}

								pts_file_io::unlink($download_destination_temp);
								$fail_count++;

								if($fail_count > 3)
								{
									$try_again = false;
								}
								else
								{
									if(count($package_urls) > 0 && $package_urls[0] != null)
									{
										self::test_install_error(null, $test_install_request, 'Attempting to download from alternate mirror.');
										$try_again = true;
									}
									else
									{
										if($no_prompts)
										{
											$try_again = false;
										}
										else if($md5_failed)
										{
											$try_again = pts_user_io::prompt_bool_input('Try downloading the file again', true, 'TRY_DOWNLOAD_AGAIN');
										}
										else
										{
											$try_again = false;
										}

										if($try_again)
										{
											$package_urls[] = $url;
										}
									}
								}

								if(!$try_again)
								{
									//self::test_install_error(null, $test_install_request, 'Download of Needed Test Dependencies Failed!');
									return false;
								}
							}
						}
						while(!is_file($download_destination));
				}
				pts_module_manager::module_process('__interim_test_download', $module_pass);
			}
		}

		pts_module_manager::module_process('__post_test_download', $identifier);

		return true;
	}
	public static function create_compiler_mask(&$test_install_request)
	{
		if(phodevi::is_bsd() || getenv('PTS_NO_COMPILER_MASK'))
		{
			// XXX: Using the compiler-mask causes a number of tests to fail to properly install due to compiler issues with at least PC-BSD 10.0
			return false;
		}

		// or pass false to $test_install_request to bypass the test checks
		$compilers = array();

		$external_dependencies = $test_install_request != false ? $test_install_request->test_profile->get_external_dependencies() : false;
		if($test_install_request === false || in_array('build-utilities', $external_dependencies))
		{
			// Handle C/C++ compilers for this external dependency
			$compilers['CC'] = array(pts_strings::first_in_string(pts_client::read_env('CC'), ' '), 'gcc', 'clang', 'icc', 'pcc');
			$compilers['CXX'] = array(pts_strings::first_in_string(pts_client::read_env('CXX'), ' '), 'g++', 'clang++', 'cpp');
		}
		if($test_install_request === false || in_array('fortran-compiler', $external_dependencies))
		{
			// Handle Fortran for this external dependency
			$compilers['F9X'] = array(pts_strings::first_in_string(pts_client::read_env('F9X'), ' '), pts_strings::first_in_string(pts_client::read_env('F95'), ' '), 'gfortran', 'f90', 'f95', 'fortran');
		}

		if(empty($compilers))
		{
			// If the test profile doesn't request a compiler external dependency, probably not compiling anything
			return false;
		}

		foreach($compilers as $compiler_type => $possible_compilers)
		{
			// Compilers to check for, listed in order of priority
			$compiler_found = false;
			foreach($possible_compilers as $i => $possible_compiler)
			{
				// first check to ensure not null sent to executable_in_path from env variable
				if($possible_compiler && (($compiler_path = is_executable($possible_compiler)) || ($compiler_path = pts_client::executable_in_path($possible_compiler, 'ccache'))))
				{
					// Replace the array of possible compilers with a string to the detected compiler executable
					$compilers[$compiler_type] = $compiler_path;
					$compiler_found = true;
					break;
				}
			}

			if($compiler_found == false)
			{
				unset($compilers[$compiler_type]);
			}
		}

		if(!empty($compilers))
		{
			// Create a temporary directory that will be at front of PATH and serve for masking the actual compiler
			if($test_install_request instanceof pts_test_install_request)
			{
				$mask_dir = pts_client::temporary_directory() . '/pts-compiler-mask-' . $test_install_request->test_profile->get_identifier_base_name() . $test_install_request->test_profile->get_test_profile_version() . '/';
			}
			else
			{
				$mask_dir = pts_client::temporary_directory() . '/pts-compiler-mask-' . rand(100, 999) . '/';
			}

			pts_file_io::mkdir($mask_dir);

			$compiler_extras = array(
				'CC' => array('safeguard-names' => array('gcc', 'cc'), 'environment-variables' => 'CFLAGS'),
				'CXX' => array('safeguard-names' => array('g++', 'c++'), 'environment-variables' => 'CXXFLAGS'),
				'F9X' => array('safeguard-names' => array('gfortran', 'f95'), 'environment-variables' => 'F9XFLAGS')
				);

			foreach($compilers as $compiler_type => $compiler_path)
			{
				$compiler_name = basename($compiler_path);
				$main_compiler = $mask_dir . $compiler_name;

				// take advantage of environment-variables to be sure they're found in the string
				$env_var_check = PHP_EOL;
				/*
				foreach(pts_arrays::to_array($compiler_extras[$compiler_type]['environment-variables']) as $env_var)
				{
					// since it's a dynamic check in script could probably get rid of this check...
					if(true || getenv($env_var))
					{
						$env_var_check .= 'if [[ $COMPILER_OPTIONS != "*$' . $env_var . '*" ]]' . PHP_EOL . 'then ' . PHP_EOL . 'COMPILER_OPTIONS="$COMPILER_OPTIONS $' . $env_var . '"' . PHP_EOL . 'fi' . PHP_EOL;
					}
				}
				*/

				// Write the main mask for the compiler
				file_put_contents($main_compiler,
					'#!/usr/bin/env bash' . PHP_EOL . 'COMPILER_OPTIONS="$@"' . PHP_EOL . $env_var_check . PHP_EOL . 'echo $COMPILER_OPTIONS >> ' . $mask_dir . $compiler_type . '-options-' . $compiler_name . PHP_EOL . $compiler_path . ' "$@"' . PHP_EOL);

				// Make executable
				chmod($main_compiler, 0755);

				// The two below code chunks ensure the proper compiler is always hit
				if($test_install_request instanceof pts_test_install_request && !in_array($compiler_name, pts_arrays::to_array($compiler_extras[$compiler_type]['safeguard-names'])) && getenv($compiler_type) == false)
				{
					// So if e.g. clang becomes the default compiler, since it's not GCC, it will ensure CC is also set to clang beyond the masking below
					$test_install_request->special_environment_vars[$compiler_type] = $compiler_name;
				}

				// Just in case any test profile script is statically always calling 'gcc' or anything not CC, try to make sure it hits one of the safeguard-names so it redirects to the intended compiler under test
				foreach(pts_arrays::to_array($compiler_extras[$compiler_type]['safeguard-names']) as $safe_name)
				{
					if(!is_file($mask_dir . $safe_name))
					{
						symlink($main_compiler, $mask_dir . $safe_name);
					}
				}
			}

			if($test_install_request instanceof pts_test_install_request)
			{
				$test_install_request->compiler_mask_dir = $mask_dir;
				// Appending the rest of the path will be done automatically within call_test_script
				$test_install_request->special_environment_vars['PATH'] = $mask_dir;
			}

			return $mask_dir;
		}

		return false;
	}
	public static function end_compiler_mask(&$test_install_request)
	{
		if($test_install_request->compiler_mask_dir == false && !is_dir($test_install_request->compiler_mask_dir))
		{
			return false;
		}

		$compiler = false;
		foreach(pts_file_io::glob($test_install_request->compiler_mask_dir . '*-options-*') as $compiler_output)
		{
			$output_name = basename($compiler_output);
			$compiler_type = substr($output_name, 0, strpos($output_name, '-'));
			$compiler_choice = substr($output_name, (strrpos($output_name, 'options-') + 8));
			$compiler_lines = explode(PHP_EOL, pts_file_io::file_get_contents($compiler_output));

			// Clean-up / reduce the compiler options that are important
			$compiler_options = null;
			$compiler_backup_line = null;
			foreach($compiler_lines as $l => $compiler_line)
			{
				$compiler_line .= ' '; // allows for easier/simplified detection in a few checks below
				$o = strpos($compiler_line, '-o ');
				if($o === false)
				{
					unset($compiler_lines[$l]);
					continue;
				}

				$o = substr($compiler_line, ($o + 3), (strpos($compiler_line, ' ', ($o + 3)) - $o - 3));
				$o_l = strlen($o);
				// $o now has whatever is set for the -o output

				if(($o_l > 2 && substr(basename($o), 0, 3) == 'lib') || ($o_l > 3 && substr($o, -4) == 'test'))
				{
					// If it's a lib, probably not what is the actual target
					unset($compiler_lines[$l]);
					continue;
				}
				else if(($o_l > 2 && substr($o, -2) == '.o'))
				{
					// If it's outputting to a .o should not be the proper compile command we want
					// but back it up in case... keep overwriting temp variable to get the last one
					$compiler_backup_line = $compiler_line;
					unset($compiler_lines[$l]);
					continue;
				}
			}

			if(!empty($compiler_lines))
			{
				$compiler_line = array_pop($compiler_lines);

				if(strpos($compiler_line, '-O') === false && strpos($compiler_line, '-f') === false && (strpos($compiler_backup_line, '-f') !== false || strpos($compiler_backup_line, '-O')))
				{
					$compiler_line .= ' ' . $compiler_backup_line;
				}

				$compiler_options = explode(' ', $compiler_line);

				foreach($compiler_options as $i => $option)
				{
					// Decide what to include and what not... D?
					if(!isset($option[2]) || $option[0] != '-' || $option[1] == 'L' || $option[1] == 'D' || $option[1] == 'I' || $option[1] == 'W' || isset($option[20]))
					{
						unset($compiler_options[$i]);
					}

					if(isset($option[1]) && $option[1] == 'l')
					{
						// If you're linking a library it's also useful for other purposes
						$library = substr($option, 1);
						// TODO XXX: scan the external dependencies to make sure $library is covered if not alert test profile maintainer...
						//unset($compiler_options[$i]);
					}
				}
				$compiler_options = implode(' ', array_unique($compiler_options));
				//sort($compiler_options);

				// TODO: right now just keep overwriting $compiler to take the last compiler.. so TODO add support for multiple compiler reporting or decide what todo
				$compiler = array('compiler-type' => $compiler_type, 'compiler' => $compiler_choice, 'compiler-options' => $compiler_options);
				//echo PHP_EOL . 'DEBUG: ' . $compiler_type . ' ' . $compiler_choice . ' :: ' . $compiler_options . PHP_EOL;
			}
		}
		pts_file_io::delete($test_install_request->compiler_mask_dir, null, true);

		return $compiler;
	}
	protected static function install_test_process(&$test_install_request, $no_prompts)
	{
		// Install a test
		$identifier = $test_install_request->test_profile->get_identifier();
		$test_install_directory = $test_install_request->test_profile->get_install_dir();
		pts_file_io::mkdir(dirname($test_install_directory));
		pts_file_io::mkdir($test_install_directory);
		$installed = false;

		if(ceil(disk_free_space($test_install_directory) / 1048576) < ($test_install_request->test_profile->get_download_size() + 128))
		{
			self::test_install_error(null, $test_install_request, 'There is not enough space at ' . $test_install_directory . ' for the test files.');
		}
		else if(ceil(disk_free_space($test_install_directory) / 1048576) < ($test_install_request->test_profile->get_environment_size(false) + 128))
		{
			self::test_install_error(null, $test_install_request, 'There is not enough space at ' . $test_install_directory . ' for this test.');
		}
		else
		{
			pts_test_installer::setup_test_install_directory($test_install_request, true);

			// Download test files
			$download_test_files = pts_test_installer::download_test_files($test_install_request, false, $no_prompts);

			if($download_test_files == false)
			{
				self::test_install_error(null, $test_install_request, 'Downloading of needed test files failed.');
				return false;
			}

			if($test_install_request->test_profile->get_file_installer() != false)
			{
				self::create_compiler_mask($test_install_request);
				pts_module_manager::module_process('__pre_test_install', $identifier);
				pts_client::$display->test_install_begin($test_install_request);

				$pre_install_message = $test_install_request->test_profile->get_pre_install_message();
				$post_install_message = $test_install_request->test_profile->get_post_install_message();
				$install_agreement = $test_install_request->test_profile->get_installation_agreement_message();

				if(!empty($install_agreement))
				{
					if(pts_strings::is_url($install_agreement))
					{
						$install_agreement = pts_network::http_get_contents($install_agreement);

						if(empty($install_agreement))
						{
							self::test_install_error(null, $test_install_request, 'The user agreement could not be found. Test installation aborted.');
							return false;
						}
					}

					echo $install_agreement . PHP_EOL;
					if(!$no_prompts)
					{
						$user_agrees = pts_user_io::prompt_bool_input('Do you agree to these terms', false, 'INSTALL_AGREEMENT');

						if(!$user_agrees)
						{
							self::test_install_error(null, $test_install_request, 'User agreement failed; this test will not be installed.');
							return false;
						}
					}
				}

				pts_client::$display->display_interrupt_message($pre_install_message);
				$install_time_length_start = microtime(true);
				$install_log = pts_tests::call_test_script($test_install_request->test_profile, 'install', null, '"' . $test_install_directory . '"', $test_install_request->special_environment_vars, false);
				$test_install_request->install_time_duration = microtime(true) - $install_time_length_start;
				pts_client::$display->display_interrupt_message($post_install_message);

				if(!empty($install_log))
				{
					file_put_contents($test_install_directory . 'install.log', $install_log);
					pts_file_io::unlink($test_install_directory . 'install-failed.log');
					pts_client::$display->test_install_output($install_log);
				}

				if(is_file($test_install_directory . 'install-exit-status'))
				{
					// If the installer writes its exit status to ~/install-exit-status, if it's non-zero the install failed
					$install_exit_status = pts_file_io::file_get_contents($test_install_directory . 'install-exit-status');
					unlink($test_install_directory . 'install-exit-status');

					if($install_exit_status != 0 && phodevi::is_windows() == false)
					{
						$install_error = null;

						// TODO: perhaps better way to handle this than to remove pts-install.xml
						pts_file_io::unlink($test_install_directory . 'pts-install.xml');

						if(is_file($test_install_directory . 'install.log'))
						{
							$install_log = pts_file_io::file_get_contents($test_install_directory . 'install.log');
							$install_error = pts_tests::scan_for_error($install_log, $test_install_directory);
							copy($test_install_directory . 'install.log', $test_install_directory . 'install-failed.log');
						}

						//pts_test_installer::setup_test_install_directory($test_install_request, true); // Remove installed files from the bunked installation
						self::test_install_error(null, $test_install_request, 'The installer exited with a non-zero exit status.');
						if($install_error != null)
						{
							$test_install_request->install_error = pts_tests::pretty_error_string($install_error);

							if($test_install_request->install_error != null)
							{
								self::test_install_error(null, $test_install_request, 'ERROR: ' . $test_install_request->install_error);
							}
						}
						pts_client::$display->test_install_error('LOG: ' . str_replace(pts_core::user_home_directory(), '~/', $test_install_directory) . 'install-failed.log' . PHP_EOL);

						if(pts_client::do_anonymous_usage_reporting())
						{
							// If anonymous usage reporting enabled, report test install failure to OpenBenchmarking.org
							pts_openbenchmarking_client::upload_usage_data('test_install_failure', array($test_install_request, $install_error));
						}

						return false;
					}
				}

				pts_module_manager::module_process('__post_test_install', $identifier);
				$installed = true;

				if(pts_config::read_bool_config('PhoronixTestSuite/Options/Installation/RemoveDownloadFiles', 'FALSE'))
				{
					// Remove original downloaded files
					foreach($test_install_request->get_download_objects() as $download_object)
					{
						pts_file_io::unlink($test_install_directory . $download_object->get_filename());
					}
				}
			}
			else
			{
				pts_client::$display->test_install_error('No installation script found.');
				$installed = true;
			}

			// Additional validation checks?
			$custom_validated_output = pts_tests::call_test_script($test_install_request->test_profile, 'validate-install', PHP_EOL . 'Validating Installation...' . PHP_EOL, '"' . $test_install_directory . '"', null, false);
			if(!empty($custom_validated_output) && !pts_strings::string_bool($custom_validated_output))
			{
				$installed = false;
			}
		}

		echo PHP_EOL;

		return $installed;
	}
	protected static function setup_test_install_directory(&$test_install_request, $remove_old_files = false)
	{
		$identifier = $test_install_request->test_profile->get_identifier();
		pts_file_io::mkdir($test_install_request->test_profile->get_install_dir());

		if($remove_old_files)
		{
			// Remove any (old) files that were installed
			$ignore_files = array('pts-install.xml', 'install-failed.log');
			foreach($test_install_request->get_download_objects() as $download_object)
			{
				$ignore_files[] = $download_object->get_filename();
			}

			pts_file_io::delete($test_install_request->test_profile->get_install_dir(), $ignore_files);
		}

		pts_file_io::symlink(pts_core::user_home_directory() . '.Xauthority', $test_install_request->test_profile->get_install_dir() . '.Xauthority');
		pts_file_io::symlink(pts_core::user_home_directory() . '.drirc', $test_install_request->test_profile->get_install_dir() . '.drirc');
	}
}

?>
