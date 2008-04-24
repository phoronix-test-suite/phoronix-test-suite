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

function __autoload($to_load)
{
	if(is_file("pts-core/objects/$to_load.php"))
		require_once("pts-core/objects/$to_load.php");
}

// Load OS-specific functions
require_once("pts-core/functions/pts-functions_config.php");
require_once("pts-core/functions/pts-functions_linux.php");

define("PTS_VERSION", "0.3.1");
define("PTS_CODENAME", "TRONDHEIM");
define("PTS_TYPE", "DESKTOP");

define("THIS_RUN_TIME", time());

define("XML_PROFILE_LOCATION", "pts/benchmark-profiles/");
define("XML_SUITE_LOCATION", "pts/benchmark-suites/");
define("DISTRO_XML_LOCATION", "pts/distro-xml/");
define("DISTRO_SCRIPT_LOCATION", "pts/distro-scripts/");
define("BENCHMARK_RESOURCE_LOCATION", "pts/benchmark-resources/");
define("PTS_USER_DIR", pts_find_home("~/.phoronix-test-suite/"));

pts_user_config_init();
define("BENCHMARK_ENVIRONMENT", pts_find_home(pts_read_user_config("PhoronixTestSuite/Options/Benchmarking/EnvironmentDirectory", "~/.phoronix-test-suite/installed-tests/")));
define("SAVE_RESULTS_LOCATION", pts_find_home(pts_read_user_config("PhoronixTestSuite/Options/Results/Directory", "~/.phoronix-test-suite/test-results/")));

// Etc
$PTS_GLOBAL_ID = 1;

if(pts_process_active("phoronix-test-suite"))
{
	echo "\nWARNING: It appears that the Phoronix Test Suite is already running.\nFor proper results, only run one instance at a time.\n";
}
pts_process_register("phoronix-test-suite");
register_shutdown_function("pts_process_remove", "phoronix-test-suite");

