<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2011 - 2015, Phoronix Media
	Copyright (C) 2011 - 2015, Michael Larabel

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

class pts_HeatMapBarGraph extends pts_Graph
{
	protected $bars;
	protected $last_updated;
	protected $keys;

	public function __construct($keys = array(), $last_updated = null)
	{
		$this->bars = array();
		$this->keys = $keys;
		$this->last_updated = $last_updated;
	}
	public function set_keys($keys = array())
	{
		$this->keys = $keys;
	}
	public function add_result_bar($min_value, $max_value, $bin_size, $sections, $lines, $test_data, $results = array())
	{
		if($min_value == $max_value)
		{
			return false;
		}

		array_push($this->bars, array(
			'min_value' => $min_value,
			'max_value' => $max_value,
			'bin_size' => $bin_size,
			'sections' => $sections,
			'draw_lines' => $lines,
			'test_data' => $test_data,
			'results' => $results
			));
	}
	public function get_count()
	{
		return count($this->bars);
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
	protected static function compare_bars_by_hardware_subsystem($a, $b)
	{
		return $a['test_data']['h'] == $b['test_data']['h'] ? strcmp($a['test_data']['t'], $b['test_data']['t']) : strcmp($a['test_data']['h'], $b['test_data']['h']);
	}
	public function sort_results_by_hardware_subsystem()
	{
		usort($this->bars, array('self', 'compare_bars_by_hardware_subsystem'));
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
	public function generate_display()
	{
		$bar_width = 580;
		$bar_height = 38;
		$heading_per_bar = 16;
		$title_bar_height = 35;
		$footer_bar_height = 14;
		$category_offsets = 0;
		$category_heights = 30;
		$categories = array();
		$border = 3;

		foreach($this->bars as &$bar)
		{
			if($bar['test_data']['h'] != null && !in_array($bar['test_data']['h'], $categories))
			{
				array_push($categories, $bar['test_data']['h']);
			}
		}

		if(empty($this->keys))
		{
			foreach($this->bars as &$bar_index)
			{
				foreach(array_keys($bar_index['results']) as $result_identifier)
				{
					if(!in_array($result_identifier, $this->keys))
					{
						array_push($this->keys, $result_identifier);
					}
				}
			}
		}

		if(!empty($this->keys))
		{
			list($longest_key_width, $key_line_height) = pts_svg_dom::estimate_text_dimensions(pts_strings::find_longest_string($this->keys), '', 10, true);
			$key_line_height += 18;
			$keys_per_line = max(floor($bar_width / max(1, $longest_key_width + 12)), 1);
			$title_key_offset = ceil(count($this->keys) / $keys_per_line) * $key_line_height;
		}
		else
		{
			$title_key_offset = 0;
		}

		$this->i['graph_width'] = $bar_width + ($border * 2);
		$this->i['graph_height'] = ($bar_height + $heading_per_bar + $border) * count($this->bars) + $border + (count($categories) * $category_heights) + $title_bar_height + $title_key_offset + $footer_bar_height;
		$this->svg_dom = new pts_svg_dom(ceil($this->i['graph_width']), ceil($this->i['graph_height']));

		$text_color = '#e12128';
		$alt_text_color = '#646464';

		// Setup
		$start_x = $border;
		$end_x = $start_x + $bar_width;

		// Title bar
		$this->svg_dom->add_element('image', array('xlink:href' => 'https://openbenchmarking.org/static/images/ob-fulltext-183x32.png', 'x' => ($end_x - 190), 'y' => 10, 'width' => 183, 'height' => 32));


		if(!empty($this->keys))
		{
			$color_cache = array('e12128', '065695', '007400');

			for($i = 0, $c = count($this->keys); $i < $c; $i++)
			{
				$component_x = $border + ($i % $keys_per_line) * ($longest_key_width + 10);
				$component_y = (floor($i / $keys_per_line) * $key_line_height) + $title_bar_height + 3;
				$key_color = self::color_cache('opc', $this->keys[$i], $color_cache);

				//$key_color = self::color_gradient('e12128', '065695', ($i / $c));
				$key_colors[$this->keys[$i]] = $key_color;

				$this->svg_dom->add_element('rect', array('x' => ($component_x + 1), 'y' => $component_y, 'width' => 10, 'height' => 10, 'fill' => $key_color, 'stroke' => self::$c['color']['border'], 'stroke-width' => 1));
				$this->svg_dom->add_text_element($this->keys[$i], array('x' => ($component_x + 15), 'y' => ($component_y + 5), 'font-size' => 10, 'fill' => $key_color, 'text-anchor' => 'start'));
			}
		}

		$previous_category = null;

		foreach($this->bars as $i => &$hmap)
		{
			$upper_y = ($i * ($bar_height + $border + $heading_per_bar)) + $border + $title_bar_height + $title_key_offset + $category_offsets + $heading_per_bar;

			if($hmap['test_data']['h'] != null && $hmap['test_data']['h'] != $previous_category)
			{
				$this->svg_dom->add_text_element($hmap['test_data']['h'] . ' Tests', array('x' => ($start_x + ($bar_width / 2)), 'y' => $upper_y, 'font-size' => 16, 'fill' => $text_color, 'text-anchor' => 'middle'));
				$category_offsets += $category_heights;
				$upper_y += $category_heights;
			}
			$previous_category = $hmap['test_data']['h'];

			$lower_y = $upper_y + $bar_height;

			$value_size = $bar_width / ($hmap['max_value'] - $hmap['min_value']);
			$prev_color = '#ffffff';
			$last_plot_x = $start_x;

			$this->svg_dom->add_text_element($hmap['test_data']['t'], array('x' => $start_x, 'y' => $upper_y, 'font-size' => 12, 'fill' => $text_color, 'text-anchor' => 'start'));
			$this->svg_dom->add_text_element($hmap['test_data']['a'], array('x' => $end_x, 'y' => $upper_y, 'font-size' => 10, 'fill' => $alt_text_color, 'text-anchor' => 'end'));

			if($hmap['test_data']['p'] == 'LIB')
			{
				// Invert results
				$new_sections = array();

				foreach($hmap['sections'] as $next_section => $next_section_value)
				{
					$new_sections[($hmap['max_value'] - $next_section)] = $next_section_value;
				}

				ksort($new_sections);
				$hmap['sections'] = $new_sections;

				foreach($hmap['draw_lines'] as &$value)
				{
					$value = $hmap['max_value'] - $value;
				}

				foreach($hmap['results'] as &$value)
				{
					$value = $hmap['max_value'] - $value;
				}

				sort($hmap['draw_lines']);

				$hmap['max_value'] -= $hmap['min_value'];
				$hmap['min_value'] = 0;
			}

			$prev_section = $hmap['min_value'];
			$max_section_value = max($hmap['sections']);

			/*
			for($i = $hmap['min_value']; $i <= $hmap['max_size'] && $hmap['bin_size'] > 0; $i += $hmap['bin_size'])
			{

			}
			*/

			$color_weight = 0.61 - (0 / $max_section_value * 0.5);
			$background_color = self::color_gradient('#FFFFFF', '#000000', $color_weight);
			$this->svg_dom->add_element('rect', array('x' => $start_x, 'y' => $upper_y, 'width' => $bar_width, 'height' => $bar_height, 'fill' => $background_color));

			foreach($hmap['sections'] as $next_section => $next_section_value)
			{
				$color_weight = 0.61 - ($next_section_value / $max_section_value * 0.5);
				$color = self::color_gradient('#FFFFFF', '#000000', $color_weight);

				if($next_section > $hmap['min_value'])
				{
					$next_section = $next_section > $hmap['max_value'] ? $hmap['max_value'] : $next_section;
					$plot_x = floor($last_plot_x + (($next_section - $prev_section) * $value_size));
					$plot_x = $plot_x > $end_x ? $end_x : $plot_x;

					if($prev_color != $color || ($color != $background_color))
					{
						// don't uselessly paint background color, it's already painted
						$this->svg_dom->draw_rectangle_gradient($last_plot_x, $upper_y, abs($plot_x - $last_plot_x), $bar_height, $prev_color, $color);
					}

					$last_plot_x = floor($plot_x - 0.6);
					$prev_section = $next_section;

					if($next_section > $hmap['max_value'])
					{
						break;
					}
				}
				$prev_color = $color;
			}

			/*

			if($prev_color != $background_color && $plot_x < $end_x)
			{
				$plot_x = $last_plot_x + $next_section + $hmap['bin_size'];
				$plot_x = $plot_x > $end_x ? $end_x : $plot_x;
				$this->svg_dom->draw_rectangle_gradient($last_plot_x, $upper_y, ceil($plot_x - $last_plot_x), $bar_height, $prev_color, $background_color);
			}
			if($last_plot_x < $end_x)
			{
				// Fill in the blank
				$this->svg_dom->add_element('rect', array('x' => $last_plot_x, 'y' => $upper_y, 'width' => ($end_x - $last_plot_x), 'height' => $bar_height, 'fill' => $prev_color));
			}
			*/

			foreach($hmap['draw_lines'] as $line_value)
			{
				$line_x = $start_x + ($line_value - $hmap['min_value']) * $value_size;
				$this->svg_dom->draw_svg_line($line_x, $upper_y, $line_x, $lower_y, self::$c['color']['border'], 1);
			}

			foreach($hmap['results'] as $identifier => $value)
			{
				if(!isset($key_colors[$identifier]))
				{
					continue;
				}

				$line_x = $start_x + ($value - $hmap['min_value']) * $value_size;

				if(false && ($start_x + 10) < $line_x && $line_x < ($end_x - 10))
				{
					$this->svg_dom->draw_svg_line($line_x, ($lower_y - 10), $line_x, ($lower_y - 1), $key_colors[$identifier], 1);
					$this->svg_dom->draw_svg_line($line_x, ($lower_y + 10), $line_x, ($lower_y + 1), $key_colors[$identifier], 1);
				}

				$this->svg_dom->draw_svg_line($line_x, $upper_y, $line_x, $lower_y, $key_colors[$identifier], 1);
			}

			$this->svg_dom->add_element('rect', array('x' => $start_x, 'y' => $upper_y, 'width' => $bar_width, 'height' => $bar_height, 'fill' => 'none', 'stroke' => self::$c['color']['border'], 'stroke-width' => 1));
		}

		// Footer
		$this->draw_arrow($start_x + 8, $lower_y + 8, $start_x + 1, $lower_y + 8, $alt_text_color, self::$c['color']['border'], 1);
		$this->svg_dom->add_text_element('Percentile Rank' . ($this->last_updated != null ? '; Data As Of ' . pts_strings::time_stamp_to_string($this->last_updated, 'j F Y') . ' For Trailing 200 Days' : null), array('x' => $start_x + 13, 'y' => $lower_y + 8, 'font-size' => 7, 'fill' => $alt_text_color, 'text-anchor' => 'start', 'dominant-baseline' => 'middle'));
		$this->svg_dom->add_text_element('OpenBenchmarking.org Performance Classification', array('x' => $end_x, 'y' => $lower_y + 8, 'font-size' => 7, 'fill' => $alt_text_color, 'text-anchor' => 'end', 'dominant-baseline' => 'middle'));

		return $this->svg_dom;
	}
}

?>
