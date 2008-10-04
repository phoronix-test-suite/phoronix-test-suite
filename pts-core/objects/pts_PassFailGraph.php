<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
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

class pts_PassFailGraph extends pts_CustomGraph
{
	public function __construct($Title, $SubTitle, $YTitle)
	{
		parent::__construct($Title, $SubTitle, $YTitle);
		$this->graph_type = "PASS_FAIL";
		$this->graph_value_type = "ABSTRACT";
		$this->graph_hide_identifiers = TRUE;
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

		$pass_color = $this->next_paint_color();
		$fail_color = $this->next_paint_color();

		for($i = 2; $i <= sqrt($identifier_count); $i++)
			if(intval($identifier_count / $i) == ($identifier_count / $i))
				$columns = $i;

		$identifiers_per_column = $identifier_count / $columns;
		$identifier_height = floor(($graph_height - (($identifiers_per_column - 1) * $spacing)) / $identifiers_per_column);
		$identifier_width = floor(($graph_width - (($columns - 1) * $spacing)) / $columns);

		while($this->return_ttf_string_width($this->graph_maximum_value, $this->graph_font, $font_size) > ($identifier_width - 8) || $this->return_ttf_string_height($this->graph_maximum_value, $this->graph_font, $font_size) > ($identifier_height - 4))
			$font_size -= 0.5;

		for($c = 0; $c < $columns; $c++)
		{
			for($i = 0; $i < $identifiers_per_column; $i++)
			{
				$element_i = ($c * $identifiers_per_column) + $i;
				$this_identifier = $this->graph_identifiers[$element_i];
				$this_value = $this->graph_data[0][$element_i];

				$this_horizontal_start = $this->graph_left_start + $horizontal_border + ($c * ($identifier_width + $spacing));
				$this_horizontal_end = $this->graph_left_start + $horizontal_border + ($c * ($identifier_width + $spacing)) + $identifier_width;
				$this_vertical_start = $this->graph_top_start + $vertical_border + ($i * ($identifier_height + $spacing));
				$this_vertical_end = $this->graph_top_start + $vertical_border + ($i * ($identifier_height + $spacing)) + $identifier_height;

				if($this_value == "PASS")
					$paint_color = $pass_color;
				else
					$paint_color = $fail_color;

				$this->draw_rectangle_border($this->graph_image, $this_horizontal_start, $this_vertical_start, $this_horizontal_end, $this_vertical_end, $this->graph_color_body_light);
				$this->draw_rectangle($this->graph_image, $this_horizontal_start + 1, $this_vertical_start + 1, $this_horizontal_end - 1, $this_vertical_end - 1, $paint_color);

				$this->write_text_center($this_identifier, $font_size, $this->graph_color_body_text, $this_horizontal_start, $this_vertical_start + (($this_vertical_end - $this_vertical_start) / 2) - ($this->return_ttf_string_height($this_identifier, $this->graph_font, $font_size) / 2), $this_horizontal_end, $this_vertical_start + (($this_vertical_end - $this_vertical_start) / 2) - ($this->return_ttf_string_height($this_identifier, $this->graph_font, $font_size) / 2));
			}
		}
	}
	protected function render_graph_result()
	{
		$this->render_graph_passfail();
	}
}

?>
