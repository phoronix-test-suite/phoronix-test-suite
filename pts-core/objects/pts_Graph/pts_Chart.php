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

class pts_Chart extends pts_Graph
{
	protected $result_tests;
	protected $result_table;
	protected $result_count;
	protected $result_systems;

	public function __construct()
	{
		parent::__construct();
		$this->graph_attr_big_border = true;
	}
	public function loadResultFile(&$result_file)
	{
		list($this->result_tests, $this->result_systems, $this->result_table, $this->result_count, $this->graph_maximum_value) = $result_file->get_result_table();
	}
	public function renderChart($file = null)
	{
		// where to start the table values
		$this->graph_left_start = $this->text_string_width($this->max_value_in_array($this->result_tests), $this->graph_font, $this->graph_font_size_identifiers);
		$identifier_height = $this->text_string_width($this->max_value_in_array($this->result_systems), $this->graph_font, $this->graph_font_size_identifiers);

		if(defined("PHOROMATIC_TRACKER"))
		{
			// $this->graph_maximum_value isn't actually correct to use, but it works
			$extra_heading_height = $this->text_string_height($this->graph_maximum_value, $this->graph_font, $this->graph_font_size_heading) * 2;
			$identifier_height += $extra_heading_height;

		}

		$table_identifier_width = $this->text_string_height($this->max_value_in_array($this->result_systems), $this->graph_font, $this->graph_font_size_identifiers);
		$table_max_value_width = $this->text_string_width($this->graph_maximum_value, $this->graph_font, $this->graph_font_size_identifiers);
		$table_item_width = ($table_max_value_width > $table_identifier_width ? $table_max_value_width : $table_identifier_width) + 4;
		$table_width = $table_item_width * count($this->result_systems);
		$table_line_height = $this->text_string_height($this->graph_maximum_value, $this->graph_font, $this->graph_font_size_identifiers) + 6;
		$table_line_height_half = ($table_line_height / 2);
		$table_height = $table_line_height * $this->result_count;

		$this->graph_attr_width = $table_width + $this->graph_left_start;
		$this->graph_attr_height = $table_height + $identifier_height;

		// Do the actual work
		//$this->requestRenderer("SVG");
		$this->render_graph_pre_init();
		$this->render_graph_init(array("cache_font_size" => true));

		// Start drawing

		// Draw the vertical table lines
		$this->graph_image->draw_dashed_line($this->graph_left_start, ($this->graph_attr_height / 2), $this->graph_attr_width, ($this->graph_attr_height / 2), $this->graph_color_body, $this->graph_attr_height, $table_item_width, $table_item_width);

		// Background horizontal
		$this->graph_image->draw_dashed_line(($this->graph_attr_width / 2), $identifier_height, ($this->graph_attr_width / 2), $this->graph_attr_height, $this->graph_color_body_light, $this->graph_attr_width, $table_line_height, $table_line_height);

		// Draw the borders
		$this->graph_image->draw_dashed_line($this->graph_left_start, ($this->graph_attr_height / 2), $this->graph_attr_width, ($this->graph_attr_height / 2), $this->graph_color_border, $this->graph_attr_height, 1, ($table_item_width - 1));
		$this->graph_image->draw_dashed_line(($this->graph_attr_width / 2), $identifier_height, ($this->graph_attr_width / 2), $this->graph_attr_height, $this->graph_color_border, $this->graph_attr_width, 1, ($table_line_height - 1));

		// Write the test names
		foreach($this->result_tests as $i => $test_title)
		{
			$this->graph_image->write_text_right($test_title, $this->graph_font, $this->graph_font_size_identifiers, $this->graph_color_text, 2, $identifier_height + ($i * $table_line_height) + $table_line_height_half, $this->graph_left_start - 2, $identifier_height + ($i * $table_line_height) + $table_line_height_half, false, "#b-" . $i);
		}

		// Write the identifiers
		$table_identifier_offset = ($table_item_width / 2) + ($table_identifier_width / 2) - 1;
		foreach($this->result_systems as $i => $system_identifier)
		{
			$this->graph_image->write_text_right($system_identifier, $this->graph_font, $this->graph_font_size_identifiers, $this->graph_color_text, $this->graph_left_start + ($i * $table_item_width) + $table_identifier_offset, $identifier_height - 10, $this->graph_left_start + ($i * $table_item_width) + $table_identifier_offset, $identifier_height - 10, 90);
		}

		// Write the values
		$col = 0;

		foreach($this->result_table as $sys_identifier => &$sys_values)
		{
			foreach($sys_values as $i => &$value)
			{
				$this->graph_image->write_text_right($value, $this->graph_font, $this->graph_font_size_identifiers, $this->graph_color_text, $this->graph_left_start + ($col * $table_item_width), $identifier_height + ($i * $table_line_height) + $table_line_height_half, $this->graph_left_start + (($col + 1) * $table_item_width ), $identifier_height + (($i + 1) * $table_line_height) + $table_line_height_half, false, null);

			}
			$col++;
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

					$this->graph_image->draw_rectangle_with_border(($this->graph_left_start + ($last_changed_col * $table_item_width)), 0, ($this->graph_left_start + ($last_changed_col * $table_item_width)) + ($table_item_width * ($current_col - $last_changed_col)), $extra_heading_height, $this->next_paint_color(), $this->graph_color_border);
					$this->graph_image->write_text_center($last_identifier, $this->graph_font, $this->graph_font_size_heading, $this->graph_color_background, $this->graph_left_start + ($last_changed_col * $table_item_width), 4, $this->graph_left_start + ($current_col * $table_item_width), $extra_heading_height);

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
