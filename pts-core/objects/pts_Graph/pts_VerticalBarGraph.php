<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2012, Phoronix Media
	Copyright (C) 2008 - 2012, Michael Larabel
	pts_VerticalBarGraph.php: The vertical bar graph object that extends pts_Graph.php

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

class pts_VerticalBarGraph extends pts_Graph
{
	public function __construct(&$result_object, &$result_file = null)
	{
		parent::__construct($result_object, $result_file);

		if($result_file != null && $result_file instanceof pts_result_file)
		{
			$this->is_multi_way_comparison = $result_file->is_multi_way_comparison();
		}
		$this->i['min_identifier_size'] = 6;
		$this->i['identifier_width'] = -1;
	}
	protected function render_graph_pre_init()
	{
		// Do some common work to this object
		$identifier_count = count($this->graph_identifiers);
		$this->i['identifier_width'] = floor(($this->i['graph_left_end'] - $this->i['left_start']) / $identifier_count);

		$longest_string = pts_strings::find_longest_string($this->graph_identifiers);
		$width = $this->i['identifier_width'] - 4;
		$this->i['identifier_size'] = $this->text_size_bounds($longest_string, $this->i['identifier_size'], $this->i['min_identifier_size'], $width);

		if($this->i['identifier_size'] <= $this->i['min_identifier_size'])
		{
			$this->update_graph_dimensions($this->i['graph_width'], $this->i['graph_height'] + $this->text_string_width($longest_string, 9));
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
				$this->svg_dom->add_element_with_value('text', $this->graph_identifiers[$i], array('x' => $x, 'y' => $px_from_top_end, 'font-size' => 9, 'fill' => self::$c['color']['headers'], 'text-anchor' => 'start', 'dominant-baseline' => 'middle', 'transform' => 'rotate(90 ' . $x . ' ' . $px_from_top_end . ')'));
			}
			else
			{
				$x = $px_bound_left + (($px_bound_right - $px_bound_left) * 0.5);
				$this->svg_dom->add_element_with_value('text', $this->graph_identifiers[$i], array('x' => $x, 'y' => ($px_from_top_end + $this->i['identifier_size']), 'font-size' => $this->i['identifier_size'], 'fill' => self::$c['color']['headers'], 'text-anchor' => 'middle'));
			}
		}
	}
	protected function render_graph_bars()
	{
		$bar_count = count($this->graph_data);
		$separator_width = ($a = (8 - (floor($bar_count / 2) * 2))) > 0 ? $a : 0;
		$bar_width = floor(($this->i['identifier_width'] - $separator_width - ($bar_count * $separator_width)) / $bar_count);

		for($i_o = 0; $i_o < $bar_count; $i_o++)
		{
			$paint_color = $this->get_paint_color((isset($this->graph_data_title[$i_o]) ? $this->graph_data_title[$i_o] : null));

			foreach(array_keys($this->graph_data[$i_o]) as $i)
			{
				$value = pts_math::set_precision($this->graph_data[$i_o][$i], 2);
				$graph_size = round(($value / $this->i['graph_max_value']) * ($this->i['graph_top_end'] - $this->i['top_start']));
				$value_plot_top = max($this->i['graph_top_end'] + 1 - $graph_size, 1);

				$px_bound_left = $this->i['left_start'] + ($this->i['identifier_width'] * $i) + ($bar_width * $i_o) + ($separator_width * ($i_o + 1));
				$px_bound_right = $px_bound_left + $bar_width;

				$title_tooltip = $this->graph_identifiers[$i] . ': ' . $value;
				$run_std_deviation = isset($this->graph_data_raw[$i_o][$i]) ? pts_math::standard_deviation(pts_strings::colon_explode($this->graph_data_raw[$i_o][$i])) : 0;

				if($run_std_deviation > 0)
				{
					$title_tooltip .= ' || ' . pts_math::set_precision($run_std_deviation, 1) . ' STD';
				}

				$this->svg_dom->add_element('rect', array('x' => ($px_bound_left + 1), 'y' => $value_plot_top, 'width' => $bar_width, 'height' => ($this->i['graph_top_end'] - $value_plot_top), 'fill' => (in_array($this->graph_identifiers[$i], $this->value_highlights) ? self::$c['color']['alert'] : $paint_color), 'stroke' => self::$c['color']['body_light'], 'stroke-width' => 1, 'xlink:title' => $title_tooltip));

				if(($px_bound_right - $px_bound_left) < 15)
				{
					// The bars are too skinny to be able to plot anything on them
					continue;
				}

				$x = $px_bound_left + (($px_bound_right - $px_bound_left) / 2);
				if($graph_size > 18)
				{
					$this->svg_dom->add_element_with_value('text', $value, array('x' => $x, 'y' => ($value_plot_top + 2), 'font-size' => floor(self::$c['size']['bars'] * 0.9), 'fill' => self::$c['color']['body_text'], 'text-anchor' => 'middle', 'dominant-baseline' => 'text-before-edge'));
				}
				else
				{
					// Make things more compact
					$this->svg_dom->add_element_with_value('text', $value, array('x' => $x, 'y' => ($value_plot_top + 2), 'font-size' => floor(self::$c['size']['bars'] * 0.6), 'fill' => self::$c['color']['body_text'], 'text-anchor' => 'middle', 'dominant-baseline' => 'text-before-edge'));
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
