<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts_LineGraph.php: The line graph object that extends pts_Graph.php.

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

class pts_LineGraph extends pts_CustomGraph
{
	var $identifier_width = -1;

	public function __construct($Title, $SubTitle, $YTitle)
	{
		parent::__construct($Title, $SubTitle, $YTitle);
		$this->graph_type = "LINE_GRAPH";
	}
	protected function render_graph_pre_init()
	{
		// Do some common work to this object
		$identifier_count = count($this->graph_identifiers) + 1;
		$this->identifier_width = ($this->graph_left_end - $this->graph_left_start) / $identifier_count;

		$longest_string = $this->find_longest_string($this->graph_identifiers);

		while($this->return_ttf_string_width($longest_string, $this->graph_font, $this->graph_font_size_identifiers) > ($this->identifier_width - 2) && $this->graph_font_size_identifiers > 6)
		{
			$this->graph_font_size_identifiers -= 0.5;
		}

		if($this->graph_font_size_identifiers == 6)
		{
			$this->update_graph_dimensions($this->graph_attr_width, $this->graph_attr_height + $this->return_ttf_string_width($longest_string, $this->graph_font, 9));
		}
	}
	protected function render_graph_identifiers()
	{
		$px_from_top_start = $this->graph_top_end - 5;
		$px_from_top_end = $this->graph_top_end + 5;

		for($i = 0; $i < count($this->graph_identifiers); $i++)
		{
			$px_from_left = $this->graph_left_start + ($this->identifier_width * ($i + 1));

			imageline($this->graph_image, $px_from_left, $px_from_top_start, $px_from_left, $px_from_top_end, $this->graph_color_notches);

			if($this->graph_font_size_identifiers == 6)
				$this->gd_write_text_left($this->graph_identifiers[$i], 9, $this->graph_color_headers, $px_from_left, $px_from_top_end + 2, TRUE);
			else
				$this->gd_write_text_center($this->graph_identifiers[$i], $this->graph_font_size_identifiers, $this->graph_color_headers, $px_from_left, $px_from_top_end + 2);
		}
	}
	protected function renderGraphLines()
	{
		for($i_o = 0; $i_o < count($this->graph_data); $i_o++)
		{
			$previous_placement = -1;
			$previous_offset = -1;
			$paint_color = $this->next_paint_color();

			$point_counter = count($this->graph_data[$i_o]);
			for($i = 0; $i < $point_counter; $i++)
			{
				$value = $this->graph_data[$i_o][$i];
				$value_plot_top = $this->graph_top_end + 1 - round(($value / $this->graph_maximum_value) * ($this->graph_top_end - $this->graph_top_start));
				$px_from_left = $this->graph_left_start + ($this->identifier_width * ($i + 1));

				if($px_from_left > $this->graph_left_end)
					$px_from_left = $this->graph_left_end - 1;

				if($value_plot_top <= $this->graph_top_end)
					$value_plot_top = $this->graph_top_end + 1;

				if($previous_placement != -1 && $previous_offset != -1)
				{
					imageline($this->graph_image, $previous_offset, $previous_placement, $px_from_left, $value_plot_top, $paint_color);
					imageline($this->graph_image, $previous_offset, $previous_placement + 1, $px_from_left, $value_plot_top + 1, $paint_color);
				}

				if($i == 0)
				{
					imageline($this->graph_image, $this->graph_left_start + 1, $value_plot_top, $px_from_left, $value_plot_top, $paint_color);
					imageline($this->graph_image, $this->graph_left_start + 1, $value_plot_top + 1, $px_from_left, $value_plot_top + 1, $paint_color);
				}
				else if($i == ($point_counter - 1))
				{
					imageline($this->graph_image, $px_from_left, $value_plot_top, $this->graph_left_end - 1, $value_plot_top, $paint_color);
					imageline($this->graph_image, $px_from_left, $value_plot_top + 1, $this->graph_left_end - 1, $value_plot_top + 1, $paint_color);
				}

				$previous_placement = $value_plot_top;
				$previous_offset = $px_from_left;
			}

			if($point_counter < 14)
			{
				$previous_placement = -1;
				$previous_offset = -1;

				for($i = 0; $i < $point_counter; $i++)
				{
					$value = $this->graph_data[$i_o][$i];
					$value_plot_top = $this->graph_top_end + 1 - round(($value / $this->graph_maximum_value) * ($this->graph_top_end - $this->graph_top_start));
					$px_from_left = $this->graph_left_start + ($this->identifier_width * ($i + 1));

					$this->render_graph_pointer($px_from_left, $value_plot_top);

					$previous_placement = $value_plot_top;
					$previous_offset = $px_from_left;
				}
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
		imagefilledrectangle($this->graph_image, $x - 2, $y - 2, $x + 3, $y + 3, $this->graph_color_notches);
	}
}

?>
