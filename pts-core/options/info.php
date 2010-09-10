<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel

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

class info implements pts_option_interface
{
	public static function run($args)
	{
		$to_info = $args[0];
		echo "\n";

		if(pts_is_suite($to_info))
		{
			$suite = new pts_test_suite($to_info);
			pts_client::$display->generic_heading($suite->get_title());
			echo "Suite Version: " . $suite->get_version() . "\n";
			echo "Maintainer: " . $suite->get_maintainer() . "\n";
			echo "Suite Type: " . $suite->get_suite_type() . "\n";
			echo "Unique Tests: " . $suite->get_unique_test_count() . "\n";
			echo "Suite Description: " . $suite->get_description() . "\n";
			echo "\n";
			echo $suite->pts_format_contained_tests_string();
			echo "\n";
		}
		else if(pts_is_virtual_suite($to_info))
		{
			pts_client::$display->generic_heading($to_info . " Virtual Suite");

			switch(pts_location_virtual_suite($to_info))
			{
				case "TYPE_VIRT_SUITE_ALL":
					echo "This virtual suite contains all supported Phoronix Test Suite tests.\n";
					break;
				case "TYPE_VIRT_SUITE_FREE":
					echo "This virtual suite contains all supported Phoronix Test Suite tests that are considered free.\n";
					break;
				case "TYPE_VIRT_SUITE_SUBSYSTEM":
					echo "This virtual suite contains all supported Phoronix Test Suite tests for the " . $to_info . " subsystem.\n";
					break;
				case "TYPE_VIRT_SUITE_INSTALLED_TESTS":
					echo "This virtual suite contains all Phoronix Test Suite test suites that are currently installed on this system.\n";
					break;
			}

			echo "\nContained Tests:\n\n";
			echo pts_user_io::display_text_list(pts_virtual_suite_tests($to_info));
			echo "\n";
		}
		else if(pts_is_test($to_info))
		{
			$test = new pts_test_profile($to_info);
			$test_title = $test->get_title();
			$test_version = $test->get_version();
			if(!empty($test_version))
			{
				$test_title .= " " . $test_version;
			}

			pts_client::$display->generic_heading($test_title);

			echo "Profile Version: " . $test->get_test_profile_version() . "\n";
			echo "Maintainer: " . $test->get_maintainer() . "\n";
			echo "Test Type: " . $test->get_test_hardware_type() . "\n";
			echo "Software Type: " . $test->get_test_software_type() . "\n";
			echo "License Type: " . $test->get_license() . "\n";
			echo "Test Status: " . $test->get_status() . "\n";
			echo "Project Web-Site: " . $test->get_project_url() . "\n";

			$download_size = $test->get_download_size();
			if(!empty($download_size))
			{
				echo "Download Size: " . $download_size . " MB\n";
			}

			$environment_size = $test->get_environment_size();
			if(!empty($environment_size))
			{
				echo "Environment Size: " . $environment_size . " MB\n";
			}

			echo "\nDescription: " . $test->get_description() . "\n";

			if(pts_test_installed($to_info))
			{
				$installed_test = new pts_installed_test($to_info);
				$last_run = $installed_test->get_last_run_date();
				$last_run = $last_run == "0000-00-00" ? "Never" : $last_run;

				$avg_time = $installed_test->get_average_run_time();
				$avg_time = !empty($avg_time) ? pts_strings::format_time($avg_time, "SECONDS") : "N/A";
				$latest_time = $installed_test->get_latest_run_time();
				$latest_time = !empty($latest_time) ? pts_strings::format_time($latest_time, "SECONDS") : "N/A";

				echo "\nTest Installed: Yes\n";
				echo "Last Run: " . $last_run . "\n";

				if($last_run != "Never")
				{
					if($installed_test->get_run_count() > 1)
					{
						echo "Average Run-Time: " . $avg_time . "\n";
					}

					echo "Latest Run-Time: " . $latest_time . "\n";
					echo "Times Run: " . $installed_test->get_run_count() . "\n";
				}
			}
			else
			{
				echo "\nTest Installed: No\n";
			}

			$dependencies = $test->get_dependencies();
			if(!empty($dependencies) && !empty($dependencies[0]))
			{
				echo "\nSoftware Dependencies:\n";
				echo pts_user_io::display_text_list($test->get_dependency_names());
			}

			$associated_suites = $test->suites_containing_test();
			if(count($associated_suites) > 0)
			{
				asort($associated_suites);
				echo "\nSuites Using This Test:\n";
				echo pts_user_io::display_text_list($associated_suites);
			}
			echo "\n";
		}
		else if(pts_find_result_file($to_info) != false)
		{
			$result_file = new pts_result_file($to_info);

			echo "Title: " . $result_file->get_title() . "\nIdentifier: " . $to_info . "\n";
			echo "\nTest Result Identifiers:\n";
			echo pts_user_io::display_text_list($result_file->get_system_identifiers());

			if(count(($tests = $result_file->get_unique_test_titles())) > 1)
			{
				echo "\nContained Tests:\n";
				echo pts_user_io::display_text_list($tests);
			}
			echo "\n";
		}
		else
		{
			echo "\n" . $to_info . " is not recognized.\n";
		}
	}
}

?>