// Phoronix Test Suite - Functions
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
	$identifier = false;

	foreach(glob(XML_PROFILE_LOCATION . "*.xml") as $benchmark_file)
	{
	 	$xml_parser = new tandem_XmlReader(file_get_contents($benchmark_file));

		if($xml_parser->getXMLValue("PTSBenchmark/Information/Title") == $name)
			$identifier = basename($benchmark_file, ".xml");
	}

	return $identifier;
}
function pts_benchmark_identifier_to_name($identifier)
{
	if(empty($identifier))
		return false;
	$name = false;

	if(is_file(XML_PROFILE_LOCATION . "$identifier.xml"))
	{
	 	$xml_parser = new tandem_XmlReader(file_get_contents(XML_PROFILE_LOCATION . $identifier . ".xml"));
		$name = $xml_parser->getXMLValue("PTSBenchmark/Information/Title");
	}

	return $name;
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
function pts_copy($from, $to)
{
	if(!is_file($to) || md5_file($from) != md5_file($to))
		copy($from, $to);
}
function pts_save_result($save_to = null, $save_results = null, $directory = null)
{
	if($directory == null)
		$directory = SAVE_RESULTS_LOCATION;

	if(strpos($save_to, ".xml") === FALSE)
	{
		$save_to .= ".xml";
	}

	$save_to_dir = dirname($directory . $save_to);

	if(!is_dir($directory))
		mkdir($directory);
	if($save_to_dir != '.' && !is_dir($save_to_dir))
		mkdir($save_to_dir);

	if(!is_dir($directory . "pts-results-viewer"))
	{
		mkdir($directory . "pts-results-viewer");
	}

	//pts_copy("pts-core/pts-results-viewer/phoronix-test-suite.gif", $directory . "pts-results-viewer/phoronix-test-suite.gif");
	pts_copy("pts-core/pts-results-viewer/pts.js", $directory . "pts-results-viewer/pts.js");
	pts_copy("pts-core/pts-results-viewer/pts-results-viewer.xsl", $directory . "pts-results-viewer/pts-results-viewer.xsl");
	pts_copy("pts-core/pts-results-viewer/pts-viewer.css", $directory . "pts-results-viewer/pts-viewer.css");

	if(!is_file($save_to_dir . "/pts-results-viewer.xsl") && !is_link($save_to_dir . "/pts-results-viewer.xsl"))
		link($directory . "pts-results-viewer/pts-results-viewer.xsl", $save_to_dir . "/pts-results-viewer.xsl");
	
	if($save_to == null || $save_results == null)
		$bool = true;
	else
	{
		// Create graphs locally or remotely

	/*	if(!extension_loaded("gd"))
		{
			if(dl("gd.so"))
			{
				$gd_available = true;
			}
			else
				$gd_available = false;
		}
		else
			$gd_available = true;

		if($gd_available)
		{
			if(!is_dir($directory . "result-graphs"))
			{
				mkdir($directory . "result-graphs");
			}

			$basename = basename($save_to, ".xml");
			if(!is_dir($directory . "result-graphs" . $basename))
			{
				mkdir($directory . "result-graphs" . $basename);
			}

			$xml_reader = new tandem_XmlReader($save_results);
			$results_name = $xml_reader->getXMLArrayValues("PhoronixTestSuite/Benchmark/Name");
			$results_version = $xml_reader->getXMLArrayValues("PhoronixTestSuite/Benchmark/Version");
			$results_attributes = $xml_reader->getXMLArrayValues("PhoronixTestSuite/Benchmark/Attributes");
			$results_scale = $xml_reader->getXMLArrayValues("PhoronixTestSuite/Benchmark/Scale");
			$results_proportion = $xml_reader->getXMLArrayValues("PhoronixTestSuite/Benchmark/Proportion");
			$results_result_format = $xml_reader->getXMLArrayValues("PhoronixTestSuite/Benchmark/ResultFormat");
			$results_raw = $xml_reader->getXMLArrayValues("PhoronixTestSuite/Benchmark/Results");

			$results_identifiers = array();
			$results_values = array();

			foreach($results_raw as $result_raw)
			{
				$xml_results = new tandem_XmlReader($result_raw);
				array_push($results_identifiers, $xml_results->getXMLArrayValues("Group/Entry/Identifier"));
				array_push($results_values, $xml_results->getXMLArrayValues("Group/Entry/Value"));
			}

			for($i = 0; $i < count($results_name); $i++)
			{
				if(strlen($results_version[$i]) > 2)
					$results_name[$i] .= " v" . $results_version[$i];

				$t = new pts_BarGraph($results_name[$i], $results_attributes[$i], $results_scale[$i]);
				$t->loadGraphIdentifiers($results_identifiers[$i]);
				$t->loadGraphValues($results_values[$i], "#1");
				$t->save_graph($directory . "result-graphs" . $basename . "/" . ($i + 1) . ".png");
				$t->renderGraph();
			}

			unset($xml_reader, $results_name, $results_version, $results_attributes, $results_scale, $results_proportion, $results_result_format, $results_raw, $results_identifiers, $results_values);
		}
		 */

		$bool = file_put_contents($directory . $save_to, $save_results);
	}

	return $bool;
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
	$view_results = pts_bool_question("Do you want to view the results in your web browser (y/N)?", false, "OPEN_BROWSER");

	if($view_results)
		shell_exec("./pts/launch-browser.sh $URL &");
}
function pts_env_variables()
{
	return array(
	"PTS_TYPE" => PTS_TYPE,
	"PTS_VERSION" => PTS_VERSION,
	"NUM_CPU_CORES" => cpu_core_count(),
	"NUM_CPU_JOBS" => cpu_job_count(),
	"SYS_MEMORY" => memory_mb_capacity(),
	"VIDEO_MEMORY" => graphics_memory_capacity(),
	"VIDEO_WIDTH" => current_screen_width(),
	"VIDEO_HEIGHT" => current_screen_height(),
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
	$hw_string .= "Total Disk Space: " . pts_posix_disk_total() . "GB, ";
	$hw_string .= "Graphics: " . graphics_processor_string() . ", ";
	$hw_string .= "Screen Resolution: " . current_screen_resolution() . " ";

	return $hw_string;
}
function pts_sw_string()
{
	$sw_string = "OS: " . operating_system_release() . ", ";
	$sw_string .= "Kernel: " . kernel_string() . " (" . kernel_arch() . "), ";
	$sw_string .= "X.Org Server: " . graphics_subsystem_version() . ", ";
	$sw_string .= "OpenGL: " . opengl_version() . ", ";
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
function pts_request_new_id()
{
	global $PTS_GLOBAL_ID;
	$PTS_GLOBAL_ID++;

	return $PTS_GLOBAL_ID;
}
function pts_global_upload_result($result_file, $tags = "")
{
	echo "\nUploading: $result_file\n";
	$ToUpload = rawurlencode(base64_encode(file_get_contents($result_file)));
	$GlobalUser = pts_current_user();
	$Globalkey = pts_read_user_config("PhoronixTestSuite/GlobalDatabase/UploadKey", "");
	$tags = rawurlencode(base64_encode($tags));

	return @file_get_contents("http://www.phoronix-test-suite.com/global/user-upload.php?result_xml=$ToUpload&global_user=$GlobalUser&global_key=$Globalkey&tags=$tags"); // Rudimentary, but works
}
function pts_trim_double($double, $accuracy = 2)
{
	// this function is to avoid using bcmath

	$return = explode(".", $double);

	if(count($return) > 1)
	{
		$strlen = strlen($return[1]);

		if($strlen > $accuracy)
			$return[1] = substr($return[1], 0, $accuracy);
		else if($strlen < $accuracy)
			for($i = $strlen; $i < $accuracy; $i++)
				$return[1] .= "0";

		$return = $return[0] . "." . $return[1];
	}
	else
		$return = $return[0];

	return $return;
}
function pts_bool_question($question, $default = true, $question_id = "UNKNOWN")
{
	if(defined("PTS_BATCH_MODE"))
	{
		switch($question_id)
		{
			case "SAVE_RESULTS":
				$auto_answer = pts_read_user_config("PhoronixTestSuite/Options/BatchMode/SaveResults", "TRUE");
				break;
			case "OPEN_BROWSER":
				$auto_answer = pts_read_user_config("PhoronixTestSuite/Options/BatchMode/OpenBrowser", "FALSE");
				break;
			case "UPLOAD_RESULTS":
				$auto_answer = pts_read_user_config("PhoronixTestSuite/Options/BatchMode/UploadResults", "TRUE");
				break;
		}

		if(isset($auto_answer))
			$answer = $auto_answer == "TRUE" || $auto_answer == "1";
		else
			$answer = $default;
	}
	else
	{
		do
		{
			echo $question . " ";
			$input = trim(strtolower(fgets(STDIN)));
		}
		while($input != "y" && $input != "n" && $input != "");

		if($input == "y")
			$answer = true;
		else if($input == "n")
			$answer = false;
		else
			$answer = $default;
	}

	return $answer;
}
function pts_clean_information_string($str)
{
	$remove_phrases = array("Corporation ", "Technologies ", ",", "Technology ", "version ", "Processor ", "processor ", "Genuine ", "(R)", "(TM)", "(tm)", "Inc. ", "Inc ");
	$str = str_replace($remove_phrases, "", $str);

	$str = preg_replace("/\s+/", ' ', $str);

	return $str;
}

?>
