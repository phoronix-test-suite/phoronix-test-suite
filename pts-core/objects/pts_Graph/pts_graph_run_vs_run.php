<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2019, Phoronix Media
	Copyright (C) 2010 - 2019, Michael Larabel

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

class pts_graph_run_vs_run extends pts_graph_core
{
	private $result_objects = array();
	private $system_left = null;
	private $system_right = null;

	public static function cmp_result_object_sort($a, $b)
	{
		$a = $a->test_profile->get_test_hardware_type() . $a->test_profile->get_result_scale_formatted() . $a->test_profile->get_test_software_type() . $a->test_profile->get_identifier(true) . $a->get_arguments_description();
		$b = $b->test_profile->get_test_hardware_type() . $b->test_profile->get_result_scale_formatted() . $b->test_profile->get_test_software_type() . $b->test_profile->get_identifier(true) . $b->get_arguments_description();

		return strcmp($a, $b);
	}
	public function __construct($result_file)
	{
		$rf = clone $result_file;
		if($rf->get_system_count() != 2)
		{
			return false;
		}

		$systems = $rf->get_systems();
		$this->system_left = array_shift($systems)->get_identifier();
		$this->system_right = array_shift($systems)->get_identifier();

		$result_object = null;
		parent::__construct($result_object, $rf);

		// System Identifiers
		$result_objects = $rf->get_result_objects();
		usort($result_objects, array('pts_graph_run_vs_run', 'cmp_result_object_sort'));
		$longest_header = 0;
		foreach($result_objects as &$r)
		{
			if($r->test_profile->get_identifier() == null)
			{
				continue;
			}
			if(count($r->test_result_buffer->get_buffer_items()) != 2)
			{
				continue;
			}
			if($r->normalize_buffer_values() == false)
			{
				continue;
			}

			$relative_win = $r->get_result_first(false);
			if($relative_win < 1.02)
			{
				continue;
			}
			$this->i['graph_max_value'] = max($this->i['graph_max_value'], $relative_win);
			$this->result_objects[] = array('winner' => $r->get_result_first(true), 'relative' => $relative_win, 'ro' => $r);
			$longest_header = max($longest_header, strlen($r->test_profile->get_title()), strlen($r->get_arguments_description_shortened()));
		}

		if(count($this->result_objects) < 3)
		{
			// No point in generating this if there aren't many valid tests
			return false;
		}

		$this->i['identifier_size'] = 6.5;
		$this->i['top_heading_height'] = max(self::$c['size']['headers'] + 22 + self::$c['size']['key'], 48);
		$this->i['top_start'] = $this->i['top_heading_height'] + 30;
		$this->i['graph_height'] = 20 + $this->i['top_start'] + ((count($this->result_objects) * 2) * (self::$c['size']['tick_mark'] + 4));
		$this->i['left_start'] = ceil(pts_graph_core::text_string_width(str_repeat('Z', $longest_header), self::$c['size']['tick_mark']) * 0.85);
		$this->i['graph_title'] = $this->system_left . ' vs. ' . $this->system_right . ' Comparison';
		$this->graph_data_title = ' vs  Comparison';
		$this->i['iveland_view'] = true;
		$this->i['graph_width'] *= 1.5;

		$this->update_graph_dimensions($this->i['graph_width'], $this->i['graph_height'] + $this->i['top_start'], true);
		$this->get_paint_color($this->system_left, true);
		$this->get_paint_color($this->system_right, true);

		return true;
	}
	protected function render_graph_heading($with_version = true)
	{
		$this->svg_dom->add_element('rect', array('x' => 0, 'y' => 0, 'width' => $this->i['graph_width'], 'height' => $this->i['top_heading_height'], 'fill' => self::$c['color']['main_headers']));
		$this->svg_dom->add_element('image', array('xlink:href' => 'https://openbenchmarking.org/static/images/pts-77x40-white.png', 'x' => 10, 'y' => round($this->i['top_heading_height'] / 40 + 1), 'width' => 77, 'height' => 40));
		$this->svg_dom->add_text_element($this->i['graph_title'], array('x' => 100, 'y' => (4 + self::$c['size']['headers']), 'font-size' => self::$c['size']['headers'], 'fill' => self::$c['color']['background'], 'text-anchor' => 'start'));
		$this->svg_dom->add_text_element($this->i['graph_version'], array('x' => 100, 'y' => (self::$c['size']['headers'] + 16), 'font-size' => self::$c['size']['key'], 'fill' => self::$c['color']['background'], 'text-anchor' => 'start', 'href' => 'http://www.phoronix-test-suite.com/'));
	}
	public function renderGraph()
	{
		if(count($this->result_objects) < 3)
		{
			// No point in generating this if there aren't many valid tests
			return false;
		}
		//$this->update_graph_dimensions($this->i['graph_width'], $this->i['graph_height'] + $this->i['top_start'], true);
		$this->i['graph_left_end'] -= 28;
		$plotting_width = $this->i['graph_left_end'] - $this->i['left_start'];
		$center_point = round($this->i['left_start'] + ($plotting_width / 2));
		$scale = round($plotting_width / 2) / ($this->i['graph_max_value'] - 1.0 + 0.25);
		// Do the actual work
		$this->render_graph_init();
		$this->graph_key_height();
		$this->render_graph_key();
		$this->render_graph_heading();
		$g_bars = $this->svg_dom->make_g(array('stroke' => self::$c['color']['body_light'], 'stroke-width' => 1));
		$g_txt_common = $this->svg_dom->make_g(array('font-size' => self::$c['size']['tick_mark'], 'fill' => self::$c['color']['notches']));
		$g_txt_common_start = $this->svg_dom->make_g(array('font-size' => self::$c['size']['tick_mark'], 'fill' => self::$c['color']['notches'], 'text-anchor' => 'start'));
		$g_txt_common_end = $this->svg_dom->make_g(array('font-size' => self::$c['size']['tick_mark'], 'fill' => self::$c['color']['notches'], 'text-anchor' => 'end'));
		$g_bold = $this->svg_dom->make_g(array('font-size' => self::$c['size']['tick_mark'], 'fill' => self::$c['color']['notches'], 'font-weight' => 'bold',  'text-anchor' => 'end'));
		$i = 0;
		foreach($this->result_objects as $r)
		{
			$vertical_offset = $this->i['top_start'] + ($i * (self::$c['size']['tick_mark'] + 4));
			$this->svg_dom->add_text_element($r['ro']->test_profile->get_title(), array('x' => ($this->i['left_start'] - 10), 'y' => $vertical_offset + 1, 'dominant-baseline' => 'hanging'), $g_bold);
			$this->svg_dom->add_text_element($r['ro']->get_arguments_description_shortened(), array('x' => ($this->i['left_start'] - 10), 'y' => $vertical_offset + self::$c['size']['tick_mark'] + 2, 'dominant-baseline' => 'hanging'), $g_txt_common_end);

			$this->svg_dom->draw_svg_line($this->i['left_start'], $vertical_offset, $this->i['left_start'] - 6, $vertical_offset, self::$c['color']['notches'], 1);

			$box_width = round(($r['relative'] - 1) * $scale);
			if($box_width == 0)
			{
				//continue;
			}
			$offset_start = $r['winner'] == $this->system_left ? $box_width * -1 : 0;
			$paint_color = $this->get_paint_color($r['winner']);
			$this->svg_dom->add_element('rect', array('x' => $center_point + $offset_start, 'y' => $vertical_offset, 'height' => (self::$c['size']['tick_mark'] * 2), 'width' => $box_width, 'fill' => $paint_color), $g_bars);

			if($r['winner'] == $this->system_left)
			{
				$this->svg_dom->add_text_element(round(($r['relative'] - 1) * 100, 1) . '%', array('x' => ($center_point - $box_width - 4), 'y' => $vertical_offset + self::$c['size']['tick_mark'], 'dominant-baseline' => 'middle'), $g_txt_common_end);
			}
			else
			{
				$this->svg_dom->add_text_element(round(($r['relative'] - 1) * 100, 1) . '%', array('x' => ($center_point + $box_width + 4), 'y' => $vertical_offset + self::$c['size']['tick_mark'], 'dominant-baseline' => 'middle'), $g_txt_common_start);
			}
			$i += 2;
		}

		$this->svg_dom->draw_svg_line($this->i['left_start'], $this->i['top_start'], $this->i['left_start'], $this->i['graph_top_end'], self::$c['color']['notches'], 1);
		$this->svg_dom->draw_svg_line($this->i['left_start'], $this->i['graph_top_end'], $this->i['graph_left_end'], $this->i['graph_top_end'], self::$c['color']['notches'], 1);
		$this->svg_dom->draw_svg_line($center_point, $this->i['graph_top_end'], $center_point, $this->i['top_start'], self::$c['color']['notches'], 1);
		$this->svg_dom->add_text_element($this->system_left, array('x' => $center_point - 4, 'y' => $this->i['top_start'] - 6, 'font-size' => round(self::$c['size']['tick_mark'] * 1.5), 'fill' => $this->get_paint_color($this->system_left), 'text-anchor' => 'end', 'font-weight' => 'bold'));
		$this->svg_dom->add_text_element($this->system_right, array('x' => $center_point + 4, 'y' => $this->i['top_start'] - 6, 'font-size' => round(self::$c['size']['tick_mark'] * 1.5), 'fill' => $this->get_paint_color($this->system_right), 'text-anchor' => 'start', 'font-weight' => 'bold'));

		for($i = 0; $i < $this->i['graph_max_value'] - 1.0; $i += round(($this->i['graph_max_value'] - 1.0) / 4, 3))
		{
			$val = $i == 0 ? 'Baseline' : '+' . round($i * 100, 1) . '%';
			$cx = round($center_point + ($i * $scale));
			$this->svg_dom->draw_svg_line($cx, $this->i['graph_top_end'] - 6, $cx, $this->i['graph_top_end'], self::$c['color']['notches'], 1);
			$this->svg_dom->add_text_element($val, array('x' => $cx, 'y' => $this->i['graph_top_end'] + 2, 'text-anchor' => 'middle', 'font-weight' => 'bold', 'dominant-baseline' => 'hanging'), $g_txt_common);

			if($i != 0)
			{
				$cx = round($center_point - ($i * $scale));
				$this->svg_dom->draw_svg_line($cx, $this->i['graph_top_end'] - 6, $cx, $this->i['graph_top_end'], self::$c['color']['notches'], 1);
				$this->svg_dom->add_text_element($val, array('x' => $cx, 'y' => $this->i['graph_top_end'] + 2, 'text-anchor' => 'middle', 'font-weight' => 'bold', 'dominant-baseline' => 'hanging'), $g_txt_common);
			}
		}

		return true;
	}
}

?>
