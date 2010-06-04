<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010, Phoronix Media
	Copyright (C) 2010, Michael Larabel
	pts_PieChart.php: A pie chart object for pts_Graph

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

class pts_PieChart extends pts_Graph
{
	public function __construct(&$result_object, &$result_file = null)
	{
		parent::__construct($result_object, $result_file);
		$this->graph_type = "PIE_CHART";
		$this->graph_value_type = "ABSTRACT";
		$this->graph_hide_identifiers = true;
		//$this->graph_data_title = array("PASSED", "FAILED");
	}
	protected function render_graph_pre_init()
	{
		// Do some common work to this object
		$draw_count = count($this->graph_identifiers);

		$this->graph_attr_width += 100;
		$this->update_graph_dimensions($this->graph_attr_width, $this->graph_attr_height);
	}
	public function renderGraph()
	{

		$this->render_graph_pre_init();
		$this->render_graph_init();
		$this->render_graph_heading(false);

		$pie_slices = count($this->graph_identifiers);
		$pie_total = 0;

		for($i = 0; $i < $pie_slices; $i++)
		{
			$pie_total += $this->graph_data[0][$i];
		}


		$center_x = ($this->graph_attr_width / 2);
		$center_y = ($this->graph_attr_height / 2);
		$radius = $this->graph_attr_width / 8;
		$offset_percent = 0;

		for($i = 0; $i < $pie_slices; $i++)
		{
			$percent = pts_math::set_precision($this->graph_data[0][$i] / $pie_total, 3);
			$this->graph_image->draw_pie_piece($center_x, $center_y, $radius, $offset_percent, $percent, $this->get_paint_color($i), $this->graph_color_border, 2);
			$offset_percent += $percent;
		}

		/*$draw_count = count($this->graph_identifiers);

		for($i_o = 0; $i_o < $draw_count; $i_o++)
		{
			$from_left = ($this->graph_attr_width / 2) - ($img_width / 2);
			$from_top = 60 + ($i_o * ($img_height + 22));

			$this->graph_image->draw_rectangle_border($from_left - 1, $from_top - 1, $from_left + $img_width, $from_top + $img_height, $this->graph_color_body_light);
			$this->graph_image->image_copy_merge(imagecreatefromstring(base64_decode($this->graph_data[0][$i_o])), $from_left, $from_top, 0, 0, $img_width, $img_height);

			$this->graph_image->write_text_center($this->graph_identifiers[$i_o], $this->graph_font, $this->graph_font_size_bars, $this->graph_color_main_headers, 0, $from_top + $img_height + 3, $this->graph_attr_width, $from_top + $img_height + 3);
		}*/

		if(!empty($this->graph_watermark_text))
		{
			$this->graph_image->write_text_center($this->graph_watermark_text, $this->graph_font, 10, $this->graph_color_text, 0, $this->graph_attr_height - 15, $this->graph_attr_width, $this->graph_attr_height - 15);
		}

		return $this->return_graph_image(100);
	}
}

?>
