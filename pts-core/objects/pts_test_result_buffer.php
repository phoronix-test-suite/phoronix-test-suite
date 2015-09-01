<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2015, Phoronix Media
	Copyright (C) 2009 - 2015, Michael Larabel

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

class pts_test_result_buffer
{
	public $buffer_items;
	protected $buffer_contains;

	public function __construct($buffer_items = array())
	{
		$this->buffer_items = $buffer_items;

		if(!empty($buffer_items))
		{
			foreach($buffer_items as $buffer_item)
			{
				$this->buffer_contains[$buffer_item->get_result_identifier() . $buffer_item->get_result_value()] = 1;
			}
		}
	}
	public function get_buffer_items()
	{
		return $this->buffer_items;
	}
	public function sort_buffer_items()
	{
		sort($this->buffer_items);
	}
	public function sort_buffer_values($asc = true)
	{
		usort($this->buffer_items, array('pts_test_result_buffer', 'buffer_value_comparison'));

		if($asc == false)
		{
			$this->buffer_items = array_reverse($this->buffer_items);
		}
	}
	public static function buffer_value_comparison($a, $b)
	{
		return strcmp($a->get_result_value(), $b->get_result_value());
	}
	public function add_buffer_item($buffer_item)
	{
		array_push($this->buffer_items, $buffer_item);
		$this->buffer_contains[$buffer_item->get_result_identifier() . $buffer_item->get_result_value()] = 1;
	}
	public function buffer_contained(&$buffer_item)
	{
		return isset($this->buffer_contains[$buffer_item->get_result_identifier() . $buffer_item->get_result_value()]);
	}
	public function get_buffer_item($i)
	{
		return isset($this->buffer_items[$i]) ? $this->buffer_items[$i] : false;
	}
	public function add_test_result($identifier, $value, $raw_value = null, $json = null, $min_value = null, $max_value = null)
	{
		array_push($this->buffer_items, new pts_test_result_buffer_item($identifier, $value, $raw_value, $json, $min_value, $max_value));
	}
	public function clear_outlier_results($add_to_other = true, $value_below = false)
	{
		pts_test_result_buffer_extra::clear_outlier_results($this->buffer_items, $add_to_other, $value_below);
	}
	public function add_composite_result($force = false)
	{
		pts_test_result_buffer_extra::add_composite_result($this, $force);
	}
	public function auto_shorten_buffer_identifiers($identifier_shorten_index = false)
	{
		// If there's a lot to plot, try to auto-shorten the identifiers
		// e.g. if each identifier contains like 'GeForce 6800', 'GeForce GT 220', etc..
		// then remove the 'GeForce' part of the name.

		if($identifier_shorten_index == false)
		{
			$identifier_shorten_index = pts_render::evaluate_redundant_identifier_words($this->get_identifiers());
		}

		if(empty($identifier_shorten_index))
		{
			return false;
		}

		foreach($this->buffer_items as &$buffer_item)
		{
			$identifier = explode(' ', $buffer_item->get_result_identifier());
			foreach($identifier_shorten_index as $pos => $value)
			{
				if($identifier[$pos] == $value)
				{
					unset($identifier[$pos]);
				}
			}
			$buffer_item->reset_result_identifier(implode(' ', $identifier));
		}

		return true;
	}
	public function clear_iqr_outlier_results()
	{
		pts_test_result_buffer_extra::clear_iqr_outlier_results($this);
	}
	public function buffer_values_sort()
	{
		usort($this->buffer_items, array('pts_test_result_buffer_item', 'compare_value'));
	}
	public function buffer_values_reverse()
	{
		$this->buffer_items = array_reverse($this->buffer_items);
	}
	public function get_count()
	{
		return count($this->buffer_items);
	}
	public function get_identifiers()
	{
		$identifiers = array();

		foreach($this->buffer_items as &$buffer_item)
		{
			array_push($identifiers, $buffer_item->get_result_identifier());
		}

		return $identifiers;
	}
	public function get_values()
	{
		$values = array();

		foreach($this->buffer_items as &$buffer_item)
		{
			array_push($values, $buffer_item->get_result_value());
		}

		return $values;
	}
	public function get_min_values()
	{
		$values = array();

		foreach($this->buffer_items as &$buffer_item)
		{
			if(($min = $buffer_item->get_min_result_value()) != null)
			{
				array_push($values, $min);
			}
		}

		return $values;
	}
	public function get_max_values()
	{
		$values = array();

		foreach($this->buffer_items as &$buffer_item)
		{
			if(($max = $buffer_item->get_max_result_value()) != null)
			{
				array_push($values, $max);
			}
		}

		return $values;
	}
	public function get_raw_values()
	{
		$raw_values = array();

		foreach($this->buffer_items as &$buffer_item)
		{
			array_push($raw_values, $buffer_item->get_result_raw());
		}

		return $raw_values;
	}
	public function get_values_as_string()
	{
		return implode(':', $this->get_values());
	}
}

?>
