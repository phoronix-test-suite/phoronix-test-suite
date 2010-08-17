<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010, Phoronix Media
	Copyright (C) 2010, Michael Larabel

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

class pts_strings
{
	public static function is_url($string)
	{
		$components = parse_url($string);

		return $components != false && isset($components["scheme"]) && isset($components["host"]);
	}
	public static function string_bool($string)
	{
		// Used for evaluating if the user inputted a string that evaluates to true
		return in_array(strtolower($string), array("true", "1"));
	}
	public static function add_trailing_slash($path)
	{
		return $path . (substr($path, -1) == '/' ? null : '/'); 
	}
	public static function trim_explode($delimiter, $to_explode)
	{
		return empty($to_explode) ? array() : array_map("trim", explode($delimiter, $to_explode));
	}
	public static function first_in_string($string, $delimited_by = ' ')
	{
		// This function returns the first word/phrase/string on the end of a string that's separated by a space or something else
		// Using this helper function will avoid a PHP E_STRICT warning if just using the code directly from the output of a function/object
		$string = explode($delimited_by, $string);
		return array_shift($string);
	}
	public static function last_in_string($string, $delimited_by = " ")
	{
		// This function returns the last word/phrase/string on the end of a string that's separated by a space or something else
		// Using this helper function will avoid a PHP E_STRICT warning if just using the code directly from the output of a function/object
		$string = explode($delimited_by, $string);
		return array_pop($string);
	}
	public static function char_is_of_type($char, $attributes)
	{
		$i = ord($char);

		if(($attributes & TYPE_CHAR_LETTER) && (($i > 64 && $i < 91) || ($i > 96 && $i < 123)))
		{
			$is_of_type = true;
		}
		else if(($attributes & TYPE_CHAR_NUMERIC) && $i > 47 && $i < 58)
		{
			$is_of_type = true;
		}
		else if(($attributes & TYPE_CHAR_DECIMAL) && $i == 46)
		{
			$is_of_type = true;
		}
		else if(($attributes & TYPE_CHAR_DASH) && $i == 45)
		{
			$is_of_type = true;
		}
		else if(($attributes & TYPE_CHAR_UNDERSCORE) && $i == 95)
		{
			$is_of_type = true;
		}
		else if(($attributes & TYPE_CHAR_COLON) && $i == 58)
		{
			$is_of_type = true;
		}
		else if(($attributes & TYPE_CHAR_SPACE) && $i == 32)
		{
			$is_of_type = true;
		}
		else if(($attributes & TYPE_CHAR_COMMA) && $i == 44)
		{
			$is_of_type = true;
		}
		else
		{
			$is_of_type = false;
		}

		return $is_of_type;
	}
	public static function trim_spaces($string)
	{
		do
		{
			$string_copy = $string;
			$string = str_replace("  ", " ", $string);
		}
		while($string_copy != $string);

		return trim($string);
	}
	public static function parse_week_string($week_string, $delimiter = ' ')
	{
		$return_array = array();

		foreach(array('S', 'M', 'T', 'W', 'TH', 'F', 'S') as $day_int => $day_char)
		{
			if($week_string[$day_int] == 1)
			{
				array_push($return_array, $day_char);
			}
		}

		return implode($delimiter, $return_array);
	}
	public static function remove_from_string($string, $attributes)
	{
		$string_r = str_split($string);
		$new_string = null;

		foreach($string_r as $char)
		{
			if(pts_strings::char_is_of_type($char, $attributes) == false)
			{
				$new_string .= $char;
			}
		}

		return $new_string;
	}
	public static function keep_in_string($string, $attributes)
	{
		$string_r = str_split($string);
		$new_string = null;

		foreach($string_r as $char)
		{
			if(pts_strings::char_is_of_type($char, $attributes) == true)
			{
				$new_string .= $char;
			}
		}

		return $new_string;
	}
	public static function proximity_match($search, $match_to)
	{
		// Proximity search in $search string for * against $match_to
		$search = explode('*', $search);
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
	public static function version_strings_comparable($old, $new)
	{
		// Checks if there's a major version difference between two strings, if so returns false.
		// If the same or only a minor difference, returns true.

		$old = explode('.', pts_strings::keep_in_string($old, TYPE_CHAR_NUMERIC | TYPE_CHAR_DECIMAL));
		$new = explode('.', pts_strings::keep_in_string($new, TYPE_CHAR_NUMERIC | TYPE_CHAR_DECIMAL));
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
	public static function result_format_to_string($result_format)
	{
		switch($result_format)
		{
			case "MAX":
				$return_str = "Maximum";
				break;
			case "MIN":
				$return_str = "Minimum";
				break;
			case "NULL":
				$return_str = null;
				break;
			case "AVG":
			default:
				$return_str = "Average";
				break;
		}

		return $return_str;
	}
	public static function swap_variables($user_str, $replace_call)
	{
		if(is_array($replace_call))
		{
			if(count($replace_call) != 2 || method_exists($replace_call[0], $replace_call[1]) == false)
			{
				echo "\nVar Swap With Method Failed.\n";
				return $user_str;
			}
		}
		else if(!function_exists($replace_call))
		{
			echo "\nVar Swap With Function Failed.\n";
			return $user_str;
		}

		$offset = 0;
		$replace_call_return = false;

		while($offset < strlen($user_str) && ($s = strpos($user_str, '$', $offset)) !== false)
		{
			$s++;
			$var_name = substr($user_str, $s, (($e = strpos($user_str, ' ', $s)) == false ? strlen($user_str) : $e) - $s);

			if($replace_call_return === false)
			{
				$replace_call_return = call_user_func($replace_call);
			}

			$var_replacement = isset($replace_call_return[$var_name]) ? $replace_call_return[$var_name] : null;

			if($var_replacement != null)
			{
				$user_str = str_replace("$" . $var_name, $var_replacement, $user_str);
			}
			else
			{
				// echo "\nVariable Swap For $var_name Failed.\n";
			}

			$offset = $s + strlen($var_replacement);
		}

		return $user_str;
	}
	public static function format_time($time, $input_format = "SECONDS", $standard_version = true, $round_to = 0)
	{
		switch($input_format)
		{
			case "MINUTES":
				$time_in_seconds = $time * 60;
				break;
			case "SECONDS":
			default:
				$time_in_seconds = $time;
				break;
		}

		if($round_to > 0)
		{
			$time_in_seconds += $round_to - ($time_in_seconds % $round_to);
		}

		$formatted_time = array();

		if($time_in_seconds > 0)
		{
			$time_r = array();
			$time_r[0] = array(floor($time_in_seconds / 3600), "Hour");
			$time_r[1] = array(floor(($time_in_seconds % 3600) / 60), "Minute");
			$time_r[2] = array($time_in_seconds % 60, "Second");

			foreach($time_r as $time_segment)
			{
				if($time_segment[0] > 0)
				{
					$formatted_part = $time_segment[0];

					if($standard_version)
					{
						$formatted_part .= " " . $time_segment[1];

						if($time_segment[0] > 1)
						{
							$formatted_part .= "s";
						}
					}
					else
					{
						$formatted_part .= strtolower(substr($time_segment[1], 0, 1));
					}

					array_push($formatted_time, $formatted_part);
				}
			}
		}

		return implode(($standard_version ? ", " : null), $formatted_time);
	}
}

?>
