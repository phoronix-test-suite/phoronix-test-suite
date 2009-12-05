<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
	pts_BarGraph.php: The bar graph object that extends pts_Graph.php.

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

class pts_BarGraph extends pts_CustomGraph
{
	protected $identifier_width = -1;
	protected $minimum_identifier_font = 6;

	public function __construct($title, $sub_title, $y_axis_title)
	{
		parent::__construct($title, $sub_title, $y_axis_title);
		$this->graph_type = "BAR_GRAPH";
	}
	protected function render_graph_pre_init()
	{
		// Do some common work to this object
		$identifier_count = count($this->graph_identifiers);
		$this->identifier_width = floor(($this->graph_left_end - $this->graph_left_start) / $identifier_count);

		$longest_string = $this->find_longest_string($this->graph_identifiers);
		$width = $this->identifier_width - 4;
		$this->graph_font_size_identifiers = $this->text_size_bounds($longest_string, $this->graph_font, $this->graph_font_size_identifiers, $this->minimum_identifier_font, $width);

		if($this->graph_font_size_identifiers <= $this->minimum_identifier_font)
		{
			$this->update_graph_dimensions($this->graph_attr_width, $this->graph_attr_height + $this->text_string_width($longest_string, $this->graph_font, 9));
		}
	}
	protected function render_graph_identifiers()
	{
		$px_from_top_start = $this->graph_top_end - 5;
		$px_from_top_end = $this->graph_top_end + 5;

		for($i = 0; $i < count($this->graph_identifiers); $i++)
		{
			$px_bound_left = $this->graph_left_start + ($this->identifier_width * $i);
			$px_bound_right = $px_bound_left + $this->identifier_width;

			if($i == 0)
			{
				$this->graph_image->draw_line($px_bound_left, $px_from_top_start, $px_bound_left, $px_from_top_end, $this->graph_color_notches);
			}

			if($i == (count($this->graph_identifiers) - 1) && $px_bound_right != $this->graph_left_end)
			{
				$px_bound_right = $this->graph_left_end;
			}

			$this->graph_image->draw_line($px_bound_right, $px_from_top_start, $px_bound_right, $px_from_top_end, $this->graph_color_notches);

			if($this->graph_font_size_identifiers <= $this->minimum_identifier_font)
			{
				$this->graph_image->write_text_left($this->graph_identifiers[$i], $this->graph_font, 9, $this->graph_color_headers, $px_bound_left + ceil($this->identifier_width / 2), $px_from_top_end, $px_bound_left + ceil($this->identifier_width / 2), $px_from_top_end, true);
			}
			else
			{
				$this->graph_image->write_text_center($this->graph_identifiers[$i], $this->graph_font, $this->graph_font_size_identifiers, $this->graph_color_headers, $px_bound_left, $px_from_top_end - 3, $px_bound_right, $px_from_top_end - 3, false, true);
			}
		}
	}
	protected function render_graph_bars()
	{
		$bar_count = count($this->graph_data);
		$separator_width = ($a = (8 - (floor($bar_count / 2) * 2))) > 0 ? $a : 0;
		$bar_width = floor(($this->identifier_width - $separator_width - ($bar_count * $separator_width)) / $bar_count);

		for($i_o = 0; $i_o < $bar_count; $i_o++)
		{
			$paint_color = $this->next_paint_color();

			for($i = 0; $i < count($this->graph_data[$i_o]); $i++)
			{
				$value = $this->trim_double($this->graph_data[$i_o][$i], 2);
				$graph_size = round(($value / $this->graph_maximum_value) * ($this->graph_top_end - $this->graph_top_start));
				$value_plot_top = $this->graph_top_end + 1 - $graph_size;

				$px_bound_left = $this->graph_left_start + ($this->identifier_width * $i) + ($bar_width * $i_o) + ($separator_width * ($i_o + 1));
				$px_bound_right = $px_bound_left + $bar_width;

				if($value_plot_top < 1)
				{
					$value_plot_top = 1;
				}

				$this->graph_image->draw_rectangle_border($px_bound_left, $value_plot_top - 1, $px_bound_right, $this->graph_top_end - 1, $this->graph_color_body_light);
				$this->graph_image->draw_rectangle($px_bound_left + 1, $value_plot_top, $px_bound_right - 1, $this->graph_top_end - 1, $paint_color);

				if(($px_bound_right - $px_bound_left) < 15)
				{
					// The bars are too skinny to be able to plot anything on them
					continue;
				}

				if($graph_size > 18)
				{
					$this->graph_image->write_text_center($this->graph_data[$i_o][$i], $this->graph_font, $this->graph_font_size_bars, $this->graph_color_body_text, $px_bound_left - 2, $value_plot_top + 2, $px_bound_right + 2, $value_plot_top + 2);
				}
				else if($graph_size > 10)
				{
					$this->graph_image->write_text_center($this->graph_data[$i_o][$i], $this->graph_font, ceil($this->graph_font_size_bars * 0.6), $this->graph_color_body_text, $px_bound_left, $value_plot_top  + 1, $px_bound_right, $value_plot_top + 1);
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
