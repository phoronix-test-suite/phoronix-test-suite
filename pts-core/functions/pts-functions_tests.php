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

function pts_save_result($save_to = null, $save_results = null)
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

		if($save_name == "composite")
		{
			pts_generate_graphs($save_results, $save_to_dir);
		}
		$bool = file_put_contents(SAVE_RESULTS_DIR . $save_to, $save_results);

		if(pts_is_assignment("TEST_RESULTS_IDENTIFIER") && (pts_string_bool(pts_read_user_config(P_OPTION_LOG_VSYSDETAILS, "TRUE")) || pts_read_assignment("IS_PCQS_MODE") != false || getenv("SAVE_SYSTEM_DETAILS") != false || pts_is_assignment("IS_BATCH_MODE"))
		{
			$test_results_identifier = pts_read_assignment("TEST_RESULTS_IDENTIFIER");

			// Save verbose system information here
			if(!is_dir($save_to_dir . "/system-details/"))
			{
				mkdir($save_to_dir . "/system-details/");
			}
			if(!is_dir($save_to_dir . "/system-details/" . $test_results_identifier))
			{
				mkdir($save_to_dir . "/system-details/" . $test_results_identifier);
			}

			if(is_file("/var/log/Xorg.0.log"))
			{
				pts_copy("/var/log/Xorg.0.log", $save_to_dir . "/system-details/" . $test_results_identifier . "/Xorg.0.log");
			}

			// lspci
			$file = shell_exec("lspci 2>&1");

			if(strpos($file, "not found") == false)
			{
				@file_put_contents($save_to_dir . "/system-details/" . $test_results_identifier . "/lspci", $file);
			}

			// sensors
			$file = shell_exec("sensors 2>&1");

			if(strpos($file, "not found") == false)
			{
				@file_put_contents($save_to_dir . "/system-details/" . $test_results_identifier . "/sensors", $file);
			}

			// dmesg
			$file = shell_exec("dmesg 2>&1");

			if(strpos($file, "not found") == false)
			{
				@file_put_contents($save_to_dir . "/system-details/" . $test_results_identifier . "/dmesg", $file);
			}

			if(IS_MACOSX)
			{
				// system_profiler (Mac OS X)
				$file = shell_exec("system_profiler 2>&1");

				if(strpos($file, "not found") == false)
				{
					@file_put_contents($save_to_dir . "/system-details/" . $test_results_identifier . "/system_profiler", $file);
				}
			}

			// cpuinfo
			if(is_file("/proc/cpuinfo"))
			{
				$file = file_get_contents("/proc/cpuinfo");
				@file_put_contents($save_to_dir . "/system-details/" . $test_results_identifier . "/cpuinfo", $file);
			}
		}
		file_put_contents($save_to_dir . "/index.html", "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\"><html><head><title>Phoronix Test Suite</title><meta http-equiv=\"REFRESH\" content=\"0;url=composite.xml\"></HEAD><BODY></BODY></HTML>");
	}

	return $bool;
}
function pts_generate_graphs($test_results, $save_to_dir = null)
{
	if(!empty($save_to_dir) && !is_dir($save_to_dir . "/result-graphs"))
	{
		mkdir($save_to_dir . "/result-graphs");
	}

	$xml_reader = new tandem_XmlReader($test_results);

	$results_pts_version = $xml_reader->getXMLValue(P_RESULTS_SYSTEM_PTSVERSION);
	$results_suite_name = $xml_reader->getXMLValue(P_RESULTS_SUITE_NAME);

	if(empty($results_pts_version))
	{
		$results_pts_version = PTS_VERSION;
	}

	$results_name = $xml_reader->getXMLArrayValues(P_RESULTS_TEST_TITLE);
	$results_testname = $xml_reader->getXMLArrayValues(P_RESULTS_TEST_TESTNAME);
	$results_version = $xml_reader->getXMLArrayValues(P_RESULTS_TEST_VERSION);
	$results_attributes = $xml_reader->getXMLArrayValues(P_RESULTS_TEST_ATTRIBUTES);
	$results_scale = $xml_reader->getXMLArrayValues(P_RESULTS_TEST_SCALE);
	$results_proportion = $xml_reader->getXMLArrayValues(P_RESULTS_TEST_PROPORTION);
	$results_result_format = $xml_reader->getXMLArrayValues(P_RESULTS_TEST_RESULTFORMAT);
	$results_raw = $xml_reader->getXMLArrayValues(P_RESULTS_RESULTS_GROUP);

	$results_identifiers = array();
	$results_values = array();
	$results_rawvalues = array();

	foreach($results_raw as $result_raw)
	{
		$xml_results = new tandem_XmlReader($result_raw);
		array_push($results_identifiers, $xml_results->getXMLArrayValues(S_RESULTS_RESULTS_GROUP_IDENTIFIER));
		array_push($results_values, $xml_results->getXMLArrayValues(S_RESULTS_RESULTS_GROUP_VALUE));
		array_push($results_rawvalues, $xml_results->getXMLArrayValues(S_RESULTS_RESULTS_GROUP_RAW));
	}

	for($i = 0; $i < count($results_name); $i++)
	{
		if(strlen($results_version[$i]) > 2)
		{
			$results_name[$i] .= " v" . $results_version[$i];
		}

		if($results_result_format[$i] == "LINE_GRAPH")
		{
			$t = new pts_LineGraph($results_name[$i], $results_attributes[$i], $results_scale[$i]);
		}
		else if($results_result_format[$i] == "PASS_FAIL")
		{
			$t = new pts_PassFailGraph($results_name[$i], $results_attributes[$i], $results_scale[$i]);
		}
		else if($results_result_format[$i] == "MULTI_PASS_FAIL")
		{
			$t = new pts_MultiPassFailGraph($results_name[$i], $results_attributes[$i], $results_scale[$i]);
		}
		else if(pts_read_assignment("GRAPH_RENDER_TYPE") == "CANDLESTICK")
		{
			$t = new pts_CandleStickGraph($results_name[$i], $results_attributes[$i], $results_scale[$i]);
		}
		else
		{
			$t = new pts_BarGraph($results_name[$i], $results_attributes[$i], $results_scale[$i]);
		}

		if(getenv("REVERSE_GRAPH_ORDER") != false)
		{
			// Plot results in reverse order on graphs if REVERSE_GRAPH_PLOTTING env variable is set
			$results_identifiers[$i] = array_reverse($results_identifiers[$i]);
			$results_values[$i] = array_reverse($results_values[$i]);
		}

		$t->loadGraphIdentifiers($results_identifiers[$i]);
		$t->loadGraphValues($results_values[$i]);
		$t->loadGraphRawValues($results_rawvalues[$i]);
		$t->loadGraphProportion($results_proportion[$i]);
		$t->loadGraphVersion($results_pts_version);

		$t->addInternalIdentifier("Test", $results_testname[$i]);
		$t->addInternalIdentifier("Identifier", $results_suite_name);
		$t->addInternalIdentifier("User", pts_current_user());

		if(!empty($save_to_dir))
		{
			$t->saveGraphToFile($save_to_dir . "/result-graphs/" . ($i + 1) . ".BILDE_EXTENSION");
		}

		$t->renderGraph();

		if(!empty($save_to_dir))
		{
			file_put_contents($save_to_dir . "/pts-results-viewer.xsl", pts_get_results_viewer_xsl_formatted($t->getRenderer(), $t->graphWidth(), $t->graphHeight()));
		}
	}
}
function pts_subsystem_test_types()
{
	return array("System", "Processor", "Disk", "Graphics", "Memory", "Network");
}
function pts_get_results_viewer_xsl_formatted($format_type = "PNG", $width, $height)
{
	$raw_xsl = file_get_contents(RESULTS_VIEWER_DIR . "pts-results-viewer.xsl");

	if($format_type == "SVG")
	{
		$graph_string = "<object type=\"image/svg+xml\"><xsl:attribute name=\"data\">result-graphs/<xsl:number value=\"position()\" />.svg</xsl:attribute></object>";
	}
	else if($format_type == "SWF")
	{
		$graph_string = "<object classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\" codebase=\"http://active.macromedia.com/flash2/cabs/swflash.cab#version=4,0,0,0\" id=\"objects\" width=\"" . $width . "\" height=\"" . $height . "\"><param name=\"movie\"><xsl:attribute name=\"value\">result-graphs/<xsl:number value=\"position()\" />.swf</xsl:attribute></param><embed width=\"" . $width . "\" height=\"" . $height . "\" type=\"application/x-shockwave-flash\" pluginspage=\"http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash\"><xsl:attribute name=\"src\">result-graphs/<xsl:number value=\"position()\" />.swf</xsl:attribute></embed></object>";
	}
	else
	{
		// Default to img
		$graph_string = "<img><xsl:attribute name=\"src\">result-graphs/<xsl:number value=\"position()\" />." . strtolower($format_type) . "</xsl:attribute></img>";
	}

	return str_replace("<!-- GRAPH TAGS -->", $graph_string, $raw_xsl);
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
function pts_test_installed($identifier)
{
	return is_file(TEST_ENV_DIR . $identifier . "/pts-install.xml");
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
function pts_test_generate_install_xml($identifier)
{
	// Generate an install XML for pts-install.xml
	return pts_test_refresh_install_xml($identifier, 0, true);
}
function pts_test_refresh_install_xml($identifier, $this_test_duration = 0, $new_install = false)
{
	// Generate/refresh an install XML for pts-install.xml
 	$xml_parser = new pts_installed_test_tandem_XmlReader($identifier, false);
	$xml_writer = new tandem_XmlWriter();

	$test_duration = $xml_parser->getXMLValue(P_INSTALL_TEST_AVG_RUNTIME);
	if(!is_numeric($test_duration))
	{
		$test_duration = $this_test_duration;
	}
	if(is_numeric($this_test_duration) && $this_test_duration > 0)
	{
		$test_duration = ceil((($test_duration * $xml_parser->getXMLValue(P_INSTALL_TEST_TIMESRUN)) + $this_test_duration) / ($xml_parser->getXMLValue(P_INSTALL_TEST_TIMESRUN) + 1));
	}

	$test_version = $xml_parser->getXMLValue(P_INSTALL_TEST_VERSION);
	if(empty($test_version) || $new_install)
	{
		$test_version = pts_test_profile_version($identifier);
	}

	$test_checksum = $xml_parser->getXMLValue(P_INSTALL_TEST_CHECKSUM);
	if(empty($test_checksum) || $new_install)
	{
		$test_checksum = pts_test_checksum_installer($identifier);
	}

	$sys_identifier = $xml_parser->getXMLValue(P_INSTALL_TEST_SYSIDENTIFY);
	if(empty($sys_identifier) || $new_install)
	{
		$sys_identifier = pts_system_identifier_string();
	}

	$install_time = $xml_parser->getXMLValue(P_INSTALL_TEST_INSTALLTIME);
	if(empty($install_time))
	{
		$install_time = date("Y-m-d H:i:s");
	}

	$times_run = $xml_parser->getXMLValue(P_INSTALL_TEST_TIMESRUN);
	if($new_install && empty($times_run))
	{
		$times_run = 0;
	}
	if(!$new_install)
		$times_run++;

	$xml_writer->addXmlObject(P_INSTALL_TEST_NAME, 1, $identifier);
	$xml_writer->addXmlObject(P_INSTALL_TEST_VERSION, 1, $test_version);
	$xml_writer->addXmlObject(P_INSTALL_TEST_CHECKSUM, 1, $test_checksum);
	$xml_writer->addXmlObject(P_INSTALL_TEST_SYSIDENTIFY, 1, $sys_identifier);
	$xml_writer->addXmlObject(P_INSTALL_TEST_INSTALLTIME, 2, $install_time);
	$xml_writer->addXmlObject(P_INSTALL_TEST_LASTRUNTIME, 2, date("Y-m-d H:i:s"));
	$xml_writer->addXmlObject(P_INSTALL_TEST_TIMESRUN, 2, $times_run);
	$xml_writer->addXmlObject(P_INSTALL_TEST_AVG_RUNTIME, 2, $test_duration, 2);

	file_put_contents(TEST_ENV_DIR . $identifier . "/pts-install.xml", $xml_writer->getXML());
}
function pts_test_name_to_identifier($name)
{
	// Convert test name to identifier
	$identifier = false;

	if(!empty($name))
	{
		foreach(glob(XML_PROFILE_DIR . "*.xml") as $test_profile_file)
		{
		 	$xml_parser = new tandem_XmlReader($test_profile_file);

			if($xml_parser->getXMLValue(P_TEST_TITLE) == $name)
			{
				$identifier = basename($test_profile_file, ".xml");
			}
		}
	}

	return $identifier;
}
function pts_test_identifier_to_name($identifier)
{
	// Convert identifier to test name
	$name = false;

	if(!empty($identifier) && pts_is_test($identifier))
	{
	 	$xml_parser = new pts_test_tandem_XmlReader($identifier);
		$name = $xml_parser->getXMLValue(P_TEST_TITLE);
	}

	return $name;
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
function pts_test_estimated_environment_size($identifier)
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
function pts_test_estimated_run_time($identifier)
{
	// Estimate the time it takes (in seconds) to complete the given test
	$estimated_length = 0;

	foreach(pts_contained_tests($identifier) as $test)
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
				// TODO: Likely integrate with P_TEST_ESTIMATEDTIME from test profile
				return -1; // TODO: no accurate calculation not available
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
			$archs = explode(",", $archs);

			foreach($archs as $key => $value)
			{
				$archs[$key] = trim($value);
			}

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
				$un_platforms = explode(",", $un_platforms);

				foreach($un_platforms as $key => $value)
				{
					$un_platforms[$key] = trim($value);
				}

				if(in_array(OPERATING_SYSTEM, $un_platforms))
				{
					$supported = false;
				}
			}
			if(!empty($platforms))
			{
				$platforms = explode(",", $platforms);

				foreach($platforms as $key => $value)
				{
					$platforms[$key] = trim($value);
				}

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
function pts_suite_supported($identifier)
{
	$tests = pts_contained_tests($identifier, true);
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
	$tests = array_unique(array_merge($tests, $local_tests));
	asort($tests);

	for($i = 0; $i < count($tests); $i++)
	{
		$tests[$i] = basename($tests[$i], ".xml");
	}

	return $tests;
}
function pts_supported_tests_array()
{
	$supported_tests = array();

	foreach(pts_available_tests_array() as $identifier)
	{
		if(pts_test_supported($identifier))
		{
			array_push($supported_tests, $identifier);
		}
	}

	return $supported_tests;
}
function pts_installed_tests_array()
{
	$tests = glob(TEST_ENV_DIR . "*/pts-install.xml");

	for($i = 0; $i < count($tests); $i++)
	{
		$install_file_arr = explode("/", $tests[$i]);
		$tests[$i] = $install_file_arr[count($install_file_arr) - 2];
	}

	return $tests;
}
function pts_available_suites_array()
{
	$suites = glob(XML_SUITE_DIR . "*.xml");
	$local_suites = glob(XML_SUITE_LOCAL_DIR . "*.xml");
	$suites = array_unique(array_merge($suites, $local_suites));
	asort($suites);

	for($i = 0; $i < count($suites); $i++)
	{
		$suites[$i] = basename($suites[$i], ".xml");
	}

	return $suites;
}
function pts_test_version_compatible($version_compare = "")
{
	$compatible = true;

	if(!empty($version_compare))
	{
		$current = pts_remove_chars(PTS_VERSION, true, false, false);

		$version_compare = explode("-", $version_compare);	
		$support_begins = pts_remove_chars(trim($version_compare[0]), true, false, false);

		if(count($version_compare) == 2)
		{
			$support_ends = trim($version_compare[1]);
		}
		else
		{
			$support_ends = PTS_VERSION;
		}
		$support_ends = pts_remove_chars(trim($support_ends), true, false, false);

		$compatible = $current >= $support_begins && $current <= $support_ends;
	}

	return $compatible;	
}
function pts_call_test_script($test_identifier, $script_name, $print_string = "", $pass_argument = "", $extra_vars = null, $use_ctp = true)
{
	$result = null;
	$test_directory = TEST_ENV_DIR . $test_identifier . "/";

	if($use_ctp)
	{
		$tests_r = pts_contained_tests($test_identifier, true);
	}
	else
	{
		$tests_r = array($test_identifier);
	}

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
	$this_arch = sw_os_architecture();
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
				$platforms = explode(",", $package_platform[$i]);

				foreach($platforms as $key => $value)
				{
					$platforms[$key] = trim($value);
				}

				$file_exempt = !in_array(OPERATING_SYSTEM, $platforms);
			}

			if(!empty($package_architecture[$i]))
			{
				$architectures = explode(",", $package_architecture[$i]);

				foreach($architectures as $key => $value)
				{
					$architectures[$key] = trim($value);
				}

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
function pts_remove_saved_result($identifier)
{
	// Remove a saved result file
	$return_value = false;

	if(is_file(SAVE_RESULTS_DIR . $identifier . "/composite.xml"))
	{
		@unlink(SAVE_RESULTS_DIR . $identifier . "/composite.xml");

		foreach(glob(SAVE_RESULTS_DIR . $identifier . "/result-graphs/*.png") as $remove_file)
		{
			@unlink($remove_file);
		}
		foreach(glob(SAVE_RESULTS_DIR . $identifier . "/result-graphs/*.svg") as $remove_file)
		{
			@unlink($remove_file);
		}

		foreach(glob(SAVE_RESULTS_DIR . $identifier . "/test-*.xml") as $remove_file)
		{
			@unlink($remove_file);
		}

		@unlink(SAVE_RESULTS_DIR . $identifier . "/pts-results-viewer.xsl");
		@rmdir(SAVE_RESULTS_DIR . $identifier . "/result-graphs/");
		@rmdir(SAVE_RESULTS_DIR . $identifier);
		echo "Removed: $identifier\n";
		$return_value = true;
	}
	return $return_value;
}
function pts_print_format_tests($object, &$write_buffer, $steps = -1)
{
	// Print out a text tree that shows the suites and tests within an object
	$steps++;
	if(pts_is_suite($object))
	{
		$xml_parser = new pts_suite_tandem_XmlReader($object);
		$tests_in_suite = array_unique($xml_parser->getXMLArrayValues(P_SUITE_TEST_NAME));

		if($steps > 0)
		{
			asort($tests_in_suite);
		}

		if($steps == 0)
		{
			$write_buffer .= $object . "\n";
		}
		else
		{
			$write_buffer .= str_repeat("  ", $steps) . "+ " . $object . "\n";
		}

		foreach($tests_in_suite as $test)
		{
			$write_buffer .= pts_print_format_tests($test, $write_buffer, $steps);
		}
	}
	else
	{
		$write_buffer .= str_repeat("  ", $steps) . "* " . $object . "\n";
	}
}
function pts_dependency_name($dependency)
{
	// Find the name of a dependency
	$return_title = "";
	if(is_file(XML_DISTRO_DIR . "generic-packages.xml"))
	{
		$xml_parser = new tandem_XmlReader(XML_DISTRO_DIR . "generic-packages.xml");
		$package_name = $xml_parser->getXMLArrayValues(P_EXDEP_PACKAGE_GENERIC);
		$title = $xml_parser->getXMLArrayValues(P_EXDEP_PACKAGE_TITLE);

		for($i = 0; $i < count($title) && empty($return_title); $i++)
		{
			if($dependency == $package_name[$i])
			{
				$return_title = $title[$i];
			}
		}
	}

	return $return_title;
}

?>
