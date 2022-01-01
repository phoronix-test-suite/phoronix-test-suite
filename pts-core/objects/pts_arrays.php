<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2020, Phoronix Media
	Copyright (C) 2008 - 2020, Michael Larabel

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
	public static function unique_unshift(&$array, $to_push)
	{
		// Only push to the array if it's a unique value
		return !in_array($to_push, $array) && array_unshift($array, $to_push);
	}
	public static function to_array($var)
	{
		return !is_array($var) ? array($var) : $var;
	}
	public static function json_encode_pretty_string($json)
	{
		return str_replace(array(',"', '{', '}'), array(",\n\t\"", " {\n\t", "\n}"), json_encode($json));
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
	public static function array_to_cleansed_item_string($items)
	{
		$items_formatted = $items;
		$items = array();

		for($i = 0; $i < count($items_formatted); $i++)
		{
			if(!empty($items_formatted[$i]))
			{
				$times_found = 1;

				for($j = ($i + 1); $j < count($items_formatted); $j++)
				{
					if(isset($items_formatted[$j]) && $items_formatted[$i] == $items_formatted[$j])
					{
						$times_found++;
						$items_formatted[$j] = '';
					}
				}
				$item = ($times_found > 1 ? $times_found . ' x '  : null) . $items_formatted[$i];
				array_push($items, $item);
			}
		}
		$items = implode(' + ', $items);

		return $items;
	}
	public static function implode_list($r)
	{
		$l = null;
		switch(count($r))
		{
			case 0:
				break;
			case 1:
				$l = array_pop($r);
				break;
			case 2:
				$l = implode(' and ', $r);
				break;
			default:
				$l1 = array_pop($r);
				$l2 = array_pop($r);
				array_push($r, $l2 . ' and ' . $l1);
				$l = implode(', ', $r);
				break;
		}

		return $l;
	}
	public static function natural_krsort(&$array)
	{
		$keys = array_keys($array);
		natsort($keys);
		$sorted_array = array();

		foreach($keys as $k)
		{
			$sorted_array[$k] = $array[$k];
		}

		$array = array_reverse($sorted_array, true);
	}
	//
	// Popularity Tracking / Most Common Occurences
	//
	public static function popularity_tracker(&$popularity_array, $add_to_tracker)
	{
		if(!is_array($popularity_array))
		{
			$popularity_array = array();
		}
		if(empty($add_to_tracker))
		{
			return;
		}
		foreach($popularity_array as &$el)
		{
			if($el['value'] == $add_to_tracker)
			{
				$el['popularity']++;
				return;
			}
		}
		$popularity_array[] = array('value' => $add_to_tracker, 'popularity' => 1);
	}
	public static function get_most_popular_from_tracker(&$popularity_array, $ret = 1)
	{
		usort($popularity_array, array('pts_arrays', 'compare_popularity'));

		if($ret == 1)
		{
			return $popularity_array[0]['value'];
		}
		else
		{
			$pops = array();
			for($i = 0; $i < $ret; $i++)
			{
				$pops[] = $popularity_array[$i]['value'];
			}
			return $pops;
		}
	}
	public static function compare_popularity($a, $b)
	{
		$a = $a['popularity'];
		$b = $b['popularity'];

		if($a == $b)
		{
			return 0;
		}

		return $a > $b ? -1 : 1;
	}
}

?>
