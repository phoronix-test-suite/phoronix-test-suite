<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts_CustomGraph.php: A pass-through extension extending pts_Graph that over-rides attributes with the PTS user configuration options.

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

class pts_CustomGraph extends pts_Graph
{
	function __construct($Title, $SubTitle, $YTitle)
	{
		if(PTS_MODE == "CLIENT")
		{
			$this->setup_custom_values();
		}

		parent::__construct($Title, $SubTitle, $YTitle);
	}
	private function setup_custom_values()
	{
		$read_config = new pts_graph_config_tandem_XmlReader();

		$this->graph_attr_width = pts_read_graph_config(P_GRAPH_SIZE_WIDTH, null, $read_config); // Graph width
		$this->graph_attr_height = pts_read_graph_config(P_GRAPH_SIZE_HEIGHT, null, $read_config); // Graph height
		$this->graph_attr_big_border = pts_read_graph_config(P_GRAPH_BORDER, null, $read_config) == "TRUE"; // Graph border

		// Colors
		$this->graph_color_notches = pts_read_graph_config(P_GRAPH_COLOR_NOTCHES, null, $read_config); // Color for notches
		$this->graph_color_text = pts_read_graph_config(P_GRAPH_COLOR_TEXT, null, $read_config); // Color for text
		$this->graph_color_border = pts_read_graph_config(P_GRAPH_COLOR_BORDER, null, $read_config); // Color for border (if used)
		$this->graph_color_main_headers = pts_read_graph_config(P_GRAPH_COLOR_MAINHEADERS, null, $read_config); // Color of main text headers
		$this->graph_color_headers = pts_read_graph_config(P_GRAPH_COLOR_HEADERS, null, $read_config); // Color of other headers
		$this->graph_color_background = pts_read_graph_config(P_GRAPH_COLOR_BACKGROUND, null, $read_config); // Color of background
		$this->graph_color_body = pts_read_graph_config(P_GRAPH_COLOR_BODY, null, $read_config); // Color of graph body
		$this->graph_color_body_text = pts_read_graph_config(P_GRAPH_COLOR_BODYTEXT, null, $read_config); // Color of graph body text
		$this->graph_color_body_light = pts_read_graph_config(P_GRAPH_COLOR_ALTERNATE, null, $read_config); // Color of the border around graph bars (if doing a bar graph)

		$this->graph_color_paint = explode(", ", pts_read_graph_config(P_GRAPH_COLOR_PAINT, null, $read_config)); // Colors to use for the bars / lines, one color for each key

		// Text
		$this->graph_watermark_text = pts_read_graph_config(P_GRAPH_WATERMARK, null, $read_config); // watermark
		$this->graph_font = pts_read_graph_config(P_GRAPH_FONT_TYPE, null, $read_config);  // TTF file name
		$this->graph_font_size_heading = pts_read_graph_config(P_GRAPH_FONT_SIZE_HEADERS, null, $read_config); // Font size of headings
		$this->graph_font_size_bars = pts_read_graph_config(P_GRAPH_FONT_SIZE_TEXT, null, $read_config); // Font size for text on the bars/objects
		$this->graph_font_size_identifiers = pts_read_graph_config(P_GRAPH_FONT_SIZE_IDENTIFIERS, null, $read_config); // Font size of identifiers
		$this->graph_font_size_sub_heading = pts_read_graph_config(P_GRAPH_FONT_SIZE_SUBHEADERS, null, $read_config); // Font size of headers
		$this->graph_font_size_axis_heading = pts_read_graph_config(P_GRAPH_FONT_SIZE_AXIS, null, $read_config); // Font size of axis headers

		$this->graph_attr_marks = pts_read_graph_config(P_GRAPH_MARKCOUNT, null, $read_config); // Number of marks to make on vertical axis

		$this->graph_renderer = pts_read_graph_config(P_GRAPH_RENDERER, null, $read_config); // Renderer
	}
}

?>
