<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2012 - 2018, Phoronix Media
	Copyright (C) 2012 - 2018, Michael Larabel
	pts_SideViewTable.php: A charting table object for pts_Graph in a side-view manner

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

class pts_SideViewTable extends pts_graph_core
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

		$this->rows = $rows;
		$this->columns = $columns;
		$this->table_data = $table_data;

		// Do some calculations
		$this->longest_column_identifier = pts_strings::find_longest_string($this->columns);
		$this->longest_row_identifier = pts_strings::find_longest_string($this->rows);

		foreach($this->columns as &$column)
		{
			if(($column instanceof pts_graph_ir_value) == false)
			{
				$column = new pts_graph_ir_value($column);
			}
		}

		$this->column_heading_vertical = false;
	}
	public function renderChart($save_as = null)
	{
		if(empty($this->rows) || empty($this->columns))
		{
			return false;
		}

		$this->render_graph_start();
		$this->render_graph_finish();
		return $this->svg_dom->output($save_as);
	}
	public function render_graph_start()
	{
		// Needs to be at least 86px wide for the PTS logo
		$this->i['left_start'] = ceil(max(86, ($this->text_string_width($this->longest_row_identifier, $this->i['identifier_size']) * 1.09) + 8));

		// Needs to be at least 46px tall for the PTS logo
		$top_identifier_height = max(($this->text_string_height($this->longest_column_identifier, $this->i['identifier_size']) + 8), 48);

		$this->i['top_heading_height'] = 1;
		if($this->i['graph_title'] != null)
		{
			$this->i['top_heading_height'] += round(self::$c['size']['headers'] + (count($this->graph_sub_titles) * (self::$c['size']['sub_headers'] + 4)));
		}

		$column_widths = array();
		$row_heights = array_fill(0, count($this->rows), (ceil($this->i['identifier_size'] * 2.2)));
		foreach($this->columns as $i => $column)
		{
			$column_width = strlen($column);
			$column_index = -1;

			// See if any of the contained values in that column are longer than the column title itself
			foreach($this->table_data[$i] as $row => &$column_data)
			{
				if(isset($column_data[$column_width]))
				{
					$column = $column_data;
					$column_width = strlen($column_data);
					$column_index = $row;
				}

				if(strlen($column_data) > 64)
				{
					// If it's too long break it into multiple rows
					$row_heights[$row] = ceil($this->i['identifier_size'] * ceil(strlen($column_data) / 64)) + 18;
					$column = substr($column, 0, 64);
				}
			}

			$column_widths[$i] = ceil($this->text_string_width($column, $this->i['identifier_size']) * 1.02) + 14;
		}

		$table_width = $this->text_string_width($this->longest_row_identifier, $this->i['identifier_size']) + array_sum($column_widths);
		$table_height = array_sum($row_heights);

		$table_proper_height = $this->i['top_heading_height'] + $table_height + $top_identifier_height;
		$this->i['graph_width'] = $table_width + $this->i['left_start'] + 1;
		$this->i['graph_height'] = round($table_proper_height + 16);

		if(!empty($this->i['notes']))
		{
			$this->i['graph_height'] += $this->note_display_height();
		}

		// Do the actual work
		$this->render_graph_pre_init();
		$this->render_graph_init();
		$this->svg_dom->add_element('rect', array('x' => 1, 'y' => 1, 'width' => ($this->i['graph_width'] - 1), 'height' => ($this->i['graph_height'] - 1), 'fill' => self::$c['color']['background'], 'stroke' => self::$c['color']['border'], 'stroke-width' => 1));

		// Start drawing
		if($this->i['left_start'] >= 170 && $top_identifier_height >= 90)
		{
			$this->svg_dom->add_element('image', array('http_link' => 'http://www.phoronix-test-suite.com/', 'xlink:href' => 'https://openbenchmarking.org/static/images/pts-160x83.png', 'x' => round($this->i['left_start'] / 2 - 80), 'y' => round(($top_identifier_height / 2 - 41.5) + $this->i['top_heading_height']), 'width' => 160, 'height' => 83));
		}
		else
		{
			$this->svg_dom->add_element('image', array('http_link' => 'http://www.phoronix-test-suite.com/', 'xlink:href' => 'https://openbenchmarking.org/static/images/pts-80x42.png', 'x' => round($this->i['left_start'] / 2 - 40), 'y' => round($top_identifier_height / 2 - 21 + $this->i['top_heading_height']), 'width' => 80, 'height' => 42));
		}

		// Draw the vertical table lines
		$v = round((($top_identifier_height + $table_height) / 2) + $this->i['top_heading_height']);

		// Heading
		if($this->i['graph_title'] != null)
		{
			$this->svg_dom->add_element('rect', array('x' => 1, 'y' => 1, 'width' => ($this->i['graph_width'] - 2), 'height' => $this->i['top_heading_height'], 'fill' => self::$c['color']['main_headers']));

			foreach($this->graph_sub_titles as $i => $sub_title)
			{
				$vertical_offset = 16 + self::$c['size']['headers'] + ($i * (self::$c['size']['sub_headers']));
				$this->svg_dom->add_text_element($sub_title, array('x' => 5, 'y' => $vertical_offset, 'font-size' => self::$c['size']['sub_headers'], 'fill' => self::$c['color']['background'], 'text-anchor' => 'start'));
			}

			$this->svg_dom->draw_svg_line(1, $this->i['top_heading_height'], $this->i['graph_width'] - 1, $this->i['top_heading_height'], self::$c['color']['border'], 1);
		}

		// Write the rows
		$horizontal_offset = $top_identifier_height + $this->i['top_heading_height'];
		foreach($this->rows as $i => $row_string)
		{
			if($i % 2 == 0)
			{
				$this->svg_dom->add_element('rect', array('x' => 1, 'y' => $horizontal_offset, 'width' => $this->i['left_start'], 'height' => $row_heights[$i], 'fill' => self::$c['color']['body_light'], 'stroke' => self::$c['color']['border'], 'stroke-width' => 1));
			}

			$this->svg_dom->add_text_element($row_string, array('x' => ($this->i['left_start'] - 4), 'y' => ($horizontal_offset + 16), 'font-size' => $this->i['identifier_size'], 'fill' => self::$c['color']['text'], 'font-weight' => 'bold', 'text-anchor' => 'end'));
			$horizontal_offset += $row_heights[$i];
		}

		// Write the columns
		$y = $this->i['top_heading_height'] + ($top_identifier_height / 2) - 6;
		$column_width_offset = $this->i['left_start'];
		foreach($this->columns as $i => $col_string)
		{
			if($i % 2 == 0)
			{
				$this->svg_dom->add_element('rect', array('x' => $column_width_offset, 'y' => $this->i['top_heading_height'], 'width' => $column_widths[$i], 'height' => $top_identifier_height, 'fill' => self::$c['color']['body_light'], 'stroke' => self::$c['color']['border'], 'stroke-width' => 1));
			}
			$this->svg_dom->add_text_element($col_string, array('x' => $column_width_offset + round($column_widths[$i] / 2) , 'y' => $y, 'font-size' => $this->i['identifier_size'], 'fill' => self::$c['color']['text'], 'font-weight' => 'bold', 'text-anchor' => 'middle', 'dominant-baseline' => 'text-before-edge'));
			$column_width_offset += $column_widths[$i];
		}

		// Write the values
		$column_width_offset = $this->i['left_start'];
		$column_count = 0;

		foreach($this->table_data as $column => &$column_data)
		{
			$x = $column_width_offset + round($column_widths[$column_count] / 2);

			$row_count = 0;
			$row_offset = $top_identifier_height + $this->i['top_heading_height'];
			foreach($column_data as $row => &$value)
			{
				if(true || $column % 2 == 0 || $row % 2 != 1)
				{
					$this->svg_dom->add_element('rect', array('x' => $column_width_offset, 'y' => $row_offset, 'width' => $column_widths[$column_count], 'height' => $row_heights[$row], 'fill' => self::$c['color'][($row % 2 == 0 ? 'body' : 'body_light')], 'stroke' => self::$c['color']['border'], 'stroke-width' => 1));
				}

				if(isset($value[64]))
				{
					// If it's a long string that needs to be broken to multiple linesd we need textarea to do automatic word wrapping
					$this->svg_dom->add_textarea_element($value, array('x' => $x, 'y' => ($row_offset + 16), 'font-size' => $this->i['identifier_size'], 'fill' => self::$c['color']['text'], 'text-anchor' => 'middle')); // , 'width' => ($column_widths[$column_count] - 8
				}
				else
				{
					$this->svg_dom->add_text_element($value, array('x' => $x, 'y' => ($row_offset + 16), 'font-size' => $this->i['identifier_size'], 'fill' => self::$c['color']['text'], 'text-anchor' => 'middle'));
				}

				$row_count++;
				$row_offset += $row_heights[$row];
			}

			$column_width_offset += $column_widths[$column_count];
			$column_count++;
		}

		// Bottom part
		$this->svg_dom->add_element('rect', array('x' => 0, 'y' => $table_proper_height, 'width' => $this->i['graph_width'], 'height' => ($this->i['graph_height'] - $table_proper_height), 'fill' => self::$c['color']['headers']));
		$this->svg_dom->add_text_element(self::$c['text']['watermark'], array('x' => ($this->i['graph_width'] - 2), 'y' => ($this->i['graph_height'] - 3), 'font-size' => $this->i['identifier_size'], 'fill' => self::$c['color']['body_text'], 'text-anchor' => 'end', 'xlink:href' => self::$c['text']['watermark_url']));

		if(!empty($this->i['notes']))
		{
			$estimated_height = 0;
			$previous_section = null;
			foreach($this->i['notes'] as $i => $note_r)
			{
				if($note_r['section'] != null && $note_r['section'] !== $previous_section)
				{
					$estimated_height += 2;
					$this->svg_dom->add_textarea_element($note_r['section'] . ' Details', array('x' => 6, 'y' => ($table_proper_height + 14 + $estimated_height), 'font-size' => (self::$c['size']['key'] - 1), 'fill' => self::$c['color']['background'], 'text-anchor' => 'start', 'style' => 'font-weight: bold'), $estimated_height);
					$estimated_height += 2;
					$previous_section = $note_r['section'];
				}

				$this->svg_dom->add_textarea_element('- ' . $note_r['note'], array('x' => 6, 'y' => ($table_proper_height + 14 + $estimated_height), 'font-size' => (self::$c['size']['key'] - 1), 'fill' => self::$c['color']['background'], 'text-anchor' => 'start'), $estimated_height);
			}
		}
	}
	public function render_graph_finish()
	{
		return true;
	}
}

?>
