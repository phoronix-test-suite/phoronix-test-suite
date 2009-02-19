<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
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
define("TYPE_LOCAL_TEST", "LOCAL_TEST"); // Type is local test
define("TYPE_LOCAL_TEST_SUITE", "LOCAL_TEST_SUITE"); // Type is a test suite
define("TYPE_BASE_TEST", "BASE_TEST"); // Type is a base test

define("TYPE_VIRT_SUITE_SUBSYSTEM", "VIRT_SUITE_SUBSYSTEM"); // Type is a virtual suite for a subsystem
define("TYPE_VIRT_SUITE_ALL", "VIRT_SUITE_ALL");

function pts_is_run_object($object)
{
	return pts_is_test($object) || pts_is_suite($object);
}
function pts_is_suite($object)
{
	$type = pts_test_type($object);

	return $type == TYPE_TEST_SUITE || $type == TYPE_LOCAL_TEST_SUITE;
}
function pts_is_virtual_suite($object)
{
	return pts_location_virtual_suite($object) != false;
}
function pts_is_test($object)
{
	$type = pts_test_type($object);

	return $type == TYPE_TEST || $type == TYPE_LOCAL_TEST || $type == TYPE_BASE_TEST;
}
function pts_is_test_result($object)
{
	return is_file(SAVE_RESULTS_DIR . $object . "/composite.xml");
}
function pts_validate_local_test_profile($identifier)
{
	if(is_file(($lp = XML_PROFILE_LOCAL_DIR . $identifier . ".xml")))
	{
		$valid = true;

		if(is_file(($sp = XML_PROFILE_DIR . $identifier . ".xml")))
		{
			$lp_parser = new pts_test_tandem_XmlReader($lp);
			$sp_parser = new pts_test_tandem_XmlReader($sp);

			$lp_version = $lp_parser->getXMLValue(P_TEST_PTSVERSION);
			$sp_version = $sp_parser->getXMLValue(P_TEST_PTSVERSION);

			if(pts_version_newer($lp_version, $sp_version) == $sp_version)
			{
				// Standard test profile version newer than the local test profile version
				$valid = false;

				// Rename test profile since it's out of date
				pts_rename($lp, XML_PROFILE_LOCAL_DIR . $identifier . ".xml.old");
			}
			
		}
	}
	else
	{
		$valid = false;
	}

	return $valid;
}
function pts_validate_local_test_suite($identifier)
{
	if(is_file(($ls = XML_SUITE_LOCAL_DIR . $identifier . ".xml")))
	{
		$valid = true;

		if(is_file(($ss = XML_SUITE_DIR . $identifier . ".xml")))
		{
			$ls_parser = new pts_test_tandem_XmlReader($ls);
			$ss_parser = new pts_test_tandem_XmlReader($ss);

			$ls_version = $ls_parser->getXMLValue(P_SUITE_VERSION);
			$ss_version = $ss_parser->getXMLValue(P_SUITE_VERSION);

			if(pts_version_newer($ls_version, $ss_version) == $ss_version)
			{
				// Standard test suite version newer than the local test suite version
				$valid = false;

				// Rename test suite since it's out of date
				pts_rename($ls, XML_SUITE_LOCAL_DIR . $identifier . ".xml.old");
			}
			
		}
	}
	else
	{
		$valid = false;
	}

	return $valid;
}
function pts_test_type($identifier)
{
	// Determine type of test based on identifier
	static $cache;

	if(!isset($cache[$identifier]))
	{
		$test_type = false;

		if(!empty($identifier))
		{
			if(is_file(XML_PROFILE_LOCAL_DIR . $identifier . ".xml") && pts_validate_local_test_profile($identifier))
			{
				$test_type = TYPE_LOCAL_TEST;
			}
			else if(is_file(XML_SUITE_LOCAL_DIR . $identifier . ".xml") && pts_validate_local_test_suite($identifier))
			{
				$test_type = TYPE_LOCAL_TEST_SUITE;
			}
			else if(is_file(XML_PROFILE_DIR . $identifier . ".xml"))
			{
				$test_type = TYPE_TEST;
			}
			else if(is_file(XML_SUITE_DIR . $identifier . ".xml"))
			{
				$test_type = TYPE_TEST_SUITE;
			}
			else if(is_file(XML_PROFILE_CTP_BASE_DIR . $identifier . ".xml"))
			{
				$test_type = TYPE_BASE_TEST;
			}
		}

		$cache[$identifier] = $test_type;
	}

	return $cache[$identifier];
}
function pts_location_suite($identifier)
{
	static $cache;

	if(!isset($cache[$identifier]))
	{
		$location = false;

		if(pts_is_suite($identifier))
		{
			$type = pts_test_type($identifier);

			if($type == TYPE_TEST_SUITE)
			{
				$location = XML_SUITE_DIR . $identifier . ".xml";
			}
			else if($type == TYPE_LOCAL_TEST_SUITE)
			{
				$location = XML_SUITE_LOCAL_DIR . $identifier . ".xml";
			}
		}

		$cache[$identifier] = $location;
	}

	return $cache[$identifier];
}
function pts_location_virtual_suite($identifier)
{
	static $cache;

	if(!isset($cache[$identifier]))
	{
		$virtual_suite = false;

		// Ensure $identifier is not a real suite/test object
		if(count(pts_contained_tests($identifier, false, false)) == 0)
		{
			if($identifier == "all")
			{
				// All tests
				$virtual_suite = TYPE_VIRT_SUITE_ALL;
			}
			else
			{
				// Check if object is a subsystem test type
				foreach(pts_subsystem_test_types() as $type)
				{
					if(strtolower($type) == $identifier)
					{
						$virtual_suite = TYPE_VIRT_SUITE_SUBSYSTEM;
						break;
					}
				}
			}
		}

		$cache[$identifier] = $virtual_suite;
	}

	return $cache[$identifier];
}
function pts_location_test($identifier)
{
	static $cache;

	if(!isset($cache[$identifier]))
	{
		$location = false;

		if(pts_is_test($identifier))
		{
			$type = pts_test_type($identifier);

			if($type == TYPE_TEST)
			{
				$location = XML_PROFILE_DIR . $identifier . ".xml";
			}
			else if($type == TYPE_LOCAL_TEST)
			{
				$location = XML_PROFILE_LOCAL_DIR . $identifier . ".xml";
			}
			else if($type == TYPE_BASE_TEST)
			{
				$location = XML_PROFILE_CTP_BASE_DIR . $identifier . ".xml";
			}
		}

		$cache[$identifier] = $location;
	}

	return $cache[$identifier];
}
function pts_location_test_resources($identifier)
{
	static $cache;

	if(!isset($cache[$identifier]))
	{
		$location = false;

		if(pts_is_test($identifier))
		{
			$type = pts_test_type($identifier);

			if($type == TYPE_LOCAL_TEST && is_dir(TEST_RESOURCE_LOCAL_DIR . $identifier))
			{
				$location = TEST_RESOURCE_LOCAL_DIR . $identifier . "/";
			}
			else if($type == TYPE_BASE_TEST && is_dir(TEST_RESOURCE_CTP_BASE_DIR . $identifier))
			{
				$location = TEST_RESOURCE_CTP_BASE_DIR . $identifier . "/";
			}
			else if(is_dir(TEST_RESOURCE_DIR . $identifier))
			{
				// TYPE_TEST
				$location = TEST_RESOURCE_DIR . $identifier . "/";
			}
		}

		$cache[$identifier] = $location;
	}

	return $cache[$identifier];
}
function pts_test_extends_below($object)
{
	// Process Extensions / Cascading Test Profiles
	$extensions = array();
	$test_extends = $object;

	do
	{
		if(pts_is_test($test_extends))
		{
			$xml_parser = new pts_test_tandem_XmlReader($test_extends);
			$test_extends = $xml_parser->getXMLValue(P_TEST_CTPEXTENDS);

			if(!empty($test_extends))
			{
				if(!in_array($test_extends, $extensions) && pts_is_test($test_extends))
				{
					array_push($extensions, $test_extends);
				}
				else
				{
					$test_extends = null;
				}
			}
		}
		else
		{
			$test_extends = null;
		}
	}
	while(!empty($test_extends));

	return array_reverse($extensions);
}
function pts_contained_tests($objects, $include_extensions = false, $check_extended = true, $remove_duplicates = true)
{
	// Provide an array containing the location(s) of all test(s) for the supplied object name
	$tests = array();
	$objects = pts_to_array($objects);

	foreach($objects as $object)
	{
		if(pts_is_suite($object)) // Object is suite
		{
			$xml_parser = new pts_suite_tandem_XmlReader($object);
			$tests_in_suite = array_unique($xml_parser->getXMLArrayValues(P_SUITE_TEST_NAME));

			foreach($tests_in_suite as $test)
			{
				foreach(pts_contained_tests($test, $include_extensions) as $sub_test)
				{
					array_push($tests, $sub_test);
				}
			}
		}
		else if(pts_is_test($object)) // Object is a test
		{
			if($include_extensions)
			{
				foreach(pts_test_extends_below($object) as $extension)
				{
					if(!in_array($extension, $tests))
					{
						array_push($tests, $extension);
					}
				}
			}
			array_push($tests, $object);
		}
		else if(is_file(($file_path = pts_input_correct_results_path($object)))) // Object is a local file
		{
			$xml_parser = new tandem_XmlReader($file_path);
			$tests_in_file = $xml_parser->getXMLArrayValues(P_RESULTS_TEST_TESTNAME);

			foreach($tests_in_file as $test)
			{
				foreach(pts_contained_tests($test, $include_extensions) as $sub_test)
				{
					array_push($tests, $sub_test);
				}
			}
		}
		else if(pts_is_test_result($object)) // Object is a saved results file
		{
			$xml_parser = new pts_results_tandem_XmlReader($object);
			$tests_in_save = $xml_parser->getXMLArrayValues(P_RESULTS_TEST_TESTNAME);

			foreach($tests_in_save as $test)
			{
				foreach(pts_contained_tests($test, $include_extensions) as $sub_test)
				{
					array_push($tests, $sub_test);
				}
			}
		}
		else if(pts_is_global_id($object)) // Object is a Phoronix Global file
		{
			$xml_parser = new tandem_XmlReader(pts_global_download_xml($object));
			$tests_in_global = $xml_parser->getXMLArrayValues(P_RESULTS_TEST_TESTNAME);

			foreach($tests_in_global as $test)
			{
				foreach(pts_contained_tests($test, $include_extensions) as $sub_test)
				{
					array_push($tests, $sub_test);
				}
			}
		}
		else if($check_extended && pts_is_virtual_suite($object))
		{
			foreach(pts_virtual_suite_tests($object) as $virt_test)
			{
				array_push($tests, $virt_test);
			}
		}
	}

	if($remove_duplicates)
	{
		$tests = array_unique($tests);
	}

	return $tests;
}
function pts_virtual_suite_tests($object)
{
	$virtual_suite_type = pts_location_virtual_suite($object);
	$contained_tests = array();

	switch($virtual_suite_type)
	{
		case TYPE_VIRT_SUITE_SUBSYSTEM:
			foreach(pts_supported_tests_array() as $test)
			{
				$xml_parser = new pts_test_tandem_XmlReader($test);
				$type = $xml_parser->getXMLValue(P_TEST_HARDWARE_TYPE);

				if(strtolower($type) == $object && pts_test_supported($test))
				{
					array_push($contained_tests, $test);
				}
			}
			break;
		case TYPE_VIRT_SUITE_ALL:
			foreach(pts_supported_tests_array() as $test)
			{
				array_push($contained_tests, $test);
			}
			break;
	}

	if(count($contained_tests) > 0)
	{
		pts_set_assignment_once("IS_DEFAULTS_MODE", true); // Use the defaults mode for the suite
	}

	return $contained_tests;
}
function pts_find_result_file($file, $check_global = true)
{
	// PTS Find A Saved File
	if(is_file($file))
	{
		$USE_FILE = $file;
	}
	else if(pts_is_test_result($file))
	{
		$USE_FILE = SAVE_RESULTS_DIR . $file . "/composite.xml";
	}
	else if($check_global && pts_is_global_id($file))
	{
		$USE_FILE = "http://www.phoronix-test-suite.com/global/pts-results-viewer.php?id=" . $file;
	}
	else
	{
		$USE_FILE = false;
	}

	return $USE_FILE;
}

?>
