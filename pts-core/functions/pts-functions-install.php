<?php

function pts_recurse_install_benchmark($TO_INSTALL, &$INSTALL_OBJ)
{
	$type = pts_benchmark_type($TO_INSTALL);

	if($type == "BENCHMARK")
	{
		if(is_array($INSTALL_OBJ))
			pts_install_external_dependencies_list($TO_INSTALL, $INSTALL_OBJ);
		else
			pts_install_benchmark($TO_INSTALL);
	}
	else if($type == "TEST_SUITE")
	{
		echo "\nInstalling Benchmarks For " . ucwords($TO_INSTALL) . " Test Suite...\n\n";

		$xml_parser = new tandem_XmlReader(file_get_contents(XML_SUITE_LOCATION . "$TO_INSTALL.xml"));
		$suite_benchmarks = $xml_parser->getXMLArrayValues("PTSuite/PTSBenchmark/Benchmark");

		foreach($suite_benchmarks as $benchmark)
			pts_recurse_install_benchmark($benchmark, $INSTALL_OBJ);
	}
	else if(is_file(pts_input_correct_results_path($TO_INSTALL)))
	{
		$xml_parser = new tandem_XmlReader(file_get_contents(pts_input_correct_results_path($TO_INSTALL)));
		$suite_benchmarks = $xml_parser->getXMLArrayValues("PhoronixTestSuite/Benchmark/TestName");

		foreach($suite_benchmarks as $benchmark)
		{
			pts_recurse_install_benchmark($benchmark, $INSTALL_OBJ);
		}
	}
	else if(trim(file_get_contents("http://www.phoronix-test-suite.com/global/profile-check.php?id=$TO_INSTALL")) == "REMOTE_FILE")
	{
		$xml_parser = new tandem_XmlReader(file_get_contents("http://www.phoronix-test-suite.com/global/pts-results-viewer.php?id=$TO_INSTALL"));
		$suite_benchmarks = $xml_parser->getXMLArrayValues("PhoronixTestSuite/Benchmark/TestName");

		foreach($suite_benchmarks as $benchmark)
		{
			pts_recurse_install_benchmark($benchmark, $INSTALL_OBJ);
		}
	}
	else
		echo "\nNot recognized: $TO_INSTALL.\n";
}
function pts_install_benchmark($Benchmark)
{
	if(pts_benchmark_type($Benchmark) != "BENCHMARK")
		return;

	if(is_file(BENCHMARK_ENVIRONMENT . "$Benchmark/pts-install") && file_get_contents(BENCHMARK_ENVIRONMENT . "$Benchmark/pts-install") == md5_file(BENCHMARK_RESOURCE_LOCATION . "$Benchmark/install.sh"))
	{
		echo ucwords($Benchmark) . " is already installed, skipping installation routine...\n";
	}
	else
	{
		if(is_file(BENCHMARK_RESOURCE_LOCATION . "$Benchmark/install.sh"))
		{
			if(!is_dir(BENCHMARK_ENVIRONMENT))
			{
				mkdir(BENCHMARK_ENVIRONMENT);
			}
			if(!is_dir(BENCHMARK_ENVIRONMENT . $Benchmark))
			{
				mkdir(BENCHMARK_ENVIRONMENT . $Benchmark);
			}
			if(!is_dir(BENCHMARK_ENVIRONMENT . "pts-shared"))
			{
				mkdir(BENCHMARK_ENVIRONMENT . "pts-shared");
			}

			echo "\n=================================\n";
			echo "Installing Benchmark: $Benchmark";
			echo "\n=================================\n";
			echo pts_exec("cd " . BENCHMARK_RESOURCE_LOCATION . "$Benchmark/ && sh install.sh " . BENCHMARK_ENVIRONMENT . $Benchmark) . "\n";

			file_put_contents(BENCHMARK_ENVIRONMENT . "$Benchmark/pts-install", md5_file(BENCHMARK_RESOURCE_LOCATION . "$Benchmark/install.sh"));
		}
		else
			echo ucwords($Benchmark) . " has no installation script, skipping installation routine...\n";
	}
}
function pts_external_dependency_generic($Name)
{
	$generic_information = "";

	if(is_file(MISC_LOCATION . "distro-xml/generic-packages.xml"))
	{
		$xml_parser = new tandem_XmlReader(file_get_contents(MISC_LOCATION . "distro-xml/generic-packages.xml"));
		$package_name = $xml_parser->getXMLArrayValues("PhoronixTestSuite/ExternalDependencies/Package/GenericName");
		$title = $xml_parser->getXMLArrayValues("PhoronixTestSuite/ExternalDependencies/Package/Title");
		$possible_packages = $xml_parser->getXMLArrayValues("PhoronixTestSuite/ExternalDependencies/Package/PossibleNames");
		$file_check = $xml_parser->getXMLArrayValues("PhoronixTestSuite/ExternalDependencies/Package/FileCheck");

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

					$generic_information = "=================================\n" . $title[$selection] . "\n=================================\nPossible Package Names: " . $possible_packages[$selection] . "\n\n";
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
	pts_install_package_on_distribution_process($install_objects);
}
function pts_install_package_on_distribution_process($install_objects)
{
	if(!empty($install_objects))
	{
		$install_objects = implode(" ", $install_objects);
		$distribution = strtolower(os_vendor());

		if(is_file(MISC_LOCATION . "distro-scripts/ && sh install-" . $distribution . "-packages.sh") || is_link(MISC_LOCATION . "distro-scripts/ && sh install-" . $distribution . "-packages.sh"))
			echo pts_exec("cd " . MISC_LOCATION . "distro-scripts/ && sh install-" . $distribution . "-packages.sh $install_objects");
	}
}
function pts_install_external_dependencies_list($Benchmark, &$INSTALL_OBJ)
{
	if(pts_benchmark_type($Benchmark) != "BENCHMARK")
		return;

	$xml_parser = new tandem_XmlReader(file_get_contents(XML_PROFILE_LOCATION . $Benchmark . ".xml"));
	$title = $xml_parser->getXMLValue("PTSBenchmark/Information/Title");
	$dependencies = $xml_parser->getXMLValue("PTSBenchmark/Information/ExternalDependencies");

	if(empty($dependencies))
		return;

	$dependencies = explode(", ", $dependencies);

	$vendor = strtolower(os_vendor());

	if(is_file(MISC_LOCATION . "distro-xml/" . $vendor . "-packages.xml"))
	{
		$xml_parser = new tandem_XmlReader(file_get_contents(MISC_LOCATION . "distro-xml/" . $vendor . "-packages.xml"));
		$generic_package = $xml_parser->getXMLArrayValues("PhoronixTestSuite/ExternalDependencies/Package/GenericName");
		$distro_package = $xml_parser->getXMLArrayValues("PhoronixTestSuite/ExternalDependencies/Package/PackageName");
		$file_check = $xml_parser->getXMLArrayValues("PhoronixTestSuite/ExternalDependencies/Package/FileCheck");

		for($i = 0; $i < count($generic_package); $i++)
			if(!empty($generic_package[$i]) && in_array($generic_package[$i], $dependencies))
			{
				if(!in_array($distro_package[$i], $INSTALL_OBJ))
				{
					if(!empty($file_check[$i]))
					{
						$files = explode(",", $file_check[$i]);
						$add_dependency = pts_file_missing_check($files);
					}
					else
						$add_dependency = true;

					if($add_dependency)
						array_push($INSTALL_OBJ, $distro_package[$i]);
				}
			}
	}
	else
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

?>
