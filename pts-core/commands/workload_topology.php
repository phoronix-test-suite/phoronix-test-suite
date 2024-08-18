<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2020, Phoronix Media
	Copyright (C) 2020, Michael Larabel

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

class workload_topology implements pts_option_interface
{
	const doc_section = 'Result Analysis';
	const doc_description = 'This option will read a saved test results file and print the test profiles contained within and their arrangement within different test suites for getting an idea as to the workload topology / make-up / logical groupings of the benchmarks being run.';

	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array('pts_types', 'is_result_file'), null)
		);
	}
	public static function run($r)
	{
		$result_file = new pts_result_file($r[0]);
		$test_profiles = array_unique($result_file->get_contained_test_profiles());

		foreach($test_profiles as $test_profile)
		{
			echo PHP_EOL . pts_client::cli_just_bold($test_profile->get_title()) . PHP_EOL;

			$suites_containing_test = pts_test_suites::suites_containing_test_profile($test_profile);
			if(!empty($suites_containing_test))
			{
				$table = array();
				foreach($suites_containing_test as $suite)
				{
					echo '   ' . $suite->get_title() . PHP_EOL;
				}
			}
		}
		echo PHP_EOL;
	}
}

?>
