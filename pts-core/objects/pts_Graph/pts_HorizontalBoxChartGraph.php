<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2011, Phoronix Media
	Copyright (C) 2008 - 2011, Michael Larabel
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

class pts_HorizontalBoxChartGraph extends pts_HorizontalBarGraph
{
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
				$min_value = round(min($this->graph_data[$i_o][$i]), 2);
				$avg_value = round(array_sum($this->graph_data[$i_o][$i]) / count($this->graph_data[$i_o][$i]), 2);
				$max_value = round(max($this->graph_data[$i_o][$i]), 2);


				$px_bound_top = $this->graph_top_start + ($multi_way ? 5 : 0) + ($this->identifier_height * $i) + ($bar_height * $i_o) + ($separator_height * ($i_o + 1));
				$px_bound_bottom = $px_bound_top + $bar_height;
				$middle_of_bar = $px_bound_top + ($bar_height / 2);

				$value = 'Min: ' . $min_value . ' / Avg: ' . $avg_value . ' / Max: ' . $max_value;
				$title_tooltip = $this->graph_identifiers[$i] . ': ' . $value;

				$value_end_left = max($this->graph_left_start + round(($min_value / $this->graph_maximum_value) * ($this->graph_left_end - $this->graph_left_start)), 1);
				$value_end_right = $this->graph_left_start + round(($max_value / $this->graph_maximum_value) * ($this->graph_left_end - $this->graph_left_start));
				$box_color = in_array($this->graph_identifiers[$i], $this->value_highlights) ? $this->graph_color_highlight : $paint_color;

				$this->graph_image->draw_line($value_end_left, $middle_of_bar, $value_end_right, $middle_of_bar, $box_color, 2, $title_tooltip);
				$this->graph_image->draw_line($value_end_left, $px_bound_top, $value_end_left, $px_bound_bottom, $this->graph_color_notches, 2, $title_tooltip);
				$this->graph_image->draw_line($value_end_right, $px_bound_top, $value_end_right, $px_bound_bottom, $this->graph_color_notches, 2, $title_tooltip);

				$box_left = $this->graph_left_start + round((pts_math::find_percentile($this->graph_data[$i_o][$i], 0.25) / $this->graph_maximum_value) * ($this->graph_left_end - $this->graph_left_start));
				$box_middle = $this->graph_left_start + round((pts_math::find_percentile($this->graph_data[$i_o][$i], 0.5) / $this->graph_maximum_value) * ($this->graph_left_end - $this->graph_left_start));
				$box_right = $this->graph_left_start + round((pts_math::find_percentile($this->graph_data[$i_o][$i], 0.75) / $this->graph_maximum_value) * ($this->graph_left_end - $this->graph_left_start));

				$this->graph_image->draw_rectangle_with_border($box_left, $px_bound_top, $box_right, $px_bound_bottom, $box_color, $this->graph_color_body_light, $title_tooltip);
				$this->graph_image->draw_line($box_middle, $px_bound_top, $box_middle, $px_bound_bottom, $this->graph_color_notches, 2, $title_tooltip);
			}
		}

		// write a new line along the bottom since the draw_rectangle_with_border above had written on top of it
		$this->graph_image->draw_line($this->graph_left_start, $this->graph_top_end, $this->graph_left_end, $this->graph_top_end, $this->graph_color_notches, 1);
	}
	protected function maximum_graph_value()
	{
		$real_maximum = 0;

		foreach($this->graph_data as &$data_r)
		{
			$real_maximum = max($real_maximum, max(max($data_r)));
		}

		$maximum = (floor(round($real_maximum * 1.285) / $this->graph_attr_marks) + 1) * $this->graph_attr_marks;
		$maximum = round(ceil($maximum / $this->graph_attr_marks), (0 - strlen($maximum) + 2)) * $this->graph_attr_marks;

		return $maximum;
	}
}

?>
