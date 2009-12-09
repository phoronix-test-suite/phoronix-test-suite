<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
	pts-includes-install.php: Functions needed for installing tests for PTS.

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

require_once(PTS_LIBRARY_PATH . "pts-includes-install_dependencies.php");

function pts_start_install($to_install, &$display_mode)
{
	$to_install = pts_to_array($to_install);

	$tests = array();

	foreach($to_install as &$to_install_test)
	{
		foreach(pts_contained_tests($to_install_test, true) as $test)
		{
			pts_array_push($tests, $test);
		}
	}

	if(count($tests) == 0)
	{
		if(!pts_is_assignment("SILENCE_MESSAGES"))
		{
			echo pts_string_header("Not Recognized: " . $to_install[0]);
		}

		return false;
	}

	foreach($tests as $key => $test)
	{
		if(!pts_test_needs_updated_install($test))
		{
			if(!pts_is_assignment("SILENCE_MESSAGES"))
			{
				echo "Already Installed: " . $test . "\n";
			}

			unset($tests[$key]);
		}
	}

	$tests = array_values($tests);

	if(($install_count = count($tests)) > 1)
	{
		echo pts_string_header($install_count . " Tests To Be Installed" . 
		"\nEstimated Download Size: " . pts_estimated_download_size($tests) . " MB" .
		"\nEstimated Install Size: " . pts_estimated_environment_size($tests) . " MB");
	}
	pts_set_assignment("TEST_INSTALL_COUNT", $install_count);
	pts_set_assignment("DOWNLOAD_AVG_COUNT", pts_storage_object::read_from_file(PTS_CORE_STORAGE, "download_average_count"));
	pts_set_assignment("DOWNLOAD_AVG_SPEED", pts_storage_object::read_from_file(PTS_CORE_STORAGE, "download_average_speed"));

	pts_module_process("__pre_install_process", $tests);
	$failed_installs = array();
	foreach($tests as $i => $test)
	{
		pts_set_assignment("TEST_INSTALL_POSITION", ($i + 1));
		pts_install_test($display_mode, $test, $failed_installs);
	}
	pts_module_process("__post_install_process", $tests);

	pts_storage_object::set_in_file(PTS_CORE_STORAGE, "download_average_count", pts_read_and_clear_assignment("DOWNLOAD_AVG_COUNT"));
	pts_storage_object::set_in_file(PTS_CORE_STORAGE, "download_average_speed", pts_read_and_clear_assignment("DOWNLOAD_AVG_SPEED"));

	if(!pts_is_assignment("SILENCE_MESSAGES") && count($failed_installs) > 0 && count($tests) > 1)
	{
		echo "\nThe following tests failed to install:\n\n";
		foreach($failed_installs as $fail)
		{
			echo "\t- " . $fail . "\n";
		}
		echo "\n";
	}

	do
	{
		$report_install = array_pop($tests);
	}
	while(!pts_test_installed($report_install) && count($tests) > 0);

	pts_set_assignment_next("PREV_TEST_INSTALLED", $report_install);
}
function pts_download_test_files($identifier, &$display_mode)
{
	// Download needed files for a test
	$download_packages = pts_objects_test_downloads($identifier);

	if(count($download_packages) > 0)
	{
		$remote_download_files = array();
		$local_cache_directories = array();

		foreach(pts_test_download_cache_directories() as $dc_directory)
		{
			if(strpos($dc_directory, "://") > 0 && ($xml_dc_file = @file_get_contents($dc_directory . "pts-download-cache.xml")) != false)
			{
				$xml_dc_parser = new tandem_XmlReader($xml_dc_file);
				$dc_file = $xml_dc_parser->getXMLArrayValues(P_CACHE_PACKAGE_FILENAME);
				$dc_md5 = $xml_dc_parser->getXMLArrayValues(P_CACHE_PACKAGE_MD5);

				for($i = 0; $i < count($dc_file); $i++)
				{
					array_push($remote_download_files, new pts_test_file_download($dc_directory . $dc_file[$i], $dc_file[$i], 0, $dc_md5[$i]));
				}
			}
			else
			{
				array_push($local_cache_directories, $dc_directory);
			}
		}

		if(ceil(disk_free_space(TEST_ENV_DIR) / 1048576) < (pts_estimated_download_size($identifier) + 50))
		{
			echo pts_string_header("There is not enough space (at " . TEST_ENV_DIR . ") for this test.");
			return false;
		}

		$module_pass = array($identifier, $download_packages);
		pts_module_process("__pre_test_download", $module_pass);

		$longest_package_name_length = 0;
		foreach($download_packages as $i => &$download_package)
		{
			if(!is_file(TEST_ENV_DIR . $identifier . "/" . $download_package->get_filename()))
			{
				// Compute the longest package name length so that the UI can know it if it wishes to align text correctly
				if(($l = strlen($download_package->get_filename())) > $longest_package_name_length)
				{
					$longest_package_name_length = $l;
				}
			}
			else
			{
				// The file is there so nothing is to be downloaded
				unset($download_packages[$i]);
			}
		}

		if(count($download_packages) > 0)
		{
			$display_mode->test_install_downloads($identifier, $download_packages);
		}

		// Get the missing packages
		foreach($download_packages as &$download_package)
		{
			$download_location = TEST_ENV_DIR . $identifier . "/";
			$package_filename = $download_package->get_filename();
			$package_filename_temp = $package_filename . ".pts";
			$download_destination = $download_location . $package_filename;
			$download_destination_temp = $download_location . $package_filename_temp;

			$urls = $download_package->get_download_url_array();
			$package_md5 = $download_package->get_md5();

			$found_in_remote_cache = false;
			foreach($remote_download_files as &$download_file)
			{
				if($download_file->get_filename() == $package_filename && $download_file->get_md5() == $package_md5)
				{
					$display_mode->test_install_download_file($download_package, "DOWNLOAD_FROM_CACHE", $longest_package_name_length);
					echo pts_download(array_pop($download_file->get_download_url_array()), $download_destination_temp);
					echo "\n";

					if(!pts_validate_md5_download_file($download_destination_temp, $package_md5))
					{
						unlink($download_destination_temp);
					}
					else
					{
						pts_move($package_filename_temp, $package_filename, $download_location);
						$urls = array();
					}

					$found_in_remote_cache = true;
					break;
				}
			}

			if(!$found_in_remote_cache)
			{
				foreach($local_cache_directories as &$cache_directory)
				{
					if(pts_validate_md5_download_file($cache_directory . $package_filename, $package_md5))
					{
						if(pts_string_bool(pts_read_user_config(P_OPTION_CACHE_SYMLINK, "FALSE")))
						{
							// P_OPTION_CACHE_SYMLINK is disabled by default for now
							$display_mode->test_install_download_file($download_package, "LINK_FROM_CACHE", $longest_package_name_length);
							pts_symlink($cache_directory . $package_filename, $download_destination);
						}
						else
						{
							$display_mode->test_install_download_file($download_package, "COPY_FROM_CACHE", $longest_package_name_length);
							copy($cache_directory . $package_filename, $download_destination);
						}

						if(is_file($download_destination))
						{
							$urls = array();
							break;
						}
					}
				}
			}

			if(count($urls) > 0 && $urls[0] != null)
			{
				shuffle($urls);
				$fail_count = 0;
				$try_again = true;

				do
				{
					if(!pts_read_assignment("IS_BATCH_MODE") && !pts_is_assignment("AUTOMATED_MODE") && pts_string_bool(pts_read_user_config(P_OPTION_PROMPT_DOWNLOADLOC, "FALSE")) && count($urls) > 1)
					{
						// Prompt user to select mirror
						do
						{
							echo "\nAvailable Download Mirrors:\n\n";
							$url = pts_text_select_menu("Select Your Preferred Mirror", $urls, false);
						}
						while(!pts_is_valid_download_url($url));
					}
					else
					{
						// Auto-select mirror
						do
						{
							$url = array_pop($urls);
						}
						while(!pts_is_valid_download_url($url));
					}

					$display_mode->test_install_download_file($download_package, "DOWNLOAD", $longest_package_name_length);
					$download_start = time();
					echo pts_download($url, $download_destination_temp);
					$download_end = time();

					if(!pts_validate_md5_download_file($download_destination_temp, $package_md5))
					{
						pts_unlink($download_destination_temp);

						$file_downloaded = false;
						$fail_count++;
						$display_mode->test_install_error("The MD5 check-sum of the downloaded file is incorrect.\nFailed URL: " . $url);

						if($fail_count > 3)
						{
							$try_again = false;
						}
						else
						{
							if(count($urls) > 0 && $urls[0] != "")
							{
								$display_mode->test_install_error("Attempting to re-download from another mirror.");
							}
							else
							{
								$try_again = pts_read_assignment("IS_BATCH_MODE") || pts_read_assignment("AUTOMATED_MODE") ? false : pts_bool_question("Would you like to try downloading the file again (Y/n)?", true, "TRY_DOWNLOAD_AGAIN");

								if($try_again)
								{
									array_push($urls, $url);
								}
							}
						}
					}
					else
					{
						if(is_file($download_destination_temp))
						{
							pts_move($package_filename_temp, $package_filename, $download_location);
						}
						$file_downloaded = true;
						$fail_count = 0;

						if(($download_size = $download_package->get_filesize()) > 0 && $download_end != $download_start)
						{
							$download_speed = floor($download_size / ($download_end - $download_start)); // bytes per second

							if(($c_s = pts_read_assignment("DOWNLOAD_AVG_SPEED")) && ($c_c = pts_read_assignment("DOWNLOAD_AVG_COUNT")))
							{
								$avg_speed = floor((($c_s * $c_c) + $download_speed) / ($c_c + 1));

								pts_set_assignment("DOWNLOAD_AVG_SPEED", $avg_speed);
								pts_set_assignment("DOWNLOAD_AVG_COUNT", ($c_c + 1));
							}
							else
							{
								pts_set_assignment("DOWNLOAD_AVG_SPEED", $download_speed);
								pts_set_assignment("DOWNLOAD_AVG_COUNT", 1);
							}
						}
					}

					if(!$try_again)
					{
						$display_mode->test_install_error("Download of Needed Test Dependencies Failed! Exiting.");
						return false;
					}
				}
				while(!$file_downloaded);
			}
			pts_module_process("__interim_test_download", $module_pass);
		}
		pts_module_process("__post_test_download", $identifier);
	}

	return true;
}
function pts_validate_md5_download_file($filename, $verified_md5)
{
	$valid = false;

	if(is_file($filename))
	{
		if(!empty($verified_md5))
		{
			$real_md5 = md5_file($filename);

			if(substr($verified_md5, 0, 7) == "http://")
			{
				foreach(pts_trim_explode("\n", pts_http_get_contents($verified_md5)) as $md5_line)
				{
					list($md5, $file) = explode(" ", $md5_line);

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
function pts_remove_local_download_test_files($identifier)
{
	// Remove locally downloaded files for a given test
	foreach(pts_objects_test_downloads($identifier) as $test_file)
	{
		pts_unlink(TEST_ENV_DIR . $identifier . "/" . $test_file->get_filename());
	}
}
function pts_setup_install_test_directory($identifier, $remove_old_files = false)
{
	pts_mkdir(TEST_ENV_DIR);
	pts_mkdir(TEST_ENV_DIR . $identifier);

	if($remove_old_files)
	{
		// Remove any (old) files that were installed
		$ignore_files = array("pts-install.xml", "install-failed.log");
		foreach(pts_objects_test_downloads($identifier) as $download_object)
		{
			array_push($ignore_files, $download_object->get_filename());
		}

		pts_remove(TEST_ENV_DIR . $identifier, $ignore_files);
	}

	if(is_file(($xauth_file = pts_user_home() . ".Xauthority")))
	{
		pts_symlink($xauth_file, TEST_ENV_DIR . $identifier . "/.Xauthority");
	}
}
function pts_install_test(&$display_mode, $identifier, &$failed_installs)
{
	if(!pts_is_test($identifier))
	{
		return false;
	}

	// Install a test
	$installed = false;
	if(!pts_test_architecture_supported($identifier))
	{
		echo pts_string_header($identifier . " is not supported on this architecture: " . phodevi::read_property("system", "kernel-architecture"));
	}
	else if(!pts_test_platform_supported($identifier))
	{
		echo pts_string_header($identifier . " is not supported by this operating system (" . OPERATING_SYSTEM . ").");
	}
	else if(!pts_test_version_supported($identifier))
	{
		echo pts_string_header($identifier . " is not supported by this version of the Phoronix Test Suite (" . PTS_VERSION . ").");
	}
	else if(($e = getenv("SKIP_TESTS")) != false && in_array($identifier, explode(",", $e)))
	{
		echo pts_string_header($identifier . " is being skipped from the installation process.");
	}
	else
	{
		$custom_validated_output = trim(pts_call_test_script($identifier, "validate-install", "\nValidating Installation...\n", TEST_ENV_DIR . $identifier . "/", pts_run_additional_vars($identifier), false));

		if(!empty($custom_validated_output) && !pts_string_bool($custom_validated_output))
		{
			$installed = false;
		}
		else
		{
			if(pts_test_needs_updated_install($identifier))
			{
				pts_setup_install_test_directory($identifier, true);
				$display_mode->test_install_start($identifier);
				$download_test_files = pts_download_test_files($identifier, $display_mode);

				if($download_test_files == false)
				{
					echo "\nInstallation of " . $identifier . " test failed.\n";
					array_push($failed_installs, $identifier);
					return false;
				}

				$install_time_length = 0;

				if(is_file(pts_location_test_resources($identifier) . "install.sh") || is_file(pts_location_test_resources($identifier) . "install.php"))
				{
					pts_module_process("__pre_test_install", $identifier);
					$display_mode->test_install_process($identifier);

					if(!empty($size) && ceil(disk_free_space(TEST_ENV_DIR) / 1048576) < $size)
					{
						echo "\nThere is not enough space (at " . TEST_ENV_DIR . ") for this test to be installed.\n";
						return false;
					}

					$xml_parser = new pts_test_tandem_XmlReader($identifier);
					$pre_install_message = $xml_parser->getXMLValue(P_TEST_PREINSTALLMSG);
					$post_install_message = $xml_parser->getXMLValue(P_TEST_POSTINSTALLMSG);
					$install_agreement = $xml_parser->getXMLValue(P_TEST_INSTALLAGREEMENT);

					if(!empty($install_agreement))
					{
						if(substr($install_agreement, 0, 7) == "http://")
						{
							$install_agreement = pts_http_get_contents($install_agreement);

							if(empty($install_agreement))
							{
								echo "\nThe user agreement could not be found. Test installation aborted.\n";
								return false;
							}
						}

						echo $install_agreement . "\n";
						$user_agrees = pts_bool_question("Do you agree to these terms (y/N)?", false, "INSTALL_AGREEMENT");

						if(!$user_agrees)
						{
							echo "\n" . $identifier . " will not be installed.\n";
							return false;
						}
					}

					pts_user_message($pre_install_message);
					$install_time_length_start = time();
					$install_log = pts_call_test_script($identifier, "install", null, TEST_ENV_DIR . $identifier . "/", pts_run_additional_vars($identifier), false);
					$install_time_length = time() - $install_time_length_start;
					pts_user_message($post_install_message);

					if(!empty($install_log))
					{
						file_put_contents(TEST_ENV_DIR . $identifier . "/install.log", $install_log);
						pts_unlink(TEST_ENV_DIR . $identifier . "/install-failed.log");
						$display_mode->test_install_output($install_log);
					}

					if(is_file(TEST_ENV_DIR . $identifier . "/install-exit-status"))
					{
						// If the installer writes its exit status to ~/install-exit-status, if it's non-zero the install failed
						$install_exit_status = pts_file_get_contents(TEST_ENV_DIR . $identifier . "/install-exit-status");
						unlink(TEST_ENV_DIR . $identifier . "/install-exit-status");

						if($install_exit_status != 0 && !IS_BSD)
						{
							// TODO: perhaps better way to handle this than to remove pts-install.xml
							pts_unlink(TEST_ENV_DIR . $identifier . "/pts-install.xml");
							pts_copy(TEST_ENV_DIR . $identifier . "/install.log", TEST_ENV_DIR . $identifier . "/install-failed.log");
							pts_setup_install_test_directory($identifier, true); // Remove installed files from the bunked installation

							echo "\nThe " . $identifier . " installer exited with a non-zero exit status.\nInstallation Log: " . TEST_ENV_DIR . $identifier . "/install-failed.log\nInstallation failed.\n";
							array_push($failed_installs, $identifier);
							return false;
						}
					}

					pts_module_process("__post_test_install", $identifier);
					$installed = true;

					if(pts_string_bool(pts_read_user_config(P_OPTION_TEST_REMOVEDOWNLOADS, "FALSE")))
					{
						pts_remove_local_download_test_files($identifier); // Remove original downloaded files
					}
				}
				else
				{
					if(!pts_is_base_test($identifier))
					{
						echo "No installation script found for " . $identifier . "\n";
					}

					$installed = true;
				}

				pts_test_update_install_xml($identifier, $install_time_length, true);
				echo "\n";
			}
			else
			{
				$installed = true;
			}
		}
	}

	return $installed;
}
function pts_is_valid_download_url($string, $basename = null)
{
	// Checks for valid download URL
	return !(strpos($string, "://") == false || !empty($basename) && $basename != basename($string));
}
function pts_test_download_cache_directories()
{
	$cache_directories = array();

	// User Defined Directory Checking
	$dir_string = ($dir = getenv("PTS_DOWNLOAD_CACHE")) != false ? $dir . ":" : null;
	$dir_string .= pts_read_user_config(P_OPTION_CACHE_DIRECTORY, DEFAULT_DOWNLOAD_CACHE_DIR);

	foreach(pts_trim_explode(":", $dir_string) as $dir_check)
	{
		if($dir_check == null)
		{
			continue;
		}

		$dir_check = pts_find_home($dir_check);

		if(strpos($dir_check, "://") === false && !is_dir($dir_check))
		{
			continue;
		}

		array_push($cache_directories, pts_add_trailing_slash($dir_check));
	}

	// Other Possible Directories
	$additional_dir_checks = array("/var/cache/phoronix-test-suite/");
	foreach($additional_dir_checks as $dir_check)
	{
		if(is_dir($dir_check))
		{
			array_push($cache_directories, $dir_check);
		}
	}

	if(pts_string_bool(pts_read_user_config(P_OPTION_CACHE_SEARCHMEDIA, "TRUE")))
	{
		$download_cache_dirs = array_merge(
		pts_glob("/media/*/download-cache/"),
		pts_glob("/Volumes/*/download-cache/")
		);

		foreach($download_cache_dirs as $dir)
		{
			array_push($cache_directories, $dir);
		}
	}

	return $cache_directories;
}

?>
