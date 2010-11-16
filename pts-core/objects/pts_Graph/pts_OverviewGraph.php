<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010, Phoronix Media
	Copyright (C) 2010, Michael Larabel
	pts_OverviewGraph.php: A graping object to create an "overview" / mini graphs of a pts_result_file for pts_Graph

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

class pts_OverviewGraph extends pts_Graph
{
	protected $result_file;

	protected $system_identifiers;
	protected $test_titles;
	protected $graphs_per_row;
	protected $graph_item_width;

	protected $graph_row_height = 100;
	protected $graph_row_count;

	public $skip_graph = false;

	public function __construct(&$result_file)
	{
		$result_object = null;
		parent::__construct($result_object, $result_file);

		// System Identifiers
		if($result_file->is_multi_way_comparison())
		{
			// Multi way comparisons currently render the overview graph as blank
			$this->skip_graph = true;
			return;
		}

		$this->system_identifiers = $result_file->get_system_identifiers();
		if(count($this->system_identifiers) < 2)
		{
			// No point in generating this when there is only one identifier
			$this->skip_graph = true;
			return;
		}

		// Test Titles
		$this->test_titles = $result_file->get_test_titles();
		if(count($this->test_titles) < 3)
		{
			// No point in generating this if there aren't many tests
			$this->skip_graph = true;
			return;
		}

		$this->graph_font_size_identifiers = 6.5;
		$this->graph_attr_width = 1000;

		list($longest_title_width, $longest_title_height) = $this->text_string_dimensions($this->find_longest_string($this->test_titles), $this->graph_font, $this->graph_font_size_identifiers);

		$this->graph_left_start += 20;
		$this->graphs_per_row = floor(($this->graph_attr_width - $this->graph_left_start - $this->graph_left_end_opp) / ($longest_title_width + 2));
		$this->graph_item_width = floor(($this->graph_attr_width - $this->graph_left_start - $this->graph_left_end_opp) / $this->graphs_per_row);
		$this->graph_row_count = ceil(count($this->test_titles) / $this->graphs_per_row);

		$height = $this->graph_top_start + ($this->graph_row_count * ($this->graph_row_height + 15));

		$this->graph_title = $result_file->get_title();
		$this->graph_y_title = null;
		$this->graph_proportion = "HIB";
		$this->graph_background_lines = true;

		$this->update_graph_dimensions($this->graph_attr_width, $height, true);
		$this->result_file = $result_file;

		return true;
	}
	public function doSkipGraph()
	{
		return $this->skip_graph;
	}
	public function renderGraph()
	{
		$this->requestRenderer("SVG");
		$this->graph_data_title = &$this->system_identifiers;
		$this->graph_attr_marks = 6;
		$this->graph_maximum_value = 1.2;
		$l_height = 15;

		if(($key_count = count($this->graph_data_title)) > 8)
		{
			$this->update_graph_dimensions(-1, $this->graph_attr_height + (floor(($key_count - 8) / 4) * 14), true);
		}

		// Do the actual work
		$this->render_graph_init();
		$this->render_graph_key();

		for($i = 0; $i < $this->graph_row_count; $i++)
		{
			$this->render_graph_base($this->graph_left_start, $this->graph_top_start + ($i * ($this->graph_row_height + $l_height)), $this->graph_left_end, $this->graph_top_start + ($i * ($this->graph_row_height + $l_height)) + $this->graph_row_height);
			$this->render_graph_value_ticks($this->graph_left_start, $this->graph_top_start + ($i * ($this->graph_row_height + $l_height)), $this->graph_left_end, $this->graph_top_start + ($i * ($this->graph_row_height + $l_height)) + $this->graph_row_height);
		}

		$row = 0;
		$col = 0;

		$bar_count = count($this->system_identifiers);
		$inter_width = $this->graph_item_width * 0.1;
		$bar_width = floor(($this->graph_item_width - ($inter_width * 2)) / $bar_count);
		$has_graphed_a_bar = false;

		foreach($this->result_file->get_result_objects() as $i => $result_object)
		{
			$top_start = $this->graph_top_start + ($row * ($this->graph_row_height + $l_height));
			$top_end = $this->graph_top_start + ($row * ($this->graph_row_height + $l_height)) + $this->graph_row_height;
			$px_bound_left = $this->graph_left_start + ($this->graph_item_width * ($col % $this->graphs_per_row));
			$px_bound_right = $px_bound_left + $this->graph_item_width;

			$this->graph_image->write_text_center($result_object->test_profile->get_title(), $this->graph_font, $this->graph_font_size_identifiers, $this->graph_color_headers, $px_bound_left, $top_end + 3, $px_bound_right, $top_end + 3, false);

			if($result_object->test_profile->get_display_format() == "BAR_GRAPH")
			{
				$all_values = $result_object->test_result_buffer->get_values();

				switch($result_object->test_profile->get_result_proportion())
				{
					case "HIB":
						$divide_value = max($all_values);
						break;
					case "LIB":
						$divide_value = min($all_values);
						break;
				}

				foreach($result_object->test_result_buffer->get_buffer_items() as $x => $buffer_item)
				{
					$paint_color = $this->get_paint_color($buffer_item->get_result_identifier());

					switch($result_object->test_profile->get_result_proportion())
					{
						case "HIB":
							$value = $buffer_item->get_result_value() / $divide_value;
							break;
						case "LIB":
							$value = $divide_value / $buffer_item->get_result_value();
							break;
					}

					$graph_size = round(($value / $this->graph_maximum_value) * ($top_end - $top_start));
					$value_plot_top = $top_end + 1 - $graph_size;

					$px_left = $px_bound_left + $inter_width + ($bar_width * $x);
					$px_right = $px_left + $bar_width;

					$this->graph_image->draw_rectangle_with_border($px_left, $value_plot_top, $px_right, $top_end, $paint_color, $this->graph_color_body_light, null);
				}

				$has_graphed_a_bar = true;
			}

			if(($i + 1) % $this->graphs_per_row == 0 && $i != 0)
			{
				$this->graph_image->draw_dashed_line($this->graph_left_start + $this->graph_item_width, $top_end, $this->graph_left_end - ($this->graph_attr_width % $this->graph_item_width), $top_end, $this->graph_color_notches, 10, 1, $this->graph_item_width - 1);
				$this->graph_image->draw_line($this->graph_left_start, $top_end, $this->graph_left_end, $top_end, $this->graph_color_notches, 1);

				$row++;
			}
			$col++;
		}

		if($has_graphed_a_bar == false)
		{
			// Don't show an empty overview graph...
			$this->skip_graph = true;
		}


		//$this->render_graph_base($this->graph_left_start, $this->graph_top_start, $this->graph_left_end, $this->graph_top_end);
		$this->render_graph_heading();
		//$this->render_graph_watermark();

		return $this->return_graph_image();
	}
}

?>
