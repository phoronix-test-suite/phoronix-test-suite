<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel
	pts_CandleStickGraph.php: Models a Japanese Candlestick chart

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

class pts_CandleStickGraph extends pts_VerticalBarGraph
{
	public function __construct(&$result_object, &$result_file = null)
	{
		parent::__construct($result_object, $result_file);
	}
	protected function render_graph_candle_sticks()
	{
 		$bar_count = count($this->graph_data_raw);
		$bar_width = floor($this->identifier_width / $bar_count) - ($bar_count * 16);

		for($i_o = 0; $i_o < $bar_count; $i_o++)
		{
			$paint_color = $this->get_paint_color((isset($this->graph_data_title[$i_o]) ? $this->graph_data_title[$i_o] : null));

			for($i = 0; $i < count($this->graph_data_raw[$i_o]); $i++)
			{
				$run_values_r = pts_strings::colon_explode($this->graph_data_raw[$i_o][$i]);

				$start_value = $run_values_r[0];
				$end_value = $run_values_r[(count($run_values_r) - 1)];
				$average_value = array_sum($run_values_r) / count($run_values_r);
				sort($run_values_r);
				$low_value = $run_values_r[0];
				$high_value = $run_values_r[(count($run_values_r) - 1)];

				$px_bound_left = $this->graph_left_start + ($this->identifier_width * $i) + ($bar_width * $i_o) + 8;
				$px_bound_center = $px_bound_left + round($bar_width / 2);
				$px_bound_right = $px_bound_left + $bar_width;

				$top_diff = $this->graph_top_end - $this->graph_top_start;
				$plot_wick_lowest = $this->graph_top_end + 1 - round(($low_value / $this->graph_maximum_value) * $top_diff);
				$plot_wick_highest = $this->graph_top_end + 1 - round(($high_value / $this->graph_maximum_value) * $top_diff);
				$plot_body_start = $this->graph_top_end + 1 - round(($start_value / $this->graph_maximum_value) * $top_diff);
				$plot_body_end = $this->graph_top_end + 1 - round(($end_value / $this->graph_maximum_value) * $top_diff);

				if($start_value > $end_value)
				{
					$body_color = $this->graph_color_body;
					$plot_body_high = $plot_body_start;
					$plot_body_low = $plot_body_end;
				}
				else
				{
					$body_color = $paint_color;
					$plot_body_low = $plot_body_start;
					$plot_body_high = $plot_body_end;
				}

				$this->graph_image->draw_line($px_bound_center, $plot_wick_lowest, $px_bound_center, $plot_wick_highest, $this->graph_color_body_light, 1);
				$this->graph_image->draw_rectangle_with_border($px_bound_left, $plot_body_low, $px_bound_right, $plot_body_high, $body_color, $this->graph_color_body_light);
			}
		}
	}
	protected function render_graph_result()
	{
		if(count($this->graph_data_raw) == 0 || empty($this->graph_data_raw[0]))
		{
			$this->render_graph_bars();
		}
		else
		{
			$this->render_graph_candle_sticks();
		}
	}
}

?>
