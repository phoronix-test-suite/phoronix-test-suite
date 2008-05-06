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

function pts_directory()
{
	$dir = getenv("PTS_DIR");

	if($dir == ".")
		$dir = "";

	if(!empty($dir))
	{
		if(substr($dir, -1) != '/')
			$dir .= '/';
	}
	
	return $dir;
}
function pts_download_cache()
{
	$dir = getenv("DOWNLOAD_CACHE");

	if(empty($dir))
		$dir = PTS_USER_DIR . "download-cache/";
	else
		if(substr($dir, -1) != '/')
			$dir .= '/';

	return $dir;
}

define("PTS_DIR", pts_directory());
define("PHP_BIN", getenv("PHP_BIN"));

function __autoload($to_load)
{
	if(is_file(PTS_DIR . "pts-core/objects/$to_load.php"))
		require_once(PTS_DIR . "pts-core/objects/$to_load.php");
}

// Load OS-specific functions
require_once("pts-core/functions/pts-functions_interfaces.php");
require_once("pts-core/functions/pts-functions_config.php");
require_once("pts-core/functions/pts-functions_system.php");
require_once("pts-core/functions/pts-functions_monitor.php");

define("PTS_VERSION", "0.5.1");
define("PTS_CODENAME", "TRONDHEIM");
define("PTS_TYPE", "DESKTOP");
define("THIS_RUN_TIME", time());

define("XML_PROFILE_DIR", PTS_DIR . "pts/test-profiles/");
define("XML_SUITE_DIR", PTS_DIR . "pts/test-suites/");
define("XML_DISTRO_DIR", PTS_DIR . "pts/distro-xml/");
define("SCRIPT_DISTRO_DIR", PTS_DIR . "pts/distro-scripts/");
define("TEST_RESOURCE_DIR", PTS_DIR . "pts/test-resources/");
define("ETC_DIR", PTS_DIR . "pts/etc/");
define("RESULTS_VIEWER_DIR", PTS_DIR . "pts-core/pts-results-viewer/");
define("PTS_USER_DIR", getenv("PTS_USER_DIR"));
define("PTS_DOWNLOAD_CACHE_DIR", pts_download_cache());
define("PTS_MONITOR_DIR", PTS_USER_DIR . strtolower(PTS_CODENAME) . '/');
//define("FONT_DIRECTORY" "/usr/share/fonts/");

pts_config_init();
define("BENCHMARK_ENV_DIR", pts_find_home(pts_read_user_config(P_OPTION_TEST_ENVIRONMENT, "~/.phoronix-test-suite/installed-tests/")));
define("SAVE_RESULTS_DIR", pts_find_home(pts_read_user_config(P_OPTION_RESULTS_DIRECTORY, "~/.phoronix-test-suite/test-results/")));

// Register PTS

if(pts_process_active("phoronix-test-suite"))
{
	pts_string_header("WARNING: It appears that the Phoronix Test Suite is already running.\nFor proper results, only run one instance at a time.");
}
pts_process_register("phoronix-test-suite");
register_shutdown_function("pts_shutdown");

// Etc

$PTS_GLOBAL_ID = 1;

if(($to_show = getenv("MONITOR")))
{
	$to_show = explode(',', $to_show);
	$monitor_all = in_array("all", $to_show);
	$monitor_temp = in_array("all.temp", $to_show) || $monitor_all;
	$monitor_power = in_array("all.power", $to_show) || $monitor_all;
	$monitor_voltage = in_array("all.voltage", $to_show) || $monitor_all;

	if(in_array("gpu.temp", $to_show)  || $monitor_temp)
	{
		define("MONITOR_GPU_TEMP", 1);
		$GPU_TEMPERATURE = array();
	}
	if(in_array("cpu.temp", $to_show)  || $monitor_temp)
	{
		
		define("MONITOR_CPU_TEMP", 1);
		$CPU_TEMPERATURE = array();
	}
	if(in_array("sys.temp", $to_show)  || $monitor_temp)
	{
		
		define("MONITOR_SYS_TEMP", 1);
		$SYS_TEMPERATURE = array();
	}
	if(in_array("battery.power", $to_show) || $monitor_power)
	{
		
		define("MONITOR_BATTERY_POWER", 1);
		$BATTERY_POWER = array();
	}
	if(in_array("cpu.voltage", $to_show) || $monitor_voltage)
	{
		
		define("MONITOR_CPU_VOLTAGE", 1);
		$CPU_VOLTAGE = array();
	}
	if(in_array("v3.voltage", $to_show) || $monitor_voltage)
	{
		
		define("MONITOR_V3_VOLTAGE", 1);
		$V3_VOLTAGE = array();
	}
	if(in_array("v5.voltage", $to_show) || $monitor_voltage)
	{
		
		define("MONITOR_V5_VOLTAGE", 1);
		$V5_VOLTAGE = array();
	}
	if(in_array("v12.voltage", $to_show) || $monitor_voltage)
	{
		
		define("MONITOR_V12_VOLTAGE", 1);
		$V12_VOLTAGE = array();
	}

	register_shutdown_function("pts_monitor_statistics");
}

