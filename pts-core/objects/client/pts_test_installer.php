<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2023, Phoronix Media
	Copyright (C) 2010 - 2023, Michael Larabel

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
	protected static $install_errors = array();

	protected static function test_install_error($test_run_manager, &$test_run_request, $error_msg)
	{
		$error_obj = array($test_run_manager, $test_run_request, $error_msg);
		pts_module_manager::module_process('__event_run_error', $error_obj);
		pts_client::$display->test_install_error($error_msg);

		if(!isset(self::$install_errors[$test_run_request->test_profile->get_identifier()]))
		{
			self::$install_errors[$test_run_request->test_profile->get_identifier()] = array();
		}

		self::$install_errors[$test_run_request->test_profile->get_identifier()][] = $error_msg;
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

		if($force_install == false)
		{
			foreach($test_profiles as $i => $tp)
			{
				$valid = pts_test_run_manager::test_profile_system_compatibility_check($tp, true);
				if($valid == false)
				{
					unset($test_profiles[$i]);
				}
			}

		}

		// Any external dependencies?
		pts_external_dependencies::install_dependencies($test_profiles, $no_prompts, $skip_tests_with_missing_dependencies, true);

		// Install tests
		if(!is_writable(pts_client::test_install_root_path()))
		{
			trigger_error('The test installation directory is not writable.' . PHP_EOL . 'Location: ' . pts_client::test_install_root_path(), E_USER_ERROR);
			return false;
		}
		$mount_options = phodevi::read_property('disk', 'mount-options');
		if(isset($mount_options['mount-options']) && strpos($mount_options['mount-options'], 'noexec') !== false)
		{
			trigger_error('The test installation directory is on a file-system mounted with the \'noexec\' mount option. Re-mount the file-system appropriately or change the Phoronix Test Suite user configuration file to point to an alternative mount point.' . PHP_EOL . 'Location: ' . pts_client::test_install_root_path(), E_USER_ERROR);
			return false;
		}

		pts_test_installer::start_install($test_profiles, $unknown_tests, $force_install, $no_prompts);
		pts_client::release_lock($lock_path);

		return $test_profiles;
	}
	public static function start_install(&$test_profiles, &$unknown_tests = null, $force_install = false, $no_prompts = false)
	{
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
				if(!empty($unknown))
				{
					$install_table[] = array('Unknown:', $unknown);
				}
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
		if(pts_env::read('NO_DOWNLOAD_CACHE') == false)
		{
			$test_install_manager->check_download_caches_for_files();
		}
		pts_client::$display->test_install_process($test_install_manager);

		// Begin the install process
		pts_module_manager::module_process('__pre_install_process', $test_install_manager);
		$failed_installs = array();
		$test_profiles = array();
		while(($test_install_request = $test_install_manager->next_in_install_queue()) != false)
		{
			$test_install_request->generate_download_object_list(); // run this again due to any late additions after the above generate_download_file_lists call
			pts_client::$display->test_install_start($test_install_request->test_profile->get_identifier());
			$test_install_request->special_environment_vars['INSTALL_FOOTNOTE'] = $test_install_request->test_profile->get_install_dir() . 'install-footnote';
			pts_triggered_system_events::pre_run_reboot_triggered_check($test_install_request->test_profile, $test_install_request->special_environment_vars);
			$installed = pts_test_installer::install_test_process($test_install_request, $no_prompts);
			$compiler_data = pts_test_installer::end_compiler_mask($test_install_request);

			pts_triggered_system_events::post_run_reboot_triggered_check($test_install_request->test_profile);

			$install_footnote = null;
			if($installed)
			{
				if(pts_client::do_anonymous_usage_reporting() && $test_install_request->test_profile->test_installation->get_latest_install_time() > 0)
				{
					// If anonymous usage reporting enabled, report install time to OpenBenchmarking.org
					pts_openbenchmarking_client::upload_usage_data('test_install', array($test_install_request, $test_install_request->test_profile->test_installation->get_latest_install_time()));
				}

				if(is_file($test_install_request->special_environment_vars['INSTALL_FOOTNOTE']))
				{
					$install_footnote = pts_file_io::file_get_contents($test_install_request->special_environment_vars['INSTALL_FOOTNOTE']);
				}

				$test_profiles[] = $test_install_request->test_profile;
			}

			// Write the metadata
			$install_failed = false;
			if(!$installed)
			{
				// Pass any errors to be preserved in the metadata
				$install_failed = true;

				if(isset(self::$install_errors[$test_install_request->test_profile->get_identifier()]))
				{
					$install_failed = self::$install_errors[$test_install_request->test_profile->get_identifier()];
				}
			}
			$test_install_request->test_profile->test_installation->update_install_data($test_install_request->test_profile, $compiler_data, $install_footnote, $install_failed);
			$test_install_request->test_profile->test_installation->save_test_install_metadata();


			if(!$installed)
			{
				$tp = pts_openbenchmarking_client::test_profile_newer_minor_version_available($test_install_request->test_profile);
				if($tp)
				{
					pts_client::$display->test_install_message('Trying newer but compatible test profile version: ' . $tp->get_identifier());
					$test_install_manager->add_test_profile($tp, true);
				}
				$failed_installs[] = $test_install_request;
			}

			pts_file_io::unlink($test_install_request->special_environment_vars['INSTALL_FOOTNOTE']);
		}
		pts_module_manager::module_process('__post_install_process', $test_install_manager);
		pts_client::save_download_speed_averages();
		pts_triggered_system_events::test_requested_queued_reboot_check();

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
	public static function only_download_test_files(&$test_profiles, $to_dir = false, $do_file_checks = true)
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
		$test_install_manager->generate_download_file_lists($do_file_checks);
		$test_install_manager->check_download_caches_for_files();

		// Begin the download process
		while(($test_install_request = $test_install_manager->next_in_install_queue()) != false)
		{
			//pts_client::$display->test_install_start($test_install_request->test_profile->get_identifier());
			pts_test_installer::download_test_files($test_install_request, $to_dir, true);
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
		$objects_completed = 0;
		$fail_if_no_downloads = false;

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
					$objects_completed++;
					continue 2;
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
						$objects_completed++;
						continue 2;
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
							$objects_completed++;
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
							$objects_completed++;
							continue 2;
						}
					}
				default:
					$package_urls = $download_package->get_download_url_array();
					if(!is_file($download_destination) && empty($package_urls))
					{
						self::test_install_error(null, $test_install_request, $package_filename . ' must be manually placed in the Phoronix Test Suite download-cache.');
						if($download_package->is_optional())
						{
							self::test_install_error(null, $test_install_request, 'This file is marked as potentially being optional.');
						}
						else
						{
							$fail_if_no_downloads = true;
						}
					}
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
								self::test_install_error(null, $test_install_request, 'Internet support is needed to acquire files and it\'s disabled or not available.');
								return false;
							}

							if($download_package->check_file_hash($download_destination_temp))
							{
								// Download worked
								if(is_file($download_destination_temp))
								{
									rename($download_destination_temp, $download_destination);
									$objects_completed++;
								}

								if($download_package->get_filesize() > 0 && $download_end != $download_start)
								{
									pts_client::update_download_speed_average($download_package->get_filesize(), ($download_end - $download_start));
								}
							}
							else if($download_package->is_optional())
							{
								self::test_install_error(null, $test_install_request, 'File failed to download, but package may be optional.');
								self::helper_on_failed_download($test_install_request, $download_package);
								$objects_completed++;
								break;
							}
							else
							{
								// Download failed
								if(is_file($download_destination_temp) && filesize($download_destination_temp) < 500 && (stripos(file_get_contents($download_destination_temp), 'not found') !== false || strpos(file_get_contents($download_destination_temp), 404) !== false))
								{
									self::test_install_error(null, $test_install_request, 'File Not Found: ' . $url);
									$checksum_failed = false;
								}
								else if(is_file($download_destination_temp) && filesize($download_destination_temp) > 0)
								{
									self::test_install_error(null, $test_install_request, 'Checksum Failed: ' . $url);
									$checksum_failed = true;
								}
								else
								{
									self::test_install_error(null, $test_install_request, 'Download Failed: ' . $url);
									$checksum_failed = false;
								}
								self::helper_on_failed_download($test_install_request, $download_package);

								pts_openbenchmarking_client::upload_usage_data('download_failure', array($test_install_request, $url));

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
										else if($checksum_failed)
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
									pts_client::$display->test_install_prompt('If able to locate the file elsewhere, place it in the download cache and re-run the command.' . PHP_EOL . PHP_EOL);
									pts_client::$display->test_install_prompt(pts_client::cli_just_bold('Download Cache: ') . pts_client::download_cache_path() . PHP_EOL);
									if($download_package->get_filename() != null)
									{
										pts_client::$display->test_install_prompt(pts_client::cli_just_bold('File Name: ') . $download_package->get_filename() . PHP_EOL);
									}
									if($download_package->get_sha256() != null)
									{
										pts_client::$display->test_install_prompt(pts_client::cli_just_bold('SHA256: ') . $download_package->get_sha256() . PHP_EOL);
									}
									else if($download_package->get_md5() != null)
									{
										pts_client::$display->test_install_prompt(pts_client::cli_just_bold('MD5: ') . $download_package->get_md5() . PHP_EOL);
									}
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

		return !$fail_if_no_downloads || $objects_completed > 0;
	}
	public static function helper_on_failed_download(&$test_install_request, &$download_package)
	{
		if($download_package->get_filesize() > 2147483648 && !function_exists('curl_init'))
		{
			self::test_install_error(null, $test_install_request, 'File is large, you may want to install PHP CURL (php-curl) support.');
		}
	}
	public static function create_python_workarounds(&$test_install_request)
	{
		// Workarounds for Python i.e. 2023 Debian/Ubuntu screw around with "externally managed" crap that breaks pip user usage...
		// Thereby breaking existing scripts just doing pip user installs... So inject a "--break-system-packages" workaround, since only doing user package installs into test's home directory, should be fine

		if($test_install_request == false)
		{
			return false;
		}

		if(in_array('python', $test_install_request->test_profile->get_external_dependencies()))
		{
			$is_externally_managed = glob('/usr/lib*/py*/EXTERNALLY-MANAGED');
			if(!empty($is_externally_managed))
			{
				$python_override_dir = pts_client::temporary_directory() . '/pts-python-override/';
				pts_file_io::mkdir($python_override_dir);
				foreach(array('pip', 'pip3') as $cmd_check)
				{
					// TODO can avoid repeating this on a per-test basis...
					if(($cmd_path = pts_client::executable_in_path($cmd_check)))
					{
						$cmd_override = $python_override_dir . $cmd_check;
						file_put_contents($cmd_override,
						'#!/bin/sh' . PHP_EOL .
						$cmd_path . ' $@ --break-system-packages' . PHP_EOL .
						'exit $?' . PHP_EOL);
						chmod($cmd_override, 0755); //executable
					}
				}
				$test_install_request->special_environment_vars['PATH'] = $python_override_dir . (!empty($test_install_request->special_environment_vars['PATH']) ? ':' . $test_install_request->special_environment_vars['PATH'] : '');
			}
		}
	}
	public static function create_compiler_mask(&$test_install_request)
	{
		if(pts_env::read('NO_COMPILER_MASK'))
		{
			return false;
		}

		// or pass false to $test_install_request to bypass the test checks
		$compilers = array();

		$external_dependencies = $test_install_request != false ? $test_install_request->test_profile->get_external_dependencies() : false;
		if($test_install_request === false || in_array('build-utilities', $external_dependencies))
		{
			// Handle C/C++ compilers for this external dependency
			$compilers['CC'] = array(pts_strings::first_in_string(getenv('CC'), ' '), 'gcc', 'clang', 'icc', 'pcc');
			$compilers['CXX'] = array(pts_strings::first_in_string(getenv('CXX'), ' '), 'g++', 'clang++', 'cpp');
		}
		if($test_install_request === false || in_array('fortran-compiler', $external_dependencies))
		{
			// Handle Fortran for this external dependency
			$compilers['F9X'] = array(pts_strings::first_in_string(getenv('F9X'), ' '), pts_strings::first_in_string(getenv('F95'), ' '), 'gfortran', 'f90', 'f95', 'fortran', 'gfortran9', 'gfortran8', 'gfortran6', 'gfortran6');
		}
		if(!pts_client::executable_in_path('python'))
		{
			//$compilers['PY2'] = array('python2', 'python2.7', 'python2.6');
		}
		if(!pts_client::executable_in_path('python3'))
		{
			$compilers['PY3'] = array('python3.8', 'python3.7', 'python3.6', 'python3.5', 'python3.4');
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
				if($possible_compiler && (($compiler_path = (is_executable($possible_compiler) ? $possible_compiler : false)) || ($compiler_path = pts_client::executable_in_path($possible_compiler, 'ccache'))))
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
				'F9X' => array('safeguard-names' => array('gfortran', 'f95'), 'environment-variables' => 'FFLAGS'),
				//'PY2' => array('safeguard-names' => array('python'), 'environment-variables' => ''),
				'PY3' => array('safeguard-names' => array('python3'), 'environment-variables' => '')
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

				// Since GCC POWER doesn't support -march=, in the compiler mask we can change it to -mcpu= before passed to the actual compiler
				if(strpos(phodevi::read_property('system', 'kernel-architecture'), 'ppc') !== false && pts_client::executable_in_path('sed'))
				{
					$env_var_check .= 'COMPILER_OPTIONS=`echo "$COMPILER_OPTIONS" | sed -e "s/\-march=/-mcpu=/g"`' . PHP_EOL;
				}

				if(is_executable('/bin/bash'))
				{
					$shebang = '/bin/bash';
				}
				else if(is_executable('/usr/bin/env'))
				{
					$shebang = '/usr/bin/env bash';
				}
				else if(($sh = pts_client::executable_in_path('bash')) || ($sh = pts_client::executable_in_path('sh')))
				{
					$shebang = $sh;
				}
				else
				{
					return false;
				}

				// Write the main mask for the compiler
				file_put_contents($main_compiler,
					'#!' . $shebang . PHP_EOL .
					'COMPILER_OPTIONS="$@"' . PHP_EOL .
					$env_var_check . PHP_EOL .
					'echo $COMPILER_OPTIONS >> ' . $mask_dir . $compiler_type . '-options-' . $compiler_name . PHP_EOL .
					$compiler_path . ' "$@"' . PHP_EOL .
					PHP_EOL);

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
				$test_install_request->special_environment_vars['PATH'] = $mask_dir . (is_dir('/usr/lib64/openmpi/bin') ? ':/usr/lib64/openmpi/bin' : null);
				foreach(pts_file_io::glob('/usr/lib*/mpi/*/*/bin/') as $mpi_bin_path)
				{
					// openSUSE has e.g. /usr/lib64/mpi/gcc/openmpi4/bin for mpicxx that otherwise is not appearing in default PATH on modern Tumbleweed
					$test_install_request->special_environment_vars['PATH'] .= ':' . $mpi_bin_path;
				}
			}

			// Additional workarounds

			if(pts_client::executable_in_path('7z') == false && ($path_7za = pts_client::executable_in_path('7za')) != false)
			{
				// This should fix Fedora/RHEL providing 7za but not 7z even though for tests just extracting files this workaround should be fine
				symlink($path_7za, $mask_dir . '7z');
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

				if((empty($compiler_line) || (strpos($compiler_line, '-O') === false && strpos($compiler_line, '-f') === false)) && (!empty($compiler_backup_line) && (strpos($compiler_backup_line, '-f') !== false || strpos($compiler_backup_line, '-O'))))
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

				// right now just keep overwriting $compiler to take the last compiler.. so add support for multiple compiler reporting or decide what should report if not just the last
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
		$test_install_directory = $test_install_request->test_profile->get_install_dir();
		pts_file_io::mkdir(dirname($test_install_directory));
		pts_file_io::mkdir($test_install_directory);
		$installed = false;

		if($test_install_request->test_profile->is_internet_required_for_install() && !pts_network::internet_support_available())
		{
			self::test_install_error(null, $test_install_request, 'This test profile requires a working/active Internet connection to install.');
		}
		else if(ceil(disk_free_space($test_install_directory) / 1048576) < ($test_install_request->test_profile->get_download_size() + 128))
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
				pts_module_manager::module_process('__pre_test_install', $test_install_request);
				self::create_compiler_mask($test_install_request);
				self::create_python_workarounds($test_install_request);
				pts_client::$display->test_install_begin($test_install_request);

				$pre_install_message = $test_install_request->test_profile->get_pre_install_message();
				$pre_install_message = $pre_install_message ? str_replace('$DOWNLOAD_CACHE', PTS_DOWNLOAD_CACHE_PATH, $pre_install_message) : '';
				$post_install_message = $test_install_request->test_profile->get_post_install_message();
				$post_install_message = $post_install_message ? str_replace('$DOWNLOAD_CACHE', PTS_DOWNLOAD_CACHE_PATH, $post_install_message) : '';
				$install_agreement = $test_install_request->test_profile->get_installation_agreement_message();
				$install_agreement = $install_agreement ? str_replace('$DOWNLOAD_CACHE', PTS_DOWNLOAD_CACHE_PATH, $install_agreement) : '';

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

				if($no_prompts && $test_install_request->test_profile->is_root_install_required() && !phodevi::is_root() && !phodevi::is_windows())
				{
					self::test_install_error(null, $test_install_request, 'Root/administrator rights are required to install this test.');
					return false;
				}

				pts_client::$display->display_interrupt_message($pre_install_message);
				$install_time_length_start = microtime(true);
				$install_log = pts_tests::call_test_script($test_install_request->test_profile, 'install', null, '"' . $test_install_directory . '"', $test_install_request->special_environment_vars, false, $no_prompts);
				$test_install_request->test_profile->test_installation->update_install_time(microtime(true) - $install_time_length_start);
				pts_client::$display->display_interrupt_message($post_install_message);

				if(!empty($install_log))
				{
					file_put_contents($test_install_request->test_profile->test_installation->get_install_log_location(), $install_log);
					pts_file_io::unlink($test_install_directory . 'install-failed.log');
					pts_client::$display->test_install_output($install_log);
				}

				if(is_file($test_install_directory . 'install-message'))
				{
					// Any helpful message to convey to the user
					$install_msg = pts_file_io::file_get_contents($test_install_directory . 'install-message');
					if(!empty($install_msg))
					{
						pts_client::$display->test_install_message($install_msg);
					}
				}

				if(is_file($test_install_directory . 'install-exit-status'))
				{
					// If the installer writes its exit status to ~/install-exit-status, if it's non-zero the install failed
					$install_exit_status = pts_file_io::file_get_contents($test_install_directory . 'install-exit-status');
					unlink($test_install_directory . 'install-exit-status');

					if($install_exit_status != 0 && phodevi::is_windows() == false)
					{
						$install_error = null;

						pts_file_io::unlink($test_install_directory . 'pts-install.json');

						if($test_install_request->test_profile->test_installation->has_install_log())
						{
							$install_log = pts_file_io::file_get_contents($test_install_request->test_profile->test_installation->get_install_log_location());
							$install_error = pts_tests::scan_for_error($install_log, $test_install_directory);
							copy($test_install_request->test_profile->test_installation->get_install_log_location(), $test_install_directory . 'install-failed.log');
						}

						//pts_test_installer::setup_test_install_directory($test_install_request, true); // Remove installed files from the bunked installation
						self::test_install_error(null, $test_install_request, 'The installer exited with a non-zero exit status.');
						if($install_error != null)
						{
							$test_install_request->install_error = pts_tests::pretty_error_string($install_error);

							if($test_install_request->install_error != null)
							{
								self::test_install_error(null, $test_install_request, 'ERROR: ' . $test_install_request->install_error);
								$reverse_dep_look_for_files = pts_tests::scan_for_file_missing_from_error($test_install_request->install_error);

								if($reverse_dep_look_for_files)
								{
									foreach($reverse_dep_look_for_files as $file)
									{
										$lib_provided_by = pts_external_dependencies::packages_that_provide($file);
										if($lib_provided_by)
										{
											if(is_array($lib_provided_by))
											{
												$lib_provided_by = array_shift($lib_provided_by);
											}
											self::test_install_error(null, $test_install_request, pts_client::cli_just_italic('Installing the package \'' . $lib_provided_by . '\' might fix this error.'));
											break;
										}
									}
								}
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

				pts_module_manager::module_process('__post_test_install', $test_install_request);
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

		if($remove_old_files && $test_install_request->test_profile->do_remove_test_install_directory_on_reinstall())
		{
			// Remove any (old) files that were installed
			$ignore_files = array('pts-install.json', 'install-failed.log');
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
