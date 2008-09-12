<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts-functions_types.php: Functions needed for type handling of tests/suites.

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

define("TYPE_TEST", "TEST"); // Type is test
define("TYPE_TEST_SUITE", "TEST_SUITE"); // Type is a test suite

function is_suite($object)
{
	$type = pts_test_type($object);

	return $type == TYPE_TEST_SUITE;
}
function is_test($object)
{
	$type = pts_test_type($object);

	return $type == TYPE_TEST;
}
function pts_locations_tests($object)
{
	// Provide an array containing the location(s) of all test(s) for the supplied object name
	$tests = array();

	if(is_suite($object)) // Object is suite
	{
		$xml_parser = new tandem_XmlReader(@file_get_contents(XML_SUITE_DIR . $object . ".xml"));
		$tests_in_suite = array_unique($xml_parser->getXMLArrayValues(P_SUITE_TEST_NAME));

		foreach($tests_in_suite as $test)
			foreach(pts_locations_tests($test) as $sub_test)
				array_push($tests, $sub_test);
	}
	else if(is_test($object)) // Object is a test
	{
		if(TYPE_TEST == $object)
			array_push($tests, XML_TEST_DIR . $object . ".xml");
	}
	else if(is_file(($file_path = pts_input_correct_results_path($object)))) // Object is a local file
	{
		$xml_parser = new tandem_XmlReader($file_path);
		$tests_in_file = $xml_parser->getXMLArrayValues(P_RESULTS_TEST_TESTNAME);

		foreach($tests_in_file as $test)
			foreach(pts_locations_tests($test) as $sub_test)
				array_push($tests, $sub_test);
	}
	else if(is_file(SAVE_RESULTS_DIR . $object . "/composite.xml")) // Object is a saved results file
	{
		$xml_parser = new tandem_XmlReader(SAVE_RESULTS_DIR . $object . "/composite.xml");
		$tests_in_save = $xml_parser->getXMLArrayValues(P_RESULTS_TEST_TESTNAME);

		foreach($tests_in_save as $test)
			foreach(pts_locations_tests($test) as $sub_test)
				array_push($tests, $sub_test);
	}
	else if(pts_is_global_id($TO_INSTALL)) // Object is a Phoronix Global file
	{
		$xml_parser = new tandem_XmlReader(pts_global_download_xml($TO_INSTALL));
		$tests_in_global = $xml_parser->getXMLArrayValues(P_RESULTS_TEST_TESTNAME);

		foreach($tests_in_global as $test)
			foreach(pts_locations_tests($test) as $sub_test)
				array_push($tests, $sub_test);
	}

	return array_unique($tests);
}

?>
