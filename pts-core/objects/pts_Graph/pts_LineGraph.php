<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
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

class pts_LineGraph extends pts_CustomGraph
{
	protected $identifier_width = -1;
	protected $minimum_identifier_font = 7;

	public function __construct($title, $sub_title, $y_axis_title)
	{
		parent::__construct($title, $sub_title, $y_axis_title);
		$this->graph_type = "LINE_GRAPH";
		$this->graph_show_key = true;
		$this->graph_background_lines = true;
	}
	protected function render_graph_pre_init()
	{
		// Do some common work to this object
		$identifier_count = (($c = count($this->graph_identifiers)) > 1 ? $c : count($this->graph_data[0])) + 1;
		$this->identifier_width = ($this->graph_left_end - $this->graph_left_start) / $identifier_count;

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
			if(is_array($this->graph_identifiers[$i]) || $this->graph_identifiers[$i] == "Array")
			{
				// TODO: Why is "Array" text getting sent with some line graphs?
				break;
			}

			$px_from_left = $this->graph_left_start + ($this->identifier_width * ($i + 1));

			$this->graph_image->draw_line($px_from_left, $px_from_top_start, $px_from_left, $px_from_top_end, $this->graph_color_notches);

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

		foreach($this->graph_data as &$graph_r)
		{
			if(count($graph_r) != $point_count)
			{
				$varying_lengths = true;
				break;
			}
		}

		for($i_o = 0; $i_o < count($this->graph_data); $i_o++)
		{
			$paint_color = $this->next_paint_color();
			$calculations_r[$paint_color] = array();

			$point_counter = count($this->graph_data[$i_o]);
			$poly_points = array();
			for($i = 0; $i < $point_counter; $i++)
			{
				$value = $this->graph_data[$i_o][$i];
				$value_plot_top = $this->graph_top_end + 1 - ($this->graph_maximum_value == 0 ? 0 : round(($value / $this->graph_maximum_value) * ($this->graph_top_end - $this->graph_top_start)));
				$px_from_left = $this->graph_left_start + ($this->identifier_width * ($i + 1));

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
					array_push($poly_points, array($this->graph_left_start + 1, $value_plot_top));
				}
				else if($identifiers_empty && $i == ($point_counter - 1))
				{
					array_push($poly_points, array($px_from_left, $value_plot_top));
					if($varying_lengths && ($point_counter * 1.1) < $point_count)
					{
						// This plotting ended prematurely
						array_push($poly_points, array($px_from_left, $this->graph_top_end - 1));
					}
					else
					{
						array_push($poly_points, array($this->graph_left_end - 1, $value_plot_top));
					}
				}
				else
				{
					array_push($poly_points, array($px_from_left, $value_plot_top));
				}

				array_push($calculations_r[$paint_color], $value);
			}

			$this->graph_image->draw_poly_line($poly_points, $paint_color, 2);

			foreach($poly_points as $i => $x_y_pair)
			{
				if(!$identifiers_empty && ($point_counter < 6 || $i == 0 || $i == ($point_counter - 1)))
				{
					$this->render_graph_pointer($x_y_pair[0], $x_y_pair[1]);
				}
			}
		}

		$to_display = array();
		$to_display[$this->graph_color_text] = array();

		foreach($calculations_r as $color => &$values)
		{
			$to_display[$color] = array();
		}

		// in_array($this->graph_y_title, array("Percent", "Milliwatts", "Megabytes", "Celsius", "MB/s", "Frames Per Second", "Seconds", "Iterations Per Minute"))
		if(true)
		{
			array_push($to_display[$this->graph_color_text], "Average:");

			foreach($calculations_r as $color => &$values)
			{
				$avg = $this->trim_double(array_sum($values) / count($values), 1);
				array_push($to_display[$color], $avg);
			}
		}
		// in_array($this->graph_y_title, array("Megabytes", "Milliwatts", "Celsius", "MB/s", "Frames Per Second", "Seconds", "Iterations Per Minute"))
		if($this->graph_y_title != "Percent" || $max_value < 100)
		{
			array_push($to_display[$this->graph_color_text], "Peak:");

			foreach($calculations_r as $color => &$values)
			{
				$high = $values[0];
				foreach($values as &$value_check)
				{
					if($value_check > $high)
					{
						$high = $value_check;
					}
				}
				$high = $this->trim_double($high, 1);
				array_push($to_display[$color], $high);
			}
		}
		if($min_value > 0)
		{
			array_push($to_display[$this->graph_color_text], "Low:");

			foreach($calculations_r as $color => &$values)
			{
				$low = $values[0];
				foreach($values as &$value_check)
				{
					if($value_check < $low)
					{
						$low = $value_check;
					}
				}
				$low = $this->trim_double($low, 1);
				array_push($to_display[$color], $low);
			}
		}

		// Do the actual rendering of avg / low / med high identifiers
		$from_left = $this->graph_left_start + 2;

		foreach($to_display as $color_key => &$column)
		{
			$from_top = $this->graph_top_start + 4 + ($color_key != $this->graph_color_text || $this->graph_image->get_renderer() == "SVG" ? 1 : 0);
			$longest_string_width = 0;

			foreach($column as &$write)
			{
				$this->graph_image->write_text_left($write, $this->graph_font, 6, $color_key, $from_left, $from_top, $from_left, $from_top);
				$string_width = $this->text_string_width($write, $this->graph_font, 6);

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
	protected function render_graph_pointer($x, $y)
	{
		$this->graph_image->draw_line($x - 5, $y - 5, $x + 5, $y + 5, $this->graph_color_notches);
		$this->graph_image->draw_line($x + 5, $y - 5, $x - 5, $y + 5, $this->graph_color_notches);
		$this->graph_image->draw_rectangle($x - 2, $y - 2, $x + 3, $y + 3, $this->graph_color_notches);
	}
}

?>
