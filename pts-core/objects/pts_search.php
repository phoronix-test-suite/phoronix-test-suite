<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2018 - 2019, Phoronix Media
	Copyright (C) 2018 - 2019, Michael Larabel

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


class pts_search
{
	public static function search_test_profiles($search_query)
	{
		$matches = array();
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
				$matches[] = new pts_test_profile($identifier);
			}
		}

		return $matches;
	}
	public static function search_local_test_profiles($search_query)
	{
		$matches = array();
		foreach(pts_tests::local_tests() as $identifier)
		{
			$test_profile = new pts_test_profile($identifier);

			if($test_profile->get_title() != null && pts_search::check_test_profile_match($test_profile, $search_query))
			{
				$matches[] = $test_profile;
			}
		}

		return $matches;
	}
	public static function check_test_profile_match(&$test_profile, $search_query)
	{
		return stripos($test_profile->get_title(), $search_query) !== false || stripos($test_profile->get_identifier(), $search_query) !== false || stripos($test_profile->get_description(), $search_query) !== false || in_array($search_query, $test_profile->get_internal_tags()) !== false;
	}
	public static function search_test_suites($search_query)
	{
		$matches = array();
		foreach(array_merge(pts_openbenchmarking::available_suites(false), pts_test_suites::local_suites()) as $identifier)
		{
			$test_suite = new pts_test_suite($identifier);
			if($test_suite->get_title() != null && pts_search::check_test_suite_match($test_suite, $search_query))
			{
				$matches[] = $test_suite;
			}
		}
		return $matches;
	}
	public static function check_test_suite_match(&$test_suite, $search_query)
	{
		return stripos($test_suite->get_title(), $search_query) !== false || stripos($test_suite->get_identifier(), $search_query) !== false || stripos($test_suite->get_description(), $search_query) !== false || stripos(implode(' ', $test_suite->get_contained_test_identifiers(false)), $search_query) !== false;
	}
	public static function search_test_results($search_query)
	{
		$matches = array();
		foreach(pts_results::saved_test_results() as $saved_results_identifier)
		{
			$result_file = new pts_result_file($saved_results_identifier);

			if(pts_search::search_in_result_file($result_file, $search_query) !== false)
			{
				$matches[] = $result_file;
			}

		}
		return $matches;
	}
	public static function search_in_result_file(&$result_file, $search_query)
	{
		if($result_file->get_title() != null && (stripos($result_file->get_title(), $search_query) !== false || stripos($result_file->get_identifier(), $search_query) !== false || stripos($result_file->get_description(), $search_query) !== false))
		{
			return 'META';
		}
		if($result_file->contains_system_hardware($search_query))
		{
			return 'HARDWARE';
		}
		if($result_file->contains_system_software($search_query))
		{
			return 'SOFTWARE';
		}
		foreach($result_file->get_systems() as $s)
		{
			if(stripos($s->get_identifier(), $search_query) !== false)
			{
				return 'SYSTEM_IDENTIFIER';
			}
		}
		return false;
	}
	public static function search_test_results_for_tests($test_profile_identifiers)
	{
		$matches = array();
		foreach(pts_results::saved_test_results() as $saved_results_identifier)
		{
			$result_file = new pts_result_file($saved_results_identifier);
			foreach($test_profile_identifiers as $test_check)
			{
				if($result_file->contains_test($test_check))
				{
					$matches[] = $result_file;
					break;
				}
			}

		}
		return $matches;
	}
}

?>
