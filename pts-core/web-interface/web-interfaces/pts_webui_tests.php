<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2013 - 2016, Phoronix Media
	Copyright (C) 2013 - 2016, Michael Larabel

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


class pts_webui_tests implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Available Tests';
	}
	public static function page_header()
	{
		return null;
	}
	public static function preload($PAGE)
	{
		return true;
	}
	public static function render_page_process($PATH)
	{
		$local_only = false;
		switch(isset($PATH[0]) ? $PATH[0] : null)
		{
			case 'locally_available_tests':
				$local_only = true;
				$selected = 'Locally Available Tests';
				$tests = pts_openbenchmarking::available_tests();
				break;
			case 'available_tests':
				$selected = 'Available Tests';
				$tests = pts_openbenchmarking::available_tests();
				break;
			case 'installed_tests':
			default:
				$tests = pts_tests::installed_tests();
				$selected = 'Installed Tests';
				break;
		}

		echo '<h2>';
		$sub_links = array('Available Tests' => 'tests/available_tests', 'Locally Available Tests' => 'tests/locally_available_tests', 'Installed Tests' => 'tests/installed_tests');
		foreach($sub_links as $txt => $loc)
		{
			echo '<a href="/?' . $loc . '">' . ($selected == $txt ? '<span class="alt">' : null) . $txt . ($selected == $txt ? '<span class="alt">' : null) . '</a> ';
		}
		echo '</h2>';

		$installed_dependencies = pts_external_dependencies::installed_dependency_names();
		$tests_to_show = array();
		foreach($tests as $identifier)
		{
			$test_profile = new pts_test_profile($identifier);

			if(!$test_profile->is_supported(false) || $test_profile->get_title() == null)
			{
				// Don't show unsupported tests
				continue;
			}
			if($local_only && count(($test_dependencies = $test_profile->get_external_dependencies())) > 0)
			{
				$dependencies_met = true;
				foreach($test_dependencies as $d)
				{
					if(!in_array($d, $installed_dependencies))
					{
						$dependencies_met = false;
						break;
					}
				}

				if($dependencies_met == false)
				{
					continue;
				}
			}
			if($local_only && pts_test_install_request::test_files_available_via_cache($test_profile) == false)
			{
				continue;
			}

			$tests_to_show[] = $test_profile;
		}

		echo '<div style="overflow: hidden;">';
		$tests_to_show = array_unique($tests_to_show);
		usort($tests_to_show, array('pts_webui_tests', 'cmp_result_object_sort'));
		$category = null;
		foreach($tests_to_show as &$test_profile)
		{
			if($category != $test_profile->get_test_hardware_type())
			{
				$category = $test_profile->get_test_hardware_type();
				echo '</div><a name="' . $category . '"></a>' . PHP_EOL . '<h2>' . $category . '</h2>' . PHP_EOL . '<div style="overflow: hidden;">';
				$popularity_index = pts_openbenchmarking_client::popular_tests(-1, pts_openbenchmarking_client::read_repository_test_profile_attribute($test_profile, 'test_type'));
			}

			$last_updated = pts_openbenchmarking_client::read_repository_test_profile_attribute($test_profile, 'last_updated');
			$popularity = array_search($test_profile->get_identifier(false), $popularity_index);
			$secondary_message = null;

			if($last_updated > (time() - (60 * 60 * 24 * 21)))
			{
				// Mark it as newly updated if uploaded in past 3 weeks
				$secondary_message = '<strong>Newly Updated.</strong>';
			}
			else if($popularity === 0)
			{
				$secondary_message = '<strong>Most Popular.</strong>';
			}
			else if($popularity < 4)
			{
				$secondary_message = '<strong>Very Popular.</strong>';
			}

			echo '<a href="?test/' . $test_profile->get_identifier() . '"><div class="pts_blue_bar"><strong>' . trim($test_profile->get_title() . ' ' . $test_profile->get_app_version()) . '</strong><br /><span style="">~' . max(1, round($test_profile->get_estimated_run_time() / 60)) . ' mins To Run. ' . $secondary_message . '</span></div></a>';
		}
		echo '</div>';
	}
	public static function cmp_result_object_sort($a, $b)
	{
		$a_comp = $a->get_test_hardware_type() . $a->get_title();
		$b_comp = $b->get_test_hardware_type() . $b->get_title();

		return strcmp($a_comp, $b_comp);
	}
}

?>
