<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2012 - 2020, Phoronix Media
	Copyright (C) 2012 - 2020, Michael Larabel

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

class list_recommended_tests implements pts_option_interface
{
	const doc_section = 'OpenBenchmarking.org';
	const doc_description = 'This option will list recommended test profiles for benchmarking sorted by hardware sub-system. The recommended tests are determined via querying OpenBenchmarking.org and determining the most popular tests for a given environment based upon the number of times a test profile has been downloaded, the number of test results available on OpenBenchmarking.org for a given test profile, the age of the test profile, and other weighted factors.';

	public static function run($r)
	{
		pts_client::$display->generic_heading('Recommended OpenBenchmarking.org Test Profiles');

		$tests = array();
		foreach(pts_openbenchmarking::available_tests(false) as $identifier)
		{
			$repo = substr($identifier, 0, strpos($identifier, '/'));
			$id = substr($identifier, strlen($repo) + 1);
			$repo_index = pts_openbenchmarking::read_repository_index($repo);
			if((!empty($repo_index['tests'][$id]['supported_platforms']) && !in_array(phodevi::os_under_test(), $repo_index['tests'][$id]['supported_platforms'])) || empty($repo_index['tests'][$id]['title']))
			{
				// Don't show unsupported tests
				continue;
			}
			if(!empty($repo_index['tests'][$id]['status']) && $repo_index['tests'][$id]['status'] != 'Verified')
			{
				// Don't show unsupported tests
				continue;
			}

			if($repo_index['tests'][$id]['last_updated'] < (time() - (60 * 60 * 24 * 365)))
			{
				// Don't show tests not actively maintained
				continue;
			}

			if(!isset($tests[$repo_index['tests'][$id]['test_type']]))
			{
				$tests[$repo_index['tests'][$id]['test_type']] = array();
			}

			$tests[$repo_index['tests'][$id]['test_type']][$identifier] = $repo_index['tests'][$id];
		}

		foreach($tests as $subsystem => $test_json)
		{
			uasort($test_json, array('pts_openbenchmarking_client', 'compare_test_json_download_counts'));
			$test_json = array_slice($test_json, 0, 10);
			pts_client::$display->generic_heading($subsystem . ' Tests');
			$table = array();
			foreach($test_json as $identifier => $test_individual_json)
			{
				$table[] = array($identifier, pts_client::cli_just_bold($test_individual_json['title']));
			}
			echo pts_user_io::display_text_table($table);
		}
	}
}

?>
