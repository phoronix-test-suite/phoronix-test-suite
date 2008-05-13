<?php

/*
	Phoronix Test Suite "Trondheim"
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts_LineGraph.php: The line graph object that extends pts_Graph.php.
*/

class pts_LineGraph extends pts_CustomGraph
{
	public function __construct($Title, $SubTitle, $YTitle)
	{
		parent::__construct($Title, $SubTitle, $YTitle);
		$this->graph_type = "LINE_GRAPH";
	}
	protected function render_graph_identifiers()
	{
		$identifier_count = count($this->graph_identifiers) + 1;
		$graph_width = $this->graph_left_end - $this->graph_left_start;
		$identifier_width = ($this->graph_left_end - $this->graph_left_start) / $identifier_count;

		$px_from_top_start = $this->graph_top_end - 5;
		$px_from_top_end = $this->graph_top_end + 5;

		$longest_string = $this->find_longest_string($this->graph_identifiers);
		$font_size = $this->graph_font_size_identifiers;

		while($this->return_ttf_string_width($longest_string, $this->graph_font, $font_size) > ($identifier_width - 2))
			$font_size -= 0.5;

		for($i = 0; $i < ($identifier_count - 1); $i++)
		{
			$px_from_left = $this->graph_left_start + ($identifier_width * ($i + 1));

			imageline($this->graph_image, $px_from_left, $px_from_top_start, $px_from_left, $px_from_top_end, $this->graph_color_notches);
			$this->gd_write_text_center($this->graph_identifiers[$i], $font_size, $this->graph_color_headers, $px_from_left, $px_from_top_end + 2);
		}
	}
	protected function renderGraphLines()
	{
		$identifier_count = count($this->graph_identifiers) + 1;
		$graph_width = $this->graph_left_end - $this->graph_left_start;
		$identifier_width = ($this->graph_left_end - $this->graph_left_start) / $identifier_count;

		for($i_o = 0; $i_o < count($this->graph_data); $i_o++)
		{
			$previous_placement = -1;
			$previous_offset = -1;
			$paint_color = $this->next_paint_color();

			for($i = 0; $i < count($this->graph_data[$i_o]); $i++)
			{
				$value = $this->graph_data[$i_o][$i];
				$value_plot_top = $this->graph_top_end + 1 - round(($value / $this->graph_maximum_value) * ($this->graph_top_end - $this->graph_top_start));
				$px_from_left = $this->graph_left_start + ($identifier_width * ($i + 1));

				if($previous_placement != -1 && $previous_offset != -1)
				{
					imageline($this->graph_image, $previous_offset, $previous_placement, $px_from_left, $value_plot_top, $paint_color);
					$this->render_graph_pointer($previous_offset, $previous_placement);
				}
				if($i == count($this->graph_data[$i_o]) - 1)
					$this->render_graph_pointer($px_from_left, $value_plot_top);

			$previous_placement = $value_plot_top;
			$previous_offset = $px_from_left;
				
			}
		}
	}
	protected function render_graph_result()
	{
		$this->renderGraphLines();
	}
	protected function render_graph_pointer($x, $y)
	{
		imageline($this->graph_image, $x - 5, $y - 5, $x + 5, $y + 5, $this->graph_color_notches);
		imageline($this->graph_image, $x + 5, $y - 5, $x - 5, $y + 5, $this->graph_color_notches);
		imagefilledrectangle($this->graph_image, $x - 2, $y - 2, $x + 2, $y + 2, $this->graph_color_notches);
	}
}

?>
