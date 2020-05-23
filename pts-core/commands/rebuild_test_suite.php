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

class rebuild_test_suite implements pts_option_interface
{
	const doc_section = 'Asset Creation';
	const doc_description = 'This option will regenerate the local test suite XML file against the OpenBenchmarking.org specification. This can be used to clean up any existing XML syntax / styling issues, etc.';

	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array('pts_test_suite', 'is_suite'), null)
		);
	}
	public static function run($r)
	{
		if(($test_suite = new pts_test_suite($r[0])) != false)
		{
			pts_client::$display->generic_heading($r[0]);
			$bind_versions = pts_user_io::prompt_bool_input('Bind current test profile versions to test suite');
			if($test_suite->save_xml(null, null, $bind_versions) != false)
			{
				echo PHP_EOL . PHP_EOL . 'Saved -- to run this suite, type: phoronix-test-suite benchmark ' . $test_suite->get_identifier() . PHP_EOL . PHP_EOL;
			}
		}
	}
}

?>
