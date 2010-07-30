<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2010, Phoronix Media
	Copyright (C) 2009 - 2010, Michael Larabel
	pts_Chart.php: A charting object for pts_Graph

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

	protected $graph_row_height = 200;
	protected $graph_row_count;

	public $skip_graph = false;

	public function __construct(&$result_file)
	{
		$result_object = null;
		parent::__construct($result_object, $result_file);

		// System Identifiers
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

		$this->graph_font_size_identifiers = 7;

		$width = 1000;
		list($longest_title_width, $longest_title_height) = $this->text_string_dimensions($this->find_longest_string($this->test_titles), $this->graph_font, $this->graph_font_size_identifiers);

		$this->graph_left_start += 20;
		$this->graphs_per_row = floor(($width - $this->graph_left_start - $this->graph_left_end_opp) / ($longest_title_width + 30));
		$this->graph_item_width = floor(($width - $this->graph_left_start - $this->graph_left_end_opp) / $this->graphs_per_row);
		$this->graph_row_count = ceil(count($this->test_titles) / $this->graphs_per_row);

		$height = $this->graph_top_start + ($this->graph_row_count * ($this->graph_row_height + 30));

		$this->graph_title = $result_file->get_title();
		$this->graph_y_title = null;
		$this->graph_proportion = "HIB";
		$this->graph_background_lines = true;

		$this->update_graph_dimensions($width, $height, true);
		$this->result_file = $result_file;

		return true;
	}
	public function renderGraph()
	{
		$this->requestRenderer("SVG");
		$this->graph_data_title = &$this->system_identifiers;
		$this->graph_attr_marks = 6;
		$this->graph_maximum_value = 1.2;

		if(($key_count = count($this->graph_data_title)) > 8)
		{
			$this->update_graph_dimensions(-1, $this->graph_attr_height + (floor(($key_count - 8) / 4) * 14), true);
		}

		// Do the actual work
		$this->render_graph_init();
		$this->render_graph_key();

		for($i = 0; $i < $this->graph_row_count; $i++)
		{
			$this->render_graph_base($this->graph_left_start, $this->graph_top_start + ($i * ($this->graph_row_height + 25)), $this->graph_left_end, $this->graph_top_start + ($i * ($this->graph_row_height + 25)) + $this->graph_row_height);
			$this->render_graph_value_ticks($this->graph_left_start, $this->graph_top_start + ($i * ($this->graph_row_height + 25)), $this->graph_left_end, $this->graph_top_start + ($i * ($this->graph_row_height + 25)) + $this->graph_row_height);
		}

		$row = 0;
		$col = 0;

		$bar_count = count($this->system_identifiers);
		$separator_width = ($a = (8 - (floor($bar_count / 2) * 2))) > 0 ? $a : 0;
		$bar_width = floor(($this->graph_item_width - $separator_width - ($bar_count * $separator_width)) / $bar_count);

		foreach($this->result_file->get_result_objects() as $i => $result_object)
		{
			$top_start = $this->graph_top_start + ($row * ($this->graph_row_height + 25));
			$top_end = $this->graph_top_start + ($row * ($this->graph_row_height + 25)) + $this->graph_row_height;
			$px_bound_left = $this->graph_left_start + ($this->graph_item_width * ($col % $this->graphs_per_row));
			$px_bound_right = $px_bound_left + $this->graph_item_width;

			$this->graph_image->write_text_center($result_object->get_name(), $this->graph_font, $this->graph_font_size_identifiers, $this->graph_color_headers, $px_bound_left, $top_end + 5, $px_bound_right, $top_end + 5, false);

			if($result_object->get_format() == "BAR_GRAPH")
			{
				$all_values = $result_object->get_result_buffer()->get_values();

				switch($result_object->get_proportion())
				{
					case "HIB":
						$divide_value = max($all_values);
						break;
					case "LIB":
						$divide_value = min($all_values);
						break;
				}

				foreach($result_object->get_result_buffer()->get_buffer_items() as $x => $buffer_item)
				{
					$paint_color = $this->get_paint_color($buffer_item->get_result_identifier());

					switch($result_object->get_proportion())
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

					$px_left = $px_bound_left + ($bar_width * $x) + ($separator_width * ($x + 1));
					$px_right = $px_left + $bar_width;

					$this->graph_image->draw_rectangle_with_border($px_left + 1, $value_plot_top, $px_right - 1, $top_end, $paint_color, $this->graph_color_body_light, null);
				}
			}

			if(($i + 1) % $this->graphs_per_row == 0 && $i != 0)
			{
				$this->graph_image->draw_dashed_line($this->graph_left_start + $this->graph_item_width, $top_end, $this->graph_left_end - ($this->graph_attr_width % $this->graph_item_width), $top_end, $this->graph_color_notches, 10, 1, $this->graph_item_width - 1);
				$this->graph_image->draw_line($this->graph_left_start, $top_end, $this->graph_left_end, $top_end, $this->graph_color_notches, 1);

				$row++;
			}
			$col++;
		}


		//$this->render_graph_base($this->graph_left_start, $this->graph_top_start, $this->graph_left_end, $this->graph_top_end);
		$this->render_graph_heading();
		//$this->render_graph_watermark();

		return $this->return_graph_image();
	}
	public function renderChart($file = null)
	{
		// where to start the table values
		$longest_test_title_length = 0;
		$longest_test_title = null;

		foreach($this->result_tests as $result_test)
		{
			if(($len = strlen($result_test[0])) > $longest_test_title_length)
			{
				$longest_test_title = $result_test[0];
				$longest_test_title_length = $len;
			}
		}

		$this->graph_left_start = $this->text_string_width($longest_test_title, $this->graph_font, $this->graph_font_size_identifiers) + 10;
		unset($longest_test_title, $longest_test_title_length);

		$identifier_height = $this->text_string_width($this->longest_system_identifier, $this->graph_font, $this->graph_font_size_identifiers) + 12;

		// Make room for the PTS logo on Phoromatic
		if($this->graph_left_start < 170)
		{
			$this->graph_left_start = 170;
		}

		// $this->graph_maximum_value isn't actually correct to use, but it works
		$extra_heading_height = $this->text_string_height($this->graph_maximum_value, $this->graph_font, $this->graph_font_size_heading) * 2;
		$identifier_height += $extra_heading_height;

		if($identifier_height < 90)
		{
			$identifier_height = 90;
		}

		$table_identifier_width = $this->text_string_height($this->longest_system_identifier, $this->graph_font, $this->graph_font_size_identifiers);
		$table_max_value_width = $this->text_string_width($this->graph_maximum_value, $this->graph_font, $this->graph_font_size_identifiers);

		$table_item_width = max($table_max_value_width, $table_identifier_width) + 6;
		$table_width = $table_item_width * count($this->result_systems);
		$table_line_height = $this->text_string_height($this->graph_maximum_value, $this->graph_font, $this->graph_font_size_identifiers) + 8;
		$table_line_height_half = ($table_line_height / 2);
		$table_height = $table_line_height * count($this->result_tests);
		$table_proper_height = $table_height + $identifier_height;

		$this->graph_attr_width = $table_width + $this->graph_left_start;
		$this->graph_attr_height = $table_proper_height + $table_line_height;

		// Do the actual work
		$this->requestRenderer("SVG");
		$this->render_graph_pre_init();
		$this->render_graph_init(array("cache_font_size" => true));

		// Start drawing
		$this->graph_image->image_copy_merge($this->graph_image->png_image_to_type("http://www.phoronix-test-suite.com/external/pts-logo-160x83.png"), ($this->graph_left_start / 2 - 80), ($identifier_height / 2 - 41.5), 0, 0, 160, 83);

		// Draw the vertical table lines
		$this->graph_image->draw_dashed_line($this->graph_left_start, ($table_proper_height / 2), $this->graph_attr_width, ($table_proper_height / 2), $this->graph_color_body, $table_proper_height, $table_item_width, $table_item_width);

		// Background horizontal
		$this->graph_image->draw_dashed_line(($this->graph_attr_width / 2), $identifier_height, ($this->graph_attr_width / 2), $table_proper_height, $this->graph_color_body_light, $this->graph_attr_width, $table_line_height, $table_line_height);

		// Draw the borders
		$this->graph_image->draw_dashed_line($this->graph_left_start, ($table_proper_height / 2), $this->graph_attr_width, ($table_proper_height / 2), $this->graph_color_border, $table_proper_height, 1, ($table_item_width - 1));
		$this->graph_image->draw_dashed_line(($this->graph_attr_width / 2), $identifier_height, ($this->graph_attr_width / 2), $this->graph_attr_height, $this->graph_color_border, $this->graph_attr_width, 1, ($table_line_height - 1));

		$this->graph_image->draw_rectangle(0, $table_proper_height, $this->graph_attr_width, $this->graph_attr_height, $this->graph_color_headers);
		$this->graph_image->write_text_right($this->graph_watermark_text, $this->graph_font, $this->graph_font_size_identifiers, $this->graph_color_body_text, $this->graph_attr_width - 2, $table_proper_height + $table_line_height_half, $this->graph_attr_width - 2, $table_proper_height + $table_line_height_half, false, $this->graph_watermark_url);

		if($this->graph_attr_width > 300)
		{
			$this->graph_image->write_text_left($this->graph_version, $this->graph_font, $this->graph_font_size_identifiers, $this->graph_color_body_text, 2, $table_proper_height + $table_line_height_half, 2, $table_proper_height + $table_line_height_half, false, "http://www.phoronix-test-suite.com/");
		}

		// Write the test names
		$row = 0;
		foreach($this->result_tests as $i => $test)
		{
			$this->graph_image->write_text_right($test[0], $this->graph_font, $this->graph_font_size_identifiers, $this->graph_color_text, 2, $identifier_height + ($row * $table_line_height) + $table_line_height_half, $this->graph_left_start - 2, $identifier_height + ($row * $table_line_height) + $table_line_height_half, false, "#b-" . $row, $test[1], true);
			$row++;
		}

		// Write the identifiers
		$table_identifier_offset = ($table_item_width / 2) + ($table_identifier_width / 2) - 1;
		foreach($this->result_systems as $i => $system_identifier)
		{
			$link = $system_identifier[1] != null ? "?k=system_logs&u=" . $system_identifier[1] . "&ts=" . $system_identifier[0] : null;

			$this->graph_image->write_text_right($system_identifier[0], $this->graph_font, $this->graph_font_size_identifiers, $this->graph_color_text, $this->graph_left_start + ($i * $table_item_width) + $table_identifier_offset, $identifier_height - 10, $this->graph_left_start + ($i * $table_item_width) + $table_identifier_offset, $identifier_height - 10, 90, $link, null, true);
		}

		// Write the values
		$col = 0;

		foreach($this->result_table as $sys_identifier => &$sys_values)
		{
			//$row = 0;

			foreach($sys_values as $i => &$result_table_value)
			{
				$row = $i; // if using $row, the alignment may be off sometimes
				$hover = array();
				$text_color = $this->graph_color_text;
				$bold = false;

				if($result_table_value->get_standard_deviation_percent() > 0)
				{
					array_push($hover, "STD Dev: " . $result_table_value->get_standard_deviation_percent() . "%");
				}
				if($result_table_value->get_standard_error() != 0)
				{
					array_push($hover, " STD Error: " . $result_table_value->get_standard_error());
				}

				if(defined("PHOROMATIC_TRACKER") && $result_table_value->get_delta() != 0)
				{
					$bold = true;
					if($result_table_value->get_delta() < 0)
					{
						$text_color = $this->graph_color_alert;
					}
					else
					{
						$text_color = $this->graph_color_headers;
					}

					array_push($hover, " Change: " . pts_math::set_precision(100 * $result_table_value->get_delta(), 2) . "%");
				}
				else if($result_table_value->get_highlight() == true)
				{
					$text_color = $this->graph_color_headers;
					$bold = true;
				}

				$this->graph_image->write_text_right($result_table_value->get_value_string(), $this->graph_font, $this->graph_font_size_identifiers, $text_color, $this->graph_left_start + ($col * $table_item_width), $identifier_height + ($row * $table_line_height) + $table_line_height_half, $this->graph_left_start + (($col + 1) * $table_item_width ), $identifier_height + (($row + 1) * $table_line_height) + $table_line_height_half, false, null, implode("; ", $hover), $bold);
				//$row++;
			}
			$col++;
		}

		if($row == 0 && $this->result_object_index != -1 && !is_array($this->result_object_index))
		{
			// No results were to be reported, so don't report the individualized graphs
			$this->graph_image->destroy_image();
			$this->saveGraphToFile($file);
			return $this->return_graph_image();
		}

		if(defined("PHOROMATIC_TRACKER"))
		{
			$last_identifier = null;
			$last_changed_col = 0;
			$show_keys = array_keys($this->result_table);
			array_push($show_keys, "Temp: Temp");

			foreach($show_keys as $current_col => $system_identifier)
			{
				$identifier = array_map("trim", explode(':', $system_identifier));

				if($identifier[0] != $last_identifier)
				{
					if($current_col == $last_changed_col)
					{
						$last_identifier = $identifier[0];
						continue;
					}

					$paint_color = $this->get_paint_color($identifier[0]);

					$this->graph_image->draw_rectangle_with_border(($this->graph_left_start + ($last_changed_col * $table_item_width)), 0, ($this->graph_left_start + ($last_changed_col * $table_item_width)) + ($table_item_width * ($current_col - $last_changed_col)), $extra_heading_height, $paint_color, $this->graph_color_border);

					if($identifier[0] != "Temp")
					{
						$this->graph_image->draw_line(($this->graph_left_start + ($current_col * $table_item_width) + 1), 1, ($this->graph_left_start + ($current_col * $table_item_width) + 1), $this->graph_attr_height, $paint_color, 1);
					}

					$this->graph_image->write_text_center($last_identifier, $this->graph_font, $this->graph_font_size_axis_heading, $this->graph_color_background, $this->graph_left_start + ($last_changed_col * $table_item_width), 4, $this->graph_left_start + ($current_col * $table_item_width), $extra_heading_height, false, null, null, true);

					$last_identifier = $identifier[0];
					$last_changed_col = $current_col;
				}
			}
		}

		$this->graph_image->draw_rectangle_border(1, 1, $this->graph_attr_width, $this->graph_attr_height, $this->graph_color_border);

		$this->saveGraphToFile($file);
		return $this->return_graph_image();
	}
}

?>
