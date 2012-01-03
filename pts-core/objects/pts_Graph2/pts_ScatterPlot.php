<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2011 - 2012, Phoronix Media
	Copyright (C) 2011 - 2012, Michael Larabel
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

class pts_ScatterPlot extends pts_Graph
{
	public $plot_overview_text = true;
	public $min_time = 0;
	public $max_time = 0;
	public $spread_time = 0;

	public function __construct(&$result_object, &$result_file = null)
	{
		parent::__construct($result_object, $result_file);
		$this->graph_show_key = true;
		$this->graph_background_lines = true;
		$this->iveland_view = true;
		//$this->c['graph']['width'] = 1400;
		//$this->c['graph']['height'] = 600;
		//$this->update_graph_dimensions(-1, -1, true);
	}
	protected function maximum_graph_value()
	{
		$maximum = 0;

		foreach($this->graph_data as &$data_r)
		{
			$maximum = max(max($data_r), $maximum);
		}

		$maximum = (floor(round($maximum * 1.2) / $this->c['graph']['mark_count']) + 1) * $this->c['graph']['mark_count'];
		$maximum = round(ceil($maximum / $this->c['graph']['mark_count']), (0 - strlen($maximum) + 2)) * $this->c['graph']['mark_count'];

		return $maximum;
	}
	protected function render_graph_pre_init()
	{
		$this->min_time = min($this->graph_identifiers);
		$this->max_time = max($this->graph_identifiers);
		$this->spread_time = $this->max_time - $this->min_time;

		// Do some common work to this object
/*
		$graph_identifiers_count = count($this->graph_identifiers);
		$identifier_count = $graph_identifiers_count > 1 ? $graph_identifiers_count : count($this->graph_data[0]);
		$this->identifier_width = ($this->graph_left_end - $this->c['pos']['left_start']) / ($identifier_count + 1);

		$longest_string = pts_strings::find_longest_string($this->graph_identifiers);
		$this->graph_font_size_identifiers = $this->text_size_bounds($longest_string, $this->graph_font, $this->graph_font_size_identifiers, $this->minimum_identifier_font, $this->identifier_width - 4);

		if($this->graph_font_size_identifiers <= $this->minimum_identifier_font)
		{
			list($text_width, $text_height) = bilde_renderer::soft_text_string_dimensions($longest_string, $this->graph_font, $this->minimum_identifier_font + 0.5);
			$this->graph_bottom_offset += $text_width;
			$this->update_graph_dimensions($this->c['graph']['width'], $this->c['graph']['height'] + $text_width);

			if(($text_height + 4) > $this->identifier_width && $graph_identifiers_count > 3)
			{
				// Show the identifiers as frequently as they will fit
				$this->show_select_identifiers = ceil(($text_height + 4) / $this->identifier_width);
			}
		}
*/
	}
	protected function render_graph_result()
	{
		$bar_count = count($this->graph_data);

		for($i_o = 0; $i_o < $bar_count; $i_o++)
		{
			$paint_color = $this->get_paint_color((isset($this->graph_data_title[$i_o]) ? $this->graph_data_title[$i_o] : null));
			$points = array();

			foreach(array_keys($this->graph_data[$i_o]) as $i)
			{
				$key_time = $this->graph_identifiers[$i];
				$value = $this->graph_data[$i_o][$i];

				if($value <= 0)
				{
					continue;
				}

				$x = $this->c['pos']['left_start'] + (($this->graph_left_end - $this->c['pos']['left_start']) * (($key_time - $this->min_time) / $this->spread_time));
				$y = $this->graph_top_end + 1 - round(($value / $this->graph_maximum_value) * ($this->graph_top_end - $this->c['pos']['top_start']));
				$this->svg_dom->add_element('ellipse', array('cx' => $x, 'cy' => $y, 'rx' = 2, 'ry' = 2, 'fill' => $paint_color, 'stroke' => $paint_color, 'stroke-width' => 1));
				array_push($points, array($x, $y));
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
			$mean_x = $sum_x / $point_count;
			$mean_y = $sum_y / $point_count;
			$denominator = ($sum_x_sq - $mean_x * $sum_x);
			$m = ($sum_x_sq - $mean_x * $sum_x) == 0 ? 0 : ($sum_xy - $mean_y * $sum_x) / $denominator;
			$b = $mean_y - $mean_x * $m;

			$pearson_num = $sum_xy - ($sum_x * $sum_y / $point_count);
			$pearson_den = sqrt(($sum_x_sq - pow($x_sum, 2) / $point_count) * ($sum_y_sq - pow($sum_y, 2) / $point_count));
			$pearson_coefficient = $pearson_den == 0 ? 0 : $pearson_num / $pearson_den;


			$start_y = ($m * $this->c['pos']['left_start']) + $b;
			$end_y = ($m * $this->graph_left_end) + $b;

			// TODO: hook into pearson_coefficient for figuring out if the line is good or not, for now if it goes out of bounds assume bad
			if($start_y > $this->graph_top_end || $start_y < $this->c['pos']['top_start'] || $end_y > $this->graph_top_end || $end_y < $this->c['pos']['top_start'])
			{
				continue;
			}

			$this->svg_dom->draw_svg_line($this->c['pos']['left_start'], $start_y, $this->graph_left_end, $end_y, $paint_color, 2);

		}
	}
	protected function render_graph_identifiers()
	{
		return;
		$px_from_top_end = $this->graph_top_end + 5;

		if(!is_array($this->graph_identifiers))
		{
			return;
		}

		$this->svg_dom->draw_svg_line($this->c['pos']['left_start'] + $this->identifier_width, $this->graph_top_end, $this->graph_left_end, $this->graph_top_end, $this->c['color']['notches'], 10, array('stroke-dasharray' => '1,' . ($this->identifier_width - 1)));

		foreach(array_keys($this->graph_identifiers) as $i)
		{
			if(is_array($this->graph_identifiers[$i]))
			{
				break;
			}

			if($this->show_select_identifiers != null && ($i % $this->show_select_identifiers) != 0)
			{
				// $show_select_identifiers contains the value of how frequently to display identifiers
				continue;
			}

			$px_from_left = $this->c['pos']['left_start'] + ($this->identifier_width * ($i + 1));

			if($this->graph_font_size_identifiers <= $this->minimum_identifier_font)
			{
				$this->svg_dom->add_text_element($this->graph_identifiers[$i], array('x' => $px_from_left, 'y' => ($px_from_top_end + 2), 'font-size' => 9, 'fill' => $this->c['color']['headers'], 'text-anchor' => 'start', 'dominant-baseline' => 'middle', 'transform' => 'rotate(90 ' . $px_from_left . ' ' . ($px_from_top_end + 2) . ')'));
			}
			else
			{
				$this->svg_dom->add_text_element($this->graph_identifiers[$i], array('x' => $px_from_left, 'y' => ($px_from_top_end + 2), 'font-size' => $this->graph_font_size_identifiers, 'fill' => $this->c['color']['headers'], 'text-anchor' => 'middle', 'dominant-baseline' => 'text-before-edge'));
			}
		}
	}
}

?>
