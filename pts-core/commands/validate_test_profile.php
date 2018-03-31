<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2011, Phoronix Media
	Copyright (C) 2009 - 2011, Michael Larabel

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

class validate_test_profile implements pts_option_interface
{
	const doc_section = 'Asset Creation';
	const doc_description = 'This option can be used for validating a Phoronix Test Suite test profile as being compliant against the OpenBenchmarking.org specification.';

	public static function run($r)
	{
		foreach(pts_types::identifiers_to_test_profile_objects($r, true, true) as $test_profile)
		{
			pts_client::$display->generic_heading($test_profile);
			pts_validation::validate_test_profile($test_profile);
		}
	}
	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array('pts_test_profile', 'is_test_profile'), null)
		);
	}
}

?>
