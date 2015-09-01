<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2015, Phoronix Media
	Copyright (C) 2008 - 2015, Michael Larabel
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

// TODO: performance optimizations...
// OpenBenchmarking.org 1107247-LI-MESACOMMI48 is a good large data set test to render to look for speed differences
// Other tests:
// 1201120-BY-MESA80GAL31

// TODO: elimiante need for some of the pts_* classes called inside here, instead build them in or find other efficient ways of handling...

// Setup main config values, should only be needed once since the configuration values should never be over-written within pts_Graph*
pts_Graph::init_graph_config();

abstract class pts_Graph
{
	// Graph config
	protected static $c = array(); // user-configurable data. no pts_Graph* should ever over-write any of this data... should be read-only.
	protected static $color_cache = array();
	protected $d = array(); // the data from the test result / whatever... important data
	protected $i = array(); // internal data, pts_Graph* can read-write
	public $svg_dom = null;

	// TODO: Convert below variables to using $this->[XXX]
	protected $graph_data = array();
	protected $graph_data_raw = array();
	protected $graph_data_title = array();
	protected $graph_sub_titles = array();
	protected $graph_identifiers;
	protected $graph_title;
	protected $graph_y_title;
	protected $is_multi_way_comparison = false;
	private $test_identifier = null;
	protected $value_highlights = array();

	public function __construct(&$result_object = null, &$result_file = null)
	{
		// Initalize Colors
		$this->i['identifier_size'] = self::$c['size']['identifiers']; // Copy this since it's commonly overwritten
		$this->i['graph_orientation'] = 'VERTICAL';
		$this->i['graph_value_type'] = 'NUMERICAL';
		$this->i['hide_graph_identifiers'] = false;
		$this->i['show_graph_key'] = false;
		$this->i['show_background_lines'] = false;
		$this->i['iveland_view'] = false;
		$this->i['graph_max_value_multiplier'] = 1.285;
		$this->i['graph_max_value'] = 0;
		$this->i['bottom_offset'] = 0;
		$this->i['hide_y_title'] = false;
		$this->i['compact_result_view'] = false;
		$this->i['key_line_height'] = 0;
		$this->i['graph_height'] = 0;
		$this->i['graph_width'] = 0;
		$this->i['left_start'] = 10;
		$this->i['left_end_right'] = 10;
		$this->i['top_start'] = 62;
		$this->i['top_end_bottom'] = 22;
		$this->i['mark_count'] = 6; // Number of marks to make on vertical axis
		$this->i['notes'] = array();

		// Reset of setup besides config
		if($result_object != null)
		{
			$test_version = $result_object->test_profile->get_app_version();

			if(isset($test_version[2]) && is_numeric($test_version[0]))
			{
				$test_version = 'v' . $test_version;
			}

			$this->graph_title = trim($result_object->test_profile->get_title() . ' ' . $test_version);

			$this->graph_y_title = $result_object->test_profile->get_result_scale_formatted();
			$this->test_identifier = $result_object->test_profile->get_identifier();
			$this->i['graph_proportion'] = $result_object->test_profile->get_result_proportion();
			$this->addSubTitle($result_object->get_arguments_description());
		}

		$this->update_graph_dimensions(self::$c['graph']['width'], self::$c['graph']['height'], true);

		if($result_file != null && $result_file instanceof pts_result_file)
		{
			$this->is_multi_way_comparison = $result_file->is_multi_way_comparison();
		}
		$this->i['graph_version'] = 'Phoronix Test Suite ' . PTS_VERSION;
	}
	public function override_i_value($key, $val)
	{
		$this->i[$key] = $val;
	}
	public static function init_graph_config($external_config = null)
	{
		self::set_default_graph_values(self::$c);

		if($external_config && is_array($external_config))
		{
			self::$c = array_merge(self::$c, $external_config);
		}
	}
	public static function set_default_graph_values(&$config)
	{
		// Setup config values
		$config['graph']['width'] = 600;
		$config['graph']['height'] = 310;
		$config['graph']['border'] = true;

		// Colors
		$config['color']['notches'] = '#757575';
		$config['color']['text'] = '#065695';
		$config['color']['border'] = '#757575';
		$config['color']['main_headers'] = '#231f20';
		$config['color']['headers'] = '#231f20';
		$config['color']['background'] = '#FEFEFE';
		$config['color']['body'] = '#BABABA';
		$config['color']['body_text'] = '#FFFFFF';
		$config['color']['body_light'] = '#949494';
		$config['color']['highlight'] = '#005a00';
		$config['color']['alert'] = '#C80000';
		$config['color']['paint'] = array('#065695', '#e12128', '#009345', '#1b75bb', '#a3365c', '#2794A9', '#ff5800', '#221914', '#AC008A', '#E00022', '#3A9137', '#00F6FF', '#8A00AC', '#949200', '#797766', '#5598b1', '#555555', '#757575', '#999999', '#CCCDDD');

		// Text
		$config['size']['tick_mark'] = 10;
		$config['size']['key'] = 9;

		if(defined('OPENBENCHMARKING_BUILD'))
		{
			$config['text']['watermark'] = 'OpenBenchmarking.org';
			$config['text']['watermark_url'] = 'http://www.openbenchmarking.org/';
		}
		else
		{
			$config['text']['watermark'] = 'PHORONIX-TEST-SUITE.COM';
			$config['text']['watermark_url'] = 'http://www.phoronix-test-suite.com/';
		}

		$config['size']['headers'] = 17;
		$config['size']['bars'] = 11;
		$config['size']['identifiers'] = 10;
		$config['size']['sub_headers'] = 11;
		$config['size']['axis_headers'] = 10;
	}

