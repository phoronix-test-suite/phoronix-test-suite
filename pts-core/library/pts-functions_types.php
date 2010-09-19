<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel
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

function pts_identifier_type($identifier, $rewrite_cache = false)
{
	// Determine type of test based on identifier
	static $cache;

	if(!isset($cache[$identifier]) || $rewrite_cache)
	{
		$test_type = false;

		if(!empty($identifier))
		{
			if(is_file(XML_SUITE_LOCAL_DIR . $identifier . ".xml") && pts_validate_local_test_suite($identifier))
			{
				$test_type = "TYPE_LOCAL_TEST_SUITE";
			}
			else if(is_file(XML_PROFILE_DIR . $identifier . ".xml"))
			{
				$test_type = "TYPE_TEST";
			}
			else if(is_file(XML_SUITE_DIR . $identifier . ".xml"))
			{
				$test_type = "TYPE_TEST_SUITE";
			}
			else if(is_file(XML_PROFILE_CTP_BASE_DIR . $identifier . ".xml"))
			{
				$test_type = "TYPE_BASE_TEST";
			}
		}

		$cache[$identifier] = $test_type;
	}

	return $cache[$identifier];
}
function pts_is_run_object($object)
{
	return pts_is_test($object) || pts_is_suite($object);
}
function pts_is_suite($object)
{
	$type = pts_identifier_type($object);

	return $type == "TYPE_TEST_SUITE" || $type == "TYPE_LOCAL_TEST_SUITE";
}
function pts_is_virtual_suite($object)
{
	return pts_location_virtual_suite($object) != false;
}
function pts_is_test($object)
{
	$type = pts_identifier_type($object);

	return $type == "TYPE_TEST" || $type == "TYPE_BASE_TEST";
}
function pts_is_base_test($object)
{
	$type = pts_identifier_type($object);

	return $type == "TYPE_BASE_TEST";
}
function pts_is_test_result($identifier)
{
	return is_file(SAVE_RESULTS_DIR . $identifier . "/composite.xml");
}
function pts_validate_local_test_suite($identifier)
{
	if(is_file(($ls = XML_SUITE_LOCAL_DIR . $identifier . ".xml")))
	{
		$valid = true;

		if(is_file(($ss = XML_SUITE_DIR . $identifier . ".xml")))
		{
			$ls_suite = new pts_test_suite($ls);
			$ls_version = $ls_suite->get_version();

			$ss_suite = new pts_test_suite($ss);
			$ss_version = $ss_suite->get_version();

			if(pts_version_newer($ls_version, $ss_version) == $ss_version)
			{
				// Standard test suite version newer than the local test suite version
				$valid = false;

				// Rename test suite since it's out of date
				rename($ls, XML_SUITE_LOCAL_DIR . $identifier . ".xml.old");
			}
			
		}
	}
	else
	{
		$valid = false;
	}

	return $valid;
}
function pts_location_suite($identifier, $rewrite_cache = false)
{
	static $cache;

	if(!isset($cache[$identifier]) || $rewrite_cache)
	{
		$location = false;

		if(pts_is_suite($identifier))
		{
			$type = pts_identifier_type($identifier);

			switch($type)
			{
				case "TYPE_TEST_SUITE":
					$location = XML_SUITE_DIR . $identifier . ".xml";
					break;
				case "TYPE_LOCAL_TEST_SUITE":
					$location = XML_SUITE_LOCAL_DIR . $identifier . ".xml";
					break;
			}
		}

		$cache[$identifier] = $location;
	}

	return $cache[$identifier];
}
function pts_location_virtual_suite($identifier)
{
	static $cache;

	if(!isset($cache[$identifier]) || in_array($identifier, array("prev-test-identifier", "prev-save-identifier")))
	{
		$virtual_suite = false;

		// Ensure $identifier is not a real suite/test object
		if(count(pts_contained_tests($identifier, false, false)) == 0)
		{
			// When updating any of this, don't forget to update (if needed) the info run option for support
			switch($identifier)
			{
				case "all":
					$virtual_suite = "TYPE_VIRT_SUITE_ALL";
					break;
				case "installed-tests":
					$virtual_suite = "TYPE_VIRT_SUITE_INSTALLED_TESTS";
					break;
				case "free":
					$virtual_suite = "TYPE_VIRT_SUITE_FREE";
					break;
				case "prev-test-identifier":
					if(pts_read_assignment("PREV_TEST_IDENTIFIER"))
					{
						$virtual_suite = "TYPE_VIRT_PREV_TEST_IDENTIFIER";
					}
					break;
				case "prev-save-identifier":
					if(pts_read_assignment("PREV_SAVE_RESULTS_IDENTIFIER"))
					{
						$virtual_suite = "TYPE_VIRT_PREV_SAVE_IDENTIFIER";
					}
					break;
				default:
					// Check if object is a subsystem test type
					foreach(pts_types::subsystem_targets() as $type)
					{
						if(strtolower($type) == $identifier)
						{
							$virtual_suite = "TYPE_VIRT_SUITE_SUBSYSTEM";
							break;
						}
					}
					break;
			}
		}

		$cache[$identifier] = $virtual_suite;
	}

	return $cache[$identifier];
}
function pts_contained_tests($objects, $include_extensions = false, $check_extended = true, $remove_duplicates = true)
{
	// Provide an array containing the location(s) of all test(s) for the supplied object name
	$tests = array();
	$objects = pts_arrays::to_array($objects);

	foreach($objects as &$object)
	{
		if(pts_is_suite($object)) // Object is suite
		{
			$test_suite = new pts_test_suite($object);

			foreach(array_unique($test_suite->get_test_names()) as $test)
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
				$test_profile = new pts_test_profile($object);

				foreach(array_reverse($test_profile->get_test_extensions_recursive()) as $extension)
				{
					if(!in_array($extension, $tests))
					{
						array_push($tests, $extension);
					}
				}
			}
			array_push($tests, $object);
		}
		else if(pts_is_test_result($object)) // Object is a saved results file
		{
			$result_file = new pts_result_file($object);

			foreach($result_file->get_result_objects() as $result_object)
			{
				$test = $result_object->test_profile->get_identifier();

				foreach(pts_contained_tests($test, $include_extensions) as $sub_test)
				{
					array_push($tests, $sub_test);
				}
			}
		}
		else if(pts_global::is_global_id($object)) // Object is a Phoronix Global file
		{
			$result_file = new pts_result_file(pts_global::download_result_xml($object));

			foreach($result_file->get_result_objects() as $result_object)
			{
				$test = $result_object->test_profile->get_identifier();

				foreach(pts_contained_tests($test, $include_extensions) as $sub_test)
				{
					array_push($tests, $sub_test);
				}
			}
		}
		else if($check_extended && pts_is_virtual_suite($object))
		{
			foreach(pts_virtual_suite_tests($object) as $test)
			{
				foreach(pts_contained_tests($test, $include_extensions) as $sub_test)
				{
					array_push($tests, $sub_test);
				}
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
		case "TYPE_VIRT_SUITE_SUBSYSTEM":
			foreach(pts_tests::supported_tests() as $test)
			{
				$test_profile = new pts_test_profile($test);

				if(strtolower($test_profile->get_test_hardware_type()) == $object && $test_profile->is_supported())
				{
					array_push($contained_tests, $test);
				}
			}
			break;
		case "TYPE_VIRT_SUITE_ALL":
			foreach(pts_tests::supported_tests() as $test)
			{
				$test_profile = new pts_test_profile_parser($test);
				$result_format = $test_profile->get_result_format();
				$test_license = $test_profile->get_license();

				if(!in_array($result_format, array("NO_RESULT", "PASS_FAIL", "MULTI_PASS_FAIL", "IMAGE_COMPARISON")) && !in_array($test_license, array("RETAIL", "RESTRICTED")))
				{
					array_push($contained_tests, $test);
				}
			}
			break;
		case "TYPE_VIRT_SUITE_INSTALLED_TESTS":
			foreach(pts_tests::installed_tests() as $test)
			{
				$test_profile = new pts_test_profile_parser($test);
				$result_format = $test_profile->get_result_format();
				$test_title = $test_profile->get_title();

				if(!empty($test_title) && !in_array($result_format, array("NO_RESULT", "PASS_FAIL", "MULTI_PASS_FAIL", "IMAGE_COMPARISON")))
				{
					array_push($contained_tests, $test);
				}
			}
			break;
		case "TYPE_VIRT_SUITE_FREE":
			foreach(pts_tests::supported_tests() as $test)
			{
				$test_profile = new pts_test_profile_parser($test);
				$test_license = $test_profile->get_license();

				if($test_license == "FREE")
				{
					array_push($contained_tests, $test);
				}
			}
			break;
		case "TYPE_VIRT_PREV_TEST_IDENTIFIER":
			foreach(pts_arrays::to_array(pts_read_assignment("PREV_TEST_IDENTIFIER")) as $test)
			{
				array_push($contained_tests, $test);
			}
			break;
		case "TYPE_VIRT_PREV_SAVE_IDENTIFIER":
			foreach(pts_arrays::to_array(pts_read_assignment("PREV_SAVE_RESULTS_IDENTIFIER")) as $test)
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
function pts_test_installed($identifier)
{
	return is_file(TEST_ENV_DIR . $identifier . "/pts-install.xml");
}
function pts_find_result_file($file, $check_global = true)
{
	// PTS Find A Saved File
	if(pts_is_test_result($file))
	{
		$USE_FILE = SAVE_RESULTS_DIR . $file . "/composite.xml";
	}
	else if($check_global && pts_global::is_global_id($file))
	{
		pts_global::clone_global_result($file, false);
		$USE_FILE = SAVE_RESULTS_DIR . $file . "/composite.xml";
	}
	else
	{
		$USE_FILE = false;
	}

	return $USE_FILE;
}
function pts_suite_needs_updated_install($identifier)
{
	if(!pts_is_assignment("CACHE_SUITE_INSTALLED_" . strtoupper($identifier)))
	{
		$needs_update = false;

		foreach(pts_contained_tests($identifier, true, true, true) as $test)
		{
			$installed_test = new pts_installed_test($test);

			if(!pts_test_installed($test) || $installed_test->get_installed_system_identifier() != phodevi::system_id_string() || pts_is_assignment("PTS_FORCE_INSTALL"))
			{
				$needs_update = true;
				break;
			}
		}

		pts_set_assignment("CACHE_SUITE_INSTALLED_" . strtoupper($identifier), $needs_update);
	}

	return pts_read_assignment("CACHE_SUITE_INSTALLED_" . strtoupper($identifier));
}
function pts_test_needs_updated_install($identifier)
{
	$installed_test = new pts_installed_test($identifier);
	$test_profile = new pts_test_profile($identifier);

	// Checks if test needs updating
	// || $installed_test->get_installed_system_identifier() != phodevi::system_id_string()
	return !pts_test_installed($identifier) || !pts_strings::version_strings_comparable($test_profile->get_test_profile_version(), $installed_test->get_installed_version()) || $test_profile->get_installer_checksum() != $installed_test->get_installed_checksum() || pts_is_assignment("PTS_FORCE_INSTALL");
}
function pts_version_newer($version_a, $version_b)
{
	$r_a = explode(".", $version_a);
	$r_b = explode(".", $version_b);

	$r_a = ($r_a[0] * 1000) + ($r_a[1] * 100) + $r_a[2];
	$r_b = ($r_b[0] * 1000) + ($r_b[1] * 100) + $r_b[2];

	return $r_a > $r_b ? $version_a : $version_b;
}

// TODO: take care of pts_tests::available_tests() needs similar changes to the other type functions


/*


function pts_validate_local_test_profile($identifier)
{
	if(is_file(($lp = XML_PROFILE_LOCAL_DIR . $identifier . ".xml")))
	{
		$valid = true;

		if(is_file(($sp = XML_PROFILE_DIR . $identifier . ".xml")))
		{
			$lp_test = new pts_test_profile($lp);
			$lp_version = $lp_test->get_test_profile_version();

			$sp_test = new pts_test_profile($sp);
			$sp_version = $sp_test->get_test_profile_version();

			if(pts_version_newer($lp_version, $sp_version) == $sp_version)
			{
				// Standard test profile version newer than the local test profile version
				$valid = false;

				// Rename test profile since it's out of date
				rename($lp, XML_PROFILE_LOCAL_DIR . $identifier . ".xml.old");
			}
			
		}
	}
	else
	{
		$valid = false;
	}

	return $valid;
}

function pts_rebuild_test_type_cache($identifier)
{
	pts_identifier_type($identifier, true);
	pts_tests::test_profile_location($identifier, true);
	pts_tests::test_resources_location($identifier, true);
}
function pts_rebuild_suite_type_cache($identifier)
{
	pts_identifier_type($identifier, true);
	pts_location_suite($identifier, true);
}
function pts_available_base_tests_array()
{
	static $cache = null;

	if($cache == null)
	{
		$base_tests = pts_file_io::glob(XML_PROFILE_CTP_BASE_DIR . "*.xml");
		asort($base_tests);

		foreach($base_tests as &$base_test)
		{
			$base_test = basename($base_test, ".xml");
		}

		$cache = $base_tests;
	}

	return $cache;
}
*/

?>
