<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel
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

class pts_HorizontalBarGraph extends pts_Graph
{
	protected $identifier_height = -1;

	public function __construct(&$result_object, &$result_file = null)
	{
		parent::__construct($result_object, $result_file);

		if($result_file != null && $result_file instanceOf pts_result_file)
		{
			$this->is_multi_way_comparison = $result_file->is_multi_way_comparison();
		}

		$this->iveland_view = true;
		$this->graph_orientation = "HORIZONTAL";
	}
	protected function render_graph_pre_init()
	{
		// Do some common work to this object
		$identifier_count = count($this->graph_identifiers);
		$this->identifier_height = floor(($this->graph_top_end - $this->graph_top_start) / $identifier_count);
	}
	protected function render_graph_identifiers()
	{
		$px_from_top_end = $this->graph_top_end + 5;

		$this->graph_image->draw_dashed_line($this->graph_left_start, $this->graph_top_start + $this->identifier_height, $this->graph_left_start, $this->graph_top_end - ($this->graph_attr_height % $this->identifier_height), $this->graph_color_notches, 10, 1, $this->identifier_height - 1);
		$multi_way = $this->is_multi_way_comparison && count($this->graph_data) > 1;

		foreach(array_keys($this->graph_identifiers) as $i)
		{
			$middle_of_vert = $this->graph_top_start + ($this->identifier_height * ($i + 1)) - ($this->identifier_height * 0.5);

			if($multi_way)
			{
				$font_size = $this->text_size_bounds($this->graph_identifiers[$i], $this->graph_font, $this->graph_font_size_identifiers, 4, ($this->identifier_height * count($this->graph_data[$i])));
				$this->graph_image->write_text_center($this->graph_identifiers[$i], $this->graph_font, $font_size, $this->graph_color_headers, 20, $middle_of_vert, 20, $middle_of_vert, true);
			}
			else
			{
				$this->graph_image->write_text_right($this->graph_identifiers[$i], $this->graph_font, $this->graph_font_size_identifiers, $this->graph_color_headers, ($this->graph_left_start - 5), $middle_of_vert, ($this->graph_left_start - 5), $middle_of_vert);
			}
		}
	}
	protected function render_graph_bars()
	{
		$bar_count = count($this->graph_data);
		$separator_height = ($a = (8 - (floor($bar_count / 2) * 2))) > 0 ? $a : 0;
		$bar_height = floor(($this->identifier_height - $separator_height - ($bar_count * $separator_height)) / $bar_count);
		$highlight_bar = PTS_MODE == "CLIENT" ? pts_strings::comma_explode(pts_client::read_env("GRAPH_HIGHLIGHT")) : false;
		$multi_way = $this->is_multi_way_comparison && count($this->graph_data) > 1;

		for($i_o = 0; $i_o < $bar_count; $i_o++)
		{
			$paint_color = $this->get_paint_color((isset($this->graph_data_title[$i_o]) ? $this->graph_data_title[$i_o] : null));

			foreach(array_keys($this->graph_data[$i_o]) as $i)
			{
				$value = pts_math::set_precision($this->graph_data[$i_o][$i], 2);
				$graph_size = round(($value / $this->graph_maximum_value) * ($this->graph_left_end - $this->graph_left_start));
				$value_end_left = $this->graph_left_start + $graph_size;

				$px_bound_top = $this->graph_top_start + ($this->identifier_height * $i) + ($bar_height * $i_o) + ($separator_height * ($i_o + 1));
				$px_bound_bottom = $px_bound_top + $bar_height;
				$middle_of_bar = $px_bound_top + ($bar_height / 2);

				if($value_end_left < 1)
				{
					$value_end_left = 1;
				}

				$title_tooltip = $this->graph_identifiers[$i] . ": " . $value;
				$std_error = isset($this->graph_data_raw[$i_o][$i]) ? pts_math::standard_error(pts_strings::colon_explode($this->graph_data_raw[$i_o][$i])) : 0;

				$this->graph_image->draw_rectangle_with_border($this->graph_left_start, $px_bound_top, $value_end_left, $px_bound_bottom, in_array($this->graph_identifiers[$i], $highlight_bar) ? $this->graph_color_alert : $paint_color, $this->graph_color_body_light, $title_tooltip);

				if($std_error > 0.01)
				{
					$std_error_height = 8;
					$std_error_rel_size = round(($std_error / $this->graph_maximum_value) * ($this->graph_left_end - $this->graph_left_start));

					if($std_error_rel_size > 4)
					{
						$this->graph_image->draw_line(($value_end_left - $std_error_rel_size), $px_bound_top, ($value_end_left - $std_error_rel_size), $px_bound_top + $std_error_height, $this->graph_color_notches, 1);
						$this->graph_image->draw_line(($value_end_left + $std_error_rel_size), $px_bound_top, ($value_end_left + $std_error_rel_size), $px_bound_top + $std_error_height, $this->graph_color_notches, 1);
						$this->graph_image->draw_line(($value_end_left - $std_error_rel_size), $px_bound_top, ($value_end_left + $std_error_rel_size), $px_bound_top, $this->graph_color_notches, 1);
					}

					$bar_offset_34 = $middle_of_bar + ($multi_way ? 0 : ($bar_height / 5) + 1);
					$this->graph_image->write_text_right("SE +/- " . pts_math::set_precision($std_error, 2), $this->graph_font, $this->graph_font_size_identifiers - 2, $this->graph_color_text, ($this->graph_left_start - 5), $bar_offset_34, ($this->graph_left_start - 5), $bar_offset_34);
				}

				if($this->text_string_width($value, $this->graph_font, $this->graph_font_size_identifiers) < $graph_size)
				{
					$this->graph_image->write_text_right($value, $this->graph_font, $this->graph_font_size_identifiers, $this->graph_color_body_text, $value_end_left - 6, $middle_of_bar, $value_end_left - 6, $middle_of_bar);
				}
				else if($value > 0)
				{
					// Write it in front of the result
					$this->graph_image->write_text_left($value, $this->graph_font, $this->graph_font_size_identifiers, $this->graph_color_text, $value_end_left + 6, $middle_of_bar, $value_end_left + 6, $middle_of_bar);
				}
			}
		}

		// write a new line along the bottom since the draw_rectangle_with_border above had written on top of it
		$this->graph_image->draw_line($this->graph_left_start, $this->graph_top_end, $this->graph_left_end, $this->graph_top_end, $this->graph_color_notches, 1);
	}
	protected function render_graph_result()
	{
		$this->render_graph_bars();
	}
}

?>