// Phoronix Test Suite - Functions
function pts_benchmark_names_to_array()
{
	$benchmark_names = array();
	foreach(glob(XML_PROFILE_DIR . "*.xml") as $benchmark_file)
	{
	 	$xml_parser = new tandem_XmlReader(file_get_contents($benchmark_file));
		$benchmark_name = $xml_parser->getXMLValue(P_TEST_TITLE);

		if(!empty($benchmark_name))
			array_push($benchmark_names, $benchmark_name);
	}
	return $benchmark_names;
}
function pts_suite_names_to_array()
{
	$benchmark_suites = array();
	foreach(glob(XML_SUITE_DIR . "*.xml") as $benchmark_file)
	{
	 	$xml_parser = new tandem_XmlReader(file_get_contents($benchmark_file));
		$benchmark_name = $xml_parser->getXMLValue(P_SUITE_TITLE);

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

	foreach(glob(XML_PROFILE_DIR . "*.xml") as $benchmark_file)
	{
	 	$xml_parser = new tandem_XmlReader(file_get_contents($benchmark_file));

		if($xml_parser->getXMLValue(P_TEST_TITLE) == $name)
			$identifier = basename($benchmark_file, ".xml");
	}

	return $identifier;
}
function pts_benchmark_identifier_to_name($identifier)
{
	if(empty($identifier))
		return false;
	$name = false;

	if(is_file(XML_PROFILE_DIR . "$identifier.xml"))
	{
	 	$xml_parser = new tandem_XmlReader(file_get_contents(XML_PROFILE_DIR . $identifier . ".xml"));
		$name = $xml_parser->getXMLValue(P_TEST_TITLE);
	}

	return $name;
}
function pts_test_type($identifier)
{
	if(empty($identifier))
		return false;
	$test_type = false;

	if(is_file(XML_PROFILE_DIR . $identifier . ".xml"))
		$test_type = "BENCHMARK";
	else if(is_file(XML_SUITE_DIR . $identifier . ".xml"))
		$test_type = "TEST_SUITE";
	else
		$test_type = false;

	return $test_type;
}
function pts_copy($from, $to)
{
	if(!is_file($to) || md5_file($from) != md5_file($to))
		copy($from, $to);
}
function pts_save_user_file($save_name = null, $contents = null, $directory = '/')
{
	if(!is_dir(PTS_MONITOR_DIR))
		mkdir(PTS_MONITOR_DIR);

	if($directory != '/')
		if(!is_dir(PTS_MONITOR_DIR . $directory))
			mkdir(PTS_MONITOR_DIR . $directory);

	if(!empty($save_name) && !empty($contents))
		file_put_contents(PTS_USER_DIR . $extension . $directory . $save_name, $contents);
}
function pts_gd_available()
{
	if(!extension_loaded("gd"))
	{
	/*	if(dl("gd.so"))
		{
			$gd_available = true;
		}
		else	*/
			$gd_available = false;
			echo "\nThe PHP GD extension must be loaded in order for the graphs to display!\n";
	}
	else
		$gd_available = true;

	return $gd_available;
}
function pts_save_result($save_to = null, $save_results = null, $directory = null)
{
	if($directory == null)
		$directory = SAVE_RESULTS_DIR;

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

	pts_copy(RESULTS_VIEWER_DIR . "pts.js", $directory . "pts-results-viewer/pts.js");
	pts_copy(RESULTS_VIEWER_DIR . "pts-results-viewer.xsl", $directory . "pts-results-viewer/pts-results-viewer.xsl");
	pts_copy(RESULTS_VIEWER_DIR . "pts-viewer.css", $directory . "pts-results-viewer/pts-viewer.css");

	if(!is_file($save_to_dir . "/pts-results-viewer.xsl") && !is_link($save_to_dir . "/pts-results-viewer.xsl"))
		link($directory . "pts-results-viewer/pts-results-viewer.xsl", $save_to_dir . "/pts-results-viewer.xsl");
	
	if($save_to == null || $save_results == null)
		$bool = true;
	else
	{
		$save_name = basename($save_to, ".xml");

		if($save_name == "composite")
		{
			if(pts_gd_available())
			{
				if(!is_dir($save_to_dir . "/result-graphs"))
				{
					mkdir($save_to_dir . "/result-graphs");
				}

				$xml_reader = new tandem_XmlReader($save_results);
				$results_name = $xml_reader->getXMLArrayValues(P_RESULTS_TEST_TITLE);
				$results_version = $xml_reader->getXMLArrayValues(P_RESULTS_TEST_VERSION);
				$results_attributes = $xml_reader->getXMLArrayValues(P_RESULTS_TEST_ATTRIBUTES);
				$results_scale = $xml_reader->getXMLArrayValues(P_RESULTS_TEST_SCALE);
				$results_proportion = $xml_reader->getXMLArrayValues(P_RESULTS_TEST_PROPORTION);
				$results_result_format = $xml_reader->getXMLArrayValues(P_RESULTS_TEST_RESULTFORMAT);
				$results_raw = $xml_reader->getXMLArrayValues(P_RESULTS_RESULTS_GROUP);

				$results_identifiers = array();
				$results_values = array();

				foreach($results_raw as $result_raw)
				{
					$xml_results = new tandem_XmlReader($result_raw);
					array_push($results_identifiers, $xml_results->getXMLArrayValues(S_RESULTS_RESULTS_GROUP_IDENTIFIER));
					array_push($results_values, $xml_results->getXMLArrayValues(S_RESULTS_RESULTS_GROUP_VALUE));
				}

				for($i = 0; $i < count($results_name); $i++)
				{
					if(strlen($results_version[$i]) > 2)
						$results_name[$i] .= " v" . $results_version[$i];

					$t = new pts_BarGraph($results_name[$i], $results_attributes[$i], $results_scale[$i]);
					$t->loadGraphIdentifiers($results_identifiers[$i]);
					$t->loadGraphValues($results_values[$i], "#1");
					$t->loadGraphProportion($results_proportion[$i]);
					$t->loadGraphVersion(PTS_VERSION);
					$t->save_graph($save_to_dir . "/result-graphs/" . ($i + 1) . ".png");
					$t->renderGraph();
				}

				unset($xml_reader, $results_name, $results_version, $results_attributes, $results_scale, $results_proportion, $results_result_format, $results_raw, $results_identifiers, $results_values);
			}
		}

		$bool = file_put_contents($directory . $save_to, $save_results);
	}

	return $bool;
}
function pts_process_register($process)
{
	if(!is_dir(BENCHMARK_ENV_DIR))
		mkdir(BENCHMARK_ENV_DIR);
	if(!is_dir(BENCHMARK_ENV_DIR . ".processes"))
		mkdir(BENCHMARK_ENV_DIR . ".processes");

	return file_put_contents(BENCHMARK_ENV_DIR . ".processes/$process.p", time());
}
function pts_process_remove($process)
{
	if(is_file(BENCHMARK_ENV_DIR . ".processes/$process.p"))
		return unlink(BENCHMARK_ENV_DIR . ".processes/$process.p");
}
function pts_process_active($process)
{
	if(is_file(BENCHMARK_ENV_DIR . ".processes/$process.p"))
	{
		$process_time = intval(file_get_contents(BENCHMARK_ENV_DIR . ".processes/$process.p"));

		if((time() - $process_time) < 30) // TODO: Replace Lock With Pid based instead of time.
			return true;
		pts_process_remove($process);
	}
	return false;
}
function display_web_browser($URL, $alt_text = NULL, $default_open = FALSE)
{
	if($alt_text == NULL)
		$text = "Do you want to view the results in your web browser";
	else
		$text = $alt_text;

	if(!$default_open)
		$view_results = pts_bool_question($text . " (y/N)?", false, "OPEN_BROWSER");
	else
		$view_results = pts_bool_question($text . " (Y/n)?", true, "OPEN_BROWSER");

	if($view_results)
		shell_exec("./pts/launch-browser.sh \"$URL\" &");
}
function pts_env_variables()
{
	return array(
	"PTS_TYPE" => PTS_TYPE,
	"PTS_VERSION" => PTS_VERSION,
	"PTS_CODENAME" => PTS_CODENAME,
	"PTS_DIR" => PTS_DIR,
	"PHP_BIN" => PHP_BIN,
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
function pts_input_correct_results_path($path)
{
	if(strpos($path, '/') === FALSE)
	{
		$path = SAVE_RESULTS_DIR . $path;
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
	$benchmark_results = file_get_contents($result_file);
	$switch_tags = array("Benchmark>" => "B>", "Results>" => "R>", "Group>" => "G>", "Entry>" => "E>", "Identifier>" => "I>", "Value>" => "V>", "System>" => "S>", "Attributes>" => "A>");

	foreach($switch_tags as $f => $t)
		$benchmark_results = str_replace($f, $t, $benchmark_results);

	$benchmark_results = str_replace(array("\n", "\t"), "", $benchmark_results);

	echo $ToUpload = rawurlencode(base64_encode($benchmark_results));
	return;
	$GlobalUser = pts_current_user();
	$Globalkey = pts_read_user_config(P_OPTION_GLOBAL_UPLOADKEY, "");
	$tags = rawurlencode(base64_encode($tags));

	return @file_get_contents("http://www.phoronix-test-suite.com/global/user-upload.php?result_xml=$ToUpload&global_user=$GlobalUser&global_key=$Globalkey&tags=$tags"); // Rudimentary, but works
}
function pts_trim_double($double, $accuracy = 2)
{
	$return = explode(".", $double);

	if(count($return) == 1)
		$return[1] = "00";

	if(count($return) == 2)
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
				$auto_answer = pts_read_user_config(P_OPTION_BATCH_SAVERESULTS, "TRUE");
				break;
			case "OPEN_BROWSER":
				$auto_answer = pts_read_user_config(P_OPTION_BATCH_LAUNCHBROWSER, "FALSE");
				break;
			case "UPLOAD_RESULTS":
				$auto_answer = pts_read_user_config(P_OPTION_BATCH_UPLOADRESULTS, "TRUE");
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
	$str = str_replace($remove_phrases, " ", $str);

	$str = preg_replace("/\s+/", " ", $str);

	return $str;
}
function pts_string_header($heading)
{
	$header_size = 36;

	foreach(explode("\n", $heading) as $line)
		if(($line_length = strlen($line)) > $header_size)
			$header_size = $line_length;

	return "\n" . str_repeat('=', $header_size) . "\n" . $heading . "\n" . str_repeat('=', $header_size) . "\n\n";
}
function pts_exit($string = "")
{
	define("PTS_EXIT", 1);
	echo $string;
	exit(0);
}
function pts_version_comparable($old, $new)
{
	$old = explode('.', $old);
	$new = explode('.', $new);
	$compare = true;

	if(count($old) >= 2 && count($new) >= 2)
		if($old[0] != $new[0] || $old[1] != $new[1])
			$compare = false;

	return $compare;	
}
function pts_shutdown()
{
	// Ensure screensaver is back to being enabled
	if(defined("SCREENSAVER_KILLED"))
		shell_exec("gconftool --type bool --set /apps/gnome-screensaver/idle_activation_enabled true 2>&1");

	// Remove process
	pts_process_remove("phoronix-test-suite");
}
function pts_disable_screensaver()
{
	if(pts_read_user_config(P_OPTION_TEST_SCREENSAVER, "FALSE") == "TRUE")
	{
		shell_exec("gconftool --type bool --set /apps/gnome-screensaver/idle_activation_enabled false");
		define("SCREENSAVER_KILLED", 1);
	}
}
function pts_enable_screensaver()
{
	if(defined("SCREENSAVER_KILLED"))
		shell_exec("gconftool --type bool --set /apps/gnome-screensaver/idle_activation_enabled true");
}
function pts_interrupt_screensaver()
{
	if(!defined("SCREENSAVER_KILLED"))
		shell_exec("xdg-screensaver reset");
}

?>
