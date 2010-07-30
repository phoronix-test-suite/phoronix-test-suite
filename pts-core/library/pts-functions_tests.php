<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel
	pts-functions_tests.php: Functions needed for some test parameters

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

function pts_setup_result_directory($save_to)
{
	$save_to_dir = SAVE_RESULTS_DIR . $save_to;

	if(strpos(basename($save_to_dir), '.'))
	{
		$save_to_dir = dirname($save_to_dir);
	}

	if($save_to_dir != ".")
	{
		pts_file_io::mkdir($save_to_dir);
	}

	file_put_contents($save_to_dir . "/index.html", "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\"><html><head><title>Phoronix Test Suite</title><meta http-equiv=\"REFRESH\" content=\"0;url=composite.xml\"></HEAD><BODY></BODY></HTML>");

	return $save_to_dir;
}
function pts_suite_needs_updated_install($identifier)
{
	if(!pts_is_assignment("CACHE_SUITE_INSTALLED_" . strtoupper($identifier)))
	{
		$needs_update = false;

		foreach(pts_contained_tests($identifier, true, true, true) as $test)
		{
			if(!pts_test_installed($test) || pts_test_installed_system_identifier($test) != phodevi::system_id_string() || pts_is_assignment("PTS_FORCE_INSTALL"))
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
	// Checks if test needs updating
	return !pts_test_installed($identifier) || !pts_strings::version_strings_comparable(pts_test_profile_version($identifier), pts_test_installed_profile_version($identifier)) || pts_test_checksum_installer($identifier) != pts_test_installed_checksum_installer($identifier) || pts_test_installed_system_identifier($identifier) != phodevi::system_id_string() || pts_is_assignment("PTS_FORCE_INSTALL");
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
function pts_test_installed_checksum_installer($identifier)
{
	// Read installer checksum of installed tests
	return pts_installed_test_read_xml($identifier, P_INSTALL_TEST_CHECKSUM);
}
function pts_test_installed_system_identifier($identifier)
{
	// Read installer checksum of installed tests
	return pts_installed_test_read_xml($identifier, P_INSTALL_TEST_SYSIDENTIFY);
}
function pts_test_installed_profile_version($identifier)
{
	// Checks installed version
	return pts_installed_test_read_xml($identifier, P_INSTALL_TEST_VERSION);
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
function pts_installed_test_read_xml($identifier, $xml_option)
{
	$read = null;

	if(pts_test_installed($identifier))
	{
	 	$xml_parser = new pts_installed_test_tandem_XmlReader($identifier, false);
		$read = $xml_parser->getXMLValue($xml_option);
	}

	return $read;
}
function pts_test_read_xml($identifier, $xml_option)
{
 	$xml_parser = new pts_test_tandem_XmlReader($identifier);
	return $xml_parser->getXMLValue($xml_option);
}
function pts_test_read_xml_array($identifier, $xml_option)
{
 	$xml_parser = new pts_test_tandem_XmlReader($identifier);
	return $xml_parser->getXMLArrayValues($xml_option);
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
function pts_test_installed($identifier)
{
	return is_file(TEST_ENV_DIR . $identifier . "/pts-install.xml");
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
function pts_test_identifier_to_name($identifier)
{
	// Convert identifier to test name
	static $cache;

	if(!isset($cache[$identifier]))
	{
		$name = false;

		if(!empty($identifier) && pts_is_test($identifier))
		{
			$name = pts_test_read_xml($identifier, P_TEST_TITLE);
		}

		$cache[$identifier] = $name;
	}

	return $cache[$identifier];
}
function pts_suite_identifier_to_name($identifier)
{
	// Convert identifier to test name
	static $cache;

	if(!isset($cache[$identifier]))
	{
		$name = false;

		if(!empty($identifier) && pts_is_suite($identifier))
		{
			$name = pts_suite_read_xml($identifier, P_SUITE_TITLE);
		}

		$cache[$identifier] = $name;
	}

	return $cache[$identifier];
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
			foreach(pts_objects_test_downloads($test) as $download_object)
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
			$this_length = pts_installed_test_read_xml($test, P_INSTALL_TEST_AVG_RUNTIME);
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
function pts_test_architecture_supported($identifier)
{
	// Check if the system's architecture is supported by a test
	$supported = true;

	if(pts_is_test($identifier))
	{
		$archs = pts_test_read_xml($identifier, P_TEST_SUPPORTEDARCHS);

		if(!empty($archs))
		{
			$archs = pts_strings::trim_explode(",", $archs);
			$supported = pts_cpu_arch_compatible($archs);
		}
	}

	return $supported;
}
function pts_test_platform_supported($identifier)
{
	// Check if the system's OS is supported by a test
	$supported = true;

	if(pts_is_test($identifier))
	{
		$platforms = pts_test_read_xml($identifier, P_TEST_SUPPORTEDPLATFORMS);

		if(!empty($platforms))
		{
			$platforms = pts_strings::trim_explode(",", $platforms);

			if(!in_array(OPERATING_SYSTEM, $platforms))
			{
				if(IS_BSD && BSD_LINUX_COMPATIBLE && in_array("Linux", $platforms))
				{
					// The OS is BSD but there is Linux API/ABI compatibility support loaded
					$supported = true;

				}
				else
				{
					$supported = false;
				}
			}
		}
	}

	return $supported;
}
function pts_test_version_supported($identifier)
{
	// Check if the test profile's version is compatible with pts-core
	$supported = true;

	if(pts_is_test($identifier))
	{
		$requires_core_version = pts_test_read_xml($identifier, P_TEST_REQUIRES_COREVERSION);

		if(!empty($requires_core_version))
		{
			$supported = pts_is_supported_core_version($requires_core_version);
		}
	}

	return $supported;
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
			$supported = pts_is_supported_core_version($requires_core_version);
		}
	}

	return $supported;
}
function pts_is_supported_core_version($core_check)
{
	$core_check = pts_strings::trim_explode('-', $core_check);	
	$support_begins = $core_check[0];
	$support_ends = isset($core_check[1]) ? $core_check[1] : PTS_CORE_VERSION;
	return PTS_CORE_VERSION >= $support_begins && PTS_CORE_VERSION <= $support_ends;
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
		if(!pts_test_supported($test))
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
function pts_test_supported($identifier)
{
	return pts_test_architecture_supported($identifier) && pts_test_platform_supported($identifier) && pts_test_version_supported($identifier);
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
function pts_objects_test_downloads($test_identifier)
{
	// TODO: this is replicated within pts_test_install_request thus this function is deprecated and should be removed
	// pts_test_install_request->generate_download_object_list()
	$obj_r = array();

	if(is_file(($download_xml_file = pts_tests::test_resources_location($test_identifier) . "downloads.xml")))
	{
		pts_loader::load_definitions("test-profile-downloads.xml");

		$xml_parser = new tandem_XmlReader($download_xml_file);
		$package_url = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_URL);
		$package_md5 = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_MD5);
		$package_filename = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_FILENAME);
		$package_filesize = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_FILESIZE);
		$package_platform = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_PLATFORMSPECIFIC);
		$package_architecture = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_ARCHSPECIFIC);

		foreach(array_keys($package_filename) as $i)
		{
			if(!empty($package_platform[$i]))
			{
				$platforms = pts_strings::trim_explode(',', $package_platform[$i]);

				if(!in_array(OPERATING_SYSTEM, $platforms) && !(IS_BSD && BSD_LINUX_COMPATIBLE && in_array("Linux", $platforms)))
				{
					// This download does not match the operating system
					continue;
				}
			}

			if(!empty($package_architecture[$i]))
			{
				$architectures = pts_strings::trim_explode(',', $package_architecture[$i]);

				if(!pts_cpu_arch_compatible($architectures))
				{
					// This download does not match the CPU architecture
					continue;
				}
			}

			array_push($obj_r, new pts_test_file_download($package_url[$i], $package_filename[$i], $package_filesize[$i], $package_md5[$i]));
		}
	}

	return $obj_r;
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
function pts_remove_test_profile($identifier)
{
	$xml_loc = pts_tests::test_profile_location($identifier);
	$resources_loc = pts_tests::test_resources_location($identifier);
	$removed = false;

	if(is_writable($xml_loc) && is_writable($resources_loc))
	{
		pts_file_io::unlink($xml_loc);
		pts_file_io::delete($resources_loc, null, true);
		$removed = true;
	}

	return $removed;
}
function pts_remove_test_result_dir($identifier)
{
	pts_file_io::delete(SAVE_RESULTS_DIR . $identifier, null, true);
}

?>
