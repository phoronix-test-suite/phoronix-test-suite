<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel
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
	protected $graph_attr_marks = 6; // Number of marks to make on vertical axis
	protected $graph_attr_width = 580; // Graph width
	protected $graph_attr_height = 300; // Graph height
	protected $graph_attr_big_border = false; // Border around graph or not

	protected $graph_left_start = 10; // Distance in px to start graph from left side
	protected $graph_left_end_opp = 10; // Distance in px to end graph from right side
	protected $graph_top_start = 62; // Distance in px to start graph from top side
	protected $graph_top_end_opp = 22; // Distance in px to end graph from bottom side

	// Colors
	protected $graph_color_notches = "#000000"; // Color for notches
	protected $graph_color_text = "#000000"; // Color for text
	protected $graph_color_border = "#000000"; // Color for border (if used)
	protected $graph_color_main_headers = "#2b6b29"; // Color of main text headers
	protected $graph_color_headers = "#2b6b29"; // Color of other headers
	protected $graph_color_background = "#FFFFFF"; // Color of background
	protected $graph_color_body = "#8b8f7c"; // Color of graph body
	protected $graph_color_body_text = "#FFFFFF"; // Color of graph body text
	protected $graph_color_body_light = "#B0B59E"; // Color of the border around graph bars (if doing a bar graph)
	protected $graph_color_alert = "#C80000"; // Color of any alerts

	protected $graph_color_paint = array("#3B433A", "#BB2413", "#FF9933", "#006C00", "#5028CA", "#0094B3", 
	"#A8BC00", "#00F6FF", "#8A00AC", "#790066", "#797766", "#5598b1"); // Colors to use for the bars / lines, one color for each key

	// Text
	protected $graph_font = "Sans.ttf"; // TTF file name
	protected $graph_font_size_tick_mark = 10; // Tick mark size
	protected $graph_font_size_key = 9; // Size of height for keys
	protected $graph_font_size_heading = 18; // Font size of headings
	protected $graph_font_size_bars = 12; // Font size for text on the bars/objects
	protected $graph_font_size_identifiers = 11; // Font size of identifiers
	protected $graph_font_size_sub_heading = 12; // Font size of headers
	protected $graph_font_size_axis_heading = 11; // Font size of axis headers
	protected $graph_watermark_text = "PHORONIX-TEST-SUITE.COM"; // Text for watermark in upper right hand corner. If null, no watermark will display
	protected $graph_watermark_url = "http://www.phoronix-test-suite.com/";
	protected $graph_version = "";
	protected $graph_proportion = "";

	// Not user-friendly changes below this line
	protected $graph_body_image = false;
	protected $graph_hide_identifiers = false;
	protected $graph_show_key = false;
	protected $graph_background_lines = false;
	protected $graph_type = "GRAPH";
	protected $graph_value_type = "NUMERICAL";
	protected $graph_image;
	protected $graph_maximum_value;

	protected $graph_output = null;
	protected $graph_renderer = "PNG";
	protected $graph_data = array();
	protected $graph_data_raw = array();
	protected $graph_data_title = array();
	protected $graph_sub_titles = array();
	protected $graph_color_paint_index = -1;
	protected $graph_identifiers;
	protected $graph_title;
	protected $graph_y_title;
	protected $graph_y_title_hide = false;
	protected $graph_top_end;
	protected $graph_left_end;

	protected $graph_internal_identifiers = array();

	// Internal Switches, Etc

	protected $regression_marker_threshold = 0;
	private $test_identifier = null;

	public function __construct(&$result_object)
	{
		$this->graph_title = $result_object->get_name_formatted();
		$this->graph_y_title = $result_object->get_scale_formatted();
		$this->test_identifier = $result_object->get_test_name();
		$this->addSubTitle($result_object->get_attributes());

		$this->update_graph_dimensions(-1, -1, true);

		$default_font = bilde_renderer::find_default_ttf_font($this->graph_font);

		if($default_font == false)
		{
			$this->requestRenderer("SVG");
			$font_type = null;
		}
		else
		{
			$font_type = basename($default_font);

			if($default_font != $font_type && !defined("CUSTOM_FONT_DIR"))
			{
				$font_path = substr($default_font, 0, 0 - (strlen($font_type)));
				define("CUSTOM_FONT_DIR", $font_path);
				bilde_renderer::setup_font_directory();
			}
		}

		$this->graph_font = $font_type;
	}
	public function requestRenderer($renderer)
	{
		$this->graph_renderer = $renderer;
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
		$this->graph_version = $data;
	}
	public function loadGraphProportion($data)
	{
		$this->graph_proportion = $data;
	}
	public function loadGraphData($data_array)
	{
		loadGraphValues($data_array);
	}
	public function loadGraphValues($data_array, $data_title = null)
	{
		foreach($data_array as &$data_item)
		{
			if(is_float($data_item))
			{
				$data_item = $this->trim_double($data_item, 2);
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
	public function addSubTitle($sub_title)
	{
		$sub_titles = array_map("trim", explode("|", $sub_title));

		foreach($sub_titles as $sub_title)
		{
			if(!empty($sub_title))
			{
				array_push($this->graph_sub_titles, $sub_title);
			}
		}
	}
	public function addInternalIdentifier($identifier, $value)
	{
		$this->graph_internal_identifiers[$identifier] = $value;
	}
	public function saveGraphToFile($file)
	{
		$this->graph_output = $file;
	}
	public function htmlEmbedCode($file, $width, $height)
	{
		$attributes = array();

		if(in_array($this->graph_renderer, array("SWF", "SVG")))
		{
			$attributes["width"] = $width;
			$attributes["height"] = $height;
		}

		return $this->graph_image->html_embed_code($file, $attributes, true);
	}
	public function graphWidth()
	{
		return $this->graph_attr_width;
	}
	public function graphHeight()
	{
		return $this->graph_attr_height;
	}
	public function markResultRegressions($threshold)
	{
		$this->regression_marker_threshold = $threshold;
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
		$real_maximum = 0;

		foreach($this->graph_data as $graph_set)
		{
			foreach($graph_set as $set_item)
			{
				if((is_numeric($set_item) && $set_item > $real_maximum) || (!is_numeric($set_item) && strlen($set_item) > strlen($real_maximum)))
				{
					$real_maximum = $set_item;
				}
			}
		}

		if(is_numeric($real_maximum))
		{
			// disable forcing 100 top when display Percent
			if(false && $real_maximum <= 100 && $this->graph_y_title == "Percent")
			{
				$maximum = (ceil(100 / $this->graph_attr_marks) + 1) * $this->graph_attr_marks;
			}
			else if($real_maximum < $this->graph_attr_marks)
			{
				$maximum = $real_maximum * 1.35;
			}
			else
			{
				$maximum = (floor(round($real_maximum * 1.35) / $this->graph_attr_marks) + 1) * $this->graph_attr_marks;

				if($real_maximum > 100)
				{
					$round_num = 1 . str_repeat('0', strlen($maximum) - 2);
					if(($maximum / $this->graph_attr_marks) % $round_num != 0)
					{
						$maximum += ($round_num - (($maximum / $this->graph_attr_marks) % $round_num)) * $this->graph_attr_marks;
					}
				}
			}
		}
		else
		{
			$maximum = 0;
		}

		return $maximum;
	}
	protected function text_size_bounds($string, $font, $font_size, $minimum_font_size, $bound_width, $bound_height = -1)
	{
		list($string_width, $string_height) = $this->text_string_dimensions($string, $font, $font_size);

		while($font_size > $minimum_font_size && $string_width > $bound_width || ($bound_height > 0 && $string_height > $bound_height))
		{
			$font_size -= 0.2;
			list($string_width, $string_height) = $this->text_string_dimensions($string, $font, $font_size);
		}

		return $font_size;
	}
	protected function find_longest_string($string_r)
	{
		$longest_string = "";
		$longest_string_length = 0;

		if(!is_array($string_r))
		{
			$string_r = array($string_r);
		}

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


	public function renderGraph()
	{
		$this->graph_maximum_value = $this->maximum_graph_value();

		// Make room for tick markings, left hand side
		if($this->graph_value_type == "NUMERICAL")
		{
			$this->graph_left_start += $this->text_string_width($this->graph_maximum_value, $this->graph_font, $this->graph_font_size_tick_mark) + 2;
		}

		if($this->graph_hide_identifiers)
		{
			$this->graph_top_end += $this->graph_top_end_opp / 2;
		}

		// Do the actual work
		$this->render_graph_pre_init();
		$this->render_graph_init();
		$this->render_graph_base();
		$this->render_graph_heading();

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
		return $this->return_graph_image();
	}
	protected function render_graph_pre_init()
	{
		return;
	}
	protected function render_graph_init()
	{
		$this->update_graph_dimensions();
		$this->graph_image = bilde_renderer::setup_renderer($this->graph_renderer, $this->graph_attr_width, $this->graph_attr_height, $this->graph_internal_identifiers);
		$this->requestRenderer($this->graph_image->get_renderer());

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
		$this->graph_color_alert = $this->graph_image->convert_hex_to_type($this->graph_color_alert);

		foreach($this->graph_color_paint as &$paint_color)
		{
			$paint_color = $this->graph_image->convert_hex_to_type($paint_color);
		}

		// Background Color
		if($this->graph_attr_big_border)
		{
			$this->graph_image->draw_rectangle_with_border(0, 0, $this->graph_attr_width, $this->graph_attr_height, $this->graph_color_background, $this->graph_color_border);
		}
		else
		{
			$this->graph_image->draw_rectangle(0, 0, $this->graph_attr_width, $this->graph_attr_height, $this->graph_color_background);
		}
	}
	protected function render_graph_heading($with_version = true)
	{
		// Default to NORMAL
		$this->graph_image->write_text_center($this->graph_title, $this->graph_font, $this->graph_font_size_heading, $this->graph_color_main_headers, $this->graph_left_start, 3, $this->graph_left_end, 3, false, "http://global.phoronix-test-suite.com/?k=category&u=" . $this->test_identifier);

		foreach($this->graph_sub_titles as $i => $sub_title)
		{
			$this->graph_image->write_text_center($sub_title, $this->graph_font, $this->graph_font_size_sub_heading, $this->graph_color_main_headers, $this->graph_left_start, (31 + ($i * 18)), $this->graph_left_end, (31 + ($i * 18)), false);
		}

		if($with_version)
		{
			$this->graph_image->write_text_right($this->graph_version, $this->graph_font, 7, $this->graph_color_body_light, $this->graph_left_end, $this->graph_top_start - 9, $this->graph_left_end, $this->graph_top_start - 9, false, "http://www.phoronix-test-suite.com/");
		}
	}
	protected function render_graph_base()
	{
		if(count($this->graph_data_title) > 1 || $this->graph_show_key)
		{
			$this->graph_top_start += 8 + (ceil(count($this->graph_data_title) / 4) * 12);
		}

		if(($sub_title_count = count($this->graph_sub_titles)) > 1)
		{
			$this->graph_top_start += (($sub_title_count - 1) * 14);
		}

		$this->graph_image->draw_rectangle_with_border($this->graph_left_start, $this->graph_top_start, $this->graph_left_end, $this->graph_top_end, $this->graph_color_body, $this->graph_color_notches);

		if($this->graph_body_image != false)
		{
			$this->graph_image->image_copy_merge($this->graph_body_image, $this->graph_left_start + (($this->graph_left_end - $this->graph_left_start) / 2) - imagesx($this->graph_body_image) / 2, $this->graph_top_start + (($this->graph_top_end - $this->graph_top_start) / 2) - imagesy($this->graph_body_image) / 2);
		}

		if(!empty($this->graph_y_title) && !$this->graph_y_title_hide)
		{
			$str = $this->graph_y_title;
			$offset = 0;

			if(!empty($this->graph_proportion))
			{
				$proportion = null;
 
				switch($this->graph_proportion)
				{
					case "LIB":
						$proportion = "Fewer Are Better";
						$offset += 12;
						$this->graph_image->draw_arrow($this->graph_left_start + 5, $this->graph_top_start - 4, $this->graph_left_start + 5, $this->graph_top_start - 11, $this->graph_color_main_headers, $this->graph_color_body_light, 1);
						break;
					case "HIB":
						//$proportion = "Higher Is Better";
						$offset += 12;
						$this->graph_image->draw_arrow($this->graph_left_start + 5, $this->graph_top_start - 11, $this->graph_left_start + 5, $this->graph_top_start - 4, $this->graph_color_main_headers, $this->graph_color_body_light, 1);
						break;
				}

				if($proportion != null)
				{
					if(!empty($str))
					{
						$str .= ", ";
					}

					$str .= $proportion;
				}
			}

			$this->graph_image->write_text_left($str, $this->graph_font, 7, $this->graph_color_main_headers, $this->graph_left_start + $offset, $this->graph_top_start - 7, $this->graph_left_start + $offset, $this->graph_top_start - 7);
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

			if($display_value != 0)
			{
				$this->graph_image->write_text_right($display_value, $this->graph_font, $this->graph_font_size_tick_mark, $this->graph_color_text, $px_from_left_start - 1, $px_from_top - 2, $px_from_left_start - 1, $px_from_top - 2);
			}

			if($i != 0 && $this->graph_background_lines)
			{
				$line_width = 6;
				$this->graph_image->draw_dashed_line($px_from_left_end + 6, $px_from_top, $this->graph_left_end, $px_from_top, $this->graph_color_body_light, 1, 20, 15);
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
		$component_y = $this->graph_top_start - 19;
		$this->reset_paint_index();

		for($i = 0; $i < count($this->graph_data_title); $i++)
		{
			if(!empty($this->graph_data_title[$i]))
			{
				$this_color = $this->next_paint_color();
				$key_counter += 1;

				$component_x = $this->graph_left_start + 14 + (($this->graph_left_end - $this->graph_left_start) / 4) * (($key_counter - 1) % 4);

				$this->graph_image->draw_rectangle_with_border($component_x - 13, $component_y - 5, $component_x - 3, $component_y + 5, $this_color, $this->graph_color_notches);
				$this->graph_image->write_text_left($this->graph_data_title[$i], $this->graph_font, $this->graph_font_size_key, $this_color, $component_x, $component_y, $component_x, $component_y);

				if($key_counter % 4 == 0)
				{
					$component_y -= 14;
				}
			}
		}
		$this->reset_paint_index();
	}
	protected function render_graph_watermark()
	{
		if(!empty($this->graph_watermark_text))
		{
			$this->graph_image->write_text_right($this->graph_watermark_text, $this->graph_font, 10, $this->graph_color_text, $this->graph_left_end - 2, $this->graph_top_start + 8, $this->graph_left_end - 2, $this->graph_top_start + 8, false, $this->graph_watermark_url);
		}
	}
	protected function return_graph_image($quality = 85)
	{
		$return_object = $this->graph_image->render_to_file($this->graph_output, $quality);
		$this->graph_image->destroy_image();

		return $return_object;
	}
	protected function trim_double($double, $accuracy = 2)
	{
		// Should be same as pts_trim_double()
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
				$return[1] .= str_repeat('0', ($accuracy - $strlen));
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
		return bilde_renderer::soft_text_string_dimensions($string, $font, $size, $big);
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
}

?>
