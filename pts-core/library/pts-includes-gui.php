<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel
	pts-includes-gui.php: Generic functions frequently needed for a GUI front-end

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

function pts_gui_installed_suites()
{
	$installed_suites = pts_installed_suites_array();
	$installed_suites = array_map("pts_suite_identifier_to_name", $installed_suites);
	sort($installed_suites);

	return $installed_suites;
}
function pts_gui_available_suites($to_show_types, $license_types = "", $dependency_limit = null, $downloads_limit = null)
{
	// TODO: Right now a suite could include both free/non-free tests, so $license_types needs to be decided
	$test_suites = pts_supported_suites_array();
	$to_show_names = array();

	// TODO: implement $dependency_limit and $downloads_limit

	foreach($test_suites as $name)
	{
		$ts = new pts_test_suite_details($name);
		$hw_type = $ts->get_suite_type();

		if(empty($hw_type) || in_array($hw_type, $to_show_types))
		{
			$show = true;

			if($dependency_limit != null)
			{
				$dependencies_satisfied = pts_test_external_dependencies_satisfied($name);

				if($dependency_limit == "DEPENDENCIES_INSTALLED")
				{
					$show = $dependencies_satisfied;
				}
				else if($dependency_limit == "DEPENDENCIES_MISSING")
				{
					$show = !$dependencies_satisfied;
				}
			}

			if($show && $downloads_limit != null)
			{
				$all_files_are_local = pts_test_download_files_locally_available($name);

				if($downloads_limit == "DOWNLOADS_LOCAL")
				{
					$show = $all_files_are_local;
				}
				else if($downloads_limit == "DOWNLOADS_MISSING")
				{
					$show = !$all_files_are_local;
				}
			}

			if($show)
			{
				array_push($to_show_names, $name);
			}
		}
	}

	$test_suites = array_map("pts_suite_identifier_to_name", $to_show_names);
	sort($test_suites);

	return $test_suites;
}
function pts_gui_installed_tests($to_show_types, $license_types)
{
	$installed_tests = array();
	$installed = pts_installed_tests_array();
	$license_types = array_map("strtoupper", $license_types);

	foreach($installed as $test)
	{
		$tp = new pts_test_profile_details($test);
		$hw_type = $tp->get_test_hardware_type();
		$license = $tp->get_license();

		if((empty($hw_type) || in_array($hw_type, $to_show_types)) && (empty($license) || in_array($license, $license_types)) && $tp->get_name() != "")
		{
			array_push($installed_tests, $test);
		}
	}

	$installed_tests = array_map("pts_test_identifier_to_name", $installed_tests);
	sort($installed_tests);

	return $installed_tests;
}
function pts_gui_available_tests($to_show_types, $license_types, $dependency_limit = null, $downloads_limit = null)
{
	$test_names = pts_supported_tests_array();
	$to_show_names = array();
	$license_types = array_map("strtoupper", $license_types);

	foreach($test_names as $name)
	{
		$tp = new pts_test_profile_details($name);
		$hw_type = $tp->get_test_hardware_type();
		$license = $tp->get_license();

		if((empty($hw_type) || in_array($hw_type, $to_show_types)) && (empty($license) || in_array($license, $license_types)) && $tp->verified_state())
		{
			$show = true;

			if($dependency_limit != null)
			{
				$dependencies_satisfied = pts_test_external_dependencies_satisfied($name);

				if($dependency_limit == "DEPENDENCIES_INSTALLED")
				{
					$show = $dependencies_satisfied;
				}
				else if($dependency_limit == "DEPENDENCIES_MISSING")
				{
					$show = !$dependencies_satisfied;
				}
			}

			if($show && $downloads_limit != null)
			{
				$all_files_are_local = pts_test_download_files_locally_available($name);

				if($downloads_limit == "DOWNLOADS_LOCAL")
				{
					$show = $all_files_are_local;
				}
				else if($downloads_limit == "DOWNLOADS_MISSING")
				{
					$show = !$all_files_are_local;
				}
			}

			if($show)
			{
				array_push($to_show_names, $name);
			}
		}
	}

	$test_names = array_map("pts_test_identifier_to_name", $to_show_names);
	sort($test_names);

	return $test_names;
}
function pts_gui_saved_test_results_identifiers()
{
	$results = array();

	foreach(glob(SAVE_RESULTS_DIR . "*/composite.xml") as $result_file)
	{
		array_push($results, pts_extract_identifier_from_path($result_file));
	}

	return $results;
}

?>
