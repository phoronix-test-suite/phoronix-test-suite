<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2018, Phoronix Media
	Copyright (C) 2018, Michael Larabel

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

class search implements pts_option_interface
{
	const doc_section = 'Information';
	const doc_description = 'This option provides command-line searching abilities for test profiles / test suites / test results. The search query can be passed as a parameter otherwise the user is prompted to input their search query..';

	public static function run($r)
	{
		pts_client::$display->generic_heading('Search');
		$search_query = empty($r[0]) ? pts_user_io::prompt_user_input('Enter search query') : $r[0];

		$table = array();
		$tests = array();
		$results_found = false;
		foreach(pts_search::search_test_profiles($search_query) as $test_profile)
		{
			$table[] = array(pts_client::cli_just_bold($test_profile->get_identifier()), $test_profile->get_title(), $test_profile->get_test_hardware_type());
			$tests[] = $test_profile->get_identifier();
		}

		foreach(pts_search::search_local_test_profiles($search_query) as $test_profile)
		{
			$table[] = array(pts_client::cli_just_bold($test_profile->get_identifier()), $test_profile->get_title(), $test_profile->get_test_hardware_type());
			$tests[] = $test_profile->get_identifier();
		}
		if(count($table) > 0)
		{
			$results_found = true;
			echo pts_client::cli_colored_text('TEST PROFILES', 'green', true) . PHP_EOL . pts_user_io::display_text_table($table, null, 1) . PHP_EOL . pts_client::cli_colored_text(pts_strings::plural_handler(count($table), 'Test') . ' Matching', 'gray');

			$table = array();
			foreach(pts_search::search_test_results_for_tests($tests) as $rf)
			{
				$table[] = array(pts_client::cli_just_bold($rf->get_identifier()), $rf->get_title());
			}
			if(!empty($table))
			{
				echo PHP_EOL . PHP_EOL . pts_client::cli_colored_text('TEST RESULTS CONTAINING MATCHING TEST PROFILE(S)', 'green', true) . PHP_EOL . pts_user_io::display_text_table($table, null, 1) . PHP_EOL . pts_client::cli_colored_text(pts_strings::plural_handler(count($table), 'Result') . ' Matching', 'gray');
			}
		}

		// SUITE SEARCH
		$table = array();
		foreach(pts_search::search_test_suites($search_query) as $ts)
		{
			$table[] = array(pts_client::cli_just_bold($ts->get_identifier()), $ts->get_title(), $ts->get_suite_type());
		}
		if(count($table) > 0)
		{
			$results_found = true;
			echo PHP_EOL . PHP_EOL . pts_client::cli_colored_text('TEST SUITES', 'green', true) . PHP_EOL . pts_user_io::display_text_table($table, null, 1) . PHP_EOL . pts_client::cli_colored_text(pts_strings::plural_handler(count($table), 'Suite') . ' Matching', 'gray');
		}

		// RESULT SEARCH
		$table = array();
		foreach(pts_search::search_test_results($search_query) as $rf)
		{
			$table[] = array(pts_client::cli_just_bold($rf->get_identifier()), $rf->get_title());
		}
		if(count($table) > 0)
		{
			$results_found = true;
			echo PHP_EOL . PHP_EOL . pts_client::cli_colored_text('TEST RESULTS', 'green', true) . PHP_EOL . pts_user_io::display_text_table($table, null, 1) . PHP_EOL . pts_client::cli_colored_text(pts_strings::plural_handler(count($table), 'Test Result') . ' Matching', 'gray');
		}

		if(!$results_found)
		{
			echo PHP_EOL . pts_client::cli_just_bold('No Search Matches Found');
		}

		echo PHP_EOL . PHP_EOL;
	}
}

?>
