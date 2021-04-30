<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2021, Phoronix Media
	Copyright (C) 2010 - 2021, Michael Larabel

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

class pts_graph_radar_chart extends pts_graph_core
{
	private $result_objects = array();
	private $systems = array();
	private $logarithmic_view = false;
	private $selection_view = null;

	public static function cmp_result_object_sort($a, $b)
	{
		$a = $a->test_profile->get_test_hardware_type() . $a->test_profile->get_result_scale_formatted() . $a->test_profile->get_test_software_type() . $a->test_profile->get_identifier(true) . $a->get_arguments_description();
		$b = $b->test_profile->get_test_hardware_type() . $b->test_profile->get_result_scale_formatted() . $b->test_profile->get_test_software_type() . $b->test_profile->get_identifier(true) . $b->get_arguments_description();

		return strcmp($a, $b);
	}
	public function __construct($result_file, $selector = null)
	{
		$rf = clone $result_file;
		$this->selection_view = $selector;
		$this->systems = $rf->get_system_identifiers();
		$system_count = count($this->systems);

		if($system_count < 2)
		{
			return false;
		}

		$result_object = null;
		parent::__construct($result_object, $rf);

		// System Identifiers
		$result_objects = $rf->get_result_objects();
		$ignore_qualify_check = false;

		if(count($result_objects) > 12)
		{
			$ros = pts_result_file_analyzer::generate_geometric_mean_result_per_test($result_file, false, $this->selection_view);

			if(count($ros) > 2)
			{
				$ignore_qualify_check = true;
				$result_objects = $ros;
			}
		}

		usort($result_objects, array('pts_graph_run_vs_run', 'cmp_result_object_sort'));
		$longest_header = 0;
		$all_max = array();
		foreach($result_objects as &$r)
		{
			if($this->selection_view == null && $r->test_profile->get_identifier() == null && !$ignore_qualify_check)
			{
				continue;
			}
			if($this->selection_view != null && strpos($r->get_arguments_description(), $this->selection_view) === false && strpos($r->test_profile->get_title(), $this->selection_view) === false && strpos($r->test_profile->get_result_scale(), $this->selection_view) === false)
			{
				continue;
			}
			if(count($r->test_result_buffer->get_buffer_items()) != $system_count)
			{
				continue;
			}
			if($r->normalize_buffer_values() == false)
			{
				continue;
			}

			$relative_win = $r->get_result_first(false);
			if($relative_win < 1.03 && count($result_objects) > 88)
			{
				continue;
			}
			$this->i['graph_max_value'] = max($this->i['graph_max_value'], $relative_win);
			$rel = array();
			$max = 0;

			foreach($r->test_result_buffer->get_buffer_items() as $buffer_item)
			{
				if(in_array($buffer_item->get_result_identifier(), $this->systems))
				{
					$val_check = $buffer_item->get_result_value();
					if(empty($val_check) || !is_numeric($val_check))
						continue;
					$rel[$buffer_item->get_result_identifier()] = $val_check;
					$max = max($max, $buffer_item->get_result_value());
				}
			}

			if($max == 0)
			{
				continue;
			}

			if(count($rel) != $system_count)
			{
				continue;
			}

			$all_max[] = $max;
			$this->result_objects[] = array('rel' => $rel, 'ro' => $r, 'max' => $max);
			$longest_header = max($longest_header, strlen($r->test_profile->get_title()), strlen($r->get_arguments_description_shortened()));
		}

		if(count($this->result_objects) < 3)
		{
			// No point in generating this if there aren't many valid tests
			return false;
		}

		if($this->i['graph_max_value'] > 4 && pts_math::arithmetic_mean($all_max) < ($this->i['graph_max_value'] * 0.48))
		{
			// better to show a logarithmic view
			$this->i['graph_max_value'] = 0;
			$this->logarithmic_view = true;
			foreach($this->result_objects as &$r)
			{
				$max = 0;
				foreach($r['rel'] as $identifier => $value)
				{
					$r['rel'][$identifier] = log10($value * 10);
					$max = max($max, $r['rel'][$identifier]);

				}
				$r['max'] = $max;
				$this->i['graph_max_value'] = max($this->i['graph_max_value'], $max);
			}
		}

		foreach($this->systems as $system)
		{
			//$this->get_paint_color($system, true);
			$this->results[$system]  = $system;
		}

		$this->i['identifier_size'] = 6.5;
		$this->i['top_heading_height'] = max(self::$c['size']['headers'] + 22 + self::$c['size']['key'], 48);
		$this->i['top_start'] = $this->i['top_heading_height'];
		$this->i['left_start'] = pts_graph_core::text_string_width(str_repeat('Z', $longest_header), self::$c['size']['tick_mark']) * 0.85;
		$this->i['graph_title'] = ($this->logarithmic_view ? 'Logarithmic ' : '') . ($this->selection_view ? $this->selection_view . ' ' : null) . 'Result Overview';
		$this->i['iveland_view'] = true;
		$this->i['show_graph_key'] = true;
		$this->i['is_multi_way_comparison'] = false;
		$this->i['graph_width'] = round($this->i['graph_width'] * 1.5, PHP_ROUND_HALF_EVEN);
		$this->i['top_start'] += $this->graph_key_height();
		$this->i['graph_height'] = $this->i['graph_width'] + $this->i['top_start'] + (count($this->result_objects) > 14 ? 50 : 0);
		$this->update_graph_dimensions($this->i['graph_width'], $this->i['graph_height'], true);
		//$this->results = $this->systems;
		return true;
	}
	protected function render_graph_heading($with_version = true)
	{
		$this->svg_dom->add_element('path', array('d' => 'm74 22v9m-5-16v16m-5-28v28m-23-2h12.5c2.485281 0 4.5-2.014719 4.5-4.5s-2.014719-4.5-4.5-4.5h-8c-2.485281 0-4.5-2.014719-4.5-4.5s2.014719-4.5 4.5-4.5h12.5m-21 5h-11m11 13h-2c-4.970563 0-9-4.029437-9-9v-20m-24 40v-20c0-4.970563 4.0294373-9 9-9 4.970563 0 9 4.029437 9 9s-4.029437 9-9 9h-9', 'stroke' => self::$c['color']['main_headers'], 'stroke-width' => 4, 'fill' => 'none', 'transform' => 'translate(' . 10 . ',' . round($this->i['top_heading_height'] / 40 + 1) . ')'));
		$this->svg_dom->add_text_element($this->i['graph_title'], array('x' => 100, 'y' => (4 + self::$c['size']['headers']), 'font-size' => self::$c['size']['headers'], 'fill' => self::$c['color']['main_headers'], 'text-anchor' => 'start'));
		$this->svg_dom->add_text_element($this->i['graph_version'], array('x' => 100, 'y' => (self::$c['size']['headers'] + 16), 'font-size' => self::$c['size']['key'], 'fill' => self::$c['color']['main_headers'], 'text-anchor' => 'start', 'xlink:href' => 'http://www.phoronix-test-suite.com/'));
	}
	public function renderGraph()
	{
		if(count($this->result_objects) < 3)
		{
			// No point in generating this if there aren't many valid tests
			return false;
		}
		//$this->update_graph_dimensions($this->i['graph_width'], $this->i['graph_height'] + $this->i['top_start'], true);
		$this->render_graph_init();
		//$this->graph_key_height();
		$this->render_graph_key();
		$this->render_graph_heading();

		$center_x = $this->i['graph_width'] / 2;
		$center_y = (($this->i['graph_height'] - $this->i['top_start']) / 2) + $this->i['top_start'];
		$max_depth = $center_x * 0.7;
		$scale = $max_depth / $this->i['graph_max_value'];
		$ro_count = count($this->result_objects);
		$degree_offset = 360 / $ro_count;
		$g_txt_common = $this->svg_dom->make_g(array('font-size' => self::$c['size']['tick_mark'], 'fill' => self::$c['color']['notches']));
		$g_txt_circle = $this->svg_dom->make_g(array('font-size' => self::$c['size']['tick_mark'] - 1, 'fill' => self::$c['color']['body_light'], 'text-anchor' => 'middle'));
		$g_txt_tests = $this->svg_dom->make_g(array('font-size' => self::$c['size']['tick_mark'] + 0.5, 'fill' => self::$c['color']['notches'], 'font-weight' => '800', 'dominant-baseline' => 'text-after-edge'));
		$g_txt_desc = $this->svg_dom->make_g(array('font-size' => self::$c['size']['tick_mark'], 'fill' => self::$c['color']['notches'], 'dominant-baseline' => 'text-after-edge'));

		for($i = 1; $i <= $this->i['graph_max_value']; $i += (($this->i['graph_max_value'] -1) / 4))
		{
			$radius = round($scale * $i);
			$this->svg_dom->draw_svg_circle($center_x, $center_y, $radius, 'transparent', array('stroke' => self::$c['color']['body_light'], 'stroke-width' => 1, 'stroke-dasharray' => '10,10,10'));
			if(!$this->logarithmic_view)
			{
				$this->svg_dom->add_text_element(round($i * 100) . '%', array('x' => $center_x, 'y' => $center_y + $radius + 2, 'dominant-baseline' => 'hanging'), $g_txt_circle);
			}
		}

		$i = 0;
		$prev_x = array();
		$prev_y = array();
		$orig_x = array();
		$orig_y = array();

		foreach($this->result_objects as &$r)
		{
			$deg = round($degree_offset * $i);
			foreach($r['rel'] as $identifier => $value)
			{
				$x = $center_x + round(($value * $scale) * cos(deg2rad($deg)));
				$y = $center_y + round(($value * $scale) * sin(deg2rad($deg)));

				if(isset($prev_x[$identifier]))
				{
					$this->svg_dom->draw_svg_line($prev_x[$identifier], $prev_y[$identifier], $x, $y, $this->get_paint_color($identifier), 3);
				}

				$prev_x[$identifier] = $x;
				$prev_y[$identifier] = $y;
			}

			$x_txt = $center_x + ceil(($this->i['graph_max_value'] * 1.03 * $scale) * cos(deg2rad($deg)));
			$y_txt = $center_y + ceil(($this->i['graph_max_value'] * 1.03 * $scale) * sin(deg2rad($deg)));
			$desc = $r['ro']->get_arguments_description_shortened();
			if($x_txt >= $center_x)
			{
				$text_anchor = 'start';
			}
			else
			{
				$text_anchor = 'end';
			}

			$v_offset = $y_txt < $center_y && !empty($desc) ? -5 : 0;
			$rotate = $ro_count > 14 ? array('transform' => 'rotate(' . ($deg > 90 && $deg < 270 ? $deg + 180 : $deg) . ' ' . $x_txt . ' ' . $y_txt . ')') : array();
			$this->svg_dom->add_text_element($r['ro']->test_profile->get_title(), array_merge(array('x' => $x_txt, 'y' => $y_txt + $v_offset, 'text-anchor' => $text_anchor), $rotate), $g_txt_tests);
			if($desc && $desc != 'Geometric Mean' && $desc != 'D.G.M')
			{
				$this->svg_dom->add_text_element($desc, array_merge(array('x' => $x_txt, 'y' => $y_txt + 10, 'text-anchor' => $text_anchor), $rotate), $g_txt_desc);
			}

			if($i == 0)
			{
				$orig_x = $prev_x;
				$orig_y = $prev_y;
			}
			$i++;
		}

		if(!empty($orig_x))
		{
			foreach($r['rel'] as $identifier => $value)
			{
				$this->svg_dom->draw_svg_line($prev_x[$identifier], $prev_y[$identifier], $orig_x[$identifier], $orig_y[$identifier], $this->get_paint_color($identifier), 2);
			}
		}

		return true;
	}
}

?>
