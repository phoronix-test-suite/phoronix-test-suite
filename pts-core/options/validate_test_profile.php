<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel

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
	public static function required_function_sets()
	{
		return array("validation");
	}
	public static function run($r)
	{
		foreach(pts_contained_tests($r, true, true, true) as $test_identifier)
		{
			echo pts_string_header($test_identifier);
			$validation_errors = array();
			$validation_warnings = array();

			$error_empty_tags = array(
			array(P_TEST_TITLE, "A title tag is required for standard test profiles."),
			array(P_TEST_PTSVERSION, "A version tag is required for standard test profiles."),
			array(P_TEST_HARDWARE_TYPE, "A hardware type tag is required for standard test profiles."),
			array(P_TEST_MAINTAINER, "Phoronix Media requires a maintainer tag for standard test profiles."),
			array(P_TEST_LICENSE, "Phoronix Media requires a license tag for standard test profiles."),
			array(P_TEST_STATUS, "Phoronix Media requires a status tag for standard test profiles."),
			array(P_TEST_DESCRIPTION, "Phoronix Media requires a description tag for standard test profiles."),
			array(P_TEST_SCALE, "A scale tag is required for most standard test profiles."),
			array(P_TEST_PROPORTION, "A proportion tag is required for most standard test profiles.")
			);

			$warning_empty_tags = array(
			array(P_TEST_SOFTWARE_TYPE, "A tag for the software program's type is recommended for standard test profiles."),
			array(P_TEST_ENVIRONMENTSIZE, "A tag for the approximate disk size needed (in MB) for the test profile is recommended."),
			array(P_TEST_PROJECTURL, "A tag for the web-site URL of the tested software is recommended.")
			);

		 	$test_parser = new pts_test_tandem_XmlReader($test_identifier);

			// Checks for missing tag errors and warnings
			pts_validation_check_xml_tags($test_parser, $error_empty_tags, $validation_errors);
			pts_validation_check_xml_tags($test_parser, $warning_empty_tags, $validation_warnings);

			// Check for other test profile problems
			foreach(pts_objects_test_downloads($test_identifier) as $package_download)
			{
				$download_urls = $package_download->get_download_url_array();

				if(count($download_urls) < 2)
				{
					array_push($validation_warnings, array($package_download->get_filename(), "Multiple file mirrors (delimited in the downloads.xml tag by a comma) are recommended for redundancy purposes."));
				}
			}

			if(count($validation_errors) == 0 && count($validation_warnings) == 0)
			{
				echo "\nNo errors or warnings found with this test profile.\n\n";
			}
			else
			{
				pts_validation_print_problem("ERROR", $validation_errors);
				pts_validation_print_problem("WARNING", $validation_warnings);
				echo "\n";
			}
		}
	}
}

?>
