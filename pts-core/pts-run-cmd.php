<?php

require("pts-core/functions/pts-functions.php");

$COMMAND = $argv[1];

if(isset($argv[2]))
	$ARG_1 = $argv[2];

if(isset($argv[3]))
	$ARG_2 = $argv[3];

switch($COMMAND)
{
	case "REMOVE_RESULT":
		if(is_file(SAVE_RESULTS_LOCATION . "$ARG_1.xml"))
		{
			unlink(SAVE_RESULTS_LOCATION . "$ARG_1.xml");
			echo "\nRemoved: $ARG_1.xml\n";

			$i = 1;
			while(is_file(SAVE_RESULTS_LOCATION . "$ARG_1-$i.xml"))
			{
				unlink(SAVE_RESULTS_LOCATION . "$ARG_1-$i.xml");
				echo "Removed: $ARG_1-$i.xml\n";
				$i++;
			}
		}
		else
			echo "\nThis result doesn't exist!\n";
		break;
	case "UPLOAD_RESULT":

		if(is_file($ARG_1))
			$USE_FILE = $ARG_1;
		else if(is_file(SAVE_RESULTS_LOCATION . $ARG_1 . ".xml"))
			$USE_FILE = SAVE_RESULTS_LOCATION . $ARG_1 . ".xml";
		else
		{
			echo "\nThis result doesn't exist!\n";
			exit(0);
		}

		$upload_url = pts_global_upload_result($USE_FILE);

		if(!empty($upload_url))
			echo "Results Uploaded To: " . $upload_url . "\n\n"; // TODO: Add checks to make sure it did work out
		break;
	case "INSTALL_BENCHMARK":
		if(empty($ARG_1))
		{
			echo "\nThe benchmark or suite name to install must be supplied.\n";
			exit;
		}

		require("pts-core/functions/pts-functions-install.php");

		$ARG_1 = strtolower($ARG_1);

		// Any external dependencies?
		pts_install_package_on_distribution($ARG_1);

		if(defined("PTS_MANUAL_SUPPORT"))
		{
			pts_bool_question("These dependencies should be installed before proceeding as one or more benchmarks could fail. Press any key when you're ready to continue");
		}

		// Install benchmarks
		$install_objects = "";
		pts_recurse_install_benchmark($ARG_1, $install_objects);
		break;
	case "INSTALL_EXTERNAL_DEPENDENCIES":
		if(empty($ARG_1))
		{
			echo "\nThe benchmark or suite name to install external dependencies for must be supplied.\n";
			exit;
		}
		require("pts-core/functions/pts-functions-install.php");

		if($ARG_1 == "phoronix-test-suite" || $ARG_1 == "pts" || $ARG_1 == "trondheim-pts")
		{
			$pts_dependencies = array("php-gd", "php-extras", "build-utilities");
			$packages_to_install = array();
			$continue_install = pts_package_generic_to_distro_name($packages_to_install, $pts_dependencies);

			if($continue_install)
				pts_install_packages_on_distribution_process($packages_to_install);
		}
		else
			pts_install_package_on_distribution($ARG_1);
		break;
	case "LIST_TESTS":
		echo "\n=================================\n";
		echo "Phoronix Test Suite - Benchmarks\n";
		echo "=================================\n\n";
			foreach(glob(XML_PROFILE_LOCATION . "*.xml") as $benchmark_file)
			{
			 	$xml_parser = new tandem_XmlReader(file_get_contents($benchmark_file));
				$name = $xml_parser->getXMLValue("PTSBenchmark/Information/Title");
				$identifier = basename($benchmark_file, ".xml");
				$license = $xml_parser->getXMLValue("PTSBenchmark/PhoronixTestSuite/License");
				$status = $xml_parser->getXMLValue("PTSBenchmark/PhoronixTestSuite/Status");

				printf("%-18ls - %-30ls [Status: %s, License: %s]\n", $identifier, $name, $status, $license);
			}
		echo "\n";
		break;
	case "LIST_SUITES":
		echo "\n=================================\n";
		echo "Phoronix Test Suite - Suites\n";
		echo "=================================\n\n";
		$benchmark_suites = array();
		foreach(glob(XML_SUITE_LOCATION . "*.xml") as $benchmark_file)
		{
		 	$xml_parser = new tandem_XmlReader(file_get_contents($benchmark_file));
			$name = $xml_parser->getXMLValue("PTSuite/PhoronixTestSuite/Title");
			$benchmark_type = $xml_parser->getXMLValue("PTSuite/PhoronixTestSuite/BenchmarkType");
			$identifier = basename($benchmark_file, ".xml");

			printf("%-16ls - %-32ls [Benchmark Type: %s]\n", $identifier, $name, $benchmark_type);
		}
		echo "\n";
		break;
	case "LIST_SAVED_RESULTS":
		echo "\n=================================\n";
		echo "Phoronix Test Suite - Saved Results\n";
		echo "=================================\n\n";
		foreach(glob(SAVE_RESULTS_LOCATION . "*.xml") as $benchmark_file)
		{
			// TODO: Clean up this check...
			$bt = substr($benchmark_file, strrpos($benchmark_file, '-') + 1);
			$bt = intval(substr($bt, 0, strpos($bt, '.')));

			if($bt == 0)
			{
		 		$xml_parser = new tandem_XmlReader(file_get_contents($benchmark_file));
				$title = $xml_parser->getXMLValue("PhoronixTestSuite/Suite/Title");
				$suite = $xml_parser->getXMLValue("PhoronixTestSuite/Suite/Name");
				$raw_results = $xml_parser->getXMLArrayValues("PhoronixTestSuite/Benchmark/Results");
				$results_xml = new tandem_XmlReader($raw_results[0]);
				$identifiers = $results_xml->getXMLArrayValues("Group/Entry/Identifier");

				if(!empty($title))
				{
					echo "Saved Name: " . basename($benchmark_file, ".xml") . "\n";
					echo "$title (Test: $suite)\n";

					foreach($identifiers as $id)
						echo "\t- $id\n";

					echo "\n";
				}
			}
		}
		echo "\n";
		break;
	case "INITIAL_CONFIG":
		if(is_file(PTS_USER_DIR . "user-config.xml"))
		{
			copy(PTS_USER_DIR . "user-config.xml", PTS_USER_DIR . "user-config.xml.old");
			unlink(PTS_USER_DIR . "user-config.xml");
		}
		pts_user_config_init();
		break;
	case "REMOTE_COMPARISON":
		echo "Now Use merge-results for remote comparison with integrated Global ID support.";
		echo "merge-results <Saved File 1 OR Global ID> <Saved File 2 OR Global ID> <Save To>: Merge two saved result sets";
		break;
	default:
		echo "Phoronix Test Suite: Internal Error. Command Not Recognized ($COMMAND).\n";
}

?>
