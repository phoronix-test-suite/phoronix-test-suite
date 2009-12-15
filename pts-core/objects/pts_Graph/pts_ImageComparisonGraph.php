<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel
	pts_ImageComparisonGraph.php: A graph object for image comparisons

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

class pts_ImageComparisonGraph extends pts_CustomGraph
{
	public function __construct($title, $sub_title, $y_axis_title)
	{
		// $y_axis_title is not used with this graph type
		parent::__construct($title, $sub_title, null);
		$this->graph_type = "IMAGE_COMPARISON";
		$this->graph_value_type = "ABSTRACT";
		$this->graph_hide_identifiers = true;
		$this->graph_data_title = array("PASSED", "FAILED");
	}
	protected function render_graph_pre_init()
	{
		if(!function_exists("imagecreatefromstring"))
		{
			echo "\nCurrently you must have PHP-GD installed to utilize this feature.\n";
			return false;
		}

		// Do some common work to this object
		$draw_count = count($this->graph_identifiers);
		$img_first = imagecreatefromstring(base64_decode($this->graph_data[0][0]));
		$img_width = imagesx($img_first);
		$img_height = imagesy($img_first);

		// Assume if the images are being rendered together they are same width and height
		$this->graph_attr_height = 72 + ($draw_count * ($img_height + 22)); // 110 at top plus 20 px between images
		$this->graph_attr_width = $this->graph_attr_width < ($img_width + 20) ? $img_width + 20 : $this->graph_attr_width;

		$this->update_graph_dimensions($this->graph_attr_width, $this->graph_attr_height);
	}
	public function renderGraph()
	{
		if(!function_exists("imagecreatefromstring"))
		{
			echo "\nCurrently you must have PHP-GD installed to utilize this feature.\n";
			return false;
		}

		$this->render_graph_pre_init();
		$this->render_graph_init();
		$this->render_graph_heading(false);

		$img_first = imagecreatefromstring(base64_decode($this->graph_data[0][0]));
		$img_width = imagesx($img_first);
		$img_height = imagesy($img_first);
		unset($img_first);

		$draw_count = count($this->graph_identifiers);

		for($i_o = 0; $i_o < $draw_count; $i_o++)
		{
			$from_left = ($this->graph_attr_width / 2) - ($img_width / 2);
			$from_top = 60 + ($i_o * ($img_height + 22));

			$this->graph_image->draw_rectangle_border($from_left - 1, $from_top - 1, $from_left + $img_width, $from_top + $img_height, $this->graph_color_body_light);
			$this->graph_image->image_copy_merge(imagecreatefromstring(base64_decode($this->graph_data[0][$i_o])), $from_left, $from_top, 0, 0, $img_width, $img_height);

			$this->graph_image->write_text_center($this->graph_identifiers[$i_o], $this->graph_font, $this->graph_font_size_bars, $this->graph_color_main_headers, 0, $from_top + $img_height + 3, $this->graph_attr_width, $from_top + $img_height + 3);
		}

		if(!empty($this->graph_watermark_text))
		{
			$this->graph_image->write_text_center($this->graph_watermark_text, $this->graph_font, 10, $this->graph_color_text, 0, $this->graph_attr_height - 15, $this->graph_attr_width, $this->graph_attr_height - 15);
		}

		return $this->return_graph_image(100);
	}
}

?>
