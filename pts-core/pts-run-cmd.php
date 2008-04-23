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
		if(is_file(SAVE_RESULTS_LOCATION . $ARG_1 . "/composite.xml"))
		{
			unlink(SAVE_RESULTS_LOCATION . $ARG_1 . "/composite.xml");

			$i = 1;
			while(is_file(SAVE_RESULTS_LOCATION . $ARG_1 . "/test-" . "$i.xml"))
			{
				unlink(SAVE_RESULTS_LOCATION . $ARG_1 . "/test-" . "$i.xml");
				$i++;
			}
			unlink(SAVE_RESULTS_LOCATION . $ARG_1 . "/pts-results-viewer.xsl");
			rmdir(SAVE_RESULTS_LOCATION . $ARG_1);
			echo "\nRemoved: $ARG_1\n";
		}
		else
			echo "\nThis result doesn't exist!\n";
		break;
	case "UPLOAD_RESULT":

		if(is_file($ARG_1))
			$USE_FILE = $ARG_1;
		else if(is_file(SAVE_RESULTS_LOCATION . $ARG_1 . "/composite.xml"))
			$USE_FILE = SAVE_RESULTS_LOCATION . $ARG_1 . "/composite.xml";
		else
		{
			echo "\nThis result doesn't exist!\n";
			exit(0);
		}

		$upload_url = pts_global_upload_result($USE_FILE);

		if(!empty($upload_url))
			echo "Results Uploaded To: " . $upload_url . "\n\n"; // TODO: Add checks to make sure it did work out
		break;
	case "LIST_SAVED_RESULTS":
		echo "\n=================================\n";
		echo "Phoronix Test Suite - Saved Results\n";
		echo "=================================\n\n";
		foreach(glob(SAVE_RESULTS_LOCATION . "*/composite.xml") as $benchmark_file)
		{
			$xml_parser = new tandem_XmlReader(file_get_contents($benchmark_file));
			$title = $xml_parser->getXMLValue("PhoronixTestSuite/Suite/Title");
			$suite = $xml_parser->getXMLValue("PhoronixTestSuite/Suite/Name");
			$raw_results = $xml_parser->getXMLArrayValues("PhoronixTestSuite/Benchmark/Results");
			$results_xml = new tandem_XmlReader($raw_results[0]);
			$identifiers = $results_xml->getXMLArrayValues("Group/Entry/Identifier");
			$saved_identifier = array_pop(explode('/', dirname($benchmark_file)));

			if(!empty($title))
			{
				echo $title . "\n";
				printf("Saved Name: %-15ls Test: %-18ls \n", $saved_identifier, $suite);

				foreach($identifiers as $id)
					echo "\t- $id\n";

				echo "\n\n";
			}
		}
		break;
	case "SHOW_RESULT":
		if(is_file(SAVE_RESULTS_LOCATION . $ARG_1 . "/composite.xml"))
			$URL = SAVE_RESULTS_LOCATION . $ARG_1 . "/composite.xml";
		//else if(trim(file_get_contents("http://www.phoronix-test-suite.com/global/profile-check.php?id=" . $ARG_1)) == "REMOTE_FILE")
		//	$URL = "http://global.phoronix-test-suite.com/index.php?k=profile&u=" . trim($ARG_1);
		else
			$URL = false;

		if($URL != FALSE)
			shell_exec("./pts/launch-browser.sh $URL &");
		else
			echo "\n$ARG_1 was not found.\n";
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
	case "INITIAL_CONFIG":
		if(is_file(PTS_USER_DIR . "user-config.xml"))
		{
			copy(PTS_USER_DIR . "user-config.xml", PTS_USER_DIR . "user-config.xml.old");
			unlink(PTS_USER_DIR . "user-config.xml");
		}
		pts_user_config_init();
		break;
	case "SYS_INFO":
		echo "\n=================================\n";
		echo "Phoronix Test Suite v" . PTS_VERSION . " (" . PTS_CODENAME . ")\n";
		echo "System Information";
		echo "\n=================================\n\n";
		echo "Hardware:\n" . pts_hw_string() . "\n\n";
		echo "Software:\n" . pts_sw_string() . "\n\n";
		break;
	case "DIAGNOSTICS_DUMP":
		echo "\n=================================\n";
		echo "Phoronix Test Suite v" . PTS_VERSION . " (" . PTS_CODENAME . ")\n";
		echo "Diagnostics Dump";
		echo "\n=================================\n\n";
		$pts_defined_constants = get_defined_constants(true);
			foreach($pts_defined_constants["user"] as $constant => $constant_value)
				echo $constant . " = " . $constant_value . "\n";

			echo "\nEnvironmental Variables (accessible via test scripts):\n";
			foreach(pts_env_variables() as $var => $var_value)
				echo $var . " = " . $var_value . "\n";
			echo "\n";
		break;
	case "LOGIN":
		echo "\nIf you haven't already registered for your free PTS Global account, you can do so at http://global.phoronix-test-suite.com/\n\nOnce you have registered your account and clicked the link within the verification email, enter your log-in information below.\n\n";
		echo "User-Name: ";
		$username = trim(strtolower(fgets(STDIN)));
		echo "Password: ";
		$password = md5(trim(strtolower(fgets(STDIN))));
		$uploadkey = @file_get_contents("http://www.phoronix-test-suite.com/global/account-verify.php?user_name=$username&user_md5_pass=$password");

		if(!empty($uploadkey))
		{
			pts_user_config_init($username, $uploadkey);
			echo "\nAccount: $uploadkey\nAccount information written to user-config.xml.\n\n";
		}
		else
			echo "\nPTS Global Account Not Found.\n";
		break;
	case "REMOTE_COMPARISON":
		echo "Now Use merge-results for remote comparison with integrated Global ID support.";
		echo "merge-results <Saved File 1 OR Global ID> <Saved File 2 OR Global ID> <Save To>: Merge two saved result sets";
		break;
	default:
		echo "Phoronix Test Suite: Internal Error. Command Not Recognized ($COMMAND).\n";
}

?>
