<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2016, Phoronix Media
	Copyright (C) 2010 - 2016, Michael Larabel
	pts_RadarOverviewGraph.php: New display type being derived from pts_OverviewGraph... WIP

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

class pts_RadarOverviewGraph extends pts_graph_core
{
	public $skip_graph = false;
	private $result_objects = array();
	private $result_file = null;

	public static function cmp_result_object_sort($a, $b)
	{
		$a = $a->test_profile->get_test_hardware_type() . $a->test_profile->get_result_scale_formatted() . $a->test_profile->get_test_software_type() . $a->test_profile->get_identifier(true) . $a->get_arguments_description();
		$b = $b->test_profile->get_test_hardware_type() . $b->test_profile->get_result_scale_formatted() . $b->test_profile->get_test_software_type() . $b->test_profile->get_identifier(true) . $b->get_arguments_description();

		return strcmp($a, $b);
	}
	public function __construct(&$result_file)
	{
		$result_object = null;
		parent::__construct($result_object, $result_file);

		// System Identifiers
		$system_identifiers = array();
		foreach($result_file->get_systems() as $system)
		{
			$system_identifiers[] = $system->get_identifier();
		}

		if($result_file->is_multi_way_comparison() || count($system_identifiers) < 3)
		{
			// Multi way comparisons currently render the overview graph as blank
			// If there aren't more than 3 tests then don't render
			// If there aren't 3 or more systems then don't render
			$this->skip_graph = true;
			return;
		}

		$result_objects = $result_file->get_result_objects();
		usort($result_objects, array('pts_RadarOverviewGraph', 'cmp_result_object_sort'));

		foreach($result_objects as &$r)
		{
			if(count($r->test_result_buffer->get_buffer_items()) != count($system_identifiers))
			{
				continue;
			}
			if($r->normalize_buffer_values() == false)
			{
				continue;
			}

			$r_multiple = max($r->test_result_buffer->get_values());
			if($r_multiple > 10 || $r_multiple < 1.02)
			{
				continue;
			}

			$this->i['graph_max_value'] = max($this->i['graph_max_value'], $r_multiple);
			$r->test_result_buffer->sort_buffer_values(false);
			$this->result_objects[] = $r;
		}

		if(count($this->result_objects) < 3)
		{
			// No point in generating this if there aren't many valid tests
			$this->skip_graph = true;
			return;
		}

		$this->i['identifier_size'] = 6.5;
		$this->i['graph_height'] = $this->i['graph_width'];
		$this->i['left_start'] = 35;
		$this->graph_title = $result_file->get_title();
		$this->graph_data_title = $system_identifiers;
		$this->i['iveland_view'] = true;
		$this->result_file = &$result_file;

		return true;
	}
	public function doSkipGraph()
	{
		return $this->skip_graph;
	}
	protected function render_graph_heading($with_version = true)
	{
		$this->svg_dom->add_element('rect', array('x' => 0, 'y' => 0, 'width' => $this->i['graph_width'], 'height' => $this->i['top_heading_height'], 'fill' => self::$c['color']['main_headers']));
		$this->svg_dom->add_element('image', array('xlink:href' => 'https://openbenchmarking.org/static/images/pts-77x40-white.png', 'x' => 10, 'y' => round($this->i['top_heading_height'] / 40 + 1), 'width' => 77, 'height' => 40));
		$this->svg_dom->add_text_element($this->graph_title, array('x' => 100, 'y' => (4 + self::$c['size']['headers']), 'font-size' => self::$c['size']['headers'], 'fill' => self::$c['color']['background'], 'text-anchor' => 'start'));
		$this->svg_dom->add_text_element($this->i['graph_version'], array('x' => 100, 'y' => (self::$c['size']['headers'] + 16), 'font-size' => self::$c['size']['key'], 'fill' => self::$c['color']['background'], 'text-anchor' => 'start', 'href' => 'http://www.phoronix-test-suite.com/'));
	}
	public function renderGraph()
	{
		$this->i['top_heading_height'] = max(self::$c['size']['headers'] + 22 + self::$c['size']['key'], 48);
		$this->i['top_start'] = $this->i['top_heading_height'] + 50;
		$this->update_graph_dimensions($this->i['graph_width'], $this->i['graph_height'] + $this->i['top_start'], true);

		// Do the actual work
		$this->render_graph_init();
		$this->graph_key_height();
		$this->render_graph_key();
		$this->render_graph_heading();

		$work_area = $this->i['graph_left_end'] - $this->i['left_start'];
		$unit_size = floor($work_area / ($this->i['graph_max_value'] * 1.05));

		for($i = $this->i['graph_max_value']; $i >= 0.99; $i -= (($this->i['graph_max_value'] - 1) / $this->i['mark_count']))
		{
			$num = pts_math::set_precision(round($i, 1), 1);
			$length = round($unit_size * $num);

			$this->svg_dom->draw_svg_arc($this->i['left_start'], $this->i['top_start'], $length, 10, 0.25, array('fill' => self::$c['color']['background'], 'stroke' => self::$c['color']['body_light'], 'stroke-width' => 1, 'stroke-dasharray' => '10,20'));
			$this->svg_dom->add_text_element($num, array('x' => ($this->i['left_start'] + $length), 'y' => ($this->i['top_start'] - self::$c['size']['tick_mark'] + 2), 'font-size' => self::$c['size']['tick_mark'], 'fill' => self::$c['color']['notches'], 'text-anchor' => 'middle'));
			$this->svg_dom->draw_svg_line($this->i['left_start'] + $length, $this->i['top_start'] - 6, $this->i['left_start'] + $length, $this->i['top_start'], self::$c['color']['notches'], 1);

			$this->svg_dom->add_text_element($num, array('x' => ($this->i['left_start'] - 8), 'y' => ($this->i['top_start'] + $length), 'font-size' => self::$c['size']['tick_mark'], 'fill' => self::$c['color']['notches'], 'text-anchor' => 'end', 'dominant-baseline' => 'middle'));
			$this->svg_dom->draw_svg_line($this->i['left_start'] - 6, $this->i['top_start'] + $length, $this->i['left_start'], $this->i['top_start'] + $length, self::$c['color']['notches'], 1);
		}

		$this->svg_dom->draw_svg_line($this->i['left_start'], $this->i['top_start'], $this->i['graph_left_end'], $this->i['top_start'], self::$c['color']['notches'], 1);
		$this->svg_dom->draw_svg_line($this->i['left_start'], $this->i['top_start'], $this->i['left_start'], $this->i['graph_top_end'], self::$c['color']['notches'], 1);
		$this->svg_dom->draw_svg_line($this->i['graph_left_end'], $this->i['graph_top_end'], $this->i['graph_left_end'], $this->i['top_start'], self::$c['color']['notches'], 1);
		$this->svg_dom->draw_svg_line($this->i['left_start'], $this->i['graph_top_end'], $this->i['graph_left_end'], $this->i['graph_top_end'], self::$c['color']['notches'], 1);

		for($i = 1, $result_object_count = count($this->result_objects); $i < $result_object_count; $i++)
		{
			for($c = 0, $c_count = $this->result_objects[$i]->test_result_buffer->get_count(); $c < $c_count; $c++)
			{
				$pre_rad = deg2rad(360 - ((($i - 1) / $result_object_count) * 90));
				$pre_result = $this->result_objects[($i - 1)]->test_result_buffer->get_buffer_item($c)->get_result_value();

				$result = $this->result_objects[$i]->test_result_buffer->get_buffer_item($c)->get_result_value();

				if(($result_object_count - 1) == $i && 90 % $result_object_count != 0)
				{
					$rad = deg2rad(270);
				}
				else
				{
					$rad = deg2rad(360 - (($i / $result_object_count) * 90));
				}

				$result_identifier = $this->result_objects[$i]->test_result_buffer->get_buffer_item($c)->get_result_identifier();

				$pre_size = $unit_size * $pre_result;
				$size = $unit_size * $result;

				$tooltip = $this->result_objects[$i]->test_profile->get_title() . ' - ' . $this->result_objects[$i]->test_result_buffer->get_buffer_item($c)->get_result_identifier() . ' - ' . $this->result_objects[$i]->test_result_buffer->get_buffer_item($c)->get_result_value() . 'x Faster Than ' . $this->result_objects[$i]->test_result_buffer->get_buffer_item(($c_count - 1))->get_result_identifier();
				$points = array(
						array($this->i['left_start'], $this->i['top_start']),
						array(round($this->i['left_start'] + cos($pre_rad) * $pre_size), round($this->i['top_start'] + abs(sin($pre_rad)) * $pre_size)),
						array(round($this->i['left_start'] + cos($rad) * $pre_size), round($this->i['top_start'] + abs(sin($rad)) * $pre_size))
					);

				$svg_poly = array();
				foreach($points as $point_pair)
				{
					$svg_poly[] = implode(',', $point_pair);
				}
				$this->svg_dom->add_element('polygon', array('points' => implode(' ', $svg_poly), 'fill' => $this->get_paint_color($result_identifier), 'stroke' => self::$c['color']['text'], 'stroke-width' => 2, 'xlink:title' => $tooltip));
			}
		}

		$this->svg_dom->draw_svg_arc($this->i['left_start'], $this->i['top_start'], round($unit_size), 10, 0.25, array('fill' => self::$c['color']['background'], 'stroke' => self::$c['color']['notches'], 'stroke-width' => 1));

		$last_hardware_type = $this->result_objects[0]->test_profile->get_test_hardware_type();
		$hw_types = array();
		$last_hardware_type_i = 0;

		for($i = 0, $result_object_count = count($this->result_objects); $i < $result_object_count; $i++)
		{
			$hardware_type = $this->result_objects[$i]->test_profile->get_test_hardware_type();

			if($hardware_type != $last_hardware_type || $i == ($result_object_count - 1))
			{
				if($i != ($result_object_count - 1))
				{
					$rad = deg2rad(360 - (($i / $result_object_count) * 90));
					$cos_unit = cos($rad) * $unit_size;
					$sin_unit = abs(sin($rad)) * $unit_size;
					$this->svg_dom->draw_svg_line(
						round($this->i['left_start'] + $cos_unit),
						round($this->i['top_start'] + $sin_unit),
						round($this->i['left_start'] + $cos_unit * $this->i['graph_max_value']),
						round($this->i['top_start'] + $sin_unit * $this->i['graph_max_value']),
						self::$c['color']['alert'],
						1);
				}

				$rad = deg2rad(360 - ((((($i - $last_hardware_type_i) / 2) + $last_hardware_type_i) / $result_object_count) * 90));
				$cos_unit = $this->i['left_start'] + cos($rad) * $unit_size * 0.9;
				$sin_unit = $this->i['top_start'] + abs(sin($rad)) * $unit_size * 0.9;

				$this->svg_dom->add_text_element($last_hardware_type, array('x' => $cos_unit, 'y' => $sin_unit, 'font-size' => self::$c['size']['bars'], 'fill' => self::$c['color']['alert'], 'text-anchor' => 'end', 'dominant-baseline' => 'middle'));

				$hw_types[] = $last_hardware_type;
				$last_hardware_type = $hardware_type;
				$last_hardware_type_i = $i;
			}
		}

		$hw = array();
		$sw = array();
		foreach($this->result_file->get_systems() as $system)
		{
			$hw[] = $system->get_hardware();
			$sw[] = $system->get_software();
		}
		$shw = array();

		foreach($this->graph_data_title as $i => $title)
		{
			$merged = pts_result_file_analyzer::system_component_string_to_array($hw[$i] . ', ' . $sw[$i], $hw_types);

			if(!empty($merged))
			{
				$shw[$title] = $merged;
			}
		}

		$i = 1;
		foreach($shw as $key => $line)
		{
			$this->svg_dom->add_text_element(implode('; ', $line), array('x' => ($this->i['graph_left_end'] - 4), 'y' => ($this->i['graph_top_end'] - ($i * self::$c['size']['key'])), 'font-size' => self::$c['size']['key'], 'fill' => $this->get_paint_color($key), 'text-anchor' => 'end', 'dominant-baseline' => 'middle'));
			$i++;
		}
	}
}

?>
