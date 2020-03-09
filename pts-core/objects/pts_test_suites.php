<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2020, Phoronix Media
	Copyright (C) 2008 - 2020, Michael Larabel

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

class pts_test_suites
{
	public static function all_suites($only_show_maintained_suites = false, $download_suites_if_needed = false)
	{
		return array_merge(pts_openbenchmarking::available_suites($download_suites_if_needed, $only_show_maintained_suites), pts_test_suites::local_suites());
	}
	public static function local_suites()
	{
		$local_suites = array();
		foreach(pts_file_io::glob(PTS_TEST_SUITE_PATH . 'local/*/suite-definition.xml') as $path)
		{
			$local_suites[] = 'local/' . basename(dirname($path));
		}

		return $local_suites;
	}
	public static function suites_on_disk($return_object = false)
	{
		if(defined('PTS_TEST_SUITE_PATH') && is_dir(PTS_TEST_SUITE_PATH))
		{
			$suite_dir = PTS_TEST_SUITE_PATH;
		}
		else if(defined('PTS_INTERNAL_OB_CACHE') && is_dir(PTS_INTERNAL_OB_CACHE . 'test-suites/'))
		{
			$suite_dir = PTS_INTERNAL_OB_CACHE . 'test-suites/';
		}
		else
		{
			return array();
		}


		$local_suites = array();
		foreach(pts_file_io::glob($suite_dir . '*/*/suite-definition.xml') as $path)
		{
			$dir = explode('/', dirname($path));

			if(count($dir) > 2)
			{
				$test = array_pop($dir);
				$repo = array_pop($dir);
				$test_suite = new pts_test_suite($repo . '/' . $test);
				if($test_suite->get_title() != null)
				{
					$local_suites[$test_suite->get_title()] = $return_object ? $test_suite : ($repo . '/' . $test);
				}
			}
		}

		return $local_suites;
	}
	public static function suites_in_result_file(&$result_file, $allow_partial = false, $upper_limit = 0)
	{
		$tests_in_result_file = array();
		$suites_in_result_file = array();
		foreach($result_file->get_contained_test_profiles() as $tp)
		{
			pts_arrays::unique_push($tests_in_result_file, $tp->get_identifier(false));
		}

		foreach(pts_test_suites::suites_on_disk(true) as $suite)
		{
			$contained_tests = $suite->get_contained_test_identifiers(false);
			$suites_in_result_file[$suite->get_identifier()] = array();
			foreach($contained_tests as $ct)
			{
				if(in_array($ct, $tests_in_result_file))
				{
					$suites_in_result_file[$suite->get_identifier()][] = $ct;
				}
			}

			if($allow_partial)
			{
				if(count($suites_in_result_file[$suite->get_identifier()]) < 2)
				{
					unset($suites_in_result_file[$suite->get_identifier()]);
				}
			}
			else
			{
				if(count($suites_in_result_file[$suite->get_identifier()]) < count($contained_tests))
				{
					unset($suites_in_result_file[$suite->get_identifier()]);
				}
			}

			if($upper_limit > 0 && isset($suites_in_result_file[$suite->get_identifier()]) && count($suites_in_result_file[$suite->get_identifier()]) > $upper_limit)
			{
				unset($suites_in_result_file[$suite->get_identifier()]);
			}
		}

		return $suites_in_result_file;
	}
	public static function suites_containing_test_profile(&$test_profile)
	{
		$suites_containing_test = array();

		foreach(pts_test_suites::suites_on_disk(true) as $suite)
		{
			$contained_tests = $suite->get_contained_test_identifiers(false);
			if(in_array($test_profile->get_identifier(false), $contained_tests))
			{
				$suites_containing_test[] = $suite;
			}
		}

		return $suites_containing_test;
	}
}

?>
