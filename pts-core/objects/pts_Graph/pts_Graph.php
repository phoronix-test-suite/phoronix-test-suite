<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2011, Phoronix Media
	Copyright (C) 2008 - 2011, Michael Larabel
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
//if(PTS_IS_CLIENT || (defined('PTS_LIB_GRAPH_CONFIG_XML') && is_file(PTS_LIB_GRAPH_CONFIG_XML)))
pts_Graph::$graph_config = new pts_graph_config_nye_XmlReader();


// TODO: performance optimizations...
// OpenBenchmarking.org 1107247-LI-MESACOMMI48 is a good large data set test to render to look for speed differences

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
	protected $graph_color_highlight; // Color of any highlights
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
	protected $graph_orientation = 'VERTICAL';
	protected $graph_body_image = false;
	protected $graph_hide_identifiers = false;
	protected $graph_show_key = false;
	protected $graph_background_lines = false;
	protected $graph_value_type = 'NUMERICAL';
	protected $graph_maximum_value;

	protected $graph_output = null;
	protected $graph_renderer = 'SVG';
	protected $graph_data = array();
	protected $graph_data_raw = array();
	protected $graph_data_title = array();
	protected $graph_sub_titles = array();
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

	protected $regression_marker_threshold = 0;
	protected $graph_maximum_value_multiplier = 1.29;
	protected $is_multi_way_comparison = false;
	private $test_identifier = null;
	protected $iveland_view = false;
	protected $link_alternate_view = null;
	protected $value_highlights = array();

	public function __construct(&$result_object = null, &$result_file = null)
	{
		// Setup config values
		$this->graph_attr_width = $this->read_graph_config('PhoronixTestSuite/Graphs/General/GraphWidth'); // Graph width
		$this->graph_attr_height = $this->read_graph_config('PhoronixTestSuite/Graphs/General/GraphHeight'); // Graph height
		$this->graph_attr_big_border = $this->read_graph_config('PhoronixTestSuite/Graphs/General/Border') == 'TRUE'; // Graph border

		// Colors
		$this->graph_color_notches = $this->read_graph_config('PhoronixTestSuite/Graphs/Colors/Notches'); // Color for notches
		$this->graph_color_text = $this->read_graph_config('PhoronixTestSuite/Graphs/Colors/Text'); // Color for text
		$this->graph_color_border = $this->read_graph_config('PhoronixTestSuite/Graphs/Colors/Border'); // Color for border (if used)
		$this->graph_color_main_headers = $this->read_graph_config('PhoronixTestSuite/Graphs/Colors/MainHeaders'); // Color of main text headers
		$this->graph_color_headers = $this->read_graph_config('PhoronixTestSuite/Graphs/Colors/Headers'); // Color of other headers
		$this->graph_color_background = $this->read_graph_config('PhoronixTestSuite/Graphs/Colors/Background'); // Color of background
		$this->graph_color_body = $this->read_graph_config('PhoronixTestSuite/Graphs/Colors/GraphBody'); // Color of graph body
		$this->graph_color_body_text = $this->read_graph_config('PhoronixTestSuite/Graphs/Colors/BodyText'); // Color of graph body text
		$this->graph_color_body_light = $this->read_graph_config('PhoronixTestSuite/Graphs/Colors/Alternate'); // Color of the border around graph bars (if doing a bar graph)
		$this->graph_color_highlight = $this->read_graph_config('PhoronixTestSuite/Graphs/Colors/Highlight'); // Color for highlight
		$this->graph_color_alert = $this->read_graph_config('PhoronixTestSuite/Graphs/Colors/Alert'); // Color for alerts
		$this->graph_color_paint = pts_strings::comma_explode($this->read_graph_config('PhoronixTestSuite/Graphs/Colors/ObjectPaint')); // Colors to use for the bars / lines, one color for each key

		// Text
		$this->graph_watermark_text = $this->read_graph_config('PhoronixTestSuite/Graphs/General/Watermark'); // watermark
		$this->graph_watermark_url = $this->read_graph_config('PhoronixTestSuite/Graphs/General/WatermarkURL'); // watermark URL
		//$this->graph_font = $this->read_graph_config('PhoronixTestSuite/Graphs/Font/FontType');  // TTF file name
		$this->graph_font_size_heading = $this->read_graph_config('PhoronixTestSuite/Graphs/Font/Headers'); // Font size of headings
		$this->graph_font_size_bars = $this->read_graph_config('PhoronixTestSuite/Graphs/Font/ObjectText'); // Font size for text on the bars/objects
		$this->graph_font_size_identifiers = $this->read_graph_config('PhoronixTestSuite/Graphs/Font/Identifiers'); // Font size of identifiers
		$this->graph_font_size_sub_heading = $this->read_graph_config('PhoronixTestSuite/Graphs/Font/SubHeaders'); // Font size of headers
		$this->graph_font_size_axis_heading = $this->read_graph_config('PhoronixTestSuite/Graphs/Font/Axis'); // Font size of axis headers

		$this->graph_attr_marks = 6; // Number of marks to make on vertical axis
		$this->graph_renderer = $this->read_graph_config('PhoronixTestSuite/Graphs/General/Renderer'); // Renderer

		// Reset of setup besides config
		if($result_object != null)
		{
			$test_version = $result_object->test_profile->get_app_version();
			$this->graph_title = $result_object->test_profile->get_title() . (isset($test_version[2]) ? ' v' . $test_version : null);

			$this->graph_y_title = $result_object->test_profile->get_result_scale_formatted();
			$this->test_identifier = $result_object->test_profile->get_identifier();
			$this->graph_proportion = $result_object->test_profile->get_result_proportion();
			$this->addSubTitle($result_object->get_arguments_description());
			$this->addInternalIdentifier('Test', $result_object->test_profile->get_identifier());
		}

		$this->update_graph_dimensions(-1, -1, true);

		$default_font = bilde_renderer::find_default_ttf_font($this->graph_font);

		if($default_font == false)
		{
			$this->requestRenderer('SVG');
			$font_type = null;
		}
		else
		{
			$font_type = basename($default_font);

			if($default_font != $font_type && !defined('CUSTOM_FONT_DIR'))
			{
				$font_path = substr($default_font, 0, 0 - (strlen($font_type)));
				define('CUSTOM_FONT_DIR', $font_path);
				bilde_renderer::setup_font_directory();
			}
		}

		if($result_file != null && $result_file instanceof pts_result_file)
		{
			$this->addInternalIdentifier('Identifier', $result_file->get_identifier());
			$pts_version = pts_arrays::last_element($result_file->get_system_pts_version());
			$this->is_multi_way_comparison = $result_file->is_multi_way_comparison();
		}

		if(!isset($pts_version) || empty($pts_version))
		{
			$pts_version = PTS_VERSION;
		}

		$this->graph_version = 'Phoronix Test Suite ' . $pts_version;
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
		if($renderer != null)
		{
			$this->graph_renderer = $renderer;
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
		$sub_titles = array_map('trim', explode('|', $sub_title));

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
	public function htmlEmbedCode($file, $width = null, $height = null)
	{
		$attributes = array();

		if(in_array($this->graph_renderer, array('SWF', 'SVG')) && $width != null)
		{
			$attributes['width'] = $width;
			$attributes['height'] = $height;
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

	protected function get_paint_color($identifier)
	{
		return $this->graph_image->convert_hex_to_type(bilde_renderer::color_cache(0, $identifier, $this->graph_color_paint));
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
				$maximum = ceil($real_maximum * 1.02 / $this->graph_attr_marks) * $this->graph_attr_marks;
			}
			else
			{
				$maximum = (floor(round($real_maximum * $this->graph_maximum_value_multiplier) / $this->graph_attr_marks) + 1) * $this->graph_attr_marks;
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
		list($string_width, $string_height) = bilde_renderer::soft_text_string_dimensions($string, $font, $font_size);

		while($font_size > $minimum_font_size && $string_width > $bound_width || ($bound_height > 0 && $string_height > $bound_height))
		{
			$font_size -= 0.2;
			list($string_width, $string_height) = bilde_renderer::soft_text_string_dimensions($string, $font, $font_size);
		}

		return $font_size;
	}
	public function highlight_values($values)
	{
		$this->value_highlights = $values;
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
			if($this->graph_value_type == 'NUMERICAL')
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
			if($this->graph_orientation == 'HORIZONTAL')
			{
				if($this->is_multi_way_comparison && count($this->graph_data_title) > 1)
				{
					$longest_r = pts_strings::find_longest_string($this->graph_identifiers);
					$longest_r = explode(' - ', $longest_r);
					$plus_extra = 0;

					if(count($longest_r) > 1)
					{
						$plus_extra = count($longest_r) * $this->graph_font_size_identifiers * 1.2;
					}

					$longest_identifier_width = $this->text_string_width($this->graph_maximum_value, $this->graph_font, $this->graph_font_size_identifiers) + 60 + $plus_extra;
				}
				else
				{
					$longest_identifier_width = $this->text_string_width(pts_strings::find_longest_string($this->graph_identifiers), $this->graph_font, $this->graph_font_size_identifiers) + 8;
				}

				$longest_identifier_max = $this->graph_attr_width * 0.5;

				$this->graph_left_start = min($longest_identifier_max, max($longest_identifier_width, 70));
				$this->graph_left_end_opp = 15;
				$this->graph_left_end = $this->graph_attr_width - $this->graph_left_end_opp;
			}
			else if($this->graph_value_type == 'NUMERICAL')
			{
				$this->graph_left_start += max(20, $this->text_string_width($this->graph_maximum_value, $this->graph_font, $this->graph_font_size_tick_mark) + 2);
			}

			// Pad 8px on top and bottom + title bar + sub-headings
			$this->graph_top_heading_height = 16 + $this->graph_font_size_heading + (count($this->graph_sub_titles) * ($this->graph_font_size_sub_heading + 4));

			$key_height = $this->graph_key_height();
			if($key_height > $this->graph_key_line_height)
			{
				// Increase height so key doesn't take up too much room
				$this->graph_attr_height += $key_height;
				$this->graph_top_end += $key_height;
			}

			$this->graph_top_start = $this->graph_top_heading_height + $key_height + 16; // + spacing before graph starts

			$bottom_heading = 14;

			if($this->graph_orientation == 'HORIZONTAL')
			{
				if($this->is_multi_way_comparison && count($this->graph_data) > 1)
				{
					$longest_string = explode(' - ', pts_strings::find_longest_string($this->graph_identifiers));
					$longest_string = pts_strings::find_longest_string($longest_string);

					$rotated_text = round($this->text_string_width($longest_string, $this->graph_font, $this->graph_font_size_identifiers) * 0.96);
					$per_identifier_height = max((14 + (22 * count($this->graph_data))), $rotated_text);
				}
				else if(count($this->graph_data_title) > 3)
				{
					$per_identifier_height = count($this->graph_data_title) * 18;
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

		// Do the actual work
		$this->render_graph_pre_init();
		$this->render_graph_init();
	}
	public function setAlternateLocation($url)
	{
		// this has been replaced by setAlternateView
		return false;
	}
	public function setAlternateView($url)
	{
		$this->link_alternate_view = $url;
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

		if($this->graph_value_type == 'NUMERICAL')
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
		if(defined('PHOROMATIC_TRACKER'))
		{
			$bilde_attributes['cache_font_size'] = true;
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
		$this->graph_color_highlight = $this->graph_image->convert_hex_to_type($this->graph_color_highlight);
		$this->graph_color_alert = $this->graph_image->convert_hex_to_type($this->graph_color_alert);

		// Background Color
		if($this->iveland_view)
		{
			//$this->graph_image->draw_rectangle_with_border(0, 0, $this->graph_attr_width, $this->graph_attr_height, $this->graph_color_background, $this->graph_color_border);
			$this->graph_image->draw_rectangle_with_border(1, 1, ($this->graph_attr_width - 1), ($this->graph_attr_height - 1), $this->graph_color_background, $this->graph_color_border);
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
		$ir_value_attributes = array();

		if($this->test_identifier != null)
		{
			$ir_value_attributes['href'] = 'http://openbenchmarking.org/test/' . $this->test_identifier;
		}

		// Default to NORMAL
		if($this->iveland_view)
		{
			$this->graph_image->draw_rectangle(0, 0, $this->graph_attr_width, $this->graph_top_heading_height, $this->graph_color_main_headers);
			$this->graph_image->write_text_left(new pts_graph_ir_value($this->graph_title, $ir_value_attributes), $this->graph_font, $this->graph_font_size_heading, $this->graph_color_background, 5, 12, $this->graph_left_end, 12);

			foreach($this->graph_sub_titles as $i => $sub_title)
			{
				$vertical_offset = 12 + 4 + $this->graph_font_size_heading + ($i * ($this->graph_font_size_sub_heading + 5));
				$this->graph_image->write_text_left($sub_title, $this->graph_font, $this->graph_font_size_sub_heading, $this->graph_color_background, 5, $vertical_offset, $this->graph_left_end, $vertical_offset);
			}
		
			$this->graph_image->image_copy_merge(new pts_graph_ir_value($this->graph_image->png_image_to_type('http://www.phoronix-test-suite.com/external/pts-logo-77x40-white.png'), array('href' => 'http://www.phoronix-test-suite.com/')), $this->graph_left_end - 77, ($this->graph_top_heading_height / 40 + 2), 0, 0, 77, 40);
		}
		else
		{
			$this->graph_image->write_text_center(new pts_graph_ir_value($this->graph_title, $ir_value_attributes), $this->graph_font, $this->graph_font_size_heading, $this->graph_color_main_headers, $this->graph_left_start, 3, $this->graph_left_end, 3);

			foreach($this->graph_sub_titles as $i => $sub_title)
			{
				$this->graph_image->write_text_center($sub_title, $this->graph_font, $this->graph_font_size_sub_heading, $this->graph_color_main_headers, $this->graph_left_start, (31 + ($i * 18)), $this->graph_left_end, (31 + ($i * 18)));
			}

			if($with_version)
			{
				$this->graph_image->write_text_right(new pts_graph_ir_value($this->graph_version, array('href' => 'http://www.phoronix-test-suite.com/')), $this->graph_font, 7, $this->graph_color_body_light, $this->graph_left_end, $this->graph_top_start - 9, $this->graph_left_end, $this->graph_top_start - 9);
			}
		}
	}
	protected function render_graph_post()
	{
		if($this->iveland_view)
		{
			$bottom_heading_start = $this->graph_top_end + $this->graph_bottom_offset + 22;
			$this->graph_image->draw_rectangle(0, $bottom_heading_start, $this->graph_attr_width, $this->graph_attr_height, $this->graph_color_main_headers);
			$this->graph_image->write_text_right(new pts_graph_ir_value('Powered By ' . $this->graph_version, array('href' => 'http://www.phoronix-test-suite.com/')), $this->graph_font, 7, $this->graph_color_background, $this->graph_left_end, $bottom_heading_start + 9, $this->graph_left_end, $bottom_heading_start + 9);

			if($this->link_alternate_view != null)
			{
				$this->graph_image->image_copy_merge(new pts_graph_ir_value($this->graph_image->png_image_to_type('http://openbenchmarking.org/ob-10x16.png'), array('href' => $this->link_alternate_view)), 4, $bottom_heading_start + 1, 0, 0, 10, 16);
			}
		}
	}
	protected function render_graph_base($left_start, $top_start, $left_end, $top_end)
	{
		if($this->graph_orientation == 'HORIZONTAL' || $this->iveland_view)
		{
			$this->graph_image->draw_line($left_start, $top_start, $left_start, $top_end, $this->graph_color_notches, 1);
			$this->graph_image->draw_line($left_start, $top_end, $left_end, $top_end, $this->graph_color_notches, 1);

			if(!empty($this->graph_watermark_text))
			{
				$this->graph_image->write_text_right(new pts_graph_ir_value($this->graph_watermark_text, array('href' => $this->graph_watermark_url)), $this->graph_font, 7, $this->graph_color_text, $left_end, $top_start - 7, $left_end, $top_start - 7);
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
				$this->graph_image->write_text_right(new pts_graph_ir_value($this->graph_watermark_text, array('href' => $this->graph_watermark_url)), $this->graph_font, 10, $this->graph_color_text, $left_end - 2, $top_start + 8, $left_end - 2, $top_start + 8);
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
					case 'LIB':
						$proportion = 'Less Is Better';
						$offset += 12;

						if($this->graph_orientation == 'HORIZONTAL')
						{
							$this->graph_image->draw_arrow($left_start, $top_start - 8, $left_start + 9, $top_start - 8, $this->graph_color_text, $this->graph_color_body_light, 1);
						}
						else
						{
							$this->graph_image->draw_arrow($left_start + 4, $top_start - 4, $left_start + 4, $top_start - 11, $this->graph_color_text, $this->graph_color_body_light, 1);
						}
						break;
					case 'HIB':
						$proportion = 'More Is Better';
						$offset += 12;
						if($this->graph_orientation == 'HORIZONTAL')
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
						$str .= ', ';
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

		if($this->graph_orientation == 'HORIZONTAL')
		{
			$tick_width = round(($left_end - $left_start) / $this->graph_attr_marks);
			$display_value = 0;

			// $this->graph_image->draw_dashed_line($left_start + $tick_width, $top_end, ($left_end - 1), $top_end, $this->graph_color_notches, 10, 1, ($tick_width));

			for($i = 0; $i < $this->graph_attr_marks; $i++)
			{
				$px_from_left = $left_start + ($tick_width * $i);

				if($i != 0)
				{
					$this->graph_image->write_text_center($display_value, $this->graph_font, $this->graph_font_size_tick_mark, $this->graph_color_text, $px_from_left, ($top_end + 5), $px_from_left, ($top_end + 5));
					$this->graph_image->draw_dashed_line($px_from_left + 2, $top_start, $px_from_left + 2, $top_end - 5, $this->graph_color_body, 1, 5, 5);
					$this->graph_image->draw_line($px_from_left + 2, $top_end - 4, $px_from_left + 2, $top_end + 4, $this->graph_color_notches, 1);
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

			// $this->graph_image->draw_dashed_line($left_start, $top_start + $tick_width, $left_start, ($top_end - 1), $this->graph_color_notches, 10, 1, ($tick_width - 1));

			for($i = 0; $i < $this->graph_attr_marks; $i++)
			{
				$px_from_top = $top_end - ($tick_width * $i);

				if($i != 0)
				{
					$this->graph_image->write_text_right($display_value, $this->graph_font, $this->graph_font_size_tick_mark, $this->graph_color_text, $px_from_left_start - 4, $px_from_top, $px_from_left_start - 4, $px_from_top);

					if($this->graph_background_lines)
					{
						$this->graph_image->draw_dashed_line($px_from_left_end + 6, $px_from_top + 1, $this->graph_left_end, $px_from_top + 1, $this->graph_color_body_light, 1, 5, 5);
					}

					$this->graph_image->draw_line($left_start - 4, $px_from_top + 1, $left_start + 4, $px_from_top + 1, $this->graph_color_notches, 1);
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
		$this->graph_key_item_width = 16 + $this->text_string_width(pts_strings::find_longest_string($this->graph_data_title), $this->graph_font, $this->graph_font_size_key);
		$this->graph_keys_per_line = floor(($this->graph_left_end - $this->graph_left_start) / $this->graph_key_item_width);

		return ceil(count($this->graph_data_title) / $this->graph_keys_per_line) * $this->graph_key_line_height;
	}
	protected function render_graph_key()
	{
		if($this->graph_key_line_height == 0)
		{
			return;
		}

		$component_y = $this->graph_top_start - $this->graph_key_height() - 7;

		for($i = 0, $key_count = count($this->graph_data_title); $i < $key_count; $i++)
		{
			if(!empty($this->graph_data_title[$i]))
			{
				$this_color = $this->get_paint_color($this->graph_data_title[$i]);

				if($i != 0 && $i % $this->graph_keys_per_line == 0)
				{
					$component_y += $this->graph_key_line_height;
				}

				$component_x = $this->graph_left_start + 13 + ($this->graph_key_item_width * ($i % $this->graph_keys_per_line));

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

	protected function text_string_width($string, $font, $size)
	{
		$dimensions = bilde_renderer::soft_text_string_dimensions($string, $font, $size);
		return $dimensions[0];
	}
	protected function text_string_height($string, $font, $size)
	{
		$dimensions = bilde_renderer::soft_text_string_dimensions($string, $font, $size);
		return $dimensions[1];
	}
}

?>
