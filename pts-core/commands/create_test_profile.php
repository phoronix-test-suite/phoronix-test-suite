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
			$input = pts_user_io::prompt_user_input('Enter an identifier/name for the test profile', false, false);
			$input = pts_validation::string_to_sanitized_test_profile_base($input);

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
			else if(pts_results::is_saved_result_file($input))
			{
				$is_valid = false;
				echo 'There is already a result file named ' . $input . '.' . PHP_EOL;
			}
		}
		while(!$is_valid);

		$tp_identifier = 'local/'. $input;
		$tp_path = PTS_TEST_PROFILE_PATH . $tp_identifier;
		echo 'Creating test profile: ' . $tp_identifier . ' @ ' . $tp_path . PHP_EOL;
		pts_file_io::mkdir($tp_path);

		$types = pts_validation::process_xsd_types();

		pts_client::$display->generic_heading('test-definition.xml Creation');
		$writer = new nye_XmlWriter();
		pts_validation::xsd_to_cli_creator(pts_openbenchmarking::openbenchmarking_standards_path() . 'schemas/test-profile.xsd', $writer, $types);
		$writer->saveXMLFile($tp_path . '/test-definition.xml');
		echo 'Generated: ' . $tp_path . '/test-definition.xml' . PHP_EOL;

		pts_client::$display->generic_heading('downloads.xml Creation');
		$writer = new nye_XmlWriter();
		do
		{
			pts_validation::xsd_to_cli_creator(pts_openbenchmarking::openbenchmarking_standards_path() . 'schemas/test-profile-downloads.xsd', $writer, $types);
		}
		while(pts_user_io::prompt_bool_input('Add another file/download?', -1));
		$writer->saveXMLFile($tp_path . '/downloads.xml');
		echo 'Generated: ' . $tp_path . '/downloads.xml' . PHP_EOL;

		/*
		pts_client::$display->generic_heading('results-definition.xml Creation');
		$writer = new nye_XmlWriter();
		pts_validation::xsd_to_cli_creator(pts_openbenchmarking::openbenchmarking_standards_path() . 'schemas/results-parser.xsd', $writer, $types);
		$writer->saveXMLFile($tp_path . '/results-definition.xml');
		echo 'Generated: ' . $tp_path . '/results-definition.xml' . PHP_EOL;
		*/

		pts_validation::generate_test_profile_file_templates($tp_identifier, $tp_path);
	}
}

?>
