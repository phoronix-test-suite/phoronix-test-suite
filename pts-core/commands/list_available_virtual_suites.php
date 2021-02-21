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

class list_available_virtual_suites implements pts_option_interface
{
	const doc_section = 'Information';
	const doc_description = 'This option will list all available virtual test suites that can be dynamically created based upon the available tests from enabled OpenBenchmarking.org repositories.';

	public static function command_aliases()
	{
		return array('list_virtual_suites');
	}
	public static function run($r)
	{
		pts_client::$display->generic_heading('Available Virtual Suites');

		$table = array();
		foreach(pts_virtual_test_suite::available_virtual_suites() as $virtual_suite)
		{
			$size = count($virtual_suite->get_contained_test_profiles());

			if($size > 0)
			{
				$table[] = array($virtual_suite->get_identifier(), pts_client::cli_just_bold($virtual_suite->get_title()), pts_client::cli_just_italic($size . ' Tests'));
			}
		}
		echo pts_user_io::display_text_table($table) . PHP_EOL;
	}
}

?>
