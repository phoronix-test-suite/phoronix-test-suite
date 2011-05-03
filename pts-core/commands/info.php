<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2011, Phoronix Media
	Copyright (C) 2008 - 2011, Michael Larabel

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
	const doc_section = 'Information';
	const doc_description = 'This option will show details about the supplied test, suite, virtual suite, or result file.';

	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array("pts_types", "identifier_to_object"), "object")
		);
	}
	public static function run($args)
	{
		echo "\n";

		if($args["object"] instanceof pts_test_suite)
		{
			pts_client::$display->generic_heading($args["object"]->get_title());
			echo "Run Identifier: " . $args["object"]->get_identifier() . "\n";
			echo "Suite Version: " . $args["object"]->get_version() . "\n";
			echo "Maintainer: " . $args["object"]->get_maintainer() . "\n";
			echo "Suite Type: " . $args["object"]->get_suite_type() . "\n";
			echo "Unique Tests: " . $args["object"]->get_unique_test_count() . "\n";
			echo "Suite Description: " . $args["object"]->get_description() . "\n";
			echo "\n";
			echo $args["object"]->pts_format_contained_tests_string();
			echo "\n";
		}
		else if($args["object"] instanceof pts_test_profile)
		{
			$test_title = $args["object"]->get_title();
			$test_version = $args["object"]->get_app_version();
			if(!empty($test_version))
			{
				$test_title .= " " . $test_version;
			}

			pts_client::$display->generic_heading($test_title);
			echo "Run Identifier: " . $args["object"]->get_identifier() . "\n";
			echo "Profile Version: " . $args["object"]->get_test_profile_version() . "\n";
			echo "Maintainer: " . $args["object"]->get_maintainer() . "\n";
			echo "Test Type: " . $args["object"]->get_test_hardware_type() . "\n";
			echo "Software Type: " . $args["object"]->get_test_software_type() . "\n";
			echo "License Type: " . $args["object"]->get_license() . "\n";
			echo "Test Status: " . $args["object"]->get_status() . "\n";
			echo "Project Web-Site: " . $args["object"]->get_project_url() . "\n";

			$download_size = $args["object"]->get_download_size();
			if(!empty($download_size))
			{
				echo "Download Size: " . $download_size . " MB\n";
			}

			$environment_size = $args["object"]->get_environment_size();
			if(!empty($environment_size))
			{
				echo "Environment Size: " . $environment_size . " MB\n";
			}

			echo "\nDescription: " . $args["object"]->get_description() . "\n";

			if($args['object']->test_installation != false)
			{
				$last_run = $args['object']->test_installation->get_last_run_date();
				$last_run = $last_run == "0000-00-00" ? "Never" : $last_run;

				$avg_time = $args['object']->test_installation->get_average_run_time();
				$avg_time = !empty($avg_time) ? pts_strings::format_time($avg_time, "SECONDS") : "N/A";
				$latest_time = $args['object']->test_installation->get_latest_run_time();
				$latest_time = !empty($latest_time) ? pts_strings::format_time($latest_time, "SECONDS") : "N/A";

				echo "\nTest Installed: Yes\n";
				echo "Last Run: " . $last_run . "\n";

				if($last_run != "Never")
				{
					if($args['object']->test_installation->get_run_count() > 1)
					{
						echo "Average Run-Time: " . $avg_time . "\n";
					}

					echo "Latest Run-Time: " . $latest_time . "\n";
					echo "Times Run: " . $args['object']->test_installation->get_run_count() . "\n";
				}
			}
			else
			{
				echo "\nTest Installed: No\n";
			}

			$dependencies = $args["object"]->get_dependencies();
			if(!empty($dependencies) && !empty($dependencies[0]))
			{
				echo "\nSoftware Dependencies:\n";
				echo pts_user_io::display_text_list($args["object"]->get_dependency_names());
			}
			echo "\n";
		}
		else if($args["object"] instanceof pts_result_file)
		{
			echo "Title: " . $args["object"]->get_title() . "\nIdentifier: " . $args["object"]->get_identifier() . "\n";
			echo "\nTest Result Identifiers:\n";
			echo pts_user_io::display_text_list($args["object"]->get_system_identifiers());

			if(count(($tests = $args["object"]->get_unique_test_titles())) > 0)
			{
				echo "\nContained Tests:\n";
				echo pts_user_io::display_text_list($tests);
			}
			echo "\n";
		}
		else if($args["object"] instanceof pts_virtual_test_suite)
		{
			pts_client::$display->generic_heading($args["object"]->get_title());
			echo "Virtual Suite Description: " . $args["object"]->get_description() . "\n\n";

			foreach($args["object"]->get_contained_test_profiles() as $test_profile)
			{
				echo '- ' . $test_profile . "\n";
			}
			echo "\n";
		}
	}
}

?>
