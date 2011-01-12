<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2011, Phoronix Media
	Copyright (C) 2009 - 2011, Michael Larabel

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
	private $buffer_items;

	public function __construct($buffer_items = array())
	{
		$this->buffer_items = $buffer_items;
	}
	public function get_buffer_items()
	{
		return $this->buffer_items;
	}
	public function sort_buffer_items()
	{
		sort($this->buffer_items);
	}
	public function add_buffer_item($buffer_item)
	{
		array_push($this->buffer_items, $buffer_item);
	}
	public function add_test_result($identifier, $value, $raw_value = null)
	{
		array_push($this->buffer_items, new pts_test_result_buffer_item($identifier, $value, $raw_value));
	}
	public function append_to_test_result($identifier, $value)
	{
		if(($key = array_search(strtolower($identifier), $this->buffer_items)) !== false)
		{
			$buffer = $this->buffer_items[$key];
			unset($this->buffer_items[$key]);

			$buffer_item = new pts_test_result_buffer_item($identifier, ($value + $buffer->get_result_value()));
		}
		else
		{
			$buffer_item = new pts_test_result_buffer_item($identifier, $value, null);
		}

		array_push($this->buffer_items, $buffer_item);
	}
	public function clear_outlier_results($add_to_other = true, $value_below = false)
	{
		$other_value = 0;

		foreach($this->buffer_items as $key => &$buffer_item)
		{
			if($value_below !== false && $buffer_item->get_result_value() < $value_below)
			{
				$other_value += $buffer_item->get_result_value();
				unset($this->buffer_items[$key]);
			}
		}

		if($add_to_other && $other_value > 0)
		{
			$this->append_to_test_result('Other', $other_value);
		}
	}
	public function add_composite_result($force = false)
	{
		$is_multi_way = $force ? $force : pts_render::multi_way_identifier_check($this->get_identifiers());

		if($is_multi_way)
		{
			$group_values = array();

			foreach($this->buffer_items as &$buffer_item)
			{
				$identifier_r = pts_strings::trim_explode(': ', $buffer_item->get_result_identifier());

				if(!isset($group_values[$identifier_r[1]]))
				{
					$group_values[$identifier_r[1]] = 0;
				}

				$group_values[$identifier_r[1]] += $buffer_item->get_result_value();
			}

			foreach($group_values as $key => $value)
			{
				if(1 == 0)
				{
					$title = $key . ': Composite';
				}
				else
				{
					$title = 'Composite: ' . $key;
				}

				$this->add_test_result($title, $value);
			}
		}
		else
		{
			$total_value = array_sum($this->get_values());
			$this->add_test_result('Composite', $total_value);
		}
	}
	public function buffer_values_to_percent()
	{
		// TODO: is this function being used at all anymore?
		$is_multi_way = pts_render::multi_way_identifier_check($this->get_identifiers());

		if($is_multi_way)
		{
			$group_values = array();

			foreach($this->buffer_items as &$buffer_item)
			{
				$identifier_r = pts_strings::trim_explode(': ', $buffer_item->get_result_identifier());

				if(!isset($group_values[$identifier_r[1]]))
				{
					$group_values[$identifier_r[1]] = 0;
				}

				$group_values[$identifier_r[1]] += $buffer_item->get_result_value();
			}

			foreach($this->buffer_items as &$buffer_item)
			{
				$identifier_r = pts_strings::trim_explode(': ', $buffer_item->get_result_identifier());

				$percent = pts_math::set_precision(($buffer_item->get_result_value() / $group_values[$identifier_r[1]] * 100), 3);
				$buffer_item->reset_result_value($percent);
			}
		}
		else
		{
			$total_value = array_sum($this->get_values());

			foreach($this->buffer_items as &$buffer_item)
			{
				$percent = pts_math::set_precision(($buffer_item->get_result_value() / $total_value * 100), 3);
				$buffer_item->reset_result_value($percent);
			}
		}
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
	public function get_raw_values()
	{
		$raw_values = array();

		foreach($this->buffer_items as &$buffer_item)
		{
			array_push($raw_values, $buffer_item->get_result_raw_());
		}

		return $raw_values;
	}
	public function get_values_as_string()
	{
		return implode(':', $this->get_values());
	}
}

?>
