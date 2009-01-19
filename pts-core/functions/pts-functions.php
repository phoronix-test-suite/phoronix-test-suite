<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
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
require_once("pts-core/functions/pts-interfaces.php");
require_once("pts-core/functions/pts-functions_io.php");
require_once("pts-core/functions/pts-functions_shell.php");
require_once("pts-core/functions/pts-functions_config.php");
require_once("pts-core/functions/pts-functions_system.php");
require_once("pts-core/functions/pts-functions_global.php");
require_once("pts-core/functions/pts-functions_tests.php");
require_once("pts-core/functions/pts-functions_types.php");
require_once("pts-core/functions/pts-functions_vars.php");
require_once("pts-core/functions/pts-functions_modules.php");
require_once("pts-core/functions/pts-functions_assignments.php");

// Phoronix Test Suite - Functions
function pts_run_option_command($command, $pass_args = null, $preset_assignments = "")
{
	pts_clear_assignments();
	pts_set_assignment(array("START_TIME", "THIS_OPTION_IDENTIFIER"), time()); // For now THIS_OPTION_IDENTIFIER is also time
	pts_set_assignment("COMMAND", $command);

	if(is_array($preset_assignments))
	{
		foreach($preset_assignments as $key => $assign)
		{
			pts_set_assignment_once($key, $assign);
		}
	}

	if(is_file("pts-core/options/" . $command . ".php"))
	{
		if(!class_exists($command, false))
		{
			include_once("pts-core/options/" . $command . ".php");
		}

		pts_module_process("__pre_option_process", $command);
		eval($command . "::run(\$pass_args);");
		pts_module_process("__post_option_process", $command);
	}
	pts_clear_assignments();
}
function pts_run_option_next($command = false, $pass_args = null, $set_assignments = "")
{
	static $options;
	$return = null;

	if(!is_array($options))
	{
		$options = array();
	}

	if($command == false)
	{
		if(count($options) == 0)
		{
			$return = false;
		}
		else
		{
			$return = array_shift($options);
		}
	}
	else
	{
		array_push($options, new pts_run_option($command, $pass_args, $set_assignments));
	}

	return $return;
}
function pts_request_new_id()
{
	// Request a new ID for a counter
	static $id = 1;
	$id++;

	return $id;
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
function pts_load_function_set($title)
{
	$function_file = "pts-core/functions/pts-functions-" . $title . ".php";

	return is_file($function_file) && include_once($function_file);
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

	return pts_trim_spaces($str);
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
	// Checks if there's a major version difference between two strings, if so returns false.
	// If the same or only a minor difference, returns true.

	$old = explode(".", pts_remove_chars($old, true, true, false));
	$new = explode(".", pts_remove_chars($new, true, true, false));
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

		if(file_put_contents(PTS_USER_DIR . "debug-messages/" . PTS_DEBUG_FILE, pts_debug_message()))
		{
			echo "\nDebug Message Saved To: " . PTS_USER_DIR . "debug-messages/" . PTS_DEBUG_FILE . "\n";
		}
	}

	if(IS_SCTP_MODE)
	{
		pts_remove_sctp_test_files();
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
function pts_remove_chars($string, $keep_numeric = true, $keep_decimal = true, $keep_alpha = true)
{
	$string_r = str_split($string);
	$new_string = "";

	foreach($string_r as $char)
	{
		$i = ord($char);
		if(($keep_numeric && $i > 47 && $i < 58) || ($keep_alpha && $i > 64 && $i < 91) || 
		($keep_alpha && $i > 96 && $i < 123) || ($keep_decimal && $i == 46))
		{
			$new_string .= $char; 
		}
	}
	return $new_string;
}
function pts_trim_spaces($string)
{
	while(strpos($string, "  ") !== false)
	{
		$string = str_replace("  ", " ", $string);
	}

	return trim($string);
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
function pts_text_save_buffer($to_add)
{
	static $buffer = null;
	$return = null;

	if($to_add == false)
	{
		$return = $to_add;
	}
	else if(!empty($to_add))
	{
		$buffer .= $to_add;
	}

	return $return;
}
function pts_debug_message($message = null)
{
	static $debug_messages = "";

	if(defined("PTS_END_TIME") && $message == null)
	{
		return $debug_messages;
	}
	// Writes a PTS debug message
	if(IS_DEBUG_MODE && !empty($message))
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
			$debug_messages .= $output;
		}
	}
}
function pts_user_message($message)
{
	if(!empty($message))
	{
		echo $message . "\n";

		if(pts_read_assignment("IS_BATCH_MODE") == false)
		{
			echo "\nHit Any Key To Continue...\n";
			fgets(STDIN);
		}
	}
}
function pts_load_pdf_generator()
{
	if(is_file("/usr/share/php/fpdf/fpdf.php"))
	{
		$pdf_loader = "/usr/share/php/fpdf/fpdf.php";
	}
	else
	{
		$pdf_loader = false;
	}

	return $pdf_loader && include_once($pdf_loader);
}
function pts_to_array($var)
{
	if(!is_array($var))
	{
		$var = array($var);
	}

	return $var;
}

?>
