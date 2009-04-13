<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel
	pts_Chart.php: A charting object for pts_Graph

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

class pts_Chart
{
	var $renderer;

	var $left_headers_title;
	var $top_headers;
	var $left_headers;
	var $data;

	public function __construct()
	{
		// TODO: better integrate pts_Chart with pts_Graph
	}
	public function loadLeftHeaders($title, $data_r)
	{
		$this->left_headers_title = $title;
		$this->left_headers = $data_r;
	}
	public function loadTopHeaders($data_r)
	{
		$this->top_headers = $data_r;
	}
	public function loadData($data_r)
	{
		$this->data = $data_r;
	}
	public function renderChart($file)
	{
		return;
		if(is_file(PTS_USER_DIR . "graph-config.xml"))
		{
			$f = file_get_contents(PTS_USER_DIR . "graph-config.xml");
		}
		else
		{
			$f = "";
		}

		$read_config = new tandem_XmlReader($f);

		$this->renderer = bilde_renderer::setup_renderer(pts_read_graph_config(P_GRAPH_RENDERER, null, $read_config), 100, 100);

		$font_type = "Sans.ttf";

		$left_header = strlen($r = $this->find_longest_string($this->left_headers)) > strlen($this->left_headers_title) ? $r : $this->left_headers_title;
		$left_header = $this->renderer->soft_text_string_dimensions($left_header, $font_type, 10);
		$top_header = $this->renderer->soft_text_string_dimensions($this->find_longest_string($this->top_headers), $font_type, 10);
		$top_header[1] += 2;

		$width = 1 + $left_header[0] + (($top_header[0]) * count($this->top_headers));
		$height = 8 + $top_header[1] + (($left_header[1] + 2) * count($this->left_headers));

		$this->renderer->resize_image($width, $height);
		$color_black = $this->renderer->convert_hex_to_type("#000000"); // TODO: Integrate with pts_CustomGraph and other graph-config.xml colors
		$color_white = $this->renderer->convert_hex_to_type("#FFFFFF");
		$color_alt = $this->renderer->convert_hex_to_type("#2b6b29");

		$this->renderer->draw_rectangle(0, 0, $width, $height, $color_white);


		$this->renderer->draw_rectangle(0, 0, $left_header[0], $height, $color_alt);
		$this->renderer->draw_rectangle(0, 0, $width, $top_header[1], $color_alt); 
		$this->renderer->write_text_left($this->left_headers_title, $font_type, 10, $color_white, 2, 5, 2 + $left_header[0], 5 + $left_header[1]);

		for($i = 0; $i < count($this->left_headers); $i++)
		{
			$this->renderer->write_text_left($this->left_headers[$i], $font_type, 10, $color_white, 2, (($i + 1) * 20) - 1, 2 + $left_header[0], (($i + 1) * 20) - 1 + $left_header[1]);
		}
		for($i = 0; $i < count($this->top_headers); $i++)
		{
			$this->renderer->write_text_center($this->top_headers[$i], $font_type, 10, $color_white, $left_header[0] + ($i * $top_header[0]), 2, $left_header[0] + (($i + 1) * $top_header[0]), 2);
		}

		for($i = 0; $i < count($this->data); $i++)
		{
			for($j = 0; $j < count($this->data[$i]); $j++)
			{
				$this->renderer->write_text_center($this->data[$i][$j], $font_type, 10, $color_black, $left_header[0] + ($j * $top_header[0]), (($i + 1) * 20) - 1, $left_header[0] + (($j + 1) * $top_header[0]), (($i + 1) * 20) - 1 + $left_header[1]);
			}
			$this->renderer->draw_line($left_header[1], (($i + 1) * 20) - 1 + $left_header[1], $width, (($i + 1) * 20) - 1 + $left_header[1], $color_alt, 1);
		}

		for($i = 0; $i <= count($this->data[0]); $i++)
		{
			$this->renderer->draw_line($left_header[0] + ($i * $top_header[0]), $top_header[1], $left_header[0] + ($i * $top_header[0]), $height, $color_alt, 1);

		}

		$this->renderer->render_to_file($file);
	}

	//
	// Shared Functions
	//


	protected function find_longest_string($string_r)
	{
		$longest_string = "";
		$longest_string_length = 0;

		foreach($string_r as $one_string)
		{
			if(($new_length = strlen($one_string)) > $longest_string_length)
			{
				$longest_string = $one_string;
				$longest_string_length = $new_length;
			}
		}

		return $longest_string;
	}
}

?>
