<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel
	pts_LineGraph.php: The line graph object that extends pts_Graph.php.

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

class pts_LineGraph extends pts_Graph
{
	protected $identifier_width = -1;
	protected $minimum_identifier_font = 7;
	protected $show_select_identifiers = null;

	public function __construct(&$result_object)
	{
		parent::__construct($result_object);
		$this->graph_type = "LINE_GRAPH";
		$this->graph_show_key = true;
		$this->graph_background_lines = true;
	}
	protected function render_graph_pre_init()
	{
		// Do some common work to this object
		$graph_identifiers_count = count($this->graph_identifiers);
		$identifier_count = $graph_identifiers_count > 1 ? $graph_identifiers_count : count($this->graph_data[0]);
		$this->identifier_width = ($this->graph_left_end - $this->graph_left_start) / ($identifier_count + 1);

		$longest_string = $this->find_longest_string($this->graph_identifiers);
		$this->graph_font_size_identifiers = $this->text_size_bounds($longest_string, $this->graph_font, $this->graph_font_size_identifiers, $this->minimum_identifier_font, $this->identifier_width - 4);

		if($this->graph_font_size_identifiers <= $this->minimum_identifier_font)
		{
			list($text_width, $text_height) = $this->text_string_dimensions($longest_string, $this->graph_font, $this->minimum_identifier_font + 2);
			$this->update_graph_dimensions($this->graph_attr_width, $this->graph_attr_height + $text_width);

			if(($text_height + 4) > $this->identifier_width && $graph_identifiers_count > 3)
			{
				// Show the identifiers as frequently as they will fit
				$this->show_select_identifiers = ceil(($text_height + 4) / $this->identifier_width);
			}
		}
	}
	protected function render_graph_identifiers()
	{
		$px_from_top_end = $this->graph_top_end + 5;

		if(!is_array($this->graph_identifiers))
		{
			return;
		}

		$this->graph_image->draw_dashed_line($this->graph_left_start + $this->identifier_width, $this->graph_top_end, $this->graph_left_end, $this->graph_top_end, $this->graph_color_notches, 10, 1, $this->identifier_width - 1);

		foreach(array_keys($this->graph_identifiers) as $i)
		{
			if(is_array($this->graph_identifiers[$i]))
			{
				// || $this->graph_identifiers[$i] == "Array"
				// TODO: Why is "Array" text getting sent with some line graphs?
				break;
			}

			if($this->show_select_identifiers != null && ($i % $this->show_select_identifiers) != 0)
			{
				// $show_select_identifiers contains the value of how frequently to display identifiers
				continue;
			}

			$px_from_left = $this->graph_left_start + ($this->identifier_width * ($i + 1));

			if($this->graph_font_size_identifiers <= $this->minimum_identifier_font)
			{
				$this->graph_image->write_text_left($this->graph_identifiers[$i], $this->graph_font, 9, $this->graph_color_headers, $px_from_left, $px_from_top_end + 2, $px_from_left, $px_from_top_end + 2, true);
			}
			else
			{
				$this->graph_image->write_text_center($this->graph_identifiers[$i], $this->graph_font, $this->graph_font_size_identifiers, $this->graph_color_headers, $px_from_left, $px_from_top_end + 2, $px_from_left, $px_from_top_end + 2);
			}
		}
	}
	protected function renderGraphLines()
	{
		$identifiers_empty = count($this->graph_identifiers) == 0;
		$calculations_r = array();
		$point_count = count($this->graph_data[0]);
		$varying_lengths = false;
		$min_value = $this->graph_data[0][0];
		$max_value = $this->graph_data[0][0];
		$prev_value = $this->graph_data[0][0];

		foreach($this->graph_data as &$graph_r)
		{
			if(count($graph_r) != $point_count)
			{
				$varying_lengths = true;
				break;
			}
		}

		foreach(array_keys($this->graph_data) as $i_o)
		{
			$paint_color = $this->get_paint_color($this->graph_data_title[$i_o]);
			$calculations_r[$paint_color] = array();

			$point_counter = count($this->graph_data[$i_o]);
			$regression_plots = array();
			$poly_points = array();
			$has_hit_non_zero = false;

			for($i = 0; $i < $point_counter; $i++)
			{
				$value = $this->graph_data[$i_o][$i];
				$data_string = $this->graph_identifiers[$i] . ": " . $value;

				if($value == 0 && !$has_hit_non_zero)
				{
					if(defined("PHOROMATIC_TRACKER"))
					{
						continue;
					}

					$has_hit_non_zero = true;
				}

				$value_plot_top = $this->graph_top_end + 1 - ($this->graph_maximum_value == 0 ? 0 : round(($value / $this->graph_maximum_value) * ($this->graph_top_end - $this->graph_top_start)));
				$px_from_left = round($this->graph_left_start + ($this->identifier_width * ($i + 1)));

				if(($i == ($point_counter - 1)) && $value == 0)
				{
					break;
				}

				if($value > $max_value)
				{
					$max_value = $value;
				}
				else if($value < $min_value)
				{
					$min_value = $value;
				}

				if($px_from_left > $this->graph_left_end)
				{
					$px_from_left = $this->graph_left_end - 1;
				}

				if($value_plot_top >= $this->graph_top_end)
				{
					$value_plot_top = $this->graph_top_end - 1;
				}

				
				if($identifiers_empty && $i == 0)
				{
					array_push($poly_points, array($this->graph_left_start + 1, $value_plot_top, $data_string));
				}
				else if($identifiers_empty && $i == ($point_counter - 1))
				{
					array_push($poly_points, array($px_from_left, $value_plot_top, $data_string));
					if($varying_lengths && ($point_counter * 1.1) < $point_count)
					{
						// This plotting ended prematurely
					//	array_push($poly_points, array($px_from_left, $this->graph_top_end - 1, null));
						$this->graph_image->draw_poly_line(array(array($px_from_left, $value_plot_top, $data_string), array($px_from_left, $this->graph_top_end - 1, null)), $paint_color, 2);
					}
					else
					{
						array_push($poly_points, array($this->graph_left_end - 1, $value_plot_top, null));
					}
				}
				else
				{
					array_push($poly_points, array($px_from_left, $value_plot_top, $data_string));
				}

				if($this->regression_marker_threshold > 0 && $i > 0 && abs(1 - ($value / $prev_value)) > $this->regression_marker_threshold)
				{
					$regression_plots[($i - 1)] = $prev_identifier . ": " . $prev_value;
					$regression_plots[$i] = $this->graph_identifiers[$i] . ": " . $value;
				}

				//array_push($poly_tips, array($value, $this->graph_identifiers[$i]));
				array_push($calculations_r[$paint_color], $value);
				$prev_identifier = $this->graph_identifiers[$i];
				$prev_value = $value;
			}

			$this->graph_image->draw_poly_line($poly_points, $paint_color, 2);

			$poly_points_count = count($poly_points);
			foreach($poly_points as $i => $x_y_pair)
			{
				if($x_y_pair[0] < ($this->graph_left_start + 2) || $x_y_pair[0] > ($this->graph_left_end - 2))
				{
					// Don't draw anything on the left or right hand edges
					continue;
				}

				if(true || !$identifiers_empty) // TODO: determine whether to kill this check
				{
					if(isset($regression_plots[$i]) && $i > 0)
					{
						$this->graph_image->draw_line($x_y_pair[0], $x_y_pair[1] + 6, $x_y_pair[0], $x_y_pair[1] - 6, $this->graph_color_alert, 4, $regression_plots[$i]);
					}

					$this->graph_image->draw_ellipse($x_y_pair[0], $x_y_pair[1], 7, 7, $paint_color, $paint_color, 1, !($point_counter < 6 || $i == 0 || $i == ($poly_points_count  - 1)), $x_y_pair[2]);
				}
			}
		}

		$to_display = array();
		$to_display[$this->graph_color_text] = array();

		foreach(array_keys($calculations_r) as $color)
		{
			$to_display[$color] = array();
		}

		// in_array($this->graph_y_title, array("Percent", "Milliwatts", "Megabytes", "Celsius", "MB/s", "Frames Per Second", "Seconds", "Iterations Per Minute"))
		if(count($calculations_r) > 0)
		{
			array_push($to_display[$this->graph_color_text], "Average:");

			foreach($calculations_r as $color => &$values)
			{
				$avg = pts_math::set_precision(array_sum($values) / count($values), 1);
				array_push($to_display[$color], $avg);
			}
		}
		// in_array($this->graph_y_title, array("Megabytes", "Milliwatts", "Celsius", "MB/s", "Frames Per Second", "Seconds", "Iterations Per Minute"))
		if($this->graph_y_title != "Percent" || $max_value < 100 && $max_value != $min_value)
		{
			array_push($to_display[$this->graph_color_text], "Peak:");

			foreach($calculations_r as $color => &$values)
			{
				array_push($to_display[$color], pts_math::set_precision(max($values), 1));
			}
		}
		if($min_value > 0 && $max_value != $min_value)
		{
			array_push($to_display[$this->graph_color_text], "Low:");

			foreach($calculations_r as $color => &$values)
			{
				array_push($to_display[$color], pts_math::set_precision(min($values), 1));
			}
		}
		if($point_counter > 9 && !in_array($this->graph_y_title, array("Percent")))
		{
			array_push($to_display[$this->graph_color_text], "Last:");

			foreach($calculations_r as $color => &$values)
			{
				array_push($to_display[$color], pts_math::set_precision($values[count($values) - 1], 1));
			}
		}

		// Do the actual rendering of avg / low / med high identifiers
		$from_left = $this->graph_left_start + 6;

		foreach($to_display as $color_key => &$column)
		{
			$from_top = $this->graph_top_start + 7 + ($color_key != $this->graph_color_text || $this->graph_image->get_renderer() == "SVG" ? 1 : 0);
			$longest_string_width = 0;

			foreach($column as &$write)
			{
				$this->graph_image->write_text_left($write, $this->graph_font, 6.5, $color_key, $from_left, $from_top, $from_left, $from_top);
				$string_width = $this->text_string_width($write, $this->graph_font, 6.5);

				if($string_width > $longest_string_width)
				{
					$longest_string_width = $string_width;
				}

				$from_top += 10;
			}

			$from_left += $longest_string_width + 3;						
		}
	}
	protected function render_graph_result()
	{
		$this->renderGraphLines();
	}
}

?>
