<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2017, Phoronix Media
	Copyright (C) 2008 - 2017, Michael Larabel

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
		new pts_argument_check(0, array('pts_types', 'identifier_to_object'))
		);
	}
	public static function run($args)
	{
		echo PHP_EOL;

		if($args[0] == 'pts/all')
		{
			$args = pts_openbenchmarking::available_tests(false);
		}

		foreach($args as $arg)
		{
			$o = pts_types::identifier_to_object($arg);

			if($o instanceof pts_test_suite)
			{
				pts_client::$display->generic_heading($o->get_title());
				echo pts_client::cli_just_bold('Run Identifier: ') . $o->get_identifier() . PHP_EOL;
				echo pts_client::cli_just_bold('Suite Version: ') . $o->get_version() . PHP_EOL;
				echo pts_client::cli_just_bold('Maintainer: ') . $o->get_maintainer() . PHP_EOL;
				echo pts_client::cli_just_bold('Suite Type: ') . $o->get_suite_type() . PHP_EOL;
				echo pts_client::cli_just_bold('Unique Tests: ') . $o->get_unique_test_count() . PHP_EOL;
				echo pts_client::cli_just_bold('Suite Description: ') . $o->get_description() . PHP_EOL;
				echo PHP_EOL . pts_client::cli_just_bold('Contained Tests: ') . PHP_EOL;
				$test_table = array();
				foreach($o->get_contained_test_result_objects() as $result_obj)
				{
					$test_table[] = array($result_obj->test_profile->get_title(), $result_obj->get_arguments_description());
				}
				echo pts_user_io::display_text_table($test_table, '    ', 1);
				echo PHP_EOL;
			}
			else if($o instanceof pts_test_profile)
			{
				$test_title = $o->get_title();
				$test_version = $o->get_app_version();
				if(!empty($test_version))
				{
					$test_title .= ' ' . $test_version;
				}

				pts_client::$display->generic_heading($test_title);
				echo pts_client::cli_just_bold('Run Identifier: ') . $o->get_identifier() . PHP_EOL;
				echo pts_client::cli_just_bold('Profile Version: ') . $o->get_test_profile_version() . PHP_EOL;
				echo pts_client::cli_just_bold('Maintainer: ') . $o->get_maintainer() . PHP_EOL;
				echo pts_client::cli_just_bold('Test Type: ') . $o->get_test_hardware_type() . PHP_EOL;
				echo pts_client::cli_just_bold('Software Type: ') . $o->get_test_software_type() . PHP_EOL;
				echo pts_client::cli_just_bold('License Type: ') . $o->get_license() . PHP_EOL;
				echo pts_client::cli_just_bold('Test Status: ') . $o->get_status() . PHP_EOL;
				echo pts_client::cli_just_bold('Project Web-Site: ') . $o->get_project_url() . PHP_EOL;
				if($o->get_estimated_run_time() > 1)
				{
					echo pts_client::cli_just_bold('Estimated Run-Time: ') . $o->get_estimated_run_time() . ' Seconds' . PHP_EOL;
				}

				$download_size = $o->get_download_size();
				if(!empty($download_size))
				{
					echo pts_client::cli_just_bold('Download Size: ') . $download_size . ' MB' . PHP_EOL;
				}

				$environment_size = $o->get_environment_size();
				if(!empty($environment_size))
				{
					echo pts_client::cli_just_bold('Environment Size: ') . $environment_size . ' MB' . PHP_EOL;
				}

				echo PHP_EOL . pts_client::cli_just_bold('Description: ') . $o->get_description() . PHP_EOL;

				if($o->test_installation != false)
				{
					$last_run = $o->test_installation->get_last_run_date();
					$last_run = $last_run == '0000-00-00' ? 'Never' : $last_run;

					$avg_time = $o->test_installation->get_average_run_time();
					$avg_time = !empty($avg_time) ? pts_strings::format_time($avg_time, 'SECONDS') : 'N/A';
					$latest_time = $o->test_installation->get_latest_run_time();
					$latest_time = !empty($latest_time) ? pts_strings::format_time($latest_time, 'SECONDS') : 'N/A';

					echo PHP_EOL . pts_client::cli_just_bold('Test Installed: ') . 'Yes' . PHP_EOL;
					echo pts_client::cli_just_bold('Last Run: ') . $last_run . PHP_EOL;

					if($last_run != 'Never')
					{
						if($o->test_installation->get_run_count() > 1)
						{
							echo pts_client::cli_just_bold('Average Run-Time: ') . $avg_time . PHP_EOL;
						}

						echo pts_client::cli_just_bold('Latest Run-Time: ') . $latest_time . PHP_EOL;
						echo pts_client::cli_just_bold('Times Run: ') . $o->test_installation->get_run_count() . PHP_EOL;
					}
				}
				else
				{
					echo PHP_EOL . pts_client::cli_just_bold('Test Installed: ') . 'No' . PHP_EOL;
				}

				$dependencies = $o->get_external_dependencies();
				if(!empty($dependencies) && !empty($dependencies[0]))
				{
					echo PHP_EOL . pts_client::cli_just_bold('Software Dependencies:') . PHP_EOL;
					echo pts_user_io::display_text_list($o->get_dependency_names());
				}
				echo PHP_EOL;
			}
			else if($o instanceof pts_result_file)
			{
				echo pts_client::cli_just_bold('Title: ') . $o->get_title() . PHP_EOL . pts_client::cli_just_bold('Identifier: ') . $o->get_identifier() . PHP_EOL;
				echo PHP_EOL . pts_client::cli_just_bold('Test Result Identifiers:') . PHP_EOL;
				echo pts_user_io::display_text_list($o->get_system_identifiers());

				$test_titles = array();
				foreach($o->get_result_objects() as $result_object)
				{
					if($result_object->test_profile->get_display_format() == 'BAR_GRAPH')
					{
						$test_titles[] = $result_object->test_profile->get_title();
					}
				}

				if(count($test_titles) > 0)
				{
					echo PHP_EOL . pts_client::cli_just_bold('Contained Tests:') . PHP_EOL;
					echo pts_user_io::display_text_list(array_unique($test_titles));
				}
				echo PHP_EOL;
			}
			else if($o instanceof pts_virtual_test_suite)
			{
				pts_client::$display->generic_heading($o->get_title());
				echo pts_client::cli_just_bold('Virtual Suite Description: ') . $o->get_description() . PHP_EOL . PHP_EOL;

				foreach($o->get_contained_test_profiles() as $test_profile)
				{
					echo '- ' . $test_profile . PHP_EOL;
				}
				echo PHP_EOL;
			}
		}
	}
}

?>
