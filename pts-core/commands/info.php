<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2021, Phoronix Media
	Copyright (C) 2008 - 2021, Michael Larabel

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
				echo pts_client::cli_just_bold('Suite Description: ') . ' ' . $o->get_description() . PHP_EOL . PHP_EOL;
				$table = array();
				$table[] = array(pts_client::cli_just_bold('Run Identifier:    '), $o->get_identifier());
				$table[] = array(pts_client::cli_just_bold('Suite Version: '), $o->get_version());
				$table[] = array(pts_client::cli_just_bold('Maintainer: '), $o->get_maintainer());
				$table[] = array(pts_client::cli_just_bold('Status: '), $o->get_status());
				$table[] = array(pts_client::cli_just_bold('Suite Type: '), $o->get_suite_type());
				$table[] = array(pts_client::cli_just_bold('Unique Tests: '), $o->get_unique_test_count());
				$table[] = array(pts_client::cli_just_bold('Contained Tests: '));
				$unique_tests = array();
				foreach($o->get_contained_test_result_objects() as $result_obj)
				{
					$table[] = array(pts_client::cli_just_bold(null), $result_obj->test_profile->get_title() . ' ', $result_obj->get_arguments_description());
					pts_arrays::unique_push($unique_tests, $result_obj->test_profile->get_title());
				}
				echo pts_user_io::display_text_table($table) . PHP_EOL;
				echo '                    ' . pts_client::cli_just_bold(count($table) . ' Tests / ' . count($unique_tests) . ' Unique Tests') . PHP_EOL;
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

				if($o->get_license() == 'Retail' || $o->get_license() == 'Restricted')
				{
					echo pts_client::cli_just_bold(strtoupper('NOTE: This test profile is marked \'' . $o->get_license() . '\' and may have issues running without third-party/commercial dependencies.')) . PHP_EOL . PHP_EOL;
				}
				if($o->get_status() != 'Verified' && $o->get_status() != null)
				{
					echo pts_client::cli_just_bold(strtoupper('NOTE: This test profile is marked \'' . $o->get_status() . '\' and may have known issues with test installation or execution.')) . PHP_EOL . PHP_EOL;
				}

				$table = array();
				$table[] = array(pts_client::cli_just_bold('Run Identifier: '), $o->get_identifier());
				$table[] = array(pts_client::cli_just_bold('Profile Version: '), $o->get_test_profile_version());
				$table[] = array(pts_client::cli_just_bold('Maintainer: '), $o->get_maintainer());
				$table[] = array(pts_client::cli_just_bold('Test Type: '), $o->get_test_hardware_type());
				$table[] = array(pts_client::cli_just_bold('Software Type: '), $o->get_test_software_type());
				$table[] = array(pts_client::cli_just_bold('License Type: '), $o->get_license());
				$table[] = array(pts_client::cli_just_bold('Test Status: '), $o->get_status());
				$table[] = array(pts_client::cli_just_bold('Supported Platforms: '), implode(', ', $o->get_supported_platforms()));
				$table[] = array(pts_client::cli_just_bold('Project Web-Site: '), $o->get_project_url());
				if($o->get_repo_url())
				{
					$table[] = array(pts_client::cli_just_bold('Source Repository Location: '), $o->get_repo_url());
				}

				if($o->get_estimated_run_time() > 1)
				{
					$table[] = array(pts_client::cli_just_bold('Estimated Run-Time: '), $o->get_estimated_run_time() . ' Seconds');
				}
				if($o->get_estimated_run_time() > 1)
				{
					$table[] = array(pts_client::cli_just_bold('Estimated Install Time: '), $o->get_estimated_install_time() . ' Seconds');
				}

				$download_size = $o->get_download_size();
				if(!empty($download_size))
				{
					$table[] = array(pts_client::cli_just_bold('Download Size: '), $download_size . ' MB');
				}

				$environment_size = $o->get_environment_size();
				if(!empty($environment_size))
				{
					$table[] = array(pts_client::cli_just_bold('Environment Size: '), $environment_size . ' MB');
				}

				echo pts_user_io::display_text_table($table);

				echo PHP_EOL . PHP_EOL . pts_client::cli_just_bold('Description: ') . $o->get_description() . PHP_EOL;

				if(stripos($o->get_identifier(0), 'local/') === false)
				{
					echo PHP_EOL . PHP_EOL . pts_client::cli_just_bold('OpenBenchmarking.org Test Profile: ') . 'https://openbenchmarking.org/test/' . $o->get_identifier() . PHP_EOL . PHP_EOL;
				}

				foreach(array('Pre-Install Message' => $o->get_pre_install_message(), 'Post-Install Message' => $o->get_post_install_message(), 'Pre-Run Message' => $o->get_pre_run_message(), 'Post-Run Message' => $o->get_post_run_message()) as $msg_type => $msg)
				{
					if($msg != null)
					{
						echo PHP_EOL . pts_client::cli_just_bold($msg_type . ': ') . $msg . PHP_EOL. PHP_EOL;
					}
				}

				if($o->test_installation != false && $o->test_installation->is_installed())
				{
					$last_run = $o->test_installation->get_last_run_date();
					$last_run = $last_run == '0000-00-00' || empty($last_run) ? 'Never' : $last_run;

					$avg_time = $o->test_installation->get_average_run_time();
					$avg_time = !empty($avg_time) ? pts_strings::format_time($avg_time, 'SECONDS') : 'N/A';
					$latest_time = $o->test_installation->get_latest_run_time();
					$latest_time = !empty($latest_time) ? pts_strings::format_time($latest_time, 'SECONDS') : 'N/A';
					$install_time = $o->test_installation->get_latest_install_time();
					$install_time = !empty($install_time) ? pts_strings::format_time($install_time, 'SECONDS') : 'N/A';

					$table = array();
					$table[] = array(pts_client::cli_just_bold('Test Installed: '), 'Yes');
					$table[] = array(pts_client::cli_just_bold('Last Run: '), $last_run);
					$table[] = array(pts_client::cli_just_bold('Install Time: '), $install_time);
					if($o->test_installation->get_install_size() > 0)
					{
						$table[] = array(pts_client::cli_just_bold('Install Size: '), $o->test_installation->get_install_size() . ' Bytes');
					}

					if($last_run != 'Never')
					{
						if($o->test_installation->get_run_count() > 1)
						{
							$table[] = array(pts_client::cli_just_bold('Average Run-Time: '), $avg_time);
						}

						$table[] = array(pts_client::cli_just_bold('Latest Run-Time: '), $latest_time);
						$table[] = array(pts_client::cli_just_bold('Times Run: '), $o->test_installation->get_run_count());
					}
					echo pts_user_io::display_text_table($table) . PHP_EOL;
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



				// OpenBenchmarking.org Change-Log
				if(stripos($o->get_identifier(), 'local/') === false && !defined('PHOROMATIC_PROCESS'))
				{

					$change_log = pts_openbenchmarking_client::fetch_repository_test_profile_changelog($o->get_identifier(false));

					if(is_array($change_log) && isset($change_log['tests'][$o->get_identifier_base_name()]['changes']))
					{
						echo pts_client::cli_just_bold('OpenBenchmarking.org Change History') . PHP_EOL;
						$change_log = $change_log['tests'][$o->get_identifier_base_name()]['changes'];
						foreach($change_log as $version => $data)
						{
							echo pts_client::cli_colored_text('v' . $version . ' - ' . date('j F Y', $data['last_updated']), 'green', true) . PHP_EOL;
							echo $data['commit_description'] . PHP_EOL;
						}
					}
				}

				// Recent Test Results With This Test
				if(!defined('PHOROMATIC_PROCESS'))
				{
					$o_identifier = $o->get_identifier(false);
					$table = array();
					foreach(pts_results::saved_test_results() as $saved_results_identifier)
					{
						$result_file = new pts_result_file($saved_results_identifier);
						foreach($result_file->get_result_objects() as $result_object)
						{
							if($result_object->test_profile->get_identifier(false) == $o_identifier)
							{
								$table[] = array(pts_client::cli_just_bold($result_file->get_identifier()), $result_file->get_title());
								break;
							}
						}
					}
					if(!empty($table))
					{
						echo PHP_EOL . pts_client::cli_just_bold('Results Containing This Test') . PHP_EOL;
						echo pts_user_io::display_text_table($table) . PHP_EOL;
					}

					$suites_containing_test = pts_test_suites::suites_containing_test_profile($o);
					if(!empty($suites_containing_test))
					{
						$table = array();
						foreach($suites_containing_test as $suite)
						{
							$table[] = array($suite->get_identifier(false), pts_client::cli_just_bold($suite->get_title()));
						}
						echo PHP_EOL . pts_client::cli_just_bold('Test Suites Containing This Test') . PHP_EOL;
						echo pts_user_io::display_text_table($table) . PHP_EOL;
					}
				}
			}
			else if($o instanceof pts_result_file)
			{
				echo pts_client::cli_just_bold('Title: ') . $o->get_title() . PHP_EOL . pts_client::cli_just_bold('Identifier: ') . $o->get_identifier() . PHP_EOL;
				echo PHP_EOL . pts_client::cli_just_bold('Test Result Identifiers:') . PHP_EOL;
				echo pts_user_io::display_text_list($o->get_system_identifiers());
				$system_count = count($o->get_system_identifiers());
				if($system_count > 8)
				{
					echo pts_client::cli_just_italic($system_count . ' Systems') . PHP_EOL;
				}

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
					$tt = array_unique($test_titles);
					natcasesort($tt);
					echo pts_user_io::display_text_list($tt);
					echo '  ' . pts_client::cli_just_italic(pts_strings::plural_handler(count($tt), 'Distinct Test Profile')) . PHP_EOL;
					echo '  ' . pts_client::cli_just_italic(pts_strings::plural_handler($o->get_test_count(), 'Test')) . PHP_EOL;
					echo '  ' . pts_client::cli_just_italic(pts_strings::plural_handler($o->get_qualified_test_count(), 'Qualified Test')) . PHP_EOL;
				}
				echo PHP_EOL;
			}
		}
	}
}

?>
