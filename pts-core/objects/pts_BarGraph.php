<?php

/*
	Phoronix Test Suite "Trondheim"
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts_BarGraph.php: The bar graph object that extends pts_Graph.php.
*/

class pts_BarGraph extends pts_CustomGraph
{
	public function __construct($Title, $SubTitle, $YTitle)
	{
		parent::__construct($Title, $SubTitle, $YTitle);
		$this->graph_type = "BAR_GRAPH";
	}
	protected function render_graph_identifiers()
	{
		$identifier_count = count($this->graph_identifiers);
		$graph_width = $this->graph_left_end - $this->graph_left_start;
		$identifier_width = ($this->graph_left_end - $this->graph_left_start) / $identifier_count;

		$px_from_top_start = $this->graph_top_end - 5;
		$px_from_top_end = $this->graph_top_end + 5;

		$longest_string = $this->find_longest_string($this->graph_identifiers);
		$font_size = $this->graph_font_size_identifiers;

		while($this->return_ttf_string_width($longest_string, $this->graph_font, $font_size) > ($identifier_width - 3))
			$font_size -= 0.5;

		for($i = 0; $i < $identifier_count; $i++)
		{
			$px_bound_left = $this->graph_left_start + ($identifier_width * $i);
			$px_bound_right = $this->graph_left_start + ($identifier_width * ($i + 1));

			if($i == 0)
				imageline($this->graph_image, $px_bound_left, $px_from_top_start, $px_bound_left, $px_from_top_end, $this->graph_color_notches);

			imageline($this->graph_image, $px_bound_right, $px_from_top_start, $px_bound_right, $px_from_top_end, $this->graph_color_notches);

			$this->gd_write_text_center($this->graph_identifiers[$i], $font_size, $this->graph_color_headers, $px_bound_left + ceil($identifier_width / 2), $px_from_top_end - 5, FALSE, TRUE);
		}
	}
	protected function render_graph_bars()
	{
		$identifier_count = count($this->graph_identifiers) + 1;
		$graph_width = $this->graph_left_end - $this->graph_left_start;
		$identifier_width = ($this->graph_left_end - $this->graph_left_start) / $identifier_count;
		$bar_count = count($this->graph_data);
		$bar_width = floor($identifier_width / $bar_count);

		$font_size = $this->graph_font_size_bars;

		while($this->return_ttf_string_width($this->trim_double($this->graph_maximum_value, 3), $this->graph_font, $font_size) > ($bar_width - 6))
			$font_size -= 0.5;

		for($i_o = 0; $i_o < $bar_count; $i_o++)
		{
			$paint_color = $this->next_paint_color();

			for($i = 0; $i < count($this->graph_data[$i_o]); $i++)
			{
				$value = $this->graph_data[$i_o][$i];
				$graph_size = round(($value / $this->graph_maximum_value) * ($this->graph_top_end - $this->graph_top_start));
				$value_plot_top = $this->graph_top_end + 1 - $graph_size;

				$identifier_count = count($this->graph_data[$i_o]);
				$graph_width = $this->graph_left_end - $this->graph_left_start;
				$identifier_width = ($this->graph_left_end - $this->graph_left_start) / $identifier_count;
				$bar_width = floor($identifier_width / $bar_count);
				$px_bound_left = $this->graph_left_start + ($identifier_width * $i)  + ($bar_width * $i_o) + 2;
				$px_bound_right = $this->graph_left_start + ($identifier_width * ($i + 1)) - ($bar_width * ($bar_count - $i_o - 1));

				if($i_o == 0)
					$size_diff_left = 5;
				else
					$size_diff_left = 0;

				if(($i_o + 1) == $bar_count)
					$size_diff_right = 5;
				else
					$size_diff_right = 0;

				if($value_plot_top < 1)
					$value_plot_top = 1;

				imagerectangle($this->graph_image, $px_bound_left + $size_diff_left, $value_plot_top - 1, $px_bound_right - $size_diff_right, $this->graph_top_end - 1, $this->graph_color_body_light);
				imagefilledrectangle($this->graph_image, $px_bound_left + $size_diff_left + 1, $value_plot_top, $px_bound_right - $size_diff_right - 1, $this->graph_top_end - 1, $paint_color);
				$value = $this->trim_double($value, 2);

				if($graph_size > 20)
					$this->gd_write_text_center($this->graph_data[$i_o][$i], $font_size, $this->graph_color_body_text, $px_bound_left + (($px_bound_right - $px_bound_left) / 2), $value_plot_top + 3);
			}
		}
	}
	protected function render_graph_result()
	{
		$this->render_graph_bars();
	}
}

?>
