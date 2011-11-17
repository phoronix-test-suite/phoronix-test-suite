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
	public $buffer_items;

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
	public function sort_buffer_values($asc = true)
	{
		usort($this->buffer_items, array('pts_test_result_buffer', 'buffer_value_comparison'));

		if($asc == false)
		{
			$this->buffer_items = array_reverse($this->buffer_items);
		}
	}
	public function buffer_value_comparison($a, $b)
	{
		$a = $a->get_result_value();
		$b = $b->get_result_value();

		return strcmp($a, $b);
	}
	public function add_buffer_item($buffer_item)
	{
		array_push($this->buffer_items, $buffer_item);
	}
	public function get_buffer_item($i)
	{
		return isset($this->buffer_items[$i]) ? $this->buffer_items[$i] : false;
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
	public function clear_iqr_outlier_results()
	{
		$is_multi_way = pts_render::multi_way_identifier_check($this->get_identifiers());

		if($is_multi_way)
		{
			$group_values = array();
			$group_keys = array();

			foreach($this->buffer_items as $key => &$buffer_item)
			{
				$identifier_r = pts_strings::trim_explode(': ', $buffer_item->get_result_identifier());

				if(!isset($group_values[$identifier_r[1]]))
				{
					$group_values[$identifier_r[1]] = array();
					$group_keys[$identifier_r[1]] = array();
				}

				array_push($group_values[$identifier_r[1]], $buffer_item->get_result_value());
				array_push($group_keys[$identifier_r[1]], $key);
			}

			foreach($group_values as $group_key => $values)
			{
				// From: http://www.mathwords.com/o/outlier.htm
				$fqr = pts_math::first_quartile($values);
				$tqr = pts_math::third_quartile($values);
				$iqr_cut = ($tqr - $fqr) * 1.5;
				$bottom_cut = $fqr - $iqr_cut;
				$top_cut = $tqr + $iqr_cut;

				foreach($group_keys[$group_key] as $key)
				{
					$value = $this->buffer_items[$key]->get_result_value();

					if($value > $top_cut || $value < $bottom_cut)
					{
						unset($this->buffer_items[$key]);
					}
				}
			}
		}
		else
		{
			// From: http://www.mathwords.com/o/outlier.htm
			$values = $this->get_values();
			$fqr = pts_math::first_quartile($values);
			$tqr = pts_math::third_quartile($values);
			$iqr_cut = ($tqr - $fqr) * 1.5;
			$bottom_cut = $fqr - $iqr_cut;
			$top_cut = $tqr + $iqr_cut;

			foreach($this->buffer_items as $key => &$buffer_item)
			{
				$value = $buffer_item->get_result_value();

				if($value > $top_cut || $value < $bottom_cut)
				{
					unset($this->buffer_items[$key]);
				}
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
