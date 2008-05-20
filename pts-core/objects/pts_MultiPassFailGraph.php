<?php

/*
	Phoronix Test Suite "Trondheim"
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts_PassFailGraph.php: An abstract graph object extending pts_Graph for showing results in a pass/fail scenario.
*/

class pts_MultiPassFailGraph extends pts_CustomGraph
{
	public function __construct($Title, $SubTitle, $YTitle)
	{
		$this->graph_y_title_hide = TRUE;
		parent::__construct($Title, $SubTitle, $YTitle, true);
		$this->graph_type = "MULTI_PASS_FAIL";
		$this->graph_value_type = "ABSTRACT";
		$this->graph_hide_identifiers = TRUE;
	}
	protected function render_graph_passfail()
	{
		$identifier_count = count($this->graph_identifiers);
		$vertical_border = 18;
		$horizontal_border = 10;
		$graph_width = $this->graph_left_end - $this->graph_left_start - ($horizontal_border * 2);
		$graph_height = $this->graph_top_end - $this->graph_top_start - ($vertical_border * 1.5) - 30;
		$line_height = floor($graph_height / $identifier_count);

		$main_width = floor($graph_width * .4);
		$main_font_size = $this->graph_font_size_bars;
		while(($this->return_ttf_string_width($this->graph_maximum_value, $this->graph_font, $main_font_size) > ($main_width - 20)) || $this->return_ttf_string_height($this->graph_maximum_value, $this->graph_font, $main_font_size) > ($line_height - 4))
			$main_font_size -= 0.5;

		if(($new_size = $this->return_ttf_string_width($this->graph_maximum_value, $this->graph_font, $main_font_size)) < ($main_width - 20))
			$main_width = $new_size + 20;

		$identifiers_total_width = $graph_width - $main_width - 20;

		$headings = explode(",", $this->graph_y_title);
		$identifiers_width = floor($identifiers_total_width / count($headings));
		$headings_font_size = $this->graph_font_size_bars;
		while(($this->return_ttf_string_width($this->find_longest_string($headings), $this->graph_font, $headings_font_size) > ($identifiers_width - 20)) || $this->return_ttf_string_height($this->graph_maximum_value, $this->graph_font, $headings_font_size) > ($line_height - 4))
			$headings_font_size -= 0.5;

		for($i = 0; $i < count($this->graph_identifiers); $i++)
			$this->gd_write_text_right($this->graph_identifiers[$i], $main_font_size, $this->graph_color_headers, $this->graph_left_start + $horizontal_border + $main_width, $this->graph_top_start + $vertical_border + ($i * $line_height) + 30, FALSE, TRUE);

		for($i = 0; $i < count($headings); $i++)
			$this->gd_write_text_center($headings[$i], $headings_font_size, $this->graph_color_headers, $this->graph_left_start + $horizontal_border + 20 + $main_width + ($i * $identifiers_width) + ($identifiers_width / 2), $this->graph_top_start + $vertical_border + 10, FALSE, TRUE);

		for($j = 0; $j < count($this->graph_data[0]); $j++)
		{
			$results = explode(",", $this->graph_data[0][$j]);
			for($i = 0; $i < count($headings) && $i < count($results); $i++)
				$this->gd_write_text_center($results[$i], $headings_font_size, $this->graph_color_headers, $this->graph_left_start + $horizontal_border + 20 + $main_width + ($i * $identifiers_width) + ($identifiers_width / 2), $this->graph_top_start + $vertical_border + ($j * $line_height) + 30, FALSE, TRUE);

		}

	/*
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

				imagerectangle($this->graph_image, $this_horizontal_start, $this_vertical_start, $this_horizontal_end, $this_vertical_end, $this->graph_color_body_light);
				imagefilledrectangle($this->graph_image, $this_horizontal_start + 1, $this_vertical_start + 1, $this_horizontal_end - 1, $this_vertical_end - 1, $paint_color);

				$this->gd_write_text_center($this_identifier, $font_size, $this->graph_color_body_text, $this_horizontal_start + (($this_horizontal_end - $this_horizontal_start) / 2), $this_vertical_start + (($this_vertical_end - $this_vertical_start) / 2) - ($this->return_ttf_string_height($this_identifier, $this->graph_font, $font_size) / 2));
			}
		}*/
	}
	protected function render_graph_result()
	{
		$this->render_graph_passfail();
	}
}

?>
