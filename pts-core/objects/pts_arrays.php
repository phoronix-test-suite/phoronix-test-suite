<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2018, Phoronix Media
	Copyright (C) 2008 - 2018, Michael Larabel

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

class pts_arrays
{
	public static function first_element($array)
	{
		// Using this helper function will avoid a PHP E_STRICT warning if just using the code directly from the output of a function/object
		return reset($array);
	}
	public static function last_element($array)
	{
		// Using this helper function will avoid a PHP E_STRICT warning if just using the code directly from the output of a function/object
		return end($array);
	}
	public static function unique_push(&$array, $to_push)
	{
		// Only push to the array if it's a unique value
		return !in_array($to_push, $array) && array_push($array, $to_push);
	}
	public static function to_array($var)
	{
		return !is_array($var) ? array($var) : $var;
	}
	public static function json_encode_pretty_string($json)
	{
		return str_replace(array(',"', '{', '}'), array(",\n\t\"", " {\n\t", "\n}"), json_encode($json));
	}
	public static function json_decode($str)
	{
		return json_decode($str, true);
	}
	public static function duplicates_in_array($array)
	{
		$duplicates = array();
		foreach(array_count_values($array) as $item => $count)
		{
			if($count > 1)
			{
				$duplicates[] = $item;
			}
		}

		return $duplicates;
	}
}

?>
