<?php

/*
	Phoronix Test Suite "Trondheim"
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts-functions-install.php: Functions needed for installing tests and external dependencies for PTS.

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

function pts_recurse_install_benchmark($TO_INSTALL, &$INSTALL_OBJ)
{
	$type = pts_test_type($TO_INSTALL);

	if($type == "BENCHMARK")
	{
		if(is_array($INSTALL_OBJ))
			pts_install_external_dependencies_list($TO_INSTALL, $INSTALL_OBJ);
		else
			pts_install_benchmark($TO_INSTALL);
	}
	else if($type == "TEST_SUITE")
	{
		if(!getenv("SILENT_INSTALL"))
			echo "\nInstalling Test Suite: " . $TO_INSTALL . "\n\n";

		$xml_parser = new tandem_XmlReader(XML_SUITE_DIR . $TO_INSTALL . ".xml");
		$suite_benchmarks = array_unique($xml_parser->getXMLArrayValues(P_SUITE_TEST_NAME));

		foreach($suite_benchmarks as $benchmark)
			pts_recurse_install_benchmark($benchmark, $INSTALL_OBJ);
	}
	else if(is_file(pts_input_correct_results_path($TO_INSTALL)))
	{
		$xml_parser = new tandem_XmlReader(pts_input_correct_results_path($TO_INSTALL));
		$suite_benchmarks = $xml_parser->getXMLArrayValues(P_RESULTS_TEST_TESTNAME);

		foreach($suite_benchmarks as $benchmark)
		{
			pts_recurse_install_benchmark($benchmark, $INSTALL_OBJ);
		}
	}
	else if(is_file(SAVE_RESULTS_DIR . $TO_INSTALL . "/composite.xml"))
	{
		$xml_parser = new tandem_XmlReader(SAVE_RESULTS_DIR . $TO_INSTALL . "/composite.xml");
		$suite_benchmarks = $xml_parser->getXMLArrayValues(P_RESULTS_TEST_TESTNAME);

		foreach($suite_benchmarks as $benchmark)
		{
			pts_recurse_install_benchmark($benchmark, $INSTALL_OBJ);
		}
	}
	else if(trim(@file_get_contents("http://www.phoronix-test-suite.com/global/profile-check.php?id=" . $TO_INSTALL)) == "REMOTE_FILE")
	{
		$xml_parser = new tandem_XmlReader(@file_get_contents("http://www.phoronix-test-suite.com/global/pts-results-viewer.php?id=" . $TO_INSTALL));
		$suite_benchmarks = $xml_parser->getXMLArrayValues(P_RESULTS_TEST_TESTNAME);

		foreach($suite_benchmarks as $benchmark)
		{
			pts_recurse_install_benchmark($benchmark, $INSTALL_OBJ);
		}
	}
	else
	{
		$exit_message = "";

		if(!getenv("SILENT_INSTALL"))
			$exit_message = "\nNot recognized: $TO_INSTALL\n";

		pts_exit($exit_message);
	}
}
function pts_download_benchmark_files($Benchmark)
{
	if(is_file(TEST_RESOURCE_DIR . $Benchmark . "/downloads.xml"))
	{
		$xml_parser = new tandem_XmlReader(TEST_RESOURCE_DIR . $Benchmark . "/downloads.xml");
		$package_url = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_URL);
		$package_md5 = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_MD5);
		$package_filename = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_FILENAME);
		$download_to = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_DESTINATION);
		$header_displayed = false;

		if(PTS_DOWNLOAD_CACHE_DIR != "" && strpos(PTS_DOWNLOAD_CACHE_DIR, "://") > 0 && ($xml_dc_file = @file_get_contents(PTS_DOWNLOAD_CACHE_DIR . "pts-download-cache.xml")) != FALSE)
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

		for($i = 0; $i < count($package_url); $i++)
		{
			if(empty($package_filename[$i]))
				$package_filename[$i] = basename($package_url[$i]);

			if($download_to[$i] == "SHARED")
				$download_location = TEST_ENV_DIR . "pts-shared/";
			else
				$download_location = TEST_ENV_DIR . $Benchmark . "/";

			if(!is_file($download_location . $package_filename[$i]))
			{
				if(!$header_displayed)
				{
					$download_append = "";
					if(($size = pts_test_estimated_download_size($Benchmark)) != "")
					{
						$download_append = "\nEstimated Download Size: " . $size . " MB";

						if(ceil(disk_free_space(PTS_TEMP_DIR) / 1048576) < $size)
						{
							echo pts_string_header("There is not enough temporary space (at " . PTS_TEMP_DIR . ") for this test.");
							pts_exit();
						}
						if(ceil(disk_free_space(TEST_ENV_DIR) / 1048576) < $size)
						{
							echo pts_string_header("There is not enough space (at " . TEST_ENV_DIR . ") for this test.");
							pts_exit();
						}
					}
					echo pts_string_header("Downloading Files For: " . $Benchmark . $download_append);

					$header_displayed = true;
				}

				if($package_url[$i] == $package_filename[$i])
					$urls = array();
				else
					$urls = explode(",", $package_url[$i]);

				if(count($urls) > 1)
					shuffle($urls);

				if(is_file(PTS_TEMP_DIR . $package_filename[$i]))
					unlink(PTS_TEMP_DIR . $package_filename[$i]);

				if(count($dc_file) > 0 && count($dc_md5) > 0)
				{
					$cache_search = true;
					for($f = 0; $f < count($dc_file) && $cache_search; $f++)
					{
						if($dc_file[$f] == $package_filename[$i] && $dc_md5[$f] == $package_md5[$i])
						{
							echo shell_exec("cd " . PTS_TEMP_DIR . " && wget " . PTS_DOWNLOAD_CACHE_DIR . $package_filename[$i] . " -O " . $package_filename[$i]);

							if(@md5_file(PTS_TEMP_DIR . $package_filename[$i]) != $package_md5[$i])
								@unlink(PTS_TEMP_DIR . $package_filename[$i]);
							else
							{
								shell_exec("mv " . PTS_TEMP_DIR . $package_filename[$i] . " " . $download_location);
								$urls = array();
							}

							$cache_search = false;
						}
					}
				}
				else if(is_file(PTS_DOWNLOAD_CACHE_DIR . $package_filename[$i]) && (empty($package_md5[$i]) || $package_md5[$i] == md5_file(PTS_DOWNLOAD_CACHE_DIR . $package_filename[$i])))
				{
					echo "Copying Cached File: " . $package_filename[$i] . "\n";

					if(copy(PTS_DOWNLOAD_CACHE_DIR . $package_filename[$i], $download_location . $package_filename[$i]))
						$urls = array();
				}

				if(count($urls) > 0)
				{
					$fail_count = 0;
					$try_again = true;

					do
					{
						$url = trim(array_pop($urls));
						echo "\n\nDownloading File: " . $package_filename[$i] . "\n\n";
						echo shell_exec("cd " . PTS_TEMP_DIR . " && wget " . $url . " -O " . $package_filename[$i]);


						if((is_file(PTS_TEMP_DIR . $package_filename[$i]) && !empty($package_md5[$i]) && md5_file(PTS_TEMP_DIR . $package_filename[$i]) != $package_md5[$i]) || !is_file(PTS_TEMP_DIR . $package_filename[$i]))
						{
							if(is_file(PTS_TEMP_DIR . $package_filename[$i]))
								unlink(PTS_TEMP_DIR . $package_filename[$i]);

							$file_downloaded = false;
							$fail_count++;
							echo "\nThe MD5 check-sum of the downloaded file is incorrect.\n";

							if($fail_count > 3)
							{
								$try_again = false;
							}
							else
							{
								if(count($urls) > 0)
								{
									echo "Attempting to re-download from another mirror...\n";
								}
								else
								{
									$try_again = pts_bool_question("Would you like to try downloading the file again (Y/n)?", true, "TRY_DOWNLOAD_AGAIN");

									if($try_again)
										array_push($urls, $url);
									else
										$try_again = false;
								}
							}
						}
						else
						{
							if(is_file(PTS_TEMP_DIR . $package_filename[$i]))
								shell_exec("mv " . PTS_TEMP_DIR . $package_filename[$i] . " " . $download_location);

							$file_downloaded = true;
							$fail_count = 0;
						}

						if(!$try_again)
						{
							pts_exit("\nDownload of Needed Test Dependencies Failed! Exiting...\n\n");
						}
					}
					while(!$file_downloaded);
				}
			}
		}
	}
}
function pts_install_benchmark($Benchmark)
{
	if(pts_test_type($Benchmark) != "BENCHMARK")
		return;

	if(!pts_test_architecture_supported($Benchmark))
	{
		echo pts_string_header($Benchmark . " is not supported on this platform (" . kernel_arch() . ").");
		return;
	}

	$custom_validated_output = "";

	if(is_file(TEST_RESOURCE_DIR . $Benchmark . "/validate-install.sh"))
	{
		$custom_validated_output = pts_exec("sh " . TEST_RESOURCE_DIR . $Benchmark . "/validate-install.sh " . TEST_ENV_DIR . $Benchmark);
	}
	else if(is_file(TEST_RESOURCE_DIR . $Benchmark . "/validate-install.php"))
	{
		$custom_validated_output = pts_exec(PHP_BIN . " " . TEST_RESOURCE_DIR . $Benchmark . "/validate-install.php " . TEST_ENV_DIR . $Benchmark);
	}
	if(!empty($custom_validated_output))
	{
		$custom_validated_output = trim($custom_validated_output);

		if($custom_validated_output != "1" && strtolower($custom_validated_output) != "true")
			return false;
	}

	if(pts_test_needs_updated_install($Benchmark) || defined("PTS_FORCE_INSTALL"))
	{
		if(!is_dir(TEST_ENV_DIR))
			mkdir(TEST_ENV_DIR);

		if(!is_dir(TEST_ENV_DIR . $Benchmark))
			mkdir(TEST_ENV_DIR . $Benchmark);

		if(!is_dir(TEST_ENV_DIR . "pts-shared"))
			mkdir(TEST_ENV_DIR . "pts-shared");

		pts_download_benchmark_files($Benchmark);

		if(is_file(TEST_RESOURCE_DIR . $Benchmark . "/install.sh") || is_file(TEST_RESOURCE_DIR . $Benchmark . "/install.php"))
		{
			pts_module_process("__pre_test_install");
			$install_header = "Installing Benchmark: " . $Benchmark;

			if(($size = pts_test_estimated_download_size($Benchmark)) != "")
				$install_header .= "\nEstimated Install Size: " . $size . " MB";

			echo pts_string_header($install_header);

			if(!empty($size) && ceil(disk_free_space(TEST_ENV_DIR) / 1048576) < $size)
			{
				echo pts_string_header("There is not enough space (at " . TEST_ENV_DIR . ") for this test to be installed.");
				pts_exit();
			}

			if(is_file(TEST_RESOURCE_DIR . $Benchmark . "/install.sh"))
			{
				echo pts_exec("cd " .  TEST_ENV_DIR . $Benchmark . "/ && sh " . TEST_RESOURCE_DIR . $Benchmark . "/install.sh " . TEST_ENV_DIR . $Benchmark) . "\n";
			}
			else if(is_file(TEST_RESOURCE_DIR . $Benchmark . "/install.php"))
			{
				echo pts_exec("cd " .  TEST_ENV_DIR . $Benchmark . "/ && " . PHP_BIN . " " . TEST_RESOURCE_DIR . $Benchmark . "/install.php " . TEST_ENV_DIR . $Benchmark) . "\n";
			}
			pts_test_generate_install_xml($Benchmark);
			pts_module_process("__post_test_install");
		}
		else
		{
			echo "Installation script missing for " . $Benchmark . "\n";
		}
	}
	else
	{
		if(!getenv("SILENT_INSTALL"))
			echo $Benchmark . " is already installed, skipping installation routine...\n";
	}
}
function pts_external_dependency_generic($Name)
{
	$generic_information = "";

	if(is_file(XML_DISTRO_DIR . "generic-packages.xml"))
	{
		$xml_parser = new tandem_XmlReader(XML_DISTRO_DIR . "generic-packages.xml");
		$package_name = $xml_parser->getXMLArrayValues(P_EXDEP_PACKAGE_GENERIC);
		$title = $xml_parser->getXMLArrayValues(P_EXDEP_PACKAGE_TITLE);
		$possible_packages = $xml_parser->getXMLArrayValues(P_EXDEP_PACKAGE_POSSIBLENAMES);
		$file_check = $xml_parser->getXMLArrayValues(P_EXDEP_PACKAGE_FILECHECK);

		$selection = -1;
		$PTS_MANUAL_SUPPORT = 0;

		for($i = 0; $i < count($title) && $selection == -1; $i++)
		{
			if($Name == $package_name[$i])
			{
				$selection = $i;
				if(pts_file_missing_check(explode(",", $file_check[$selection])))
				{
					if($PTS_MANUAL_SUPPORT == 0)
						$PTS_MANUAL_SUPPORT = 1;

					echo pts_string_header($title[$selection] . "\nPossible Package Names: " . $possible_packages[$selection]);
				}
			}
		}

		if($PTS_MANUAL_SUPPORT == 1)
		{
			echo "The above dependencies should be installed before proceeding. Press any key when you're ready to continue.";
			fgets(STDIN);
		}
	}

	return $generic_information;
}
function pts_file_missing_check($file_arr)
{
	$file_missing = false;

	foreach($file_arr as $file)
	{
		$file = trim($file);

		if(!is_file($file) && !is_dir($file) && !is_link($file))
			$file_missing = true;
	}

	return $file_missing;
}
function pts_install_package_on_distribution($benchmark)
{
	$benchmark = strtolower($benchmark);
	$install_objects = array();
	pts_recurse_install_benchmark($benchmark, $install_objects);
	pts_install_packages_on_distribution_process($install_objects);
}
function pts_install_packages_on_distribution_process($install_objects)
{
	if(!empty($install_objects))
	{
		if(is_array($install_objects))
			$install_objects = implode(" ", $install_objects);

		$distribution = pts_vendor_identifier();

		if(is_file(SCRIPT_DISTRO_DIR . "install-" . $distribution . "-packages.sh") || is_link(SCRIPT_DISTRO_DIR . "install-" . $distribution . "-packages.sh"))
		{
			echo "This process may take several minutes...\n";
			echo shell_exec("cd " . SCRIPT_DISTRO_DIR . " && sh install-" . $distribution . "-packages.sh " . $install_objects);
		}
		else
			echo "Distribution install script not found!";
	}
}
function pts_install_external_dependencies_list($Benchmark, &$INSTALL_OBJ)
{
	if(pts_test_type($Benchmark) != "BENCHMARK")
		return;

	$xml_parser = new tandem_XmlReader(XML_PROFILE_DIR . $Benchmark . ".xml");
	$title = $xml_parser->getXMLValue(P_TEST_TITLE);
	$dependencies = $xml_parser->getXMLValue(P_TEST_EXDEP);

	if(empty($dependencies))
		return;

	$dependencies = explode(',', $dependencies);

	for($i = 0; $i < count($dependencies); $i++)
		$dependencies[$i] = trim($dependencies[$i]);

	$vendor = pts_vendor_identifier();

	if(!pts_package_generic_to_distro_name($INSTALL_OBJ, $dependencies))
	{
		$package_string = "";
		foreach($dependencies as $dependency)
		{
			$package_string .= pts_external_dependency_generic($dependency);
		}

		if(!empty($package_string))
			echo "\nSome additional dependencies are required to run or more of these tests, and they could not be installed automatically for your distribution. Below are the software packages that must be installed for the test(s) to run properly.\n\n" . $package_string;
	}
}
function pts_package_generic_to_distro_name(&$package_install_array, $generic_names)
{
	$vendor = pts_vendor_identifier();
	$generated = false;

	if(is_file(XML_DISTRO_DIR . $vendor . "-packages.xml"))
	{
		$xml_parser = new tandem_XmlReader(XML_DISTRO_DIR . $vendor . "-packages.xml");
		$generic_package = $xml_parser->getXMLArrayValues(P_EXDEP_PACKAGE_GENERIC);
		$distro_package = $xml_parser->getXMLArrayValues(P_EXDEP_PACKAGE_SPECIFIC);
		$file_check = $xml_parser->getXMLArrayValues(P_EXDEP_PACKAGE_FILECHECK);

		for($i = 0; $i < count($generic_package); $i++)
			if(!empty($generic_package[$i]) && in_array($generic_package[$i], $generic_names))
			{
				if(!in_array($distro_package[$i], $package_install_array))
				{
					if(!empty($file_check[$i]))
					{
						$files = explode(",", $file_check[$i]);
						$add_dependency = pts_file_missing_check($files);
					}
					else
						$add_dependency = true;

					if($add_dependency)
						array_push($package_install_array, $distro_package[$i]);
				}
			}
		$generated = true;
	}

	return $generated;
}
function pts_test_architecture_supported($identifier)
{
	$supported = true;

	if(is_file(XML_PROFILE_DIR . $identifier . ".xml"))
	{
	 	$xml_parser = new tandem_XmlReader(XML_PROFILE_DIR . $identifier . ".xml");
		$archs = $xml_parser->getXMLValue(P_TEST_SUPPORTEDARCHS);

		if(!empty($archs))
		{
			$archs = explode(",", $archs);

			foreach($archs as $key => $value)
				$archs[$key] = trim($value);

			$this_arch = kernel_arch();

			if(strlen($this_arch) > 3 && substr($this_arch, -2) == "86")
				$this_arch = "x86";

			if(!in_array($this_arch, $archs))
				$supported = false;
		}
	}

	return $supported;
}
function pts_test_estimated_download_size($identifier)
{
	$size = "";

	if(is_file(XML_PROFILE_DIR . $identifier . ".xml"))
	{
	 	$xml_parser = new tandem_XmlReader(XML_PROFILE_DIR . $identifier . ".xml");
		$size = $xml_parser->getXMLValue(P_TEST_DOWNLOADSIZE);
	}

	return $size;
}
function pts_test_estimated_environment_size($identifier)
{
	$size = "";

	if(is_file(XML_PROFILE_DIR . $identifier . ".xml"))
	{
	 	$xml_parser = new tandem_XmlReader(XML_PROFILE_DIR . $identifier . ".xml");
		$size = $xml_parser->getXMLValue(P_TEST_ENVIRONMENTSIZE);
	}

	return $size;
}
?>
