<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
	pts-functions_client.php: General functions that are specific to the Phoronix Test Suite client

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

// Phoronix Test Suite - Functions
function pts_run_option_command($command, $pass_args = null, $preset_assignments = "")
{
	if(is_file(OPTIONS_DIR . $command . ".php") && !class_exists($command, false))
	{
		pts_load_run_option($command);
	}

	if(method_exists($command, "argument_checks"))
	{
		eval("\$argument_checks = " . $command . "::" . "argument_checks();");

		foreach($argument_checks as $argument_check)
		{
			$function_check = $argument_check->get_function_check();

			if(substr($function_check, 0, 1) == "!")
			{
				$function_check = substr($function_check, 1);
				$return_fails_on = true;
			}
			else
			{
				$return_fails_on = false;
			}

			if(!function_exists($function_check))
			{
				continue;
			}

			$return_value = call_user_func_array($function_check, array((isset($pass_args[$argument_check->get_argument_index()]) ? $pass_args[$argument_check->get_argument_index()] : null)));

			if($return_value == $return_fails_on)
			{
				echo pts_string_header($argument_check->get_error_string());
				return false;
			}
			else
			{
				if($argument_check->get_function_return_key() != null && !isset($pass_args[$argument_check->get_function_return_key()]))
				{
					$pass_args[$argument_check->get_function_return_key()] = $return_value;
				}
			}
		}
	}

	pts_assignment_manager::clear_all();
	$time = time();
	pts_set_assignment("START_TIME", $time);
	pts_set_assignment("THIS_OPTION_IDENTIFIER", $time); // For now THIS_OPTION_IDENTIFIER is also time
	pts_set_assignment("COMMAND", $command);

	if(is_array($preset_assignments))
	{
		foreach($preset_assignments as $key => $assign)
		{
			pts_set_assignment_once($key, $assign);
		}
	}

	pts_module_process("__pre_option_process", $command);

	if(is_file(OPTIONS_DIR . $command . ".php"))
	{
		eval($command . "::run(\$pass_args);");
	}
	else if(pts_module_valid_user_command($command))
	{
		$module_command = explode(".", $command);
		pts_module_run_user_command(array_shift($module_command), array_pop($module_command), $pass_args);
	}

	pts_module_process("__post_option_process", $command);
	pts_set_assignment_next("PREV_COMMAND", $command);
	pts_assignment_manager::clear_all();
}
function pts_run_option_next($command, $pass_args = null, $set_assignments = "")
{
	return pts_run_option_manager::add_run_option($command, $pass_args, $set_assignments);
}
function pts_request_new_id()
{
	// Request a new ID for a counter
	static $id = 1;
	$id++;

	return $id;
}
function pts_clean_information_string($str)
{
	// Clean a string containing hardware information of some common things to change/strip out
	static $remove_phrases = null;
	static $change_phrases = null;

	if($remove_phrases == null && is_file(STATIC_DIR . "info-strings-remove.txt"))
	{
		$word_file = trim(file_get_contents(STATIC_DIR . "info-strings-remove.txt"));
		$remove_phrases = pts_trim_explode("\n", $word_file);
	}
	if($change_phrases == null && is_file(STATIC_DIR . "info-strings-replace.txt"))
	{
		$word_file = trim(file_get_contents(STATIC_DIR . "info-strings-replace.txt"));
		$phrases_r = pts_trim_explode("\n", $word_file);
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
function pts_create_lock($lock_file, &$file_pointer)
{
	$file_pointer = fopen($lock_file, "w");
	return $file_pointer != false && flock($file_pointer, LOCK_EX | LOCK_NB);
}
function pts_release_lock(&$file_pointer, $lock_file)
{
	// Remove lock
	if(is_resource($file_pointer))
	{
		fclose($file_pointer);
	}

	pts_unlink($lock_file);
}
function pts_shutdown()
{
	// Shutdown process for PTS
	define("PTS_END_TIME", time());

	// Generate Phodevi Smart Cache
	if(pts_string_bool(pts_read_user_config(P_OPTION_PHODEVI_CACHE, "TRUE")))
	{
		phodevi::create_smart_cache(PTS_USER_DIR, PTS_VERSION);
	}
	else
	{
		phodevi::remove_smart_cache(PTS_USER_DIR);
	}
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
function pts_evaluate_math_expression($expr)
{
	// TODO: add security check to ensure that only math is being done in expr

	eval("\$result = $expr;");

	return $result;
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
function pts_check_option_for_function($option, $check_function)
{
	$in_option = false;

	if(is_file(OPTIONS_DIR . $option . ".php"))
	{
		if(!class_exists($option, false))
		{
			pts_load_run_option($option);
		}

		if(method_exists($option, $check_function))
		{
			$in_option = true;
		}
	}

	return $in_option;
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
function pts_get_display_mode_object()
{
	switch((($env_mode = getenv("PTS_DISPLAY_MODE")) != false ? $env_mode : pts_read_user_config(P_OPTION_DISPLAY_MODE, "DEFAULT") == "BATCH"))
	{
		case "BATCH":
		case "CONCISE":
			$display_mode = new pts_concise_display_mode();
			break;
		default:
			if(pts_is_assignment("IS_BATCH_MODE"))
			{
				$display_mode = new pts_concise_display_mode();
			}
			else
			{
				$display_mode = new pts_standard_display_mode();
			}
			break;
	}

	return $display_mode;
}
function pts_http_stream_context_create($http_parameters = null)
{
	if(!is_array($http_parameters))
	{
		$http_parameters = array();
	}

	$proxy_address = pts_read_user_config(P_OPTION_NET_PROXY_ADDRESS, null);
	$proxy_port = pts_read_user_config(P_OPTION_NET_PROXY_PORT, null);

	if($proxy_address != null && is_numeric($proxy_port))
	{
		$http_parameters["http"]["proxy"] = "tcp://" . $proxy_address . ":" . $proxy_port;
		$http_parameters["http"]["request_fulluri"] = true;
	}

	$http_parameters["http"]["timeout"] = 8;

	$stream_context = stream_context_create($http_parameters);

	return $stream_context;
}
function pts_http_get_contents($url, $override_proxy = false, $override_proxy_port = false)
{
	if(pts_string_bool(pts_read_user_config(P_OPTION_NET_NO_NETWORK, "FALSE")))
	{
		return false;
	}

	$stream_context = pts_http_stream_context_create();
	$contents = file_get_contents($url, 0, $stream_context);

	return $contents;
}
function pts_http_upload_via_post($url, $to_post_data)
{
	if(pts_string_bool(pts_read_user_config(P_OPTION_NET_NO_NETWORK, "FALSE")))
	{
		return false;
	}

	$upload_data = http_build_query($to_post_data);
	$http_parameters = array("http" => array("method" => "POST", "content" => $upload_data));
	$stream_context = pts_http_stream_context_create($http_parameters);
	$opened_url = @fopen($url, "rb", false, $stream_context);
	$response = @stream_get_contents($opened_url);

	return $response;
}
function pts_anonymous_usage_reporting()
{
	return pts_string_bool(pts_read_user_config(P_OPTION_USAGE_REPORTING, 0));
}

?>
