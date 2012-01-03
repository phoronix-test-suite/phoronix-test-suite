<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2011, Phoronix Media
	Copyright (C) 2008 - 2011, Michael Larabel
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

class pts_PassFailGraph extends pts_Graph
{
	public function __construct(&$result_object, &$result_file = null)
	{
		parent::__construct($result_object, $result_file);
		$this->graph_value_type = "ABSTRACT";
		$this->graph_hide_identifiers = true;
		$this->graph_data_title = array("PASSED", "FAILED");
	}
	protected function render_graph_passfail()
	{
		$identifier_count = count($this->graph_identifiers);
		$vertical_border = 18;
		$horizontal_border = 10;
		$spacing = 8;
		$columns = 1;
		$graph_width = $this->graph_left_end - $this->graph_left_start - ($horizontal_border * 2);
		$graph_height = $this->graph_top_end - $this->graph_top_start - ($vertical_border * 1.5);
		$font_size = $this->graph_font_size_bars * 1.5;

		$pass_color = $this->get_paint_color('PASS');
		$fail_color = $this->get_paint_color('FAIL');

		for($i = 2; $i <= sqrt($identifier_count); $i++)
		{
			if(intval($identifier_count / $i) == ($identifier_count / $i))
			{
				$columns = $i;
			}
		}

		$identifiers_per_column = $identifier_count / $columns;
		$identifier_height = floor(($graph_height - (($identifiers_per_column - 1) * $spacing)) / $identifiers_per_column);
		$identifier_width = floor(($graph_width - (($columns - 1) * $spacing)) / $columns);

		$width = $identifier_width - 8;
		$height = $line_height - 4;
		$main_font_size = $this->text_size_bounds($this->graph_maximum_value, $this->graph_font, $font_size, 4, $width, $height);

		for($c = 0; $c < $columns; $c++)
		{
			for($i = 0; $i < $identifiers_per_column; $i++)
			{
				$element_i = ($c * $identifiers_per_column) + $i;
				$this_identifier = $this->graph_identifiers[$element_i];
				$this_value = $this->graph_data[0][$element_i];

				$this_x_start = $this->graph_left_start + $horizontal_border + ($c * ($identifier_width + $spacing));
				$this_x_end = $this->graph_left_start + $horizontal_border + ($c * ($identifier_width + $spacing)) + $identifier_width;
				$this_y_start = $this->graph_top_start + $vertical_border + ($i * ($identifier_height + $spacing));
				$this_y_end = $this->graph_top_start + $vertical_border + ($i * ($identifier_height + $spacing)) + $identifier_height;

				if($this_value == "PASS")
				{
					$paint_color = $pass_color;
				}
				else
				{
					$paint_color = $fail_color;
				}

				$this->svg_dom->add_element('rect', array('x' => $this_x_start, 'y' => $this_y_start, 'width' => $identifier_width, 'height' => $identifier_height, 'fill' => $paint_color, 'stroke' => $this->graph_color_body_light, 'stroke-width' => 1));
				$x = $this_x_start + (($this_x_end - $this_x_start) / 2);
				$y = $this_y_start + (($this_y_end - $this_y_start) / 2);
				$this->svg_dom->add_text_element($this_identifier, array('x' => $x, 'y' => $y, 'font-size' => $font_size, 'fill' => $this->graph_color_body_text, 'text-anchor' => 'middle', 'dominant-baseline' => 'middle'));
			}
		}
	}
	protected function render_graph_result()
	{
		$this->render_graph_passfail();
	}
}

?>
