<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2019, Phoronix Media
	Copyright (C) 2008 - 2019, Michael Larabel
	pts_PassFailGraph.php: An abstract graph object extending pts_Graph for showing results in a pass/fail scenario.

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

class pts_graph_passfail extends pts_graph_core
{
	public function __construct(&$result_object, &$result_file = null, $extra_attributes = null)
	{
		parent::__construct($result_object, $result_file, $extra_attributes);
		$this->i['hide_y_title'] = true;
		$this->i['graph_value_type'] = 'ABSTRACT';
		$this->i['hide_graph_identifiers'] = true;
		$this->i['iveland_view'] = true;
	}
	protected function render_graph_passfail()
	{
		$identifier_count = $this->test_result->test_result_buffer->get_count();
		$vertical_border = 20;
		$horizontal_border = 14;
		$heading_height = 24;
		$graph_width = $this->i['graph_left_end'] - $this->i['left_start'] - ($horizontal_border * 2);
		$graph_height = $this->i['graph_top_end'] - $this->i['top_start'] - ($vertical_border * 2) - $heading_height;
		$line_height = floor($graph_height / $identifier_count);

		$fail_color = $this->get_paint_color('FAIL');
		$pass_color = $this->get_paint_color('PASS');

		$main_width = floor($graph_width * .24);
		$main_font_size = self::$c['size']['bars'];
		$main_greatest_length = $this->test_result->test_result_buffer->get_longest_identifier();

		$width = $main_width - 8;
		$height = $line_height - 4;
		$main_font_size = $this->text_size_bounds($main_greatest_length, $main_font_size, 4, $width, $height);

		if(($new_size = $this->text_string_width($main_greatest_length, $main_font_size)) < ($main_width - 12))
		{
			$main_width = $new_size + 10;
		}

		$identifiers_total_width = $graph_width - $main_width - 2;

		$headings = pts_strings::comma_explode($this->i['graph_y_title']);
		if(empty($headings))
		{
			$headings = array(' ');
		}
		$identifiers_width = floor($identifiers_total_width / count($headings));
		$headings_font_size = self::$c['size']['bars'];
		while(($this->text_string_width(pts_strings::find_longest_string($headings), $headings_font_size) > ($identifiers_width - 2)) || $this->text_string_height($this->i['graph_max_value'], $headings_font_size) > ($line_height - 4))
		{
			$headings_font_size -= 0.5;
		}

		foreach(array_keys($this->test_result->test_result_buffer->buffer_items) as $j)
		{
			$results = array_reverse(pts_strings::comma_explode($this->test_result->test_result_buffer->buffer_items[$j]->get_result_value()));

			$line_ttf_height = $this->text_string_height('AZ@![]()@|_', self::$c['size']['bars']);
			for($i = 0; $i < count($headings) && $i < count($results); $i++)
			{
				$paint_color = $results[$i] == 'PASS' ? $pass_color : $fail_color;
				$this_bottom_end = $this->i['top_start'] + $vertical_border + (($j + 1) * $line_height) + $heading_height + 1;

				if($this_bottom_end >= $this->i['graph_top_end'] - $vertical_border)
				{
					$this_bottom_end = $this->i['graph_top_end'] - $vertical_border - 1;
				}
				else if($j == ($this->test_result->test_result_buffer->get_count() - 1) && $this_bottom_end < $this->i['graph_top_end'] - $vertical_border)
				{
					$this_bottom_end = $this->i['graph_top_end'] - $vertical_border - 1;
				}

				$x = $this->i['graph_left_end'] - $horizontal_border - ($i * $identifiers_width);
				$y = $this->i['top_start'] + $vertical_border + ($j * $line_height) + $heading_height;

				$this->svg_dom->add_element('rect', array('x' => ($x - $identifiers_width), 'y' => $y, 'width' => $identifiers_width, 'height' => ($this_bottom_end - $y), 'fill' => $paint_color, 'stroke' => self::$c['color']['border'], 'stroke-width' => 1));
				$x = $this->i['graph_left_end'] - $horizontal_border - ($i * $identifiers_width) - ($identifiers_width * 0.5);
				$y = $this->i['top_start'] + $vertical_border + ($j * $line_height) + $heading_height + ($line_height / 2) - ($line_ttf_height / 2);
				$this->svg_dom->add_text_element($results[$i], array('x' => $x, 'y' => $y, 'font-size' => self::$c['size']['bars'], 'fill' => self::$c['color']['body_text'], 'text-anchor' => 'middle', 'dominant-baseline' => 'text-before-edge'));
			}
		}

		$headings = array_reverse($headings);
		$line_ttf_height = $this->text_string_height('AZ@![]()@|_', $headings_font_size);
		for($i = 0; $i < count($headings); $i++)
		{
			//$this->svg_dom->draw_svg_line($this->i['graph_left_end'] - $horizontal_border - (($i + 1) * $identifiers_width), $this->i['top_start'] + $vertical_border, $this->i['graph_left_end'] - $horizontal_border - (($i + 1) * $identifiers_width), $this->i['graph_top_end'] - $vertical_border, self::$c['color']['body_light']);
			$x = $this->i['graph_left_end'] - $horizontal_border - ($i * $identifiers_width) - (0.5 * $identifiers_width);
			$y = $this->i['top_start'] + $vertical_border + ($heading_height / 2) - ($line_ttf_height / 2);
			$this->svg_dom->add_text_element($headings[$i], array('x' => $x, 'y' => $y, 'font-size' => $headings_font_size, 'fill' => self::$c['color']['headers'], 'text-anchor' => 'middle', 'dominant-baseline' => 'text-before-edge'));
		}

		$line_ttf_height = $this->text_string_height('AZ@![]()@|_', $main_font_size);
		foreach(array_keys($this->test_result->test_result_buffer->buffer_items) as $i)
		{
			//$this->svg_dom->draw_svg_line($this->i['left_start'] + $horizontal_border, $this->i['top_start'] + $vertical_border + ($i * $line_height) + $heading_height, $this->i['graph_left_end'] - $horizontal_border, $this->i['top_start'] + $vertical_border + ($i * $line_height) + $heading_height, self::$c['color']['body_light']);

			$x = $this->i['left_start'] + $horizontal_border + $main_width;
			$y = $this->i['top_start'] + $vertical_border + ($i * $line_height) + $heading_height + ($line_height / 2) - 2;
			$this->svg_dom->add_text_element($this->test_result->test_result_buffer->buffer_items[$i]->get_result_identifier(), array('x' => $x, 'y' => $y, 'font-size' => $main_font_size, 'fill' => self::$c['color']['headers'], 'text-anchor' => 'end', 'dominant-baseline' => 'middle', 'font-weight' => 'bold'));
		}

		//$this->svg_dom->draw_svg_line($this->i['left_start'] + $horizontal_border, $this->i['top_start'] + $vertical_border, $this->i['graph_left_end'] - $horizontal_border, $this->i['top_start'] + $vertical_border, self::$c['color']['body_light']);
		//$this->svg_dom->draw_svg_line($this->i['left_start'] + $horizontal_border, $this->i['top_start'] + $vertical_border, $this->i['left_start'] + $horizontal_border, $this->i['graph_top_end'] - $vertical_border, self::$c['color']['body_light']);
		//$this->svg_dom->draw_svg_line($this->i['graph_left_end'] - $horizontal_border, $this->i['top_start'] + $vertical_border, $this->i['graph_left_end'] - $horizontal_border, $this->i['graph_top_end'] - $vertical_border, self::$c['color']['body_light']);
		//$this->svg_dom->draw_svg_line($this->i['left_start'] + $horizontal_border, $this->i['graph_top_end'] - $vertical_border, $this->i['graph_left_end'] - $horizontal_border, $this->i['graph_top_end'] - $vertical_border, self::$c['color']['body_light']);
	}
	protected function render_graph_result()
	{
		$this->render_graph_passfail();
	}
}

?>
