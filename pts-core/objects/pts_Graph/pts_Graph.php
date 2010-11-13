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

// Since the graph_config should be the same for the duration, only create it once rather than creating it everytime a graph is made
//if(PTS_IS_CLIENT || (defined("PTS_LIB_GRAPH_CONFIG_XML") && is_file(PTS_LIB_GRAPH_CONFIG_XML)))
pts_Graph::$graph_config = new pts_graph_config_tandem_XmlReader();

abstract class pts_Graph
{
	// Graph config
	public static $graph_config = null;
	public $graph_image;

	// Defaults
	protected $graph_attr_marks; // Number of marks to make on vertical axis
	protected $graph_attr_width; // Graph width
	protected $graph_attr_height; // Graph height
	protected $graph_attr_big_border; // Border around graph or not

	protected $graph_left_start = 10; // Distance in px to start graph from left side
	protected $graph_left_end_opp = 10; // Distance in px to end graph from right side
	protected $graph_top_start = 62; // Distance in px to start graph from top side
	protected $graph_top_end_opp = 22; // Distance in px to end graph from bottom side

	// Colors
	protected $graph_color_notches; // Color for notches
	protected $graph_color_text; // Color for text
	protected $graph_color_border; // Color for border (if used)
	protected $graph_color_main_headers; // Color of main text headers
	protected $graph_color_headers; // Color of other headers
	protected $graph_color_background; // Color of background
	protected $graph_color_body; // Color of graph body
	protected $graph_color_body_text; // Color of graph body text
	protected $graph_color_body_light; // Color of the border around graph bars (if doing a bar graph)
	protected $graph_color_alert; // Color of any alerts
	protected $graph_color_paint; // Colors to use for the bars / lines, one color for each key

	// Text
	protected $graph_font; // TTF file name
	protected $graph_font_size_tick_mark = 10; // Tick mark size
	protected $graph_font_size_key = 9; // Size of height for keys
	protected $graph_font_size_heading; // Font size of headings
	protected $graph_font_size_bars; // Font size for text on the bars/objects
	protected $graph_font_size_identifiers; // Font size of identifiers
	protected $graph_font_size_sub_heading ; // Font size of headers
	protected $graph_font_size_axis_heading; // Font size of axis headers
	protected $graph_watermark_text; // Text for watermark in upper right hand corner. If null, no watermark will display
	protected $graph_watermark_url;
	protected $graph_version = null;
	protected $graph_proportion = null;

	// Not user-friendly changes below this line
	protected $graph_orientation = "VERTICAL";
	protected $graph_body_image = false;
	protected $graph_hide_identifiers = false;
	protected $graph_show_key = false;
	protected $graph_background_lines = false;
	protected $graph_value_type = "NUMERICAL";
	protected $graph_maximum_value;

	protected $graph_output = null;
	protected $graph_renderer;
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
	protected $graph_top_heading_height;

	protected $graph_key_line_height = 0;
	protected $graph_key_item_width;
	protected $graph_keys_per_line;
	protected $graph_bottom_offset = 0;

	protected $graph_internal_identifiers = array();

	// Internal Switches, Etc

	protected static $used_paint_colors;
	protected $regression_marker_threshold = 0;
	protected $is_multi_way_comparison = false;
	private $test_identifier = null;
	protected $iveland_view = false;

