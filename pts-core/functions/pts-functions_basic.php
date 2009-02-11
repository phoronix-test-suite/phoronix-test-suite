<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
	pts-functions_basic.php: Basic functions for loading parts of the Phoronix Test Suite

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
function __autoload($to_load)
{
	pts_load_object($to_load);
}
function pts_load_function_set($title)
{
	$function_file = PTS_PATH . "pts-core/functions/pts-includes-" . $title . ".php";

	return is_file($function_file) && include_once($function_file);
}
function pts_load_object($to_load)
{
	if(class_exists($to_load))
	{
		return;
	}

	static $sub_objects = null;

	if($sub_objects == null && !is_array($sub_objects))
	{
		$sub_objects = array();
		$sub_object_files = glob(PTS_PATH . "pts-core/objects/*/*.php");

		foreach($sub_object_files as $file)
		{
			$object_name = basename($file, ".php");
			$sub_objects[$object_name] = $file;
		}
	}

	if(is_file(PTS_PATH . "pts-core/objects/" . $to_load . ".php"))
	{
		include(PTS_PATH . "pts-core/objects/" . $to_load . ".php");
	}
	else if(isset($sub_objects[$to_load]))
	{
		include($sub_objects[$to_load]);
		unset($sub_objects[$to_load]);
	}
}

?>
