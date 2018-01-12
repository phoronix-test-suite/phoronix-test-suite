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

		$test_matches = 0;
		$test_results = null;
		foreach(pts_openbenchmarking::available_tests(false) as $identifier)
		{
			$repo = substr($identifier, 0, strpos($identifier, '/'));
			$id = substr($identifier, strlen($repo) + 1);
			$repo_index = pts_openbenchmarking::read_repository_index($repo);

			if(empty($repo_index['tests'][$id]['title']))
			{
				// Don't show unsupported tests
				continue;
			}
			if(stripos($repo_index['tests'][$id]['title'], $search_query) !== false || stripos($id, $search_query) !== false || stripos($repo_index['tests'][$id]['description'], $search_query) !== false || in_array($search_query, $repo_index['tests'][$id]['internal_tags']) !== false || $search_query == $repo_index['tests'][$id]['test_type'])
			{
				$test_results .= sprintf('%-30ls - %-35ls %-9ls', pts_client::cli_just_bold($identifier), $repo_index['tests'][$id]['title'], $repo_index['tests'][$id]['test_type']) . PHP_EOL;
				$test_matches++;
			}
		}

		foreach(pts_tests::local_tests() as $identifier)
		{
			$test_profile = new pts_test_profile($identifier);

			if($test_profile->get_title() != null && (stripos($test_profile->get_title(), $search_query) !== false || stripos($test_profile->get_identifier(), $search_query) !== false || stripos($test_profile->get_description(), $search_query) !== false || in_array($search_query, $test_profile->get_internal_tags()) !== false))
			{
				$test_results .= sprintf('%-30ls - %-35ls %-9ls', pts_client::cli_just_bold($test_profile->get_identifier()), $test_profile->get_title(), $test_profile->get_test_hardware_type()) . PHP_EOL;
				$test_matches++;
			}
		}
		if($test_matches > 0)
		{
			echo pts_client::cli_just_bold('TEST PROFILES') . PHP_EOL . $test_results . pts_strings::plural_handler($test_matches, 'Test') . ' Matching';
		}

		// SUITE SEARCH
		$suite_matches = 0;
		$suite_results = null;
		foreach(array_merge(pts_openbenchmarking::available_suites(false), pts_tests::local_suites()) as $identifier)
		{
			$test_suite = new pts_test_suite($identifier);
			if($test_suite->get_title() != null && (stripos($test_suite->get_title(), $search_query) !== false || stripos($test_suite->get_identifier(), $search_query) !== false || stripos($test_suite->get_description(), $search_query) !== false))
			{
				$suite_results .= sprintf('%-30ls - %-35ls %-9ls', pts_client::cli_just_bold($test_suite->get_identifier()), $test_suite->get_title(), $test_suite->get_suite_type()) . PHP_EOL;
				$suite_matches++;
			}
		}
		if($suite_matches > 0)
		{
			echo PHP_EOL . PHP_EOL . pts_client::cli_just_bold('TEST SUITES') . PHP_EOL . $suite_results . pts_strings::plural_handler($suite_matches, 'Suite') . ' Matching';
		}

		// RESULT SEARCH
		$result_matches = 0;
		$result_results = null;
		foreach(pts_client::saved_test_results() as $saved_results_identifier)
		{
			$result_file = new pts_result_file($saved_results_identifier);

			// TODO Add support for searching contained hardware/software of system
			if(($title = $result_file->get_title()) != null && (stripos($result_file->get_title(), $search_query) !== false || stripos($result_file->get_identifier(), $search_query) !== false || stripos($test_suite->get_description(), $search_query) !== false))
			{
				$result_results .= sprintf('%-30ls - %-35ls', pts_client::cli_just_bold($result_file->get_identifier()), $result_file->get_title()) . PHP_EOL;
				$result_matches++;
			}
		}
		if($result_matches > 0)
		{
			echo PHP_EOL . PHP_EOL . pts_client::cli_just_bold('TEST RESULTS') . PHP_EOL . $result_results . pts_strings::plural_handler($result_matches, 'Test Result') . ' Matching';
		}

		echo PHP_EOL . PHP_EOL;
	}
}

?>