	public function __construct(&$result_object = null, &$result_file = null)
	{
		// Setup config values
		$this->graph_attr_width = $this->read_graph_config(P_GRAPH_SIZE_WIDTH); // Graph width
		$this->graph_attr_height = $this->read_graph_config(P_GRAPH_SIZE_HEIGHT); // Graph height
		$this->graph_attr_big_border = $this->read_graph_config(P_GRAPH_BORDER) == "TRUE"; // Graph border

		// Colors
		$this->graph_color_notches = $this->read_graph_config(P_GRAPH_COLOR_NOTCHES); // Color for notches
		$this->graph_color_text = $this->read_graph_config(P_GRAPH_COLOR_TEXT); // Color for text
		$this->graph_color_border = $this->read_graph_config(P_GRAPH_COLOR_BORDER); // Color for border (if used)
		$this->graph_color_main_headers = $this->read_graph_config(P_GRAPH_COLOR_MAINHEADERS); // Color of main text headers
		$this->graph_color_headers = $this->read_graph_config(P_GRAPH_COLOR_HEADERS); // Color of other headers
		$this->graph_color_background = $this->read_graph_config(P_GRAPH_COLOR_BACKGROUND); // Color of background
		$this->graph_color_body = $this->read_graph_config(P_GRAPH_COLOR_BODY); // Color of graph body
		$this->graph_color_body_text = $this->read_graph_config(P_GRAPH_COLOR_BODYTEXT); // Color of graph body text
		$this->graph_color_body_light = $this->read_graph_config(P_GRAPH_COLOR_ALTERNATE); // Color of the border around graph bars (if doing a bar graph)
		$this->graph_color_alert = $this->read_graph_config(P_GRAPH_COLOR_ALERT); // Color for alerts
		$this->graph_color_paint = pts_strings::comma_explode($this->read_graph_config(P_GRAPH_COLOR_PAINT)); // Colors to use for the bars / lines, one color for each key

		// Text
		$this->graph_watermark_text = $this->read_graph_config(P_GRAPH_WATERMARK); // watermark
		$this->graph_watermark_url = $this->read_graph_config(P_GRAPH_WATERMARK_URL); // watermark URL
		//$this->graph_font = $this->read_graph_config(P_GRAPH_FONT_TYPE);  // TTF file name
		$this->graph_font_size_heading = $this->read_graph_config(P_GRAPH_FONT_SIZE_HEADERS); // Font size of headings
		$this->graph_font_size_bars = $this->read_graph_config(P_GRAPH_FONT_SIZE_TEXT); // Font size for text on the bars/objects
		$this->graph_font_size_identifiers = $this->read_graph_config(P_GRAPH_FONT_SIZE_IDENTIFIERS); // Font size of identifiers
		$this->graph_font_size_sub_heading = $this->read_graph_config(P_GRAPH_FONT_SIZE_SUBHEADERS); // Font size of headers
		$this->graph_font_size_axis_heading = $this->read_graph_config(P_GRAPH_FONT_SIZE_AXIS); // Font size of axis headers

		$this->graph_attr_marks = $this->read_graph_config(P_GRAPH_MARKCOUNT); // Number of marks to make on vertical axis
		$this->graph_renderer = $this->read_graph_config(P_GRAPH_RENDERER); // Renderer

		// Reset of setup besides config
		if($result_object != null)
		{
			$test_version = $result_object->test_profile->get_version();
			$this->graph_title = $result_object->test_profile->get_title() . (isset($test_version[2]) ? " v" . $test_version : null);

			$this->graph_y_title = $result_object->test_profile->get_result_scale_formatted();
			$this->test_identifier = $result_object->test_profile->get_identifier();
			$this->graph_proportion = $result_object->test_profile->get_result_proportion();
			$this->addSubTitle($result_object->get_arguments_description());
			$this->addInternalIdentifier("Test", $result_object->test_profile->get_identifier());
		}

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

		if($result_file != null && $result_file instanceOf pts_result_file)
		{
			//$this->addInternalIdentifier("Identifier", null); // TODO: result file name
			$pts_version = pts_arrays::last_element($result_file->get_system_pts_version());
		}

		if(!isset($pts_version) || empty($pts_version))
		{
			$pts_version = PTS_VERSION;
		}

		$this->graph_version = "Phoronix Test Suite " . $pts_version;
		$this->graph_font = $font_type;
	}
	public function read_graph_config($xml_path)
	{
		static $config_store = null;

		if(!isset($config_store[$xml_path]))
		{
			$config_store[$xml_path] = self::$graph_config->getXmlValue($xml_path);
		}

		return $config_store[$xml_path];
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
				$data_item = round($data_item, 2);
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
		foreach($data_array as &$data_item)
		{
			if(is_float($data_item))
			{
				$data_item = round($data_item, 2);
			}
		}

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
		$sub_titles = array_map("trim", explode('|', $sub_title));

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
	protected function shade_color($hex_color)
	{
		$stepping = 54;
		$hex[] = hexdec(substr($hex_color, 1, 2));
		$hex[] = hexdec(substr($hex_color, 3, 2));
		$hex[] = hexdec(substr($hex_color, 5, 2));

		foreach($hex as &$component)
		{
			if($component == 0)
			{
				continue;
			}

			if($component < $stepping)
			{
				$component = 255;
			}

			$component -= $stepping;
		}
		return '#' . str_pad(dechex($hex[0]), 2, 0) . str_pad(dechex($hex[1]), 2, 0) . str_pad(dechex($hex[2]), 2, 0);
	}
	public static function clear_paint_color_cache()
	{
		self::$used_paint_colors = null;
	}
	protected function get_paint_color($identifier = 0)
	{
		if(!isset(self::$used_paint_colors[$identifier]))
		{
			if(PTS_IS_CLIENT && pts_client::read_env("GRAPH_GROUP_SIMILAR"))
			{
				static $groups;

				$group = trim(pts_arrays::last_element(pts_strings::colon_explode($identifier)));

				if(!isset($groups[$group]))
				{
					$groups[$group] = $this->next_paint_color();
				}
				else
				{
					$color = $this->graph_image->convert_type_to_hex($groups[$group]);
					$color = $this->shade_color($color);
					$groups[$group] = $this->graph_image->convert_hex_to_type($color);
				}

				self::$used_paint_colors[$identifier] = $groups[$group];
			}
			else
			{
				// Use a counter to prevent possible infinite loop when running out of unique paint colors
				$i = 0;

				do
				{
					$i++;
					$next_color = $this->next_paint_color();
				}
				while(is_array(self::$used_paint_colors) && in_array($next_color, self::$used_paint_colors) == true && $i < 10);

				self::$used_paint_colors[$identifier] = $next_color;
			}
		}

		return self::$used_paint_colors[$identifier];
	}
	protected function reset_paint_index()
	{
		$this->graph_color_paint_index = -1;
	}
	protected function maximum_graph_value()
	{
		$real_maximum = 0;

		foreach($this->graph_data as &$data_r)
		{
			$data_max = max($data_r);

			if(!is_numeric($data_max))
			{
				$data_max = str_repeat(9, strlen($data_max));
			}

			if($data_max > $real_maximum)
			{
				$real_maximum = $data_max;
			}
		}

		if(is_numeric($real_maximum))
		{
			if($real_maximum < $this->graph_attr_marks)
			{
				$maximum = $real_maximum * 1.35;
			}
			else
			{
				$maximum = (floor(round($real_maximum * 1.285) / $this->graph_attr_marks) + 1) * $this->graph_attr_marks;
				$maximum = round(ceil($maximum / $this->graph_attr_marks), (0 - strlen($maximum) + 2)) * $this->graph_attr_marks;
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
	protected function find_longest_string(&$string_r)
	{
		$longest_string = null;
		$longest_string_length = 0;

		if(!is_array($string_r))
		{
			$string_r = array($string_r);
		}

		foreach($string_r as $one_string)
		{
			if(is_array($one_string))
			{
				$one_string = $this->find_longest_string($one_string);
			}

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
		$this->render_graph_start();
		$this->render_graph_finish();
	}
	public function render_graph_start()
	{
		$this->graph_maximum_value = $this->maximum_graph_value();

		// Make room for tick markings, left hand side
		if($this->iveland_view == false)
		{
			if($this->graph_value_type == "NUMERICAL")
			{
				$this->graph_left_start += $this->text_string_width($this->graph_maximum_value, $this->graph_font, $this->graph_font_size_tick_mark) + 2;
			}

			if($this->graph_hide_identifiers)
			{
				$this->graph_top_end += $this->graph_top_end_opp / 2;
			}

			$this->graph_top_start += $this->graph_key_height();
		}
		else
		{
			if($this->graph_orientation == "HORIZONTAL")
			{
				if($this->is_multi_way_comparison)
				{
					// TODO: verify this is good and covered for all scenarios
					$longest_identifier_width = $this->text_string_width($this->graph_maximum_value, $this->graph_font, $this->graph_font_size_identifiers) + 65;
				}
				else
				{
					$longest_identifier_width = $this->text_string_width($this->find_longest_string($this->graph_identifiers), $this->graph_font, $this->graph_font_size_identifiers) + 6;
				}

				$longest_identifier_max = $this->graph_attr_width * 0.5;

				$this->graph_left_start = min($longest_identifier_max, max($longest_identifier_width, 70));
				$this->graph_left_end_opp = 15;
				$this->graph_left_end = $this->graph_attr_width - $this->graph_left_end_opp;
			}
			else if($this->graph_value_type == "NUMERICAL")
			{
				$this->graph_left_start += $this->text_string_width($this->graph_maximum_value, $this->graph_font, $this->graph_font_size_tick_mark) + 2;
			}

			// Pad 8px on top and bottom + title bar + sub-headings
			$this->graph_top_heading_height = 16 + $this->graph_font_size_heading + (count($this->graph_sub_titles) * ($this->graph_font_size_sub_heading + 4));

			$key_height = $this->graph_key_height();
			if($key_height > ($this->graph_key_line_height * 2))
			{
				$this->graph_attr_height += $key_height;
				$this->graph_top_end += $key_height;
			}

			$this->graph_top_start = $this->graph_top_heading_height + $key_height + 16; // + spacing before graph starts

			$bottom_heading = 14;

			if($this->graph_orientation == "HORIZONTAL")
			{
				if($this->is_multi_way_comparison && count($this->graph_data) > 1)
				{
					$per_identifier_height = 20 + (18 * count($this->graph_data));
				}
				else if(count($this->graph_data_title) > 3)
				{
					$per_identifier_height = count($this->graph_data_title) * 14;
				}
				else
				{
					// If there's too much to plot, reduce the size so each graph doesn't take too much room
					$per_identifier_height = count($this->graph_data[0]) > 10 ? 36 : 46;
				}


				$num_identifiers = count($this->graph_identifiers);
				$this->graph_top_end = $this->graph_top_start + ($num_identifiers * $per_identifier_height);
				// $this->graph_top_end_opp
				$this->graph_attr_height = $this->graph_top_end + 25 + $bottom_heading;
			}
			else
			{
				$this->graph_attr_height += $bottom_heading + 4;
			}
		}

		/*
		// Not sure this code is needed anymore...
		if(($key_count = count($this->graph_data_title)) > 8)
		{
			$this->update_graph_dimensions(-1, $this->graph_attr_height + (floor(($key_count - 8) / 4) * 14), true);
		}
		*/

		// Do the actual work
		$this->render_graph_pre_init();
		$this->render_graph_init();
	}
	public function render_graph_finish()
	{
		$this->render_graph_key();
		$this->render_graph_base($this->graph_left_start, $this->graph_top_start, $this->graph_left_end, $this->graph_top_end);
		$this->render_graph_heading();

		if($this->graph_hide_identifiers == false)
		{
			$this->render_graph_identifiers();
		}

		if($this->graph_value_type == "NUMERICAL")
		{
			$this->render_graph_value_ticks($this->graph_left_start, $this->graph_top_start, $this->graph_left_end, $this->graph_top_end);
		}

		$this->render_graph_result();
		$this->render_graph_post();

		return $this->return_graph_image();
	}
	protected function render_graph_pre_init()
	{
		return;
	}
	protected function render_graph_init($bilde_attributes = null)
	{
		if(defined("PHOROMATIC_TRACKER"))
		{
			$bilde_attributes["cache_font_size"] = true;
		}

		$this->update_graph_dimensions();
		$this->graph_image = bilde_renderer::setup_renderer($this->graph_renderer, $this->graph_attr_width, $this->graph_attr_height, $this->graph_internal_identifiers, $bilde_attributes);
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
		if($this->iveland_view)
		{
			$this->graph_image->draw_rectangle_with_border(0, 0, $this->graph_attr_width, ($this->graph_attr_height - 1), $this->graph_color_background, $this->graph_color_border);
		}
		else if($this->graph_attr_big_border)
		{
			$this->graph_image->draw_rectangle_with_border(0, 0, $this->graph_attr_width, $this->graph_attr_height, $this->graph_color_background, $this->graph_color_border);
		}
		else
		{
			$this->graph_image->draw_rectangle(0, 0, $this->graph_attr_width, $this->graph_attr_height, $this->graph_color_background);
		}

		if($this->iveland_view == false && ($sub_title_count = count($this->graph_sub_titles)) > 1)
		{
			$this->graph_top_start += (($sub_title_count - 1) * ($this->graph_font_size_sub_heading + 4));
		}
	}
	protected function render_graph_heading($with_version = true)
	{
		// Default to NORMAL
		if($this->iveland_view)
		{
			$this->graph_image->draw_rectangle(0, 0, $this->graph_attr_width, $this->graph_top_heading_height, $this->graph_color_main_headers);
			$this->graph_image->write_text_left($this->graph_title, $this->graph_font, $this->graph_font_size_heading, $this->graph_color_background, 5, 12, $this->graph_left_end, 12, false, "http://global.phoronix-test-suite.com/?k=category&u=" . $this->test_identifier);

			foreach($this->graph_sub_titles as $i => $sub_title)
			{
				$vertical_offset = 12 + 4 + $this->graph_font_size_heading + ($i * ($this->graph_font_size_sub_heading + 3));
				$this->graph_image->write_text_left($sub_title, $this->graph_font, $this->graph_font_size_sub_heading, $this->graph_color_background, 5, $vertical_offset, $this->graph_left_end, $vertical_offset, false);
			}
		
			$this->graph_image->image_copy_merge($this->graph_image->png_image_to_type("http://www.phoronix-test-suite.com/external/pts-logo-77x40-white.png"), $this->graph_left_end - 77, ($this->graph_top_heading_height / 40 + 2), 0, 0, 77, 40);
		}
		else
		{
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
	}
	protected function render_graph_post()
	{
		if($this->iveland_view)
		{
			$bottom_heading_start = $this->graph_top_end + $this->graph_bottom_offset + 25;
			$this->graph_image->draw_rectangle(0, $bottom_heading_start, $this->graph_attr_width, $this->graph_attr_height, $this->graph_color_main_headers);
			$this->graph_image->write_text_right("Powered By " . $this->graph_version, $this->graph_font, 7, $this->graph_color_background, $this->graph_left_end, $bottom_heading_start + 7, $this->graph_left_end, $bottom_heading_start + 7, false, "http://www.phoronix-test-suite.com/");
		}
	}
	protected function render_graph_base($left_start, $top_start, $left_end, $top_end)
	{
		if($this->graph_orientation == "HORIZONTAL" || $this->iveland_view)
		{
			$this->graph_image->draw_line($left_start, $top_start, $left_start, $top_end, $this->graph_color_notches, 1);
			$this->graph_image->draw_line($left_start, $top_end, $left_end, $top_end, $this->graph_color_notches, 1);

			if(!empty($this->graph_watermark_text))
			{
				$this->graph_image->write_text_right($this->graph_watermark_text, $this->graph_font, 7, $this->graph_color_text, $left_end, $top_start - 7, $left_end, $top_start - 7, false, $this->graph_watermark_url);
			}
		}
		else
		{
			$this->graph_image->draw_rectangle_with_border($left_start, $top_start, $left_end, $top_end, $this->graph_color_body, $this->graph_color_notches);

			if($this->graph_body_image != false)
			{
				$this->graph_image->image_copy_merge($this->graph_body_image, $left_start + (($left_end - $left_start) / 2) - imagesx($this->graph_body_image) / 2, $top_start + (($top_end - $top_start) / 2) - imagesy($this->graph_body_image) / 2);
			}

			if(!empty($this->graph_watermark_text))
			{
				$this->graph_image->write_text_right($this->graph_watermark_text, $this->graph_font, 10, $this->graph_color_text, $left_end - 2, $top_start + 8, $left_end - 2, $top_start + 8, false, $this->graph_watermark_url);
			}
		}

		if(!empty($this->graph_y_title) && $this->graph_y_title_hide == false)
		{
			$str = $this->graph_y_title;
			$offset = 0;

			if(!empty($this->graph_proportion))
			{
				$proportion = null;
 
				switch($this->graph_proportion)
				{
					case "LIB":
						$proportion = "Less Is Better";
						$offset += 12;

						if($this->graph_orientation == "HORIZONTAL")
						{
							$this->graph_image->draw_arrow($left_start, $top_start - 8, $left_start + 9, $top_start - 8, $this->graph_color_text, $this->graph_color_body_light, 1);
						}
						else
						{
							$this->graph_image->draw_arrow($left_start + 4, $top_start - 4, $left_start + 4, $top_start - 11, $this->graph_color_text, $this->graph_color_body_light, 1);
						}
						break;
					case "HIB":
						$proportion = "Higher Is Better";
						$offset += 12;
						if($this->graph_orientation == "HORIZONTAL")
						{
							$this->graph_image->draw_arrow($left_start + 9, $top_start - 8, $left_start, $top_start - 8, $this->graph_color_text, $this->graph_color_body_light, 1);
						}
						else
						{
							$this->graph_image->draw_arrow($left_start + 4, $top_start - 11, $left_start + 4, $top_start - 4, $this->graph_color_text, $this->graph_color_body_light, 1);
						}
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

			$this->graph_image->write_text_left($str, $this->graph_font, 7, $this->graph_color_text, $left_start + $offset, $top_start - 7, $left_start + $offset, $top_start - 7);
		}
	}
	protected function render_graph_value_ticks($left_start, $top_start, $left_end, $top_end)
	{
		$increment = round($this->graph_maximum_value / $this->graph_attr_marks, 2);

		if($this->graph_orientation == "HORIZONTAL")
		{
			$tick_width = ($left_end - $left_start) / $this->graph_attr_marks;
			$display_value = 0;

			$this->graph_image->draw_dashed_line($left_start + $tick_width, $top_end, ($left_end - 1), $top_end, $this->graph_color_notches, 10, 1, ($tick_width - 1));

			for($i = 0; $i < $this->graph_attr_marks; $i++)
			{
				$px_from_left = $left_start + ($tick_width * $i);

				if($display_value != 0)
				{
					$this->graph_image->write_text_center($display_value, $this->graph_font, $this->graph_font_size_tick_mark, $this->graph_color_text, $px_from_left, ($top_end + 5), $px_from_left, ($top_end + 5));
				}

				if($i != 0)
				{
					$line_width = 6;
					$this->graph_image->draw_dashed_line($px_from_left, $top_start, $px_from_left + 1, $top_end - 5, $this->graph_color_body, 1, 5, 5);
				}

				$display_value += $increment;
			}

		}
		else
		{
			$tick_width = ($top_end - $top_start) / $this->graph_attr_marks;
			$px_from_left_start = $left_start - 5;
			$px_from_left_end = $left_start + 5;

			$display_value = 0;

			$this->graph_image->draw_dashed_line($left_start, $top_start + $tick_width, $left_start, ($top_end - 1), $this->graph_color_notches, 10, 1, ($tick_width - 1));

			for($i = 0; $i < $this->graph_attr_marks; $i++)
			{
				$px_from_top = $top_end - ($tick_width * $i);

				//$this->graph_image->draw_line($px_from_left_start, $px_from_top, $px_from_left_end, $px_from_top, $this->graph_color_notches);

				if($display_value != 0)
				{
					$this->graph_image->write_text_right($display_value, $this->graph_font, $this->graph_font_size_tick_mark, $this->graph_color_text, $px_from_left_start - 4, $px_from_top, $px_from_left_start - 4, $px_from_top);
				}

				if($i != 0 && $this->graph_background_lines)
				{
					$line_width = 6;
					$this->graph_image->draw_dashed_line($px_from_left_end + 6, $px_from_top + 1, $this->graph_left_end, $px_from_top + 1, $this->graph_color_body_light, 1, 5, 5);
				}

				$display_value += $increment;
			}
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
	protected function graph_key_height()
	{
		if(count($this->graph_data_title) < 2 && $this->graph_show_key == false)
		{
			return 0;
		}

		$this->graph_key_line_height = 16;
		$this->graph_key_item_width = 16 + $this->text_string_width($this->find_longest_string($this->graph_data_title), $this->graph_font, $this->graph_font_size_key);
		$this->graph_keys_per_line = floor(($this->graph_left_end - $this->graph_left_start) / $this->graph_key_item_width);

		ceil(count($this->graph_data_title) / $this->graph_keys_per_line) . ' ' . $this->graph_keys_per_line;

		return ceil(count($this->graph_data_title) / $this->graph_keys_per_line) * $this->graph_key_line_height;
	}
	protected function render_graph_key()
	{
		if($this->graph_key_line_height == 0)
		{
			return;
		}

		$key_pos = 0;
		$key_count = count($this->graph_data_title);
		$component_y = $this->graph_top_start - $this->graph_key_height() - 7;
		$this->reset_paint_index();

		for($i = 0; $i < $key_count; $i++)
		{
			if(!empty($this->graph_data_title[$i]))
			{
				$this_color = $this->get_paint_color($this->graph_data_title[$i]);
				$key_pos++;

				if($i != 0 && $key_pos % $this->graph_keys_per_line == 1)
				{
					$key_pos = 1;
					$component_y += $this->graph_key_line_height;
				}
				else if($this->is_multi_way_comparison)
				{
					$this_key_way = substr($this->graph_data_title[$i], 0, strpos($this->graph_data_title[$i], ": "));

					if($i != 0 && $this_key_way != $prev_key_way)
					{
						$key_pos = 1;
						$component_y += $this->graph_key_line_height;
					}

					$prev_key_way = $this_key_way;
				}

				$component_x = $this->graph_left_start + 13 + ($this->graph_key_item_width * ($key_pos - 1 % $this->graph_keys_per_line));

				$this->graph_image->draw_rectangle_with_border($component_x - 13, $component_y - 5, $component_x - 3, $component_y + 5, $this_color, $this->graph_color_notches);
				$this->graph_image->write_text_left($this->graph_data_title[$i], $this->graph_font, $this->graph_font_size_key, $this_color, $component_x, $component_y, $component_x, $component_y);
			}
		}
	}
	protected function return_graph_image($quality = 85)
	{
		$return_object = $this->graph_image->render_to_file($this->graph_output, $quality);
		$this->graph_image->destroy_image();

		return $return_object;
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
