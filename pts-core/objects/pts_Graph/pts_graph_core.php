<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2023, Phoronix Media
	Copyright (C) 2008 - 2023, Michael Larabel
	pho_graph.php: The core graph object that is used by the different graphing objects.

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

// TODO: elimiante need for some of the pts_* classes called inside here, instead build them in or find other efficient ways of handling...

// Setup main config values, should only be needed once since the configuration values should never be over-written within pts_Graph*
pts_graph_core::init_graph_config();

abstract class pts_graph_core
{
	// Graph config
	protected static $c = array(); // user-configurable data. no pts_Graph* should ever over-write any of this data... should be read-only.
	protected static $color_cache = array();
	protected $d = array(); // the data from the test result / whatever... important data
	protected $i = array(); // internal data, pts_Graph* can read-write
	public $svg_dom = null;

	protected $graph_identifiers = array();
	protected $graph_sub_titles = array();
	protected $test_result;
	protected $results;

	public function __construct(&$result_object = null, &$result_file = null, $extra_attributes = null)
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
		$this->i['graph_min_value'] = 0;
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
		$this->i['multi_way_comparison_invert_default'] = true;
		$this->i['support_color_branding'] = true;
		$this->i['notes'] = array();
		$this->i['is_multi_way_comparison'] = false;
		$this->i['graph_y_title'] = null;
		$this->i['graph_title'] = null;
		$this->i['header_link'] = null;
		$this->i['highlight_values'] = false;
		$this->i['skip_headers'] = isset($extra_attributes['skip_headers']);
		$this->i['graph_version'] = false;
		$this->i['watermark'] = false;
		$this->i['force_graph_key'] = isset($extra_attributes['force_graph_key']);

		// Reset of setup besides config
		if($result_object != null)
		{
			$test_version = $result_object->test_profile->get_app_version();
			$this->i['graph_title'] = trim($result_object->test_profile->get_title() . ' ' . $test_version);

			$this->i['graph_y_title'] = $result_object->test_profile->get_result_scale_formatted();
			if(($test_identifier = $result_object->test_profile->get_identifier()))
			{
				$this->i['header_link'] = 'https://openbenchmarking.org/test/' . $result_object->test_profile->get_identifier(false);
			}
			$this->i['graph_proportion'] = $result_object->test_profile->get_result_proportion();
			$this->addSubTitle($result_object->get_arguments_description());
		}

		$this->update_graph_dimensions(isset($extra_attributes['pdf_generation']) ? 730 : self::$c['graph']['width'], self::$c['graph']['height'], true);
		if(!isset($extra_attributes['skip_multi_way_comparison_check']))
		{
			if(isset($extra_attributes['force_tracking_line_graph']))
			{
				// Phoromatic result tracker
				// TODO: investigate this check as it could cause problems... bad assumption to make
				$this->i['is_multi_way_comparison'] = true;
			}
			else if(isset($result_object->test_result_buffer))
			{
				$this->i['is_multi_way_comparison'] = pts_render::multi_way_identifier_check($result_object->test_result_buffer->get_identifiers());
			}
		}

		if(!isset($extra_attributes['pdf_generation']))
		{
			if(!isset($extra_attributes['skip_graph_version']))
			{
				$this->i['graph_version'] = 'Phoronix Test Suite';
			}
			$this->i['watermark'] = self::$c['text']['watermark'];
		}

		if(isset($extra_attributes['regression_marker_threshold']))
		{
			$this->d['regression_marker_threshold'] = $extra_attributes['regression_marker_threshold'];
		}
		if(isset($extra_attributes['set_alternate_view']))
		{
			$this->d['link_alternate_view'] = $extra_attributes['set_alternate_view'];
		}
		if(isset($extra_attributes['highlight_graph_values']))
		{
			$this->i['highlight_values'] = $extra_attributes['highlight_graph_values'];
		}
		else if(PTS_IS_CLIENT && pts_env::read('GRAPH_HIGHLIGHT') != false)
		{
			//$this->i['highlight_values'] = pts_strings::comma_explode(getenv('GRAPH_HIGHLIGHT'));
			// We support GRAPH_HIGHLIGHT as a series of
			// ID, ID=colorId or ID=color values, e.g.
			// GRAPH_HIGHLIGHT="will_be_different,group1a=1,group1b=1,blue=#0000ff"
			$this->i['highlight_values'] = array();
			foreach(pts_strings::comma_explode(getenv('GRAPH_HIGHLIGHT')) as $id)
			{
				$split = explode('=', $id);
				if(count($split) == 2)
				{
					$this->i['highlight_values'][$split[0]] = null;
					$color = $split[1];
					if(is_numeric($color)) // TODO clean this up with a better color check
					{
						$color = self::$c['color']['paint'][$color];
						$this->i['highlight_values'][$split[0]] = $color;
					}
				}
				else
				{
					$this->i['highlight_values'][$id] = null;
				}
			}
		}
		if(isset($extra_attributes['force_simple_keys']))
		{
			$this->override_i_value('force_simple_keys', true);
		}
		if(isset($extra_attributes['multi_way_comparison_invert_default']))
		{
			$this->i['multi_way_comparison_invert_default'] = $extra_attributes['multi_way_comparison_invert_default'];
		}
		if(isset($extra_attributes['no_color_branding']))
		{
			$this->i['support_color_branding'] = false;
		}

