<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2021, Phoronix Media
	Copyright (C) 2008 - 2021, Michael Larabel

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

class pts_graph_box_plot extends pts_graph_horizontal_bars
{
	protected $is_already_percentile = false;
	public function data_is_percentiles()
	{
		$this->is_already_percentile = true;
	}
	protected function render_graph_bars()
	{
		$bar_count = count($this->results);
		$separator_height = ($a = (6 - (floor($bar_count / 2) * 2))) > 0 ? $a : 0;
		$bar_height = floor(($this->i['identifier_height'] - ($this->i['is_multi_way_comparison'] ? 4 : 0) - $separator_height - ($bar_count * $separator_height)) / $bar_count);
		$this->i['graph_max_value'] = $this->i['graph_max_value'] != 0 ? $this->i['graph_max_value'] : 1;
		$work_area_width = $this->i['graph_left_end'] - $this->i['left_start'];

		$group_offsets = array();
		$id_offsets = array();
		$g_lines = $this->svg_dom->make_g(array('stroke' => self::$c['color']['body_light'], 'stroke-width' => 2));
		$g_bars = $this->svg_dom->make_g(array('stroke' => self::$c['color']['body_light'], 'stroke-width' => 1));
		$g_overtop = $this->svg_dom->make_g(array('stroke' => self::$c['color']['headers'], 'stroke-width' => 1));
		$g_text = $this->svg_dom->make_g(array('font-size' => ($this->i['identifier_size'] - 2), 'fill' => self::$c['color']['text'], 'text-anchor' => 'end'));
		$g_circles = $this->svg_dom->make_g(array('fill' => self::$c['color']['headers']));

		foreach($this->results as $identifier => &$group)
		{
			$paint_color = $this->get_paint_color($identifier);
			foreach($group as &$buffer_item)
			{
				$values = $buffer_item->get_result_value();
				$values = explode(',', $values);

				if($this->is_already_percentile == false)
				{
					if(empty($values) || count($values) < 2)
					{
						$values = $buffer_item->get_result_raw();
						$values = explode(':', $values);
					}

					if(empty($values) || count($values) < 2)
					{
						continue;
					}

					if(isset($values[10]))
					{
						// Ignore any zeros at the start
						if($values[0] == 0 && $values[5] != 0)
						{
							$j = 0;
							while($values[$j] == 0)
							{
								unset($values[$j]);
								$j++;
							}
						}
						// Ignore any zeros at the end
						if($values[(count($values) - 1)] == 0 && $values[(count($values) - 5)] != 0)
						{
							$j = count($values) - 1;
							while($values[$j] == 0)
							{
								unset($values[$j]);
								$j--;
							}
						}
					}
				}

				$i_o = $this->calc_offset($group_offsets, $identifier);
				$i = $this->calc_offset($id_offsets, $buffer_item->get_result_identifier());
				$px_bound_top = $this->i['top_start'] + ($this->i['is_multi_way_comparison'] ? 5 : 0) + ($this->i['identifier_height'] * $i) + ($bar_height * $i_o) + ($separator_height * ($i_o + 1));
				$px_bound_bottom = $px_bound_top + $bar_height;
				$middle_of_bar = $px_bound_top + ($bar_height / 2);

				$avg_text = 'Avg';
				if($this->is_already_percentile)
				{
					$avg_text = 'Median';
					$avg_value = $values[50];
					$whisker_bottom = $values[2];
					$whisker_top = $values[98];
					$median =  $values[50];
					$p_25 =  $values[25];
					$p_75 =  $values[75];

					$unique_values = array();
					$min_value = min($values);
					$max_value = max($values);
				}
				else
				{
					// sort values now as optimization rather than in find_percentile
					sort($values, SORT_NUMERIC);

					$avg_value = round(pts_math::arithmetic_mean($values), 2);
					$whisker_bottom = pts_math::find_percentile($values, 0.02, true);
					$whisker_top = pts_math::find_percentile($values, 0.98, true);
					$median = pts_math::find_percentile($values, 0.5, true);
					$p_25 = pts_math::find_percentile($values, 0.25, true);
					$p_75 = pts_math::find_percentile($values, 0.75, true);

					$unique_values = array_unique($values);
					$min_value = min($unique_values);
					$max_value = max($unique_values);
				}

				if(!is_numeric($min_value) || !is_numeric($max_value))
				{
					continue;
				}
				$min_value = round($min_value, 2);
				$max_value = round($max_value, 2);

				$value_end_left = $this->i['left_start'] + max(1, round(($whisker_bottom / $this->i['graph_max_value']) * $work_area_width));
				$value_end_right = $this->i['left_start'] + round(($whisker_top / $this->i['graph_max_value']) * $work_area_width);
				// if identifier is 0, not a multi-way comparison or anything special
				if($identifier == 0 && !$this->i['is_multi_way_comparison'])
				{
					// See if the result identifier matches something to be color-coded better
					$box_color = self::identifier_to_branded_color($buffer_item->get_result_identifier(), $paint_color);
				}
				else
				{
					$box_color = $paint_color;
				}

				$box_color = $this->adjust_color($buffer_item->get_result_identifier(), $box_color);

				$this->svg_dom->add_element('line', array('x1' => $value_end_left, 'y1' => $middle_of_bar, 'x2' => $value_end_right, 'y2' => $middle_of_bar), $g_lines);
				$this->svg_dom->add_element('line', array('x1' => $value_end_left, 'y1' => $px_bound_top, 'x2' => $value_end_left, 'y2' => $px_bound_bottom), $g_lines);
				$this->svg_dom->add_element('line', array('x1' => $value_end_right, 'y1' => $px_bound_top, 'x2' => $value_end_right, 'y2' => $px_bound_bottom), $g_lines);

				$box_left = $this->i['left_start'] + round(($p_25 / $this->i['graph_max_value']) * $work_area_width);
				$box_middle = $this->i['left_start'] + round(($median / $this->i['graph_max_value']) * $work_area_width);
				$box_right = $this->i['left_start'] + round(($p_75 / $this->i['graph_max_value']) * $work_area_width);

				$this->svg_dom->add_element('rect', array('x' => $box_left, 'y' => $px_bound_top, 'width' => ($box_right - $box_left), 'height' => $bar_height, 'fill' => $box_color), $g_bars);
				$this->svg_dom->add_element('line', array('x1' => $box_middle, 'y1' => $px_bound_top, 'x2' => $box_middle, 'y2' => $px_bound_bottom), $g_overtop);

				$this->svg_dom->add_text_element('Min: ' . $min_value . ' / ' . $avg_text . ': ' . $avg_value . ' / Max: ' . $max_value, array('x' => ($this->i['left_start'] - 5), 'y' => ceil($px_bound_top + ($bar_height * 0.8) + 6)), $g_text);

				foreach($unique_values as &$val)
				{
					if(($val < $whisker_bottom || $val > $whisker_top) && $val > 0.1)
					{
						$this->svg_dom->add_element('circle', array('cx' => $this->i['left_start'] + round(($val / $this->i['graph_max_value']) * $work_area_width), 'cy' => $middle_of_bar, 'r' => 1), $g_circles);
					}
				}
			}
		}

		// write a new line along the bottom since the draw_rectangle_with_border above had written on top of it
		$this->svg_dom->draw_svg_line($this->i['left_start'], $this->i['graph_top_end'], $this->i['graph_left_end'], $this->i['graph_top_end'], self::$c['color']['notches'], 1);
	}
	public function render_graph_dimensions()
	{
		parent::render_graph_dimensions();
		$longest_sub_identifier_width = self::text_string_width('Min: ' . $this->i['graph_max_value'] . ' / Avg: XX / Max: ' . $this->i['graph_max_value'], $this->i['identifier_size']);
		$this->i['left_start'] = max($this->i['left_start'], $longest_sub_identifier_width);
	}
	protected function maximum_graph_value($v = -1)
	{
		$max = 0;
		foreach($this->test_result->test_result_buffer->buffer_items as &$buffer_item)
		{
			$val = $buffer_item->get_result_value();
			if(strpos($val, ','))
			{
				$val = max(explode(',', $val));
			}
			$raw = $buffer_item->get_result_raw() ? explode(':', $buffer_item->get_result_raw()) : '';
			if((empty($raw) || count($raw) < 2) && $buffer_item->get_result_raw())
			{
				$raw = explode(',', $buffer_item->get_result_raw());
			}
			$raw = is_numeric($raw) ? max($raw) : 0;
			$max = max($max, $val, $raw);
		}

		$maximum = (ceil(round($max * 1.04) / $this->i['mark_count']) + 1) * $this->i['mark_count'];
		$maximum = round(ceil($maximum / $this->i['mark_count']), (0 - strlen($maximum) + 2)) * $this->i['mark_count'];
		return $maximum;
	}
}

?>
