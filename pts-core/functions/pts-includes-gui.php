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
	$installed_suites = array();

	foreach(pts_available_suites_array() as $suite)
	{
		if(!pts_suite_needs_updated_install($suite))
		{
			array_push($installed_suites, $suite);
		}
	}

	$installed_suites = array_map("pts_suite_identifier_to_name", $installed_suites);
	sort($installed_suites);

	return $installed_suites;
}
function pts_gui_available_suites($to_show_types)
{
	$test_suites = pts_supported_suites_array();
	$to_show_names = array();

	foreach($test_suites as $name)
	{
		$ts = new pts_test_suite_details($name);
		$hw_type = $ts->get_suite_type();

		if(empty($hw_type) || in_array($hw_type, $to_show_types))
		{
			array_push($to_show_names, $name);
		}
	}

	$test_suites = array_map("pts_suite_identifier_to_name", $to_show_names);
	sort($test_suites);

	return $test_suites;
}
function pts_gui_installed_tests($to_show_types)
{
	$installed_tests = array();
	$installed = pts_installed_tests_array();

	foreach($installed as $test)
	{
		$tp = new pts_test_profile_details($test);
		$hw_type = $tp->get_test_hardware_type();

		if((empty($hw_type) || in_array($hw_type, $to_show_types)) && $tp->get_name() != "")
		{
			array_push($installed_tests, $test);
		}
	}

	$installed_tests = array_map("pts_test_identifier_to_name", $installed_tests);
	sort($installed_tests);

	return $installed_tests;
}
function pts_gui_available_tests($to_show_types)
{
	$test_names = pts_supported_tests_array();
	$to_show_names = array();

	foreach($test_names as $name)
	{
		$tp = new pts_test_profile_details($name);
		$hw_type = $tp->get_test_hardware_type();

		if((empty($hw_type) || in_array($hw_type, $to_show_types)) && $tp->verified_state())
		{
			array_push($to_show_names, $name);
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
		array_push($results, pts_extract_identifier_from_directory($result_file));
	}

	return $results;
}

?>
