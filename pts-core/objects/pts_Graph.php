<?php

/*
   Copyright (C) 2008, Michael Larabel.
   Copyright (C) 2008, Phoronix Media.

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

class pts_Graph
{
	// Defaults
	var $graph_attr_marks = 6; // Number of marks to make on vertical axis
	var $graph_attr_width = 580; // Graph width
	var $graph_attr_height = 300; // Graph height
	var $graph_attr_big_border = FALSE; // Border around graph or not

	var $graph_left_start = 27; // Distance in px to start graph from left side
	var $graph_left_end_opp = 10; // Distance in px to end graph from right side
	var $graph_top_start = 59; // Distance in px to start graph from top side
	var $graph_top_end_opp = 22; // Distance in px to end graph from bottom side

	// Colors
	var $graph_color_notches = "#000000"; // Color for notches
	var $graph_color_text = "#000000"; // Color for text
	var $graph_color_border = "#000000"; // Color for border (if used)
	var $graph_color_main_headers = "#2b6b29"; // Color of main text headers
	var $graph_color_headers = "#2b6b29"; // Color of other headers
	var $graph_color_background = "#FFFFFF"; // Color of background
	var $graph_color_body = "#8b8f7c"; // Color of graph body
	var $graph_color_body_text = "#FFFFFF"; // Color of graph body text
	var $graph_color_body_light = "#B0B59E"; // Color of the border around graph bars (if doing a bar graph)

	var $graph_color_paint = array("#3B433A", "#BB2413", "#FF9933", "#006C00", "#5028CA"); // Colors to use for the bars / lines, one color for each key

	// Text
	var $graph_font = "DejaVuSans.ttf"; // TTF file name
	var $graph_font_size_tick_mark = 10; // Tick mark size
	var $graph_font_size_key = 9; // Size of height for keys
	var $graph_font_size_heading = 18; // Font size of headings
	var $graph_font_size_bars = 12; // Font size for text on the bars/objects
	var $graph_font_size_identifiers = 11; // Font size of identifiers
	var $graph_font_size_sub_heading = 12; // Font size of headers
	var $graph_font_size_axis_heading = 11; // Font size of axis headers
	var $graph_watermark_text = "PHORONIX-TEST-SUITE.COM"; // Text for watermark in upper right hand corner. If null, no watermark will display
	var $graph_version = "";
	var $graph_proportion = "";

	// CHANGE DIRECTORY FOR TTF FONT LOCATION INSIDE __construct FUNCTION

	// Not user-friendly changes below this line
	var $graph_body_image = FALSE;
	var $graph_type = "GRAPH";
	var $graph_image;
	var $graph_maximum_value;

	var $graph_output = null;
	var $graph_data = array();
	var $graph_data_title = array();
	var $graph_color_paint_index = -1;
	var $graph_identifiers;
	var $graph_title;
	var $graph_sub_title;
	var $graph_y_title;
	var $graph_top_end;
	var $graph_left_end;

	public function __construct($Title, $SubTitle, $YTitle)
	{
		$this->graph_title = $Title;
		$this->graph_sub_title = $SubTitle;
		$this->graph_y_title = $YTitle;

		$this->graph_top_end = $this->graph_attr_height - $this->graph_top_end_opp;
		$this->graph_left_end = $this->graph_attr_width - $this->graph_left_end_opp;

		// Directory for TTF Fonts
		if(!defined("FONT_DIRECTORY"))
		{
			putenv("GDFONTPATH=" . RESULTS_VIEWER_DIR);
			//putenv("GDFONTPATH=" . getcwd()); // The directory where the TTF font files should be. getcwd() will look in the same directory as this file
		}
		else
		{
			putenv("GDFONTPATH=" . FONT_DIRECTORY);
		}
	}

	//
	// Load Functions
	//

	public function loadGraphIdentifiers($data_array)
	{
		$this->graph_identifiers = $data_array;
	}
	public function loadGraphVersion($data)
	{
		if(!empty($data))
			$this->graph_version = "Phoronix Test Suite v" . $data;
	}
	public function loadGraphProportion($data)
	{
		if($data == "LIB")
			$this->graph_proportion = "* Less is better";
		//else if($data == "HIB")
		//	$this->graph_proportion = "* More is better";
	}
	public function loadGraphData($data_array)
	{
		loadGraphValues($data_array);
	}
	public function loadGraphValues($data_array, $data_title = NULL)
	{
		for($i = 0; $i < count($data_array); $i++)
			if(is_float($data_array[$i]))
				$data_array[$i] = bcdiv($data_array[$i], 1, 2);

		array_push($this->graph_data, $data_array);
		array_push($this->graph_data_title, $data_title);
	}
	public function setGraphBackgroundPNG($File)
	{
		$IMG = @imagecreatefrompng($File);

		if($IMG != FALSE)
			$this->graph_body_image = $IMG;
	}

	//
	// Misc Functions
	//

	protected function next_paint_color()
	{
		if($this->graph_color_paint_index + 1 < count($this->graph_color_paint))
		{
			$this->graph_color_paint_index += 1;
		}
		else
		{
			$this->graph_color_paint_index = 0;
		}

		return $this->graph_color_paint[$this->graph_color_paint_index];
	}
	protected function reset_paint_index()
	{
		$this->graph_color_paint_index = -1;
	}
	protected function maximum_graph_value()
	{
		$maximum = $this->graph_attr_marks;

		foreach($this->graph_data as $graph_set)
			foreach($graph_set as $set_item)
				if($set_item > $maximum)
					$maximum = $set_item;

		return $maximum;
	}
	protected function convert_hex_to_gd($Hex)
	{
		return imagecolorallocate($this->graph_image, hexdec(substr($Hex, 1, 2)), hexdec(substr($Hex, 3, 2)), hexdec(substr($Hex, 5, 2)));
	}
	protected function return_ttf_string_dimensions($String, $Font, $Size, $Big = FALSE)
	{
		$box_array = imagettfbbox($Size, 0, $Font, $String);
		$box_width = $box_array[4] - $box_array[6];

		if($Big)
			$box_array = imagettfbbox($Size, 0, $Font, "AZ@![]()@|");
		$box_height = $box_array[1] - $box_array[7];

		// Width x Height
		return array($box_width, $box_height);
	}
	protected function return_ttf_string_width($String, $Font, $Size)
	{
		$dimensions = $this->return_ttf_string_dimensions($String, $Font, $Size);
		return $dimensions[0];
	}
	protected function gd_write_text_center($String, $Size, $Color, $CenterX, $CenterY, $Rotate = FALSE, $Big = FALSE)
	{
		if(empty($String))
			return;

		$Font = $this->graph_font;

		$ttf_dimensions = $this->return_ttf_string_dimensions(strtoupper($String), $Font, $Size, $Big);
		$ttf_height = $ttf_dimensions[1];

		$ttf_dimensions = $this->return_ttf_string_dimensions($String, $Font, $Size, $Big);
		$ttf_width = $ttf_dimensions[0];

		if($CenterX == "TRUE_CENTER")
			$CenterX = $this->graph_attr_width / 2;
		else if($CenterX == "GRAPH_CENTER")
			$CenterX = $this->return_graph_x_center();

		if($Rotate == FALSE)
		{
			$Rotation = 0;
			$text_x = $CenterX - round($ttf_width / 2);
			$text_y = $CenterY + $ttf_height;
		}
		else
		{
			$Rotation = 90;
			$text_x = $CenterX + $ttf_height;
			$text_y = $CenterY + round($ttf_width / 2);
		}

		imagettftext($this->graph_image, $Size, $Rotation, $text_x, $text_y, $Color, $Font, $String);
	}
	protected function find_longest_string($arr_string)
	{
		$longest_string = "";
		$px_length = 0;

		foreach($arr_string as $one_string)
			if(($new_length = strlen($one_string)) > strlen($longest_string))
			{
				$longest_string = $one_string;
				$px_length = $new_length;
			}
		return $longest_string;
	}
	protected function return_graph_x_center()
	{
		return $this->graph_left_start + (($this->graph_left_end - $this->graph_left_start) / 2);
	}
	protected function gd_write_text_right($String, $Size, $Color, $RightX, $CenterY, $Rotate = FALSE)
	{
		if(empty($String))
			return;

		$Font = $this->graph_font;

		$ttf_dimensions = $this->return_ttf_string_dimensions($String, $Font, $Size);

		$ttf_width = $ttf_dimensions[0];
		$ttf_height = $ttf_dimensions[1];

		$Rotation = 0;
		$text_x = $RightX - $ttf_width;
		$text_y = $CenterY + round($ttf_height / 2);

		imagettftext($this->graph_image, $Size, $Rotation, $text_x, $text_y, $Color, $Font, $String);
	}
	protected function gd_write_text_left($String, $Size, $Color, $LeftX, $CenterY, $Rotate = FALSE)
	{
		if(empty($String))
			return;

		$Font = $this->graph_font;

		$ttf_dimensions = $this->return_ttf_string_dimensions($String, $Font, $Size);

		$ttf_width = $ttf_dimensions[0];
		$ttf_height = $ttf_dimensions[1];

		$Rotation = 0;
		$text_x = $LeftX;
		$text_y = $CenterY + round($ttf_height / 2);

		imagettftext($this->graph_image, $Size, $Rotation, $text_x, $text_y, $Color, $Font, $String);
	}

	//
	// Render Functions
	//

	protected function render_graph_init()
	{
		$this->graph_image = imagecreate($this->graph_attr_width, $this->graph_attr_height);
		//imageantialias($this->graph_image, true);
		imageinterlace($this->graph_image, true);

		// Initalize GD Colors

		$this->graph_color_notches = $this->convert_hex_to_gd($this->graph_color_notches);
		$this->graph_color_text = $this->convert_hex_to_gd($this->graph_color_text);
		$this->graph_color_border = $this->convert_hex_to_gd($this->graph_color_border);
		$this->graph_color_main_headers = $this->convert_hex_to_gd($this->graph_color_main_headers);
		$this->graph_color_headers = $this->convert_hex_to_gd($this->graph_color_headers);
		$this->graph_color_background = $this->convert_hex_to_gd($this->graph_color_background);
		$this->graph_color_body = $this->convert_hex_to_gd($this->graph_color_body);
		$this->graph_color_body_text = $this->convert_hex_to_gd($this->graph_color_body_text);
		$this->graph_color_body_light = $this->convert_hex_to_gd($this->graph_color_body_light);

		for($i = 0; $i < count($this->graph_color_paint); $i++)
			$this->graph_color_paint[$i] = $this->convert_hex_to_gd($this->graph_color_paint[$i]);

		// Background Color
		imagefilledrectangle($this->graph_image, 0, 0, $this->graph_attr_width, $this->graph_attr_height, $this->graph_color_background);

		if($this->graph_attr_big_border == TRUE)
			imagerectangle($this->graph_image, 0, 0, $this->graph_attr_width - 1, $this->graph_attr_height - 1, $this->graph_color_border);

		// Etc
		$this->graph_maximum_value = (floor(round($this->maximum_graph_value() * 1.35) / $this->graph_attr_marks) + 1) * $this->graph_attr_marks;
	}
	protected function render_graph_base()
	{
		if(count($this->graph_data_title) > 1)
		{
			$num_key_lines = ceil(count($this->graph_data_title) / 4);
			$this->graph_top_start = $this->graph_top_start + ($num_key_lines * 10);
		}

		// Make room for tick markings, left hand side
		$this->graph_left_start += $this->return_ttf_string_width($this->graph_maximum_value, $this->graph_font, $this->graph_font_size_tick_mark) + 2;

		imagefilledrectangle($this->graph_image, $this->graph_left_start, $this->graph_top_start, $this->graph_left_end, $this->graph_top_end, $this->graph_color_body);
		imagerectangle($this->graph_image, $this->graph_left_start, $this->graph_top_start, $this->graph_left_end, $this->graph_top_end, $this->graph_color_border);

		if($this->graph_body_image != FALSE)
		{
			imagecopymerge($this->graph_image, $this->graph_body_image, $this->graph_left_start + (($this->graph_left_end - $this->graph_left_start) / 2) - imagesx($this->graph_body_image) / 2, $this->graph_top_start + (($this->graph_top_end - $this->graph_top_start) / 2) - imagesy($this->graph_body_image) / 2, 0, 0, imagesx($this->graph_body_image), imagesy($this->graph_body_image), 95);

		}
		
		// Text
		$this->gd_write_text_left($this->graph_proportion, 7, $this->graph_color_body_light, $this->graph_left_start + 1, $this->graph_top_start - 6);
		$this->gd_write_text_right($this->graph_version, 7, $this->graph_color_body_light, $this->graph_left_end - 2, $this->graph_top_start - 6);
		$this->gd_write_text_center($this->graph_title, $this->graph_font_size_heading, $this->graph_color_main_headers, "GRAPH_CENTER", 6);
		$this->gd_write_text_center($this->graph_sub_title, $this->graph_font_size_sub_heading, $this->graph_color_main_headers, "GRAPH_CENTER", 36);
		$this->gd_write_text_center($this->graph_y_title, $this->graph_font_size_axis_heading, $this->graph_color_headers, 4, $this->graph_top_start + (($this->graph_top_end - $this->graph_top_start) / 2), TRUE);
	}
	protected function render_graph_value_ticks()
	{
		$tick_width = ($this->graph_top_end - $this->graph_top_start) / $this->graph_attr_marks;
		$px_from_left_start = $this->graph_left_start - 5;
		$px_from_left_end = $this->graph_left_start + 5;

		$display_value = 0;

		for($i = 0; $i < $this->graph_attr_marks; $i++)
		{
			$px_from_top = $this->graph_top_end - ($tick_width * $i);

			imageline($this->graph_image, $px_from_left_start, $px_from_top, $px_from_left_end, $px_from_top, $this->graph_color_notches);

			$this->gd_write_text_right($display_value, $this->graph_font_size_tick_mark, $this->graph_color_text, $px_from_left_start - 3, $px_from_top);

			if($i != 0 && $this->graph_type == "LINE_GRAPH")
			{
				$line_width = 6;
				for($y = $px_from_left_end + $line_width; $y < $this->graph_left_end; $y += ($line_width * 2))
					if($y + $line_width < $this->graph_left_end)
						imageline($this->graph_image, $y, $px_from_top, $y += $line_width, $px_from_top, $this->graph_color_body_light);
					else
						imageline($this->graph_image, $y, $px_from_top, $y += ($this->graph_left_end - $y) - 1, $px_from_top, $this->graph_color_body_light);
			}

			$display_value += bcdiv($this->graph_maximum_value / $this->graph_attr_marks, 1, 2);
		}
	}
	public function renderGraph()
	{
		$this->render_graph_init();
		$this->render_graph_base();
		$this->render_graph_identifiers();
		$this->render_graph_value_ticks();
		$this->render_graph_key();
		$this->render_graph_result();
		$this->render_graph_watermark();
		$this->return_graph_image();
	}
	protected function render_graph_identifiers()
	{
		return;
	}
	protected function render_graph_result()
	{
		return;
	}
	protected function render_graph_key()
	{
		if(count($this->graph_data_title) < 2)
			return;

		$key_counter = 0;
		$component_y = $this->graph_top_start - 11;
		$this->reset_paint_index();

		for($i = 0; $i < count($this->graph_data_title); $i++)
		{
			if(!empty($this->graph_data_title))
			{
				$this_color = $this->next_paint_color();
				$key_counter += 1;
				$key_offset = $key_counter % 4;

				$component_x = $this->graph_left_start + 15 + (($this->graph_left_end - $this->graph_left_start) / 4) * $key_offset;

				$this->gd_write_text_left($this->graph_data_title[$i], $this->graph_font_size_key, $this_color, $component_x, $component_y);

				imagefilledrectangle($this->graph_image, $component_x - 13, $component_y - 5, $component_x - 3, $component_y + 5, $this_color);
				imagerectangle($this->graph_image, $component_x - 13, $component_y - 5, $component_x - 3, $component_y + 5, $this->graph_color_notches);

				if($key_counter % 4 == 0)
					$component_y -= 12;
			}
		}
		$this->reset_paint_index();
	}
	protected function render_graph_watermark()
	{
		if(empty($this->graph_watermark_text))
			return;

		$this->gd_write_text_right($this->graph_watermark_text, 10, $this->graph_color_text, $this->graph_left_end - 2,  $this->graph_top_start + 8);

	}
	protected function return_graph_image()
	{
		Imagepng($this->graph_image, $this->graph_output, 0);
		ImageDestroy($this->graph_image);
	}
	public function save_graph($file)
	{
		$this->graph_output = $file;
	}
}

?>
