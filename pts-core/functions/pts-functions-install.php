<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts-functions-install.php: Functions needed for installing tests for PTS.

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

require_once("pts-core/functions/pts-functions-install_dependencies.php");

function pts_start_install($to_install)
{
	if(!is_array($to_install))
	{
		$to_install = array($to_install);
	}

	if(IS_SCTP_MODE)
	{
		$tests = array($to_install[0]);
	}
	else
	{
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

			if(pts_read_assignment("COMMAND") != "benchmark")
			{
				echo pts_string_header("\nNot recognized: " . $to_install[0] . "\n");
			}
			return false;
		}
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
			foreach(glob("/media/*/download-cache/") as $dir)
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

						if(ceil(disk_free_space(TEST_ENV_DIR) / 1048576) < $size)
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
							echo pts_download(PTS_DOWNLOAD_CACHE_DIR . $package_filename, $download_location);

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
							echo "Copying Cached File: " . $package_filename . "\n";

							if(copy($cache_directories[$j] . $package_filename, $download_destination))
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
						if(getenv("PTS_BATCH_MODE") == false && pts_string_bool(pts_read_user_config(P_OPTION_PROMPT_DOWNLOADLOC, "FALSE")) && count($urls) > 1)
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
		// Remove any files that were installed, since this test will be reinstalled and remove any old download files not used
		$ignore_files = array("pts-install.xml");
		foreach(pts_objects_test_downloads($identifier) as $download_object)
		{
			array_push($ignore_files, $download_object->get_filename());
		}
		pts_remove(TEST_ENV_DIR . $identifier, $ignore_files);
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

					if(($size = pts_estimated_download_size($identifier)) > 0)
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
				if(pts_read_assignment("COMMAND") != "benchmark")
				{
					echo "Already Installed: " . $identifier . "\n";
				}
			}
		}
	}
	return $installed;
}
function pts_generate_download_cache()
{
	// Generates a PTS Download Cache
	if(!is_dir(PTS_DOWNLOAD_CACHE_DIR))
	{
		mkdir(PTS_DOWNLOAD_CACHE_DIR);
	}
	else
	{
		if(is_file(PTS_DOWNLOAD_CACHE_DIR . "make-cache-howto"))
		{
			unlink(PTS_DOWNLOAD_CACHE_DIR . "make-cache-howto");
		}
	}

	$xml_writer = new tandem_XmlWriter();
	$xml_writer->addXmlObject(P_CACHE_PTS_VERSION, -1, PTS_VERSION);
	$file_counter = 0;
	$normal_downloads = glob(TEST_RESOURCE_DIR . "*/downloads.xml");
	$base_downloads = glob(TEST_RESOURCE_DIR . "base/*/downloads.xml");
	
	foreach(array_merge($normal_downloads, $base_downloads) as $downloads_file)
	{
		$test = array_pop(explode("/", dirname($downloads_file)));
		$xml_parser = new tandem_XmlReader($downloads_file);
		$package_url = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_URL);
		$package_md5 = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_MD5);
		$package_filename = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_FILENAME);
		$cached = false;

		echo "\nChecking Downloads For: " . $test . "\n";
		$test_install_message = true;

		for($i = 0; $i < count($package_url); $i++)
		{
			if(empty($package_filename[$i]))
			{
				$package_filename[$i] = basename($package_url[$i]);
			}

			if(is_file(PTS_DOWNLOAD_CACHE_DIR . $package_filename[$i]) && (empty($package_md5[$i]) || md5_file(PTS_DOWNLOAD_CACHE_DIR . $package_filename[$i]) == $package_md5[$i]))
			{
				echo "\tPreviously Cached: " . $package_filename[$i] . "\n";
				$cached = true;
			}
			else
			{
				if(is_dir(TEST_ENV_DIR . $test . "/"))
				{
					if(is_file(TEST_ENV_DIR . $test . "/" . $package_filename[$i]))
					{
						if(empty($package_md5[$i]) || md5_file(TEST_ENV_DIR . $test . "/" . $package_filename[$i]) == $package_md5[$i])
						{
							echo "\tCaching: " . $package_filename[$i] . "\n";

							if(copy(TEST_ENV_DIR . $test . "/" . $package_filename[$i], PTS_DOWNLOAD_CACHE_DIR . $package_filename[$i]))
							{
								$cached = true;
							}
						}
					}
				}
				else
				{
					if($test_install_message)
					{
						echo "\tTest Not Installed\n";
						$test_install_message = false;
					}
				}
			}

			if($cached)
			{
				$xml_writer->addXmlObject(P_CACHE_PACKAGE_FILENAME, $file_counter, $package_filename[$i]);
				$xml_writer->addXmlObject(P_CACHE_PACKAGE_MD5, $file_counter, $package_md5[$i]);
				$file_counter++;
			}
		}
	}

	$cache_xml = $xml_writer->getXML();
	file_put_contents(PTS_DOWNLOAD_CACHE_DIR . "pts-download-cache.xml", $cache_xml);
}

?>
