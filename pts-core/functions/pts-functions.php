<?php

/*
   Copyright (C) 2008, Michael Larabel.
   Copyright (C) 2008, Phoronix Media.

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

define("PTS_VERSION", "0.1.0");
define("PTS_LINE", "PTSV1LD");
define("PTS_TYPE", "DESKTOP");

define("THIS_RUN_TIME", time());

define("XML_PROFILE_LOCATION", "pts/benchmark-profiles/");
define("XML_SUITE_LOCATION", "pts/benchmark-suites/");
define("BENCHMARK_RESOURCE_LOCATION", "pts/benchmark-resources/");
define("BENCHMARK_ENVIRONMENT", pts_find_home(pts_read_user_config("PhoronixTestSuite/Options/Benchmarking/EnvironmentDirectory", "~/benchmark-env/")));
define("SAVE_RESULTS_LOCATION", pts_find_home(pts_read_user_config("PhoronixTestSuite/Options/Benchmarking/ResultsDirectory", "test-results/")));

// Load OS-specific functions
require_once("pts-core/functions/pts-functions_linux.php");

// Etc
$PTS_GLOBAL_ID = 1;

if(pts_process_active("phoronix-test-suite"))
{
	echo "\nWARNING: It appears that the Phoronix Test Suite is already running.\nFor proper results, only run one instance at a time.\n";
}
pts_process_register("phoronix-test-suite");
register_shutdown_function("pts_process_remove", "phoronix-test-suite");

function __autoload($to_load)
{
	if(is_file("pts-core/objects/$to_load.php"))
		require_once("pts-core/objects/$to_load.php");
}

// Phoronix Test Suite - Functions
function pts_find_home($path)
{
	if(strpos($path, '~') !== FALSE)
	{
		$whoami = trim(shell_exec("whoami"));

		if($whoami == "root")
			$home_path = "/root";
		else
			$home_path = "/home/$whoami";

		$path = str_replace('~', $home_path, $path);
	}
	return $path;
}
function pts_current_user()
{
	$pts_user = pts_read_user_config("PhoronixTestSuite/GlobalDatabase/UserName", "Default User");

	if($pts_user == "Default User")
		$pts_user = trim(shell_exec("whoami"));

	return $pts_user;
}
function pts_benchmark_names_to_array()
{
	$benchmark_names = array();
	foreach(glob(XML_PROFILE_LOCATION . "*.xml") as $benchmark_file)
	{
	 	$xml_parser = new tandem_XmlReader(file_get_contents($benchmark_file));
		$benchmark_name = $xml_parser->getXMLValue("PTSBenchmark/Information/Title");

		if(!empty($benchmark_name))
			array_push($benchmark_names, $benchmark_name);
	}
	return $benchmark_names;
}
function pts_suite_names_to_array()
{
	$benchmark_suites = array();
	foreach(glob(XML_SUITE_LOCATION . "*.xml") as $benchmark_file)
	{
	 	$xml_parser = new tandem_XmlReader(file_get_contents($benchmark_file));
		$benchmark_name = $xml_parser->getXMLValue("PTSuite/PhoronixTestSuite/Title");

		if(!empty($benchmark_name))
			array_push($benchmark_suites, $benchmark_name);
	}
	return $benchmark_suites;
}
function pts_benchmark_name_to_identifier($name)
{
	if(empty($name))
		return false;

	foreach(glob(XML_PROFILE_LOCATION . "*.xml") as $benchmark_file)
	{
	 	$xml_parser = new tandem_XmlReader(file_get_contents($benchmark_file));
		if($xml_parser->getXMLValue("PTSBenchmark/Information/Title") == $name)
			return $xml_parser->getXMLValue("PTSBenchmark/Information/Identifier");
	}
	return false;
}
function pts_benchmark_identifier_to_name($identifier)
{
	if(empty($identifier))
		return false;

	if(is_file(XML_PROFILE_LOCATION . "$identifier.xml"))
	{
	 	$xml_parser = new tandem_XmlReader(file_get_contents(XML_PROFILE_LOCATION . $identifier . ".xml"));
		if($xml_parser->getXMLValue("PTSBenchmark/Information/Identifier") == $identifier)
			return $xml_parser->getXMLValue("PTSBenchmark/Information/Title");
	}
	return false;
}
function pts_benchmark_type($identifier)
{
	if(empty($identifier))
		return false;

	if(is_file(XML_PROFILE_LOCATION . $identifier . ".xml"))
		return "BENCHMARK";
	else if(is_file(XML_SUITE_LOCATION . $identifier . ".xml"))
		return "TEST_SUITE";
	else
		return false;
}
function pts_save_result($save_to, $save_results)
{
	if(!is_dir(SAVE_RESULTS_LOCATION))
		mkdir(SAVE_RESULTS_LOCATION);

	if(!is_dir(SAVE_RESULTS_LOCATION . "pts-results-viewer"))
	{
		mkdir(SAVE_RESULTS_LOCATION . "pts-results-viewer"); // TODO: Clean this up
		copy("pts-core/pts-results-viewer/phoronix-test-suite.gif", SAVE_RESULTS_LOCATION . "pts-results-viewer/phoronix-test-suite.gif");
		copy("pts-core/pts-results-viewer/pts.js", SAVE_RESULTS_LOCATION . "pts-results-viewer/pts.js");
		copy("pts-core/pts-results-viewer/pts-results-viewer.xsl", SAVE_RESULTS_LOCATION . "pts-results-viewer/pts-results-viewer.xsl");
		copy("pts-core/pts-results-viewer/pts-viewer.css", SAVE_RESULTS_LOCATION . "pts-results-viewer/pts-viewer.css");
	}

	return file_put_contents(SAVE_RESULTS_LOCATION . $save_to, $save_results);
}
function pts_process_register($process)
{
	if(!is_dir(BENCHMARK_ENVIRONMENT))
		mkdir(BENCHMARK_ENVIRONMENT);
	if(!is_dir(BENCHMARK_ENVIRONMENT . ".processes"))
		mkdir(BENCHMARK_ENVIRONMENT . ".processes");

	return file_put_contents(BENCHMARK_ENVIRONMENT . ".processes/$process.p", time());
}
function pts_process_remove($process)
{
	if(is_file(BENCHMARK_ENVIRONMENT . ".processes/$process.p"))
		return unlink(BENCHMARK_ENVIRONMENT . ".processes/$process.p");
}
function pts_process_active($process)
{
	if(is_file(BENCHMARK_ENVIRONMENT . ".processes/$process.p"))
	{
		$process_time = intval(file_get_contents(BENCHMARK_ENVIRONMENT . ".processes/$process.p"));

		if((time() - $process_time) < 30) // TODO: Replace Lock With Pid based instead of time.
			return true;
		pts_process_remove($process);
	}
	return false;
}
function display_web_browser($URL)
{
	echo "Do you want to view the results in your web browser (Y/n)? ";
	$VIEW_RESULTS = strtolower(trim(fgets(STDIN)));

	if($VIEW_RESULTS == "y")
		shell_exec("firefox $URL &");
}
function pts_env_variables()
{
	return array(
	"PTS_TYPE" => PTS_TYPE,
	"PTS_LINE" => PTS_LINE,
	"PTS_VERSION" => PTS_VERSION,
	"NUM_CPU_CORES" => cpu_core_count(),
	"NUM_CPU_JOBS" => cpu_job_count(),
	"MEM_CAPACITY" => memory_mb_capacity(),
	"SCREEN_WIDTH" => current_screen_width(),
	"SCREEN_HEIGHT" => current_screen_height(),
	"OS" => os_vendor(),
	"OS_VERSION" => os_version(),
	"OS_ARCH" => kernel_arch(),
	"THIS_RUN_TIME" => THIS_RUN_TIME
	);
}
function pts_hw_string()
{
	$hw_string = "Processor: " . processor_string() . " (Total Cores: " . cpu_core_count() . "), ";
	$hw_string .= "Motherboard Chipset: " . motherboard_chipset_string() . ", ";
	$hw_string .= "System Memory: " . memory_mb_capacity() . "MB, ";
	$hw_string .= "Graphics: " . graphics_processor_string() . ", ";
	$hw_string .= "Screen Resolution: " . current_screen_width() . "x" . current_screen_height() . " ";

	return $hw_string;
}
function pts_sw_string()
{
	$sw_string = "OS: " . operating_system_release() . ", ";
	$sw_string .= "Kernel: " . kernel_string() . " (" . kernel_arch() . "), ";
	$sw_string .= "X.Org Server: " . graphics_subsystem_version() . ", ";
	$sw_string .= "Compiler: " . compiler_version() . " ";

	return $sw_string;
}
function pts_input_correct_results_path($path)
{
	if(strpos($path, '/') === FALSE)
	{
		$path = SAVE_RESULTS_LOCATION . $path;
	}
	if(strpos($MERGE_TO, ".xml") === FALSE)
	{
		$path = $path . ".xml";
	}
	return $path;
}
function pts_variables_export_string($vars = null)
{
	$return_string = "";

	if($vars == null)
		$vars = pts_env_variables();
	else
		$vars = array_merge(pts_env_variables(), $vars);

	foreach($vars as $name => $var)
	{
		$return_string .= "export $name=$var;";
	}
	return $return_string . " ";
}
function pts_exec($exec, $extra_vars = null)
{
	return shell_exec(pts_variables_export_string($extra_vars) . "$exec");
}
function pts_read_user_config($xml_pointer, $value = null)
{
	if(is_file("user-config.xml"))
		if(($file = file_get_contents("user-config.xml")) != FALSE)
		{
			$xml_parser = new tandem_XmlReader($file);
			unset($file);
			$temp_value = $xml_parser->getXmlValue($xml_pointer);

			if(!empty($temp_value))
				$value = $temp_value;
		}

	return $value;
}
function pts_request_new_id()
{
	global $PTS_GLOBAL_ID;
	$PTS_GLOBAL_ID++;

	return $PTS_GLOBAL_ID;
}
function pts_global_upload_result($result_file)
{
	echo "\nUploading: $result_file\n";
	$ToUpload = rawurlencode(base64_encode(file_get_contents($result_file)));
	$GlobalUser = pts_current_user();
	$Globalkey = pts_read_user_config("PhoronixTestSuite/GlobalDatabase/UploadKey", "");

	return file_get_contents("http://www.phoronix-test-suite.com/global/user-upload.php?result_xml=$ToUpload&global_user=$GlobalUser&global_key=$Globalkey"); // Rudimentary, but works
}
function operating_system_release()
{
	return os_vendor() . " " . os_version();
}


?>
