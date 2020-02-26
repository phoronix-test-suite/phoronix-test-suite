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

class pts_graph_vertical_bars extends pts_graph_core
{
	protected $make_identifiers_web_links = false;
	public function __construct(&$result_object, &$result_file = null, $extra_attributes = null)
	{
		parent::__construct($result_object, $result_file, $extra_attributes);
		$this->i['iveland_view'] = true;
		$this->i['graph_orientation'] = 'VERTICAL';
		$this->i['identifier_height'] = -1;

		if(isset($extra_attributes['make_identifiers_web_links']) && !empty($extra_attributes['make_identifiers_web_links']))
		{
			$this->make_identifiers_web_links = $extra_attributes['make_identifiers_web_links'];
		}

		$this->i['min_identifier_size'] = 6;
		$this->i['identifier_width'] = -1;
	}
	protected function render_graph_pre_init()
	{
		// Do some common work to this object
		$identifier_count = count($this->graph_identifiers);
		$this->i['identifier_width'] = max(1, floor(($this->i['graph_left_end'] - $this->i['left_start']) / $identifier_count));
		$longest_string = pts_strings::find_longest_string($this->graph_identifiers);
		$width = $this->i['identifier_width'] - 4;
		$this->i['identifier_size'] = $this->text_size_bounds($longest_string, $this->i['identifier_size'], $this->i['min_identifier_size'], $width);
		if($this->i['identifier_size'] <= $this->i['min_identifier_size'])
		{
			$extra_height = $this->text_string_width($longest_string, 9);
			$this->update_graph_dimensions($this->i['graph_width'], $this->i['graph_height'] += $extra_height, false);
			$this->i['bottom_offset'] += $extra_height;
//			$this->i['graph_top_end'] += $extra_height;
		}
	}
	protected function render_graph_identifiers()
	{
		$px_from_top_end = $this->i['graph_top_end'] + 5;
		$this->svg_dom->draw_svg_line($this->i['left_start'] + $this->i['identifier_width'], $this->i['graph_top_end'], $this->i['graph_left_end'] - ($this->i['graph_width'] % $this->i['identifier_width']), $this->i['graph_top_end'], self::$c['color']['notches'], 10, array('stroke-dasharray' => '1,' . ($this->i['identifier_width'] - 1)));
		foreach(array_keys($this->graph_identifiers) as $i)
		{
			$px_bound_left = $this->i['left_start'] + ($this->i['identifier_width'] * $i);
			$px_bound_right = $px_bound_left + $this->i['identifier_width'];
			if($i == (count($this->graph_identifiers) - 1) && $px_bound_right != $this->i['graph_left_end'])
			{
				$px_bound_right = $this->i['graph_left_end'];
			}
			if($this->i['identifier_size'] <= $this->i['min_identifier_size'])
			{
				$x = $px_bound_left + ceil($this->i['identifier_width'] / 2);
				$this->svg_dom->add_text_element($this->graph_identifiers[$i], array('x' => $x, 'y' => $px_from_top_end, 'font-size' => 9, 'fill' => self::$c['color']['headers'], 'text-anchor' => 'start', 'dominant-baseline' => 'middle', 'font-weight' => 'bold', 'transform' => 'rotate(90 ' . $x . ' ' . $px_from_top_end . ')'));
			}
			else
			{
				$x = $px_bound_left + (($px_bound_right - $px_bound_left) * 0.5);
				$this->svg_dom->add_text_element($this->graph_identifiers[$i], array('x' => $x, 'y' => ($px_from_top_end + $this->i['identifier_size']), 'font-size' => $this->i['identifier_size'], 'fill' => self::$c['color']['headers'], 'text-anchor' => 'middle'));
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
			$r[] = $a;
			return (count($r) - 1);
		}
	}
	protected function render_graph_bars()
	{
		$bar_count = count($this->results);
		$separator_width = ($a = (8 - (floor($bar_count / 2) * 2))) > 0 ? $a : 0;
		$bar_width = floor(($this->i['identifier_width'] - $separator_width - ($bar_count * $separator_width)) / $bar_count);
		$bar_font_size_ratio = 1;

		while(floor($bar_width * 0.82) < self::text_string_width($this->i['graph_max_value'], floor(self::$c['size']['bars'] * $bar_font_size_ratio)) && $bar_font_size_ratio >= 0.3)
		{
			$bar_font_size_ratio -= 0.05;
		}

		$group_offsets = array();
		$id_offsets = array();
		foreach($this->results as $identifier => &$group)
		{
			$paint_color = $this->get_paint_color($identifier);
			foreach($group as &$buffer_item)
			{
				if($identifier == 0 && !$this->i['is_multi_way_comparison'])
				{
					// See if the result identifier matches something to be color-coded better
					$paint_color = self::identifier_to_branded_color($buffer_item->get_result_identifier(), $this->get_paint_color($identifier));
				}

				$i_o = $this->calc_offset($group_offsets, $identifier);
				if($this->i['is_multi_way_comparison'])
					$i = $this->calc_offset($id_offsets, $buffer_item->get_result_identifier());
				else
					$i = $this->calc_offset($id_offsets, $buffer_item->get_result_identifier() . ' ' . (isset($value) ? $value : ''));
				$value = $buffer_item->get_result_value();
				$graph_size = round(($value / $this->i['graph_max_value']) * ($this->i['graph_top_end'] - $this->i['top_start']));
				$value_plot_top = max($this->i['graph_top_end'] + 1 - $graph_size, 1);
				$px_bound_left = $this->i['left_start'] + ($this->i['identifier_width'] * $i) + ($bar_width * $i_o) + ($separator_width * ($i_o + 1));
				$px_bound_right = $px_bound_left + $bar_width;
				$title_tooltip = $buffer_item->get_result_identifier() . ': ' . $value;

				$std_error = -1;
				if(($raw_values = $buffer_item->get_result_raw_array()))
				{
					switch(count($raw_values))
					{
						case 0:
							$std_error = -1;
							break;
						case 1:
							$std_error = 0;
							break;
						default:
							$std_error = pts_math::standard_error($raw_values);
							break;
					}
				}

				$this->svg_dom->add_element('rect', array('x' => ($px_bound_left + 1), 'y' => $value_plot_top, 'width' => $bar_width, 'height' => ($this->i['graph_top_end'] - $value_plot_top), 'fill' => $this->adjust_color($buffer_item->get_result_identifier(), $paint_color), 'stroke' => self::$c['color']['body_light'], 'stroke-width' => 1, 'xlink:title' => $title_tooltip));

				if($std_error != -1 && $std_error > 0 && $value != null)
				{
					$std_error_height = 8;
					if($std_error > 0 && is_numeric($std_error))
					{
						$std_error_rel_size = round(($std_error / $this->i['graph_max_value']) * ($this->i['graph_top_end'] - $this->i['top_start']));
						if($std_error_rel_size > 4)
						{
							$std_error_base_bottom = ($value_plot_top - $std_error_rel_size);
							$std_error_base_top = ($value_plot_top + $std_error_rel_size);

							$g = $this->svg_dom->make_g(array('stroke' => self::$c['color']['notches'], 'stroke-width' => 1));
							$this->svg_dom->add_element('line', array('x1' => ($px_bound_left + 1), 'y1' => $std_error_base_bottom, 'x2' => $px_bound_left + $std_error_height, 'y2' => $std_error_base_bottom), $g);
							$this->svg_dom->add_element('line', array('x1' => ($px_bound_left + 1), 'y1' => $std_error_base_bottom, 'x2' => ($px_bound_left + 1), 'y2' => $std_error_base_top), $g);
							$this->svg_dom->add_element('line', array('x1' => ($px_bound_left + 1), 'y1' => $std_error_base_top, 'x2' => $px_bound_left + $std_error_height, 'y2' => $std_error_base_top), $g);
						}
					}
				}

				if(($px_bound_right - $px_bound_left) > 10)
				{
					// The bars are too skinny to be able to plot anything on them
					if($bar_font_size_ratio >= 0.5)
					{
						$x = $px_bound_left + (($px_bound_right - $px_bound_left) / 2);
						$this->svg_dom->add_text_element($value, array('x' => $x, 'y' => ($value_plot_top + 2), 'font-size' => floor(self::$c['size']['bars'] * $bar_font_size_ratio), 'fill' => self::$c['color']['body_text'], 'text-anchor' => 'middle', 'dominant-baseline' => 'text-before-edge'));
					}
					else if($bar_width >= self::$c['size']['bars'])
					{
						$x = $px_bound_left + (($px_bound_right - $px_bound_left) / 2);
						$this->svg_dom->add_text_element($value, array('x' => $x, 'y' => ($value_plot_top + 2), 'font-size' => floor(self::$c['size']['bars']), 'fill' => self::$c['color']['body_text'], 'text-anchor' => 'start', 'dominant-baseline' => 'middle', 'transform' => 'rotate(90 ' . $x . ' ' . ($value_plot_top + 2) . ')'));

					}
				}
			}
		}
		// write a new line along the bottom since the draw_rectangle_with_border above had written on top of it
		$this->svg_dom->draw_svg_line($this->i['left_start'], $this->i['graph_top_end'], $this->i['graph_left_end'], $this->i['graph_top_end'], self::$c['color']['notches'], 1);
	}
	protected function render_graph_result()
	{
		$this->render_graph_bars();
	}
}

?>
