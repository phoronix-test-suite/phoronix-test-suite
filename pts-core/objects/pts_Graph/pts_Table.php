<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2010, Phoronix Media
	Copyright (C) 2009 - 2010, Michael Larabel
	pts_Table.php: A charting table object for pts_Graph

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

class pts_Table extends pts_Graph
{
	protected $rows;
	protected $columns;
	protected $table_data;
	protected $longest_column_identifier;
	protected $longest_row_identifier;
	protected $result_object_index = -1;
	protected $column_heading_vertical = true;

	public function __construct($rows, $columns, $table_data)
	{
		parent::__construct();
		$this->graph_attr_big_border = false;
		$this->rows = $rows;
		$this->columns = $columns;
		$this->table_data = $table_data;

		// Do some calculations
		$this->longest_column_identifier = $this->find_longest_string($this->columns);
		$this->longest_row_identifier = $this->find_longest_string($this->rows);
		$this->graph_maximum_value = $this->find_longest_string($this->table_data);
	}
	public static function CreateFromResultFile(&$result_file, $system_id_keys = null, $result_object_index = -1)
	{
		list($rows, $columns, $table_data) = $result_file->get_result_table($system_id_keys, $result_object_index);
		$table = new pts_Table($rows, $columns, $table_data);
		$table->result_object_index = $result_object_index;

		// where to start the table values
		$table->longest_row_identifier = null;
		$longest_row_title_length = 0;
		foreach($table->rows as $result_test)
		{
			if(($len = strlen($result_test[0])) > $longest_row_title_length)
			{
				$table->longest_row_identifier = $result_test[0];
				$longest_row_title_length = $len;
			}
		}

		return $table;
	}
	public static function CreateFromResultFile_Systems(&$result_file)
	{
		$columns = $result_file->get_system_identifiers();
		$rows = array();
		$table_data = array();

		foreach($result_file->get_system_hardware() as $info_string)
		{
			$col = array();
			foreach(explode(', ', $info_string) as $component)
			{
				$c_pos = strpos($component, ': ');

				if($c_pos !== false)
				{
					$index = substr($component, 0, $c_pos);
					$value = substr($component, ($c_pos + 2));

					if(isset($rows[$index]) == false)
					{
						$rows[$index] = $index;
					}
					array_push($col, $value);				
				}
			}
			array_push($table_data, $col);
		}

		$table = new pts_Table($rows, $columns, $table_data);
		$table->graph_font_size_identifiers *= 0.8;
		$table->column_heading_vertical = false;

		return $table;
	}
	public function renderChart($file = null)
	{
		// Needs to be at least 170px wide for the PTS logo
		$this->graph_left_start = max(170, $this->text_string_width($this->longest_row_identifier, $this->graph_font, $this->graph_font_size_identifiers) + 10);

		if($this->column_heading_vertical)
		{
			$identifier_height = $this->text_string_width($this->longest_column_identifier, $this->graph_font, $this->graph_font_size_identifiers) + 12;
			$table_identifier_width = $this->text_string_height($this->longest_column_identifier, $this->graph_font, $this->graph_font_size_identifiers);
		}
		else
		{
			$identifier_height = $this->text_string_height($this->longest_column_identifier, $this->graph_font, $this->graph_font_size_identifiers) + 8;
			$table_identifier_width = $this->text_string_width($this->longest_column_identifier, $this->graph_font, $this->graph_font_size_identifiers);
		}

		// $this->graph_maximum_value isn't actually correct to use, but it works
		$extra_heading_height = $this->text_string_height($this->graph_maximum_value, $this->graph_font, $this->graph_font_size_heading) * 2;

		// Needs to be at least 90px tall for the PTS logo
		$identifier_height = max($identifier_height, 90);

		$table_max_value_width = $this->text_string_width($this->graph_maximum_value, $this->graph_font, $this->graph_font_size_identifiers);

		$table_item_width = max($table_max_value_width, $table_identifier_width) + 8;
		$table_width = $table_item_width * count($this->columns);
		$table_line_height = $this->text_string_height($this->graph_maximum_value, $this->graph_font, $this->graph_font_size_identifiers) + 8;
		$table_line_height_half = ($table_line_height / 2);
		$table_height = $table_line_height * count($this->rows);

		// The identifer_height needs to be at least 90px for the PTS logo
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
		foreach($this->rows as $i => $row_string)
		{
			if(is_array($row_string))
			{
				$hover = $row_string[1];
				$row_string = $row_string[0];
			}
			else
			{
				$hover = null;
			}

			$this->graph_image->write_text_right($row_string, $this->graph_font, $this->graph_font_size_identifiers, $this->graph_color_text, 2, $identifier_height + ($row * $table_line_height) + $table_line_height_half, $this->graph_left_start - 2, $identifier_height + ($row * $table_line_height) + $table_line_height_half, false, "#b-" . $row, $hover, true);
			$row++;
		}

		// Write the identifiers
		$table_identifier_offset = ($table_item_width / 2) + ($table_identifier_width / 2) - 1;
		foreach($this->columns as $i => $col_string)
		{
			if(is_array($col_string))
			{
				$link = $col_string[1] != null ? "?k=system_logs&u=" . $col_string[1] . "&ts=" . $col_string[0] : null;
				$col_string = $col_string[0];
			}
			else
			{
				$link = null;
			}

			if($this->column_heading_vertical)
			{
				$this->graph_image->write_text_right($col_string, $this->graph_font, $this->graph_font_size_identifiers, $this->graph_color_text, $this->graph_left_start + ($i * $table_item_width) + $table_identifier_offset, $identifier_height - 10, $this->graph_left_start + ($i * $table_item_width) + $table_identifier_offset, $identifier_height - 10, 90, $link, null, true);
			}
			else
			{
				$this->graph_image->write_text_center($col_string, $this->graph_font, $this->graph_font_size_identifiers, $this->graph_color_text, $this->graph_left_start + ($i * $table_item_width), ($identifier_height / 2), $this->graph_left_start + (($i + 1) * $table_item_width), ($identifier_height / 2), false, $link, null, true);
			}
		}

		// Write the values
		$col = 0;

		foreach($this->table_data as &$table_values)
		{
			if(!is_array($table_values))
			{
				// TODO: determine why sometimes $table_values is empty or not an array
				continue;
			}

			foreach($table_values as $i => &$result_table_value)
			{
				$row = $i - 1; // if using $row, the alignment may be off sometimes
				$hover = array();
				$text_color = $this->graph_color_text;
				$bold = false;

				if($result_table_value instanceof pts_table_value)
				{
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

					$value = $result_table_value->get_value();
				}
				else
				{
					$value = $result_table_value;
				}

				$this->graph_image->write_text_right($value, $this->graph_font, $this->graph_font_size_identifiers, $text_color, $this->graph_left_start + ($col * $table_item_width) - 3, $identifier_height + ($row * $table_line_height) + $table_line_height_half, $this->graph_left_start + (($col + 1) * $table_item_width) - 3, $identifier_height + (($row + 1) * $table_line_height) + $table_line_height_half, false, null, implode("; ", $hover), $bold);
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
			$show_keys = array_keys($this->table_data);
			array_push($show_keys, "Temp: Temp");

			foreach($show_keys as $current_col => $system_identifier)
			{
				$identifier = pts_strings::colon_explode($system_identifier);

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
