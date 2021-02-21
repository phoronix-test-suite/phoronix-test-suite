<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2011 - 2020, Phoronix Media
	Copyright (C) 2011 - 2020, Michael Larabel

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

class recently_added_tests implements pts_option_interface
{
	const doc_section = 'OpenBenchmarking.org';
	const doc_description = 'This option will list the most recently added (newest) test profiles.';

	public static function command_aliases()
	{
		return array('newest_tests');
	}
	public static function run($r)
	{
		pts_client::$display->generic_heading('Recently Added OpenBenchmarking.org Tests');
		$table = array();
		foreach(pts_openbenchmarking_client::recently_added_tests(20) as $added => $test_identifier)
		{
			$test_profile = new pts_test_profile($test_identifier);
			$table[] = array(pts_client::cli_just_bold($test_profile->get_identifier(false)), pts_client::cli_just_bold($test_profile->get_title()));
			$table[] = array(pts_client::cli_just_italic(date('d F Y', $added)), $test_profile->get_test_hardware_type() . ' Test');
			$table[] = array('', '');
		}
		echo pts_user_io::display_text_table($table) . PHP_EOL;
	}
}

?>
