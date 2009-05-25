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
function pts_string_bool($string)
{
	// Used for evaluating if the user inputted a string that evaluates to true
	$string = strtolower($string);
	return $string == "true" || $string == "1" || $string == "on";
}
function pts_array_merge($array1, $array2)
{
	if(is_array($array1) && is_array($array2))
	{
		$array1 = array_merge($array1, $array2);
	}

	return $array1;
}
function pts_trim_spaces($string)
{
	$s_l = strlen($string);

	do
	{
		$string_l = $s_l;
		$string = str_replace("  ", " ", $string);
	}
	while($string_l != ($s_l = strlen($string)));

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
	return (!is_array($var) ? array($var) : $var);
}

?>
