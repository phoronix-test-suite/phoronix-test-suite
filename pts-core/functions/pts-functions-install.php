<?php

function pts_recurse_install_benchmark($TO_INSTALL, &$INSTALL_OBJ)
{
	$type = pts_benchmark_type($TO_INSTALL);

	if($type == "BENCHMARK")
	{
		if(is_array($INSTALL_OBJ))
			pts_install_external_dependencies($TO_INSTALL, $INSTALL_OBJ);
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
		$package_name = $xml_parser->getXMLValue("PhoronixTestSuite/Package/Name");
		$title = $xml_parser->getXMLValue("PhoronixTestSuite/Package/Title");
		$description = $xml_parser->getXMLValue("PhoronixTestSuite/Package/Description");
		$possible_packages = $xml_parser->getXMLValue("PhoronixTestSuite/Package/PossibleNames");

		$selection = -1;

		for($i = 0; $i < count($title); $i++)
			if($Name == $package_name[$i])
			{
				$selection = $i;
				break;
			}

		if($selection != -1)
			$generic_information = $title[$selection] . "\n" . $description[$selection] . "\n\nPossible Package Names: " . $possible_packages[$selection];
	}

	return $generic_information;
}
function pts_install_external_dependencies($Benchmark, &$INSTALL_OBJ)
{
	if(pts_benchmark_type($Benchmark) != "BENCHMARK")
		return;

	$xml_parser = new tandem_XmlReader(file_get_contents(XML_PROFILE_LOCATION . $Benchmark . ".xml"));
	$title = $xml_parser->getXMLValue("PTSBenchmark/Information/Title");
	$dependencies = $xml_parser->getXMLValue("PTSBenchmark/Information/ExternalDependencies");

	if(empty($dependencies))
		return;

	$dependencies = explode(", ", $dependencies);

	$dep_match_count = 0;
	$vendor = strtolower(os_vendor());

	if(is_file(MISC_LOCATION . "distro-xml/" . $vendor . "-packages.xml"))
	{
		$xml_parser = new tandem_XmlReader(file_get_contents(MISC_LOCATION . "distro-xml/" . $vendor . "-packages.xml"));
		$generic_package = $xml_parser->getXMLArrayValues("PhoronixTestSuite/ExternalDependencies/Package/GenericName");
		$distro_package = $xml_parser->getXMLArrayValues("PhoronixTestSuite/ExternalDependencies/Package/PackageName");

		for($i = 0; $i < count($generic_package); $i++)
			if(!empty($generic_package[$i]) && in_array($generic_package[$i], $dependencies))
				if(!in_array($distro_package[$i], $INSTALL_OBJ))
				{
					array_push($INSTALL_OBJ, $distro_package[$i]);
					$dep_match_count++;
				}
	}

	if($dep_match_count == 0)
	{
		echo "No packages found for your distribution (" . os_vendor() . ").";
	}
}

?>
