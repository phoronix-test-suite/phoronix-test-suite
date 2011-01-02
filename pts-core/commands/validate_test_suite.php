<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2010, Phoronix Media
	Copyright (C) 2009 - 2010, Michael Larabel

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

class validate_test_suite implements pts_option_interface
{
	const doc_section = 'Asset Creation';
	const doc_description = 'This option can be used for validating a Phoronix Test Suite test suite as being compliant against the OpenBenchmarking.org specification.';

	public static function run($r)
	{
		if(($test_suite = pts_types::identifier_to_object($r[0])) != false)
		{
			pts_client::$display->generic_heading($r[0]);
			if($test_suite->xml_parser->getFileLocation() == null)
			{
				echo PHP_EOL . 'ERROR: The file location of the XML test suite source could not be determined.' . PHP_EOL;
				return false;
			}


			// Validate the XML against the XSD Schemas
			libxml_clear_errors();

			// First rewrite the main XML file to ensure it is properly formatted, elements are ordered according to the schema, etc...
			$test_suite_writer = new pts_test_suite_writer();
			$test_suite_writer->add_suite_information_from_reader($test_suite->xml_parser);
			$test_suite_writer->add_to_suite_from_reader($test_suite->xml_parser);

			$test_suite_new = new pts_test_suite($test_suite_writer->get_xml());
			$valid = $test_suite_new->xml_parser->validate();

			if($valid == false)
			{
				echo PHP_EOL . 'Errors occurred parsing the main XML.' . PHP_EOL;
				pts_validation::process_libxml_errors();
				return false;
			}
			else
			{
				echo PHP_EOL . 'Test Suite XML Is Valid.' . PHP_EOL;
			}

			$test_suite_writer->save_xml($test_suite->xml_parser->getFileLocation());
			$suite_identifier = basename($test_suite->xml_parser->getFileLocation(), '.xml');

			$zip_file = PTS_OPENBENCHMARKING_SCRATCH_PATH . $suite_identifier . '-' . $test_suite_new->get_version() . '.zip';
			$zip_created = pts_compression::zip_archive_create($zip_file, $test_suite->xml_parser->getFileLocation());

			if($zip_created == false)
			{
				echo PHP_EOL . 'Failed to create zip file.' . PHP_EOL;
				return false;
			}

			$zip = new ZipArchive();
			$zip->open($zip_file);
			$zip->renameName(basename($test_suite->xml_parser->getFileLocation()), 'suite-definition.xml');
			$zip->close();

			// TODO: chmod +x the .sh files, appropriate permissions elsewhere
			//unlink($zip_file);
		}
	}
}

?>
