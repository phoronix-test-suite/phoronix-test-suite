<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2012, Phoronix Media
	Copyright (C) 2008 - 2012, Michael Larabel
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

class pts_HorizontalBarGraph extends pts_Graph
{
	protected $identifier_height = -1;

	public function __construct(&$result_object, &$result_file = null)
	{
		parent::__construct($result_object, $result_file);
		$this->iveland_view = true;
		$this->graph_orientation = 'HORIZONTAL';
	}
	protected function render_graph_pre_init()
	{
		// Do some common work to this object
		$identifier_count = count($this->graph_identifiers);
		$this->identifier_height = floor(($this->graph_top_end - $this->c['pos']['top_start']) / $identifier_count);
	}
	protected function render_graph_identifiers()
	{
		$px_from_top_end = $this->graph_top_end + 5;

		$this->svg_dom->draw_svg_line($this->c['pos']['left_start'], $this->c['pos']['top_start'] + $this->identifier_height, $this->c['pos']['left_start'], $this->graph_top_end - ($this->c['graph']['height'] % $this->identifier_height), $this->c['color']['notches'], 10, array('stroke-dasharray' => 1 . ',' . ($this->identifier_height - 1)));
		$multi_way = $this->is_multi_way_comparison && count($this->graph_data) > 1;
		$middle_of_vert = $this->c['pos']['top_start'] + ($multi_way ? 5 : 0) - ($this->identifier_height * 0.5) - 2;

		foreach(array_keys($this->graph_identifiers) as $i)
		{
			$middle_of_vert += $this->identifier_height;

			if($multi_way)
			{
				foreach(explode(' - ', $this->graph_identifiers[$i]) as $i => $identifier_line)
				{
					$x = 16 + round($i * $this->graph_font_size_identifiers * 1.4);
					$this->svg_dom->add_text_element($identifier_line, array('x' => $x, 'y' => $middle_of_vert, 'font-size' => $this->graph_font_size_identifiers, 'fill' => $this->c['color']['headers'], 'text-anchor' => 'middle', 'dominant-baseline' => 'text-before-edge', 'transform' => 'rotate(90 ' . $x . ' ' . $middle_of_vert . ')'));
				}
			}
			else
			{
				$this->svg_dom->add_text_element($this->graph_identifiers[$i], array('x' => ($this->c['pos']['left_start'] - 5), 'y' => $middle_of_vert, 'font-size' => $this->graph_font_size_identifiers, 'fill' => $this->c['color']['headers'], 'text-anchor' => 'end', 'dominant-baseline' => 'middle'));
			}
		}
	}
	protected function render_graph_bars()
	{
		$bar_count = count($this->graph_data);
		$separator_height = ($a = (6 - (floor($bar_count / 2) * 2))) > 0 ? $a : 0;
		$multi_way = $this->is_multi_way_comparison && count($this->graph_data) > 1;
		$bar_height = floor(($this->identifier_height - ($multi_way ? 4 : 0) - $separator_height - ($bar_count * $separator_height)) / $bar_count);

		for($i_o = 0; $i_o < $bar_count; $i_o++)
		{
			$paint_color = $this->get_paint_color((isset($this->graph_data_title[$i_o]) ? $this->graph_data_title[$i_o] : null));

			foreach(array_keys($this->graph_data[$i_o]) as $i)
			{
				$value = $this->graph_data[$i_o][$i];
				$graph_size = max(0, round(($value / $this->graph_maximum_value) * ($this->graph_left_end - $this->c['pos']['left_start'])));
				$value_end_right = max($this->c['pos']['left_start'] + $graph_size, 1);

				$px_bound_top = $this->c['pos']['top_start'] + ($multi_way ? 5 : 0) + ($this->identifier_height * $i) + ($bar_height * $i_o) + ($separator_height * ($i_o + 1));
				$px_bound_bottom = $px_bound_top + $bar_height;
				$middle_of_bar = $px_bound_top + ($bar_height / 2);

				$title_tooltip = $this->graph_identifiers[$i] . ': ' . $value;

				$std_error = -1;
				if(isset($this->graph_data_raw[$i_o][$i]))
				{
					$std_error = pts_strings::colon_explode($this->graph_data_raw[$i_o][$i]);

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

				$this->svg_dom->add_element('rect', array('x' => $this->c['pos']['left_start'], 'y' => $px_bound_top, 'width' => $graph_size, 'height' => $bar_height, 'fill' => (in_array($this->graph_identifiers[$i], $this->value_highlights) ? $this->c['color']['highlight'] : $paint_color), 'stroke' => $this->c['color']['body_light'], 'stroke-width' => 1, 'xlink:title' => $title_tooltip));

				if($std_error != -1 && $value != null)
				{
					$std_error_height = 8;

					if($std_error > 0 && is_numeric($std_error))
					{
						$std_error_rel_size = round(($std_error / $this->graph_maximum_value) * ($this->graph_left_end - $this->c['pos']['left_start']));
						if($std_error_rel_size > 4)
						{
							$this->svg_dom->draw_svg_line(($value_end_right - $std_error_rel_size), $px_bound_top, ($value_end_right - $std_error_rel_size), $px_bound_top + $std_error_height, $this->c['color']['notches'], 1);
							$this->svg_dom->draw_svg_line(($value_end_right + $std_error_rel_size), $px_bound_top, ($value_end_right + $std_error_rel_size), $px_bound_top + $std_error_height, $this->c['color']['notches'], 1);
							$this->svg_dom->draw_svg_line(($value_end_right - $std_error_rel_size), $px_bound_top, ($value_end_right + $std_error_rel_size), $px_bound_top, $this->c['color']['notches'], 1);
						}
					}

					$bar_offset_34 = $middle_of_bar + ($multi_way ? 0 : ($bar_height / 5) + 4);
					$this->svg_dom->add_text_element('SE +/- ' . pts_math::set_precision($std_error, 2), array('x' => ($this->c['pos']['left_start'] - 5), 'y' => $bar_offset_34, 'font-size' => ($this->graph_font_size_identifiers - 2), 'fill' => $this->c['color']['text'], 'text-anchor' => 'end', 'dominant-baseline' => 'middle'));
				}

				if(($this->text_string_width($value, $this->graph_font, $this->graph_font_size_identifiers) + 2) < $graph_size)
				{
					$this->svg_dom->add_text_element($value, array('x' => ($value_end_right - 5), 'y' => $middle_of_bar, 'font-size' => $this->graph_font_size_identifiers, 'fill' => $this->c['color']['body_text'], 'text-anchor' => 'end', 'dominant-baseline' => 'middle'));
				}
				else if($value > 0)
				{
					// Write it in front of the result
					$this->svg_dom->add_text_element($value, array('x' => ($value_end_right + 6), 'y' => $middle_of_bar, 'font-size' => $this->graph_font_size_identifiers, 'fill' => $this->c['color']['text'], 'text-anchor' => 'start', 'dominant-baseline' => 'middle'));
				}
			}
		}

		// write a new line along the bottom since the draw_rectangle_with_border above had written on top of it
		$this->svg_dom->draw_svg_line($this->c['pos']['left_start'], $this->graph_top_end, $this->graph_left_end, $this->graph_top_end, $this->c['color']['notches'], 1);
	}
	protected function render_graph_result()
	{
		$this->render_graph_bars();
	}
}

?>
