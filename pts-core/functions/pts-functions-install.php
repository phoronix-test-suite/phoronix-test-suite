<?php

function pts_recurse_install_benchmark($TO_INSTALL)
{
	$type = pts_benchmark_type($TO_INSTALL);

	if($type == "BENCHMARK")
	{
		pts_install_benchmark($TO_INSTALL);
	}
	else if($type == "TEST_SUITE")
	{
		echo "\nInstalling Benchmarks For " . ucwords($TO_INSTALL) . " Test Suite...\n\n";

		$xml_parser = new tandem_XmlReader(file_get_contents(XML_SUITE_LOCATION . "$TO_INSTALL.xml"));
		$suite_benchmarks = $xml_parser->getXMLArrayValues("PTSuite/PTSBenchmark/Benchmark");

		foreach($suite_benchmarks as $benchmark)
			pts_recurse_install_benchmark($benchmark);
	}
	else if(is_file(pts_input_correct_results_path($TO_INSTALL)))
	{
		$xml_parser = new tandem_XmlReader(file_get_contents(pts_input_correct_results_path($TO_INSTALL)));
		$suite_benchmarks = $xml_parser->getXMLArrayValues("PhoronixTestSuite/Benchmark/TestName");

		foreach($suite_benchmarks as $benchmark)
		{
			pts_recurse_install_benchmark($benchmark);
		}
	}
	else if(trim(file_get_contents("http://www.phoronix-test-suite.com/global/profile-check.php?id=$TO_INSTALL")) == "REMOTE_FILE")
	{
		$xml_parser = new tandem_XmlReader(file_get_contents("http://www.phoronix-test-suite.com/global/pts-results-viewer.php?id=$TO_INSTALL"));
		$suite_benchmarks = $xml_parser->getXMLArrayValues("PhoronixTestSuite/Benchmark/TestName");

		foreach($suite_benchmarks as $benchmark)
		{
			pts_recurse_install_benchmark($benchmark);
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

?>
