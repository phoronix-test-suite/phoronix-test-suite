<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2012, Phoronix Media
	Copyright (C) 2012, Michael Larabel

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
		$test_count = 0;
		$recommendation_index = pts_openbenchmarking::make_openbenchmarking_request('recommended_tests_index');
		$recommendation_index = json_decode($recommendation_index, true);

		foreach($recommendation_index['recommended_tests'] as $subsystem => $tests)
		{
			pts_client::$display->generic_heading($subsystem . ' Tests');
			foreach($tests as $test)
			{
				echo sprintf('%-32ls - %-35ls', $test['test_profile'], $test['title']) . PHP_EOL;
			}

			$test_count++;
		}

		if($test_count == 0)
		{
			echo PHP_EOL . 'No tests found. Please check that you have Internet connectivity to download test profile data from OpenBenchmarking.org. The Phoronix Test Suite has documentation on configuring the network setup, proxy settings, and PHP network options. Please contact Phoronix Media if you continuing to experience problems.' . PHP_EOL . PHP_EOL;
		}
	}
}

?>
