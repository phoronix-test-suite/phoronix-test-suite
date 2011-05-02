<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2011, Phoronix Media
	Copyright (C) 2010 - 2011, Michael Larabel

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
	public static function standard_install($items_to_install, $test_flags = 0)
	{
		// Refresh the pts_client::$display in case we need to run in debug mode
		pts_client::init_display_mode();

		// Create a lock
		$lock_path = pts_client::temporary_directory() . '/phoronix-test-suite.active';
		pts_client::create_lock($lock_path);

		pts_client::set_test_flags($test_flags);

		// Get the test profiles
		$test_profiles = pts_types::identifiers_to_test_profile_objects($items_to_install, true, true);

		// Any external dependencies?
		pts_external_dependencies::install_dependencies($test_profiles);

		// Install tests
		if(!is_writable(pts_client::test_install_root_path()))
		{
			echo PHP_EOL . 'ERROR: The test installation directory is not writable.' . PHP_EOL . 'Location: ' . pts_client::test_install_root_path() . PHP_EOL;
			return false;
		}

		pts_test_installer::start_install($test_profiles);
		pts_client::release_lock($lock_path);

		return $test_profiles;
	}
	public static function start_install(&$test_profiles)
	{
		if(count($test_profiles) == 0)
		{
			pts_client::$display->generic_error('No Tests Found For Installation.');
			return false;
		}

		// Setup the install manager and add the tests
		$test_install_manager = new pts_test_install_manager();

		foreach($test_profiles as &$test_profile)
		{
			if($test_profile->get_identifier() == null)
			{
				continue;
			}

			if($test_profile->needs_updated_install())
			{
				if($test_profile->is_supported(false) == false)
				{
					pts_client::$display->generic_sub_heading('Not Supported: ' . $test_profile->get_identifier());
				}
				else if($test_install_manager->add_test_profile($test_profile) != false)
				{
					pts_client::$display->generic_sub_heading('To Install: ' . $test_profile->get_identifier());
				}
			}
			else
			{
				$installed_test = new pts_installed_test($test_profile);
				pts_client::$display->generic_sub_heading('Installed: ' . $test_profile->get_identifier());
			}
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
			$installed = pts_test_installer::install_test_process($test_install_request);

			if($installed == false)
			{
				array_push($failed_installs, $test_install_request->test_profile);
			}
			else
			{
				array_push($test_profiles, $test_install_request->test_profile);
			}
		}
		pts_module_manager::module_process('__post_install_process', $test_install_manager);
		pts_download_speed_manager::save_data();

		if(count($failed_installs) > 0)
		{
			echo PHP_EOL . 'The following tests failed to install:' . PHP_EOL . PHP_EOL;
			echo pts_user_io::display_text_list($failed_installs, "\t- ");
			echo PHP_EOL;
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
	protected static function download_test_files(&$test_install_request, $download_location = false)
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
			$package_md5 = $download_package->get_md5();
			$download_destination = $download_location . $package_filename;
			$download_destination_temp = $download_destination . '.pts';

			switch($download_package->get_download_location_type())
			{
				case 'IN_DESTINATION_DIR':
					pts_client::$display->test_install_download_file('FILE_FOUND', $download_package);
					continue;
				case 'REMOTE_DOWNLOAD_CACHE':
					foreach($download_package->get_download_location_path() as $remote_download_cache_file)
					{
						pts_client::$display->test_install_download_file('DOWNLOAD_FROM_CACHE', $download_package);
						pts_network::download_file($remote_download_cache_file, $download_destination_temp);

						if(pts_test_installer::validate_md5_download_file($download_destination_temp, $package_md5))
						{
							rename($download_destination_temp, $download_destination);
							continue;
						}
						else
						{
							pts_client::$display->test_install_error('The check-sum of the downloaded file failed.');
							pts_file_io::unlink($download_destination_temp);
						}
					}
				case 'MAIN_DOWNLOAD_CACHE':
				case 'LOCAL_DOWNLOAD_CACHE':
					$download_cache_file = pts_arrays::last_element($download_package->get_download_location_path());

					if(is_file($download_cache_file))
					{
						if(pts_config::read_bool_config(P_OPTION_CACHE_SYMLINK, 'FALSE') || pts_bypass::is_live_cd())
						{
							// P_OPTION_CACHE_SYMLINK is disabled by default
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
								if(pts_test_installer::validate_md5_download_file($download_destination_temp, $package_md5))
								{
									rename($download_destination_temp, $download_destination);
									break;
								}
								else
								{
									pts_client::$display->test_install_error('The check-sum of the copied file failed.');
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
							if((pts_c::$test_flags ^ pts_c::batch_mode) && (pts_c::$test_flags ^ pts_c::auto_mode) && pts_config::read_bool_config(P_OPTION_PROMPT_DOWNLOADLOC, 'FALSE') && count($package_urls) > 1)
							{
								// Prompt user to select mirror
								do
								{
									echo PHP_EOL . 'Available Download Mirrors:' . PHP_EOL . PHP_EOL;
									$url = pts_user_io::prompt_text_menu('Select Your Preferred Mirror', $package_urls, false);
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

							if(pts_test_installer::validate_md5_download_file($download_destination_temp, $package_md5))
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
								if(is_file($download_destination_temp) && filesize($download_destination_temp) > 0)
								{
									pts_client::$display->test_install_error('MD5 Failed: ' . $url);
									$md5_failed = true;
								}
								else
								{
									pts_client::$display->test_install_error('Download Failed: ' . $url);
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
										pts_client::$display->test_install_error('Attempting to re-download from another mirror.');
										$try_again = true;
									}
									else
									{
										if((pts_c::$test_flags & pts_c::batch_mode) || (pts_c::$test_flags & pts_c::auto_mode))
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
											array_push($package_urls, $url);
										}
									}
								}

								if(!$try_again)
								{
									//pts_client::$display->test_install_error('Download of Needed Test Dependencies Failed!');
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
	protected static function install_test_process(&$test_install_request)
	{
		// Install a test
		$identifier = $test_install_request->test_profile->get_identifier();
		$test_install_directory = $test_install_request->test_profile->get_install_dir();
		pts_file_io::mkdir(dirname($test_install_directory));
		pts_file_io::mkdir($test_install_directory);
		$installed = false;

		if(ceil(disk_free_space($test_install_directory) / 1048576) < ($test_install_request->test_profile->get_download_size() + 128))
		{
			pts_client::$display->test_install_error('There is not enough space at ' . $test_install_directory . ' for the test files.');
		}
		else if(ceil(disk_free_space($test_install_directory) / 1048576) < ($test_install_request->test_profile->get_environment_size(false) + 128))
		{
			pts_client::$display->test_install_error('There is not enough space at ' . $test_install_directory . ' for this test.');
		}
		else
		{
			$install_time_length = 0;
			pts_test_installer::setup_test_install_directory($test_install_request, true);

			// Download test files
			$download_test_files = pts_test_installer::download_test_files($test_install_request);

			if($download_test_files == false)
			{
				pts_client::$display->test_install_error('Downloading of needed test files failed.');
				return false;
			}

			if($test_install_request->test_profile->get_file_installer() != false)
			{
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
							pts_client::$display->test_install_error('The user agreement could not be found. Test installation aborted.');
							return false;
						}
					}

					echo $install_agreement . PHP_EOL;
					$user_agrees = pts_user_io::prompt_bool_input('Do you agree to these terms', false, 'INSTALL_AGREEMENT');

					if(!$user_agrees)
					{
						pts_client::$display->test_install_error('User agreement failed; this test will not be installed.');
						return false;
					}
				}

				pts_user_io::display_interrupt_message($pre_install_message);
				$install_time_length_start = time();
				$install_log = pts_tests::call_test_script($test_install_request->test_profile, 'install', null, $test_install_directory, null, false);
				$install_time_length = time() - $install_time_length_start;
				pts_user_io::display_interrupt_message($post_install_message);

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

					if($install_exit_status != 0 && phodevi::is_bsd() == false && phodevi::is_windows() == false)
					{
						// TODO: perhaps better way to handle this than to remove pts-install.xml
						pts_file_io::unlink($test_install_directory . 'pts-install.xml');
						copy($test_install_directory . 'install.log', $test_install_directory . 'install-failed.log');
						pts_test_installer::setup_test_install_directory($test_install_request, true); // Remove installed files from the bunked installation
						pts_client::$display->test_install_error('The installer exited with a non-zero exit status.');
						pts_client::$display->test_install_error('Installation Log: ' . $test_install_directory . 'install-failed.log' . PHP_EOL);
						return false;
					}
				}

				pts_module_manager::module_process('__post_test_install', $identifier);
				$installed = true;

				if(pts_config::read_bool_config(P_OPTION_TEST_REMOVEDOWNLOADS, 'FALSE'))
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
			$custom_validated_output = pts_tests::call_test_script($test_install_request->test_profile, 'validate-install', PHP_EOL . 'Validating Installation...' . PHP_EOL, $test_install_directory, null, false);
			if(!empty($custom_validated_output) && !pts_strings::string_bool($custom_validated_output))
			{
				$installed = false;
			}

			if($installed)
			{
				pts_tests::update_test_install_xml($test_install_request->test_profile, $install_time_length, true);
			}
		}

		echo PHP_EOL;

		return $installed;
	}
	public static function validate_md5_download_file($filename, $verified_md5)
	{
		$valid = false;

		if(is_file($filename))
		{
			if(pts_client::read_env('NO_MD5_CHECKS') != false)
			{
				$valid = true;
			}
			else if(!empty($verified_md5))
			{
				$real_md5 = md5_file($filename);

				if(pts_strings::is_url($verified_md5))
				{
					foreach(pts_strings::trim_explode("\n", pts_network::http_get_contents($verified_md5)) as $md5_line)
					{
						list($md5, $file) = explode(' ', $md5_line);

						if($md5_file == $filename)
						{
							if($md5 == $real_md5)
							{
								$valid = true;
							}

							break;
						}
					}
				}
				else if($real_md5 == $verified_md5)
				{
					$valid = true;
				}
			}
			else
			{
				$valid = true;
			}
		}

		return $valid;
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
				array_push($ignore_files, $download_object->get_filename());
			}

			pts_file_io::delete($test_install_request->test_profile->get_install_dir(), $ignore_files);
		}

		pts_file_io::symlink(pts_client::user_home_directory() . '.Xauthority', $test_install_request->test_profile->get_install_dir() . '.Xauthority');
	}
}

?>
