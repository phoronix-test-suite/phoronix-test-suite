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

class create_test_profile implements pts_option_interface
{
	const doc_section = 'Asset Creation';
	const doc_description = 'This option can be used for creating a Phoronix Test Suite test profile by answering questions about the test for constructing the test profile XML meta-data and handling other boiler-plate basics for getting started in developing new tests.';

	public static function run($r)
	{
		echo 'The create-test-profile helper will attempt to walk you through creating the basics of creating a new test profile for execution by the Phoronix Test Suite. Follow the prompts to begin filling out the XML meta-data that defines a test profile.' . PHP_EOL;

		do
		{
			$is_valid = true;
			$input = pts_user_io::prompt_user_input('Enter an identifier/name for the test profile: ', false, false);
			$input = pts_strings::keep_in_string(str_replace(' ', '-', strtolower($input)), pts_strings::CHAR_LETTER | pts_strings::CHAR_NUMERIC | pts_strings::CHAR_DASH);

			if(pts_test_profile::is_test_profile($input))
			{
				$is_valid = false;
				echo 'There is already a ' . $input . ' test profile.' . PHP_EOL;
			}
			else if(pts_test_suite::is_suite($input))
			{
				$is_valid = false;
				echo 'There is already a test suite named ' . $input . '.' . PHP_EOL;
			}
			else if(pts_result_file::is_test_result_file($input))
			{
				$is_valid = false;
				echo 'There is already a result file named ' . $input . '.' . PHP_EOL;
			}
		}
		while(!$is_valid);

		$tp_identifier = 'local/'. $input;
		echo 'Creating test profile: ' . $tp_identifier . PHP_EOL;
		pts_file_io::mkdir(PTS_TEST_PROFILE_PATH . $tp_identifier);

		$types = pts_validation::process_xsd_types();
		$test_profile = new pts_test_profile();
		pts_client::$display->generic_heading('test-definition.xml Creation');
		pts_validation::xsd_to_cli_creator(pts_openbenchmarking::openbenchmarking_standards_path() . 'schemas/test-profile.xsd', $test_profile, $types);
		$test_profile_writer = new pts_test_profile_writer();
		$test_profile_writer->rebuild_test_profile($test_profile);
		$test_profile_writer->save_xml(PTS_TEST_PROFILE_PATH . $tp_identifier . '/test-definition.xml');
		echo 'Generated: ' . PTS_TEST_PROFILE_PATH . $tp_identifier . '/test-definition.xml' . PHP_EOL;

		return;
		pts_client::$display->generic_heading('downloads.xml Creation');
		pts_validation::xsd_to_cli_creator(pts_openbenchmarking::openbenchmarking_standards_path() . 'schemas/test-profile-downloads.xsd', $test_profile, $types);
		pts_client::$display->generic_heading('results-definition.xml Creation');
		pts_validation::xsd_to_cli_creator(pts_openbenchmarking::openbenchmarking_standards_path() . 'schemas/results-parser.xsd', $test_profile, $types);

	}
}

?>
