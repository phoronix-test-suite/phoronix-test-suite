<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2012, Phoronix Media
	Copyright (C) 2008 - 2012, Michael Larabel
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

	protected $c;
	public $svg_dom;

	protected $graph_proportion = null;

	// Not user-friendly changes below this line
	protected $graph_orientation = 'VERTICAL';
	protected $graph_hide_identifiers = false;
	protected $graph_show_key = false;
	protected $graph_background_lines = false;
	protected $graph_value_type = 'NUMERICAL';
	protected $graph_maximum_value;

	protected $graph_output = null;
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
		$this->c['graph']['width'] = $this->read_graph_config('PhoronixTestSuite/Graphs/General/GraphWidth'); // Graph width
		$this->c['graph']['height'] = $this->read_graph_config('PhoronixTestSuite/Graphs/General/GraphHeight'); // Graph height
		$this->c['graph']['border'] = $this->read_graph_config('PhoronixTestSuite/Graphs/General/Border') == 'TRUE'; // Graph border

		$this->c['pos']['left_start'] = 10;
		$this->c['pos']['left_end_right'] = 10;
		$this->c['pos']['top_start'] = 62;
		$this->c['pos']['top_end_bottom'] = 22;

		// Colors
		$this->c['color']['notches'] = $this->read_graph_config('PhoronixTestSuite/Graphs/Colors/Notches'); // Color for notches
		$this->c['color']['text'] = $this->read_graph_config('PhoronixTestSuite/Graphs/Colors/Text'); // Color for text
		$this->c['color']['border'] = $this->read_graph_config('PhoronixTestSuite/Graphs/Colors/Border'); // Color for border (if used)
		$this->c['color']['main_headers'] = $this->read_graph_config('PhoronixTestSuite/Graphs/Colors/MainHeaders'); // Color of main text headers
		$this->c['color']['headers'] = $this->read_graph_config('PhoronixTestSuite/Graphs/Colors/Headers'); // Color of other headers
		$this->c['color']['background'] = $this->read_graph_config('PhoronixTestSuite/Graphs/Colors/Background'); // Color of background
		$this->c['color']['body'] = $this->read_graph_config('PhoronixTestSuite/Graphs/Colors/GraphBody'); // Color of graph body
		$this->c['color']['body_text'] = $this->read_graph_config('PhoronixTestSuite/Graphs/Colors/BodyText'); // Color of graph body text
		$this->c['color']['body_light'] = $this->read_graph_config('PhoronixTestSuite/Graphs/Colors/Alternate'); // Color of the border around graph bars (if doing a bar graph)
		$this->c['color']['highlight'] = $this->read_graph_config('PhoronixTestSuite/Graphs/Colors/Highlight'); // Color for highlight
		$this->c['color']['alert'] = $this->read_graph_config('PhoronixTestSuite/Graphs/Colors/Alert'); // Color for alerts
		$this->c['color']['paint'] = pts_strings::comma_explode($this->read_graph_config('PhoronixTestSuite/Graphs/Colors/ObjectPaint')); // Colors to use for the bars / lines, one color for each key

		// Text
		$this->c['size']['tick_mark'] = 10;
		$this->c['size']['key'] = 9;

		$this->c['text']['watermark'] = $this->read_graph_config('PhoronixTestSuite/Graphs/General/Watermark'); // watermark
		$this->c['text']['watermark_url'] = $this->read_graph_config('PhoronixTestSuite/Graphs/General/WatermarkURL'); // watermark URL
		$this->c['size']['headers'] = $this->read_graph_config('PhoronixTestSuite/Graphs/Font/Headers'); // Font size of headings
		$this->c['size']['bars'] = $this->read_graph_config('PhoronixTestSuite/Graphs/Font/ObjectText'); // Font size for text on the bars/objects
		$this->c['size']['identifiers'] = $this->read_graph_config('PhoronixTestSuite/Graphs/Font/Identifiers'); // Font size of identifiers
		$this->c['size']['sub_headers'] = $this->read_graph_config('PhoronixTestSuite/Graphs/Font/SubHeaders'); // Font size of headers
		$this->c['size']['axis_headers'] = $this->read_graph_config('PhoronixTestSuite/Graphs/Font/Axis'); // Font size of axis headers

		$this->c['graph']['mark_count'] = 6; // Number of marks to make on vertical axis

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

		$this->c['text']['graph_version'] = 'Phoronix Test Suite ' . $pts_version;
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
	public function markResultRegressions($threshold)
	{
		$this->regression_marker_threshold = $threshold;
	}

	//
	// Misc Functions
	//

	protected function get_paint_color($identifier)
	{
		return pts_svg_dom::sanitize_hex(bilde_renderer::color_cache(0, $identifier, $this->c['color']['paint']));
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
			if($real_maximum < $this->c['graph']['mark_count'])
			{
				$maximum = ceil($real_maximum * 1.02 / $this->c['graph']['mark_count']) * $this->c['graph']['mark_count'];
			}
			else
			{
				$maximum = (floor(round($real_maximum * $this->graph_maximum_value_multiplier) / $this->c['graph']['mark_count']) + 1) * $this->c['graph']['mark_count'];
				$maximum = round(ceil($maximum / $this->c['graph']['mark_count']), (0 - strlen($maximum) + 2)) * $this->c['graph']['mark_count'];
			}
		}
		else
		{
			$maximum = 0;
		}

		return $maximum;
	}
	protected function text_size_bounds($string, $font_size, $minimum_font_size, $bound_width, $bound_height = -1)
	{
		list($string_width, $string_height) = pts_svg_dom::estimate_text_dimensions($string, $font_size);

		while($font_size > $minimum_font_size && $string_width > $bound_width || ($bound_height > 0 && $string_height > $bound_height))
		{
			$font_size -= 0.2;
			list($string_width, $string_height) = pts_svg_dom::estimate_text_dimensions($string, $font_size);
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
		$this->c['graph']['width'] = max($this->c['graph']['width'], $width);
		$this->c['graph']['height'] = max($this->c['graph']['height'], $height);

		if($recalculate_offsets)
		{
			$this->graph_top_end = $this->c['graph']['height'] - $this->c['pos']['top_end_bottom'];
			$this->graph_left_end = $this->c['graph']['width'] - $this->c['pos']['left_end_right'];
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
				$this->c['pos']['left_start'] += $this->text_string_width($this->graph_maximum_value, $this->c['size']['tick_mark']) + 2;
			}

			if($this->graph_hide_identifiers)
			{
				$this->graph_top_end += $this->c['pos']['top_end_bottom'] / 2;
			}

			$this->c['pos']['top_start'] += $this->graph_key_height();
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
						$plus_extra = count($longest_r) * $this->c['size']['identifiers'] * 1.2;
					}

					$longest_identifier_width = $this->text_string_width($this->graph_maximum_value, $this->c['size']['identifiers']) + 60 + $plus_extra;
				}
				else
				{
					$longest_identifier_width = $this->text_string_width(pts_strings::find_longest_string($this->graph_identifiers), $this->c['size']['identifiers']) + 8;
				}

				$longest_identifier_max = $this->c['graph']['width'] * 0.5;

				$this->c['pos']['left_start'] = min($longest_identifier_max, max($longest_identifier_width, 70));
				$this->c['pos']['left_end_right'] = 15;
				$this->graph_left_end = $this->c['graph']['width'] - $this->c['pos']['left_end_right'];
			}
			else if($this->graph_value_type == 'NUMERICAL')
			{
				$this->c['pos']['left_start'] += max(20, $this->text_string_width($this->graph_maximum_value, $this->c['size']['tick_mark']) + 2);
			}

			// Pad 8px on top and bottom + title bar + sub-headings
			$this->graph_top_heading_height = 16 + $this->c['size']['headers'] + (count($this->graph_sub_titles) * ($this->c['size']['sub_headers'] + 4));

			if($this->iveland_view)
			{
				// Ensure there is enough room to print PTS logo
				$this->graph_top_heading_height = max($this->graph_top_heading_height, 46);
			}

			$key_height = $this->graph_key_height();
			if($key_height > $this->graph_key_line_height)
			{
				// Increase height so key doesn't take up too much room
				$this->c['graph']['height'] += $key_height;
				$this->graph_top_end += $key_height;
			}

			$this->c['pos']['top_start'] = $this->graph_top_heading_height + $key_height + 16; // + spacing before graph starts

			$bottom_heading = 14;

			if($this->graph_orientation == 'HORIZONTAL')
			{
				if($this->is_multi_way_comparison && count($this->graph_data) > 1)
				{
					$longest_string = explode(' - ', pts_strings::find_longest_string($this->graph_identifiers));
					$longest_string = pts_strings::find_longest_string($longest_string);

					$rotated_text = round($this->text_string_width($longest_string, $this->c['size']['identifiers']) * 0.96);
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
				$this->graph_top_end = $this->c['pos']['top_start'] + ($num_identifiers * $per_identifier_height);
				// $this->c['pos']['top_end_bottom']
				$this->c['graph']['height'] = $this->graph_top_end + 25 + $bottom_heading;
			}
			else
			{
				$this->c['graph']['height'] += $bottom_heading + 4;
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
		$this->render_graph_base($this->c['pos']['left_start'], $this->c['pos']['top_start'], $this->graph_left_end, $this->graph_top_end);
		$this->render_graph_heading();

		if($this->graph_hide_identifiers == false)
		{
			$this->render_graph_identifiers();
		}

		if($this->graph_value_type == 'NUMERICAL')
		{
			$this->render_graph_value_ticks($this->c['pos']['left_start'], $this->c['pos']['top_start'], $this->graph_left_end, $this->graph_top_end);
		}

		$this->render_graph_result();
		$this->render_graph_post();
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
		$this->svg_dom = new pts_svg_dom(ceil($this->c['graph']['width']), ceil($this->c['graph']['height']));

		// Initalize Colors
		$this->c['color']['notches'] = pts_svg_dom::sanitize_hex($this->c['color']['notches']);
		$this->c['color']['text'] = pts_svg_dom::sanitize_hex($this->c['color']['text']);
		$this->c['color']['border'] = pts_svg_dom::sanitize_hex($this->c['color']['border']);
		$this->c['color']['main_headers'] = pts_svg_dom::sanitize_hex($this->c['color']['main_headers']);
		$this->c['color']['headers'] = pts_svg_dom::sanitize_hex($this->c['color']['headers']);
		$this->c['color']['background'] = pts_svg_dom::sanitize_hex($this->c['color']['background']);
		$this->c['color']['body'] = pts_svg_dom::sanitize_hex($this->c['color']['body']);
		$this->c['color']['body_text'] = pts_svg_dom::sanitize_hex($this->c['color']['body_text']);
		$this->c['color']['body_light'] = pts_svg_dom::sanitize_hex($this->c['color']['body_light']);
		$this->c['color']['highlight'] = pts_svg_dom::sanitize_hex($this->c['color']['highlight']);
		$this->c['color']['alert'] = pts_svg_dom::sanitize_hex($this->c['color']['alert']);

		// Background Color
		if($this->iveland_view)
		{
			$this->svg_dom->add_element('rect', array('x' => 1, 'y' => 1, 'width' => ($this->c['graph']['width'] - 1), 'height' => ($this->c['graph']['height'] - 1), 'fill' => $this->c['color']['background'], 'stroke' => $this->c['color']['border'], 'stroke-width' => 1));
		}
		else if($this->c['graph']['border'])
		{
			$this->svg_dom->add_element('rect', array('x' => 0, 'y' => 0, 'width' => $this->c['graph']['width'], 'height' => $this->c['graph']['height'], 'fill' => $this->c['color']['background'], 'stroke' => $this->c['color']['border'], 'stroke-width' => 1));
		}
		else
		{
			$this->svg_dom->add_element('rect', array('x' => 0, 'y' => 0, 'width' => $this->c['graph']['width'], 'height' => $this->c['graph']['height'], 'fill' => $this->c['color']['background']));
		}

		if($this->iveland_view == false && ($sub_title_count = count($this->graph_sub_titles)) > 1)
		{
			$this->c['pos']['top_start'] += (($sub_title_count - 1) * ($this->c['size']['sub_headers'] + 4));
		}
	}
	protected function render_graph_heading($with_version = true)
	{
		$href = null;

		if($this->test_identifier != null)
		{
			$href = 'http://openbenchmarking.org/test/' . $this->test_identifier;
		}

		// Default to NORMAL
		if($this->iveland_view)
		{
			$this->svg_dom->add_element('rect', array('x' => 0, 'y' => 0, 'width' => $this->c['graph']['width'], 'height' => $this->graph_top_heading_height, 'fill' => $this->c['color']['main_headers']));

			if(isset($this->graph_title[36]))
			{
				// If it's a long string make sure it won't run over the side...
				while(self::text_string_width($this->graph_title, $this->c['size']['headers']) > ($this->graph_left_end - 60))
				{
					$this->c['size']['headers'] -= 0.5;
				}
			}

			$this->svg_dom->add_text_element($this->graph_title, array('x' => 5, 'y' => 12, 'font-size' => $this->c['size']['headers'], 'fill' => $this->c['color']['background'], 'text-anchor' => 'start', 'dominant-baseline' => 'middle', 'xlink:show' => 'new', 'xlink:href' => $href));

			foreach($this->graph_sub_titles as $i => $sub_title)
			{
				$vertical_offset = 12 + 4 + $this->c['size']['headers'] + ($i * ($this->c['size']['sub_headers'] + 5));
				$this->svg_dom->add_text_element($sub_title, array('x' => 5, 'y' => $vertical_offset, 'font-size' => $this->c['size']['sub_headers'], 'fill' => $this->c['color']['background'], 'text-anchor' => 'start', 'dominant-baseline' => 'middle'));
			}

			$this->svg_dom->add_element('image', array('xlink:href' => 'http://www.phoronix-test-suite.com/external/pts-logo-77x40-white.png', 'x' => ($this->graph_left_end - 77), 'y' => (($this->graph_top_heading_height / 40 + 2)), 'width' => 77, 'height' => 40));
		}
		else
		{
			$this->svg_dom->add_text_element($this->graph_title, array('x' => round($this->graph_left_end / 2), 'y' => 3, 'font-size' => $this->c['size']['headers'], 'fill' => $this->c['color']['main_headers'], 'text-anchor' => 'middle', 'dominant-baseline' => 'text-before-edge', 'xlink:show' => 'new', 'xlink:href' => $href));

			foreach($this->graph_sub_titles as $i => $sub_title)
			{
				$this->svg_dom->add_text_element($sub_title, array('x' => round($this->graph_left_end / 2), 'y' => (31 + ($i * 18)), 'font-size' => $this->c['size']['sub_headers'], 'fill' => $this->c['color']['main_headers'], 'text-anchor' => 'middle', 'dominant-baseline' => 'text-before-edge'));
			}

			if($with_version)
			{
				$this->svg_dom->add_text_element($this->c['text']['graph_version'], array('x' => $this->graph_left_end , 'y' => ($this->c['pos']['top_start'] - 3), 'font-size' => 7, 'fill' => $this->c['color']['body_light'], 'text-anchor' => 'end', 'dominant-baseline' => 'bottom', 'xlink:show' => 'new', 'xlink:href' => 'http://www.phoronix-test-suite.com/'));
			}
		}
	}
	protected function render_graph_post()
	{
		if($this->iveland_view)
		{
			$bottom_heading_start = $this->graph_top_end + $this->graph_bottom_offset + 22;
			$this->svg_dom->add_element('rect', array('x' => 0, 'y' => $bottom_heading_start, 'width' => $this->c['graph']['width'], 'height' => ($this->c['graph']['height'] - $bottom_heading_start), 'fill' => $this->c['color']['main_headers']));
			$this->svg_dom->add_text_element('Powered By ' . $this->c['text']['graph_version'], array('x' => $this->graph_left_end, 'y' => ($bottom_heading_start + 9), 'font-size' => 7, 'fill' => $this->c['color']['background'], 'text-anchor' => 'end', 'dominant-baseline' => 'middle', 'xlink:show' => 'new', 'xlink:href' => 'http://www.phoronix-test-suite.com/'));

			if($this->link_alternate_view != null)
			{
				// offer link of image to $this->link_alternate_view
				$this->svg_dom->add_element('image', array('xlink:href' => 'http://openbenchmarking.org/ob-10x16.png', 'x' => 4, 'y' => ($bottom_heading_start + 1), 'width' => 10, 'height' => 16));
			}
		}
	}
	protected function render_graph_base($left_start, $top_start, $left_end, $top_end)
	{
		if($this->graph_orientation == 'HORIZONTAL' || $this->iveland_view)
		{
			$this->svg_dom->draw_svg_line($left_start, $top_start, $left_start, $top_end, $this->c['color']['notches'], 1);
			$this->svg_dom->draw_svg_line($left_start, $top_end, $left_end, $top_end, $this->c['color']['notches'], 1);

			if(!empty($this->c['text']['watermark']))
			{
				$this->svg_dom->add_text_element($this->c['text']['watermark'], array('x' => $left_end, 'y' => ($top_start - 7), 'font-size' => 7, 'fill' => $this->c['color']['text'], 'text-anchor' => 'end', 'dominant-baseline' => 'middle', 'xlink:show' => 'new', 'xlink:href' => $this->c['text']['watermark_url']));
			}
		}
		else
		{
			$this->svg_dom->add_element('rect', array('x' => $left_start, 'y' => $top_start, 'width' => ($left_end - $left_start), 'height' => ($top_end - $top_start), 'fill' => $this->c['color']['body'], 'stroke' => $this->c['color']['notches'], 'stroke-width' => 1));

			if(!empty($this->c['text']['watermark']))
			{
				$this->svg_dom->add_text_element($this->c['text']['watermark'], array('x' => ($left_end - 2), 'y' => ($top_start + 8), 'font-size' => 10, 'fill' => $this->c['color']['text'], 'text-anchor' => 'end', 'dominant-baseline' => 'middle', 'xlink:show' => 'new', 'xlink:href' => $this->c['text']['watermark_url']));
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
							$this->draw_arrow($left_start, $top_start - 8, $left_start + 9, $top_start - 8, $this->c['color']['text'], $this->c['color']['body_light'], 1);
						}
						else
						{
							$this->draw_arrow($left_start + 4, $top_start - 4, $left_start + 4, $top_start - 11, $this->c['color']['text'], $this->c['color']['body_light'], 1);
						}
						break;
					case 'HIB':
						$proportion = 'More Is Better';
						$offset += 12;
						if($this->graph_orientation == 'HORIZONTAL')
						{
							$this->draw_arrow($left_start + 9, $top_start - 8, $left_start, $top_start - 8, $this->c['color']['text'], $this->c['color']['body_light'], 1);
						}
						else
						{
							$this->draw_arrow($left_start + 4, $top_start - 11, $left_start + 4, $top_start - 4, $this->c['color']['text'], $this->c['color']['body_light'], 1);
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

			$this->svg_dom->add_text_element($str, array('x' => ($left_start + $offset), 'y' => ($top_start - 7), 'font-size' => 7, 'fill' => $this->c['color']['text'], 'text-anchor' => 'start', 'dominant-baseline' => 'middle'));
		}
	}
	protected function render_graph_value_ticks($left_start, $top_start, $left_end, $top_end)
	{
		$increment = round($this->graph_maximum_value / $this->c['graph']['mark_count'], 2);

		if($this->graph_orientation == 'HORIZONTAL')
		{
			$tick_width = round(($left_end - $left_start) / $this->c['graph']['mark_count']);
			$display_value = 0;

			for($i = 0; $i < $this->c['graph']['mark_count']; $i++)
			{
				$px_from_left = $left_start + ($tick_width * $i);

				if($i != 0)
				{
					$this->svg_dom->add_text_element($display_value, array('x' => $px_from_left, 'y' => ($top_end + 5), 'font-size' => $this->c['size']['tick_mark'], 'fill' => $this->c['color']['text'], 'text-anchor' => 'middle', 'dominant-baseline' => 'text-before-edge'));
					$this->svg_dom->draw_svg_line($px_from_left + 2, $top_start, $px_from_left + 2, $top_end - 5, $this->c['color']['body'], 1, array('stroke-dasharray' => '5,5'));
					$this->svg_dom->draw_svg_line($px_from_left + 2, $top_end - 4, $px_from_left + 2, $top_end + 4, $this->c['color']['notches'], 1);
				}

				$display_value += $increment;
			}

		}
		else
		{
			$tick_width = round(($top_end - $top_start) / $this->c['graph']['mark_count']);
			$px_from_left_start = $left_start - 5;
			$px_from_left_end = $left_start + 5;

			$display_value = 0;

			for($i = 0; $i < $this->c['graph']['mark_count']; $i++)
			{
				$px_from_top = round($top_end - ($tick_width * $i));

				if($i != 0)
				{
					$this->svg_dom->add_text_element($display_value, array('x' => ($px_from_left_start - 4), 'y' => $px_from_top, 'font-size' => $this->c['size']['tick_mark'], 'fill' =>  $this->c['color']['text'], 'text-anchor' => 'end', 'dominant-baseline' => 'middle'));

					if($this->graph_background_lines)
					{
						$this->svg_dom->draw_svg_line($px_from_left_end + 6, $px_from_top + 1, $this->graph_left_end, $px_from_top + 1, $this->c['color']['body_light'], 1, array('stroke-dasharray' => '5,5'));
					}

					$this->svg_dom->draw_svg_line($left_start, $px_from_top + 1, $left_end, $px_from_top + 1, $this->c['color']['notches'], 1, array('stroke-dasharray' => '5,5'));
					$this->svg_dom->draw_svg_line($left_start - 4, $px_from_top + 1, $left_start + 4, $px_from_top + 1, $this->c['color']['notches'], 1);
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
		$this->graph_key_item_width = 16 + $this->text_string_width(pts_strings::find_longest_string($this->graph_data_title), $this->c['size']['key']);
		$this->graph_keys_per_line = floor(($this->graph_left_end - $this->c['pos']['left_start']) / $this->graph_key_item_width);

		return ceil(count($this->graph_data_title) / $this->graph_keys_per_line) * $this->graph_key_line_height;
	}
	protected function render_graph_key()
	{
		if($this->graph_key_line_height == 0)
		{
			return;
		}

		$y = $this->c['pos']['top_start'] - $this->graph_key_height() - 7;

		for($i = 0, $key_count = count($this->graph_data_title); $i < $key_count; $i++)
		{
			if(!empty($this->graph_data_title[$i]))
			{
				$this_color = $this->get_paint_color($this->graph_data_title[$i]);

				if($i != 0 && $i % $this->graph_keys_per_line == 0)
				{
					$y += $this->graph_key_line_height;
				}

				$x = $this->c['pos']['left_start'] + 13 + ($this->graph_key_item_width * ($i % $this->graph_keys_per_line));

				$this->svg_dom->add_element('rect', array('x' => ($x - 13), 'y' => ($y - 5), 'width' => 10, 'height' => 10, 'fill' => $this_color, 'stroke' => $this->c['color']['notches'], 'stroke-width' => 1));
				$this->svg_dom->add_text_element($this->graph_data_title[$i], array('x' => $x, 'y' => $y, 'font-size' => $this->c['size']['key'], 'fill' => $this_color, 'text-anchor' => 'start', 'dominant-baseline' => 'middle'));
			}
		}
	}
	protected function draw_arrow($tip_x1, $tip_y1, $tail_x1, $tail_y1, $background_color, $border_color = null, $border_width = 0)
	{
		$is_vertical = ($tip_x1 == $tail_x1);

		if($is_vertical)
		{
			// Vertical arrow
			$arrow_length = sqrt(pow(($tail_x1 - $tip_x1), 2) + pow(($tail_y1 - $tip_y1), 2));
			$arrow_length_half = $arrow_length / 2;

			$arrow_points = array(
				$tip_x1 . ',' . $tip_y1,
				($tail_x1 + $arrow_length_half) . ',' . $tail_y1,
				($tail_x1 - $arrow_length_half) . ',' . $tail_y1
				);
		}
		else
		{
			// Horizontal arrow
			$arrow_length = sqrt(pow(($tail_x1 - $tip_x1), 2) + pow(($tail_y1 - $tip_y1), 2));
			$arrow_length_half = $arrow_length / 2;

			$arrow_points = array(
				$tip_x1 . ',' . $tip_y1,
				$tail_x1 . ',' . ($tail_y1 + $arrow_length_half),
				$tail_x1 . ',' . ($tail_y1 - $arrow_length_half)
				);
		}

		$this->svg_dom->add_element('polygon', array('points' => implode(' ', $arrow_points), 'fill' => $background_color, 'stroke' => $border_color, 'stroke-width' => $border_width));
	}
	protected function return_graph_image($quality = 85)
	{
		return null;
		//$svg_image = $this->svg_dom->save_xml();
		//unset($this->svg_dom);
		//return $this->graph_output != null ? @file_put_contents(str_replace('BILDE_EXTENSION', 'svg', $this->graph_output), $svg_image) : $svg_image;
	}

	protected function text_string_width($string, $size)
	{
		$dimensions = pts_svg_dom::estimate_text_dimensions($string, $size);
		return $dimensions[0];
	}
	protected function text_string_height($string, $size)
	{
		$dimensions = pts_svg_dom::estimate_text_dimensions($string, $size);
		return $dimensions[1];
	}
}

?>
