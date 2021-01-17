<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2019, Phoronix Media
	Copyright (C) 2009 - 2019, Michael Larabel
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

class pts_Table extends pts_graph_core
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

		$this->rows = $rows;
		$this->columns = $columns;
		$this->table_data = $table_data;

		// Do some calculations
		$this->longest_column_identifier = pts_strings::find_longest_string($this->columns);
		$this->longest_row_identifier = pts_strings::find_longest_string($this->rows);
		$this->i['graph_max_value'] = $this->find_longest_string_in_table_data($this->table_data);

		foreach($this->columns as &$column)
		{
			if(($column instanceof pts_graph_ir_value) == false)
			{
				$column = new pts_graph_ir_value($column);
			}
		}
	}
	public static function report_system_notes_to_table(&$result_file, &$table)
	{
		$identifier_count = $result_file->get_system_count();
		$system_attributes = pts_result_file_analyzer::system_notes_to_formatted_array($result_file);

		foreach($system_attributes as $index_name => $attributes)
		{
			$unique_attribue_count = count(array_unique($attributes));

			$section = $identifier_count > 1 ? ucwords($index_name) : null;

			switch($unique_attribue_count)
			{
				case 0:
					break;
				case 1:
					if($identifier_count == count($attributes))
					{
						// So there is something for all of the test runs and it's all the same...
						$table->addTestNote(array_pop($attributes), null, $section);
					}
					else
					{
						// There is missing data for some test runs for this value so report the runs this is relevant to.
						$table->addTestNote(implode(', ', array_keys($attributes)) . ': ' . array_pop($attributes), null, $section);
					}
					break;
				default:
					foreach($attributes as $identifier => $configuration)
					{
						$table->addTestNote($identifier . ': ' . $configuration, null, $section);
					}
					break;
			}
		}
	}
	protected function find_longest_string_in_table_data(&$table_data)
	{
		$longest_string = null;
		$longest_string_length = 0;

		foreach($table_data as &$column)
		{
			if($column == null)
			{
				continue;
			}

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
		$this->i['left_start'] = ceil(max(86, ($this->text_string_width($this->longest_row_identifier, $this->i['identifier_size']) * 1.1) + 12));

		if($this->column_heading_vertical)
		{
			$top_identifier_height = round($this->text_string_width($this->longest_column_identifier, $this->i['identifier_size']) * 1.1) + 12;
			$table_identifier_width = $this->text_string_width($this->longest_column_identifier, $this->i['identifier_size']);
		}
		else
		{
			$top_identifier_height = $this->text_string_height($this->longest_column_identifier, $this->i['identifier_size']) + 8;
			$table_identifier_width = round($this->text_string_width($this->longest_column_identifier, $this->i['identifier_size']) * 1.1) + 8;
		}

		// Needs to be at least 46px tall for the PTS logo
		$top_identifier_height = max($top_identifier_height, 48);

		if(defined('PHOROMATIC_TRACKER') || $this->is_multi_way)
		{
			$extra_heading_height = round($this->text_string_height($this->longest_column_identifier, self::$c['size']['headers']) * 1.25);
			$top_identifier_height += 6 + $extra_heading_height;
		}

		$this->i['top_heading_height'] = 8;
		if($this->i['graph_title'] != null)
		{
			$this->i['top_heading_height'] += round(self::$c['size']['headers'] + (count($this->graph_sub_titles) * (self::$c['size']['sub_headers'] + 4)));
		}

		$table_max_value_width = ceil($this->text_string_width($this->i['graph_max_value'], $this->i['identifier_size']) * 1.02) + 2;

		$table_item_width = max($table_max_value_width, $table_identifier_width) + 2;
		$table_width = max(($table_item_width * count($this->columns)), floor($this->text_string_width($this->i['graph_title'], 12) / $table_item_width) * $table_item_width);
		//$table_width = $table_item_width * count($this->columns);
		$table_line_height = round($this->text_string_height($this->i['graph_max_value'], $this->i['identifier_size']) + 8);
		$table_line_height_half = round($table_line_height / 2);
		$table_height = $table_line_height * count($this->rows);

		$table_proper_height = $this->i['top_heading_height'] + $table_height + $top_identifier_height;

		$this->i['graph_width'] = $table_width + $this->i['left_start'];
		$this->i['graph_height'] = round($table_proper_height + $table_line_height);

		if(!empty($this->i['notes']))
		{
			$this->i['graph_height'] += $this->note_display_height();
		}

		// Do the actual work
		$this->render_graph_pre_init();
		$this->render_graph_init();
		$this->svg_dom->add_element('rect', array('x' => 1, 'y' => 1, 'width' => ($this->i['graph_width'] - 1), 'height' => ($this->i['graph_height'] - 1), 'fill' => self::$c['color']['background'], 'stroke' => self::$c['color']['border'], 'stroke-width' => 1));

		// Start drawing
		$this->svg_dom->add_element('path', array('d' => 'm74 22v9m-5-16v16m-5-28v28m-23-2h12.5c2.485281 0 4.5-2.014719 4.5-4.5s-2.014719-4.5-4.5-4.5h-8c-2.485281 0-4.5-2.014719-4.5-4.5s2.014719-4.5 4.5-4.5h12.5m-21 5h-11m11 13h-2c-4.970563 0-9-4.029437-9-9v-20m-24 40v-20c0-4.970563 4.0294373-9 9-9 4.970563 0 9 4.029437 9 9s-4.029437 9-9 9h-9', 'stroke' => '#000000', 'stroke-width' => 4, 'fill' => 'none', 'transform' => 'translate(' . round($this->i['left_start'] / 2 - 40) . ',' . round($top_identifier_height / 2 - 21 + $this->i['top_heading_height']) . ')'));

		// Draw the vertical table lines
		$v = round((($top_identifier_height + $table_height) / 2) + $this->i['top_heading_height']);
		$table_columns_end = $this->i['left_start'] + ($table_item_width * count($this->columns));

		$this->svg_dom->draw_svg_line($this->i['left_start'], $v, $table_columns_end, $v, self::$c['color']['body'], $table_height + $top_identifier_height, array('stroke-dasharray' => $table_item_width . ',' . $table_item_width));

		if($table_columns_end < $this->i['graph_width'])
		{
			$this->svg_dom->add_element('rect', array('x' => $table_columns_end, 'y' => $this->i['top_heading_height'], 'width' => ($this->i['graph_width'] - $table_columns_end), 'height' => ($table_height + $top_identifier_height), 'fill' => self::$c['color']['body_light']));
		}

		// Background horizontal
		$this->svg_dom->draw_svg_line(round($table_columns_end / 2), ($top_identifier_height + $this->i['top_heading_height']), round($table_columns_end / 2), $table_proper_height, self::$c['color']['body_light'], $table_columns_end, array('stroke-dasharray' => $table_line_height . ',' . $table_line_height));

		// Draw the borders
		$this->svg_dom->draw_svg_line($this->i['left_start'], $v, $table_columns_end + ($table_columns_end < $this->i['graph_width'] ? $table_item_width : 0), $v, self::$c['color']['border'], $table_height + $top_identifier_height, array('stroke-dasharray' => '1,' . ($table_item_width - 1)));

		// Heading
		if($this->i['graph_title'] != null)
		{
			$this->svg_dom->add_element('rect', array('x' => 1, 'y' => 1, 'width' => ($this->i['graph_width'] - 2), 'height' => $this->i['top_heading_height'], 'fill' => self::$c['color']['main_headers']));
			if(!$this->is_multi_way)
				$this->svg_dom->add_text_element($this->i['graph_title'], array('x' => 5, 'y' => (self::$c['size']['headers'] + 2), 'font-size' => self::$c['size']['headers'], 'fill' => self::$c['color']['background'], 'text-anchor' => 'start'));

			foreach($this->graph_sub_titles as $i => $sub_title)
			{
				$vertical_offset = 16 + self::$c['size']['headers'] + ($i * (self::$c['size']['sub_headers']));
				$this->svg_dom->add_text_element($sub_title, array('x' => 5, 'y' => $vertical_offset, 'font-size' => self::$c['size']['sub_headers'], 'fill' => self::$c['color']['background'], 'text-anchor' => 'start'));
			}

			$this->svg_dom->draw_svg_line(1, $this->i['top_heading_height'], $this->i['graph_width'] - 1, $this->i['top_heading_height'], self::$c['color']['border'], 1);
		}

		// Write the rows
		$row = 1;
		$g = $this->svg_dom->make_g(array('font-size' => $this->i['identifier_size'], 'font-weight' => 'bold', 'fill' => self::$c['color']['text'], 'text-anchor' => 'end'));
		foreach($this->rows as $i => $row_string)
		{
			if(($row_string instanceof pts_graph_ir_value) == false)
			{
				$row_string = new pts_graph_ir_value($row_string);
			}

			$v = round($top_identifier_height + $this->i['top_heading_height'] + ($row * $table_line_height) - 4);
			$r = array('x' => ($this->i['left_start'] - 2), 'y' => $v, 'xlink:href' => $row_string->get_attribute('href'));
			if($row_string->get_attribute('alert'))
			{
				$r['fill'] =  self::$c['color']['alert'];
			}
			$this->svg_dom->add_text_element($row_string, $r, $g);
			$row++;
		}

		// Write the identifiers
		if(defined('PHOROMATIC_TRACKER') || $this->is_multi_way)
		{
			$last_identifier = null;
			$last_changed_col = 0;
			$show_keys = array_keys($this->table_data);
			$show_keys[] = 'Temp: Temp';

			$g1 = $this->svg_dom->make_g(array());
			$g2 = $this->svg_dom->make_g(array('font-size' => self::$c['size']['axis_headers'], 'fill' => self::$c['color']['background'], 'font-weight' => 'bold', 'text-anchor' => 'middle'));
			foreach($show_keys as $current_col => $system_identifier)
			{
				$identifier = pts_strings::colon_explode($system_identifier);

				if(isset($identifier[0]) && $identifier[0] != $last_identifier)
				{
					if($current_col == $last_changed_col)
					{
						$last_identifier = $identifier[0];
						continue;
					}

					//$paint_color = $this->get_paint_color($system_identifier);

					if($this->i['top_heading_height'] > 0)
					{
						$extra_heading_height = $this->i['top_heading_height'];
					}

					$x = $this->i['left_start'] + 1 + ($last_changed_col * $table_item_width);
					$x_end = ($this->i['left_start'] + ($last_changed_col * $table_item_width)) + ($table_item_width * ($current_col - $last_changed_col));

					//$this->svg_dom->add_element('rect', array('x' => $x, 'y' => 0, 'width' => ($table_item_width * ($current_col - $last_changed_col)) - 2, 'height' => $extra_heading_height, 'fill' => $paint_color), $g1);

					if($identifier[0] != 'Temp')
					{
						$this->svg_dom->draw_svg_line(($this->i['left_start'] + ($current_col * $table_item_width) + 1), 1, ($this->i['left_start'] + ($current_col * $table_item_width) + 1), $table_proper_height, self::$c['color']['background'], 1);
					}

					//$x = $this->i['left_start'] + ($last_changed_col * $table_item_width) + ($this->i['left_start'] + ($current_col * $table_item_width) - $this->i['left_start'] + ($last_changed_col * $table_item_width));
					$this->svg_dom->add_text_element($last_identifier, array('x' => round($x + (($x_end - $x) / 2)), 'y' => (self::$c['size']['axis_headers'] + 4)), $g2);

					$last_identifier = $identifier[0];
					$last_changed_col = $current_col;
				}
			}
		}

		$table_identifier_offset = ($table_item_width / 2) + ($table_identifier_width / 2) - 1;
		$g_opts = array('font-size' => $this->i['identifier_size'], 'fill' => self::$c['color']['text'], 'font-weight' => 'bold');
		if($this->column_heading_vertical)
		{
			$g_opts['text-anchor'] = 'end';
		}
		else
		{
			$g_opts['text-anchor'] = 'middle';
			$g_opts['dominant-baseline'] = 'text-before-edge';
		}
		$g = $this->svg_dom->make_g($g_opts);
		foreach($this->columns as $i => $col_string)
		{
			if($this->column_heading_vertical)
			{
				$x = $this->i['left_start'] + ($i * $table_item_width) + $table_identifier_offset;
				$y = $this->i['top_heading_height'] + $top_identifier_height - 4;
				$this->svg_dom->add_text_element($col_string, array('x' => $x, 'y' => $y, 'transform' => 'rotate(90 ' . $x . ' ' . $y . ')'), $g);
			}
			else
			{
				$x = round($this->i['left_start'] + ($i * $table_item_width) + ($table_item_width / 2));
				$y = round($this->i['top_heading_height'] + ($top_identifier_height / 2));
				$this->svg_dom->add_text_element($col_string, array('x' => $x, 'y' => $y), $g);
			}
		}

		// Write the columns
		$g_background = $this->svg_dom->make_g(array('fill' => self::$c['color']['body']));
		$g = $this->svg_dom->make_g(array('text-anchor' => 'middle', 'font-size' => $this->i['identifier_size'], 'fill' => self::$c['color']['text']));
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
				$text_color = null;
				$bold = false;

				if($result_table_value == null)
				{
					continue;
				}

				if($result_table_value instanceof pts_graph_ir_value)
				{
					if(defined('PHOROMATIC_TRACKER') && ($t = $result_table_value->get_attribute('delta')) != 0)
					{
						$bold = true;
						$text_color = $t < 0 ? self::$c['color']['alert'] : self::$c['color']['headers'];
					}
					else if($result_table_value->get_attribute('highlight') == true)
					{
						$text_color = self::$c['color']['highlight'];
					}
					else if($result_table_value->get_attribute('alert') == true)
					{
						$text_color = self::$c['color']['alert'];
					}

					$value = $result_table_value->get_value();
					$spans_col = $result_table_value->get_attribute('spans_col');
				}
				else
				{
					$value = $result_table_value;
					$spans_col = 1;
				}

				$left_bounds = $this->i['left_start'] + ($col * $table_item_width);
				$right_bounds = $this->i['left_start'] + (($col + max(1, $spans_col)) * $table_item_width);

				if($spans_col > 1)
				{
					if($col == 1)
					{
						$background_paint = $i % 2 == 1 ? self::$c['color']['background'] : self::$c['color']['body'];
					}
					else
					{
						$background_paint = $i % 2 == 0 ? self::$c['color']['body_light'] : self::$c['color']['body'];
					}

					$gbr = array('x' => $left_bounds, 'y' => round($this->i['top_heading_height'] + $top_identifier_height + (($row + 1) * $table_line_height)) + 1, 'width' => ($right_bounds - $left_bounds), 'height' => $table_line_height);

					if(self::$c['color']['body'] != $background_paint)
					{
						$gbr['fill'] = $background_paint;
					}

					$this->svg_dom->add_element('rect', $gbr, $g_background);
				}

				$x = round($left_bounds + (($right_bounds - $left_bounds) / 2));
				$gbr = array('x' => $x, 'y' => round($this->i['top_heading_height'] + $top_identifier_height + (($row + 2) * $table_line_height) - 3), 'xlink:href' => $result_table_value->get_attribute('href'));
				if($text_color != null)
				{
					$gbr['fill'] = $text_color;
				}
				$this->svg_dom->add_text_element($result_table_value, $gbr, $g);
				//$row++;
			}
		}

		//$this->svg_dom->draw_svg_line(($table_columns_end / 2), ($top_identifier_height + $this->i['top_heading_height']), round($table_columns_end / 2), $this->i['graph_height'], self::$c['color']['border'], $table_columns_end, array('stroke-dasharray' => '1,' . ($table_line_height - 1)));
		$this->svg_dom->draw_svg_line(round($table_columns_end / 2), ($top_identifier_height + $this->i['top_heading_height']), round($table_columns_end / 2), $table_proper_height, self::$c['color']['body_light'], $table_columns_end, array('stroke-dasharray' => 1 . ',' . ($table_line_height - 1)));

		// Bottom part

		$this->svg_dom->add_element('rect', array('x' => 0, 'y' => $table_proper_height, 'width' => $this->i['graph_width'], 'height' => ($this->i['graph_height'] - $table_proper_height), 'fill' => self::$c['color']['headers']));
		$this->svg_dom->add_text_element(self::$c['text']['watermark'], array('x' => ($this->i['graph_width'] - 2), 'y' => ($this->i['graph_height'] - 3), 'font-size' => $this->i['identifier_size'], 'fill' => self::$c['color']['body_text'], 'text-anchor' => 'end', 'xlink:href' => self::$c['text']['watermark_url']));

		if(isset($this->d['link_alternate_view']) && $this->d['link_alternate_view'])
		{
			$this->svg_dom->add_text_element(0, array('x' => 6, 'y' => ($this->i['graph_height'] - 3), 'font-size' => 7, 'fill' => self::$c['color']['background'], 'text-anchor' => 'start', 'xlink:href' => $this->d['link_alternate_view'], 'show' => 'replace', 'font-weight' => 'bold'));
		}

		if(!empty($this->i['notes']))
		{
			$estimated_height = 0;
			$previous_section = null;
			foreach($this->i['notes'] as $i => $note_r)
			{
				if($note_r['section'] != null && $note_r['section'] !== $previous_section)
				{
					$estimated_height += 2;
					$this->svg_dom->add_textarea_element($note_r['section'] . ' Details', array('x' => 6, 'y' => ($table_proper_height + $table_line_height + $estimated_height), 'style' => 'font-weight: bold', 'text-anchor' => 'start', 'fill' => self::$c['color']['background'], 'font-size' => (self::$c['size']['key'] - 1)), $estimated_height);
					$estimated_height += 2;
					$previous_section = $note_r['section'];
				}

				$this->svg_dom->add_textarea_element('- ' . $note_r['note'], array('x' => 6, 'y' => ($table_proper_height + $table_line_height + $estimated_height), 'text-anchor' => 'start', 'fill' => self::$c['color']['background'], 'font-size' => (self::$c['size']['key'] - 1)), $estimated_height);
			}
		}

		$this->rendered_rows = $row;
	}
	public function render_graph_finish()
	{
		if($this->rendered_rows == 0 && $this->result_object_index != -1 && !is_array($this->result_object_index))
		{
			// No results were to be reported, so don't report the individualized graphs
			// destroy surface
		}
	}
}

?>
