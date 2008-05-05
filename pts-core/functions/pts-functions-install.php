<?php

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
		echo "\nInstalling Software For " . ucwords($TO_INSTALL) . " Test Suite...\n\n";

		$xml_parser = new tandem_XmlReader(file_get_contents(XML_SUITE_DIR . $TO_INSTALL . ".xml"));
		$suite_benchmarks = $xml_parser->getXMLArrayValues(P_SUITE_TEST_NAME);

		foreach($suite_benchmarks as $benchmark)
			pts_recurse_install_benchmark($benchmark, $INSTALL_OBJ);
	}
	else if(is_file(pts_input_correct_results_path($TO_INSTALL)))
	{
		$xml_parser = new tandem_XmlReader(file_get_contents(pts_input_correct_results_path($TO_INSTALL)));
		$suite_benchmarks = $xml_parser->getXMLArrayValues(P_RESULTS_TEST_TESTNAME);

		foreach($suite_benchmarks as $benchmark)
		{
			pts_recurse_install_benchmark($benchmark, $INSTALL_OBJ);
		}
	}
	else if(trim(@file_get_contents("http://www.phoronix-test-suite.com/global/profile-check.php?id=$TO_INSTALL")) == "REMOTE_FILE")
	{
		$xml_parser = new tandem_XmlReader(@file_get_contents("http://www.phoronix-test-suite.com/global/pts-results-viewer.php?id=$TO_INSTALL"));
		$suite_benchmarks = $xml_parser->getXMLArrayValues(P_RESULTS_TEST_TESTNAME);

		foreach($suite_benchmarks as $benchmark)
		{
			pts_recurse_install_benchmark($benchmark, $INSTALL_OBJ);
		}
	}
	else
		pts_exit("\nNot recognized: $TO_INSTALL.\n");
}
function pts_download_benchmark_files($Benchmark)
{
	if(is_file(BENCHMARK_RESOURCE_DIR . $Benchmark . "/downloads.xml"))
	{
		$xml_parser = new tandem_XmlReader(file_get_contents(BENCHMARK_RESOURCE_DIR . $Benchmark . "/downloads.xml"));
		$package_url = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_URL);
		$package_md5 = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_MD5);
		$package_filename = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_FILENAME);
		$download_to = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_DESTINATION);
		$header_displayed = false;

		for($i = 0; $i < count($package_url); $i++)
		{
			if(empty($package_filename[$i]))
			{
				$package_filename[$i] = basename($package_url[$i]);
			}

			if((!is_file(BENCHMARK_ENV_DIR . $Benchmark . "/" . $package_filename[$i]) && $download_to[$i] != "SHARED") || (!is_file(BENCHMARK_ENV_DIR . "pts-shared/" . $package_filename[$i]) && $download_to[$i] == "SHARED"))
			{
				if(!$header_displayed)
				{
					echo pts_string_header("Downloading Files For: " . $Benchmark);
					$header_displayed = true;
				}

				$urls = explode(",", $package_url[$i]);

				if(count($urls) > 1)
					shuffle($urls);

				$fail_count = 0;
				$try_again = true;

				if($download_to[$i] == "SHARED")
					$download_location = BENCHMARK_ENV_DIR . "pts-shared/";
				else
					$download_location = BENCHMARK_ENV_DIR . $Benchmark . "/";

				if(is_file(PTS_DOWNLOAD_CACHE_DIR . $package_filename[$i]) && $package_md5[$i] == md5_file(PTS_DOWNLOAD_CACHE_DIR . $package_filename[$i]))
				{
					echo "\nTransferring Cached File: " . $package_filename[$i] . "\n";

					if(copy(PTS_DOWNLOAD_CACHE_DIR . $package_filename[$i], $download_location . $package_filename[$i]))
						$urls = array();
				}

				if(count($urls) > 0)
				{
					do
					{
						echo $url = trim(array_pop($urls));
						echo "\n\nDownloading File: " . $package_filename[$i] . "\n\n";
						echo shell_exec("cd " . $download_location . " && wget " . $url . " -O " . $package_filename[$i]);


						if((is_file($download_location . $package_filename[$i]) && !empty($package_md5[$i]) && md5_file($download_location . $package_filename[$i]) != $package_md5[$i]) || !is_file($download_location . $package_filename[$i]))
						{
							unlink($download_location . $package_filename[$i]);
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
							$file_downloaded = true;
							$fail_count = 0;
						}

						if(!$try_again)
						{
							pts_exit("\nDownload of Needed Test Dependencies Failed! Exiting...\n\n");
						}

					}while(!$file_downloaded);
				}
			}
		}
	}
}
function pts_install_benchmark($Benchmark)
{
	if(pts_test_type($Benchmark) != "BENCHMARK")
		return;

	if(!defined("PTS_FORCE_INSTALL") && is_file(BENCHMARK_ENV_DIR . "$Benchmark/pts-install") && ((is_file(BENCHMARK_RESOURCE_DIR . "$Benchmark/install.sh") && file_get_contents(BENCHMARK_ENV_DIR . "$Benchmark/pts-install") == @md5_file(BENCHMARK_RESOURCE_DIR . "$Benchmark/install.sh")) || (is_file(BENCHMARK_RESOURCE_DIR . "$Benchmark/install.php") && file_get_contents(BENCHMARK_ENV_DIR . "$Benchmark/pts-install") == @md5_file(BENCHMARK_RESOURCE_DIR . "$Benchmark/install.php"))))
	{
		// pts_download_benchmark_files($Benchmark);
		echo ucwords($Benchmark) . " is already installed, skipping installation routine...\n";
	}
	else
	{
		if(!is_dir(BENCHMARK_ENV_DIR))
		{
			mkdir(BENCHMARK_ENV_DIR);
		}
		if(!is_dir(BENCHMARK_ENV_DIR . $Benchmark))
		{
			mkdir(BENCHMARK_ENV_DIR . $Benchmark);
		}
		if(!is_dir(BENCHMARK_ENV_DIR . "pts-shared"))
		{
			mkdir(BENCHMARK_ENV_DIR . "pts-shared");
		}

		pts_download_benchmark_files($Benchmark);

		if(is_file(BENCHMARK_RESOURCE_DIR . "$Benchmark/install.sh") || is_file(BENCHMARK_RESOURCE_DIR . "$Benchmark/install.php"))
		{
			echo pts_string_header("Installing Benchmark: " . $Benchmark);

			if(is_file(BENCHMARK_RESOURCE_DIR . "$Benchmark/install.sh"))
			{
				echo pts_exec("cd " . BENCHMARK_RESOURCE_DIR . "$Benchmark/ && sh install.sh " . BENCHMARK_ENV_DIR . $Benchmark) . "\n";
				file_put_contents(BENCHMARK_ENV_DIR . "$Benchmark/pts-install", md5_file(BENCHMARK_RESOURCE_DIR . "$Benchmark/install.sh"));
			}
			else if(is_file(BENCHMARK_RESOURCE_DIR . "$Benchmark/install.php"))
			{
				echo pts_exec("cd " . BENCHMARK_RESOURCE_DIR . "$Benchmark/ && " . PHP_BIN . " install.php " . BENCHMARK_ENV_DIR . $Benchmark) . "\n";
				file_put_contents(BENCHMARK_ENV_DIR . "$Benchmark/pts-install", md5_file(BENCHMARK_RESOURCE_DIR . "$Benchmark/install.php"));
			}
		}
		else
		{
			file_put_contents(BENCHMARK_ENV_DIR . "$Benchmark/pts-install", 0);
			echo ucwords($Benchmark) . " has no installation script, skipping installation routine...\n";
		}
	}
}
function pts_external_dependency_generic($Name)
{
	$generic_information = "";

	if(is_file(XML_DISTRO_DIR . "generic-packages.xml"))
	{
		$xml_parser = new tandem_XmlReader(file_get_contents(XML_DISTRO_DIR . "generic-packages.xml"));
		$package_name = $xml_parser->getXMLArrayValues(P_EXDEP_PACKAGE_GENERIC);
		$title = $xml_parser->getXMLArrayValues(P_EXDEP_PACKAGE_TITLE);
		$possible_packages = $xml_parser->getXMLArrayValues(P_EXDEP_PACKAGE_POSSIBLENAMES);
		$file_check = $xml_parser->getXMLArrayValues(P_EXDEP_PACKAGE_FILECHECK);

		$selection = -1;
		for($i = 0; $i < count($title) && $selection == -1; $i++)
		{
			if($Name == $package_name[$i])
			{
				$selection = $i;

				if(pts_file_missing_check(explode(",", $file_check[$selection])))
				{
					if(!defined("PTS_MANUAL_SUPPORT"))
						define("PTS_MANUAL_SUPPORT", 1);

					echo pts_string_header($title[$selection] . "\nPossible Package Names: " . $possible_packages[$selection]);
				}
			}
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

		$distribution = strtolower(os_vendor());

		if(is_file(SCRIPT_DISTRO_DIR . "install-" . $distribution . "-packages.sh") || is_link(SCRIPT_DISTRO_DIR . "install-" . $distribution . "-packages.sh"))
			echo shell_exec("cd " . SCRIPT_DISTRO_DIR . " && sh install-" . $distribution . "-packages.sh $install_objects");
		else
			echo "Distribution install script not found!";
	}
}
function pts_install_external_dependencies_list($Benchmark, &$INSTALL_OBJ)
{
	if(pts_test_type($Benchmark) != "BENCHMARK")
		return;

	$xml_parser = new tandem_XmlReader(file_get_contents(XML_PROFILE_DIR . $Benchmark . ".xml"));
	$title = $xml_parser->getXMLValue(P_TEST_TITLE);
	$dependencies = $xml_parser->getXMLValue(P_TEST_EXDEP);

	if(empty($dependencies))
		return;

	$dependencies = explode(", ", $dependencies);

	$vendor = strtolower(os_vendor());

	if(!pts_package_generic_to_distro_name($INSTALL_OBJ, $dependencies))
	{
		$package_string = "";
		foreach($dependencies as $dependency)
		{
			$package_string .= pts_external_dependency_generic($dependency);
		}

		if(!empty($package_string))
			echo "\nSome additional dependencies are required to run or more of these benchmarks, and they could not be installed automatically for your distribution by the Phoronix Test Suite. Below are the software packages that must be installed for this benchmark to run properly.\n\n" . $package_string;
	}
}
function pts_package_generic_to_distro_name(&$package_install_array, $generic_names)
{
	$vendor = strtolower(os_vendor());
	$generated = false;

	if(is_file(XML_DISTRO_DIR . $vendor . "-packages.xml"))
	{
		$xml_parser = new tandem_XmlReader(file_get_contents(XML_DISTRO_DIR . $vendor . "-packages.xml"));
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
?>
