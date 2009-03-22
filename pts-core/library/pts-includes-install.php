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

function pts_start_install($to_install)
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
			"\nEstimated Install Size: " . pts_test_estimated_environment_size($will_be_installed) . " MB");
		}
	}
	foreach($tests as $test)
	{
		pts_install_test($test);
	}

	pts_set_assignment_next("PREV_TEST_INSTALLED", $tests[(count($tests) - 1)]);
	pts_module_process("__post_install_process", $tests);
}
function pts_download_test_files($identifier)
{
	// Download needed files for a test
	$download_packages = pts_objects_test_downloads($identifier);

	if(count($download_packages) > 0)
	{
		$header_displayed = false;
		$cache_directories = array(PTS_DOWNLOAD_CACHE_DIR);

		if(strpos(PTS_DOWNLOAD_CACHE_DIR, "://") > 0 && ($xml_dc_file = @file_get_contents(PTS_DOWNLOAD_CACHE_DIR . "pts-download-cache.xml")) != false)
		{
			$xml_dc_parser = new tandem_XmlReader($xml_dc_file);
			$dc_file = $xml_dc_parser->getXMLArrayValues(P_CACHE_PACKAGE_FILENAME);
			$dc_md5 = $xml_dc_parser->getXMLArrayValues(P_CACHE_PACKAGE_MD5);
		}
		else
		{
			$dc_file = array();
			$dc_md5 = array();
		}

		if(pts_string_bool(pts_read_user_config(P_OPTION_CACHE_SEARCHMEDIA, "TRUE")))
		{
			$download_cache_dirs = array_merge(
			glob("/media/*/download-cache/"),
			glob("/Volumes/*/download-cache/")
			);

			foreach($download_cache_dirs as $dir)
			{
				array_push($cache_directories, $dir);
			}
		}

		for($i = 0; $i < count($download_packages); $i++)
		{
			$download_location = TEST_ENV_DIR . $identifier . "/";
			$package_filename = $download_packages[$i]->get_filename();
			$download_destination = $download_location . $package_filename;

			if(!is_file($download_destination))
			{
				if(!$header_displayed)
				{
					$download_append = "";
					if(($size = pts_estimated_download_size($identifier)) > 0)
					{
						$download_append = "\nEstimated Download Size: " . $size . " MB";

						if(ceil(disk_free_space(TEST_ENV_DIR) / 1048576) < ($size + 50))
						{
							echo pts_string_header("There is not enough space (at " . TEST_ENV_DIR . ") for this test.");
							return false;
						}
					}
					echo pts_string_header("Downloading Files For: " . $identifier . $download_append);

					$header_displayed = true;
				}

				$urls = $download_packages[$i]->get_download_url_array();
				$package_md5 = $download_packages[$i]->get_md5();

				if(count($dc_file) > 0 && count($dc_md5) > 0)
				{
					$cache_search = true;
					for($f = 0; $f < count($dc_file) && $cache_search; $f++)
					{
						if($dc_file[$f] == $package_filename && $dc_md5[$f] == $package_md5)
						{
							echo "Downloading From Remote Cache: " . $package_filename . "\n\n";
							echo pts_download(PTS_DOWNLOAD_CACHE_DIR . $package_filename, $download_destination . ".temp");
							echo "\n";

							if(!pts_validate_md5_download_file($download_destination . ".temp", $package_md5))
							{
								@unlink($download_destination . ".temp");
							}
							else
							{
								pts_move_file($package_filename . ".temp", $package_filename, $download_location);
								$urls = array();
							}
							$cache_search = false;
						}
					}
				}
				else
				{
					$used_cache = false;
					for($j = 0; $j < count($cache_directories) && $used_cache == false; $j++)
					{
						if(pts_validate_md5_download_file($cache_directories[$j] . $package_filename, $package_md5))
						{

							if(pts_string_bool(pts_read_user_config(P_OPTION_CACHE_SYMLINK, "FALSE")))
							{
								// P_OPTION_CACHE_SYMLINK is disabled by default for now
								echo "Linking Cached File: " . $package_filename . "\n";
								pts_symlink($cache_directories[$j] . $package_filename, $download_destination);
							}
							else
							{
								echo "Copying Cached File: " . $package_filename . "\n";
								copy($cache_directories[$j] . $package_filename, $download_destination);
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
						if(!pts_is_assignment("IS_BATCH_MODE") && pts_string_bool(pts_read_user_config(P_OPTION_PROMPT_DOWNLOADLOC, "FALSE")) && count($urls) > 1)
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

							if(is_numeric($mirror_choice))
							{
								$url = $urls[($mirror_choice - 1)];
							}
							else
							{
								$url = $mirror_choice;
							}
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
						echo pts_download($url, $download_destination . ".temp");

						if(!pts_validate_md5_download_file($download_destination . ".temp", $package_md5))
						{
							if(is_file($download_destination . ".temp"))
							{
								unlink($download_destination . ".temp");
							}

							$file_downloaded = false;
							$fail_count++;
							echo "\nThe MD5 check-sum of the downloaded file is incorrect.\n";

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
							if(is_file($download_destination . ".temp"))
							{
								pts_move_file($package_filename . ".temp", $package_filename, $download_location);
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
			@unlink($file_location);
		}
	}
}
function pts_setup_install_test_directory($identifier, $remove_old_files = false)
{
	if(!is_dir(TEST_ENV_DIR))
	{
		mkdir(TEST_ENV_DIR);
	}

	if(!is_dir(TEST_ENV_DIR . $identifier))
	{
		mkdir(TEST_ENV_DIR . $identifier);
	}
	else if($remove_old_files)
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
function pts_install_test($identifier)
{
	if(!pts_is_test($identifier))
	{
		return;
	}

	// Install a test
	$installed = false;
	if(!pts_test_architecture_supported($identifier))
	{
		echo pts_string_header($identifier . " is not supported on this architecture: " . sw_os_architecture());
	}
	else if(!pts_test_platform_supported($identifier))
	{
		echo pts_string_header($identifier . " is not supported by this operating system (" . OPERATING_SYSTEM . ").");
	}
	else if(!pts_test_version_supported($identifier))
	{
		echo pts_string_header($identifier . " is not supported by this version of the Phoronix Test Suite (" . PTS_VERSION . ").");
	}
	else
	{
		// TODO: clean up validate-install and put in pts_validate_test_install
		$custom_validated_output = trim(pts_call_test_script($identifier, "validate-install", "\nValidating Installation...\n", TEST_ENV_DIR . $identifier . "/", pts_run_additional_vars($identifier), false));

		if(!empty($custom_validated_output) && !pts_string_bool($custom_validated_output))
		{
			$installed = false;
		}
		else
		{
			if(pts_test_needs_updated_install($identifier))
			{
				if(!pts_is_assignment("PTS_TOTAL_SIZE_MSG"))
				{
					if(isset($argv[1]))
					{
						$total_download_size = pts_estimated_download_size($argv[1]);

						if($total_download_size > 0 && pts_is_suite($argv[1]))
						{
							echo pts_string_header("Total Estimated Download Size: " . $total_download_size . " MB");
						}
					}

					pts_set_assignment("PTS_TOTAL_SIZE_MSG", 1);
				}

				pts_setup_install_test_directory($identifier, true);
				$download_test_files = pts_download_test_files($identifier);

				if($download_test_files == false)
				{
					echo "\nInstallation of " . $identifier . " test failed.\n";
					return false;
				}

				if(is_file(pts_location_test_resources($identifier) . "install.sh") || is_file(pts_location_test_resources($identifier) . "install.php"))
				{
					pts_module_process("__pre_test_install", $identifier);
					$install_header = "Installing Test: " . $identifier;

					if(($size = pts_test_estimated_environment_size($identifier)) > 0)
					{
						$install_header .= "\nEstimated Install Size: " . $size . " MB";
					}

					echo pts_string_header($install_header);

					if(!empty($size) && ceil(disk_free_space(TEST_ENV_DIR) / 1048576) < $size)
					{
						echo "\nThere is not enough space (at " . TEST_ENV_DIR . ") for this test to be installed.\n";
						return false;
					}

					$xml_parser = new pts_test_tandem_XmlReader($identifier);
					$pre_install_message = $xml_parser->getXMLValue(P_TEST_PREINSTALLMSG);
					$post_install_message = $xml_parser->getXMLValue(P_TEST_POSTINSTALLMSG);

					pts_user_message($pre_install_message);

					$install_log = pts_call_test_script($identifier, "install", null, TEST_ENV_DIR . $identifier . "/", pts_run_additional_vars($identifier), false);

					if(!empty($install_log))
					{
						@file_put_contents(TEST_ENV_DIR . $identifier . "/install.log", $install_log);

						if(strlen($install_log) < 10240)
						{
							// Not worth printing files over 10kb to screen
							echo $install_log;
						}
					}

					pts_user_message($post_install_message);

					pts_test_generate_install_xml($identifier);
					pts_module_process("__post_test_install", $identifier);

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

	$test_duration = $xml_parser->getXMLValue(P_INSTALL_TEST_AVG_RUNTIME);
	if(!is_numeric($test_duration))
	{
		$test_duration = $this_test_duration;
	}
	if(is_numeric($this_test_duration) && $this_test_duration > 0)
	{
		$test_duration = ceil((($test_duration * $xml_parser->getXMLValue(P_INSTALL_TEST_TIMESRUN)) + $this_test_duration) / ($xml_parser->getXMLValue(P_INSTALL_TEST_TIMESRUN) + 1));
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
	$xml_writer->addXmlObject(P_INSTALL_TEST_AVG_RUNTIME, 2, $test_duration, 2);

	file_put_contents(TEST_ENV_DIR . $identifier . "/pts-install.xml", $xml_writer->getXML());
}

?>
