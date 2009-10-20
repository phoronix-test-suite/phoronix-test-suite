<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
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
	$save_to_dir = dirname(SAVE_RESULTS_DIR . $save_to);

	if($save_to_dir != ".")
	{
		pts_mkdir($save_to_dir);
	}

	return $save_to_dir;
}
function pts_save_result($save_to = null, $save_results = null, $render_graphs = true)
{
	// Saves PTS result file
	if(substr($save_to, -4) != ".xml")
	{
		$save_to .= ".xml";
	}

	$save_to_dir = pts_setup_result_directory($save_to);
	
	if($save_to == null || $save_results == null)
	{
		$bool = false;
	}
	else
	{
		$save_name = basename($save_to, ".xml");

		if($save_name == "composite" && $render_graphs)
		{
			pts_generate_graphs($save_results, $save_to_dir);
		}

		$bool = file_put_contents(SAVE_RESULTS_DIR . $save_to, $save_results);

		if(pts_is_assignment("TEST_RESULTS_IDENTIFIER") && (pts_string_bool(pts_read_user_config(P_OPTION_LOG_VSYSDETAILS, "TRUE")) || pts_read_assignment("IS_PCQS_MODE") || getenv("SAVE_SYSTEM_DETAILS") || pts_is_assignment("IS_BATCH_MODE")))
		{
			$test_results_identifier = pts_read_assignment("TEST_RESULTS_IDENTIFIER");

			// Save verbose system information here
			pts_mkdir($save_to_dir . "/system-logs/");
			pts_mkdir(($system_log_dir = $save_to_dir . "/system-logs/" . $test_results_identifier));

			// Backup system files
			$system_log_files = array("/var/log/Xorg.0.log", "/proc/cpuinfo");

			foreach($system_log_files as $file)
			{
				if(is_file($file))
				{
					file_put_contents($system_log_dir . "/" . basename($file), file_get_contents($file));
				}
			}

			// Generate logs from system commands to backup
			$system_log_commands = array("lspci", "lshal", "sensors", "dmesg", "glxinfo");

			if(IS_MACOSX)
			{
				array_push($system_log_commands, "system_profiler");
			}

			foreach($system_log_commands as $command_string)
			{
				if(pts_executable_in_path($command = array_pop(explode(" ", $command_string))))
				{
					@file_put_contents($system_log_dir . "/" . $command, shell_exec($command_string . " 2>&1"));
				}
			}
		}

		file_put_contents($save_to_dir . "/index.html", "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\"><html><head><title>Phoronix Test Suite</title><meta http-equiv=\"REFRESH\" content=\"0;url=composite.xml\"></HEAD><BODY></BODY></HTML>");
	}

	return $bool;
}
function pts_generate_graphs($test_results_identifier, $save_to_dir = false)
{
	if($save_to_dir)
	{
		if(!is_dir($save_to_dir . "/result-graphs"))
		{
			mkdir($save_to_dir . "/result-graphs", 0777, true);
		}
		else
		{
			foreach(glob($save_to_dir . "/result-graphs/*") as $old_file)
			{
				unlink($old_file);
			}
		}
	}

	$result_file = new pts_result_file($test_results_identifier);
	$pts_version = array_pop($result_file->get_system_pts_version());
	if(empty($pts_version))
	{
		$pts_version = PTS_VERSION;
	}

	$i = 0;
	$generated_graphs = array();
	foreach($result_file->get_result_objects() as $result_object)
	{
		$i++;
		$save_to = $save_to_dir;

		if($save_to_dir && is_dir($save_to_dir))
		{
			$save_to .= "/result-graphs/" . $i . ".BILDE_EXTENSION";
		}

		$graph = pts_render_graph($result_object, $save_to, $result_file->get_suite_name(), $pts_version);
		array_push($generated_graphs, $graph);
	}

	// Save XSL
	if(count($generated_graphs) > 0 && $save_to_dir)
	{
		file_put_contents($save_to_dir . "/pts-results-viewer.xsl", pts_get_results_viewer_xsl_formatted());
	}

	// Render overview chart
	// TODO: Get chart working

	if($save_to_dir && false) // not working right yet
	{
		$chart = new pts_Chart();
		$chart->loadLeftHeaders("", $results_name);
		$chart->loadTopHeaders($results_identifiers[0]);
		$chart->loadData($results_values);
		$chart->renderChart($save_to_dir . "/result-graphs/overview.BILDE_EXTENSION");
	}

	return $generated_graphs;
}
function pts_render_graph($r_o, $save_as = false, $suite_name = null, $pts_version = PTS_VERSION)
{
	$version = $r_o->get_version();
	$name = $r_o->get_name() . (isset($version[2]) ? " v" . $version : "");
	$result_format = $r_o->get_format();

	$identifiers = $r_o->get_identifiers();
	$values = $r_o->get_values();
	$raw_values = $r_o->get_raw_values();

	if(getenv("REVERSE_GRAPH_ORDER"))
	{
		// Plot results in reverse order on graphs if REVERSE_GRAPH_ORDER env variable is set
		$identifiers = array_reverse($identifiers);
		$values = array_reverse($values);
		$raw_values = array_reverse($raw_values);
	}

	if($result_format == "LINE_GRAPH" || $result_format == "BAR_ANALYZE_GRAPH")
	{
		if($result_format == "LINE_GRAPH")
		{
			$t = new pts_LineGraph($name, $r_o->get_attributes(), $r_o->get_scale_formatted());
		}
		else
		{
			$t = new pts_BarGraph($name, $r_o->get_attributes(), $r_o->get_scale_formatted());
		}

		//$t->hideGraphIdentifiers();

		for($i = 0; $i < count($values); $i++)
		{
			$values[$i] = explode(",", $values[$i]);
			$t->loadGraphValues($values[$i], $identifiers[$i]);
		}

		if(count(($ss = explode(",", $r_o->get_scale_special()))) > 0)
		{
			$t->loadGraphIdentifiers($ss);
		}
	}
	else
	{
		if($result_format == "PASS_FAIL")
		{
			$t = new pts_PassFailGraph($name, $r_o->get_attributes(), $r_o->get_scale_formatted());
		}
		else if($result_format == "MULTI_PASS_FAIL")
		{
			$t = new pts_MultiPassFailGraph($name, $r_o->get_attributes(), $r_o->get_scale_formatted());
		}
		else if(function_exists("pts_read_assignment") && pts_read_assignment("GRAPH_RENDER_TYPE") == "CANDLESTICK")
		{
			$t = new pts_CandleStickGraph($name, $r_o->get_attributes(), $r_o->get_scale_formatted());
		}
		else
		{
			$t = new pts_BarGraph($name, $r_o->get_attributes(), $r_o->get_scale_formatted());
		}

		$t->loadGraphIdentifiers($identifiers);
		$t->loadGraphValues($values);
		$t->loadGraphRawValues($raw_values);
	}

	$t->loadGraphProportion($r_o->get_proportion());
	$t->loadGraphVersion("Phoronix Test Suite " . $pts_version);

	$t->addInternalIdentifier("Test", $r_o->get_test_name());
	$t->addInternalIdentifier("Identifier", $suite_name);

	if(function_exists("pts_current_user"))
	{
		$t->addInternalIdentifier("User", pts_current_user());
	}

	if($save_as)
	{
		$t->saveGraphToFile($save_as);
	}

	if(function_exists("pts_set_assignment"))
	{
		pts_set_assignment("LAST_RENDERED_GRAPH", $t);
	}

	return $t->renderGraph();
}
function pts_subsystem_test_types()
{
	return array("System", "Processor", "Disk", "Graphics", "Memory", "Network");
}
function pts_license_test_types()
{
	return array("Free", "Non-Free", "Retail", "Restricted");
}
function pts_get_results_viewer_xsl_formatted()
{
	$pts_Graph = pts_read_assignment("LAST_RENDERED_GRAPH");

	if(!($pts_Graph instanceOf pts_Graph))
	{
		return;
	}

	$raw_xsl = file_get_contents(RESULTS_VIEWER_DIR . "pts-results-viewer.xsl");
	$graph_string = $pts_Graph->htmlEmbedCode("result-graphs/<xsl:number value=\"position()\" />.BILDE_EXTENSION", $pts_Graph->graphWidth(), $pts_Graph->graphWidth());

	$raw_xsl = str_replace("<!-- GRAPH TAGS -->", $graph_string, $raw_xsl);
	//$raw_xsl = str_replace("<!-- OVERVIEW TAG -->", $overview_string, $raw_xsl);

	return $raw_xsl;
}
function pts_suite_needs_updated_install($identifier)
{
	if(!pts_is_assignment("CACHE_SUITE_INSTALLED_" . strtoupper($identifier)))
	{
		$needs_update = false;

		foreach(pts_contained_tests($identifier, true, true, true) as $test)
		{
			if(pts_test_needs_updated_install($test))
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
	return !pts_test_installed($identifier) || !pts_version_comparable(pts_test_profile_version($identifier), pts_test_installed_profile_version($identifier)) || pts_test_checksum_installer($identifier) != pts_test_installed_checksum_installer($identifier) || pts_test_installed_system_identifier($identifier) != pts_system_identifier_string() || pts_is_assignment("PTS_FORCE_INSTALL");
}
function pts_test_checksum_installer($identifier)
{
	// Calculate installed checksum
	$md5_checksum = "";

	if(is_file(pts_location_test_resources($identifier) . "install.php"))
	{
		$md5_checksum = md5_file(pts_location_test_resources($identifier) . "install.php");
	}
	else if(is_file(pts_location_test_resources($identifier) . "install.sh"))
	{
		$md5_checksum = md5_file(pts_location_test_resources($identifier) . "install.sh");
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
	$version = "";

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
	$read = $xml_parser->getXMLValue($xml_option);

	return $read;
}
function pts_test_read_xml_array($identifier, $xml_option)
{
 	$xml_parser = new pts_test_tandem_XmlReader($identifier);
	$read = $xml_parser->getXMLArrayValues($xml_option);

	return $read;
}
function pts_suite_read_xml($identifier, $xml_option)
{
 	$xml_parser = new pts_suite_tandem_XmlReader($identifier);
	$read = $xml_parser->getXMLValue($xml_option);

	return $read;
}
function pts_suite_read_xml_array($identifier, $xml_option)
{
 	$xml_parser = new pts_suite_tandem_XmlReader($identifier);
	$read = $xml_parser->getXMLArrayValues($xml_option);

	return $read;
}
function pts_test_installed($identifier)
{
	return is_file(TEST_ENV_DIR . $identifier . "/pts-install.xml");
}
function pts_test_name_to_identifier($name)
{
	// Convert test name to identifier
	static $available_tests = null;
	static $cache = array();

	if(!isset($cache[$name]))
	{
		$this_identifier = false;

		if($available_tests == null)
		{
			$available_tests = pts_available_tests_array();
		}

		foreach($available_tests as $i => $identifier)
		{
			if(pts_test_read_xml($identifier, P_TEST_TITLE) == $name)
			{
				$this_identifier = $identifier;
				unset($available_tests[$i]);
				break;
			}
		}

		$cache[$name] = $this_identifier;
	}

	return $cache[$name];
}
function pts_suite_name_to_identifier($name)
{
	// Convert test name to identifier
	static $available_suites = null;
	static $cache = array();

	if(!isset($cache[$name]))
	{
		$this_identifier = false;

		if($available_suites == null)
		{
			$available_suites = pts_available_suites_array();
		}

		foreach($available_suites as $i => $identifier)
		{
			if(pts_suite_read_xml($identifier, P_SUITE_TITLE) == $name)
			{
				$this_identifier = $identifier;
				unset($available_suites[$i]);
				break;
			}
		}

		$cache[$name] = $this_identifier;
	}

	return $cache[$name];
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
function pts_estimated_download_size($identifier)
{
	// Estimate the size of files to be downloaded
	static $cache;

	if(is_array($identifier) || !isset($cache[$identifier]))
	{
		$estimated_size = 0;

		foreach(pts_contained_tests($identifier, true) as $test)
		{
			// The work for calculating the download size in 1.4.0+
			foreach(pts_objects_test_downloads($test) as $download_object)
			{
				$estimated_size += pts_trim_double($download_object->get_filesize() / 1048576);
			}
		}

		if(!is_array($identifier))
		{
			$cache[$identifier] = $estimated_size;
		}
	}

	return is_array($identifier) ? $estimated_size : $cache[$identifier];
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
		foreach($trq_r as $test_run_request)
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
		 	$xml_parser = new pts_installed_test_tandem_XmlReader($test);
			$this_length = $xml_parser->getXMLValue(P_INSTALL_TEST_AVG_RUNTIME);
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
			$archs = pts_trim_explode(",", $archs);
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
	 	$xml_parser = new pts_test_tandem_XmlReader($identifier);
		$platforms = $xml_parser->getXMLValue(P_TEST_SUPPORTEDPLATFORMS);
		$un_platforms = $xml_parser->getXMLValue(P_TEST_UNSUPPORTEDPLATFORMS);

		if(OPERATING_SYSTEM != "Unknown")
		{
			if(!empty($un_platforms))
			{
				$un_platforms = pts_trim_explode(",", $un_platforms);

				if(in_array(OPERATING_SYSTEM, $un_platforms))
				{
					$supported = false;
				}
			}
			if(!empty($platforms))
			{
				$platforms = pts_trim_explode(",", $platforms);

				if(!in_array(OPERATING_SYSTEM, $platforms))
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
		$requires_core_version = pts_test_read_xml($identifier, P_TEST_SUPPORTS_COREVERSION);
		$supported = pts_test_version_compatible($requires_core_version);
	}

	return $supported;
}
function pts_test_version_compatible($version_compare = "")
{
	$compatible = true;

	if(!empty($version_compare))
	{
		$current = pts_remove_chars(PTS_VERSION, true, false, false);

		$version_compare = explode("-", $version_compare);	
		$support_begins = pts_remove_chars(trim($version_compare[0]), true, false, false);

		$support_ends = count($version_compare) == 2 ? trim($version_compare[1]) : PTS_VERSION;
		$support_ends = pts_remove_chars(trim($support_ends), true, false, false);

		$compatible = $current >= $support_begins && $current <= $support_ends;
	}

	return $compatible;	
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

	for($i = 0; $i < $original_size; $i++)
	{
		if(!pts_test_supported(@$tests[$i]))
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
function pts_available_tests_array()
{
	$tests = glob(XML_PROFILE_DIR . "*.xml");
	$local_tests = glob(XML_PROFILE_LOCAL_DIR . "*.xml");
	$tests = array_unique(pts_array_merge($tests, $local_tests));
	asort($tests);

	for($i = 0; $i < count($tests); $i++)
	{
		$tests[$i] = basename($tests[$i], ".xml");
	}

	return $tests;
}
function pts_available_base_tests_array()
{
	$base_tests = glob(XML_PROFILE_CTP_BASE_DIR . "*.xml");
	asort($base_tests);

	for($i = 0; $i < count($base_tests); $i++)
	{
		$base_tests[$i] = basename($base_tests[$i], ".xml");
	}

	return $base_tests;
}
function pts_supported_tests_array()
{
	static $cache = null;

	if($cache == null)
	{
		$supported_tests = array();

		foreach(pts_available_tests_array() as $identifier)
		{
			if(pts_test_supported($identifier))
			{
				array_push($supported_tests, $identifier);
			}
		}

		$cache = $supported_tests;
	}

	return $cache;
}
function pts_installed_tests_array()
{
	if(!pts_is_assignment("CACHE_INSTALLED_TESTS"))
	{
		$cleaned_tests = array();

		foreach(glob(TEST_ENV_DIR . "*/pts-install.xml") as $test)
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
function pts_available_suites_array()
{
	static $cache = null;

	if($cache == null)
	{
		$suites = glob(XML_SUITE_DIR . "*.xml");
		$local_suites = glob(XML_SUITE_LOCAL_DIR . "*.xml");
		$suites = array_unique(pts_array_merge($suites, $local_suites));
		asort($suites);

		for($i = 0; $i < count($suites); $i++)
		{
			$suites[$i] = basename($suites[$i], ".xml");
		}

		$cache = $suites;
	}

	return $cache;
}
function pts_installed_suites_array()
{
	$installed_suites = array();

	foreach(pts_available_suites_array() as $suite)
	{
		if(!pts_suite_needs_updated_install($suite))
		{
			array_push($installed_suites, $suite);
		}
	}

	return $installed_suites;
}
function pts_supported_suites_array()
{
	static $cache = null;

	if($cache == null)
	{
		$supported_suites = array();

		foreach(pts_available_suites_array() as $identifier)
		{
			$suite = new pts_test_suite_details($identifier);

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
	$check_against = pts_to_array($check_against);

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
	$obj_r = array();

	if(is_file(($download_xml_file = pts_location_test_resources($test_identifier) . "downloads.xml")))
	{
		$xml_parser = new tandem_XmlReader($download_xml_file);
		$package_url = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_URL);
		$package_md5 = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_MD5);
		$package_filename = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_FILENAME);
		$package_filesize = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_FILESIZE);
		$package_platform = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_PLATFORMSPECIFIC);
		$package_architecture = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_ARCHSPECIFIC);

		for($i = 0; $i < count($package_url); $i++)
		{
			$file_exempt = false;

			if(!empty($package_platform[$i]))
			{
				$platforms = pts_trim_explode(",", $package_platform[$i]);
				$file_exempt = !in_array(OPERATING_SYSTEM, $platforms);
			}

			if(!empty($package_architecture[$i]))
			{
				$architectures = pts_trim_explode(",", $package_architecture[$i]);
				$file_exempt = !pts_cpu_arch_compatible($architectures);
			}

			if(!$file_exempt)
			{
				array_push($obj_r, new pts_test_file_download($package_url[$i], $package_filename[$i], $package_filesize[$i], $package_md5[$i]));
			}
		}
	}

	return $obj_r;
}
function pts_saved_test_results_identifiers()
{
	$results = array();
	$ignore_ids = pts_generic_reference_system_comparison_ids();

	foreach(glob(SAVE_RESULTS_DIR . "*/composite.xml") as $result_file)
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
		$comparison_ids = pts_trim_explode("\n", trim(file_get_contents(STATIC_DIR . "reference-system-comparisons.txt")));
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

?>
