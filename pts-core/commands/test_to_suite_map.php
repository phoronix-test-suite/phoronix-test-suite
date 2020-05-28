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

class test_to_suite_map implements pts_option_interface
{
	const doc_section = 'Information';
	const doc_description = 'This option will list all test profiles and any test suites each test belongs to.';

	public static function run($r)
	{
		pts_client::$display->generic_heading('Available Tests');

		$test_count = 0;
		foreach(pts_openbenchmarking::available_tests(false) as $identifier)
		{
			$table = array();
			$test_profile = new pts_test_profile($identifier);
			$table[] = array($identifier, pts_client::cli_just_bold($test_profile->get_title()));
			$suites_containing_test = pts_test_suites::suites_containing_test_profile($test_profile);
			if(!empty($suites_containing_test))
			{
				foreach($suites_containing_test as $suite)
				{
					$table[] = array($suite->get_identifier(false), pts_client::cli_just_italic($suite->get_title()));
				}
			}

			echo pts_user_io::display_text_table($table) . PHP_EOL . PHP_EOL;
		}

	}
}

?>
