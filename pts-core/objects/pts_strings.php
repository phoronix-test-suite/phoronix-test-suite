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
	public static function string_bool($string)
	{
		// Used for evaluating if the user inputted a string that evaluates to true
		return in_array(strtolower($string), array("true", "1"));
	}
	public static function trim_explode($delimiter, $to_explode)
	{
		return array_map("trim", explode($delimiter, $to_explode));
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
}

?>