		$this->test_result = &$result_object;

		if(!isset($extra_attributes['no_compact_results_var']))
		{
			$this->generate_results_var();
		}
		$this->i['no_graph_value_ticks'] = isset($extra_attributes['no_graph_value_ticks']);

		// reverve bar paint color (first color) from being used otherwise such as in charts rendered first
		$this->get_paint_color(0);
	}
	public static function get_graph_config($o1 = null, $o2 = null)
	{
		if(isset(self::$c[$o1][$o2]))
		{
			return self::$c[$o1][$o2];
		}
		else
		{
			return self::$c;
		}
	}
	protected function generate_results_var()
	{
		$this->results = array();
		if($this->i['is_multi_way_comparison'])
		{
			$this->test_result->test_result_buffer->adjust_precision();
			foreach($this->test_result->test_result_buffer->buffer_items as $i => &$buffer_item)
			{
				if($buffer_item->get_result_value() == null)
				{
					unset($this->test_result->test_result_buffer->buffer_items[$i]);
					continue;
				}

				$identifier = array_map('trim', explode(':', $buffer_item->get_result_identifier()));

				if($this->i['multi_way_comparison_invert_default'])
				{
					$identifier = array_reverse($identifier);
				}

				switch(count($identifier))
				{
					case 2:
						$system = $identifier[0];
						$date = $identifier[1];
						break;
					case 1:
						$system = 0;
						$date = $identifier[0];
						break;
					default:
						continue 2;
				}
				/*if(in_array($buffer_item->get_result_identifier(), $this->graph_identifiers))
				{
					// Don't allow duplicate identifiers
					unset($this->test_result->test_result_buffer->buffer_items[$i]);
					continue;

				}*/

				$buffer_item->reset_result_identifier($system);

				if(!isset($this->results[$date]))
				{
					$this->results[$date] = array();
				}

				$this->results[$date][] = $buffer_item;
				pts_arrays::unique_push($this->graph_identifiers, $system);
			}

			if(count($this->results) == 1)
			{
				$this->i['is_multi_way_comparison'] = false;
			}
		}
		else if(isset($this->test_result->test_result_buffer))
		{
			$this->test_result->test_result_buffer->adjust_precision();
			foreach($this->test_result->test_result_buffer->buffer_items as $i => &$buffer_item)
			{
				if($buffer_item->get_result_value() == null)
				{
					unset($this->test_result->test_result_buffer->buffer_items[$i]);
					continue;
				}
				if(in_array($buffer_item->get_result_identifier(), $this->graph_identifiers))
				{
					// Don't allow duplicate identifiers
					unset($this->test_result->test_result_buffer->buffer_items[$i]);
					continue;

				}

				$this->graph_identifiers[] = $buffer_item->get_result_identifier();
			}
			$this->results = array($this->test_result->test_result_buffer->buffer_items);
		}
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

		self::$c['color']['seeded_paint'] = array();
		foreach(self::$c['color']['paint'] as $color)
		{
			self::$c['color']['seeded_paint'][] = $color;
		}
	}
	public static function set_default_graph_values(&$config)
	{
		// Setup config values
		$config['graph']['width'] = 600;
		$config['graph']['height'] = 310;
		$config['graph']['border'] = false;

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
		$config['color']['paint'] = array('#2196f3', '#f44336', '#673ab7', '#009688', '#4caf50', '#e91e63', '#795548', '#9e9e9e', '#607d8b', '#FFB300', '#803E75', '#FF6800', '#A6BDD7', '#C10020', '#CEA262', '#817066', '#007D34', '#01579b', '#F6768E', '#ff5722', '#00538A', '#FF7A5C', '#53377A', '#FF8E00', '#B32851', '#F4C800', '#7F180D', '#93AA00', '#593315', '#F13A13', '#232C16');

		// Text
		$config['size']['tick_mark'] = 10;
		$config['size']['key'] = 9;

		if(isset($_REQUEST['phoronix_export']))
		{
			$config['text']['watermark'] = 'Phoronix.com';
			$config['text']['watermark_url'] = 'https://www.phoronix.com/';
		}
		else if(defined('OPENBENCHMARKING_BUILD'))
		{
			$config['text']['watermark'] = 'OpenBenchmarking.org';
			if(defined('OPENBENCHMARKING_WATERMARK_URL'))
			{
				$config['text']['watermark_url'] = OPENBENCHMARKING_WATERMARK_URL;
			}
			else
			{
				$config['text']['watermark_url'] = 'https://www.openbenchmarking.org/';
			}
		}
		else
		{
			$config['text']['watermark'] = 'Phoronix Test Suite';
			$config['text']['watermark_url'] = '';
		}

		$config['size']['headers'] = 17;
		$config['size']['bars'] = 11;
		$config['size']['identifiers'] = 10;
		$config['size']['sub_headers'] = 11;
		$config['size']['axis_headers'] = 10;

		$config['style']['allow_box_plots'] = true;
		$config['style']['bar_graphs_horizontal'] = true;
	}

	//
	// Load Functions
	//

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
	public function addSubTitle($sub_title)
	{
		if(empty($sub_title))
		{
			return;
		}

		$sub_titles = array_map('trim', explode('|', $sub_title));

		foreach($sub_titles as $sub_title)
		{
			if(!empty($sub_title))
			{
				$this->graph_sub_titles[] = $sub_title;
			}
		}
	}
	public function addTestNote($note, $hover_title = null, $section = null)
	{
		$this->i['notes'][] = array('note' => $note, 'hover-title' => $hover_title, 'section' => $section);
	}

	//
	// Misc Functions
	//

	protected function get_paint_color($identifier, $check_branding = false)
	{
		// For now to try to improve the color handling of line graphs, first try to use a pre-defined pool of colors until falling back to the old color code once exhausted
		if(!isset(self::$color_cache[$check_branding][$identifier]))
		{
			if(!empty(self::$c['color']['seeded_paint']))
			{
				self::$color_cache[$check_branding][$identifier] = array_shift(self::$c['color']['seeded_paint']);
			}
			else
			{
				self::$color_cache[$check_branding][$identifier] = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
			}

			if($check_branding)
			{
				self::$color_cache[$check_branding][$identifier] = self::identifier_to_branded_color($identifier, self::$color_cache[$check_branding][$identifier]);
			}
		}

		return self::$color_cache[$check_branding][$identifier];
	}
	protected function maximum_graph_value($v = -1)
	{
		$real_maximum = 0;

		$data_max = $v == -1 ? $this->test_result->test_result_buffer->get_max_value() : $v;
		$max_precision = pts_math::get_precision($data_max);
		if(!is_numeric($data_max))
		{
			if(is_array($data_max))
			{
				$data_max = max($data_max);
			}
			if(is_array($data_max))
			{
				$data_max = max($data_max);
			}

			if(!is_numeric($data_max) && $data_max != null)
			{
				$data_max = str_repeat(9, strlen($data_max));
			}
		}
		if($data_max > $real_maximum)
		{
			$real_maximum = $data_max;
		}

		if(is_numeric($real_maximum))
		{
			if($real_maximum < $this->i['mark_count'])
			{
				$maximum = (($real_maximum * 1.35 / $this->i['mark_count']) * $this->i['mark_count']);

				if($maximum > 1)
				{
					pts_math::set_precision($maximum, $max_precision);
				}
			}
			else
			{
				$maximum = (floor(round($real_maximum * $this->i['graph_max_value_multiplier']) / $this->i['mark_count']) + 1) * $this->i['mark_count'];
				$maximum = round(ceil($maximum / $this->i['mark_count']), (0 - strlen($maximum) + 2)) * $this->i['mark_count'];
				$maximum = pts_math::set_precision($maximum, $max_precision);
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
	protected function update_graph_dimensions($width = -1, $height = -1, $recalculate_offsets = false)
	{
		// Allow render area to be increased, but not decreased
		$this->i['graph_width'] = intval(max($this->i['graph_width'], $width));
		$this->i['graph_height'] = intval(max($this->i['graph_height'], $height));

		if($recalculate_offsets)
		{
			$this->i['graph_top_end'] = $this->i['graph_height'] - $this->i['top_end_bottom'];
			$this->i['graph_left_end'] = $this->i['graph_width'] - $this->i['left_end_right'];
		}
	}
	protected function identifier_to_branded_color($identifier, $fallback_color = null)
	{
		if($this->i['support_color_branding'] == false || !isset($identifier[5]))
		{
			return $fallback_color;
		}

		$paint_color = pts_render::identifier_to_brand_color($identifier, $fallback_color);

		if($paint_color != $fallback_color && strpos($identifier, ' - '))
		{
			// If there is " - " in string, darken the color... based upon idea when doing AMDGPU vs. Mesa vs. stock NVIDIA comparison for RX 480
			$paint_color = self::shift_color($paint_color);
		}

		return $paint_color;
	}

	//
	// Render Functions
	//
	public static function shift_color($paint_color, $percent = 0.7, $mask = 0)
	{
		$new_color = null;
		foreach(str_split(str_replace('#', '', $paint_color), 2) as $color)
		{
			$dec = hexdec($color);
			$dec = min(max(0, round($dec * $percent) + round($mask * (1 - $percent))), 255);
			$new_color .= str_pad(dechex($dec), 2, 0, STR_PAD_LEFT);
		}
		return '#' . substr($new_color, 0, 6);
	}
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
		$longest_identifier = $this->test_result->test_result_buffer->get_longest_identifier();
		$se_width = self::text_string_width('SE +/- 0.04, N = 3', $this->i['identifier_size'] - 1);

		// Make room for tick markings, left hand side
		if($this->i['iveland_view'] == false)
		{
			if($this->i['graph_value_type'] == 'NUMERICAL')
			{
				$this->i['left_start'] += self::text_string_width($this->i['graph_max_value'], self::$c['size']['tick_mark']) + 2;
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
				if($this->i['is_multi_way_comparison'] && count($this->results) > 1)
				{
					$longest_r = $longest_identifier;
					$longest_r = $longest_r == null ? array() : explode(' - ', $longest_r);
					$plus_extra = 0;

					if(count($longest_r) > 1)
					{
						$plus_extra += floor(count($longest_r) * $this->i['identifier_size'] * 1.2);
					}

					$longest_identifier_width = self::text_string_width($this->i['graph_max_value'], $this->i['identifier_size']) + 64 + $plus_extra;
				}
				else
				{
					$longest_identifier_width = max($se_width, ceil(self::text_string_width($longest_identifier, $this->i['identifier_size']) * 1.09) + 24);
				}

				$longest_identifier_max = ceil(($this->i['graph_width'] * 0.55) + 0.01);

				$this->i['left_start'] = min($longest_identifier_max, max($longest_identifier_width, 70));
				$this->i['left_end_right'] = 15;
				$this->i['graph_left_end'] = $this->i['graph_width'] - $this->i['left_end_right'];
			}
			else if($this->i['graph_value_type'] == 'NUMERICAL')
			{
				$this->i['left_start'] += max(20, self::text_string_width($this->i['graph_max_value'], self::$c['size']['tick_mark']) + 3);
			}
			$this->i['left_start'] += 10;

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

			if($this->i['skip_headers'])
			{
				$this->i['top_heading_height'] = 0;
				$this->i['top_start'] = 5;
			}

			$bottom_heading = $this->i['skip_headers'] ? 0 : 14;

			if($this->i['graph_orientation'] == 'HORIZONTAL')
			{
				if($this->i['is_multi_way_comparison'] && count($this->results) > 1)
				{
					$longest_string = $longest_identifier != null ? explode(' - ', $longest_identifier) : array();
					$longest_string = pts_strings::find_longest_string($longest_string);
					$per_identifier_height = 22; // default
					if($this->test_result->test_result_buffer->get_count() > 9)
					{
						$per_identifier_height = 20; // default
						$this->i['identifier_size'] = floor($this->i['identifier_size'] * 0.88);
					}
					$rotated_text = round(self::text_string_width($longest_string, $this->i['identifier_size']) * 1.02);

					if(ceil($rotated_text * 1.2) >= floor($per_identifier_height * count($this->results)))
					{
						// this is to avoid having a rotated text bar overrun other results
						$per_identifier_height = max(($per_identifier_height + 2), ceil($rotated_text / count($this->results)));
					}
				}
				else if(count($this->results) > 3)
				{
					$per_identifier_height = count($this->results) * 18;
				}
				else
				{
					// If there's too much to plot, reduce the size so each graph doesn't take too much room
					$id_count = count(pts_arrays::first_element($this->results));
					if($id_count < 10)
					{
						$per_identifier_height = 46;
					}
					else if($id_count < 20)
					{
						$per_identifier_height = 36;
					}
					else
					{
						$this->i['compact_result_view'] = true;
						$per_identifier_height = 28;
					}
				}

				$num_identifiers = $this->test_result->test_result_buffer->get_count() + ($this->i['is_multi_way_comparison'] ? 2 : 0);
				$this->i['graph_top_end'] = $this->i['top_start'] + ($num_identifiers * $per_identifier_height);
				// $this->i['top_end_bottom']
				$this->i['graph_height'] = $this->i['graph_top_end'] + ($this->i['skip_headers'] ? 0 : 25) + $bottom_heading;
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
		/*
		if(self::$c['graph']['border'])
		{
			$this->svg_dom->add_element('rect', array('x' => 0, 'y' => 0, 'width' => $this->i['graph_width'], 'height' => $this->i['graph_height'], 'fill' => self::$c['color']['background'], 'stroke' => self::$c['color']['border'], 'stroke-width' => 2));
		}
		else
		{
			$this->svg_dom->add_element('rect', array('x' => 0, 'y' => 0, 'width' => $this->i['graph_width'], 'height' => $this->i['graph_height'], 'fill' => self::$c['color']['background']));
		}
		*/

		if($this->i['iveland_view'] == false && ($sub_title_count = count($this->graph_sub_titles)) > 1)
		{
			$this->i['top_start'] += (($sub_title_count - 1) * (self::$c['size']['sub_headers'] + 4));
		}
	}
	protected function render_graph_heading($with_version = true)
	{
		if($this->i['skip_headers'])
		{
			return;
		}

		// Default to NORMAL
		if($this->i['iveland_view'])
		{
			//$this->svg_dom->add_element('rect', array('x' => 0, 'y' => 0, 'width' => $this->i['graph_width'], 'height' => $this->i['top_heading_height'], 'fill' => self::$c['color']['main_headers']));

			if(isset($this->i['graph_title'][36]))
			{
				// If it's a long string make sure it won't run over the side...
				while(self::text_string_width($this->i['graph_title'], self::$c['size']['headers']) > ($this->i['graph_left_end'] - 20))
				{
					self::$c['size']['headers'] -= 0.5;
				}
			}

			$this->svg_dom->add_text_element($this->i['graph_title'], array('x' => 6, 'y' => (self::$c['size']['headers'] + 2), 'font-size' => self::$c['size']['headers'], 'fill' => self::$c['color']['main_headers'], 'text-anchor' => 'start', 'xlink:href' => $this->i['header_link'], 'font-weight' => 'bold'));

			foreach($this->graph_sub_titles as $i => $sub_title)
			{
				$vertical_offset = 12 + self::$c['size']['headers'] + (($i + 1) * (self::$c['size']['sub_headers'] - 4));
				$sub_title_size = self::$c['size']['sub_headers'];
				if(isset($sub_title[69]))
				{
					while(self::text_string_width($sub_title, $sub_title_size) > ($this->i['graph_left_end'] - 20))
						$sub_title_size -= 0.5;
				}
				$this->svg_dom->add_text_element($sub_title, array('x' => 6, 'y' => $vertical_offset, 'font-size' => $sub_title_size, 'font-weight' => 'bold', 'fill' => self::$c['color']['main_headers'], 'text-anchor' => 'start'));
			}

			// SVG version of PTS thanks to https://gist.github.com/xorgy/65c6d0e87757dbb56a75
			if($this->i['graph_version'])
			{
				$this->svg_dom->add_element('path', array('d' => 'm74 22v9m-5-16v16m-5-28v28m-23-2h12.5c2.485281 0 4.5-2.014719 4.5-4.5s-2.014719-4.5-4.5-4.5h-8c-2.485281 0-4.5-2.014719-4.5-4.5s2.014719-4.5 4.5-4.5h12.5m-21 5h-11m11 13h-2c-4.970563 0-9-4.029437-9-9v-20m-24 40v-20c0-4.970563 4.0294373-9 9-9 4.970563 0 9 4.029437 9 9s-4.029437 9-9 9h-9', 'stroke' => self::$c['color']['main_headers'], 'stroke-width' => 4, 'fill' => 'none', 'transform' => 'translate(' . ceil($this->i['graph_left_end'] - 77) . ',' . (ceil($this->i['top_heading_height'] / 40 + 2)) . ')'));
			}
		}
		else
		{
			$this->svg_dom->add_text_element($this->i['graph_title'], array('x' => round($this->i['graph_left_end'] / 2), 'y' => (self::$c['size']['headers'] + 2), 'font-size' => self::$c['size']['headers'], 'fill' => self::$c['color']['main_headers'], 'text-anchor' => 'middle', 'xlink:href' => $this->i['header_link']));

			foreach($this->graph_sub_titles as $i => $sub_title)
			{
				$this->svg_dom->add_text_element($sub_title, array('x' => round($this->i['graph_left_end'] / 2), 'y' => (31 + (($i + 1) * 18)), 'font-size' => self::$c['size']['sub_headers'], 'fill' => self::$c['color']['main_headers'], 'text-anchor' => 'middle'));
			}

			if($with_version && !empty($this->i['graph_version']))
			{
				$this->svg_dom->add_text_element($this->i['graph_version'], array('x' => $this->i['graph_left_end'] , 'y' => ($this->i['top_start'] - 3), 'font-size' => 7, 'fill' => self::$c['color']['body_light'], 'text-anchor' => 'end'));
			}
		}
	}
	protected function render_graph_post()
	{
		if($this->i['skip_headers'])
		{
			return;
		}

		if($this->i['iveland_view'])
		{
			$bottom_heading_start = $this->i['graph_top_end'] + $this->i['bottom_offset'] + 22;

			if(!empty($this->i['notes']))
			{
				$estimated_height = 0;
				foreach($this->i['notes'] as $i => $note_r)
				{
					$this->svg_dom->add_textarea_element(($i + 1) . '. ' . $note_r['note'], array('x' => 5, 'y' => ($bottom_heading_start + (self::$c['size']['key'] * 2) + $estimated_height), 'font-size' => (self::$c['size']['key'] - 1), 'fill' => self::$c['color']['main_headers'], 'text-anchor' => 'start'), $estimated_height);
				}
			}
		}
	}
	protected function render_graph_base($left_start, $top_start, $left_end, $top_end)
	{
		if($this->i['graph_orientation'] == 'HORIZONTAL' || $this->i['iveland_view'])
		{
			$g = $this->svg_dom->make_g(array('stroke' => self::$c['color']['notches'], 'stroke-width' => 1));
			$this->svg_dom->add_element('line', array('x1' => $left_start, 'y1' => $top_start, 'x2' => $left_start, 'y2' => ($top_end + 1)), $g);
			$this->svg_dom->add_element('line', array('x1' => $left_start, 'y1' => $top_end, 'x2' => ($left_end + 1), 'y2' => $top_end), $g);

			if($this->i['watermark'] && isset($this->d['link_alternate_view']) && $this->d['link_alternate_view'])
			{
				$a = $this->svg_dom->make_a($this->d['link_alternate_view']);
				$g = $this->svg_dom->make_g(array('transform' => 'translate(' . ($left_end - 10) . ',' . ($top_start - 5 - 10) . ')', 'width' => 10, 'height' => 16), $a);
				$this->svg_dom->add_element('path', array('d' => 'M5 0v6.5L0 11l3-3-3-3.5L5 0', 'fill' => '#038bb8'), $g);
				$this->svg_dom->add_element('path', array('d' => 'M5 0v6.5l5 4.5-3-3 3-3.5L5 0', 'fill' => '#25b3e8'), $g);
				$this->svg_dom->add_element('path', array('d' => 'M5 16V9l5-4.5V11l-5 5', 'fill' => '#e4f4fd'), $g);
				$this->svg_dom->add_element('path', array('d' => 'M5 16V9L0 4.5V11l5 5', 'fill' => '#65cbf4'), $g);

				$this->svg_dom->add_text_element($this->i['watermark'], array('x' => $left_end - 12, 'y' => ($top_start - 5), 'font-size' => 8, 'fill' => self::$c['color']['text'], 'text-anchor' => 'end', 'font-weight' => 'bold', 'xlink:href' => self::$c['text']['watermark_url']));
			}
			else if(!empty($this->i['watermark']))
			{
				$this->svg_dom->add_text_element($this->i['watermark'], array('x' => $left_end, 'y' => ($top_start - 5), 'font-size' => 8, 'fill' => self::$c['color']['text'], 'text-anchor' => 'end', 'font-weight' => 'bold', 'xlink:href' => self::$c['text']['watermark_url']));
			}
		}
		else
		{
			$this->svg_dom->add_element('rect', array('x' => $left_start, 'y' => $top_start, 'width' => ($left_end - $left_start), 'height' => ($top_end - $top_start), 'fill' => self::$c['color']['body'], 'stroke' => self::$c['color']['notches'], 'stroke-width' => 1));

			if($this->i['watermark'] != null)
			{
				$this->svg_dom->add_text_element($this->i['watermark'], array('x' => ($left_end - 2), 'y' => ($top_start + 12), 'font-size' => 10, 'fill' => self::$c['color']['text'], 'text-anchor' => 'end', 'font-weight' => 'bold', 'xlink:href' => self::$c['text']['watermark_url']));
			}
		}

		if(!empty($this->i['graph_y_title']) && $this->i['hide_y_title'] == false)
		{
			$str = $this->i['graph_y_title'];
			$offset = 0;

			if($this->i['graph_proportion'] != null)
			{
				$proportion = null;

				switch($this->i['graph_proportion'])
				{
					case 'LIB':
						$proportion = 'Fewer Is Better';
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

			$this->svg_dom->add_text_element($str, array('x' => ($left_start + $offset), 'y' => ($top_start - 5), 'font-size' => 8, 'font-weight' => 'bold', 'fill' => self::$c['color']['text'], 'text-anchor' => 'start'));
		}
	}
	protected function render_graph_value_ticks($left_start, $top_start, $left_end, $top_end, $show_numbers = true)
	{
		$increment = round(($this->i['graph_max_value'] - $this->i['graph_min_value']) / $this->i['mark_count'], $this->i['graph_max_value'] < 10 ? 4 : 2);

		$dv_divide = 1;
		$dv_postfix = null;
		if($increment > 1000000 && ($increment % 1000) == 0)
		{
			$dv_divide = 1000000;
			$dv_postfix = 'M';
		}
		else if($increment > 1000 && ($increment % 1000) == 0)
		{
			$dv_divide = 1000;
			$dv_postfix = 'K';
		}

		if($this->i['graph_orientation'] == 'HORIZONTAL')
		{
			$tick_width = round(($left_end - $left_start) / $this->i['mark_count']);
			$display_value = $this->i['graph_min_value'];

			$tick_font_size = self::$c['size']['tick_mark'];
			while($this->i['graph_max_value'] > 100000 && self::text_string_width($this->i['graph_max_value'], $tick_font_size) + 4 > $tick_width)
			{
				$tick_font_size--;
			}

			$g = $this->svg_dom->make_g(array('font-size' => $tick_font_size, 'fill' => self::$c['color']['text'], 'text-anchor' => 'middle'));
			$g_lines = $this->svg_dom->make_g(array('stroke' => self::$c['color']['body'], 'stroke-width' => 1));
			for($i = 0; $i < $this->i['mark_count']; $i++)
			{
				$px_from_left = $left_start + ($tick_width * $i);

				if($i != 0 && $display_value != 0)
				{
					if(!$this->i['no_graph_value_ticks'] && $show_numbers)
					{
						$dv = $display_value;
						if($dv_divide > 1)
						{
							$dv = round($dv / $dv_divide, 2) . $dv_postfix;
						}
						$this->svg_dom->add_text_element($dv, array('x' => $px_from_left + 2, 'y' => ($top_end + 5 + self::$c['size']['tick_mark'])), $g);
					}
					$this->svg_dom->add_element('line', array('x1' => ($px_from_left + 2), 'y1' => ($top_start), 'x2' => ($px_from_left + 2), 'y2' => ($top_end - 5), 'stroke-dasharray' => '5,5'), $g_lines);
					$this->svg_dom->add_element('line', array('x1' => ($px_from_left + 2), 'y1' => ($top_end - 4), 'x2' => ($px_from_left + 2), 'y2' => ($top_end + 5)), $g_lines);
				}

				$display_value += $increment;
			}

		}
		else
		{
			$tick_width = round(($top_end - $top_start) / $this->i['mark_count']);
			$px_from_left_start = $left_start - 5;
			$px_from_left_end = $left_start + 5;

			$display_value = $this->i['graph_min_value'];

			$g_lines = $this->svg_dom->make_g(array('stroke' => self::$c['color']['notches'], 'stroke-width' => 1, 'stroke-dasharray' => '5,5'));
			$g_lines_2 = $this->svg_dom->make_g(array('stroke' => self::$c['color']['notches'], 'stroke-width' => 1));
			$g_background_lines = $this->svg_dom->make_g(array('stroke' =>  self::$c['color']['body_light'], 'stroke-width' => 1, 'stroke-dasharray' => '5,5'));
			$g_text = $this->svg_dom->make_g(array('font-size' => self::$c['size']['tick_mark'], 'fill' => self::$c['color']['text'], 'text-anchor' => 'end'));
			for($i = 0; $i < $this->i['mark_count']; $i++)
			{
				$px_from_top = round($top_end - ($tick_width * $i));

				if($display_value != 0)
				{
					if(!$this->i['no_graph_value_ticks'] && $show_numbers)
					{
						$dv = $display_value;
						if($dv_divide > 1)
						{
							$dv = round($dv / $dv_divide, 2) . $dv_postfix;
						}
						$this->svg_dom->add_text_element($dv, array('x' => ($px_from_left_start - 4), 'y' => round($px_from_top + (self::$c['size']['tick_mark'] / 2))), $g_text);
					}

					if($i != 0 && $this->i['show_background_lines'])
					{
					//	$this->svg_dom->add_element('line', array('x1' => ($px_from_left_end + 6), 'y1' => ($px_from_top + 1), 'x2' => ($this->i['graph_left_end']), 'y2' => ($px_from_top + 1)), $g_background_lines);
					}
					if($i != 0)
					{
						$this->svg_dom->add_element('line', array('x1' => ($left_start), 'y1' => ($px_from_top + 1), 'x2' => ($left_end), 'y2' => ($px_from_top + 1)), $g_lines);
						$this->svg_dom->add_element('line', array('x1' => ($left_start - 4), 'y1' => ($px_from_top + 1), 'x2' => ($left_start + 4), 'y2' => ($px_from_top + 1)), $g_lines_2);
					}
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
		if((count($this->results) < 2 || $this->i['show_graph_key'] == false) && !$this->i['is_multi_way_comparison'] && !$this->i['force_graph_key'])
		{
			return 0;
		}

		$this->i['key_line_height'] = 16;
		$ak = array_keys($this->results);
		$this->i['key_item_width'] = 2 + ceil(self::text_string_width(pts_strings::find_longest_string($ak), self::$c['size']['key']) * 1.3);
		$this->i['keys_per_line'] = max(1, floor(($this->i['graph_left_end'] - $this->i['left_start']) / $this->i['key_item_width']));

		return ceil(count($this->results) / $this->i['keys_per_line']) * $this->i['key_line_height'];
	}
	protected function render_graph_key()
	{
		if($this->i['key_line_height'] == 0)
		{
			return;
		}

		$y = $this->i['top_start'] - $this->graph_key_height() - 7;

		$i = 0;
		$g_rect = $this->svg_dom->make_g(array('stroke' => self::$c['color']['notches'], 'stroke-width' => 1));
		$g_text = $this->svg_dom->make_g(array('font-size' => self::$c['size']['key'], 'text-anchor' => 'start', 'font-weight' => 'bold'));

		if(!is_array($this->results))
		{
			return false;
		}

		foreach(array_keys($this->results) as $title)
		{
			if(!empty($title))
			{
				$this_color = $this->get_paint_color($title);

				if($i != 0 && $i % $this->i['keys_per_line'] == 0)
				{
					$y += $this->i['key_line_height'];
				}

				$x = $this->i['left_start'] + 13 + ($this->i['key_item_width'] * ($i % $this->i['keys_per_line']));

				$this->svg_dom->add_element('rect', array('x' => ($x - 13), 'y' => ($y - 5), 'width' => 10, 'height' => 10, 'fill' => $this_color), $g_rect);
				$this->svg_dom->add_text_element($title, array('x' => $x, 'y' => ($y + 4), 'fill' => $this_color), $g_text);
				$i++;
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
			$arrow_length_half = round($arrow_length / 2);

			$arrow_points = array(
				$tip_x1 . ',' . $tip_y1,
				$tail_x1 . ',' . ($tail_y1 + $arrow_length_half),
				$tail_x1 . ',' . ($tail_y1 - $arrow_length_half)
				);
		}

		$this->svg_dom->add_element('polygon', array('points' => implode(' ', $arrow_points), 'fill' => $background_color, 'stroke' => $border_color, 'stroke-width' => $border_width));
	}
	protected function adjust_color($identifier, $paint_color)
	{
		// Adjust the color based on the identifier and the content of value_highlights
		if($this->i['highlight_values'] && (array_key_exists($identifier, $this->i['highlight_values']) || in_array($identifier, $this->i['highlight_values'])))
		{
			$color = isset($this->i['highlight_values'][$identifier]) ? $this->i['highlight_values'][$identifier] : null;
			$paint_color = empty($color) ? self::shift_color($paint_color) : $color;
		}
		return $paint_color;
	}
	protected static function text_string_width($string, $size)
	{
		$dimensions = pts_svg_dom::estimate_text_dimensions($string, $size);
		return $dimensions[0];
	}
	protected static function text_string_height($string, $size)
	{
		$dimensions = pts_svg_dom::estimate_text_dimensions($string, $size);
		return $dimensions[1];
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
				$note_height += !isset($note['note'][36]) ? (self::$c['size']['key'] + 2) : (ceil(self::text_string_width($note['note'], self::$c['size']['key']) / ($this->i['graph_width'] - 14))) * self::$c['size']['key'];

				if($note['section'] != null)
				{
					$sections[] = $note['section'];
				}
			}
			$note_height += self::$c['size']['key'];
		}
		$note_height += ((count(array_unique($sections)) + 1) * (self::$c['size']['key'] + 6));

		return $note_height;
	}
}

?>
