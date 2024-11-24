<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2021, Phoronix Media
	Copyright (C) 2009 - 2021, Michael Larabel

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

	// TODO XXX: ultimately revisit the test_result_buffer handling in the future to see if it's safe these days for map buffer_items keys by identifier
	// likely some corner cases around renaming, sorting, etc still to be sorted out...
	protected $buffer_contains;
	protected $buffer_by_identifier;
	protected $added_multi_sample_result = false;
	protected $max_precision = 0;
	protected $min_bi;
	protected $min_value = 0;
	protected $max_bi;
	protected $max_value = 0;

	public function __construct($buffer_items = array())
	{
		$this->buffer_items = $buffer_items;

		if(!empty($buffer_items))
		{
			foreach($buffer_items as $i => &$buffer_item)
			{
				$this->buffer_contains[$buffer_item->get_result_identifier() . $buffer_item->get_result_value()] = 1;
				$this->buffer_by_identifier[$buffer_item->get_result_identifier()] = $i;
				$this->check_buffer_item_for_min_max($buffer_item);
			}
		}
	}
	public function add_buffer_item($buffer_item)
	{
		if(isset($this->buffer_by_identifier[$buffer_item->get_result_identifier()]) && $this->buffer_items[$this->buffer_by_identifier[$buffer_item->get_result_identifier()]]->get_result_value() == '')
		{
			// Overwrite the buffer item if there is a match but empty (incomplete) result
			$this->remove($buffer_item->get_result_identifier());
		}

		if(!$this->buffer_contained($buffer_item))
		{
			$this->buffer_items[] = $buffer_item;
			$this->buffer_by_identifier[$buffer_item->get_result_identifier()] = (count($this->buffer_items) - 1);
			$this->buffer_contains[$buffer_item->get_result_identifier() . $buffer_item->get_result_value()] = 1;
			$this->check_buffer_item_for_min_max($buffer_item);
		}
	}
	public function add_test_result($identifier, $value, $raw_value = null, $json = null, $min_value = null, $max_value = null)
	{
		$buffer_item = new pts_test_result_buffer_item($identifier, $value, $raw_value, $json, $min_value, $max_value);
		if(isset($this->buffer_by_identifier[$buffer_item->get_result_identifier()]) && $this->buffer_items[$this->buffer_by_identifier[$buffer_item->get_result_identifier()]]->get_result_value() == '')
		{
			// Overwrite the buffer item if there is a match but empty (incomplete) result
			$this->remove($buffer_item->get_result_identifier());
		}

		$this->check_buffer_item_for_min_max($buffer_item);
		$this->buffer_items[] = $buffer_item;

		if(is_array($value))
		{
			$value = implode(':', $value);
		}

		if($this->added_multi_sample_result == false && $raw_value && !is_array($raw_value))
		{
			$this->added_multi_sample_result = strpos($raw_value, ':') !== false;
		}

		$this->buffer_contains[$identifier . $value] = 1;
		$this->buffer_by_identifier[$identifier] = (count($this->buffer_items) - 1);
	}
	public function has_incomplete_result()
	{
		foreach($this->buffer_items as &$buffer_item)
		{
			if($buffer_item->get_result_value() == '')
			{
				return true;
			}
		}
		return false;
	}
	public function has_successful_run()
	{
		foreach($this->buffer_items as &$buffer_item)
		{
			if($buffer_item->get_result_value() != '')
			{
				return true;
			}
		}
		return false;
	}
	public function recalculate_buffer_items_min_max()
	{
		$this->min_value = 0;
		$this->max_value = 0;

		foreach($this->buffer_items as &$buffer_item)
		{
			$this->check_buffer_item_for_min_max($buffer_item);
		}
	}
	protected function check_buffer_item_for_min_max(&$buffer_item)
	{
		$value = $buffer_item->get_result_value();
		if(!is_numeric($value))
		{
			$values = !is_array($value) ? explode(',', $value) : $value;
			$min_value = min($values);
			$max_value = max($values);

			if(!is_numeric($min_value))
			{
				return;
			}
		}
		else
		{
			$min_value = $value;
			$max_value = $value;
		}
		if($min_value < $this->min_value || $this->min_value == 0)
		{
			$this->min_value = $min_value;
			$this->min_bi = $buffer_item;
		}
		if($max_value > $this->max_value)
		{
			$this->max_value = $max_value;
			$this->max_bi = $buffer_item;
		}

		// Also check precision
		$this->max_precision = max($this->max_precision, pts_math::get_precision($buffer_item->get_result_value()));
	}
	public function __clone()
	{
		foreach($this->buffer_items as $i => $v)
		{
			$this->buffer_items[$i] = clone $this->buffer_items[$i];
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
	public function find_buffer_item($identifier)
	{
		foreach($this->buffer_items as &$buf)
		{
			if($buf->get_result_identifier() == $identifier)
			{
				return $buf;
			}
		}

		return false;
	}
	public function get_result_from_identifier($identifier)
	{
		foreach($this->buffer_items as &$buf)
		{
			if($buf->get_result_identifier() == $identifier)
			{
				return $buf->get_result_value();
			}
		}

		return false;
	}
	public function buffer_contained(&$buffer_item)
	{
		return isset($this->buffer_contains[$buffer_item->get_result_identifier() . $buffer_item->get_result_value()]);
	}
	public function get_buffer_item($i)
	{
		return isset($this->buffer_items[$i]) ? $this->buffer_items[$i] : false;
	}
	public function detected_multi_sample_result()
	{
		return $this->added_multi_sample_result;
	}
	public function clear_outlier_results($value_below)
	{
		$cleared = false;
		foreach($this->buffer_items as $key => &$buffer_item)
		{
			if($buffer_item->get_result_value() < $value_below)
			{
				unset($this->buffer_items[$key]);
				$cleared = true;
			}
		}

		if($cleared)
		{
			$this->recalculate_buffer_items_min_max();
		}
	}
	public function rename($from, $to)
	{
		if($from == 'PREFIX')
		{
			foreach($this->buffer_items as &$buffer_item)
			{
				$buffer_item->reset_result_identifier($to . ': ' . $buffer_item->get_result_identifier());
			}
		}
		else if($from == null && count($this->buffer_items) == 1)
		{
			foreach($this->buffer_items as &$buffer_item)
			{
				$buffer_item->reset_result_identifier($to);
			}
			return true;
		}
		else
		{
			foreach($this->buffer_items as &$buffer_item)
			{
				if($buffer_item->get_result_identifier() == $from)
				{
					$buffer_item->reset_result_identifier($to);
					return true;
				}
			}
		}
		return false;
	}
	public function reorder($new_order)
	{
		foreach($new_order as $identifier)
		{
			foreach($this->buffer_items as $i => &$buffer_item)
			{
				if($buffer_item->get_result_identifier() == $identifier)
				{
					$c = $buffer_item;
					unset($this->buffer_items[$i]);
					$this->buffer_items[] = $c;
					break;
				}
			}
		}
	}
	public function remove($remove)
	{
		$remove = pts_arrays::to_array($remove);
		$removed = false;
		foreach($this->buffer_items as $i => &$buffer_item)
		{
			if(in_array($buffer_item->get_result_identifier(), $remove))
			{
				unset($this->buffer_by_identifier[$this->buffer_items[$i]->get_result_identifier()]);
				unset($this->buffer_contains[$this->buffer_items[$i]->get_result_identifier() . $this->buffer_items[$i]->get_result_value()]);
				unset($this->buffer_items[$i]);
				$removed = true;
			}
		}

		if($removed)
		{
			$this->recalculate_buffer_items_min_max();
		}

		return $removed;
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
		$is_multi_way = pts_render::multi_way_identifier_check($this->get_identifiers());
		$cleared = false;

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

				$group_values[$identifier_r[1]][] = $buffer_item->get_result_value();
				$group_keys[$identifier_r[1]][] = $key;
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
						$cleared = true;
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
					$cleared = true;
				}
			}
		}

		if($cleared)
		{
			$this->recalculate_buffer_items_min_max();
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
			$identifiers[] = $buffer_item->get_result_identifier();
		}

		return $identifiers;
	}
	public function get_total_value_sum()
	{
		$sum = 0;

		foreach($this->buffer_items as &$buffer_item)
		{
			$v = $buffer_item->get_result_value();
			if(is_numeric($v))
			{
				$sum += $v;
			}
		}

		return $sum;
	}
	public function get_longest_identifier()
	{
		$identifier = null;
		$length = 0;

		foreach($this->buffer_items as &$buffer_item)
		{
			if(($l = strlen($buffer_item->get_result_identifier())) > $length)
			{
				$length = $l;
				$identifier = $buffer_item->get_result_identifier();
			}
		}

		return $identifier;
	}
	public function get_max_precision()
	{
		return $this->max_precision;
	}
	public function reset_precision($precision)
	{
		foreach($this->buffer_items as &$buffer_item)
		{
			if(!is_numeric($buffer_item->get_result_value()))
			{
				continue;
			}

			$p = pts_math::set_precision($buffer_item->get_result_value(), $precision);
			$buffer_item->reset_result_value($p, false);
		}
	}
	public function reduce_precision()
	{
		$min_value = $this->get_min_value();
		$max_value = $this->get_max_value();
		if($min_value > 20 && ($max_value / $min_value) > 1.25)
		{
			$this->reset_precision(0);
		}
		else
		{
			$max_precision = $this->get_max_precision();
			if($max_precision >= 1)
			{
				if($min_value > 10 && $max_precision > 1)
				{
					$max_precision = 2;
				}
				/*else if($max_precision > 3 && ($max_value / $min_value) > 1.3)
				{
					$max_precision = 3;
				}*/
				else if($max_precision > 3)
				{
					$max_precision = 3;
				}

				$this->reset_precision(($max_precision - 1));
			}
		}
	}
	public function get_min_value($return_identifier = false)
	{
		if($this->min_bi == null)
		{
			return null;
		}
		else if($return_identifier === 2)
		{
			return $this->min_bi;
		}
		else if($return_identifier)
		{
			return $this->min_bi->get_result_identifier();
		}
		else
		{
			return pts_math::set_precision($this->min_value, $this->get_max_precision());
		}
	}
	public function get_max_value($return_identifier = false)
	{
		if($this->max_bi == null)
		{
			return null;
		}
		else if($return_identifier === 2)
		{
			return $this->max_bi;
		}
		else if($return_identifier)
		{
			return $this->max_bi->get_result_identifier();
		}
		else
		{
			return pts_math::set_precision($this->max_value, $this->get_max_precision());
		}
	}
	public function has_run_with_multiple_samples()
	{
		foreach($this->buffer_items as &$buffer_item)
		{
			if($buffer_item->get_sample_count() > 1)
			{
				return true;
			}
		}

		return false;
	}
	public function get_value_from_identifier($result_identifier)
	{
		foreach($this->buffer_items as &$buffer_item)
		{
			if($buffer_item->get_result_identifier() == $result_identifier)
			{
				return $buffer_item->get_result_value();
			}
		}

		return false;
	}
	public function get_identifier_value_map()
	{
		$m = array();

		foreach($this->buffer_items as &$buffer_item)
		{
			$m[$buffer_item->get_result_identifier()] = $buffer_item->get_result_value();
		}

		return $m;
	}
	public function get_map_by_identifier()
	{
		$m = array();

		foreach($this->buffer_items as &$buffer_item)
		{
			$m[$buffer_item->get_result_identifier()] = &$buffer_item;
		}

		return $m;
	}
	public function buffer_values_to_percent()
	{
		$is_multi_way = pts_render::multi_way_identifier_check($this->get_identifiers());
		if($is_multi_way)
		{
			$group_values = array();
			foreach($this->buffer_items as &$buffer_item)
			{
				if(!is_numeric($buffer_item->get_result_value()))
				{
					continue;
				}
				$identifier_r = pts_strings::trim_explode(': ', $buffer_item->get_result_identifier());
				if(!isset($group_values[$identifier_r[1]]))
				{
					$group_values[$identifier_r[1]] = 0;
				}
				$group_values[$identifier_r[1]] += $buffer_item->get_result_value();
			}
			foreach($this->buffer_items as &$buffer_item)
			{
				if(!is_numeric($buffer_item->get_result_value()))
				{
					continue;
				}
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
	public function adjust_precision($precision = 'auto')
	{
		if($precision == 'auto')
		{
			// For very large results, little point in keeping the precision...
			$min_value = $this->get_min_value();
			$precision = -1;
			if($min_value >= 100)
			{
				$precision = 0;
			}
			if($min_value >= 10)
			{
				$precision = 2;
			}

			$current_precision = $this->get_max_precision();
			$precision = $precision == -1 ? $current_precision : min($precision, $current_precision);
		}

		if(is_numeric($precision))
		{
			foreach($this->buffer_items as &$buffer_item)
			{
				if(is_numeric(($val = $buffer_item->get_result_value())))
				{
					$buffer_item->reset_result_value(pts_math::set_precision($val, $precision), false);
				}
			}

		}
	}
	public function get_values()
	{
		$values = array();

		foreach($this->buffer_items as &$buffer_item)
		{
			$values[] = $buffer_item->get_result_value();
		}

		return $values;
	}
	public function get_median()
	{
		return pts_math::median($this->get_values());
	}
	public function get_values_as_string()
	{
		return implode(':', $this->get_values());
	}
}

?>
