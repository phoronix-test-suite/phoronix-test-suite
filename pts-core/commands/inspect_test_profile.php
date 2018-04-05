<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2018, Phoronix Media
	Copyright (C) 2018, Michael Larabel

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

class inspect_test_profile implements pts_option_interface
{
	const doc_section = 'Asset Creation';
	const doc_description = 'This option can be used for inspecting a Phoronix Test Suite test profile with providing inside details on test profiles for debugging / evaluation / learning purposes.';

	public static function run($r)
	{
		$types = pts_validation::process_xsd_types();
		foreach(pts_types::identifiers_to_test_profile_objects($r, true, true) as $test_profile)
		{
			pts_client::$display->generic_heading($test_profile . ' - test-definition.xml');
			pts_validation::process_xsd_display_chart(pts_openbenchmarking::openbenchmarking_standards_path() . 'schemas/test-profile.xsd', $test_profile, $types);
			pts_client::$display->generic_heading($test_profile . ' - downloads.xml');
			pts_validation::process_xsd_display_chart(pts_openbenchmarking::openbenchmarking_standards_path() . 'schemas/test-profile-downloads.xsd', $test_profile, $types);
			pts_client::$display->generic_heading($test_profile . ' - results-definition.xml');
			pts_validation::process_xsd_display_chart(pts_openbenchmarking::openbenchmarking_standards_path() . 'schemas/results-parser.xsd', $test_profile, $types);
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
