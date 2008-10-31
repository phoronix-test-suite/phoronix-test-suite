<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts_Graph.php: The core graph object that is used by the different graphing objects.

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
	var $graph_attr_big_border = false; // Border around graph or not

	var $graph_left_start = 20; // Distance in px to start graph from left side
	var $graph_left_end_opp = 10; // Distance in px to end graph from right side
	var $graph_top_start = 60; // Distance in px to start graph from top side
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
	var $graph_font = "Sans.ttf"; // TTF file name
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
	var $graph_body_image = false;
	var $graph_hide_identifiers = false;
	var $graph_show_key = false;
	var $graph_background_lines = false;
	var $graph_type = "GRAPH";
	var $graph_value_type = "NUMERICAL";
	var $graph_image;
	var $graph_maximum_value;

	var $graph_output = null;
	var $graph_renderer = "PNG";
	var $graph_data = array();
	var $graph_data_title = array();
	var $graph_color_paint_index = -1;
	var $graph_identifiers;
	var $graph_title;
	var $graph_sub_title;
	var $graph_y_title;
	var $graph_y_title_hide = false;
	var $graph_top_end;
	var $graph_left_end;

	var $graph_user_identifiers = array();

	public function __construct($Title, $SubTitle, $YTitle)
	{
		$this->graph_title = $Title;
		$this->graph_sub_title = $SubTitle;
		$this->graph_y_title = $YTitle;

		$this->update_graph_dimensions(-1, -1, true);

		// Directory for TTF Fonts
		if(defined("FONT_DIR"))
		{
			putenv("GDFONTPATH=" . FONT_DIR);
		}
		else if(($font_env = getenv("FONT_DIR")) != false)
		{
			putenv("GDFONTPATH=" . $font_env);
		}
		else
		{
			putenv("GDFONTPATH=" . getcwd());
		}
	}
	public function setRenderer($Renderer)
	{
		if($Renderer == "SVG")
		{		
			$this->graph_renderer = "SVG";
			$this->graph_left_start += 10;
		}
		else
		{
			$this->graph_renderer = "PNG";
		}
	}
	public function getRenderer()
	{
		return $this->graph_renderer;
	}

	//
	// Load Functions
	//

	public function loadGraphIdentifiers($data_array)
	{
		$this->graph_identifiers = $data_array;
	}
	public function hideGraphIdentifiers()
	{
		$this->graph_hide_identifiers = true;
	}
	public function loadGraphVersion($data)
	{
		if(!empty($data))
		{
			$this->graph_version = "Phoronix Test Suite " . $data;
		}
	}
	public function loadGraphProportion($data)
	{
		if($data == "LIB")
		{
			$this->graph_proportion = "Less Is Better";
		}
		//else if($data == "HIB")
		//	$this->graph_proportion = "More Is Better";
	}
	public function loadGraphData($data_array)
	{
		loadGraphValues($data_array);
	}
	public function loadGraphValues($data_array, $data_title = null)
	{
		for($i = 0; $i < count($data_array); $i++)
		{
			if(is_float($data_array[$i]))
			{
				$data_array[$i] = $this->trim_double($data_array[$i], 2);
			}
		}

		array_push($this->graph_data, $data_array);

		if(!empty($data_title))
		{
			array_push($this->graph_data_title, $data_title);
		}
	}
	public function setGraphBackgroundPNG($File)
	{
		$IMG = $this->read_png_image($File);

		if($IMG != false)
		{
			$this->graph_body_image = $IMG;
		}
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
		{
			foreach($graph_set as $set_item)
			{
				if((is_numeric($set_item) && $set_item > $maximum) || (!is_numeric($set_item) && strlen($set_item) > strlen($maximum)))
				{
					$maximum = $set_item;
				}
			}
		}

		if(is_numeric($maximum))
		{
			$maximum = (floor(round($maximum * 1.35) / $this->graph_attr_marks) + 1) * $this->graph_attr_marks;
		}

		return $maximum;
	}
	protected function return_ttf_string_width($String, $Font, $Size)
	{
		$dimensions = $this->return_ttf_string_dimensions($String, $Font, $Size);
		return $dimensions[0];
	}
	protected function return_ttf_string_height($String, $Font, $Size)
	{
		$dimensions = $this->return_ttf_string_dimensions($String, $Font, $Size);
		return $dimensions[1];
	}
	protected function find_longest_string($arr_string)
	{
		$longest_string = "";
		$px_length = 0;

		foreach($arr_string as $one_string)
		{
			if(($new_length = strlen($one_string)) > strlen($longest_string))
			{
				$longest_string = $one_string;
				$px_length = $new_length;
			}
		}

		return $longest_string;
	}
	protected function update_graph_dimensions($width = -1, $height = -1, $recalculate_offsets = false)
	{
		// Allow render area to be increased, but not decreased
		if($width > $this->graph_attr_width)
		{
			$this->graph_attr_width = $width;
		}
		if($height > $this->graph_attr_height)
		{
			$this->graph_attr_height = $height;
		}

		if($recalculate_offsets)
		{
			$this->graph_top_end = $this->graph_attr_height - $this->graph_top_end_opp;
			$this->graph_left_end = $this->graph_attr_width - $this->graph_left_end_opp;
		}
	}

	//
	// Render Functions
	//

	protected function render_graph_pre_init()
	{
		return;
	}
	protected function render_graph_init()
	{
		$this->update_graph_dimensions();
		$this->graph_image = $this->init_blank_image($this->graph_attr_width, $this->graph_attr_height);

		// Initalize Colors

		$this->graph_color_notches = $this->convert_hex_to_type($this->graph_color_notches);
		$this->graph_color_text = $this->convert_hex_to_type($this->graph_color_text);
		$this->graph_color_border = $this->convert_hex_to_type($this->graph_color_border);
		$this->graph_color_main_headers = $this->convert_hex_to_type($this->graph_color_main_headers);
		$this->graph_color_headers = $this->convert_hex_to_type($this->graph_color_headers);
		$this->graph_color_background = $this->convert_hex_to_type($this->graph_color_background);
		$this->graph_color_body = $this->convert_hex_to_type($this->graph_color_body);
		$this->graph_color_body_text = $this->convert_hex_to_type($this->graph_color_body_text);
		$this->graph_color_body_light = $this->convert_hex_to_type($this->graph_color_body_light);

		for($i = 0; $i < count($this->graph_color_paint); $i++)
		{
			$this->graph_color_paint[$i] = $this->convert_hex_to_type($this->graph_color_paint[$i]);
		}

		// Background Color
		$this->draw_rectangle($this->graph_image, 0, 0, $this->graph_attr_width, $this->graph_attr_height, $this->graph_color_background);

		if($this->graph_attr_big_border == true)
		{
			$this->draw_rectangle_border($this->graph_image, 0, 0, $this->graph_attr_width - 1, $this->graph_attr_height - 1, $this->graph_color_border);
		}
	}
	protected function render_graph_base()
	{
		if(count($this->graph_data_title) > 1 || $this->graph_show_key == true)
		{
			$num_key_lines = ceil(count($this->graph_data_title) / 4);
			$this->graph_top_start = $this->graph_top_start + 8 + ($num_key_lines * 11);
		}

		$this->draw_rectangle($this->graph_image, $this->graph_left_start, $this->graph_top_start, $this->graph_left_end, $this->graph_top_end, $this->graph_color_body);
		$this->draw_rectangle_border($this->graph_image, $this->graph_left_start, $this->graph_top_start, $this->graph_left_end, $this->graph_top_end, $this->graph_color_notches);

		if($this->graph_body_image != false && $this->graph_renderer == "PNG")
		{
			imagecopymerge($this->graph_image, $this->graph_body_image, $this->graph_left_start + (($this->graph_left_end - $this->graph_left_start) / 2) - imagesx($this->graph_body_image) / 2, $this->graph_top_start + (($this->graph_top_end - $this->graph_top_start) / 2) - imagesy($this->graph_body_image) / 2, 0, 0, imagesx($this->graph_body_image), imagesy($this->graph_body_image), 95);
		}

		// Text
		$this->write_text_right($this->graph_version, 7, $this->graph_color_body_light, $this->graph_left_end, $this->graph_top_start - 9, $this->graph_left_end, $this->graph_top_start - 9);
		$this->write_text_center($this->graph_title, $this->graph_font_size_heading, $this->graph_color_main_headers, $this->graph_left_start, 4, $this->graph_left_end, 4);
		$this->write_text_center($this->graph_sub_title, $this->graph_font_size_sub_heading, $this->graph_color_main_headers, $this->graph_left_start, 26, $this->graph_left_end, 26, false, true);

		if(!empty($this->graph_y_title) && !$this->graph_y_title_hide)
		{
			$str = $this->graph_y_title;

			if(!empty($this->graph_proportion))
			{
				if(!empty($str))
				{
					$str .= ", ";
				}

				$str .= $this->graph_proportion;
			}

			$this->write_text_left($str, 7, $this->graph_color_main_headers, $this->graph_left_start, $this->graph_top_start - 9, $this->graph_left_start, $this->graph_top_start - 9);
			//$this->write_text_center($this->graph_y_title, $this->graph_font_size_axis_heading, $this->graph_color_headers, 3, $this->graph_top_start, 3, $this->graph_top_end, true);
		}
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

			$this->draw_line($this->graph_image, $px_from_left_start, $px_from_top, $px_from_left_end, $px_from_top, $this->graph_color_notches);

			$this->write_text_right($display_value, $this->graph_font_size_tick_mark, $this->graph_color_text, $px_from_left_start - 1, $px_from_top - 2, $px_from_left_start - 1, $px_from_top - 2);

			if($i != 0 && $this->graph_background_lines == true)
			{
				$line_width = 6;
				for($y = $px_from_left_end + $line_width; $y < $this->graph_left_end; $y += ($line_width * 2))
				{
					if($y + $line_width < $this->graph_left_end)
					{
						$this->draw_line($this->graph_image, $y, $px_from_top, $y += $line_width, $px_from_top, $this->graph_color_body_light);
					}
					else
					{
						$this->draw_line($this->graph_image, $y, $px_from_top, $y += ($this->graph_left_end - $y) - 1, $px_from_top, $this->graph_color_body_light);
					}
				}
			}

			$display_value += $this->trim_double($this->graph_maximum_value / $this->graph_attr_marks, 2);
		}
	}
	public function renderGraph()
	{
		$this->graph_maximum_value = $this->maximum_graph_value();

		// Make room for tick markings, left hand side
		if($this->graph_value_type == "NUMERICAL")
		{
			$this->graph_left_start += $this->return_ttf_string_width($this->graph_maximum_value, $this->graph_font, $this->graph_font_size_tick_mark) + 2;
		}

		if($this->graph_hide_identifiers == true)
		{
			$this->graph_top_end += $this->graph_top_end_opp / 2;
		}

		// Do the actual work
		$this->render_graph_pre_init();
		$this->render_graph_init();
		$this->render_graph_base();

		if(!$this->graph_hide_identifiers)
		{
			$this->render_graph_identifiers();
		}

		if($this->graph_value_type == "NUMERICAL")
		{
			$this->render_graph_value_ticks();
		}

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
		if(count($this->graph_data_title) < 2 && $this->graph_show_key == false)
		{
			return;
		}

		$key_counter = 0;
		$component_y = $this->graph_top_start - 20;
		$this->reset_paint_index();

		for($i = 0; $i < count($this->graph_data_title); $i++)
		{
			if(!empty($this->graph_data_title))
			{
				$this_color = $this->next_paint_color();
				$key_counter += 1;
				$key_offset = $key_counter % 4;

				$component_x = $this->graph_left_start + 15 + (($this->graph_left_end - $this->graph_left_start) / 4) * $key_offset;

				$this->write_text_left($this->graph_data_title[$i], $this->graph_font_size_key, $this_color, $component_x, $component_y, $component_x, $component_y);

				$this->draw_rectangle($this->graph_image, $component_x - 13, $component_y - 5, $component_x - 3, $component_y + 5, $this_color);
				$this->draw_rectangle_border($this->graph_image, $component_x - 13, $component_y - 5, $component_x - 3, $component_y + 5, $this->graph_color_notches);

				if($key_counter % 4 == 0)
				{
					$component_y -= 12;
				}
			}
		}
		$this->reset_paint_index();
	}
	protected function render_graph_watermark()
	{
		if(!empty($this->graph_watermark_text))
		{
			$this->write_text_right($this->graph_watermark_text, 10, $this->graph_color_text, $this->graph_left_end - 2, $this->graph_top_start + 8, $this->graph_left_end - 2, $this->graph_top_start + 8);
		}
	}
	protected function return_graph_image()
	{
		$this->render_image($this->graph_image, $this->graph_output, 5);
		$this->deinit_image($this->graph_image);
	}
	public function save_graph($file)
	{
		$this->graph_output = $file;
	}
	protected function trim_double($double, $accuracy = 2)
	{
		// Set precision for a variable's points after the decimal spot
		$return = explode(".", $double);

		if(count($return) == 1)
		{
			$return[1] = "00";
		}
	
		if(count($return) == 2 && $accuracy > 0)
		{
			$strlen = strlen($return[1]);

			if($strlen > $accuracy)
			{
				$return[1] = substr($return[1], 0, $accuracy);
			}
			else if($strlen < $accuracy)
			{
				for($i = $strlen; $i < $accuracy; $i++)
				{
					$return[1] .= '0';
				}
			}

			$return = $return[0] . "." . $return[1];
		}
		else
		{
			$return = $return[0];
		}

		return $return;
	}
	function add_user_identifier($identifier, $value)
	{
		$this->graph_user_identifiers[$identifier] = $value;
	}


	//
	// Renderer-specific Functions
	//

	protected function init_blank_image($width, $height)
	{
		if($this->graph_renderer == "PNG")
		{
			$img = imagecreate($width, $height);

			imageinterlace($img, true);

			if(function_exists("imageantialias"))
			{
				imageantialias($img, true);
			}
		}
		else if($this->graph_renderer == "SVG")
		{
			$img = "<?xml version=\"1.0\"?>\n<!DOCTYPE svg PUBLIC \"-//W3C//DTD SVG 1.1//EN\" \"http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd\">\n";

			foreach($this->graph_user_identifiers as $key => $value)
			{
				$img .= "<!-- " . $key . ": " . $value . " -->\n";
			}

			$img .= "<svg xmlns=\"http://www.w3.org/2000/svg\" version=\"1.1\" viewbox=\"0 0 " . $width . " " . $height . "\" width=\"" . $width . "\" height=\"" . $height . "\">\n\n";
		}

		return $img;
	}
	protected function render_image(&$img_object, $output_file = null, $quality = 0)
	{
		if($this->graph_renderer == "PNG")
		{
			imagepng($img_object, $output_file, $quality);
		}
		else if($this->graph_renderer == "SVG")
		{
			$img_object .= "\n\n</svg>";

			if($output_file != null)
			{
				@file_put_contents($output_file, $img_object);
			}
		}
	}
	protected function read_png_image($File)
	{
		if($this->graph_renderer == "PNG")
		{
			$img = @imagecreatefrompng($File);
		}
		else
		{
			$img = null;
		}

		return $img;
	}
	protected function deinit_image(&$img_object)
	{
		if($this->graph_renderer == "PNG")
		{
			imagedestroy($img_object);
		}
		else if($this->graph_renderer == "SVG")
		{
			$img_object = null;
		}
	}
	protected function convert_hex_to_type($Hex)
	{
		if($this->graph_renderer == "PNG")
		{
			$color = imagecolorallocate($this->graph_image, hexdec(substr($Hex, 1, 2)), hexdec(substr($Hex, 3, 2)), hexdec(substr($Hex, 5, 2)));
		}
		else if($this->graph_renderer == "SVG")
		{
			$color = $Hex;
		}

		return $color;
	}
	protected function write_text_left($text_string, $font_size, $font_color, $bound_x1, $bound_y1, $bound_x2, $bound_y2, $rotate = false)
	{
		if(empty($text_string))
		{
			return;
		}

		if($this->graph_renderer == "PNG")
		{
			$ttf_dimensions = $this->return_ttf_string_dimensions($text_string, $this->graph_font, $font_size);
			$ttf_width = $ttf_dimensions[0];
			$ttf_height = $ttf_dimensions[1];

			if($rotate == false)
			{
				$text_x = $bound_x1;
				$text_y = $bound_y1 + round($ttf_height / 2);
				$rotation = 0;
			}
			else
			{
				$text_x = $bound_x1 - round($ttf_height / 4);
				$text_y = $bound_y1 + round($ttf_height / 2);
				$rotation = 270;
			}
			imagettftext($this->graph_image, $font_size, $rotation, $text_x, $text_y, $font_color, $this->graph_font, $text_string);
		}
		else if($this->graph_renderer == "SVG")
		{
			$ttf_dimensions = $this->return_ttf_string_dimensions($text_string, $this->graph_font, $font_size);
			$ttf_width = $ttf_dimensions[0];
			$ttf_height = $ttf_dimensions[1];

			if($rotate == false)
			{
				$text_x = $bound_x1;
				$text_y = $bound_y1 + round($ttf_height / 2);
				$rotation = 0;
			}
			else
			{
				$text_x = $bound_x1 - round($ttf_height / 4);
				$text_y = $bound_y1 + round($ttf_height / 2);
				$rotation = 270;
			}
			$this->write_svg_text($this->graph_image, $font_size, $rotation, $text_x, $text_y, $font_color, $this->graph_font, $text_string, "LEFT");
		}
	}
	protected function write_text_right($text_string, $font_size, $font_color, $bound_x1, $bound_y1, $bound_x2, $bound_y2, $rotate_text = false)
	{
		if(empty($text_string))
		{
			return;
		}

		if($this->graph_renderer == "PNG")
		{
			$ttf_dimensions = $this->return_ttf_string_dimensions($text_string, $this->graph_font, $font_size);
			$ttf_width = $ttf_dimensions[0];
			$ttf_height = $ttf_dimensions[1];

			if($rotate_text == false)
			{
				$rotation = 0;
			}
			else
			{
				$rotation = 90;
			}

			$text_x = $bound_x2 - $ttf_width;
			$text_y = $bound_y1 + round($ttf_height / 2);

			imagettftext($this->graph_image, $font_size, $rotation, $text_x, $text_y, $font_color, $this->graph_font, $text_string);
		}
		else if($this->graph_renderer == "SVG")
		{
			$ttf_dimensions = $this->return_ttf_string_dimensions($text_string, $this->graph_font, $font_size);
			$ttf_width = $ttf_dimensions[0];
			$ttf_height = $ttf_dimensions[1];

			$bound_x1 -= 2;
			$bound_x2 -= 2;

			if($rotate_text == false)
			{
				$rotation = 0;
			}
			else
			{
				$rotation = 90;
			}

			$text_x = $bound_x2 - $ttf_width;
			$text_y = $bound_y1 + round($ttf_height / 2);

			$this->write_svg_text($this->graph_image, $font_size, $rotation, $text_x, $text_y, $font_color, $this->graph_font, $text_string, "RIGHT");
		}
	}
	protected function write_text_center($text_string, $font_size, $font_color, $bound_x1, $bound_y1, $bound_x2, $bound_y2, $rotate_text = false, $big_type = false)
	{
		if(empty($text_string))
		{
			return;
		}

		if($this->graph_renderer == "PNG")
		{
			if($bound_x1 != $bound_x2)
			{
				while($this->return_ttf_string_width($this->trim_double($text_string, 2), $this->graph_font, $font_size) > abs($bound_x2 - $bound_x1 - 3))
				{
					$font_size -= 0.5;
				}
			}

			$ttf_dimensions = $this->return_ttf_string_dimensions(strtoupper($text_string), $this->graph_font, $font_size, $big_type);
			$ttf_height = $ttf_dimensions[1];

			$ttf_dimensions = $this->return_ttf_string_dimensions($text_string, $this->graph_font, $font_size, $big_type);
			$ttf_width = $ttf_dimensions[0];

			if($rotate_text == false)
			{
				$rotation = 0;
				$text_x = (($bound_x2 - $bound_x1) / 2) + $bound_x1 - round($ttf_width / 2);
				$text_y = $bound_y1 + $ttf_height;
			}
			else
			{
				$rotation = 90;
				$text_x = $bound_x1 + $ttf_height;
				$text_y = (($bound_y2 - $bound_y1) / 2) + $bound_y1 + round($ttf_width / 2);
			}

			imagettftext($this->graph_image, $font_size, $rotation, $text_x, $text_y, $font_color, $this->graph_font, $text_string);
		}
		else if($this->graph_renderer == "SVG")
		{
		//	$font_size = $this->graph_font_size_bars;
		//	while($this->return_ttf_string_width($this->trim_double($this->graph_maximum_value, 3), $this->graph_font, $font_size) > ($bar_width - 6))
		//		$font_size -= 0.5;

			$ttf_dimensions = $this->return_ttf_string_dimensions(strtoupper($text_string), $this->graph_font, $font_size, $big_type);
			$ttf_height = $ttf_dimensions[1];

			$ttf_dimensions = $this->return_ttf_string_dimensions($text_string, $this->graph_font, $font_size, $big_type);
			$ttf_width = $ttf_dimensions[0];

			if($rotate_text == false)
			{
				$rotation = 0;
				$text_x = (($bound_x2 - $bound_x1) / 2) + $bound_x1 - round($ttf_width / 2);
				$text_y = $bound_y1 + $ttf_height;
			}
			else
			{
				$rotation = 90;
				$text_x = $bound_x1 + $ttf_height;
				$text_y = (($bound_y2 - $bound_y1) / 2) + $bound_y1 + round($ttf_width / 2);
			}

			$this->write_svg_text($this->graph_image, $font_size, $rotation, $text_x, $text_y, $font_color, $this->graph_font, $text_string, "CENTER");
		}
	}
	protected function write_svg_text(&$img_object, $font_size, $rotation, $text_x, $text_y, $color, $font, $string, $orientation = "LEFT")
	{
		$font_size += 1.5;
		$baseline = "middle";

		if($rotation != 0)
		{
			$text_y = (0 - ($text_y / 2));
			$text_x = $text_y + 5;
		}

		switch($orientation)
		{
			case "CENTER":
				$text_anchor = "middle";
				$baseline = "text-before-edge";
				break;
			case "RIGHT":
				$text_anchor = "end";
				break;
			case "LEFT":
			default:
				$text_anchor = "start";
				break;
		}

		// TODO: Implement $font through style="font-family: $font;"
			$img_object .= "<text x=\"" . round($text_x) . "\" y=\"" . round($text_y) . "\" fill=\"" . $color . "\" transform=\"rotate(" . (360 - $rotation) . ", " . $rotation . ", 0)\" font-size=\"" . $font_size . "\" text-anchor=\"" . $text_anchor . "\" dominant-baseline=\"" . $baseline . "\">" . $string . "</text>\n";
	}
	protected function draw_rectangle(&$img_object, $x1, $y1, $width, $height, $background_color)
	{
		if($this->graph_renderer == "PNG")
		{
			imagefilledrectangle($img_object, $x1, $y1, $width, $height, $background_color);
		}
		else if($this->graph_renderer == "SVG")
		{
			$width = $width - $x1;
			$height = $height - $y1;

			if($width < 0)
			{
				$x1 += $width;
			}
			if($height < 0)
			{
				$y1 += $height;
			}

			$img_object .= "<rect x=\"" . round($x1) . "\" y=\"" . round($y1) . "\" width=\"" . abs(round($width)) . "\" height=\"" . abs(round($height)) . "\" fill=\"" . $background_color . "\" />\n";
		}
	}
	protected function draw_rectangle_border(&$img_object, $x1, $y1, $width, $height, $color)
	{
		if($this->graph_renderer == "PNG")
		{
			imagerectangle($img_object, $x1, $y1, $width, $height, $color);
		}
		else if($this->graph_renderer == "SVG")
		{
			$img_object .= "<rect x=\"" . round($x1) . "\" y=\"" . round($y1) . "\" width=\"" . round($width - $x1) . "\" height=\"" . round($height - $y1) . "\" fill=\"transparent\" stroke=\"" . $color . "\" stroke-width=\"1px\" />\n";
		}
	}
	protected function draw_line(&$img_object, $left_start, $top_start, $from_left, $from_top, $color)
	{
		if($this->graph_renderer == "PNG")
		{
			imageline($img_object, $left_start, $top_start, $from_left, $from_top, $color);
		}
		else if($this->graph_renderer == "SVG")
		{
			$img_object .= "<line x1=\"" . round($left_start) . "\" y1=\"" . round($top_start) . "\" x2=\"" . round($from_left) . "\" y2=\"" . round($from_top) . "\" stroke=\"" . $color . "\" stroke-width=\"1px\" />\n";
		}
	}
	protected function return_ttf_string_dimensions($String, $Font, $Size, $Big = false)
	{
		if($this->graph_renderer == "PNG" && function_exists("imagettfbbox"))
		{
			$box_array = imagettfbbox($Size, 0, $Font, $String);
			$box_width = $box_array[4] - $box_array[6];

			if($Big)
			{
				$box_array = imagettfbbox($Size, 0, $Font, "AZ@![]()@|_");
			}
			$box_height = $box_array[1] - $box_array[7];
		}
		else if($this->graph_renderer == "SVG")
		{
			// TODO: This really could be improved
			$box_height = 0;
			$box_width = 0;
		}

		// Width x Height
		return array($box_width, $box_height);
	}
}

?>
