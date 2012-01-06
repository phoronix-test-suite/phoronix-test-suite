<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2012, Phoronix Media
	Copyright (C) 2009 - 2012, Michael Larabel
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

		$this->c['graph']['border'] = false;
		$this->rows = $rows;
		$this->columns = $columns;
		$this->table_data = $table_data;

		// Do some calculations
		$this->longest_column_identifier = pts_strings::find_longest_string($this->columns);
		$this->longest_row_identifier = pts_strings::find_longest_string($this->rows);
		$this->graph_maximum_value = $this->find_longest_string_in_table_data($this->table_data);

		foreach($this->columns as &$column)
		{
			if(($column instanceof pts_graph_ir_value) == false)
			{
				$column = new pts_graph_ir_value($column);
			}
		}
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
				if($row instanceof pts_graph_ir_value)
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

				if(isset($value[$longest_string_length]))
				{
					$longest_string = $value;
					$longest_string_length = strlen($value);
				}
			}
		}

		return $longest_string;
	}
	public function renderChart($save_as = null)
	{
		$this->render_graph_start();
		$this->render_graph_finish();
		return $this->svg_dom->output($save_as);
	}
	public function render_graph_start()
	{
		// Needs to be at least 86px wide for the PTS logo
		$this->c['pos']['left_start'] = max(86, $this->text_string_width($this->longest_row_identifier, $this->c['size']['identifiers']) + 10);

		if($this->column_heading_vertical)
		{
			$identifier_height = $this->text_string_width($this->longest_column_identifier, $this->c['size']['identifiers']) + 12;
			$table_identifier_width = $this->text_string_height($this->longest_column_identifier, $this->c['size']['identifiers']);
		}
		else
		{
			$identifier_height = $this->text_string_height($this->longest_column_identifier, $this->c['size']['identifiers']) + 8;
			$table_identifier_width = $this->text_string_width($this->longest_column_identifier, $this->c['size']['identifiers']);
		}


		// $this->graph_maximum_value isn't actually correct to use, but it works
		$extra_heading_height = $this->text_string_height($this->graph_maximum_value, $this->c['size']['headers']) * 1.2;

		// Needs to be at least 46px tall for the PTS logo
		$identifier_height = max($identifier_height, 48);

		if(defined('PHOROMATIC_TRACKER') || $this->is_multi_way)
		{
			$identifier_height += 6 + $extra_heading_height;
		}

		if($this->graph_title != null)
		{
			$this->graph_top_heading_height = 8 + $this->c['size']['headers'] + (count($this->graph_sub_titles) * ($this->c['size']['sub_headers'] + 4));
		}

		$table_max_value_width = $this->text_string_width($this->graph_maximum_value, $this->c['size']['identifiers']);

		$table_item_width = max($table_max_value_width, $table_identifier_width) + 2;
		$table_width = max(($table_item_width * count($this->columns)), floor($this->text_string_width($this->graph_title, 12) / $table_item_width) * $table_item_width);
		//$table_width = $table_item_width * count($this->columns);
		$table_line_height = round($this->text_string_height($this->graph_maximum_value, $this->c['size']['identifiers']) + 8);
		$table_line_height_half = round($table_line_height / 2);
		$table_height = $table_line_height * count($this->rows);

		$table_proper_height = $this->graph_top_heading_height + $table_height + $identifier_height;

		$this->c['graph']['width'] = $table_width + $this->c['pos']['left_start'];
		$this->c['graph']['height'] = $table_proper_height + $table_line_height;

		// Do the actual work
		$this->render_graph_pre_init();
		$this->render_graph_init(array('cache_font_size' => true));
		$this->svg_dom->add_element('rect', array('x' => 0, 'y' => 0, 'width' => $this->c['graph']['width'], 'height' => $this->c['graph']['height'], 'fill' => $this->c['color']['background'], 'stroke' => $this->c['color']['border'], 'stroke-width' => 1));

		// Start drawing
		if($this->c['pos']['left_start'] >= 170 && $identifier_height >= 90)
		{
			$this->svg_dom->add_element('image', array('xlink:href' => 'http://www.phoronix-test-suite.com/external/pts-logo-160x83.png', 'x' => round($this->c['pos']['left_start'] / 2 - 80), 'y' => round(($identifier_height / 2 - 41.5) + $this->graph_top_heading_height), 'width' => 160, 'height' => 83));
		}
		else
		{
			$this->svg_dom->add_element('image', array('xlink:href' => 'http://www.phoronix-test-suite.com/external/pts-logo-80x42.png', 'x' => round($this->c['pos']['left_start'] / 2 - 40), 'y' => round($identifier_height / 2 - 21 + $this->graph_top_heading_height), 'width' => 80, 'height' => 42));

		}

		// Draw the vertical table lines
		$v = round((($identifier_height + $table_height) / 2) + $this->graph_top_heading_height);
		$table_columns_end = $this->c['pos']['left_start'] + ($table_item_width * count($this->columns));

		$this->svg_dom->draw_svg_line($this->c['pos']['left_start'], $v, $table_columns_end, $v, $this->c['color']['body'], $table_height + $identifier_height, array('stroke-dasharray' => $table_item_width . ',' . $table_item_width));

		if($table_columns_end < $this->c['graph']['width'])
		{
			$this->svg_dom->add_element('rect', array('x' => $table_columns_end, 'y' => $this->graph_top_heading_height, 'width' => ($this->c['graph']['width'] - $table_columns_end), 'height' => ($table_height + $identifier_height), 'fill' => $this->c['color']['body_light']));
		}

		// Background horizontal
		$this->svg_dom->draw_svg_line(($table_columns_end / 2), ($identifier_height + $this->graph_top_heading_height), round($table_columns_end / 2), $table_proper_height, $this->c['color']['body_light'], $table_columns_end, array('stroke-dasharray' => $table_line_height . ',' . $table_line_height));

		// Draw the borders
		$this->svg_dom->draw_svg_line(($table_columns_end / 2), ($identifier_height + $this->graph_top_heading_height), round($table_columns_end / 2), $this->c['graph']['height'], $this->c['color']['border'], $table_columns_end, array('stroke-dasharray' => '1,' . ($table_line_height - 1)));
		$this->svg_dom->draw_svg_line($this->c['pos']['left_start'], $v, $table_columns_end + ($table_columns_end < $this->c['graph']['width'] ? $table_item_width : 0), $v, $this->c['color']['border'], $table_height + $identifier_height, array('stroke-dasharray' => '1,' . ($table_item_width - 1)));

		$this->svg_dom->add_element('rect', array('x' => 0, 'y' => $table_proper_height, 'width' => $this->c['graph']['width'], 'height' => ($this->c['graph']['height'] - $table_proper_height), 'fill' => $this->c['color']['headers']));
		$this->svg_dom->add_text_element($this->c['text']['watermark'], array('x' => ($this->c['graph']['width'] - 2), 'y' => ($table_proper_height + $table_line_height_half), 'font-size' => $this->c['size']['identifiers'], 'fill' => $this->c['color']['body_text'], 'text-anchor' => 'end', 'dominant-baseline' => 'middle', 'xlink:show' => 'new', 'xlink:href' => $this->c['text']['watermark_url']));

		if($this->link_alternate_view != null)
		{
			$this->svg_dom->add_text_element(0, array('x' => 6, 'y' => ($table_proper_height + $table_line_height_half), 'font-size' => 7, 'fill' => $this->c['color']['background'], 'text-anchor' => 'start', 'dominant-baseline' => 'middle', 'xlink:show' => 'new', 'xlink:href' => $this->link_alternate_view, 'show' => 'replace', 'font-weight' => 'bold'));
		}

		// Heading
		if($this->graph_title != null)
		{
			$this->svg_dom->add_element('rect', array('x' => 1, 'y' => 1, 'width' => ($this->c['graph']['width'] - 2), 'height' => $this->graph_top_heading_height, 'fill' => $this->c['color']['main_headers']));
			$this->svg_dom->add_text_element($this->graph_title, array('x' => 5, 'y' => 12, 'font-size' => $this->c['size']['headers'], 'fill' => $this->c['color']['background'], 'text-anchor' => 'start', 'dominant-baseline' => 'middle'));

			foreach($this->graph_sub_titles as $i => $sub_title)
			{
				$vertical_offset = 16 + $this->c['size']['headers'] + ($i * ($this->c['size']['sub_headers'] + 3));
				$this->svg_dom->add_text_element($sub_title, array('x' => 5, 'y' => $vertical_offset, 'font-size' => $this->c['size']['sub_headers'], 'fill' => $this->c['color']['background'], 'text-anchor' => 'start', 'dominant-baseline' => 'middle'));
			}

			$this->svg_dom->draw_svg_line(1, $this->graph_top_heading_height, $this->c['graph']['width'] - 1, $this->graph_top_heading_height, $this->c['color']['border'], 1);

		}

		// Write the test names
		$row = 0;
		foreach($this->rows as $i => $row_string)
		{
			if(($row_string instanceof pts_graph_ir_value) == false)
			{
				$row_string = new pts_graph_ir_value($row_string);
			}

			$text_color = $row_string->get_attribute('alert') ? $this->c['color']['alert'] : $this->c['color']['text'];

			$v = round($identifier_height + $this->graph_top_heading_height + ($row * $table_line_height) + $table_line_height_half);
			$this->svg_dom->add_text_element($row_string, array('x' => ($this->c['pos']['left_start'] - 2), 'y' => $v, 'font-size' => $this->c['size']['identifiers'], 'fill' => $text_color, 'font-weight' => 'bold', 'text-anchor' => 'end', 'dominant-baseline' => 'middle'));
			$row++;
		}

		// Write the identifiers

		if(defined('PHOROMATIC_TRACKER') || $this->is_multi_way)
		{
			$last_identifier = null;
			$last_changed_col = 0;
			$show_keys = array_keys($this->table_data);
			array_push($show_keys, 'Temp: Temp');

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

					if($this->graph_top_heading_height > 0)
					{
						$extra_heading_height = $this->graph_top_heading_height;
					}

					$x = $this->c['pos']['left_start'] + 1 + ($last_changed_col * $table_item_width);
					$x_end = ($this->c['pos']['left_start'] + ($last_changed_col * $table_item_width)) + ($table_item_width * ($current_col - $last_changed_col));

					$this->svg_dom->add_element('rect', array('x' => $x, '2' => 0, 'width' => ($table_item_width * ($current_col - $last_changed_col)), 'height' => ($extra_heading_height - 2), 'fill' => $paint_color, 'stroke' => $this->c['color']['border'], 'stroke-width' => 1));

					if($identifier[0] != 'Temp')
					{
						$this->svg_dom->draw_svg_line(($this->c['pos']['left_start'] + ($current_col * $table_item_width) + 1), 1, ($this->c['pos']['left_start'] + ($current_col * $table_item_width) + 1), $table_proper_height, $paint_color, 1);
					}

					$x = $this->c['pos']['left_start'] + ($last_changed_col * $table_item_width) + ($this->c['pos']['left_start'] + ($current_col * $table_item_width) - $this->c['pos']['left_start'] + ($last_changed_col * $table_item_width));
					$this->svg_dom->add_text_element($last_identifier, array('x' => $x, 'y' => 4, 'font-size' => $this->c['size']['axis_headers'], 'fill' => $this->c['color']['background'], 'font-weight' => 'bold', 'text-anchor' => 'middle', 'dominant-baseline' => 'text-before-edge'));

					$last_identifier = $identifier[0];
					$last_changed_col = $current_col;
				}
			}
		}

		$table_identifier_offset = ($table_item_width / 2) + ($table_identifier_width / 2) - 1;
		foreach($this->columns as $i => $col_string)
		{
			if($this->column_heading_vertical)
			{
				$x = $this->c['pos']['left_start'] + ($i * $table_item_width) + $table_identifier_offset;
				$y = $this->graph_top_heading_height + $identifier_height - 10;
				$this->svg_dom->add_text_element($col_string, array('x' => $x, 'y' => $y, 'font-size' => $this->c['size']['identifiers'], 'fill' => $this->c['color']['text'], 'font-weight' => 'bold', 'text-anchor' => 'end', 'dominant-baseline' => 'middle', 'transform' => 'rotate(90 ' . $x . ' ' . $y . ')'));
			}
			else
			{
				$x = $this->c['pos']['left_start'] + ($i * $table_item_width) + ($table_item_width / 2);
				$y = $this->graph_top_heading_height + ($identifier_height / 2);
				$this->svg_dom->add_text_element($col_string, array('x' => $x, 'y' => $y, 'font-size' => $this->c['size']['identifiers'], 'fill' => $this->c['color']['text'], 'font-weight' => 'bold', 'text-anchor' => 'middle', 'dominant-baseline' => 'text-before-edge'));
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

			$col = array_search(strval($index), $this->columns);

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
				$text_color = $this->c['color']['text'];
				$bold = false;

				if($result_table_value == null)
				{
					continue;
				}

				if($result_table_value instanceof pts_graph_ir_value)
				{
					if(($t = $result_table_value->get_attribute('std_percent')) > 0)
					{
						array_push($hover, 'STD Dev: ' . $t . '%');
					}
					if(($t = $result_table_value->get_attribute('std_error')) != 0)
					{
						array_push($hover, ' STD Error: ' . $t);
					}

					if(defined('PHOROMATIC_TRACKER') &&($t = $result_table_value->get_attribute('delta')) != 0)
					{
						$bold = true;
						$text_color = $t < 0 ? $this->c['color']['alert'] : $this->c['color']['headers'];
						array_push($hover, ' Change: ' . pts_math::set_precision(100 * $t, 2) . '%');
					}
					else if($result_table_value->get_attribute('highlight') == true)
					{
						$text_color = $this->c['color']['highlight'];
					}
					else if($result_table_value->get_attribute('alert') == true)
					{
						$text_color = $this->c['color']['alert'];
					}

					$value = $result_table_value->get_value();
					$spans_col = $result_table_value->get_attribute('spans_col');
				}
				else
				{
					$value = $result_table_value;
					$spans_col = 1;
				}

				$left_bounds = $this->c['pos']['left_start'] + ($col * $table_item_width);
				$right_bounds = $this->c['pos']['left_start'] + (($col + max(1, $spans_col)) * $table_item_width);
				$top_bounds = round($this->graph_top_heading_height + $identifier_height + (($row + 1.2) * $table_line_height));

				if($spans_col > 1)
				{
					if($col == 1)
					{
						$background_paint = $i % 2 == 1 ? $this->c['color']['background'] : $this->c['color']['body'];
					}
					else
					{
						$background_paint = $i % 2 == 0 ? $this->c['color']['body_light'] : $this->c['color']['body'];
					}

					$y = $this->graph_top_heading_height + $identifier_height + (($row + 1) * $table_line_height) + 1;
					$this->svg_dom->add_element('rect', array('x' => ($left_bounds + 1), 'y' => $y, 'width' => ($right_bounds - $left_bounds), 'height' => $table_line_height, 'fill' => $background_paint));
				}

				$x = $left_bounds + (($right_bounds - $left_bounds) / 2);
				$this->svg_dom->add_text_element($result_table_value, array('x' => $x, 'y' => $top_bounds, 'font-size' => $this->c['size']['identifiers'], 'fill' => $text_color, 'text-anchor' => 'middle', 'dominant-baseline' => 'text-before-edge', 'xlink:title' => implode('; ', $hover)));
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
			//$this->graph_image->destroy_image();
		}
	}
}

?>
