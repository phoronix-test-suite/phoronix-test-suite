<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts-functions.php: General functions required for Phoronix Test Suite operation.

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

require_once("pts-core/functions/pts.php");
require_once("pts-core/functions/pts-init.php");

// Load Main Functions
require_once("pts-core/objects/pts-generic-classes.php");
require_once("pts-core/functions/pts-interfaces.php");
require_once("pts-core/functions/pts-functions_shell.php");
require_once("pts-core/functions/pts-functions_config.php");
require_once("pts-core/functions/pts-functions_system.php");
require_once("pts-core/functions/pts-functions_tests.php");
require_once("pts-core/functions/pts-functions_types.php");
require_once("pts-core/functions/pts-functions_modules.php");

if(IS_SCTP_MODE)
{
	include_once("pts-core/functions/pts-functions-sctp.php");
}

// User's home directory for storing results, module files, test installations, etc.
define("PTS_DIR", pts_directory());
define("PTS_USER_DIR", pts_user_home() . ".phoronix-test-suite/");

// Distribution External Dependency Locations
define("XML_DISTRO_DIR", PTS_DIR . "pts/distro-xml/");
define("SCRIPT_DISTRO_DIR", PTS_DIR . "pts/distro-scripts/");

// Misc Locations
define("ETC_DIR", PTS_DIR . "pts/etc/");
define("MODULE_DIR", PTS_DIR . "pts-core/modules/");
define("RESULTS_VIEWER_DIR", PTS_DIR . "pts-core/results-viewer/");
define("TEST_LIBRARIES_DIR", PTS_DIR . "pts-core/test-libraries/");
define("STATIC_DIR", PTS_DIR . "pts-core/static/");
define("FONT_DIR", RESULTS_VIEWER_DIR . "fonts/");

// Test & Suite Locations
define("XML_PROFILE_DIR", PTS_DIR . "pts/test-profiles/");
define("XML_PROFILE_CTP_BASE_DIR", XML_PROFILE_DIR . "base/");
define("XML_SUITE_DIR", PTS_DIR . "pts/test-suites/");
define("TEST_RESOURCE_DIR", PTS_DIR . "pts/test-resources/");
define("TEST_RESOURCE_CTP_BASE_DIR", TEST_RESOURCE_DIR . "base/");
define("XML_PROFILE_LOCAL_DIR", PTS_USER_DIR . "test-profiles/");
define("XML_PROFILE_LOCAL_CTP_BASE_DIR", XML_PROFILE_LOCAL_DIR . "base/");
define("XML_SUITE_LOCAL_DIR", PTS_USER_DIR . "test-suites/");
define("TEST_RESOURCE_LOCAL_DIR", PTS_USER_DIR . "test-resources/");
define("TEST_RESOURCE_LOCAL_CTP_BASE_DIR", TEST_RESOURCE_LOCAL_DIR . "base/");

