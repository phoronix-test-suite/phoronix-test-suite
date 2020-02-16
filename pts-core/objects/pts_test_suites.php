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
	public static function suites_on_disk()
	{
		$local_suites = array();
		foreach(pts_file_io::glob(PTS_TEST_SUITE_PATH . '*/*/suite-definition.xml') as $path)
		{
			$dir = explode('/', dirname($path));
			if(count($dir) > 2)
			{
				$test = array_pop($dir);
				$repo = array_pop($dir);
				$test_suite = new pts_test_suite($repo . '/' . $test);
				if($test_suite->get_title() != null)
				{
					$local_suites[$test_suite->get_title()] = $repo . '/' . $test;
				}
			}
		}

		return $local_suites;
	}
}

?>
