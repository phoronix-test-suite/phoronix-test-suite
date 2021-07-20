<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2011 - 2016, Phoronix Media
	Copyright (C) 2011 - 2016, Michael Larabel
	pts_ScatterPlot.php: The scatter plot graph object that extends pts_Graph.php.

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

class pts_graph_scatter_plot extends pts_graph_core
{
	public function __construct(&$result_object, &$result_file = null, $extra_attributes = null)
	{
		parent::__construct($result_object, $result_file, $extra_attributes);
		$this->i['show_graph_key'] = true;
		$this->i['show_background_lines'] = true;
		$this->i['iveland_view'] = true;
		$this->i['min_time'] = 0;
		$this->i['max_time'] = 0;
		$this->i['spread_time'] = 0;
		$this->i['plot_overview_text'] = true;
		$this->i['min_identifier_size'] = 6.5;
		//$this->i['graph_width'] = 1400;
		//$this->i['graph_height'] = 600;
		//$this->update_graph_dimensions(-1, -1, true);
	}
	protected function render_graph_pre_init()
	{
		$this->i['min_time'] = strtotime(min($this->test_result->test_result_buffer->get_identifiers()));
		$this->i['max_time'] = strtotime(max($this->test_result->test_result_buffer->get_identifiers()));
		$this->i['spread_time'] = $this->i['max_time'] - $this->i['min_time'];

		// Do some common work to this object

		$graph_identifiers_count = count($this->graph_identifiers);
		$this->i['identifier_width'] = ($this->i['graph_left_end'] - $this->i['left_start']) / ($graph_identifiers_count + 1);

		$longest_string = pts_strings::find_longest_string($this->graph_identifiers);
		$this->i['identifier_size'] = $this->text_size_bounds($longest_string, $this->i['identifier_size'], $this->i['min_identifier_size'], $this->i['identifier_width'] - 4);

		if($this->i['identifier_size'] <= $this->i['min_identifier_size'])
		{
			list($text_width, $text_height) = pts_svg_dom::estimate_text_dimensions($longest_string, $this->i['min_identifier_size'] + 0.5);
			$this->i['bottom_offset'] += $text_width;
			$this->update_graph_dimensions($this->i['graph_width'], $this->i['graph_height'] + $text_width);

			if(($text_height + 4) > $this->i['identifier_width'] && $graph_identifiers_count > 3)
			{
				// Show the identifiers as frequently as they will fit
				$this->i['display_select_identifiers'] = ceil(($text_height + 4) / $this->i['identifier_width']);
			}
		}

	}
	protected function render_graph_result()
	{
		if($this->i['spread_time'] == 0)
			return;

		$bar_count = count($this->results);
		$g = $this->svg_dom->make_g(array('stroke-width' => 1));
		foreach($this->results as $identifier => &$group)
		{
			$points = array();
			$paint_color = $this->get_paint_color($identifier);
			foreach($group as &$buffer_item)
			{
				$key_time = strtotime($buffer_item->get_result_identifier());
				$value = $buffer_item->get_result_value();

				if($value <= 0)
				{
					continue;
				}

				$x = $this->i['left_start'] + (($this->i['graph_left_end'] - $this->i['left_start']) * (($key_time - $this->i['min_time']) / $this->i['spread_time']));
				$y = $this->i['graph_top_end'] + 1 - round(($value / $this->i['graph_max_value']) * ($this->i['graph_top_end'] - $this->i['top_start']));
				$this->svg_dom->add_element('ellipse', array('cx' => $x, 'cy' => $y, 'rx' => 2, 'ry' => 2, 'fill' => $paint_color, 'stroke' => $paint_color), $g);
				$points[] = array($x, $y);
			}

			$sum_x = 0;
			$sum_y = 0;
			$sum_x_sq = 0;
			$sum_y_sq = 0;
			$sum_xy = 0;

			foreach($points as $point_set)
			{
				$sum_x += $point_set[0];
				$sum_y += $point_set[1];
				$sum_x_sq += pow($point_set[0], 2);
				$sum_y_sq += pow($point_set[1], 2);
				$sum_xy += $point_set[0] * $point_set[1];
			}

			$point_count = count($points);

			if($point_count == 0)
				continue;

			$mean_x = $sum_x / $point_count;
			$mean_y = $sum_y / $point_count;
			$denominator = ($sum_x_sq - $mean_x * $sum_x);
			$m = ($sum_x_sq - $mean_x * $sum_x) == 0 ? 0 : ($sum_xy - $mean_y * $sum_x) / $denominator;
			$b = $mean_y - $mean_x * $m;

			$pearson_num = $sum_xy - ($sum_x * $sum_y / $point_count);
			$pearson_den = sqrt(($sum_x_sq - pow($sum_x, 2) / $point_count) * ($sum_y_sq - pow($sum_y, 2) / $point_count));
			$pearson_coefficient = $pearson_den == 0 ? 0 : $pearson_num / $pearson_den;


			$start_y = ($m * $this->i['left_start']) + $b;
			$end_y = ($m * $this->i['graph_left_end']) + $b;

			// TODO: hook into pearson_coefficient for figuring out if the line is good or not, for now if it goes out of bounds assume bad
			if($start_y > $this->i['graph_top_end'] || $start_y < $this->i['top_start'] || $end_y > $this->i['graph_top_end'] || $end_y < $this->i['top_start'])
			{
				continue;
			}

			//$this->svg_dom->draw_svg_line($this->i['left_start'], $start_y, $this->i['graph_left_end'], $end_y, $paint_color, 2);

		}
	}
	protected function render_graph_identifiers()
	{
		//return;
		$px_from_top_end = $this->i['graph_top_end'] + 5;

		if(!is_array($this->graph_identifiers))
		{
			return;
		}

		$this->svg_dom->draw_svg_line($this->i['left_start'] + $this->i['identifier_width'], $this->i['graph_top_end'], $this->i['graph_left_end'], $this->i['graph_top_end'], self::$c['color']['notches'], 10, array('stroke-dasharray' => '1,' . ($this->i['identifier_width'] - 1)));

		$i = 0;
		foreach($this->test_result->test_result_buffer->buffer_items as &$buffer_item)
		{
			$px_from_left = $this->i['left_start'] + ($this->i['identifier_width'] * ($i + 1));

			if($this->i['identifier_size'] <= $this->i['min_identifier_size'])
			{
				$this->svg_dom->add_text_element($buffer_item->get_result_identifier(), array('x' => $px_from_left, 'y' => ($px_from_top_end + 2), 'font-size' => 9, 'fill' => self::$c['color']['headers'], 'text-anchor' => 'start', 'dominant-baseline' => 'middle', 'transform' => 'rotate(90 ' . $px_from_left . ' ' . ($px_from_top_end + 2) . ')'));
			}
			else
			{
				$this->svg_dom->add_text_element($buffer_item->get_result_identifier(), array('x' => $px_from_left, 'y' => ($px_from_top_end + 2), 'font-size' => $this->i['identifier_size'], 'fill' => self::$c['color']['headers'], 'text-anchor' => 'middle', 'dominant-baseline' => 'text-before-edge'));
			}
			$i++;
		}
	}
}

?>