// Phoronix Test Suite - Functions
function p_str($str_o)
{
	//  $_ENV["LANG"]
	return $str_o;
}
function pts_gd_available()
{
	// Checks if PHP GD library is available for image rendering

	if(!extension_loaded("gd"))
	{
	/*	if(dl("gd.so"))
		{
			$gd_available = true;
		}
		else	*/
			$gd_available = false;
	}
	else
	{
		$gd_available = true;
	}

	return $gd_available;
}
function pts_process_register($process)
{
	// Register a process as active
	if(!is_dir(TEST_ENV_DIR))
	{
		mkdir(TEST_ENV_DIR);
	}
	if(!is_dir(TEST_ENV_DIR . ".processes"))
	{
		mkdir(TEST_ENV_DIR . ".processes");
	}

	return file_put_contents(TEST_ENV_DIR . ".processes/" . $process . ".p", getmypid());
}
function pts_process_remove($process)
{
	// Remove a process from being active, if present
	return is_file(TEST_ENV_DIR . ".processes/" . $process . ".p") && @unlink(TEST_ENV_DIR . ".processes/" . $process . ".p");
}
function pts_process_active($process)
{
	// Register a process as active
	$active = false;
	if(is_file(TEST_ENV_DIR . ".processes/" . $process . ".p") && !IS_SOLARIS)
	{
		$pid = trim(@file_get_contents(TEST_ENV_DIR . ".processes/" . $process . ".p"));
		$ps = trim(shell_exec("ps -p $pid 2>&1"));

		if(strpos($ps, "php") > 0)
		{
			$active = true;
		}
		else
		{
			pts_process_remove($process);
		}
	}
	return $active;
}
function pts_env_variables()
{
	// The PTS environmental variables passed during the testing process, etc
	static $env_variables = null;

	if(empty($env_variables))
	{
		$env_variables = array(
		"PTS_TYPE" => PTS_TYPE,
		"PTS_VERSION" => PTS_VERSION,
		"PTS_CODENAME" => PTS_CODENAME,
		"PTS_DIR" => PTS_DIR,
		"FONT_DIR" => FONT_DIR,
		"PHP_BIN" => PHP_BIN,
		"NUM_CPU_CORES" => cpu_core_count(),
		"NUM_CPU_JOBS" => cpu_job_count(),
		"SYS_MEMORY" => memory_mb_capacity(),
		"VIDEO_MEMORY" => graphics_memory_capacity(),
		"VIDEO_WIDTH" => current_screen_width(),
		"VIDEO_HEIGHT" => current_screen_height(),
		"VIDEO_MONITOR_COUNT" => graphics_monitor_count(),
		"VIDEO_MONITOR_LAYOUT" => graphics_monitor_layout(),
		"VIDEO_MONITOR_SIZES" => graphics_monitor_resolutions(),
		"OPERATING_SYSTEM" => pts_vendor_identifier(),
		"OS_VERSION" => os_version(),
		"OS_ARCH" => kernel_arch(),
		"OS_TYPE" => OPERATING_SYSTEM,
		"THIS_RUN_TIME" => THIS_RUN_TIME
		);
	}

	return $env_variables;
}
function pts_user_runtime_variables()
{
	static $runtime_variables = null;

	if(empty($runtime_variables))
	{
		$runtime_variables = array(
		"VIDEO_RESOLUTION" => current_screen_resolution(),
		"VIDEO_CARD" => graphics_processor_string(),
		"VIDEO_DRIVER" => opengl_version(),
		"OPERATING_SYSTEM" => operating_system_release(),
		"PROCESSOR" => processor_string(),
		"MOTHERBOARD" => main_system_hardware_string(),
		"CHIPSET" => motherboard_chipset_string(),
		"KERNEL_VERSION" => kernel_string(),
		"COMPILER" => compiler_version(),
		"HOSTNAME" => system_hostname()
		);
	}

	return $runtime_variables;
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
function pts_variables_export_string($vars = null)
{
	// Convert pts_env_variables() into shell export variable syntax
	$return_string = "";

	if($vars == null)
	{
		$vars = pts_env_variables();
	}
	else
	{
		$vars = array_merge(pts_env_variables(), $vars);
	}

	foreach($vars as $name => $var)
	{
		$return_string .= "export " . $name . "=" . $var . ";";
	}
	return $return_string . " ";
}
function pts_run_additional_vars($identifier)
{
	$extra_vars = array();

	$extra_vars["HOME"] = TEST_ENV_DIR . $identifier . "/";

	$ctp_extension_string = "";
	$extends = pts_test_extends_below($identifier);
	foreach($extends as $extended_test)
	{
		if(is_dir(TEST_ENV_DIR . $extended_test . "/"))
		{
			$ctp_extension_string .= TEST_ENV_DIR . $extended_test . ":";
		}
	}

	if(!empty($ctp_extension_string))
	{
		$extra_vars["PATH"] = $ctp_extension_string . "\$PATH";
	}

	if(count($extends) > 0)
	{
		$extra_vars["TEST_EXTENDS"] = TEST_ENV_DIR . $extends[0];
	}

	return $extra_vars;
}
function pts_exec($exec, $extra_vars = null)
{
	// Same as shell_exec() but with the PTS env variables added in
	return shell_exec(pts_variables_export_string($extra_vars) . $exec);
}
function pts_request_new_id()
{
	// Request a new ID for a counter
	global $PTS_GLOBAL_ID;
	$PTS_GLOBAL_ID++;

	return $PTS_GLOBAL_ID;
}
function pts_is_global_id($global_id)
{
	// Checks if a string is a valid Phoronix Global ID
	return pts_global_valid_id_string($global_id) && trim(@file_get_contents("http://www.phoronix-test-suite.com/global/profile-check.php?id=" . $global_id)) == "REMOTE_FILE";
}
function pts_global_download_xml($global_id)
{
	// Download a saved test result from Phoronix Global
	return @file_get_contents("http://www.phoronix-test-suite.com/global/pts-results-viewer.php?id=" . $global_id);
}
function pts_global_valid_id_string($global_id)
{
	// Basic checking to see if the string is possibly a Global ID
	$is_valid = true;

	if(count(explode("-", $global_id)) < 3) // Global IDs should have three (or more) dashes
	{
		$is_valid = false;
	}

	if(strlen($global_id) < 13) // Shortest Possible ID would be X-000-000-000
	{
		$is_valid = false;
	}

	return $is_valid;
}
function pts_trim_double($double, $accuracy = 2)
{
	// Set precision for a variable's points after the decimal spot
	$return = explode(".", $double);

	if(count($return) == 1)
	{
		$return[1] = "00";
	}
	
	if(count($return) == 2 && $accuracy > 0)
	{
		$strlen = strlen($return[1]);

		if($strlen > $accuracy)
		{
			$return[1] = substr($return[1], 0, $accuracy);
		}
		else if($strlen < $accuracy)
		{
			for($i = $strlen; $i < $accuracy; $i++)
			{
				$return[1] .= '0';
			}
		}

		$return = $return[0] . "." . $return[1];
	}
	else
	{
		$return = $return[0];
	}

	return $return;
}
function pts_bool_question($question, $default = true, $question_id = "UNKNOWN")
{
	// Prompt user for yes/no question
	if(defined("IS_BATCH_MODE") && IS_BATCH_MODE)
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
		{
			$answer = $auto_answer == "TRUE" || $auto_answer == "1";
		}
		else
		{
			$answer = $default;
		}
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
		{
			$answer = true;
		}
		else if($input == "n")
		{
			$answer = false;
		}
		else
		{
			$answer = $default;
		}
	}

	return $answer;
}
function pts_clean_information_string($str)
{
	// Clean a string containing hardware information of some common things to change/strip out
	static $remove_phrases = null;
	static $change_phrases = null;

	if(empty($remove_phrases) && is_file(STATIC_DIR . "info-strings-remove.txt"))
	{
		$word_file = trim(file_get_contents(STATIC_DIR . "info-strings-remove.txt"));
		$remove_phrases = array_map("trim", explode("\n", $word_file));
	}
	if(empty($change_phrases) && is_file(STATIC_DIR . "info-strings-replace.txt"))
	{
		$word_file = trim(file_get_contents(STATIC_DIR . "info-strings-replace.txt"));
		$phrases_r = array_map("trim", explode("\n", $word_file));
		$change_phrases = array();

		foreach($phrases_r as $phrase)
		{
			$phrase_r = explode("=", $phrase);
			$change_phrases[trim($phrase_r[1])] = trim($phrase_r[0]);
		}
	}

	$str = str_ireplace($remove_phrases, " ", $str);

	foreach($change_phrases as $new_phrase => $original_phrase)
	{
		$str = str_ireplace($original_phrase, $new_phrase, $str);
	}

	$str = trim(preg_replace("/\s+/", " ", $str));

	return $str;
}
function pts_string_header($heading, $char = '=')
{
	// Return a string header
	$header_size = 36;

	foreach(explode("\n", $heading) as $line)
	{
		if(($line_length = strlen($line)) > $header_size)
		{
			$header_size = $line_length;
		}
	}

	$terminal_width = trim(shell_exec("tput cols 2>&1"));

	if($header_size > $terminal_width && $terminal_width > 1)
	{
		$header_size = $terminal_width;
	}

	return "\n" . str_repeat($char, $header_size) . "\n" . $heading . "\n" . str_repeat($char, $header_size) . "\n\n";
}
function pts_exit($string = "")
{
	// Have PTS exit abruptly
	define("PTS_EXIT", 1);
	echo $string;
	exit(0);
}
function pts_version_comparable($old, $new)
{
	// Checks if there's a major version difference between two strings, if so returns false. If the same or only a minor difference, returns true.
	$old = explode(".", preg_replace("/[^.0-9]/", "", $old));
	$new = explode(".", preg_replace("/[^.0-9]/", "", $new));
	$compare = true;

	if(count($old) >= 2 && count($new) >= 2)
	{
		if($old[0] != $new[0] || $old[1] != $new[1])
		{
			$compare = false;
		}
	}

	return $compare;	
}
function pts_shutdown()
{
	// Shutdown process for PTS
	define("PTS_END_TIME", time());

	// Re-run the config file generation to save the last run version
	pts_user_config_init();

	if(IS_DEBUG_MODE && defined("PTS_DEBUG_FILE"))
	{
		if(!is_dir(PTS_USER_DIR . "debug-messages/"))
		{
			mkdir(PTS_USER_DIR . "debug-messages/");
		}

		if(file_put_contents(PTS_USER_DIR . "debug-messages/" . PTS_DEBUG_FILE, $GLOBALS["DEBUG_CONTENTS"]))
		{
			echo "\nDebug Message Saved To: " . PTS_USER_DIR . "debug-messages/" . PTS_DEBUG_FILE . "\n";
		}
	}

	if(IS_SCTP_MODE)
	{
		remote_sctp_test_files();
	}

	// Remove process
	pts_process_remove("phoronix-test-suite");
}
function pts_string_bool($string)
{
	// Used for evaluating if the user inputted a string that evaluates to true
	$string = strtolower($string);
	return $string == "true" || $string == "1" || $string == "on";
}
function pts_is_valid_download_url($string, $basename = null)
{
	// Checks for valid download URL
	$is_valid = true;

	if(strpos($string, "://") == false)
	{
		$is_valid = false;
	}

	if(!empty($basename) && $basename != basename($string))
	{
		$is_valid = false;
	}

	return $is_valid;
}
function pts_format_time_string($time, $format = "SECONDS", $standard_version = true)
{
	// Format an elapsed time string
	if($format == "MINUTES")
	{
		$time *= 60;
	}

	$formatted_time = array();

	if($time > 0)
	{
		$time_hours = floor($time / 3600);
		$time_minutes = floor(($time - ($time_hours * 3600)) / 60);
		$time_seconds = $time % 60;

		if($time_hours > 0)
		{
			if($standard_version)
			{
				$formatted_part = $time_hours . " Hour";

				if($time_hours > 1)
				{
					$formatted_part .= "s";
				}
			}
			else
			{
				$formatted_part = $time_hours . "h";
			}

			array_push($formatted_time, $formatted_part);
		}
		if($time_minutes > 0)
		{
			if($standard_version)
			{
				$formatted_part = $time_minutes . " Minute";

				if($time_minutes > 1)
				{
					$formatted_part .= "s";
				}
			}
			else
			{
				$formatted_part = $time_minutes . "m";
			}

			array_push($formatted_time, $formatted_part);
		}
		if($time_seconds > 0)
		{
			if($standard_version)
			{
				$formatted_part = $time_seconds . " Second";

				if($time_seconds > 1)
				{
					$formatted_part .= "s";
				}
			}
			else
			{
				$formatted_part = $time_seconds . "s";
			}

			array_push($formatted_time, $formatted_part);
		}
	}

	if($standard_version)
	{
		$time_string = implode(", ", $formatted_time);
	}
	else
	{
		$time_string = implode("", $formatted_time);
	}

	return $time_string;
}
function pts_evaluate_script_type($script)
{
	$script = explode("\n", trim($script));
	$script_eval = trim($script[0]);
	$script_type = false;

	if(strpos($script_eval, "<?php") !== false)
	{
		$script_type = "PHP";
	}
	else if(strpos($script_eval, "#!/bin/sh") !== false)
	{
		$script_type = "SH";
	}
	else if(strpos($script_eval, "<") !== false && strpos($script_eval, ">") !== false)
	{
		$script_type = "XML";
	}

	return $script_type;
}
function pts_set_environment_variable($name, $value)
{
	// Sets an environmental variable
	if(getenv($name) == false)
	{
		putenv($name . "=" . $value);
	}
}
function pts_proximity_match($search, $match_to)
{
	// Proximity search in $search string for * against $match_to
	$search = explode("*", $search);
	$is_match = true;

	if(count($search) == 1)
	{
		$is_match = false;
	}

	for($i = 0; $i < count($search) && $is_match && !empty($search[$i]); $i++)
	{
		if(($match_point = strpos($match_to, $search[$i])) !== false && ($i > 0 || $match_point == 0))
		{
			$match_to = substr($match_to, ($match_point + strlen($search[$i])));
		}
		else
		{
			$is_match = false;
		}
	}

	return $is_match;
}
function pts_estimated_time_string($time)
{
	// Estimated time that it will take for the test to complete
	$strlen_time = strlen($time);

	if(strlen($time_trim = str_replace("~", "", $time)) != $strlen_time)
	{
		$formatted_string = "Approximately " . $time_trim;
	}
	else if(strlen($time_trim = str_replace(array('l'), '', $time)) != $strlen_time)
	{
		$formatted_string = "Less Than " . $time_trim;
	}
	else if(strlen($time_trim = str_replace(array('g'), '', $time)) != $strlen_time)
	{
		$formatted_string = "Greater Than " . $time_trim;
	}
	else if(strlen($time_trim = str_replace("-", ", ", $time)) != $strlen_time)
	{
		$time_trim = explode(",", $time_trim);

		$time_trim = array_map("trim", $time_trim);

		if(count($time_trim) == 2)
		{
			$formatted_string = $time_trim[0] . " to " . $time_trim[1];
		}
	}
	else
	{
		$formatted_string = $time;
	}

	$formatted_string .= " Minutes";

	return $formatted_string;
}
function pts_debug_message($message)
{
	// Writes a PTS debug message
	if(IS_DEBUG_MODE)
	{
		if(strpos($message, "$") > 0)
		{
			foreach(pts_env_variables() as $key => $value)
			{
				$message = str_replace("$" . $key, $value, $message);
			}
		}

		echo "DEBUG: " . ($output = $message . "\n");

		if(defined("PTS_DEBUG_FILE"))
		{
			$GLOBALS["DEBUG_CONTENTS"] = $output;
		}
	}
}
function pts_user_message($message)
{
	if(!empty($message))
	{
		echo $message . "\n";

		if(!IS_BATCH_MODE)
		{
			echo "\nHit Any Key To Continue...\n";
			fgets(STDIN);
		}
	}
}
function pts_set_assignment_once($assignment, $value)
{
	if(!pts_is_assignment($assignment))
	{
		pts_set_assignment($assignment, $value);
	}
}
function pts_set_assignment($assignment, $value)
{
	$GLOBALS["PTS_VAR_CACHE"]["ASSIGNMENTS"][$assignment] = $value;
}
function pts_read_assignment($assignment)
{
	$value = false;

	if(isset($GLOBALS["PTS_VAR_CACHE"]["ASSIGNMENTS"][$assignment]))
	{
		$value = $GLOBALS["PTS_VAR_CACHE"]["ASSIGNMENTS"][$assignment];
	}

	return $value;
}
function pts_is_assignment($assignment)
{
	return isset($GLOBALS["PTS_VAR_CACHE"]["ASSIGNMENTS"][$assignment]);
}
function pts_clear_assignments()
{
	$GLOBALS["PTS_VAR_CACHE"]["ASSIGNMENTS"] = array();
}
function pts_clear_assignment($assignment)
{
	unset($GLOBALS["PTS_VAR_CACHE"]["ASSIGNMENTS"][$assignment]);
}

?>
