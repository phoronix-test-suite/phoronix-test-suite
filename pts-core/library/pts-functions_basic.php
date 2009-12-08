<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
	pts-functions_basic.php: Basic functions for the Phoronix Test Suite

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
			$return[1] .= str_repeat('0', ($accuracy - $strlen));
		}

		$return = $return[0] . "." . $return[1];
	}
	else
	{
		$return = $return[0];
	}

	return $return;
}
function pts_add_trailing_slash($path)
{
	return $path . (substr($path, -1) == "/" ? null : "/"); 
}
function pts_first_string_in_string($string, $delimited_by = " ")
{
	// This function returns the first word/phrase/string on the end of a string that's separated by a space or something else
	// Using this helper function will avoid a PHP E_STRICT warning if just using the code directly from the output of a function/object
	$string = explode($delimited_by, $string);
	return array_shift($string);
}
function pts_last_string_in_string($string, $delimited_by = " ")
{
	// This function returns the last word/phrase/string on the end of a string that's separated by a space or something else
	// Using this helper function will avoid a PHP E_STRICT warning if just using the code directly from the output of a function/object
	$string = explode($delimited_by, $string);
	return array_pop($string);
}
function pts_first_element_in_array($array)
{
	// Using this helper function will avoid a PHP E_STRICT warning if just using the code directly from the output of a function/object
	return reset($array);
}
function pts_last_element_in_array($array)
{
	// Using this helper function will avoid a PHP E_STRICT warning if just using the code directly from the output of a function/object
	return end($array);
}
function pts_string_bool($string)
{
	// Used for evaluating if the user inputted a string that evaluates to true
	return in_array(strtolower($string), array("true", "1", "on"));
}
function pts_array_merge($array1, $array2)
{
	return is_array($array1) && is_array($array2) ? array_merge($array1, $array2) : $array1;
}
function pts_mkdir($dir, $mode = 0777, $recursive = false)
{
	return !is_dir($dir) && mkdir($dir, $mode, $recursive);
}
function pts_glob($pattern, $flags = 0)
{
	$r = glob($pattern, $flags);
	return is_array($r) ? $r : array();
}
function pts_multi_glob()
{
	$multi = array();

	foreach(func_get_args() as $arg_glob)
	{
		foreach(pts_glob($arg_glob) as $arg_select)
		{
			array_push($multi, $arg_select);
		}
	}

	return $multi;
}
function pts_rmdir($dir)
{
	return is_dir($dir) && rmdir($dir);
}
function pts_unlink($file)
{
	return is_file($file) && unlink($file);
}
function pts_array_push(&$array, $to_push)
{
	return !in_array($to_push, $array) && array_push($array, $to_push);
}
function pts_empty($var)
{
	return trim($var) == null;
}
function pts_file_get_contents($filename, $flags = 0, $context = null)
{
	return trim(file_get_contents($filename, $flags, $context));
}
function pts_trim_spaces($string)
{
	do
	{
		$string_copy = $string;
		$string = str_replace("  ", " ", $string);
	}
	while($string_copy != $string);

	return trim($string);
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
function pts_array_with_key_to_2d($array)
{
	$array_2d = array();

	foreach($array as $key => $value)
	{
		array_push($array_2d, array($key, $value));
	}

	return $array_2d;
}
function pts_extract_identifier_from_path($path)
{
	return substr(($d = dirname($path)), strrpos($d, "/") + 1);
}
function pts_remove_chars($string, $keep_numeric = true, $keep_decimal = true, $keep_alpha = true, $keep_dash = false, $keep_underscore = false, $keep_colon = false)
{
	$string_r = str_split($string);
	$new_string = "";

	foreach($string_r as $char)
	{
		$i = ord($char);
		if(($keep_numeric && $i > 47 && $i < 58) || ($keep_alpha && $i > 64 && $i < 91) || 
		($keep_alpha && $i > 96 && $i < 123) || ($keep_decimal && $i == 46) || ($keep_dash && $i == 45) || 
		($keep_underscore && $i == 95) || ($keep_colon && $i == 58))
		{
			$new_string .= $char; 
		}
	}
	return $new_string;
}
function pts_to_array($var)
{
	return !is_array($var) ? array($var) : $var;
}
function pts_trim_explode($delimiter, $to_explode)
{
	return array_map("trim", explode($delimiter, $to_explode));
}
function pts_is_file(&$file_check)
{
	// $file_check could contain the XML markup already, so first check for < as the start of an open tag from say <?xml version
	return !isset($file_check[1024]) && substr($file_check, 0, 1) != "<" && is_file($file_check);
}
function pts_request_new_id()
{
	// Request a new ID for a counter
	static $id = 1;
	$id++;

	return $id;
}
function pts_is_file_or_url($path)
{
	// Basic check if $path is a file or possibly a download URL
	return strpos($path, "://") != false || is_file($path) ? $path : false;
}

?>
