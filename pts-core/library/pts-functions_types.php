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

function pts_is_run_object($object)
{
	return pts_is_test($object) || pts_is_suite($object);
}
function pts_is_suite($object)
{
	$type = pts_type_handler::pts_identifier_type($object);

	return $type == "TYPE_TEST_SUITE" || $type == "TYPE_LOCAL_TEST_SUITE";
}
function pts_is_virtual_suite($object)
{
	return pts_location_virtual_suite($object) != false;
}
function pts_is_test($object)
{
	$type = pts_type_handler::pts_identifier_type($object);

	return $type == "TYPE_TEST" || $type == "TYPE_LOCAL_TEST" || $type == "TYPE_BASE_TEST";
}
function pts_is_base_test($object)
{
	$type = pts_type_handler::pts_identifier_type($object);

	return $type == "TYPE_BASE_TEST";
}
function pts_is_test_result($identifier)
{
	return is_file(SAVE_RESULTS_DIR . $identifier . "/composite.xml");
}
function pts_validate_local_test_profile($identifier)
{
	if(is_file(($lp = XML_PROFILE_LOCAL_DIR . $identifier . ".xml")))
	{
		$valid = true;

		if(is_file(($sp = XML_PROFILE_DIR . $identifier . ".xml")))
		{
			$lp_version = pts_test_read_xml($lp, P_TEST_PTSVERSION);
			$sp_version = pts_test_read_xml($sp, P_TEST_PTSVERSION);

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
function pts_validate_local_test_suite($identifier)
{
	if(is_file(($ls = XML_SUITE_LOCAL_DIR . $identifier . ".xml")))
	{
		$valid = true;

		if(is_file(($ss = XML_SUITE_DIR . $identifier . ".xml")))
		{
			$ls_version = pts_test_read_xml($ls, P_SUITE_VERSION);
			$ss_version = pts_test_read_xml($ss, P_SUITE_VERSION);

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
			$type = pts_type_handler::pts_identifier_type($identifier);

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
function pts_test_extends_below($object)
{
	// Process Extensions / Cascading Test Profiles
	$extensions = array();
	$test_extends = $object;

	do
	{
		if(pts_is_test($test_extends))
		{
			$test_extends = pts_test_read_xml($test_extends, P_TEST_CTPEXTENDS);

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
	$objects = pts_arrays::to_array($objects);

	foreach($objects as &$object)
	{
		if(pts_is_suite($object)) // Object is suite
		{
			foreach(array_unique(pts_suite_read_xml_array($object, P_SUITE_TEST_NAME)) as $test)
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
		else if(pts_is_test_result($object)) // Object is a saved results file
		{
			$xml_parser = new pts_results_tandem_XmlReader($object);

			foreach($xml_parser->getXMLArrayValues(P_RESULTS_TEST_TESTNAME) as $test)
			{
				foreach(pts_contained_tests($test, $include_extensions) as $sub_test)
				{
					array_push($tests, $sub_test);
				}
			}
		}
		else if(pts_global::is_global_id($object)) // Object is a Phoronix Global file
		{
			$xml_parser = new pts_results_tandem_XmlReader(pts_global::download_result_xml($object));

			foreach($xml_parser->getXMLArrayValues(P_RESULTS_TEST_TESTNAME) as $test)
			{
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
				$result_format = pts_test_read_xml($test, P_TEST_RESULTFORMAT);
				$test_license = pts_test_read_xml($test, P_TEST_LICENSE);

				if(!in_array($result_format, array("NO_RESULT", "PASS_FAIL", "MULTI_PASS_FAIL", "IMAGE_COMPARISON")) && !in_array($test_license, array("RETAIL", "RESTRICTED")))
				{
					array_push($contained_tests, $test);
				}
			}
			break;
		case "TYPE_VIRT_SUITE_INSTALLED_TESTS":
			foreach(pts_installed_tests_array() as $test)
			{
				$result_format = pts_test_read_xml($test, P_TEST_RESULTFORMAT);
				$test_title = pts_test_read_xml($test, P_TEST_TITLE);

				if(!empty($test_title) && !in_array($result_format, array("NO_RESULT", "PASS_FAIL", "MULTI_PASS_FAIL", "IMAGE_COMPARISON")))
				{
					array_push($contained_tests, $test);
				}
			}
			break;
		case "TYPE_VIRT_SUITE_FREE":
			foreach(pts_tests::supported_tests() as $test)
			{
				$test_license = pts_test_read_xml($test, P_TEST_LICENSE);

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
function pts_rebuild_test_type_cache($identifier)
{
	pts_type_handler::pts_identifier_type($identifier, true);
	pts_tests::test_profile_location($identifier, true);
	pts_tests::test_resources_location($identifier, true);
}
function pts_rebuild_suite_type_cache($identifier)
{
	pts_type_handler::pts_identifier_type($identifier, true);
	pts_location_suite($identifier, true);
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

	// Checks if test needs updating
	return !pts_test_installed($identifier) || !pts_strings::version_strings_comparable(pts_test_profile_version($identifier), $installed_test->get_installed_version()) || pts_test_checksum_installer($identifier) != $installed_test->get_installed_checksum() || $installed_test->get_installed_system_identifier() != phodevi::system_id_string() || pts_is_assignment("PTS_FORCE_INSTALL");
}
function pts_test_checksum_installer($identifier)
{
	// Calculate installed checksum
	$test_resources_location = pts_tests::test_resources_location($identifier);
	$os_postfix = '_' . strtolower(OPERATING_SYSTEM);

	if(is_file($test_resources_location . "install" . $os_postfix . ".sh"))
	{
		$md5_checksum = md5_file($test_resources_location . "install" . $os_postfix . ".sh");
	}
	else if(is_file($test_resources_location . "install.sh"))
	{
		$md5_checksum = md5_file($test_resources_location . "install.sh");
	}
	else
	{
		$md5_checksum = null;
	}

	return $md5_checksum;
}
function pts_test_profile_version($identifier)
{
	// Checks PTS profile version
	$version = null;

	if(pts_is_test($identifier))
	{
		$version = pts_test_read_xml($identifier, P_TEST_PTSVERSION);		
	}

	return $version;
}
function pts_test_read_xml($identifier, $xml_option)
{
 	$xml_parser = new pts_test_tandem_XmlReader($identifier);
	return $xml_parser->getXMLValue($xml_option);
}
function pts_suite_read_xml($identifier, $xml_option)
{
 	$xml_parser = new pts_suite_tandem_XmlReader($identifier);
	return $xml_parser->getXMLValue($xml_option);
}
function pts_suite_read_xml_array($identifier, $xml_option)
{
 	$xml_parser = new pts_suite_tandem_XmlReader($identifier);
	return $xml_parser->getXMLArrayValues($xml_option);
}
function pts_test_name_to_identifier($name)
{
	// Convert test name to identifier
	static $cache = null;

	if($cache == null)
	{
		$cache = array();

		foreach(pts_tests::available_tests() as $identifier)
		{
			$title = pts_test_read_xml($identifier, P_TEST_TITLE);
			$cache[$title] = $identifier;
		}
	}

	return isset($cache[$name]) ? $cache[$name] : false;
}
function pts_suite_name_to_identifier($name)
{
	// Convert test name to identifier
	static $cache = null;

	if($cache == null)
	{
		$cache = array();

		foreach(pts_suites::available_suites() as $identifier)
		{
			$title = pts_suite_read_xml($identifier, P_SUITE_TITLE);
			$cache[$title] = $identifier;
		}
	}

	return isset($cache[$name]) ? $cache[$name] : false;
}
function pts_estimated_download_size($identifier, $divider = 1048576, $include_extensions = true)
{
	// Estimate the size of files to be downloaded
	static $cache;

	if(($id_is_array = is_array($identifier)) || !isset($cache[$identifier][$divider]))
	{
		$estimated_size = 0;

		foreach(pts_contained_tests($identifier, $include_extensions) as $test)
		{
			// The work for calculating the download size in 1.4.0+
			foreach(pts_test_install_request::read_download_object_list($test) as $download_object)
			{
				$estimated_size += $download_object->get_filesize();
			}
		}

		$estimated_size = $estimated_size > 0 ? round($estimated_size / $divider, 2) : 0;

		if(!$id_is_array)
		{
			$cache[$identifier][$divider] = $estimated_size;
		}
	}

	return $id_is_array ? $estimated_size : $cache[$identifier][$divider];
}
function pts_estimated_environment_size($identifier)
{
	// Estimate the environment size of a test or suite
	$estimated_size = 0;

	foreach(pts_contained_tests($identifier, true) as $test)
	{
		$this_size = pts_test_read_xml($test, P_TEST_ENVIRONMENTSIZE);

		if(!empty($this_size) && is_numeric($this_size))
		{
			$estimated_size += $this_size;
		}
	}

	return $estimated_size;
}
function pts_tests_within_run_manager($test_run_manager)
{
	$identifiers = array();

	if($test_run_manager instanceOf pts_test_run_manager && is_array(($trq_r = $test_run_manager->get_tests_to_run())))
	{
		foreach($trq_r as &$test_run_request)
		{
			if($test_run_request instanceOf pts_test_run_request)
			{
				array_push($identifiers, $test_run_request->get_identifier());
			}
			else
			{
				foreach(pts_tests_within_run_manager($run_request) as $to_add)
				{
					array_push($identifiers, $to_add);
				}
			}
		}
	}

	return $identifiers;
}
function pts_estimated_run_time($identifier, $return_total_time = true, $return_on_missing = true)
{
	// Estimate the time it takes (in seconds) to complete the given test
	$estimated_lengths = array();
	$estimated_total = 0;

	if($identifier instanceOf pts_test_run_manager)
	{
		$identifier = pts_tests_within_run_manager($identifier);
	}

	foreach(pts_contained_tests($identifier, false, true, false) as $test)
	{
		if(pts_test_installed($test))
		{
			$installed_test = new pts_installed_test($test);
			$this_length = $installed_test->get_average_run_time();
			$estimated_length = 0;

			if(is_numeric($this_length) && $this_length > 0)
			{
				$estimated_length = $this_length;
			}
			else
			{
				$el = pts_test_read_xml($test, P_TEST_ESTIMATEDTIME);

				if(is_numeric($el) && $el > 0)
				{
					$estimated_length = ($el * 60);
				}
				else if($return_total_time && $return_on_missing)
				{
					// No accurate calculation available
					return -1;
				}
			}

			$estimated_lengths[$test] = $estimated_length;
			$estimated_total += $estimated_length;
		}
	}

	return $return_total_time ? $estimated_total : $estimated_lengths;
}
function pts_suite_version_supported($identifier)
{
	// Check if the test suite's version is compatible with pts-core
	$supported = true;

	if(pts_is_suite($identifier))
	{
		$requires_core_version = pts_suite_read_xml($identifier, P_SUITE_REQUIRES_COREVERSION);

		if(!empty($requires_core_version))
		{
			$core_check = pts_strings::trim_explode('-', $requires_core_version);	
			$support_begins = $core_check[0];
			$support_ends = isset($core_check[1]) ? $core_check[1] : PTS_CORE_VERSION;
			$supported = PTS_CORE_VERSION >= $support_begins && PTS_CORE_VERSION <= $support_ends;
		}
	}

	return $supported;
}
function pts_version_newer($version_a, $version_b)
{
	$r_a = explode(".", $version_a);
	$r_b = explode(".", $version_b);

	$r_a = ($r_a[0] * 1000) + ($r_a[1] * 100) + $r_a[2];
	$r_b = ($r_b[0] * 1000) + ($r_b[1] * 100) + $r_b[2];

	return $r_a > $r_b ? $version_a : $version_b;
}
function pts_suite_supported($identifier)
{
	$tests = pts_contained_tests($identifier, false, false, true);
	$supported_size = $original_size = count($tests);

	foreach($tests as &$test)
	{
		$test_profile = new pts_test_profile($test);

		if($test_profile->is_supported())
		{
			$supported_size--;
		}
	}

	if($supported_size == 0)
	{
		$return_code = 0;
	}
	else if($supported_size != $original_size)
	{
		$return_code = 1;
	}
	else
	{
		$return_code = 2;
	}

	return $return_code;
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
function pts_installed_tests_array()
{
	if(!pts_is_assignment("CACHE_INSTALLED_TESTS"))
	{
		$cleaned_tests = array();

		foreach(pts_file_io::glob(TEST_ENV_DIR . "*/pts-install.xml") as $test)
		{
			$test = pts_extract_identifier_from_path($test);

			if(pts_is_test($test))
			{
				array_push($cleaned_tests, $test);
			}
		}

		pts_set_assignment("CACHE_INSTALLED_TESTS", $cleaned_tests);
	}

	return pts_read_assignment("CACHE_INSTALLED_TESTS");
}
function pts_installed_suites_array()
{
	if(!pts_is_assignment("CACHE_INSTALLED_SUITES"))
	{
		$installed_suites = array();

		foreach(pts_suites::available_suites() as $suite)
		{
			if(!pts_suite_needs_updated_install($suite))
			{
				array_push($installed_suites, $suite);
			}
		}

		pts_set_assignment("CACHE_INSTALLED_SUITES", $installed_suites);
	}

	return pts_read_assignment("CACHE_INSTALLED_SUITES");
}
function pts_supported_suites_array()
{
	static $cache = null;

	if($cache == null)
	{
		$supported_suites = array();

		foreach(pts_suites::available_suites() as $identifier)
		{
			$suite = new pts_test_suite($identifier);

			if(!$suite->not_supported())
			{
				array_push($supported_suites, $identifier);
			}
		}

		$cache = $supported_suites;
	}

	return $cache;
}
function pts_cpu_arch_compatible($check_against)
{
	$compatible = true;
	$this_arch = phodevi::read_property("system", "kernel-architecture");
	$check_against = pts_arrays::to_array($check_against);

	if(isset($this_arch[2]) && substr($this_arch, -2) == "86")
	{
		$this_arch = "x86";
	}
	if(!in_array($this_arch, $check_against))
	{
		$compatible = false;
	}

	return $compatible;
}
function pts_saved_test_results_identifiers()
{
	$results = array();
	$ignore_ids = pts_generic_reference_system_comparison_ids();

	foreach(pts_file_io::glob(SAVE_RESULTS_DIR . "*/composite.xml") as $result_file)
	{
		$identifier = pts_extract_identifier_from_path($result_file);

		if(!in_array($identifier, $ignore_ids))
		{
			array_push($results, $identifier);
		}
	}

	return $results;
}
function pts_generic_reference_system_comparison_ids()
{
	static $comparison_ids = null;

	if($comparison_ids == null)
	{
		$comparison_ids = pts_strings::trim_explode("\n", pts_file_io::file_get_contents(STATIC_DIR . "lists/reference-system-comparisons.list"));

		foreach(explode(' ', pts_config::read_user_config(P_OPTION_EXTRA_REFERENCE_SYSTEMS, null)) as $reference_check)
		{
			if(pts_global::is_global_id($reference_check))
			{
				array_push($comparison_ids, $reference_check);
			}
		}
	}

	return $comparison_ids;
}
function pts_test_comparison_hash($test_identifier, $arguments, $attributes = null, $version = null)
{
	$hash_table = array(
	$test_identifier,
	trim($arguments),
	trim($attributes),
	$version
	);

	return base64_encode(implode(",", $hash_table));
}
function pts_suites_containing_test($test_identifier)
{
	$associated_suites = array();

	foreach(pts_suites::available_suites() as $identifier)
	{
		if(in_array($test_identifier, pts_contained_tests($identifier)))
		{
			array_push($associated_suites, pts_suite_read_xml($identifier, P_SUITE_TITLE));
		}
	}

	return $associated_suites;
}
function pts_remove_test_result_dir($identifier)
{
	pts_file_io::delete(SAVE_RESULTS_DIR . $identifier, null, true);
}

?>