	//
	// Load Functions
	//

	public function loadGraphIdentifiers($data_array)
	{
		$this->graph_identifiers = $data_array;
	}
	public function addGraphIdentifierNote($identifier, $note)
	{
		if(!isset($this->d['identifier_notes'][$identifier]) || empty($this->d['identifier_notes'][$identifier]))
		{
			$this->d['identifier_notes'][$identifier] = $note;
		}
		else
		{
			$this->d['identifier_notes'][$identifier] .= ' - ' . $note;
		}
	}
	public function hideGraphIdentifiers()
	{
		$this->i['hide_graph_identifiers'] = true;
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
	public function addTestNote($note, $hover_title = null, $section = null)
	{
		array_push($this->i['notes'], array('note' => $note, 'hover-title' => $hover_title, 'section' => $section));
	}
	public function markResultRegressions($threshold)
	{
		$this->d['regression_marker_threshold'] = $threshold;
	}

	//
	// Misc Functions
	//

	protected function get_paint_color($identifier)
	{
		return self::color_cache(0, $identifier, self::$c['color']['paint']);
	}
	protected function get_special_paint_color($identifier)
	{
		// For now to try to improve the color handling of line graphs, first try to use a pre-defined pool of colors until falling back to the old color code once exhausted
		// Thanks to ua=42 in the Phoronix Forums for the latest attempt at improving the automated color handling
		static $predef_line_colors = array('#FFB300', '#803E75', '#FF6800', '#A6BDD7', '#C10020', '#CEA262', '#817066', '#007D34', '#F6768E', '#00538A', '#FF7A5C', '#53377A', '#FF8E00', '#B32851', '#F4C800', '#7F180D', '#93AA00', '#593315', '#F13A13', '#232C16');

		if(!isset(self::$color_cache[0][$identifier]))
		{
			if(!empty($predef_line_colors))
			{
				self::$color_cache[0][$identifier] = array_pop($predef_line_colors);
			}
			else
			{
				self::$color_cache[0][$identifier] = $this->get_paint_color($identifier);
			}
		}

		return self::$color_cache[0][$identifier];
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
			if($real_maximum < $this->i['mark_count'])
			{
				$maximum = (($real_maximum * 1.35 / $this->i['mark_count']) * $this->i['mark_count']);

				if($maximum > 1)
				{
					round($maximum);
				}
			}
			else
			{
				$maximum = (floor(round($real_maximum * $this->i['graph_max_value_multiplier']) / $this->i['mark_count']) + 1) * $this->i['mark_count'];
				$maximum = round(ceil($maximum / $this->i['mark_count']), (0 - strlen($maximum) + 2)) * $this->i['mark_count'];
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
			$font_size -= 0.3;
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
		$this->i['graph_width'] = max($this->i['graph_width'], $width);
		$this->i['graph_height'] = max($this->i['graph_height'], $height);

		if($recalculate_offsets)
		{
			$this->i['graph_top_end'] = $this->i['graph_height'] - $this->i['top_end_bottom'];
			$this->i['graph_left_end'] = $this->i['graph_width'] - $this->i['left_end_right'];
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
		$this->render_graph_dimensions();
		$this->render_graph_pre_init();
		$this->render_graph_init();
	}
	public function render_graph_dimensions()
	{
		$this->i['graph_max_value'] = $this->maximum_graph_value();

		// Make room for tick markings, left hand side
		if($this->i['iveland_view'] == false)
		{
			if($this->i['graph_value_type'] == 'NUMERICAL')
			{
				$this->i['left_start'] += $this->text_string_width($this->i['graph_max_value'], self::$c['size']['tick_mark']) + 2;
			}

			if($this->i['hide_graph_identifiers'])
			{
				$this->i['graph_top_end'] += $this->i['top_end_bottom'] / 2;
			}

			$this->i['top_start'] += $this->graph_key_height();
		}
		else
		{
			if($this->i['graph_orientation'] == 'HORIZONTAL')
			{
				if($this->is_multi_way_comparison && count($this->graph_data_title) > 1)
				{
					$longest_r = pts_strings::find_longest_string($this->graph_identifiers);
					$longest_r = explode(' - ', $longest_r);
					$plus_extra = 0;

					if(count($longest_r) > 1)
					{
						$plus_extra = count($longest_r) * $this->i['identifier_size'] * 1.2;
					}

					$longest_identifier_width = $this->text_string_width($this->i['graph_max_value'], $this->i['identifier_size']) + 60 + $plus_extra;
				}
				else
				{
					$longest_identifier_width = $this->text_string_width(pts_strings::find_longest_string($this->graph_identifiers), $this->i['identifier_size']) + 8;
				}

				$longest_identifier_max = ($this->i['graph_width'] * 0.5) + 0.01;

				$this->i['left_start'] = min($longest_identifier_max, max($longest_identifier_width, 70));
				$this->i['left_end_right'] = 15;
				$this->i['graph_left_end'] = $this->i['graph_width'] - $this->i['left_end_right'];
			}
			else if($this->i['graph_value_type'] == 'NUMERICAL')
			{
				$this->i['left_start'] += max(20, $this->text_string_width($this->i['graph_max_value'] + 0.01, self::$c['size']['tick_mark']) + 2);
			}

			// Pad 8px on top and bottom + title bar + sub-headings
			$this->i['top_heading_height'] = 16 + self::$c['size']['headers'] + (count($this->graph_sub_titles) * (self::$c['size']['sub_headers'] + 4));

			if($this->i['iveland_view'])
			{
				// Ensure there is enough room to print PTS logo
				$this->i['top_heading_height'] = max($this->i['top_heading_height'], 46);
			}

			$key_height = $this->graph_key_height();
			if($key_height > $this->i['key_line_height'])
			{
				// Increase height so key doesn't take up too much room
				$this->i['graph_height'] += $key_height;
				$this->i['graph_top_end'] += $key_height;
			}

			$this->i['top_start'] = $this->i['top_heading_height'] + $key_height + 16; // + spacing before graph starts

			$bottom_heading = 14;

			if($this->i['graph_orientation'] == 'HORIZONTAL')
			{
				if($this->is_multi_way_comparison && count($this->graph_data) > 1)
				{
					$longest_string = explode(' - ', pts_strings::find_longest_string($this->graph_identifiers));
					$longest_string = pts_strings::find_longest_string($longest_string);

					$rotated_text = round($this->text_string_width($longest_string, $this->i['identifier_size']) * 0.96);
					$per_identifier_height = max((14 + (22 * count($this->graph_data))), $rotated_text);
				}
				else if(count($this->graph_data_title) > 3)
				{
					$per_identifier_height = count($this->graph_data_title) * 18;
				}
				else
				{
					// If there's too much to plot, reduce the size so each graph doesn't take too much room
					$id_count = count($this->graph_data[0]);
					if($id_count < 10)
					{
						$per_identifier_height = 46;
					}
					else if($id_count < 20)
					{
						$per_identifier_height = 36;
					}
					else if($id_count <= 38)
					{
						$this->i['compact_result_view'] = true;
						$per_identifier_height = 30;
					}
					else
					{
						$this->i['compact_result_view'] = true;
						$per_identifier_height = 26;
					}
				}


				$num_identifiers = count($this->graph_identifiers);
				$this->i['graph_top_end'] = $this->i['top_start'] + ($num_identifiers * $per_identifier_height);
				// $this->i['top_end_bottom']
				$this->i['graph_height'] = $this->i['graph_top_end'] + 25 + $bottom_heading;
			}
			else
			{
				$this->i['graph_height'] += $bottom_heading + 4;
			}

			if(!empty($this->i['notes']))
			{
				$this->i['graph_height'] += $this->note_display_height();
			}
		}
	}
	public function setAlternateLocation($url)
	{
		// this has been replaced by setAlternateView
		return false;
	}
	public function setAlternateView($url)
	{
		$this->d['link_alternate_view'] = $url;
	}
	public function render_graph_finish()
	{
		$this->render_graph_key();
		$this->render_graph_base($this->i['left_start'], $this->i['top_start'], $this->i['graph_left_end'], $this->i['graph_top_end']);
		$this->render_graph_heading();

		if($this->i['hide_graph_identifiers'] == false)
		{
			$this->render_graph_identifiers();
		}

		if($this->i['graph_value_type'] == 'NUMERICAL')
		{
			$this->render_graph_value_ticks($this->i['left_start'], $this->i['top_start'], $this->i['graph_left_end'], $this->i['graph_top_end']);
		}

		$this->render_graph_result();
		$this->render_graph_post();
	}
	protected function render_graph_pre_init()
	{
		return;
	}
	protected function render_graph_init()
	{
		$this->update_graph_dimensions();
		$this->svg_dom = new pts_svg_dom(ceil($this->i['graph_width']), ceil($this->i['graph_height']));

		// Background Color
		if($this->i['iveland_view'] || self::$c['graph']['border'])
		{
			$this->svg_dom->add_element('rect', array('x' => 1, 'y' => 1, 'width' => ($this->i['graph_width'] - 2), 'height' => ($this->i['graph_height'] - 1), 'fill' => self::$c['color']['background'], 'stroke' => self::$c['color']['border'], 'stroke-width' => 1));
		}
		else
		{
			$this->svg_dom->add_element('rect', array('x' => 0, 'y' => 0, 'width' => $this->i['graph_width'], 'height' => $this->i['graph_height'], 'fill' => self::$c['color']['background']));
		}

		if($this->i['iveland_view'] == false && ($sub_title_count = count($this->graph_sub_titles)) > 1)
		{
			$this->i['top_start'] += (($sub_title_count - 1) * (self::$c['size']['sub_headers'] + 4));
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
		if($this->i['iveland_view'])
		{
			$this->svg_dom->add_element('rect', array('x' => 0, 'y' => 0, 'width' => $this->i['graph_width'], 'height' => $this->i['top_heading_height'], 'fill' => self::$c['color']['main_headers']));

			if(isset($this->graph_title[36]))
			{
				// If it's a long string make sure it won't run over the side...
				while(self::text_string_width($this->graph_title, self::$c['size']['headers']) > ($this->i['graph_left_end'] - 20))
				{
					self::$c['size']['headers'] -= 0.5;
				}
			}

			$this->svg_dom->add_text_element($this->graph_title, array('x' => 6, 'y' => (self::$c['size']['headers'] + 2), 'font-size' => self::$c['size']['headers'], 'fill' => self::$c['color']['background'], 'text-anchor' => 'start', 'xlink:show' => 'new', 'xlink:href' => $href));

			foreach($this->graph_sub_titles as $i => $sub_title)
			{
				$vertical_offset = 12 + self::$c['size']['headers'] + (($i + 1) * (self::$c['size']['sub_headers'] - 4));
				$sub_title_size = self::$c['size']['sub_headers'];
				if(isset($sub_title[69]))
				{
					while($this->text_string_width($sub_title, $sub_title_size) > ($this->i['graph_left_end'] - 20))
						$sub_title_size -= 0.5;
				}
				$this->svg_dom->add_text_element($sub_title, array('x' => 6, 'y' => $vertical_offset, 'font-size' => $sub_title_size, 'fill' => self::$c['color']['background'], 'text-anchor' => 'start'));
			}

			$this->svg_dom->add_element('image', array('http_link' => 'http://www.phoronix-test-suite.com/', 'xlink:href' => pts_svg_dom::embed_png_image(PTS_CORE_STATIC_PATH . 'images/pts-77x40-white.png'), 'x' => ($this->i['graph_left_end'] - 77), 'y' => (($this->i['top_heading_height'] / 40 + 2)), 'width' => 77, 'height' => 40));
		}
		else
		{
			$this->svg_dom->add_text_element($this->graph_title, array('x' => round($this->i['graph_left_end'] / 2), 'y' => (self::$c['size']['headers'] + 2), 'font-size' => self::$c['size']['headers'], 'fill' => self::$c['color']['main_headers'], 'text-anchor' => 'middle', 'xlink:show' => 'new', 'xlink:href' => $href));

			foreach($this->graph_sub_titles as $i => $sub_title)
			{
				$this->svg_dom->add_text_element($sub_title, array('x' => round($this->i['graph_left_end'] / 2), 'y' => (31 + (($i + 1) * 18)), 'font-size' => self::$c['size']['sub_headers'], 'fill' => self::$c['color']['main_headers'], 'text-anchor' => 'middle'));
			}

			if($with_version)
			{
				$this->svg_dom->add_text_element($this->i['graph_version'], array('x' => $this->i['graph_left_end'] , 'y' => ($this->i['top_start'] - 3), 'font-size' => 7, 'fill' => self::$c['color']['body_light'], 'text-anchor' => 'end', 'xlink:show' => 'new', 'xlink:href' => 'http://www.phoronix-test-suite.com/'));
			}
		}
	}
	protected function render_graph_post()
	{
		if($this->i['iveland_view'])
		{
			$bottom_heading_start = $this->i['graph_top_end'] + $this->i['bottom_offset'] + 22;
			$this->svg_dom->add_element('rect', array('x' => 0, 'y' => $bottom_heading_start, 'width' => $this->i['graph_width'], 'height' => ($this->i['graph_height'] - $bottom_heading_start), 'fill' => self::$c['color']['main_headers']));
			$this->svg_dom->add_text_element($this->i['graph_version'], array('x' => $this->i['graph_left_end'], 'y' => ($bottom_heading_start + self::$c['size']['key'] + 3), 'font-size' => self::$c['size']['key'], 'fill' => self::$c['color']['background'], 'text-anchor' => 'end', 'xlink:show' => 'new', 'xlink:href' => 'http://www.phoronix-test-suite.com/'));

			if(isset($this->d['link_alternate_view']) && $this->d['link_alternate_view'])
			{
				// offer link of image to $this->d['link_alternate_view']
				$this->svg_dom->add_element('image', array('http_link' => $this->d['link_alternate_view'], 'xlink:href' => pts_svg_dom::embed_png_image(PTS_CORE_STATIC_PATH . 'images/ob-10x16.png'), 'x' => 4, 'y' => ($bottom_heading_start + 1), 'width' => 10, 'height' => 16));
			}

			if(!empty($this->i['notes']))
			{
				$estimated_height = 0;
				foreach($this->i['notes'] as $i => $note_r)
				{
					$this->svg_dom->add_textarea_element(($i + 1) . '. ' . $note_r['note'], array('x' => 5, 'y' => ($bottom_heading_start + (self::$c['size']['key'] * 2) + 8 + $estimated_height), 'font-size' => (self::$c['size']['key'] - 1), 'fill' => self::$c['color']['background'], 'text-anchor' => 'start', 'xlink:title' => $note_r['hover-title']), $estimated_height);
				}
			}
		}
	}
	protected function render_graph_base($left_start, $top_start, $left_end, $top_end)
	{
		if($this->i['graph_orientation'] == 'HORIZONTAL' || $this->i['iveland_view'])
		{
			$this->svg_dom->draw_svg_line($left_start, $top_start, $left_start, $top_end, self::$c['color']['notches'], 1);
			$this->svg_dom->draw_svg_line($left_start, $top_end, $left_end, $top_end, self::$c['color']['notches'], 1);

			if(!empty(self::$c['text']['watermark']))
			{
				$this->svg_dom->add_text_element(self::$c['text']['watermark'], array('x' => $left_end, 'y' => ($top_start - 5), 'font-size' => 7, 'fill' => self::$c['color']['text'], 'text-anchor' => 'end', 'xlink:show' => 'new', 'xlink:href' => self::$c['text']['watermark_url']));
			}
		}
		else
		{
			$this->svg_dom->add_element('rect', array('x' => $left_start, 'y' => $top_start, 'width' => ($left_end - $left_start), 'height' => ($top_end - $top_start), 'fill' => self::$c['color']['body'], 'stroke' => self::$c['color']['notches'], 'stroke-width' => 1));

			if(self::$c['text']['watermark'] != null)
			{
				$this->svg_dom->add_text_element(self::$c['text']['watermark'], array('x' => ($left_end - 2), 'y' => ($top_start + 12), 'font-size' => 10, 'fill' => self::$c['color']['text'], 'text-anchor' => 'end', 'xlink:show' => 'new', 'xlink:href' => self::$c['text']['watermark_url']));
			}
		}

		if(!empty($this->graph_y_title) && $this->i['hide_y_title'] == false)
		{
			$str = $this->graph_y_title;
			$offset = 0;

			if($this->i['graph_proportion'] != null)
			{
				$proportion = null;

				switch($this->i['graph_proportion'])
				{
					case 'LIB':
						$proportion = 'Less Is Better';
						$offset += 12;

						if($this->i['graph_orientation'] == 'HORIZONTAL')
						{
							$this->draw_arrow($left_start, $top_start - 8, $left_start + 9, $top_start - 8, self::$c['color']['text'], self::$c['color']['body_light'], 1);
						}
						else
						{
							$this->draw_arrow($left_start + 4, $top_start - 4, $left_start + 4, $top_start - 11, self::$c['color']['text'], self::$c['color']['body_light'], 1);
						}
						break;
					case 'HIB':
						$proportion = 'More Is Better';
						$offset += 12;
						if($this->i['graph_orientation'] == 'HORIZONTAL')
						{
							$this->draw_arrow($left_start + 9, $top_start - 8, $left_start, $top_start - 8, self::$c['color']['text'], self::$c['color']['body_light'], 1);
						}
						else
						{
							$this->draw_arrow($left_start + 4, $top_start - 11, $left_start + 4, $top_start - 4, self::$c['color']['text'], self::$c['color']['body_light'], 1);
						}
						break;
				}

				if($proportion)
				{
					if($str)
					{
						$str .= ', ';
					}
					$str .= $proportion;
				}
			}

			$this->svg_dom->add_text_element($str, array('x' => ($left_start + $offset), 'y' => ($top_start - 5), 'font-size' => 7, 'fill' => self::$c['color']['text'], 'text-anchor' => 'start'));
		}
	}
	protected function render_graph_value_ticks($left_start, $top_start, $left_end, $top_end, $show_numbers = true)
	{
		$increment = round($this->i['graph_max_value'] / $this->i['mark_count'], $this->i['graph_max_value'] < 10 ? 4 : 2);

		if($this->i['graph_orientation'] == 'HORIZONTAL')
		{
			$tick_width = round(($left_end - $left_start) / $this->i['mark_count']);
			$display_value = 0;

			for($i = 0; $i < $this->i['mark_count']; $i++)
			{
				$px_from_left = $left_start + ($tick_width * $i);

				if($i != 0)
				{
					$show_numbers && $this->svg_dom->add_text_element($display_value, array('x' => $px_from_left, 'y' => ($top_end + 5 + self::$c['size']['tick_mark']), 'font-size' => self::$c['size']['tick_mark'], 'fill' => self::$c['color']['text'], 'text-anchor' => 'middle'));
					$this->svg_dom->draw_svg_line($px_from_left + 2, $top_start, $px_from_left + 2, $top_end - 5, self::$c['color']['body'], 1, array('stroke-dasharray' => '5,5'));
					$this->svg_dom->draw_svg_line($px_from_left + 2, $top_end - 4, $px_from_left + 2, $top_end + 4, self::$c['color']['notches'], 1);
				}

				$display_value += $increment;
			}

		}
		else
		{
			$tick_width = round(($top_end - $top_start) / $this->i['mark_count']);
			$px_from_left_start = $left_start - 5;
			$px_from_left_end = $left_start + 5;

			$display_value = 0;

			for($i = 0; $i < $this->i['mark_count']; $i++)
			{
				$px_from_top = round($top_end - ($tick_width * $i));

				if($i != 0)
				{
					$show_numbers && $this->svg_dom->add_text_element($display_value, array('x' => ($px_from_left_start - 4), 'y' => round($px_from_top + (self::$c['size']['tick_mark'] / 2)), 'font-size' => self::$c['size']['tick_mark'], 'fill' =>  self::$c['color']['text'], 'text-anchor' => 'end'));

					if($this->i['show_background_lines'])
					{
						$this->svg_dom->draw_svg_line($px_from_left_end + 6, $px_from_top + 1, $this->i['graph_left_end'], $px_from_top + 1, self::$c['color']['body_light'], 1, array('stroke-dasharray' => '5,5'));
					}

					$this->svg_dom->draw_svg_line($left_start, $px_from_top + 1, $left_end, $px_from_top + 1, self::$c['color']['notches'], 1, array('stroke-dasharray' => '5,5'));
					$this->svg_dom->draw_svg_line($left_start - 4, $px_from_top + 1, $left_start + 4, $px_from_top + 1, self::$c['color']['notches'], 1);
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
		if(count($this->graph_data_title) < 2 && $this->i['show_graph_key'] == false)
		{
			return 0;
		}

		$this->i['key_line_height'] = 16;
		$this->i['key_item_width'] = 16 + $this->text_string_width(pts_strings::find_longest_string($this->graph_data_title), self::$c['size']['key']);
		$this->i['keys_per_line'] = max(1, floor(($this->i['graph_left_end'] - $this->i['left_start']) / $this->i['key_item_width']));

		return ceil(count($this->graph_data_title) / $this->i['keys_per_line']) * $this->i['key_line_height'];
	}
	protected function render_graph_key()
	{
		if($this->i['key_line_height'] == 0)
		{
			return;
		}

		$y = $this->i['top_start'] - $this->graph_key_height() - 7;

		for($i = 0, $key_count = count($this->graph_data_title); $i < $key_count; $i++)
		{
			if(!empty($this->graph_data_title[$i]))
			{
				$this_color = $this->get_special_paint_color($this->graph_data_title[$i]);

				if($i != 0 && $i % $this->i['keys_per_line'] == 0)
				{
					$y += $this->i['key_line_height'];
				}

				$x = $this->i['left_start'] + 13 + ($this->i['key_item_width'] * ($i % $this->i['keys_per_line']));

				$this->svg_dom->add_element('rect', array('x' => ($x - 13), 'y' => ($y - 5), 'width' => 10, 'height' => 10, 'fill' => $this_color, 'stroke' => self::$c['color']['notches'], 'stroke-width' => 1));
				$this->svg_dom->add_text_element($this->graph_data_title[$i], array('x' => $x, 'y' => ($y + 4), 'font-size' => self::$c['size']['key'], 'fill' => $this_color, 'text-anchor' => 'start'));
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
	public static function color_cache($ns, $id, &$colors)
	{
		static $color_shift = 0;
		$i = count(self::$color_cache);
		$color_shift_size = ($i == 0 ? 120 : 360 / $i); // can't be assigned directly to static var

		if(!isset(self::$color_cache[$ns][$id]))
		{
			if(!isset(self::$color_cache[$ns]))
			{
				self::$color_cache[$ns] = array();
			}

			do
			{
				if(empty($colors))
				{
					return false;
				}

				$hsl = self::color_rgb_to_hsl($colors[0]);
				$hsl = self::shift_hsl($hsl, $color_shift % 360);
				$color = self::color_hsl_to_hex($hsl);

				$color_shift += $color_shift_size;
				if($color_shift == ($color_shift_size * 3))
				{
					$color_shift_size *= 0.3;
					$colors[0] = self::color_shade($colors[0], 0.9, 1);
				}
				else if($color_shift > 630)
				{
					// We have already exhausted the cache pool once
					array_shift($colors);
					$color_shift = 0;
				}
			}
			while(in_array($color, self::$color_cache[$ns]));
			self::$color_cache[$ns][$id] = $color;
		}

		return self::$color_cache[$ns][$id];
	}
	public static function color_hex_to_rgb($hex)
	{
		$color = hexdec($hex);

		return array(
			'r' => ($color >> 16) & 0xff,
			'g' => ($color >> 8) & 0xff,
			'b' => $color & 0xff
			);
	}
	protected function note_display_height()
	{
		// This basically figures out how many lines of notes there are times the size of the font key...
		// additionally, it attempts to figure out if the note will word-wrap to additional lines to accomodate that
		$note_height = 0;
		$sections = array();
		if(!empty($this->i['notes']))
		{
			foreach($this->i['notes'] as $note)
			{
				// If the note isn't at least 36 characters long, assume it's not long enough to word-wrap, so take short-cut for efficiency
				$note_height += !isset($note['note'][36]) ? (self::$c['size']['key'] + 2) : (ceil($this->text_string_width($note['note'], self::$c['size']['key']) / ($this->i['graph_width'] - 14))) * self::$c['size']['key'];

				if($note['section'] != null)
				{
					array_push($sections, $note['section']);
				}
			}
			$note_height += self::$c['size']['key'];
		}
		$note_height += ((count(array_unique($sections)) + 1) * (self::$c['size']['key'] + 6));

		return $note_height;
	}
	public static function color_hsl_to_hex($hsl)
	{
		if($hsl['s'] == 0)
		{
			$rgb['r'] = $hsl['l'] * 255;
			$rgb['g'] = $hsl['l'] * 255;
			$rgb['b'] = $hsl['l'] * 255;
		}
		else
		{
			$conv2 = $hsl['l'] < 0.5 ? $hsl['l'] * (1 + $hsl['s']) : ($hsl['l'] + $hsl['s']) - ($hsl['l'] * $hsl['s']);
			$conv1 = 2 * $hsl['l'] - $conv2;
			$rgb['r'] = round(255 * self::color_hue_convert($conv1, $conv2, $hsl['h'] + (1 / 3)));
			$rgb['g'] = round(255 * self::color_hue_convert($conv1, $conv2, $hsl['h']));
			$rgb['b'] = round(255 * self::color_hue_convert($conv1, $conv2, $hsl['h'] - (1 / 3)));
		}

		return self::color_rgb_to_hex($rgb['r'], $rgb['g'], $rgb['b']);
	}
	protected static function color_hue_convert($v1, $v2, $vh)
	{
		if($vh < 0)
		{
			$vh += 1;
		}

		if($vh > 1)
		{
			$vh -= 1;
		}

		if((6 * $vh) < 1)
		{
			return $v1 + ($v2 - $v1) * 6 * $vh;
		}

		if((2 * $vh) < 1)
		{
			return $v2;
		}

		if((3 * $vh) < 2)
		{
			return $v1 + ($v2 - $v1) * ((2 / 3 - $vh) * 6);
		}

		return $v1;
	}
	public static function color_rgb_to_hsl($hex)
	{
		$rgb = self::color_hex_to_rgb($hex);

		foreach($rgb as &$value)
		{
			$value = $value / 255;
		}

		$min = min($rgb);
		$max = max($rgb);
		$delta = $max - $min;

		$hsl['l'] = ($max + $min) / 2;

		if($delta == 0)
		{
			$hsl['h'] = 0;
			$hsl['s'] = 0;
		}
		else
		{
			$hsl['s'] = $delta / ($hsl['l'] < 0.5 ? $max + $min : 2 - $max - $min);

			$delta_rgb = array();
			foreach($rgb as $color => $value)
			{
				$delta_rgb[$color] = ((($max - $value) / 6) + ($max / 2)) / $delta;
			}

			switch($max)
			{
				case $rgb['r']:
					$hsl['h'] = $delta_rgb['b'] - $delta_rgb['g'];
					break;
				case $rgb['g']:
					$hsl['h'] = (1 / 3) + $delta_rgb['r'] - $delta_rgb['b'];
					break;
				case $rgb['b']:
				default:
					$hsl['h'] = (2 / 3) + $delta_rgb['g'] - $delta_rgb['r'];
					break;
			}

			$hsl['h'] += $hsl['h'] < 0 ? 1 : 0;
			$hsl['h'] -= $hsl['h'] > 1 ? 1 : 0;
		}

		return $hsl;
	}
	public static function shift_hsl($hsl, $rotate_h_degrees = 180)
	{
		if($rotate_h_degrees > 0)
		{
			$rotate_dec = $rotate_h_degrees / 360;
			$hsl['h'] = $hsl['h'] <= $rotate_dec ? $hsl['h'] + $rotate_dec : $hsl['h'] - $rotate_dec;
		}

		return $hsl;
	}
	public static function color_rgb_to_hex($r, $g, $b)
	{
		$color = ($r << 16) | ($g << 8) | $b;
		return '#' . sprintf('%06x', $color);
	}
	public static function color_gradient($color1, $color2, $color_weight)
	{
		$color1 = self::color_hex_to_rgb($color1);
		$color2 = self::color_hex_to_rgb($color2);

		$diff_r = $color2['r'] - $color1['r'];
		$diff_g = $color2['g'] - $color1['g'];
		$diff_b = $color2['b'] - $color1['b'];

		$r = ($color1['r'] + $diff_r * $color_weight);
		$g = ($color1['g'] + $diff_g * $color_weight);
		$b = ($color1['b'] + $diff_b * $color_weight);

		return self::color_rgb_to_hex($r, $g, $b);
	}
	public static function color_shade($color, $percent, $mask)
	{
		$color = self::color_hex_to_rgb($color);

		foreach($color as &$color_value)
		{
			$color_value = round($color_value * $percent) + round($mask * (1 - $percent));
			$color_value = $color_value > 255 ? 255 : $color_value;
		}

		return self::color_rgb_to_hex($color['r'], $color['g'], $color['b']);
	}
}

?>
