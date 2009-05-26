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

function pts_save_result($save_to = null, $save_results = null, $render_graphs = true)
{
	// Saves PTS result file
	if(strpos($save_to, ".xml") === false)
	{
		$save_to .= ".xml";
	}

	$save_to_dir = dirname(SAVE_RESULTS_DIR . $save_to);

	if(!is_dir(SAVE_RESULTS_DIR))
	{
		mkdir(SAVE_RESULTS_DIR);
	}
	if($save_to_dir != '.' && !is_dir($save_to_dir))
	{
		mkdir($save_to_dir);
	}

	if(!is_dir(SAVE_RESULTS_DIR . "pts-results-viewer"))
	{
		mkdir(SAVE_RESULTS_DIR . "pts-results-viewer");
	}

	pts_copy(RESULTS_VIEWER_DIR . "pts.js", SAVE_RESULTS_DIR . "pts-results-viewer/pts.js");
	pts_copy(RESULTS_VIEWER_DIR . "pts-viewer.css", SAVE_RESULTS_DIR . "pts-results-viewer/pts-viewer.css");
	pts_copy(RESULTS_VIEWER_DIR . "pts-logo.png", SAVE_RESULTS_DIR . "pts-results-viewer/pts-logo.png");
	
	if($save_to == null || $save_results == null)
	{
		$bool = true;
	}
	else
	{
		$save_name = basename($save_to, ".xml");

		if($save_name == "composite" && $render_graphs)
		{
			pts_generate_graphs($save_results, $save_to_dir);
		}

		$bool = file_put_contents(SAVE_RESULTS_DIR . $save_to, $save_results);

		if(pts_is_assignment("TEST_RESULTS_IDENTIFIER") && (pts_string_bool(pts_read_user_config(P_OPTION_LOG_VSYSDETAILS, "TRUE")) || pts_read_assignment("IS_PCQS_MODE") != false || getenv("SAVE_SYSTEM_DETAILS") != false || pts_is_assignment("IS_BATCH_MODE")))
		{
			$test_results_identifier = pts_read_assignment("TEST_RESULTS_IDENTIFIER");

			// Save verbose system information here
			if(!is_dir($save_to_dir . "/system-logs/"))
			{
				mkdir($save_to_dir . "/system-logs/");
			}

			$system_log_dir = $save_to_dir . "/system-logs/" . $test_results_identifier;

			if(!is_dir($system_log_dir))
			{
				mkdir($system_log_dir);
			}

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
	if($save_to_dir != false && !is_dir($save_to_dir . "/result-graphs"))
	{
		mkdir($save_to_dir . "/result-graphs", 0777, true);
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

		if($save_to_dir != false && is_dir($save_to_dir))
		{
			$save_to .= "/result-graphs/" . $i . ".BILDE_EXTENSION";
		}

		$graph = pts_render_graph($result_object, $save_to, $result_file->get_suite_name(), $pts_version);
		array_push($generated_graphs, $graph);
	}

	// Save XSL
	if(count($generated_graphs) > 0 && $save_to_dir != false)
	{
		file_put_contents($save_to_dir . "/pts-results-viewer.xsl", pts_get_results_viewer_xsl_formatted());
	}

	// Render overview chart
	if($save_to_dir != false)
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
	$name = $r_o->get_name() . (strlen($r_o->get_version()) > 2 ? " v" . $r_o->get_version() : "");
	$result_format = $r_o->get_format();

	if($result_format == "LINE_GRAPH")
	{
		$t = new pts_LineGraph($name, $r_o->get_attributes(), $r_o->get_scale());
	}
	else if($result_format == "PASS_FAIL")
	{
		$t = new pts_PassFailGraph($name, $r_o->get_attributes(), $r_o->get_scale());
	}
	else if($result_format == "MULTI_PASS_FAIL")
	{
		$t = new pts_MultiPassFailGraph($name, $r_o->get_attributes(), $r_o->get_scale());
	}
	else if(function_exists("pts_read_assignment") && pts_read_assignment("GRAPH_RENDER_TYPE") == "CANDLESTICK")
	{
		$t = new pts_CandleStickGraph($name, $r_o->get_attributes(), $r_o->get_scale());
	}
	else
	{
		$t = new pts_BarGraph($name, $r_o->get_attributes(), $r_o->get_scale());
	}

	$identifiers = $r_o->get_identifiers();
	$values = $r_o->get_values();
	$raw_values = $r_o->get_raw_values();

	if(getenv("REVERSE_GRAPH_ORDER") != false)
	{
		// Plot results in reverse order on graphs if REVERSE_GRAPH_ORDER env variable is set
		$identifiers = array_reverse($identifiers);
		$values = array_reverse($values);
		$raw_values = array_reverse($raw_values);
	}

	$t->loadGraphIdentifiers($identifiers);
	$t->loadGraphValues($values);
	$t->loadGraphRawValues($raw_values);
	$t->loadGraphProportion($r_o->get_proportion());
	$t->loadGraphVersion($pts_version);

	$t->addInternalIdentifier("Test", $r_o->get_test_name());
	$t->addInternalIdentifier("Identifier", $suite_name);

	if(function_exists("pts_current_user"))
	{
		$t->addInternalIdentifier("User", pts_current_user());
	}

	if($save_as != false)
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
function pts_parse_svg_options($svg_file)
{
	$svg_parser = new tandem_XmlReader($svg_file);
	$svg_test = array_pop($svg_parser->getStatement("Test"));
	$svg_identifier = array_pop($svg_parser->getStatement("Identifier"));

	$run_options = array();
	if(pts_is_test($svg_test))
	{
		array_push($run_options, array($svg_test, "Run this test (" . $svg_test . ")"));
	}
	if(pts_is_suite($svg_identifier))
	{
		array_push($run_options, array($svg_identifier, "Run this suite (" . $svg_identifier . ")"));
	}
	else if(pts_is_global_id($svg_identifier))
	{
		array_push($run_options, array($svg_identifier, "Run this Phoronix Global comparison (" . $svg_identifier . ")"));
	}

	return $run_options;
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
	return !pts_test_installed($identifier)  || !pts_version_comparable(pts_test_profile_version($identifier), pts_test_installed_profile_version($identifier)) || pts_test_checksum_installer($identifier) != pts_test_installed_checksum_installer($identifier) || pts_test_installed_system_identifier($identifier) != pts_system_identifier_string() || pts_is_assignment("PTS_FORCE_INSTALL");
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
	$version = "";

	if(pts_test_installed($identifier))
	{
	 	$xml_parser = new pts_installed_test_tandem_XmlReader($identifier, false);
		$version = $xml_parser->getXMLValue(P_INSTALL_TEST_CHECKSUM);
	}

	return $version;
}
function pts_input_correct_results_path($path)
{
	// Correct an input path for an XML file
	if(strpos($path, "/") === false)
	{
		$path = SAVE_RESULTS_DIR . $path;
	}
	if(strpos($path, ".xml") === false)
	{
		$path = $path . ".xml";
	}
	return $path;
}
function pts_test_installed_system_identifier($identifier)
{
	// Read installer checksum of installed tests
	$value = "";

	if(pts_test_installed($identifier))
	{
	 	$xml_parser = new pts_installed_test_tandem_XmlReader($identifier, false);
		$value = $xml_parser->getXMLValue(P_INSTALL_TEST_SYSIDENTIFY);
	}

	return $value;
}
function pts_test_profile_version($identifier)
{
	// Checks PTS profile version
	$version = "";

	if(pts_is_test($identifier))
	{
	 	$xml_parser = new pts_test_tandem_XmlReader($identifier);
		$version = $xml_parser->getXMLValue(P_TEST_PTSVERSION);
	}

	return $version;
}
function pts_test_installed_profile_version($identifier)
{
	// Checks installed version
	$version = "";

	if(pts_test_installed($identifier))
	{
	 	$xml_parser = new pts_installed_test_tandem_XmlReader($identifier, false);
		$version = $xml_parser->getXMLValue(P_INSTALL_TEST_VERSION);
	}

	return $version;
}
function pts_test_installed($identifier)
{
	return is_file(TEST_ENV_DIR . $identifier . "/pts-install.xml");
}
function pts_test_name_to_identifier($name)
{
	// Convert test name to identifier
	static $cache;

	if(!isset($cache[$name]))
	{
		$this_identifier = false;

		foreach(pts_available_tests_array() as $identifier)
		{
		 	$xml_parser = new pts_test_tandem_XmlReader($identifier);

			if($xml_parser->getXMLValue(P_TEST_TITLE) == $name)
			{
				$this_identifier = $identifier;
			}
		}

		$cache[$name] = $this_identifier;
	}

	return $cache[$name];
}
function pts_suite_name_to_identifier($name)
{
	// Convert test name to identifier
	static $cache;

	if(!isset($cache[$name]))
	{
		$this_identifier = false;

		foreach(pts_available_suites_array() as $identifier)
		{
		 	$xml_parser = new pts_suite_tandem_XmlReader($identifier);

			if($xml_parser->getXMLValue(P_SUITE_TITLE) == $name)
			{
				$this_identifier = $identifier;
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
		 	$xml_parser = new pts_test_tandem_XmlReader($identifier);
			$name = $xml_parser->getXMLValue(P_TEST_TITLE);
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
		 	$xml_parser = new pts_suite_tandem_XmlReader($identifier);
			$name = $xml_parser->getXMLValue(P_SUITE_TITLE);
		}

		$cache[$identifier] = $name;
	}

	return $cache[$identifier];
}
function pts_estimated_download_size($identifier)
{
	// Estimate the size of files to be downloaded
	$estimated_size = 0;
	foreach(pts_contained_tests($identifier, true) as $test)
	{
		// The work for calculating the download size in 1.4.0+
		foreach(pts_objects_test_downloads($test) as $download_object)
		{
			$estimated_size += pts_trim_double($download_object->get_filesize() / 1048576);
		}
	}

	return $estimated_size;
}
function pts_estimated_environment_size($identifier)
{
	// Estimate the environment size of a test or suite
	$estimated_size = 0;

	foreach(pts_contained_tests($identifier, true) as $test)
	{
	 	$xml_parser = new pts_test_tandem_XmlReader($test);
		$this_size = $xml_parser->getXMLValue(P_TEST_ENVIRONMENTSIZE);

		if(!empty($this_size) && is_numeric($this_size))
		{
			$estimated_size += $this_size;
		}
	}

	return $estimated_size;
}
function pts_estimated_run_time($identifier)
{
	// Estimate the time it takes (in seconds) to complete the given test
	$estimated_length = 0;

	foreach(pts_contained_tests($identifier, false, true, false) as $test)
	{
		if(pts_test_installed($test))
		{
		 	$xml_parser = new pts_installed_test_tandem_XmlReader($test);
			$this_length = $xml_parser->getXMLValue(P_INSTALL_TEST_AVG_RUNTIME);

			if(is_numeric($this_length) && $this_length > 0)
			{
				$estimated_length += $this_length;
			}
			else
			{
				$xml_parser = new pts_test_tandem_XmlReader($test);
				$el = $xml_parser->getXMLValue(P_TEST_ESTIMATEDTIME);

				if(is_numeric($el) && $el > 0)
				{
					$estimated_length += ($el * 60);
				}
				else
				{
					return -1; // no accurate calculation available
				}
			}
		}
	}

	return $estimated_length;
}
function pts_test_architecture_supported($identifier)
{
	// Check if the system's architecture is supported by a test
	$supported = true;

	if(pts_is_test($identifier))
	{
	 	$xml_parser = new pts_test_tandem_XmlReader($identifier);
		$archs = $xml_parser->getXMLValue(P_TEST_SUPPORTEDARCHS);

		if(!empty($archs))
		{
			$archs = array_map("trim", explode(",", $archs));
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
				$un_platforms = array_map("trim", explode(",", $un_platforms));

				if(in_array(OPERATING_SYSTEM, $un_platforms))
				{
					$supported = false;
				}
			}
			if(!empty($platforms))
			{
				$platforms = array_map("trim", explode(",", $platforms));

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
	 	$xml_parser = new pts_test_tandem_XmlReader($identifier);
		$requires_core_version = $xml_parser->getXMLValue(P_TEST_SUPPORTS_COREVERSION);

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
function pts_call_test_script($test_identifier, $script_name, $print_string = "", $pass_argument = "", $extra_vars = null, $use_ctp = true)
{
	$result = null;
	$test_directory = TEST_ENV_DIR . $test_identifier . "/";

	$tests_r = ($use_ctp ? pts_contained_tests($test_identifier, true) : array($test_identifier));

	foreach($tests_r as $this_test)
	{
		if(is_file(($run_file = pts_location_test_resources($this_test) . $script_name . ".php")) || is_file(($run_file = pts_location_test_resources($this_test) . $script_name . ".sh")))
		{
			$file_extension = substr($run_file, (strrpos($run_file, ".") + 1));

			if(!empty($print_string))
			{
				echo $print_string;
			}

			if($file_extension == "php")
			{
				$this_result = pts_exec("cd " .  $test_directory . " && " . PHP_BIN . " " . $run_file . " \"" . $pass_argument . "\"", $extra_vars);
			}
			else if($file_extension == "sh")
			{
				$this_result = pts_exec("cd " .  $test_directory . " && sh " . $run_file . " \"" . $pass_argument . "\"", $extra_vars);
			}
			else
			{
				$this_result = null;
			}

			if(trim($this_result) != "")
			{
				$result = $this_result;
			}
		}
	}

	return $result;
}
function pts_cpu_arch_compatible($check_against)
{
	$compatible = true;
	$this_arch = phodevi::read_property("system", "kernel-architecture");
	$check_against = pts_to_array($check_against);

	if(strlen($this_arch) > 3 && substr($this_arch, -2) == "86")
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
				$platforms = array_map("trim", explode(",", $package_platform[$i]));
				$file_exempt = !in_array(OPERATING_SYSTEM, $platforms);
			}

			if(!empty($package_architecture[$i]))
			{
				$architectures = array_map("trim", explode(",", $package_architecture[$i]));
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
function pts_result_file_reference_tests($result)
{
	$xml_parser = new pts_results_tandem_XmlReader($result);
	$result_test = $xml_parser->getXMLValue(P_RESULTS_SUITE_NAME);
	$reference_tests = array();

	if(pts_is_suite($result_test))
	{
		$xml_parser = new pts_suite_tandem_XmlReader($result_test);
		$reference_systems_xml = $xml_parser->getXMLValue(P_SUITE_REFERENCE_SYSTEMS);
	}
	else if(pts_is_test($result_test))
	{
		$xml_parser = new pts_test_tandem_XmlReader($result_test);
		$reference_systems_xml = $xml_parser->getXMLValue(P_TEST_REFERENCE_SYSTEMS);
	}
	else
	{
		$reference_systems_xml = null;
	}

	foreach(array_map("trim", explode(",", $reference_systems_xml)) as $global_id)
	{
		if(pts_is_global_id($global_id))
		{
			if(!pts_is_test_result($global_id))
			{
				pts_clone_from_global($global_id, false);
			}
			array_push($reference_tests, $global_id);
		}
	}

	return $reference_tests;
}
function pts_archive_result_directory($identifier, $save_to = null)
{
	if($save_to == null)
	{
		$save_to = SAVE_RESULTS_DIR . $identifier . ".zip";
	}

	if(is_file(SAVE_RESULTS_DIR . $identifier . "/composite.xml"))
	{
		pts_compress(SAVE_RESULTS_DIR . $identifier . "/", $save_to);
	}
}

?>
