<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2012, Phoronix Media
	Copyright (C) 2009 - 2012, Michael Larabel

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

class pts_test_result_buffer_extra
{
	public static function append_to_test_result(&$buffer_items, $identifier, $value)
	{
		if(($key = array_search(strtolower($identifier), $buffer_items)) !== false)
		{
			$buffer = $buffer_items[$key];
			unset($buffer_items[$key]);

			$buffer_item = new pts_test_result_buffer_item($identifier, ($value + $buffer->get_result_value()));
		}
		else
		{
			$buffer_item = new pts_test_result_buffer_item($identifier, $value, null);
		}

		array_push($buffer_items, $buffer_item);
	}
	public static function clear_outlier_results(&$buffer_items, $add_to_other = true, $value_below = false)
	{
		$other_value = 0;

		foreach($buffer_items as $key => &$buffer_item)
		{
			if($value_below !== false && $buffer_item->get_result_value() < $value_below)
			{
				$other_value += $buffer_item->get_result_value();
				unset($buffer_items[$key]);
			}
		}

		if($add_to_other && $other_value > 0)
		{
			self::append_to_test_result($buffer_items, 'Other', $other_value);
		}
	}
	public static function add_composite_result(&$test_result_buffer, $force = false)
	{
		$is_multi_way = $force ? $force : pts_render::multi_way_identifier_check($test_result_buffer->get_identifiers());

		if($is_multi_way)
		{
			$group_values = array();

			foreach($test_result_buffer->buffer_items as &$buffer_item)
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

				$test_result_buffer->add_test_result($title, $value);
			}
		}
		else
		{
			$total_value = array_sum($test_result_buffer->get_values());
			$test_result_buffer->add_test_result('Composite', $total_value);
		}
	}
	public static function clear_iqr_outlier_results(&$test_result_buffer)
	{
		$is_multi_way = pts_render::multi_way_identifier_check($test_result_buffer->get_identifiers());

		if($is_multi_way)
		{
			$group_values = array();
			$group_keys = array();

			foreach($test_result_buffer->buffer_items as $key => &$buffer_item)
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
					$value = $test_result_buffer->buffer_items[$key]->get_result_value();

					if($value > $top_cut || $value < $bottom_cut)
					{
						unset($test_result_buffer->buffer_items[$key]);
					}
				}
			}
		}
		else
		{
			// From: http://www.mathwords.com/o/outlier.htm
			$values = $test_result_buffer->get_values();
			$fqr = pts_math::first_quartile($values);
			$tqr = pts_math::third_quartile($values);
			$iqr_cut = ($tqr - $fqr) * 1.5;
			$bottom_cut = $fqr - $iqr_cut;
			$top_cut = $tqr + $iqr_cut;

			foreach($test_result_buffer->buffer_items as $key => &$buffer_item)
			{
				$value = $buffer_item->get_result_value();

				if($value > $top_cut || $value < $bottom_cut)
				{
					unset($test_result_buffer->buffer_items[$key]);
				}
			}
		}
	}
}

?>
