<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts-run-cmd.php: The main code for supporting non-run options aside from the test execution itself.

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


require("pts-core/functions/pts-functions.php");
require("pts-core/functions/pts-functions-extra.php");

$COMMAND = $argv[1];

if(isset($argv[2]))
{
	$ARG_1 = $argv[2];
}
else
{
	$ARG_1 = "";
}

if(isset($argv[3]))
{
	$ARG_2 = $argv[3];
}
else
{
	$ARG_2 = "";
}

if(isset($argv[4]))
{
	$ARG_3 = $argv[4];
}
else
{
	$ARG_3 = "";
}

switch($COMMAND)
{
	case "LIST_SAVED_RESULTS":
		echo pts_string_header("Phoronix Test Suite - Saved Results");
		foreach(glob(SAVE_RESULTS_DIR . "*/composite.xml") as $saved_results_file)
		{
			$saved_identifier = array_pop(explode("/", dirname($saved_results_file)));
			$xml_parser = new tandem_XmlReader($saved_results_file);
			$title = $xml_parser->getXMLValue(P_RESULTS_SUITE_TITLE);
			$suite = $xml_parser->getXMLValue(P_RESULTS_SUITE_NAME);
			$raw_results = $xml_parser->getXMLArrayValues(P_RESULTS_RESULTS_GROUP);
			$results_xml = new tandem_XmlReader($raw_results[0]);
			$identifiers = $results_xml->getXMLArrayValues(S_RESULTS_RESULTS_GROUP_IDENTIFIER);

			if(!empty($title))
			{
				echo $title . "\n";
				printf("Saved Name: %-18ls Test: %-18ls \n", $saved_identifier, $suite);

				foreach($identifiers as $id)
				{
					echo "\t- " . $id . "\n";
				}

				echo "\n";
			}
		}
		break;
	case "FORCE_INSTALL_TEST":
	case "INSTALL_TEST":
		include_once("pts-core/functions/pts-functions-install.php");

		if(empty($ARG_1))
		{
			echo "\nThe test or suite name to install must be supplied.\n";
		}
		else
		{
			if(IS_SCTP_MODE)
			{
				$ARG_1 = basename($ARG_1);
			}

			if($COMMAND == "FORCE_INSTALL_TEST")
			{
				define("PTS_FORCE_INSTALL", 1);
			}

			$ARG_1 = strtolower($ARG_1);

			if(strpos($ARG_1, "pcqs") !== false && !is_file(XML_SUITE_LOCAL_DIR . "pcqs-license.txt"))
			{
				// Install the Phoronix Certification & Qualification Suite
				$agreement = wordwrap(file_get_contents("http://www.phoronix-test-suite.com/pcqs/pcqs-license.txt"), 65);

				if(strpos($agreement, "PCQS") == false)
				{
					pts_exit("An error occurred while connecting to the Phoronix Test Suite Server. Please try again later.");
				}

				echo "\n\n" . $agreement;
				$agree = pts_bool_question("Do you agree to these terms in full and wish to proceed (y/n)?", false);

				if($agree)
				{
					pts_download("http://www.phoronix-test-suite.com/pcqs/download-pcqs.php", XML_SUITE_LOCAL_DIR . "pcqs-suite.tar");
					shell_exec("cd " . XML_SUITE_LOCAL_DIR . " && tar -xf pcqs-suite.tar && rm -f pcqs-suite.tar");
					echo pts_string_header("The Phoronix Certification & Qualification Suite is now installed.");
				}
				else
				{
					pts_exit(pts_string_header("In order to run PCQS you must agree to the listed terms."));
				}
			}

			// Any external dependencies?
			echo "\n";
			pts_install_package_on_distribution($ARG_1);

			// Install tests
			pts_start_install($ARG_1);

			if(getenv("SILENT_INSTALL") !== false)
			{
				define("PTS_EXIT", 1);
			}
		}
		break;
	case "FORCE_INSTALL_ALL":
	case "INSTALL_ALL":
		include_once("pts-core/functions/pts-functions-install.php");

		if($COMMAND == "FORCE_INSTALL_ALL")
		{
			define("PTS_FORCE_INSTALL", 1);
		}

		pts_module_process("__pre_install_process");
		foreach(pts_available_tests_array() as $test)
		{
			// Any external dependencies?
			pts_install_package_on_distribution($test);

			// Install tests
			pts_start_install($test);
		}
		pts_module_process("__post_install_process");
		break;
	case "INSTALL_EXTERNAL_DEPENDENCIES":
		include_once("pts-core/functions/pts-functions-install.php");

		if(empty($ARG_1))
		{
			echo "\nThe test or suite name to install external dependencies for must be supplied.\n";
		}
		else
		{
			if($ARG_1 == "phoronix-test-suite" || $ARG_1 == "pts" || $ARG_1 == "trondheim-pts")
			{
				$pts_dependencies = array("php-gd", "php-extras", "build-utilities");
				$packages_to_install = array();
				$continue_install = pts_package_generic_to_distro_name($packages_to_install, $pts_dependencies);

				if($continue_install)
				{
					pts_install_packages_on_distribution_process($packages_to_install);
				}
			}
			else
			{
				pts_install_package_on_distribution($ARG_1);
			}
		}
		break;
	case "MAKE_DOWNLOAD_CACHE":
		echo pts_string_header("Phoronix Test Suite - Generating Download Cache");
		pts_generate_download_cache();
		echo "\n";
		break;
	case "LIST_TESTS":
	case "LIST_ALL_TESTS":
		echo pts_string_header("Phoronix Test Suite - Tests");
		foreach(pts_available_tests_array() as $identifier)
		{
		 	$xml_parser = new pts_test_tandem_XmlReader(pts_location_test($identifier));
			$name = $xml_parser->getXMLValue(P_TEST_TITLE);
			$license = $xml_parser->getXMLValue(P_TEST_LICENSE);
			$status = $xml_parser->getXMLValue(P_TEST_STATUS);

			if(IS_DEBUG_MODE)
			{
				$test_version = $xml_parser->getXMLValue(P_TEST_VERSION);
				$version = $xml_parser->getXMLValue(P_TEST_PTSVERSION);
				$test_download_size = pts_estimated_download_size($identifier);
				$test_environment_size = pts_test_estimated_environment_size($identifier);
				$test_maintainer = $xml_parser->getXMLValue(P_TEST_MAINTAINER);

				printf("%-18ls %-6ls %-6ls %-12ls %-12ls %-4ls %-4ls %-22ls\n", $identifier, $test_version, $version, $status, $license, $test_download_size, $test_environment_size, $test_maintainer);
			}
			else
			{
				if(!empty($name) && ($COMMAND == "LIST_ALL_TESTS" || !in_array($status, array("PRIVATE", "BROKEN", "EXPERIMENTAL", "UNVERIFIED", "STANDALONE", "SCTP"))))
				{
					if(pts_test_supported($identifier))
					{
						printf("%-18ls - %-36ls [%s, %10ls]\n", $identifier, $name, $status, $license);
					}
				}
			}
		}
		echo "\n";
		break;
	case "LIST_SUITES":
		echo pts_string_header("Phoronix Test Suite - Suites");
		$has_partially_supported_suite = false;
		foreach(pts_available_suites_array() as $identifier)
		{
		 	$xml_parser = new tandem_XmlReader(pts_location_suite($identifier));
			$name = $xml_parser->getXMLValue(P_SUITE_TITLE);
			$test_type = $xml_parser->getXMLValue(P_SUITE_TYPE);

			if(IS_DEBUG_MODE)
			{
				$version = $xml_parser->getXMLValue(P_SUITE_VERSION);
				$type = $xml_parser->getXMLValue(P_SUITE_TYPE);

				printf("%-26ls - %-32ls %-4ls  %-12ls\n", $identifier, $name, $version, $type);
			}
			else
			{
				$suite_support_code = pts_suite_supported($identifier);

				if($suite_support_code > 0)
				{
					$identifier_prefix = " ";
					if($suite_support_code == 1)
					{
						$identifier_prefix = "*";

						if(!$has_partially_supported_suite)
						{
							$has_partially_supported_suite = true;
						}
					}

					printf("%-24ls - %-32ls [Type: %s]\n", $identifier_prefix . " " . $identifier, $name, $test_type);
				}
			}
		}
		echo "\n";
		if($has_partially_supported_suite)
		{
			echo "* Indicates a partially supported suite.\n\n";
		}
		break;
	case "LIST_MODULES":
		echo pts_string_header("Phoronix Test Suite - Modules");
		foreach(glob(MODULE_DIR . "*.php") as $module_file)
		{
		 	$module = basename($module_file, ".php");

			if(!in_array($module, pts_attached_modules()))
			{
				include_once($module_file);
			}

			$module_name = pts_php_module_call($module, "module_name");
			$module_version = pts_php_module_call($module, "module_version");
			$module_author = pts_php_module_call($module, "module_author");

			printf("%-22ls - %-30ls [%s]\n", $module, $module_name . " v" . $module_version, $module_author);
		}
		foreach(glob(MODULE_DIR . "*.sh") as $module_file)
		{
		 	$module = basename($module_file, ".sh");
			$module_name = pts_sh_module_call($module, "module_name");
			$module_version = pts_sh_module_call($module, "module_version");
			$module_author = pts_sh_module_call($module, "module_author");

			printf("%-22ls - %-30ls [%s]\n", $module, $module_name . " v" . $module_version, $module_author);
		}
		echo "\n";
		break;
	case "LIST_INSTALLED_TESTS":
		echo pts_string_header("Phoronix Test Suite - Installed Tests");
		foreach(pts_installed_tests_array() as $identifier)
		{
			if(is_test($identifier))
			{
			 	$xml_parser = new pts_test_tandem_XmlReader(pts_location_test($identifier));
				$name = $xml_parser->getXMLValue(P_TEST_TITLE);

				if(!empty($name))
				{
					printf("%-18ls - %-30ls\n", $identifier, $name);
				}
			}
		}
		echo "\n";
		break;
	case "LIST_TEST_USAGE":
		echo pts_string_header("Phoronix Test Suite - Test Usage");
		printf("%-18ls   %-8ls %-13ls %-11ls %-13ls %-10ls\n", "TEST", "VERSION", "INSTALL DATE", "LAST RUN", "AVG RUN-TIME", "TIMES RUN");
		foreach(pts_installed_tests_array() as $identifier)
		{
			$xml_parser = new tandem_XmlReader(TEST_ENV_DIR . $identifier . "/pts-install.xml");
			$test_time_install = substr($xml_parser->getXMLValue(P_INSTALL_TEST_INSTALLTIME), 0, 10);
			$test_time_lastrun = substr($xml_parser->getXMLValue(P_INSTALL_TEST_LASTRUNTIME), 0, 10);
			$test_version = $xml_parser->getXMLValue(P_INSTALL_TEST_VERSION);
			$test_avg_runtime = pts_format_time_string($xml_parser->getXMLValue(P_INSTALL_TEST_AVG_RUNTIME), "SECONDS", false);
			$test_times_run = $xml_parser->getXMLValue(P_INSTALL_TEST_TIMESRUN);

			if($test_time_lastrun == "0000-00-00" || $xml_parser->getXMLValue(P_INSTALL_TEST_INSTALLTIME) == $xml_parser->getXMLValue(P_INSTALL_TEST_LASTRUNTIME))
			{
				$test_time_lastrun = "NEVER";
				$test_times_run = "";
			}

			$xml_parser = new pts_test_tandem_XmlReader(pts_location_test($identifier));
			$name = $xml_parser->getXMLValue(P_TEST_TITLE);

			if(empty($test_times_run))
			{
				$test_times_run = 0;
			}

			if(empty($test_avg_runtime))
			{
				$test_avg_runtime = "N/A";
			}

			if(!empty($name))
			{
				printf("%-18ls - %-8ls %-13ls %-11ls %-13ls %-10ls\n", $identifier, $test_version, $test_time_install, $test_time_lastrun, $test_avg_runtime, $test_times_run);
			}
		}
		echo "\n";
		break;
	case "LIST_POSSIBLE_EXTERNAL_DEPENDENCIES":
		echo pts_string_header("Phoronix Test Suite - Possible External Dependencies");
		$xml_parser = new tandem_XmlReader(XML_DISTRO_DIR . "generic-packages.xml");
		$dependency_titles = $xml_parser->getXMLArrayValues(P_EXDEP_PACKAGE_TITLE);
		sort($dependency_titles);

		foreach($dependency_titles as $title)
		{
			echo "- " . $title . "\n";
		}
		echo "\n";

		break;
	case "INFO":
		if(is_suite($ARG_1))
		{
			$xml_parser = new tandem_XmlReader(pts_location_suite($ARG_1));
			$suite_name = $xml_parser->getXMLValue(P_SUITE_TITLE);
			$suite_maintainer = $xml_parser->getXMLValue(P_SUITE_MAINTAINER);
			$suite_version = $xml_parser->getXMLValue(P_SUITE_VERSION);
			$suite_description = $xml_parser->getXMLValue(P_SUITE_DESCRIPTION);
			$suite_type = $xml_parser->getXMLValue(P_SUITE_TYPE);
			$unique_tests = count(pts_contained_tests($ARG_1));

			echo pts_string_header($suite_name);

			$suite_maintainer = explode("|", $suite_maintainer);
			if(count($suite_maintainer) == 2)
			{
				$suite_maintainer = trim($suite_maintainer[0]) . " <" . trim($suite_maintainer[1]) . ">";
			}
			else
			{
				$suite_maintainer = $suite_maintainer[0];
			}

			echo "Suite Version: " . $suite_version . "\n";
			echo "Maintainer: " . $suite_maintainer . "\n";
			echo "Suite Type: " . $suite_type . "\n";
			echo "Unique Tests: " . $unique_tests . "\n";
			echo "Suite Description: " . $suite_description . "\n";
			echo "\n";

			echo pts_print_format_tests($ARG_1);
		
			echo "\n";
		}
		else if(is_test($ARG_1))
		{
			$xml_parser = new pts_test_tandem_XmlReader(pts_location_test($ARG_1));
			$test_title = $xml_parser->getXMLValue(P_TEST_TITLE);
			$test_sw_version = $xml_parser->getXMLValue(P_TEST_VERSION);
			$test_version = $xml_parser->getXMLValue(P_TEST_PTSVERSION);
			$test_description = $xml_parser->getXMLValue(P_TEST_DESCRIPTION);
			$test_type = $xml_parser->getXMLValue(P_TEST_HARDWARE_TYPE);
			$test_app_type = $xml_parser->getXMLValue(P_TEST_SOFTWARE_TYPE);
			$test_license = $xml_parser->getXMLValue(P_TEST_LICENSE);
			$test_status = $xml_parser->getXMLValue(P_TEST_STATUS);
			$test_maintainer = $xml_parser->getXMLValue(P_TEST_MAINTAINER);
			$test_estimated_length = $xml_parser->getXMLValue(P_TEST_ESTIMATEDTIME);
			$test_dependencies = $xml_parser->getXMLValue(P_TEST_EXDEP);
			$test_projecturl = $xml_parser->getXMLValue(P_TEST_PROJECTURL);

			$test_download_size = pts_estimated_download_size($ARG_1);
			$test_environment_size = pts_test_estimated_environment_size($ARG_1);

			if(empty($test_title))
			{
				echo $ARG_1 . " is not a Phoronix Test Suite test.";
			}
			else
			{
				if(!empty($test_sw_version))
				{
					$test_title .= " " . $test_sw_version;
				}

				echo pts_string_header($test_title);

				$test_maintainer = explode("|", $test_maintainer);
				if(count($test_maintainer) == 2)
				{
					$test_maintainer = trim($test_maintainer[0]) . " <" . trim($test_maintainer[1]) . ">";
				}
				else
				{
					$test_maintainer = $test_maintainer[0];
				}

				echo "Test Version: " . $test_version . "\n";
				echo "Maintainer: " . $test_maintainer . "\n";
				echo "Test Type: " . $test_type . "\n";
				echo "Software Type: " . $test_app_type . "\n";
				echo "License Type: " . $test_license . "\n";
				echo "Test Status: " . $test_status . "\n";
				echo "Project Web-Site: " . $test_projecturl . "\n";

				if(!empty($test_download_size))
				{
					echo "Download Size: " . $test_download_size . " MB\n";
				}
				if(!empty($test_environment_size))
				{
					echo "Environment Size: " . $test_environment_size . " MB\n";
				}
				if(!empty($test_estimated_length))
				{
					echo "Estimated Length: " . pts_estimated_time_string($test_estimated_length) . "\n";
				}

				echo "\nDescription: " . $test_description . "\n";

				if(is_file(TEST_ENV_DIR . $ARG_1 . "/pts-install.xml"))
				{
					$xml_parser = new tandem_XmlReader(TEST_ENV_DIR . $ARG_1 . "/pts-install.xml", false);
					$last_run = $xml_parser->getXMLValue(P_INSTALL_TEST_LASTRUNTIME);
					$avg_time = $xml_parser->getXMLValue(P_INSTALL_TEST_AVG_RUNTIME);

					if($last_run == "0000-00-00 00:00:00")
					{
						$last_run = "Never";
					}

					echo "\nTest Installed: Yes\n";
					echo "Last Run: " . $last_run . "\n";

					if($avg_time > 0)
					{
						echo "Average Run-Time: " . $avg_time . " Seconds\n";
					}
					if($last_run != "Never")
					{
						echo "Times Run: " . $xml_parser->getXMLValue(P_INSTALL_TEST_TIMESRUN) . "\n";
					}
				}
				else
				{
					echo "\nTest Installed: No\n";
				}

				if(!empty($test_dependencies))
				{
					echo "\nSoftware Dependencies:\n";
					foreach(explode(',', $test_dependencies) as $dependency)
					{
						if(($title = pts_dependency_name(trim($dependency)) )!= "")
						{
							echo "- " . $title . "\n";
						}
					}
				}

				$associated_suites = array();
				foreach(pts_available_suites_array() as $identifier)
				{
				 	$xml_parser = new tandem_XmlReader(pts_location_suite($identifier));
					$name = $xml_parser->getXMLValue(P_SUITE_TITLE);
					$tests = pts_contained_tests($identifier);

					if(in_array($ARG_1, $tests))
					{
						array_push($associated_suites, $identifier);
					}
				}

				if(count($associated_suites) > 0)
				{
					asort($associated_suites);
					echo "\nSuites Using This Test:\n";
					foreach($associated_suites as $suite)
					{
						echo "- " . $suite . "\n";
					}
				}

				echo "\n";
			}
		}
		else
		{
			echo "\n" . $ARG_1 . " is not recognized.\n";
		}
		break;
	case "MODULE_INFO":
		$ARG_1 = strtolower($ARG_1);
		if(is_file(MODULE_DIR . $ARG_1 . ".php") || is_file(MODULE_DIR . $ARG_1 . ".sh"))
		{
		 	$module = $ARG_1;
			$pre_message = "";

			if(is_file(MODULE_DIR . $module . ".php"))
			{
				if(!in_array($module, pts_attached_modules()) && !class_exists($module))
				{
					include_once(MODULE_DIR . $module . ".php");
				}
			}

			if(in_array($module, pts_attached_modules()))
			{
				$pre_message = "** This module is currently loaded. **\n";
			}

			$module_name = pts_module_call($module, "module_name");
			$module_version = pts_module_call($module, "module_version");
			$module_author = pts_module_call($module, "module_author");
			$module_description = pts_module_call($module, "module_description");
			$module_information = pts_module_call($module, "module_info");

			echo pts_string_header("Module: " . $module_name);
			echo $pre_message;
			echo "Version: " . $module_version . "\n";
			echo "Author: " . $module_author . "\n";
			echo "Description: " . $module_description . "\n";

			if(!empty($module_information))
			{
				echo "\n" . $module_information . "\n";
			}

			echo "\n";
		}
		else
		{
			echo "\n" . $ARG_1 . " is not recognized.\n";
		}
		break;
	case "MODULE_SETUP":
		$ARG_1 = strtolower($ARG_1);
		if(is_file(MODULE_DIR . $ARG_1 . ".php"))
		{
		 	$module = $ARG_1;
			$pre_message = "";

			if(!in_array($module, pts_attached_modules()) && !class_exists($module))
			{
				include(MODULE_DIR . $module . ".php");
			}

			$module_name = pts_php_module_call($module, "module_name");
			$module_description = pts_php_module_call($module, "module_description");
			$module_setup = pts_php_module_call($module, "module_setup");

			echo pts_string_header("Module: " . $module_name);
			echo $module_description . "\n";

			if(count($module_setup) == 0)
			{
				echo "\nThere are no options available for configuring with the " . $ARG_1 . " module.";
			}
			else
			{
				$set_options = array();
				foreach($module_setup as $module_option)
				{
					do
					{
						echo "\n" . $module_option->get_formatted_question();
						$input = trim(fgets(STDIN));
					}
					while(!$module_option->is_supported_value($input));

					if(empty($input))
					{
						$input = $module_option->get_default_value();
					}

					$this_input_identifier = $module_option->get_identifier();

					$set_options[$ARG_1 . "__" . $this_input_identifier] = $input;
				}
				pts_module_config_init($set_options);
			}

			echo "\n";
		}
		else
		{
			echo "\n" . $ARG_1 . " is not recognized.\n";
		}
		break;
	case "SHOW_RESULT":
		$URL =  pts_find_result_file($ARG_1);

		if($URL != false)
		{
			shell_exec("sh pts-core/scripts/launch-browser.sh $URL 2>&1");
		}
		else
		{
			echo "\n$ARG_1 was not found.\n";
		}
		break;
	case "REFRESH_GRAPHS":
		if(is_file(SAVE_RESULTS_DIR . $ARG_1 . "/composite.xml"))
		{
			$composite_xml = file_get_contents(SAVE_RESULTS_DIR . $ARG_1 . "/composite.xml");

			if(pts_save_result($ARG_1 . "/composite.xml", $composite_xml))
			{
				echo "\nThe Phoronix Test Suite Graphs Have Been Re-Rendered.\n";
				display_web_browser(SAVE_RESULTS_DIR . $ARG_1 . "/composite.xml");
			}
		}
		else
		{
			echo pts_string_header($ARG_1 . " was not found.");
		}
		break;
	case "UPLOAD_RESULT":
		include_once("pts-core/functions/pts-functions-run.php");

		$USE_FILE = pts_find_result_file($ARG_1, false);

		if($USE_FILE == false)
		{
			echo "\nThis result doesn't exist!\n";
			exit(0);
		}

		$tags_input = pts_promt_user_tags();
		echo "\n";

		$upload_url = pts_global_upload_result($USE_FILE, $tags_input);

		if(!empty($upload_url))
		{
			echo "Results Uploaded To: " . $upload_url . "\n\n";
			pts_module_process("__event_global_upload", $upload_url);
		}
		else
		{
			echo "\nResults Failed To Upload.\n";
		}
		break;
	case "REMOVE_ALL_RESULTS":
		$remove_all = pts_bool_question("Are you sure you wish to remove all saved results (Y/n)?", true);

		if($remove_all)
		{
			foreach(glob(SAVE_RESULTS_DIR . "*/composite.xml") as $saved_results_file)
			{
				$saved_identifier = basename($saved_results_file, ".xml");
				pts_remove_saved_result($saved_identifier);
			}
			echo "\n";
		}
		break;
	case "REMOVE_RESULT":
		if(is_file(SAVE_RESULTS_DIR . $ARG_1 . "/composite.xml"))
		{
			echo "\n";
			pts_remove_saved_result($ARG_1);
		}
		else
		{
			echo "\nThis result doesn't exist!\n";
		}
		break;
	case "REMOVE_INSTALLED_TEST":
		if(is_file(TEST_ENV_DIR . $ARG_1 . "/pts-install.xml"))
		{
			if(pts_bool_question("Are you sure you wish to remove the test " . $ARG_1 . " (y/N)?", false))
			{
				pts_remove(TEST_ENV_DIR . $ARG_1);
				echo "\nThe " . $ARG_1 . " test has been removed.\n\n";
			}
			else
			{
				echo "\n";
			}
		}
		else
		{
			echo "\n" . $ARG_1 . " is not installed.\n\n";
		}
		break;
	case "SYS_INFO":
		echo pts_string_header("Phoronix Test Suite v" . PTS_VERSION . " (" . PTS_CODENAME . ")\nSystem Information");
		echo "Hardware:\n" . pts_hw_string() . "\n\n";
		echo "Software:\n" . pts_sw_string() . "\n\n";
		break;
	case "MERGE_RESULTS":
		include_once("pts-core/functions/pts-functions-merge.php");

		$BASE_FILE = $ARG_1;
		$MERGE_FROM_FILE = $ARG_2;
		$MERGE_TO = $ARG_3;

		if(empty($BASE_FILE) || empty($MERGE_FROM_FILE))
		{
			echo "\nTwo saved result profile names must be supplied.\n";
		}
		else
		{
			$BASE_FILE = pts_find_result_file($BASE_FILE);
			$MERGE_FROM_FILE = pts_find_result_file($MERGE_FROM_FILE);

			if($BASE_FILE == false || $MERGE_FROM_FILE == false)
			{
				echo "\n" . $BASE_FILE . " or " . $MERGE_FROM_FILE . " couldn't be found.\n";
			}
			else
			{
				if(!empty($MERGE_TO) && !is_dir(SAVE_RESULTS_DIR . $MERGE_TO))
				{
					$MERGE_TO .= "/composite.xml";
				}
				else
				{
					$MERGE_TO = null;
				}

				if(empty($MERGE_TO))
				{
					do
					{
						$rand_file = rand(1000, 9999);
						$MERGE_TO = "merge-" . $rand_file . '/';
					}
					while(is_dir(SAVE_RESULTS_DIR . $MERGE_TO));

					$MERGE_TO .= "composite.xml";
				}

				// Merge Results
				$MERGED_RESULTS = pts_merge_test_results(file_get_contents($BASE_FILE), file_get_contents($MERGE_FROM_FILE));
				pts_save_result($MERGE_TO, $MERGED_RESULTS);
				echo "Merged Results Saved To: " . SAVE_RESULTS_DIR . $MERGE_TO . "\n\n";
				display_web_browser(SAVE_RESULTS_DIR . $MERGE_TO);
			}
		}
		break;
	case "ANALYZE_RESULTS":
		include_once("pts-core/functions/pts-functions-merge.php");

		$BASE_FILE = pts_find_result_file($ARG_1);
		$SAVE_TO = $ARG_2;

		if($BASE_FILE == false)
		{
			echo "\n" . $BASE_FILE . " couldn't be found.\n";
		}
		else
		{
			if(!empty($SAVE_TO) && !is_dir(SAVE_RESULTS_DIR . $SAVE_TO))
			{
				$SAVE_TO .= "/composite.xml";
			}
			else
			{
				$SAVE_TO = null;
			}

			if(empty($SAVE_TO))
			{
				do
				{
					$rand_file = rand(1000, 9999);
					$SAVE_TO = "analyze-" . $rand_file . '/';
				}
				while(is_dir(SAVE_RESULTS_DIR . $SAVE_TO));

				$SAVE_TO .= "composite.xml";
			}

			// Analyze Results
			$SAVED_RESULTS = pts_merge_batch_tests_to_line_comparison(@file_get_contents($BASE_FILE));
			pts_save_result($SAVE_TO, $SAVED_RESULTS);
			echo "Results Saved To: " . SAVE_RESULTS_DIR . $SAVE_TO . "\n\n";
			display_web_browser(SAVE_RESULTS_DIR . $SAVE_TO);
		}
		break;
	case "TEST_MODULE":
		$module = strtolower($ARG_1);
		if(is_file(MODULE_DIR . $module . ".php") || is_file(MODULE_DIR . $module . ".sh"))
		{
			pts_load_module($module);
			pts_attach_module($module);

			echo pts_string_header("Starting Module Test Process");

			$module_processes = pts_module_processes();

			foreach($module_processes as $process)
			{
				if(IS_DEBUG_MODE)
				{
					echo "Calling: " . $process . "()\n";
				}

				pts_module_process($process);
				sleep(1);
			}
			echo "\n";
		}
		else
		{
			echo "\n" . $module . " is not recognized.\n";
		}
		break;
	case "DIAGNOSTICS_DUMP":
		echo pts_string_header("Phoronix Test Suite v" . PTS_VERSION . " (" . PTS_CODENAME . ")\n" . "Diagnostics Dump");
		$pts_defined_constants = get_defined_constants(true);
			foreach($pts_defined_constants["user"] as $constant => $constant_value)
			{
				if(substr($constant, 0, 2) != "P_" && substr($constant, 0, 3) != "IS_")
				{
					echo $constant . " = " . $constant_value . "\n";
				}
			}

			echo "\nEnd-User Run-Time Variables:\n";
			foreach(pts_user_runtime_variables() as $var => $var_value)
			{
				echo $var . " = " . $var_value . "\n";
			}
			echo "\nEnvironmental Variables (accessible via test scripts):\n";
			foreach(pts_env_variables() as $var => $var_value)
			{
				echo $var . " = " . $var_value . "\n";
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
	case "LOGIN":
		echo "\nIf you haven't already registered for your free Phoronix Global account, you can do so at http://global.phoronix-test-suite.com/\n\nOnce you have registered your account and clicked the link within the verification email, enter your log-in information below.\n\n";
		echo "User-Name: ";
		$username = trim(fgets(STDIN));
		echo "Password: ";
		$password = md5(trim(fgets(STDIN)));
		$uploadkey = @file_get_contents("http://www.phoronix-test-suite.com/global/account-verify.php?user_name=" . $username . "&user_md5_pass=" . $password);

		if(!empty($uploadkey))
		{
			pts_user_config_init($username, $uploadkey);
			echo "\nAccount: " . $uploadkey . "\nAccount information written to user-config.xml.\n\n";
		}
		else
		{
			echo "\nPhoronix Global Account Not Found.\n";
		}
		break;
	case "BATCH_SETUP":
		echo "\nThese are the default configuration options for when running the Phoronix Test Suite in a batch mode (i.e. running phoronix-test-suite batch-benchmark universe). Running in a batch mode is designed to be as autonomous as possible, except for where you'd like any end-user interaction.\n\n";
		$batch_options = array();
		$batch_options[0] = pts_bool_question("Save test results when in batch mode (Y/n)?", true);

		if($batch_options[0] == true)
		{
			$batch_options[1] = pts_bool_question("Open the web browser automatically when in batch mode (y/N)?", false);
			$batch_options[2] = pts_bool_question("Auto upload the results to Phoronix Global (Y/n)?", true);
			$batch_options[3] = pts_bool_question("Prompt for test identifier (Y/n)?", true);
			$batch_options[4] = pts_bool_question("Prompt for test description (Y/n)?", true);
			$batch_options[5] = pts_bool_question("Prompt for saved results file-name (Y/n)?", true);
		}
		else
		{
			$batch_options[1] = false;
			$batch_options[2] = false;
			$batch_options[3] = false;
			$batch_options[4] = false;
			$batch_options[5] = false;
		}

		pts_user_config_init(null, null, $batch_options);
		echo "\nBatch settings saved.\n\n";
		break;
	case "CLONE":
		if(is_file(SAVE_RESULTS_DIR . $ARG_1 . "/composite.xml"))
		{
			echo "A saved result already exists with the same name.\n\n";
		}
		else
		{
			if(pts_is_global_id($ARG_1))
			{
				pts_save_result($ARG_1 . "/composite.xml", pts_global_download_xml($ARG_1));
				// TODO: re-render the XML file and generate the graphs through that save
				echo "Result Saved To: " . SAVE_RESULTS_DIR . $ARG_1 . "/composite.xml\n\n";
				//display_web_browser(SAVE_RESULTS_DIR . $ARG_1 . "/composite.xml");
			}
			else
			{
				echo $ARG_1 . " is an unrecognized Phoronix Global ID.\n\n";
			}
		}
		break;
	case "VERSION":
		echo "\nPhoronix Test Suite v" . PTS_VERSION . " (" . PTS_CODENAME . ")\n\n";
		break;
	default:
		echo "Phoronix Test Suite: Internal Error.\nCommand Not Recognized (" . $COMMAND . ").\n";
}

?>
