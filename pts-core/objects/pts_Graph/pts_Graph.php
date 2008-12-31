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

abstract class pts_Graph
{
	// Defaults
	var $graph_attr_marks = 6; // Number of marks to make on vertical axis
	var $graph_attr_width = 580; // Graph width
	var $graph_attr_height = 300; // Graph height
	var $graph_attr_big_border = false; // Border around graph or not

	var $graph_left_start = 20; // Distance in px to start graph from left side
	var $graph_left_end_opp = 10; // Distance in px to end graph from right side
	var $graph_top_start = 62; // Distance in px to start graph from top side
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

	var $graph_color_paint = array("#3B433A", "#BB2413", "#FF9933", "#006C00", "#5028CA", "#B30000", 
	"#A8BC00", "#00F6FF", "#8A00AC", "#790066", "#797766", "#5598b1"); // Colors to use for the bars / lines, one color for each key

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
	var $graph_data_raw = array();
	var $graph_data_title = array();
	var $graph_color_paint_index = -1;
	var $graph_identifiers;
	var $graph_title;
	var $graph_sub_title;
	var $graph_y_title;
	var $graph_y_title_hide = false;
	var $graph_top_end;
	var $graph_left_end;

	var $graph_internal_identifiers = array();

	public function __construct($title, $sub_title, $y_axis_title)
	{
		$this->graph_title = $title;
		$this->graph_sub_title = $sub_title;
		$this->graph_y_title = $y_axis_title;

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

		// Determine renderers available
		$ming_available = extension_loaded("ming");
		if(!extension_loaded("gd"))
		{
		/*	if(dl("gd.so"))
			{
				$gd_available = true;
			}
			else	*/
				$gd_available = false;
		}
		else
		{
			$gd_available = true;
		}
		
		// Set a renderer
		if($ming_available && getenv("SWF_DEBUG") != false)
		{
			$this->setRenderer("SWF");
		}
		else if($gd_available && getenv("SVG_DEBUG") == false)
		{
			if(getenv("JPG_DEBUG") !== false)
			{
				$this->setRenderer("JPG");
			}
			else
			{
				$this->setRenderer("PNG");
			}
		}
		else
		{
			$this->setRenderer("SVG");
		}
	}
	public function setRenderer($renderer)
	{
		if($renderer == "SVG")
		{
			$this->graph_renderer = "SVG";
			$this->graph_left_start += 10;
		}
		else if($renderer == "SWF")
		{
			$this->graph_renderer = "SWF";
		}
		else if($renderer == "JPG")
		{
			$this->graph_renderer = "JPG";
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
	public function loadGraphRawValues($data_array)
	{
		array_push($this->graph_data_raw, $data_array);
	}
	public function setGraphBackgroundPNG($file)
	{
		$this->graph_image->image_file_to_type($file);

		if(!empty($img))
		{
			$this->graph_body_image = $img;
		}
	}
	public function renderGraph()
	{
		$this->graph_maximum_value = $this->maximum_graph_value();

		// Make room for tick markings, left hand side
		if($this->graph_value_type == "NUMERICAL")
		{
			$this->graph_left_start += $this->text_string_width($this->graph_maximum_value, $this->graph_font, $this->graph_font_size_tick_mark) + 2;
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
	public function addInternalIdentifier($identifier, $value)
	{
		$this->graph_internal_identifiers[$identifier] = $value;
	}
	public function saveGraphToFile($file)
	{
		$this->graph_output = $file;
	}
	public function graphWidth()
	{
		return $this->graph_attr_width;
	}
	public function graphHeight()
	{
		return $this->graph_attr_height;
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
		$maximum = 0;

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
			if($maximum <= 100 && $this->graph_y_title == "Percent")
			{
				$maximum = (ceil(100 / $this->graph_attr_marks) + 1) * $this->graph_attr_marks;
			}
			else if($maximum < $this->graph_attr_marks)
			{
				$maximum = $maximum * 1.35;
			}
			else
			{
				$maximum = (floor(round($maximum * 1.35) / $this->graph_attr_marks) + 1) * $this->graph_attr_marks;
			}
		}

		return $maximum;
	}
	protected function text_string_width($string, $font, $size)
	{
		$dimensions = $this->text_string_dimensions($string, $font, $size);
		return $dimensions[0];
	}
	protected function text_string_height($string, $font, $size)
	{
		$dimensions = $this->text_string_dimensions($string, $font, $size);
		return $dimensions[1];
	}
	protected function text_size_bounds($string, $font, $font_size, $minimum_font_size, $bound_width, $bound_height = -1)
	{
		while($font_size > $minimum_font_size && ($this->text_string_width($string, $font, $font_size) > $bound_width || ($bound_height > 0 && $this->text_string_height($string, $font, $font_size) > $bound_height)))
		{
			$font_size -= 0.5;
		}

		return $font_size;
	}
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

		if($this->graph_renderer == "PNG")
		{
			$this->graph_image = new bilde_png_renderer($this->graph_attr_width, $this->graph_attr_height, $this->graph_internal_identifiers);
		}
		else if($this->graph_renderer == "SVG")
		{
			$this->graph_image = new bilde_svg_renderer($this->graph_attr_width, $this->graph_attr_height, $this->graph_internal_identifiers);
		}
		else if($this->graph_renderer == "JPG")
		{
			$this->graph_image = new bilde_jpg_renderer($this->graph_attr_width, $this->graph_attr_height, $this->graph_internal_identifiers);
		}
		else if($this->graph_renderer == "SWF")
		{
			$this->graph_image = new bilde_swf_renderer($this->graph_attr_width, $this->graph_attr_height, $this->graph_internal_identifiers);
		}

		// Initalize Colors

		$this->graph_color_notches = $this->graph_image->convert_hex_to_type($this->graph_color_notches);
		$this->graph_color_text = $this->graph_image->convert_hex_to_type($this->graph_color_text);
		$this->graph_color_border = $this->graph_image->convert_hex_to_type($this->graph_color_border);
		$this->graph_color_main_headers = $this->graph_image->convert_hex_to_type($this->graph_color_main_headers);
		$this->graph_color_headers = $this->graph_image->convert_hex_to_type($this->graph_color_headers);
		$this->graph_color_background = $this->graph_image->convert_hex_to_type($this->graph_color_background);
		$this->graph_color_body = $this->graph_image->convert_hex_to_type($this->graph_color_body);
		$this->graph_color_body_text = $this->graph_image->convert_hex_to_type($this->graph_color_body_text);
		$this->graph_color_body_light = $this->graph_image->convert_hex_to_type($this->graph_color_body_light);

		for($i = 0; $i < count($this->graph_color_paint); $i++)
		{
			$this->graph_color_paint[$i] = $this->graph_image->convert_hex_to_type($this->graph_color_paint[$i]);
		}

		// Background Color
		$this->graph_image->draw_rectangle(0, 0, $this->graph_attr_width, $this->graph_attr_height, $this->graph_color_background);

		if($this->graph_attr_big_border == true)
		{
			$this->graph_image->draw_rectangle_border(0, 0, $this->graph_attr_width - 1, $this->graph_attr_height - 1, $this->graph_color_border);
		}
	}
	protected function render_graph_base()
	{
		if(count($this->graph_data_title) > 1 || $this->graph_show_key == true)
		{
			$num_key_lines = ceil(count($this->graph_data_title) / 4);
			$this->graph_top_start = $this->graph_top_start + 8 + ($num_key_lines * 11);
		}

		$this->graph_image->draw_rectangle($this->graph_left_start, $this->graph_top_start, $this->graph_left_end, $this->graph_top_end, $this->graph_color_body);
		$this->graph_image->draw_rectangle_border($this->graph_left_start, $this->graph_top_start, $this->graph_left_end, $this->graph_top_end, $this->graph_color_notches);

		if($this->graph_body_image != false)
		{
			$this->graph_image->image_copy_merge($this->graph_body_image, $this->graph_left_start + (($this->graph_left_end - $this->graph_left_start) / 2) - imagesx($this->graph_body_image) / 2, $this->graph_top_start + (($this->graph_top_end - $this->graph_top_start) / 2) - imagesy($this->graph_body_image) / 2);
		}

		// Text
		$this->graph_image->write_text_right($this->graph_version, $this->graph_font, 7, $this->graph_color_body_light, $this->graph_left_end, $this->graph_top_start - 9, $this->graph_left_end, $this->graph_top_start - 9);
		$this->graph_image->write_text_center($this->graph_title, $this->graph_font, $this->graph_font_size_heading, $this->graph_color_main_headers, $this->graph_left_start, 3, $this->graph_left_end, 4);
		$this->graph_image->write_text_center($this->graph_sub_title, $this->graph_font, $this->graph_font_size_sub_heading, $this->graph_color_main_headers, $this->graph_left_start, 30, $this->graph_left_end, 26, false, true);

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

			$this->graph_image->write_text_left($str, $this->graph_font, 7, $this->graph_color_main_headers, $this->graph_left_start, $this->graph_top_start - 9, $this->graph_left_start, $this->graph_top_start - 9);
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

			$this->graph_image->draw_line($px_from_left_start, $px_from_top, $px_from_left_end, $px_from_top, $this->graph_color_notches);

			$this->graph_image->write_text_right($display_value, $this->graph_font, $this->graph_font_size_tick_mark, $this->graph_color_text, $px_from_left_start - 1, $px_from_top - 2, $px_from_left_start - 1, $px_from_top - 2);

			if($i != 0 && $this->graph_background_lines == true)
			{
				$line_width = 6;
				for($y = $px_from_left_end + $line_width; $y < $this->graph_left_end; $y += ($line_width * 2))
				{
					if($y + $line_width < $this->graph_left_end)
					{
						$this->graph_image->draw_line($y, $px_from_top, $y += $line_width, $px_from_top, $this->graph_color_body_light);
					}
					else
					{
						$this->graph_image->draw_line($y, $px_from_top, $y += ($this->graph_left_end - $y) - 1, $px_from_top, $this->graph_color_body_light);
					}
				}
			}

			$display_value += $this->trim_double($this->graph_maximum_value / $this->graph_attr_marks, 2);
		}
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

				$this->graph_image->write_text_left($this->graph_data_title[$i], $this->graph_font, $this->graph_font_size_key, $this_color, $component_x, $component_y, $component_x, $component_y);

				$this->graph_image->draw_rectangle($component_x - 13, $component_y - 5, $component_x - 3, $component_y + 5, $this_color);
				$this->graph_image->draw_rectangle_border($component_x - 13, $component_y - 5, $component_x - 3, $component_y + 5, $this->graph_color_notches);

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
			$this->graph_image->write_text_right($this->graph_watermark_text, $this->graph_font, 10, $this->graph_color_text, $this->graph_left_end - 2, $this->graph_top_start + 8, $this->graph_left_end - 2, $this->graph_top_start + 8);
		}
	}
	protected function return_graph_image()
	{
		$this->graph_image->render_image($this->graph_output, 85);
		$this->graph_image->destroy_image();
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


	//
	// Renderer-specific Functions
	//

	protected function text_string_dimensions($string, $font, $size, $big = false)
	{
		// TODO: Switch to using bilde_renderer interface
		if($this->graph_renderer == "PNG" && function_exists("imagettfbbox"))
		{
			$box_array = imagettfbbox($size, 0, $this->graph_font, $string);
			$box_width = $box_array[4] - $box_array[6];

			if($big)
			{
				$box_array = imagettfbbox($size, 0, $font, "AZ@![]()@|_");
			}
			$box_height = $box_array[1] - $box_array[7];
		}
		else if($this->graph_renderer == "SVG")
		{
			// TODO: This needs to be implemented
			$box_height = 0;
			$box_width = 0;
		}

		// Width x Height
		return array($box_width, $box_height);
	}
}

?>
