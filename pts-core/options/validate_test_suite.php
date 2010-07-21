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
	public static function run($r)
	{
		if(pts_is_suite($r[0]))
		{
			$suite_identifier = $r[0];

			pts_client::$display->generic_heading($suite_identifier);
			$validation_errors = array();
			$validation_warnings = array();

			$error_empty_tags = array(
			array(P_SUITE_TITLE, "A title tag for the suite is required."),
			array(P_SUITE_VERSION, "A version tag for the suite is required."),
			array(P_SUITE_DESCRIPTION, "A description tag for the suite is required."),
			array(P_SUITE_MAINTAINER, "A maintainer tag for the suite is required."),
			array(P_SUITE_TYPE, "A type tag for the suite is required."),
			);

			$warning_empty_tags = array(

			);

		 	$suite_parser = new pts_suite_tandem_XmlReader($suite_identifier);

			// Checks for missing tag errors and warnings
			pts_validation::check_xml_tags($suite_parser, $error_empty_tags, $validation_errors);
			pts_validation::check_xml_tags($suite_parser, $warning_empty_tags, $validation_warnings);

			// Other checks
			$contained_tests = $suite_parser->getXMLArrayValues(P_SUITE_TEST_NAME);

			if(count($contained_tests) == 0)
			{
				array_push($validation_errors, array(P_SUITE_TEST_NAME, "No tags of tests to run in this suite were found."));
			}
			else
			{
				foreach($contained_tests as $test)
				{
					if(!pts_is_run_object($test))
					{
						array_push($validation_errors, array($test, $test . " is not a recognized test or suite."));
					}
				}
			}


			if(count($validation_errors) == 0 && count($validation_warnings) == 0)
			{
				echo "\nNo errors or warnings found with this suite.\n\n";
			}
			else
			{
				pts_validation::print_issue("ERROR", $validation_errors);
				pts_validation::print_issue("WARNING", $validation_warnings);
				echo "\n";
			}
		}
		else
		{
			echo "\n" . $r[0] . " is not a suite.\n\n";
		}
	}
}

?>
