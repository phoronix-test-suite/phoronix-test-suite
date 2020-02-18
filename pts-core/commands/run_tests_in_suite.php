<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2020, Phoronix Media
	Copyright (C) 2010 - 2020, Michael Larabel

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

class run_tests_in_suite implements pts_option_interface
{
	const doc_section = 'Testing';
	const doc_description = 'This option can be used if you wish to run all of the tests found in a supplied suite, but you wish to re-configure each of the test options rather than using the defaults supplied by the suite.';

	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array('pts_test_suite', 'is_suite'), null)
		);
	}
	public static function run($r)
	{
		$to_run = array();

		foreach(pts_types::identifiers_to_test_profile_objects($r, false, true) as $test_profile)
		{
			pts_arrays::unique_push($to_run, $test_profile);
		}

		$run_manager = new pts_test_run_manager();
		$run_manager->standard_run($to_run);
	}
}

?>
