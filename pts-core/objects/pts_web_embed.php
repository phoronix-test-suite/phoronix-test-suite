<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2018 - 2022, Phoronix Media
	Copyright (C) 2018 - 2022, Michael Larabel

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

class pts_web_embed
{
	public static function cookie_checkbox_option_helper($cookie_name, $description_string)
	{
		$html = '<p style="margin-top: 0; margin-bottom: 1px;">';
		if(isset($_COOKIE[$cookie_name]) && $_COOKIE[$cookie_name])
		{
			$html .= '<input type="checkbox" checked="checked" onchange="javascript:document.cookie=\'' . $cookie_name . '=0\'; location.reload();" /> ' . $description_string;
		}
		else
		{
			$html .= '<input type="checkbox" onchange="javascript:document.cookie=\'' . $cookie_name . '=1\'; location.reload();" /> ' . $description_string;
		}
		
		return $html . '</p>';
	}
	public static function cookie_input_helper($cookie_name, $description_string)
	{
		$html = '<p style="margin-top: 0; margin-bottom: 1px;"><input type="text" onchange="javascript:document.cookie=\'' . $cookie_name . '=\' + this.value + \'\'; location.reload();" value="' . (self::cookie_check($cookie_name) ? self::cookie_check($cookie_name) : '') . '" placeholder="' . $description_string . '" /> </p>';
		
		return $html;
	}
	public static function cookie_check($cookie_name)
	{
		return isset($_COOKIE[$cookie_name]) && $_COOKIE[$cookie_name] ? $_COOKIE[$cookie_name] : false;
	}
	public static function tests_cmp_result_object_sort($a, $b)
	{
		$a_comp = $a->get_test_hardware_type() . $a->get_title();
		$b_comp = $b->get_test_hardware_type() . $b->get_title();

		return strcmp($a_comp, $b_comp);
	}
	public static function tests_list($tests_to_show = false)
	{
		$html = '';
		$html .= pts_web_embed::cookie_checkbox_option_helper('show_linux_tests', 'Limit to tests that support Linux.');
		$html .= pts_web_embed::cookie_checkbox_option_helper('show_windows_tests', 'Limit to tests that support Windows.');
		$html .= pts_web_embed::cookie_checkbox_option_helper('show_macos_tests', 'Limit to tests that support macOS.');
		$html .= pts_web_embed::cookie_checkbox_option_helper('show_bsd_tests', 'Limit to tests that support BSD.');
		$html .= pts_web_embed::cookie_checkbox_option_helper('include_outdated_tests', 'Include test profiles not actively maintained (potentially outdated).');
		$html .= pts_web_embed::cookie_checkbox_option_helper('include_deprecated_tests', 'Include test profiles marked deprecated or broken.');
		$html .= pts_web_embed::cookie_checkbox_option_helper('linear_list', 'Show test profiles in a linear list.');
		$html .= pts_web_embed::cookie_input_helper('search_tests', 'Search test profiles');
		$tests = pts_openbenchmarking::available_tests(false, false, true);
		if($tests_to_show == false)
		{
			$tests_to_show = array();
			foreach($tests as $identifier)
			{
				$test_profile = new pts_test_profile($identifier);

				if($test_profile->get_title() == null)
				{
					// Don't show unsupported tests
					continue;
				}

				$tests_to_show[] = $test_profile;
			}
		}
		
		if(empty($tests_to_show))
		{
			$html .= '<p>No cached test profiles found.</p>';
		}
		else
		{
			$html .= '<p><em>The test profiles below are cached on the local system and in a current state. For a complete listing of available tests visit <a href="https://openbenchmarking.org/">OpenBenchmarking.org</a>.</em></p>';
		}

		$html .= '<div class="pts_test_boxes">';
		$tests_to_show = array_unique($tests_to_show);
		usort($tests_to_show, array('pts_web_embed', 'tests_cmp_result_object_sort'));
		$category = null;
		$tests_in_category = 0;
		foreach($tests_to_show as &$test_profile)
		{
			if($category != $test_profile->get_test_hardware_type())
			{
				$category = $test_profile->get_test_hardware_type();
				if($category == null)
				{
					continue;
				}
				if($tests_in_category > 0)
				{
					$html .= '<br style="clear: both;" /><em>' . pts_strings::plural_handler($tests_in_category, 'Test') . '</em>';
				}
				$tests_in_category = 0;
				$html .= '</div><a name="' . $category . '"></a>' . PHP_EOL . '<h2>' . $category . '</h2>' . PHP_EOL . '<div class="pts_test_boxes">';
				$popularity_index = pts_openbenchmarking_client::popular_tests(-1, pts_openbenchmarking_client::read_repository_test_profile_attribute($test_profile, 'test_type'));
			}
			if($category == null)
			{
				continue;
			}
			if(self::cookie_check('include_deprecated_tests') == false && ($test_profile->get_status() == 'Deprecated' || $test_profile->get_status() == 'Broken'))
			{
				// Don't show deprecated/broken tests
				continue;
			}
			if(self::cookie_check('show_linux_tests') && !in_array('Linux', $test_profile->get_supported_platforms()))
			{
				continue;
			}
			if(self::cookie_check('show_windows_tests') && !in_array('Windows', $test_profile->get_supported_platforms()))
			{
				continue;
			}
			if(self::cookie_check('show_macos_tests') && !in_array('MacOSX', $test_profile->get_supported_platforms()))
			{
				continue;
			}
			if(self::cookie_check('show_bsd_tests') && !in_array('BSD', $test_profile->get_supported_platforms()))
			{
				continue;
			}

			$last_updated = pts_openbenchmarking_client::read_repository_test_profile_attribute($test_profile, 'last_updated');
			$versions = pts_openbenchmarking_client::read_repository_test_profile_attribute($test_profile, 'versions');
			$popularity = isset($popularity_index) && is_array($popularity_index) ? array_search($test_profile->get_identifier(false), $popularity_index) : false;
			
			if(self::cookie_check('include_outdated_tests') == false && $last_updated < (time() - (86400 * 365 * 4)))
			{
				// Don't show really old tests
				continue;
			}
			if(!empty($search_query = self::cookie_check('search_tests')) && !pts_search::check_test_profile_match($test_profile, $search_query))
			{
				continue;
			}

			$secondary_message = '';
			if($last_updated > (time() - (86400 * 30)))
			{
				$secondary_message = count($versions) == 1 ? '- <em>Newly Added</em>' : '- <em>Recently Updated</em>';
			}
			else if($popularity === 0)
			{
				$secondary_message = '- <em>Most Popular</em>';
			}
			else if($popularity < 4)
			{
				$secondary_message = '- <em>Very Popular</em>';
			}
			else if($popularity < 6)
			{
				$secondary_message = '- <em>Quite Popular</em>';
			}

			if(defined('PHOROMATIC_SERVER'))
			{
				$test_page_url = '/?tests/' . $test_profile->get_identifier();
				if(!PHOROMATIC_USER_IS_VIEWER)
				{
					$secondary_message .= (strpos($test_profile->get_identifier(), 'local/') !== false ? '<a href="/?create_test/' . $test_profile->get_identifier() . '">Edit Test</a> - <a href="/?create_test/' . $test_profile->get_identifier() . '&delete" onclick="return confirm(\'Are you sure you want to delete this test?\');">Delete Test</a>' : '');
				}
			}
			else
			{
				$test_page_url = WEB_URL_PATH . 'test/' . base64_encode($test_profile->get_identifier());
			}
			if(self::cookie_check('linear_list'))
			{
				$html .= '<h1 style="margin-bottom: 0;"><a href="' . $test_page_url . '">' . $test_profile->get_title() . '</a></h1>';
				$html .= '<p><strong>' . $test_profile->get_identifier() . '</strong> ' . $secondary_message . ' <em>-</em> ' . $test_profile->get_description() . '</p>';
			}
			else
			{
				$html .= '<a href="' . $test_page_url . '"><div class="table_test_box"><strong>' . $test_profile->get_title(). '</strong><br /><span>~' . pts_strings::plural_handler(max(1, round(pts_openbenchmarking_client::read_repository_test_profile_attribute($test_profile, 'average_run_time') / 60)),
'min') . ' run-time ' . $secondary_message . '</span></div></a>';
			}
			$tests_in_category++;
		}
		if($tests_in_category > 0)
		{
			$html .= '<br style="clear: both;" /><em>' . $tests_in_category . ' Tests</em>';
		}
		$html .= '</div>';
		
		return $html;
	}
	public static function suites_cmp_result_object_sort($a, $b)
	{
		$a_comp = $a->get_suite_type() . $a->get_title();
		$b_comp = $b->get_suite_type() . $b->get_title();

		return strcmp($a_comp, $b_comp);
	}
	public static function test_suites_list()
	{
		$html = '';
		$suites = pts_test_suites::all_suites_cached();
		$suites_to_show = array();
		foreach($suites as $identifier)
		{
			$test_suite = new pts_test_suite($identifier);

			if($test_suite->get_title() == null)
			{
				// Don't show unsupported suites
				continue;
			}

			$suites_to_show[] = $test_suite;
		}
		if(empty($suites_to_show))
		{
			$html .= '<p>No cached test suites found.</p>';
		}
		else
		{
			$html .= pts_web_embed::cookie_input_helper('search_suites', 'Search test suites');
			$html .= '<p><em>The test suites below are cached and available on the local system. For a complete listing of available test suites visit <a href="https://openbenchmarking.org/">OpenBenchmarking.org</a>.</em></p>';
		}

		$html .= '<div class="pts_test_boxes">';
		$suites_to_show = array_unique($suites_to_show);

		usort($suites_to_show, array('pts_web_embed', 'suites_cmp_result_object_sort'));
		$category = null;
		$suites_in_category = 0;
		foreach($suites_to_show as &$test_suite)
		{
			if(!empty($search_query = self::cookie_check('search_suites')) && !pts_search::check_test_suite_match($test_suite, $search_query))
			{
				continue;
			}
			if($category != $test_suite->get_suite_type())
			{
				$category = $test_suite->get_suite_type();
				if($category == null) continue;
				if($suites_in_category > 0)
				{
					$html .= '<br style="clear: both;" /><em>' . pts_strings::plural_handler($suites_in_category, 'Suite') . '</em>';
				}
				$suites_in_category = 0;
				$html .= '</div><a name="' . $category . '"></a>' . PHP_EOL . '<h2>' . $category . '</h2>' . PHP_EOL . '<div class="pts_test_boxes">';
			}
			if($category == null) continue;
			$suites_in_category++;

			$last_updated = pts_openbenchmarking_client::read_repository_test_suite_attribute($test_suite->get_identifier(), 'last_updated');
			$versions = pts_openbenchmarking_client::read_repository_test_suite_attribute($test_suite->get_identifier(), 'versions');
			$secondary_message = null;

			if($last_updated > (time() - (86400 * 45)))
			{
				// Mark it as newly updated if uploaded in past 3 weeks
				$secondary_message = count($versions) == 1 ? '- <em>Newly Added</em>' : '- <em>Recently Updated</em>';
			}

			$html .= '<a href="' . WEB_URL_PATH . 'suite/' . base64_encode($test_suite->get_identifier()) . '"><div class="table_test_box"><strong>' . $test_suite->get_title(). '</strong><br /><span>' . $test_suite->get_test_count() . ' Tests (' . $test_suite->get_unique_test_count() . ' Unique Profiles) ' . $secondary_message . '</span></div></a>';
		}
		if($suites_in_category > 0)
		{
			$html .= '<br style="clear: both;" /><em>' . $suites_in_category . ' Tests</em>';
		}
		$html .= '</div>';
		
		return $html;
	}
	public static function test_suite_overview(&$test_suite)
	{
		$html = '<h1>' . $test_suite->get_title() . '</h1>';

		$table = array();
		$table[] = array('Run Identifier: ', $test_suite->get_identifier());
		$table[] = array('Profile Version: ', $test_suite->get_version());
		$table[] = array('Maintainer: ', $test_suite->get_maintainer());
		$table[] = array('Test Type: ', $test_suite->get_suite_type());

		$cols = array(array(), array());
		foreach($table as &$row)
		{
			$row[0] = '<strong>' . $row[0] . '</strong>';
			$cols[0][] = $row[0];
			$cols[1][] = $row[1];
		}
		$html .= '<br /><div style="float: left;">' . implode('<br />', $cols[0]) . '</div>';
		$html .= '<div style="float: left; padding-left: 15px;">' . implode('<br />', $cols[1]) . '</div>' . '<br style="clear: both;" />';
		$html .= '<p>'. $test_suite->get_description() . '</p>';
		foreach($test_suite->get_contained_test_result_objects() as $ro)
		{
			$html .= '<h2><a href="' . WEB_URL_PATH . 'test/' . base64_encode($ro->test_profile->get_identifier()) . '">' . $ro->test_profile->get_title() . '</a></h2>';
			$html .= '<p>' . $ro->get_arguments_description() . '</p>';
		}
		
		return $html;
	}
	public static function test_profile_overview(&$test_profile)
	{
		$html = '<h1>' . $test_profile->get_title() . '</h1>';

		if($test_profile->get_license() == 'Retail' || $test_profile->get_license() == 'Restricted')
		{
			$html .= '<p><em>NOTE: This test profile is marked \'' . $test_profile->get_license() . '\' and may have issues running without third-party/commercial dependencies.</em></p>';
		}
		if($test_profile->get_status() != 'Verified' && $test_profile->get_status() != null)
		{
			$html .= '<p><em>NOTE: This test profile is marked \'' . $test_profile->get_status() . '\' and may have known issues with test installation or execution.</em></p>';
		}

		$table = array();
		$table[] = array('Run Identifier: ', $test_profile->get_identifier());
		$table[] = array('Profile Version: ', $test_profile->get_test_profile_version());
		$table[] = array('Maintainer: ', $test_profile->get_maintainer());
		$table[] = array('Test Type: ', $test_profile->get_test_hardware_type());
		$table[] = array('Software Type: ', $test_profile->get_test_software_type());
		$table[] = array('License Type: ', $test_profile->get_license());
		$table[] = array('Test Status: ', $test_profile->get_status());
		$table[] = array('Supported Platforms: ', implode(', ', $test_profile->get_supported_platforms()));
		$table[] = array('Project Web-Site: ', '<a target="_blank" href="' . $test_profile->get_project_url() . '">' . $test_profile->get_project_url() . '</a>');
		if($test_profile->get_repo_url())
		{
			$table[] = array('Project Repository: ', '<a target="_blank" href="' . $test_profile->get_repo_url() . '">' . $test_profile->get_repo_url() . '</a>');
		}

		$download_size = $test_profile->get_download_size();
		if(!empty($download_size))
		{
			$table[] = array('Download Size: ', $download_size . ' MB');
		}

		$environment_size = $test_profile->get_environment_size();
		if(!empty($environment_size))
		{
			$table[] = array('Environment Size: ', $environment_size . ' MB');
		}

		$cols = array(array(), array());
		foreach($table as &$row)
		{
			$row[0] = '<strong>' . $row[0] . '</strong>';
			$cols[0][] = $row[0];
			$cols[1][] = $row[1];
		}
		$html .= '<br /><div style="float: left;">' . implode('<br />', $cols[0]) . '</div>';
		$html .= '<div style="float: left; padding-left: 15px;">' . implode('<br />', $cols[1]) . '</div>' . '<br style="clear: both;" />';
		$html .= '<p>'. $test_profile->get_description() . '</p>';

		foreach(array('Pre-Install Message' => $test_profile->get_pre_install_message(), 'Post-Install Message' => $test_profile->get_post_install_message(), 'Pre-Run Message' => $test_profile->get_pre_run_message(), 'Post-Run Message' => $test_profile->get_post_run_message()) as $msg_type => $msg)
		{
			if($msg != null)
			{
				$html .= '<p><em>' . $msg_type . ': ' . $msg . '</em></p>';
			}
		}

		$dependencies = $test_profile->get_external_dependencies();
		if(!empty($dependencies) && !empty($dependencies[0]))
		{
			$html .= PHP_EOL . '<strong>Software External Dependencies:</strong>' . '<br />';
			$html .= implode('<br />', $dependencies) . '<br /><br />';
		}

		$system_dependencies = $test_profile->get_system_dependencies();
		if(!empty($system_dependencies))
		{
			$html .= PHP_EOL . '<strong>System Dependencies To Check For:</strong>' . '<br />';
			$html .= implode('<br />', $system_dependencies) . '<br />';
		}
		
		if(stripos($test_profile->get_identifier(0), 'local/') === false)
		{
			$html .= PHP_EOL . '<br /><strong>OpenBenchmarking.org Test Profile Page: </strong>' . '<a href="https://openbenchmarking.org/test/' . $test_profile->get_identifier() . '">https://openbenchmarking.org/test/' . $test_profile->get_identifier() . '</a><br />';
		}

		$overview_data = $test_profile->get_generated_data();
		if(!empty($overview_data) && isset($overview_data['overview']) && !empty($overview_data['overview']))
		{
			$html .= '<hr /><h2>OpenBenchmarking.org Overview Metrics</h2><p>';
			$tested_archs = array();
			foreach($overview_data['overview'] as $comparison_Hash => $d)
			{
				if(empty($d['description']) || $d['samples'] < 5)
				{
					continue;
				}
				$html .= '<h3>' . $d['description'] . '</h3>' . PHP_EOL;
				$html .= '<strong>Average Deviation Between Runs:</strong> <em>'  . $d['stddev_avg'] . '%</em> ';
				$html .= '<strong>Sample Analysis Count:</strong> <em>'  . $d['samples'] . '</em></p>' . PHP_EOL;

				$box_buffer = new pts_test_result_buffer();
				$box_buffer->add_test_result('Overview', implode(',', $d['percentiles']));
				$tph = new pts_test_profile();
				$tph->set_test_title($d['description']);
				$tph->set_result_scale($d['unit']);
				$tph->set_result_proportion($d['hib'] ? 'HIB' : 'LIB');
				$box_result = new pts_test_result($tph);
				$box_result->set_test_result_buffer($box_buffer);
				$dd = new pts_graph_box_plot($box_result);
				$dd->data_is_percentiles();
				$html .= '<div class="results_area">' . pts_render::render_graph_inline_embed($dd) . '</div>';

				$html .= '<p><strong>[Run-Time Requirements] Average Run-Time:</strong> <em>'  . pts_strings::format_time($d['run_time_avg'], 'SECONDS', true, 60) . '</em> ';

				$box_buffer = new pts_test_result_buffer();
				$box_buffer->add_test_result('Overview', implode(',', $d['run_time_percentiles']));
				$tph = new pts_test_profile();
				$tph->set_test_title($d['description'] . ' Run-Time Requirements');
				$tph->set_result_scale('Seconds');
				$tph->set_result_proportion('LIB');
				$box_result = new pts_test_result($tph);
				$box_result->set_test_result_buffer($box_buffer);
				$dd = new pts_graph_box_plot($box_result);
				$dd->data_is_percentiles();
				$html .= '<div class="results_area">' . pts_render::render_graph_inline_embed($dd) . '</div>';
				if(isset($d['tested_archs']) && !empty($d['tested_archs']))
				{
					foreach($d['tested_archs'] as $ta)
					{
						pts_arrays::unique_push($tested_archs, $ta);
					}
				}
			}
			if(isset($overview_data['capabilities']) && !empty($overview_data['capabilities']))
			{
				$html .= '<hr /><h2>OpenBenchmarking.org Workload Analysis</h2><p>';
				if(isset($overview_data['capabilities']['shared_libraries']) && !empty($overview_data['capabilities']['shared_libraries']))
				{
					$html .= '<strong>Shared Libraries Used By This Test:</strong> ' . implode(', ', $overview_data['capabilities']['shared_libraries']) . '<br />';
				}
				if(isset($overview_data['capabilities']['default_instructions']) && !empty($overview_data['capabilities']['default_instructions']))
				{
					$html .= '<strong>Notable Instructions Used By Test On Capable CPUs:</strong> ' . implode(', ', $overview_data['capabilities']['default_instructions']) . '<br />';
					if(isset($overview_data['capabilities']['max_instructions']) && !empty($overview_data['capabilities']['max_instructions']) && $overview_data['capabilities']['default_instructions'] != $overview_data['capabilities']['max_instructions'])
					{
						$html .= '<strong>Instructions Possible On Capable CPUs With Extra Compiler Flags:</strong> ' . implode(', ', $overview_data['capabilities']['max_instructions']) . '<br />';
					}
				}
				if(isset($overview_data['capabilities']['honors_cflags']) && $overview_data['capabilities']['honors_cflags'] == 1)
				{
					$html .= '<strong>Honors CFLAGS/CXXFLAGS:</strong> ' . 'Yes' . '<br />';
				}
				if(isset($overview_data['capabilities']['scales_cpu_cores']) && $overview_data['capabilities']['scales_cpu_cores'] !== null)
				{
					$html .= '<strong>Test Multi-Threaded / CPU Core Scaling:</strong> ' . ($overview_data['capabilities']['scales_cpu_cores'] ? 'Yes' : 'No') . '<br />';
				}
				if(!empty($tested_archs))
				{
					sort($tested_archs);
					$html .= '<strong>Tested CPU Architectures:</strong> ' . implode(', ', $tested_archs) . '<br />';
				}
				$html .= '</p>';
			}
		}

		$change_log = $test_profile->get_changelog();
		if(!empty($change_log))
		{
			$html .= '<hr /><h2>Test Profile Change History</h2><p>';
			foreach($change_log as $version => $data)
			{
				$html .= '<strong>v' . $version . '</strong> - <em>' . date('j F Y', $data['last_updated']) . '</em> - '  . $data['commit_description'] . '<br />' . PHP_EOL;
			}
			$html .= '</p>';
		}

		$html .= '<hr />';
		
		return $html;
	}
}

?>
