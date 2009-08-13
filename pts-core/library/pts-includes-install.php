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

	foreach($to_install as $to_install_test)
	{
		foreach(pts_contained_tests($to_install_test, true) as $test)
		{
			array_push($tests, $test);
		}
	}
	$tests = array_unique($tests);

	if(count($tests) == 0)
	{
		$exit_message = "";

		if(!pts_is_assignment("SILENCE_MESSAGES"))
		{
			echo pts_string_header("Not recognized: " . $to_install[0]);
		}

		return false;
	}

	pts_module_process("__pre_install_process", $tests);

	if(count($tests) > 1)
	{
		$will_be_installed = array();

		foreach($tests as $test)
		{
			if(pts_test_needs_updated_install($test))
			{
				array_push($will_be_installed, $test);
			}
		}

		if(($install_count = count($will_be_installed)) > 1)
		{
			echo pts_string_header($install_count . " Tests To Be Installed" . 
			"\nEstimated Download Size: " . pts_estimated_download_size($will_be_installed) . " MB" .
			"\nEstimated Install Size: " . pts_estimated_environment_size($will_be_installed) . " MB");
		}
	}

	foreach($tests as $test)
	{
		pts_install_test($test, $display_mode);
	}

	pts_module_process("__post_install_process", $tests);

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
		$header_displayed = false;
		$remote_download_files = array();
		$local_cache_directories = array();

		foreach(pts_test_download_caches() as $dc_directory)
		{
			if(strpos($dc_directory, "://") > 0 && ($xml_dc_file = @file_get_contents($dc_directory . "pts-download-cache.xml")) != false)
			{
				$xml_dc_parser = new tandem_XmlReader($xml_dc_file);
				$dc_file = $xml_dc_parser->getXMLArrayValues(P_CACHE_PACKAGE_FILENAME);
				$dc_md5 = $xml_dc_parser->getXMLArrayValues(P_CACHE_PACKAGE_MD5);

				for($i = 0; $i < count($dc_file); $i++)
				{
					array_push($remote_download_files, new pts_download_cache_file_reference($dc_directory, $dc_file[$i], $dc_md5[$i]));
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

		for($i = 0; $i < count($download_packages); $i++)
		{
			$download_location = TEST_ENV_DIR . $identifier . "/";
			$package_filename = $download_packages[$i]->get_filename();
			$package_filename_temp = $package_filename . ".pts";
			$download_destination = $download_location . $package_filename;
			$download_destination_temp = $download_location . $package_filename_temp;

			if(!is_file($download_destination))
			{
				if(!$header_displayed)
				{
					$display_mode->test_install_downloads($identifier, $download_packages);
					$header_displayed = true;
				}

				$urls = $download_packages[$i]->get_download_url_array();
				$package_md5 = $download_packages[$i]->get_md5();

				$found_in_remote_cache = false;
				if(($remote_download_file_count = count($remote_download_files)) > 0)
				{
					for($f = 0; $f < $remote_download_file_count && !$found_in_remote_cache; $f++)
					{
						if($remote_download_files[$f]->get_filename() == $package_filename && $remote_download_files[$f]->get_md5() == $package_md5)
						{
							echo "Downloading From Remote Cache: " . $package_filename . "\n\n";
							echo pts_download($remote_download_files[$f]->get_download_cache_directory() . $package_filename, $download_destination_temp);
							echo "\n";

							if(!pts_validate_md5_download_file($download_destination_temp, $package_md5))
							{
								@unlink($download_destination_temp);
							}
							else
							{
								pts_move($package_filename_temp, $package_filename, $download_location);
								$urls = array();
							}

							$found_in_remote_cache = true;
						}
					}
				}

				if(!$found_in_remote_cache)
				{
					$used_cache = false;
					for($j = 0; $j < count($local_cache_directories) && $used_cache == false; $j++)
					{
						if(pts_validate_md5_download_file($local_cache_directories[$j] . $package_filename, $package_md5))
						{

							if(pts_string_bool(pts_read_user_config(P_OPTION_CACHE_SYMLINK, "FALSE")))
							{
								// P_OPTION_CACHE_SYMLINK is disabled by default for now
								echo "Linking Cached File: " . $package_filename . "\n";
								pts_symlink($local_cache_directories[$j] . $package_filename, $download_destination);
							}
							else
							{
								echo "Copying Cached File: " . $package_filename . "\n";
								copy($local_cache_directories[$j] . $package_filename, $download_destination);
							}

							if(is_file($download_destination))
							{
								$urls = array();
								$used_cache = true;
							}
						}
					}
				}

				if(count($urls) > 0 && $urls[0] != "")
				{
					shuffle($urls);
					$fail_count = 0;
					$try_again = true;

					do
					{
						if(!pts_is_assignment("IS_BATCH_MODE") && !pts_is_assignment("AUTOMATED_MODE") && pts_string_bool(pts_read_user_config(P_OPTION_PROMPT_DOWNLOADLOC, "FALSE")) && count($urls) > 1)
						{
							// Prompt user to select mirror
							do
							{
								echo "\nAvailable Download Mirrors:\n\n";
								for($j = 0; $j < count($urls); $j++)
								{
									echo ($j + 1) . ": " . $urls[$j] . "\n";
								}
								echo "\nEnter Your Preferred Mirror: ";
								$mirror_choice = trim(fgets(STDIN));
							}
							while(($mirror_choice < 1 || $mirror_choice > count($urls)) && !pts_is_valid_download_url($mirror_choice, $package_filename));

							$url = (is_numeric($mirror_choice) ? $urls[($mirror_choice - 1)] : $mirror_choice);
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

						echo "\n\nDownloading File: " . $package_filename . "\n\n";
						echo pts_download($url, $download_destination_temp);

						if(!pts_validate_md5_download_file($download_destination_temp, $package_md5))
						{
							if(is_file($download_destination_temp))
							{
								unlink($download_destination_temp);
							}

							$file_downloaded = false;
							$fail_count++;
							echo "\nThe MD5 check-sum of the downloaded file is incorrect.\n";
							echo "Failed URL: " . $url . "\n";

							if($fail_count > 3)
							{
								$try_again = false;
							}
							else
							{
								if(count($urls) > 0 && $urls[0] != "")
								{
									echo "Attempting to re-download from another mirror.\n";
								}
								else
								{
									$try_again = pts_bool_question("Would you like to try downloading the file again (Y/n)?", true, "TRY_DOWNLOAD_AGAIN");

									if($try_again)
									{
										array_push($urls, $url);
									}
									else
									{
										$try_again = false;
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
						}

						if(!$try_again)
						{
							echo "\nDownload of Needed Test Dependencies Failed! Exiting.\n\n";
							return false;
						}
					}
					while(!$file_downloaded);
				}
			}
		}
	}
	return true;
}
function pts_validate_md5_download_file($filename, $verified_md5)
{
	$valid = true;

	if(!is_file($filename))
	{
		$valid = false;
	}
	else
	{
		if(!empty($verified_md5))
		{
			$real_md5 = md5_file($filename);

			if(count(explode("://", $verified_md5)) > 1)
			{
				$md5_file = explode("\n", trim(@file_get_contents($verified_md5)));

				for($i = 0; $i < count($md5_file) && $valid; $i++)
				{
					$line_explode = explode(" ", trim($md5_file[$i]));

					if($line_explode[(count($line_explode) - 1)] == $filename)
					{
						if($line_explode[0] != $real_md5)
						{
							$valid = false;
						}
					}
				}
			}
			else if($real_md5 != $verified_md5)
			{
				$valid = false;
			}
		}
	}

	return $valid;
}
function pts_remove_local_download_test_files($identifier)
{
	// Remove locally downloaded files for a given test
	foreach(pts_objects_test_downloads($identifier) as $test_file)
	{
		$file_location = TEST_ENV_DIR . $identifier . "/" . $test_file->get_filename();

		if(is_file($file_location))
		{
			unlink($file_location);
		}
	}
}
function pts_setup_install_test_directory($identifier, $remove_old_files = false)
{
	pts_mkdir(TEST_ENV_DIR);
	pts_mkdir(TEST_ENV_DIR . $identifier);

	if($remove_old_files)
	{
		// Remove any (old) files that were installed
		$ignore_files = array("pts-install.xml");
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
function pts_install_test($identifier, &$display_mode)
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
					return false;
				}

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
							$install_agreement = file_get_contents($install_agreement);

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
					$install_log = pts_call_test_script($identifier, "install", null, TEST_ENV_DIR . $identifier . "/", pts_run_additional_vars($identifier), false);
					pts_user_message($post_install_message);

					if(!empty($install_log))
					{
						@file_put_contents(TEST_ENV_DIR . $identifier . "/install.log", $install_log);
						$display_mode->test_install_output($install_log);
					}

					if(is_file(TEST_ENV_DIR . $identifier . "/install-exit-status"))
					{
						// If the installer writes its exit status to ~/install-exit-status, if it's non-zero the install failed
						$install_exit_status = trim(file_get_contents(TEST_ENV_DIR . $identifier . "/install-exit-status"));
						unlink(TEST_ENV_DIR . $identifier . "/install-exit-status");

						if($install_exit_status != "0")
						{
							// TODO: perhaps better way to handle this than to remove pts-install.xml
							if(is_file(TEST_ENV_DIR . $identifier . "/pts-install.xml"))
							{
								unlink(TEST_ENV_DIR . $identifier . "/pts-install.xml");
							}

							pts_setup_install_test_directory($identifier, true); // Remove installed files from the bunked installation

							echo "\nThe " . $identifier . " installer exited with a non-zero exit status. Installation failed.\n";
							return false;
						}
					}

					pts_test_generate_install_xml($identifier);
					pts_module_process("__post_test_install", $identifier);
					$installed = true;

					if(pts_string_bool(pts_read_user_config(P_OPTION_TEST_REMOVEDOWNLOADS, "FALSE")))
					{
						pts_remove_local_download_test_files($identifier); // Remove original downloaded files
					}
				}
				else
				{
					echo "No installation script found for " . $identifier . "\n";
					$installed = true;
					pts_test_generate_install_xml($identifier);
				}
			}
			else
			{
				$installed = true;

				if(!pts_is_assignment("SILENCE_MESSAGES"))
				{
					echo "Already Installed: " . $identifier . "\n";
				}
			}
		}
	}

	return $installed;
}
function pts_test_generate_install_xml($identifier)
{
	// Rrefresh an install XML for pts-install.xml
	// Similar to pts_test_refresh_install_xml()
 	$xml_parser = new pts_installed_test_tandem_XmlReader($identifier, false);
	$xml_writer = new tandem_XmlWriter();

	$average_test_duration = $xml_parser->getXMLValue(P_INSTALL_TEST_AVG_RUNTIME);
	if(!is_numeric($average_test_duration))
	{
		$average_test_duration = 0;
	}

	$latest_test_duration = $xml_parser->getXMLValue(P_INSTALL_TEST_LATEST_RUNTIME);
	if(!is_numeric($latest_test_duration))
	{
		$latest_test_duration = 0;
	}

	$test_version = pts_test_profile_version($identifier);
	$test_checksum = pts_test_checksum_installer($identifier);
	$sys_identifier = pts_system_identifier_string();
	$install_time = date("Y-m-d H:i:s");

	$times_run = $xml_parser->getXMLValue(P_INSTALL_TEST_TIMESRUN);
	if(empty($times_run))
	{
		$times_run = 0;
	}

	$xml_writer->addXmlObject(P_INSTALL_TEST_NAME, 1, $identifier);
	$xml_writer->addXmlObject(P_INSTALL_TEST_VERSION, 1, $test_version);
	$xml_writer->addXmlObject(P_INSTALL_TEST_CHECKSUM, 1, $test_checksum);
	$xml_writer->addXmlObject(P_INSTALL_TEST_SYSIDENTIFY, 1, $sys_identifier);
	$xml_writer->addXmlObject(P_INSTALL_TEST_INSTALLTIME, 2, $install_time);
	$xml_writer->addXmlObject(P_INSTALL_TEST_LASTRUNTIME, 2, date("Y-m-d H:i:s"));
	$xml_writer->addXmlObject(P_INSTALL_TEST_TIMESRUN, 2, $times_run);
	$xml_writer->addXmlObject(P_INSTALL_TEST_AVG_RUNTIME, 2, $average_test_duration);
	$xml_writer->addXmlObject(P_INSTALL_TEST_LATEST_RUNTIME, 2, $latest_test_duration);

	file_put_contents(TEST_ENV_DIR . $identifier . "/pts-install.xml", $xml_writer->getXML());
}
function pts_is_valid_download_url($string, $basename = null)
{
	// Checks for valid download URL
	return !(strpos($string, "://") == false || !empty($basename) && $basename != basename($string));
}

?>
