<?php
/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2015, Phoronix Media
	Copyright (C) 2008 - 2015, Michael Larabel
	pts_HorizontalBarGraph.php: The horizontal bar graph object that extends pts_Graph.php

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

class pts_graph_horizontal_bars extends pts_graph_core
{
	public function __construct(&$result_object, &$result_file = null, $extra_attributes = null)
	{
		parent::__construct($result_object, $result_file, $extra_attributes);
		$this->i['iveland_view'] = true;
		$this->i['graph_orientation'] = 'HORIZONTAL';
		$this->i['identifier_height'] = -1;
	}
	protected function render_graph_pre_init()
	{
		// Do some common work to this object
		$this->i['identifier_height'] = floor(($this->i['graph_top_end'] - $this->i['top_start']) / count($this->graph_identifiers));
	}
	protected function render_graph_identifiers()
	{
		$px_from_top_end = $this->i['graph_top_end'] + 5;

		$this->svg_dom->draw_svg_line($this->i['left_start'] + 0.5, $this->i['top_start'] + $this->i['identifier_height'], $this->i['left_start'] + 0.5, $this->i['graph_top_end'] - ($this->i['graph_height'] % $this->i['identifier_height']), self::$c['color']['notches'], 11, array('stroke-dasharray' => 1 . ',' . ($this->i['identifier_height'] - 1)));
		$middle_of_vert = $this->i['top_start'] + ($this->is_multi_way_comparison ? 5 : 0) - ($this->i['identifier_height'] * 0.5) - 2;

		$g = $this->svg_dom->make_g(array('font-size' => $this->i['identifier_size'], 'fill' => self::$c['color']['headers']));
		foreach($this->graph_identifiers as $identifier)
		{
			$middle_of_vert += $this->i['identifier_height'];
			if($this->is_multi_way_comparison)
			{
				foreach(explode(' - ', $identifier) as $i => $identifier_line)
				{
					$x = 8;
					$this->svg_dom->add_text_element($identifier_line, array('x' => $x, 'y' => $middle_of_vert, 'text-anchor' => 'middle', 'transform' => 'rotate(90 ' . $x . ' ' . $middle_of_vert . ')'), $g);
				}
			}
			else
			{
				$this->svg_dom->add_text_element($identifier, array('x' => ($this->i['left_start'] - 5), 'y' => $middle_of_vert, 'text-anchor' => 'end'), $g);
			}
		}
	}
	protected function calc_offset(&$r, $a)
	{
		if(($s = array_search($a, $r)) !== false)
		{
			return $s;
		}
		else
		{
			array_push($r, $a);
			return (count($r) - 1);
		}
	}
	protected function render_graph_bars()
	{
		$bar_count = count($this->results);
		$separator_height = ($a = (6 - (floor($bar_count / 2) * 2))) > 0 ? $a : 0;
		$bar_height = floor(($this->i['identifier_height'] - ($this->is_multi_way_comparison ? 4 : 0) - $separator_height - ($bar_count * $separator_height)) / $bar_count);
		$this->i['graph_max_value'] = $this->i['graph_max_value'] != 0 ? $this->i['graph_max_value'] : 1;
		$work_area_width = $this->i['graph_left_end'] - $this->i['left_start'];

		$group_offsets = array();
		$id_offsets = array();
		$g_bars = $this->svg_dom->make_g(array('stroke' => self::$c['color']['body_light'], 'stroke-width' => 1));
		$g_se = $this->svg_dom->make_g(array('font-size' => ($this->i['identifier_size'] - 2), 'fill' => self::$c['color']['text'], 'text-anchor' => 'end'));
		$g_values = $this->svg_dom->make_g(array('font-size' => $this->i['identifier_size'], 'fill' => self::$c['color']['body_text']));
		$bar_x = $this->i['left_start'] + 0.5;
		foreach($this->results as $identifier => &$group)
		{
			$paint_color = $this->get_paint_color($identifier);
			foreach($group as &$buffer_item)
			{
				// if identifier is 0, not a multi-way comparison or anything special
				if($identifier == 0)
				{
					// See if the result identifier matches something to be color-coded better
					$result_identifier = strtolower($buffer_item->get_result_identifier());
					if(strpos($result_identifier, 'geforce') !== false || strpos($result_identifier, 'nvidia') !== false)
					{
						$paint_color = '#75b900';
					}
					else if(strpos($result_identifier, 'radeon') !== false || strpos($result_identifier, 'amd ') !== false)
					{
						$paint_color = '#f1052d';
					}
					else if(strpos($result_identifier, 'intel ') !== false)
					{
						$paint_color = '#0b5997';
					}
				}

				$i_o = $this->calc_offset($group_offsets, $identifier);
				$i = $this->calc_offset($id_offsets, $buffer_item->get_result_identifier());
				$value = $buffer_item->get_result_value();
				$graph_size = max(0, round(($value / $this->i['graph_max_value']) * $work_area_width));
				$value_end_right = max($this->i['left_start'] + $graph_size, 1);
				$px_bound_top = $this->i['top_start'] + ($this->is_multi_way_comparison ? 5 : 0) + ($this->i['identifier_height'] * $i) + ($bar_height * $i_o) + ($separator_height * ($i_o + 1));
				$px_bound_bottom = $px_bound_top + $bar_height;
				$middle_of_bar = $px_bound_top + ($bar_height / 2) + ($this->i['identifier_size'] - 4);
				$title_tooltip = $buffer_item->get_result_identifier() . ': ' . $value;

				$std_error = -1;
				if(($raw_values = $buffer_item->get_result_raw()))
				{
					$std_error = pts_strings::colon_explode($raw_values);

					switch(count($std_error))
					{
						case 0:
							$std_error = -1;
							break;
						case 1:
							$std_error = 0;
							break;
						default:
							$std_error = pts_math::standard_error($std_error);
							break;
					}
				}

				$this->svg_dom->add_element('rect', array('x' => $bar_x, 'y' => $px_bound_top + 0.5, 'height' => $bar_height, 'width' => $graph_size, 'fill' => (in_array($buffer_item->get_result_identifier(), $this->value_highlights) ? self::$c['color']['highlight'] : $paint_color), 'xlink:title' => $title_tooltip), $g_bars);

				if($std_error != -1 && $value != null)
				{
					$std_error_height = 8;
					if($std_error > 0 && is_numeric($std_error))
					{
						$std_error_rel_size = round(($std_error / $this->i['graph_max_value']) * ($this->i['graph_left_end'] - $this->i['left_start']));
						if($std_error_rel_size > 4)
						{
							$std_error_base_left = ($value_end_right - $std_error_rel_size) + 0.5;
							$std_error_base_right = ($value_end_right + $std_error_rel_size) + 0.5;
							$this->svg_dom->draw_svg_line($std_error_base_left, $px_bound_top, $std_error_base_left, $px_bound_top + $std_error_height, self::$c['color']['notches'], 1);
							$this->svg_dom->draw_svg_line($std_error_base_right, $px_bound_top, $std_error_base_right, $px_bound_top + $std_error_height, self::$c['color']['notches'], 1);
							$this->svg_dom->draw_svg_line($std_error_base_left, $px_bound_top + 0.5, $std_error_base_right, $px_bound_top + 0.5, self::$c['color']['notches'], 1);
						}
					}
					$bar_offset_34 = round($middle_of_bar + ($this->is_multi_way_comparison ? 0 : ($bar_height / 5) + 1));
					$this->svg_dom->add_text_element('SE +/- ' . pts_math::set_precision($std_error, 2), array('y' => $bar_offset_34, 'x' => ($this->i['left_start'] - 5)), $g_se);
				}

				if((self::text_string_width($value, $this->i['identifier_size']) + 2) < $graph_size)
				{
					if(isset($this->d['identifier_notes'][$buffer_item->get_result_identifier()]) && $this->i['compact_result_view'] == false)
					{
						$note_size = self::$c['size']['key'] - 2;
						$this->svg_dom->add_text_element($this->d['identifier_notes'][$buffer_item->get_result_identifier()], array('x' => ($this->i['left_start'] + 4), 'y' => ($px_bound_top + self::$c['size']['key']), 'font-size' => $note_size, 'fill' => self::$c['color']['body_text'], 'text-anchor' => 'start'));
					}

					$this->svg_dom->add_text_element($value, array('x' => ($value_end_right - 5), 'y' => $middle_of_bar, 'text-anchor' => 'end'), $g_values);
				}
				else if($value > 0)
				{
					// Write it in front of the result
					$this->svg_dom->add_text_element($value, array('x' => ($value_end_right + 6), 'y' => $middle_of_bar, 'fill' => self::$c['color']['text'], 'text-anchor' => 'start'), $g_values);
				}
			}
		}
	}
	protected function render_graph_result()
	{
		$this->render_graph_bars();
	}
}

?>
