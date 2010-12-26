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
	protected $rendered_rows;
	protected $columns;
	protected $table_data;
	protected $longest_column_identifier;
	protected $longest_row_identifier;
	protected $is_multi_way = false;
	protected $result_object_index = -1;
	protected $column_heading_vertical = true;

	public function __construct($rows, $columns, $table_data, &$result_file)
	{
		parent::__construct();

		if($result_file instanceof pts_result_file)
		{
			$this->is_multi_way = $result_file->is_multi_way_comparison();
		}

		if($this->is_multi_way)
		{
			foreach($columns as &$c_str)
			{
				// in multi-way comparisons this will be reported above, so don't need to report it in the column header as it kills space
				if(($c = strpos($c_str, ':')) !== false)
				{
					$c_str = substr($c_str, $c + 1);
				}
			}
		}

		$this->graph_attr_big_border = false;
		$this->rows = $rows;
		$this->columns = $columns;
		$this->table_data = $table_data;

		// Do some calculations
		$this->longest_column_identifier = $this->find_longest_string($this->columns);
		$this->longest_row_identifier = $this->find_longest_string($this->rows);
		$this->graph_maximum_value = $this->find_longest_string_in_table_data($this->table_data);
	}
	protected function find_longest_string_in_table_data(&$table_data)
	{
		$longest_string = null;
		$longest_string_length = 0;

		foreach($table_data as &$column)
		{
			if($column == null) continue;

			foreach($column as &$row)
			{
				if($row instanceof pts_table_value)
				{
					$value = $row->get_value();

					if(($spans_col = $row->get_attribute('spans_col')) > 1)
					{
						// Since the txt will be spread over multiple columns, it doesn't need to all fit into one
						$value = substr($value, 0, ceil(strlen($value) / $spans_col));
					}
				}
				else
				{
					$value = $row;
				}

				if(($new_length = strlen($value)) > $longest_string_length)
				{
					$longest_string = $value;
					$longest_string_length = $new_length;
				}
			}
		}

		return $longest_string;
	}
	public function renderChart($file = null)
	{
		$this->saveGraphToFile($file);
		$this->render_graph_start();
		return $this->render_graph_finish();
	}
	public function render_graph_start()
	{
		// Needs to be at least 86px wide for the PTS logo
		$this->graph_left_start = max(86, $this->text_string_width($this->longest_row_identifier, $this->graph_font, $this->graph_font_size_identifiers) + 10);

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
		$extra_heading_height = $this->text_string_height($this->graph_maximum_value, $this->graph_font, $this->graph_font_size_heading) * 1.2;

		// Needs to be at least 46px tall for the PTS logo
		$identifier_height = max($identifier_height, 48);

		if(defined("PHOROMATIC_TRACKER") || $this->is_multi_way)
		{
			$identifier_height += 6 + $extra_heading_height;
		}

		$table_max_value_width = $this->text_string_width($this->graph_maximum_value, $this->graph_font, $this->graph_font_size_identifiers);

		$table_item_width = max($table_max_value_width, $table_identifier_width);
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
		if($this->graph_left_start >= 170 && $identifier_height >= 90)
		{
			$this->graph_image->image_copy_merge($this->graph_image->png_image_to_type("http://www.phoronix-test-suite.com/external/pts-logo-160x83.png"), ($this->graph_left_start / 2 - 80), ($identifier_height / 2 - 41.5), 0, 0, 160, 83);
		}
		else
		{
			$this->graph_image->image_copy_merge($this->graph_image->png_image_to_type("http://www.phoronix-test-suite.com/external/pts-logo-80x42.png"), ($this->graph_left_start / 2 - 40), ($identifier_height / 2 - 21), 0, 0, 80, 42);

		}

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

		if(defined("PHOROMATIC_TRACKER") || $this->is_multi_way)
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

					$this->graph_image->draw_rectangle_with_border(($this->graph_left_start + 1 + ($last_changed_col * $table_item_width)), 2, ($this->graph_left_start + ($last_changed_col * $table_item_width)) + ($table_item_width * ($current_col - $last_changed_col)), $extra_heading_height, $paint_color, $this->graph_color_border);

					if($identifier[0] != "Temp")
					{
						$this->graph_image->draw_line(($this->graph_left_start + ($current_col * $table_item_width) + 1), 1, ($this->graph_left_start + ($current_col * $table_item_width) + 1), $this->graph_attr_height, $paint_color, 1);
					}

					$this->graph_image->write_text_center($last_identifier, $this->graph_font, $this->graph_font_size_axis_heading, $this->graph_color_background, $this->graph_left_start + ($last_changed_col * $table_item_width), 4, $this->graph_left_start + ($current_col * $table_item_width), 4, false, $last_identifier->get_attribute('onclick'), null, true);

					$last_identifier = $identifier[0];
					$last_changed_col = $current_col;
				}
			}
		}

		$table_identifier_offset = ($table_item_width / 2) + ($table_identifier_width / 2) - 1;
		foreach($this->columns as $i => $col_string)
		{
			$link = null;

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
		foreach($this->table_data as $index => &$table_values)
		{
			if(!is_array($table_values))
			{
				// TODO: determine why sometimes $table_values is empty or not an array
				continue;
			}

			if($this->is_multi_way && ($c = strpos($index, ':')) !== false)
			{
				$index = substr($index, $c + 1);
			}

			$col = array_search($index, $this->columns);

			if($col === false)
			{
				continue;
			}
			else
			{
				// unset this since it shouldn't be needed anymore and will then wipe out it from where multiple results have same name
				unset($this->columns[$col]);
			}

			foreach($table_values as $i => &$result_table_value)
			{
				$row = $i - 1; // if using $row, the alignment may be off sometimes
				$hover = array();
				$text_color = $this->graph_color_text;
				$bold = false;

				if($result_table_value == null)
				{
					continue;
				}

				if($result_table_value instanceof pts_table_value)
				{
					if(($t = $result_table_value->get_attribute('std_percent')) > 0)
					{
						array_push($hover, "STD Dev: " . $t . "%");
					}
					if(($t = $result_table_value->get_attribute('std_error')) != 0)
					{
						array_push($hover, " STD Error: " . $t);
					}

					if(defined("PHOROMATIC_TRACKER") &&($t = $result_table_value->get_attribute('delta')) != 0)
					{
						$bold = true;
						$text_color = $t < 0 ? $this->graph_color_alert : $this->graph_color_headers;
						array_push($hover, " Change: " . pts_math::set_precision(100 * $t, 2) . "%");
					}
					else if($result_table_value->get_attribute('highlight') == true)
					{
						$text_color = $this->graph_color_headers;
						$bold = true;
					}

					$value = $result_table_value->get_value();
					$spans_col = $result_table_value->get_attribute('spans_col');
				}
				else
				{
					$value = $result_table_value;
					$spans_col = 1;
				}

				$left_bounds = $this->graph_left_start + ($col * $table_item_width);
				$right_bounds = $this->graph_left_start + (($col + max(1, $spans_col)) * $table_item_width);
				$top_bounds = $identifier_height + (($row + 1.2) * $table_line_height);

				if($spans_col > 1)
				{
					if($col == 1)
					{
						$background_paint = $i % 2 == 1 ? $this->graph_color_background : $this->graph_color_body;
					}
					else
					{
						$background_paint = $i % 2 == 0 ? $this->graph_color_body_light : $this->graph_color_body;
					}

					$this->graph_image->draw_rectangle($left_bounds + 1, $identifier_height + (($row + 1) * $table_line_height) + 1, $right_bounds, $identifier_height + (($row + 2) * $table_line_height), $background_paint);
				}

				$this->graph_image->write_text_center($value, $this->graph_font, $this->graph_font_size_identifiers, $text_color, $left_bounds, $top_bounds, $right_bounds, $top_bounds, false, $result_table_value->get_attribute('onclick'), implode('; ', $hover), $bold);
				//$row++;
			}
		}

		$this->rendered_rows = $row;
	}
	public function render_graph_finish()
	{
		if($this->rendered_rows == 0 && $this->result_object_index != -1 && !is_array($this->result_object_index))
		{
			// No results were to be reported, so don't report the individualized graphs
			$this->graph_image->destroy_image();
			return $this->return_graph_image();
		}

		$this->graph_image->draw_rectangle_border(1, 1, $this->graph_attr_width, $this->graph_attr_height, $this->graph_color_border);
		return $this->return_graph_image();
	}
}

?>
