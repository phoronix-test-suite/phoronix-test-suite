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
		return array_unique(array_merge(pts_openbenchmarking::available_suites($download_suites_if_needed, $only_show_maintained_suites), pts_test_suites::local_suites()));
	}
	public static function all_suites_cached($remove_redundant_versions = true)
	{
		$suites = array();
		foreach(pts_file_io::glob(PTS_TEST_SUITE_PATH . '*/*/suite-definition.xml') as $path)
		{
			$suite = str_replace(PTS_TEST_SUITE_PATH, '', dirname($path));
			$suite_short = $suite;
			if($remove_redundant_versions && ($c = strrpos($suite, '-')) && pts_strings::is_version(substr($suite, ($c + 1))))
			{
				$suite_short = substr($suite, 0, $c);
			}

			$suites[$suite_short] = $suite;
		}
		return $suites;
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
	public static function suites_on_disk($return_object = false, $skip_deprecated = true, $sort_by_size = false)
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

		static $cache;
		if(!isset($cache[$suite_dir][$return_object][$skip_deprecated][$sort_by_size]))
		{
		$local_suites = array();
		$suite_xml_files = pts_file_io::glob($suite_dir . '*/*/suite-definition.xml');
		sort($suite_xml_files);
		$skip_suites_deprecated = array();
		foreach($suite_xml_files as $path)
		{
			$dir = explode('/', dirname($path));

			if(count($dir) > 2)
			{
				$test = array_pop($dir);
				$repo = array_pop($dir);
				$test_suite = new pts_test_suite($repo . '/' . $test);

				if($test_suite->get_title() != null)
				{
					if($skip_deprecated && ($test_suite->is_deprecated() || in_array($test_suite->get_identifier(false), $skip_suites_deprecated) ))
					{
						$skip_suites_deprecated[] = $test_suite->get_identifier(false);
						continue;
					}
					if(isset($local_suites[$test_suite->get_identifier(false)]))
					{
						if($return_object)
						{
							if($local_suites[$test_suite->get_identifier(false)]->get_version() > $test_suite->get_version())
							{
								continue;
							}
						}
					}

					$local_suites[$test_suite->get_identifier(false)] = $return_object ? $test_suite : ($repo . '/' . $test);
				}
			}
		}

		if($return_object && !empty($local_suites) && $sort_by_size)
		{
			uasort($local_suites, function ($a, $b) { $a = $a->get_test_count(); $b = $b->get_test_count(); if($a == $b) return 0; return $a < $b ? 1 : -1;});
		}
		$cache[$suite_dir][$return_object][$skip_deprecated][$sort_by_size] = &$local_suites;
		}

		return $cache[$suite_dir][$return_object][$skip_deprecated][$sort_by_size];
	}
	public static function suites_in_result_file(&$result_file, $allow_partial = false, $upper_limit = 0)
	{
		$tests_in_result_file = array();
		$suites_in_result_file = array();
		foreach($result_file->get_contained_test_profiles() as $tp)
		{
			pts_arrays::unique_push($tests_in_result_file, $tp->get_identifier(false));
		}

		foreach(pts_test_suites::suites_on_disk(true, true) as $suite)
		{
			$contained_tests = $suite->get_contained_test_identifiers(false);
			$sb = basename($suite->get_identifier(false));
			$suites_in_result_file[$sb] = array($suite, array());
			foreach($contained_tests as $ct)
			{
				if(in_array($ct, $tests_in_result_file))
				{
					$suites_in_result_file[$sb][1][] = $ct;
				}
			}

			if($allow_partial)
			{
				if(count($contained_tests) == 1 && count($suites_in_result_file[$sb][1]))
				{
					// Only 1 test profile in suite (e.g. browsers), so allow this combination and not fail below check
				}
				else if(count($suites_in_result_file[$sb][1]) < 2)
				{
					unset($suites_in_result_file[$sb]);
				}
			}
			else
			{
				if(count($suites_in_result_file[$sb][1]) < count($contained_tests))
				{
					unset($suites_in_result_file[$sb]);
				}
			}

			if($upper_limit > 0 && isset($suites_in_result_file[$sb]) && count($suites_in_result_file[$sb][1]) > $upper_limit)
			{
				unset($suites_in_result_file[$sb]);
			}
		}

		$ctp = $result_file->get_contained_test_profiles(true);
		foreach(pts_virtual_test_suite::get_external_dependency_suites() as $suite_identifier => $data)
		{
			if(isset($suites_in_result_file[$suite_identifier]))
			{
				continue;
			}
			$suites_in_result_file[$suite_identifier] = array(new pts_virtual_test_suite($suite_identifier), array());
			foreach($ctp as $tp)
			{
				if(in_array($data[0], $tp->get_external_dependencies()))
				{
					$suites_in_result_file[$suite_identifier][1][] = $tp->get_identifier(false);
				}
			}
			if(count($suites_in_result_file[$suite_identifier][1]) < 2)
			{
				unset($suites_in_result_file[$suite_identifier]);
			}
		}

		return $suites_in_result_file;
	}
	public static function suites_containing_test_profile(&$test_profile)
	{
		$suites_containing_test = array();

		foreach(pts_test_suites::suites_on_disk(true, true, true) as $suite)
		{
			$contained_tests = $suite->get_contained_test_identifiers(false);
			if(in_array($test_profile->get_identifier(false), $contained_tests))
			{
				$suites_containing_test[] = $suite;
			}
		}

		return $suites_containing_test;
	}
	public static function test_to_common_suites(&$test_profile)
	{
		static $tests_to_suite_map = array();
		$test_identifier = $test_profile->get_identifier();
		if(!isset($tests_to_suite_map[$test_identifier]))
		{
			$suites_containing_test = empty($test_identifier) ? array() : pts_test_suites::suites_containing_test_profile($test_profile);
			if(!empty($suites_containing_test))
			{
				foreach($suites_containing_test as &$sct)
				{
					$sct = $sct->get_identifier(false);
				}
			}
			$tests_to_suite_map[$test_identifier] = implode(' ', $suites_containing_test);
		}
		return $tests_to_suite_map[$test_identifier];
	}
}

?>
