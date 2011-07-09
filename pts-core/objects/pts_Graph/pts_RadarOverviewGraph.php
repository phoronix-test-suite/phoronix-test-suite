<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2011, Phoronix Media
	Copyright (C) 2010 - 2011, Michael Larabel
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

class pts_RadarOverviewGraph extends pts_Graph
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
		// System Identifiers
		if($result_file->is_multi_way_comparison())
		{
			// Multi way comparisons currently render the overview graph as blank
			$this->skip_graph = true;
			return;
		}

		$system_identifiers = $result_file->get_system_identifiers();
		if(count($system_identifiers) < 2)
		{
			// No point in generating this when there is only one identifier
			$this->skip_graph = true;
			return;
		}

		// Test Titles
		if(count($result_file->get_test_titles()) < 3)
		{
			// No point in generating this if there aren't many tests
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

			$this->graph_maximum_value = max($this->graph_maximum_value, $r_multiple);
			$r->test_result_buffer->sort_buffer_values(false);
			array_push($this->result_objects, $r);
		}

		if(count($this->result_objects) < 3)
		{
			// No point in generating this if there aren't many valid tests
			$this->skip_graph = true;
			return;
		}

		$result_object = null;
		parent::__construct($result_object, $result_file);

		$this->graph_font_size_identifiers = 6.5;
		$this->graph_attr_height = $this->graph_attr_width;

		$this->graph_left_start = 35;

		$this->graph_title = $result_file->get_title();
		$this->graph_y_title = null;
		$this->graph_background_lines = true;
		$this->graph_attr_big_border = true;
		$this->graph_data_title = $system_identifiers;
		$this->iveland_view = true;

		$this->result_file = &$result_file;

		return true;
	}
	public function doSkipGraph()
	{
		return $this->skip_graph;
	}
	protected function render_graph_heading($with_version = true)
	{
		$this->graph_image->draw_rectangle(0, 0, $this->graph_attr_width, $this->graph_top_heading_height, $this->graph_color_main_headers);
		$this->graph_image->image_copy_merge(new pts_graph_ir_value($this->graph_image->png_image_to_type('http://www.phoronix-test-suite.com/external/pts-logo-77x40-white.png'), array('href' => 'http://www.phoronix-test-suite.com/')), 10, ($this->graph_top_heading_height / 40 + 1), 0, 0, 77, 40);
		$this->graph_image->write_text_left(new pts_graph_ir_value($this->graph_title), $this->graph_font, $this->graph_font_size_heading, $this->graph_color_background, 100, 12, $this->graph_left_end, 12);
		$this->graph_image->write_text_left(new pts_graph_ir_value($this->graph_version, array('href' => 'http://www.phoronix-test-suite.com/')), $this->graph_font, $this->graph_font_size_key, $this->graph_color_background, 100, $this->graph_font_size_heading + 15, $this->graph_left_end, $this->graph_font_size_heading + 15);
	}
	public function renderGraph()
	{
		$this->requestRenderer('SVG');
		$this->graph_top_heading_height = max($this->graph_font_size_heading + 22 + $this->graph_font_size_key, 48);
		$this->graph_top_start = $this->graph_top_heading_height + 50;
		$this->update_graph_dimensions($this->graph_attr_width, $this->graph_attr_height + $this->graph_top_start, true);

		// Do the actual work
		$this->render_graph_init();
		$this->graph_key_height();
		$this->render_graph_key();
		$this->render_graph_heading();

		$work_area = $this->graph_left_end - $this->graph_left_start;
		$unit_size = floor($work_area / ($this->graph_maximum_value * 1.05));

		for($i = $this->graph_maximum_value; $i >= 0.99; $i -= (($this->graph_maximum_value - 1) / $this->graph_attr_marks))
		{
			$num = pts_math::set_precision(round($i, 1), 1);
			$length = round($unit_size * $num);

			$this->graph_image->draw_arc($this->graph_left_start, $this->graph_top_start, $length, 10, 0.25, $this->graph_color_background, $this->graph_color_body_light, 1, null, true);

			$this->graph_image->write_text_center($num, $this->graph_font, $this->graph_font_size_tick_mark, $this->graph_color_notches, $this->graph_left_start + $length, $this->graph_top_start - 8 - $this->graph_font_size_tick_mark, $this->graph_left_start + $length, $this->graph_top_start - 8 - $this->graph_font_size_tick_mark);
			$this->graph_image->draw_line($this->graph_left_start + $length, $this->graph_top_start - 6, $this->graph_left_start + $length, $this->graph_top_start, $this->graph_color_notches, 1);

			$this->graph_image->write_text_right($num, $this->graph_font, $this->graph_font_size_tick_mark, $this->graph_color_notches, $this->graph_left_start - 8, $this->graph_top_start + $length, $this->graph_left_start - 8, $this->graph_top_start + $length);
			$this->graph_image->draw_line($this->graph_left_start - 6, $this->graph_top_start + $length, $this->graph_left_start, $this->graph_top_start + $length, $this->graph_color_notches, 1);
		}

		$this->graph_image->draw_line($this->graph_left_start, $this->graph_top_start, $this->graph_left_end, $this->graph_top_start, $this->graph_color_notches, 1);
		$this->graph_image->draw_line($this->graph_left_start, $this->graph_top_start, $this->graph_left_start, $this->graph_top_end, $this->graph_color_notches, 1);
		$this->graph_image->draw_line($this->graph_left_end, $this->graph_top_end, $this->graph_left_end, $this->graph_top_start, $this->graph_color_notches, 1);
		$this->graph_image->draw_line($this->graph_left_start, $this->graph_top_end, $this->graph_left_end, $this->graph_top_end, $this->graph_color_notches, 1);

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

				$this->graph_image->draw_polygon(
					array(
						array($this->graph_left_start, $this->graph_top_start),
						array(round($this->graph_left_start + cos($pre_rad) * $pre_size), round($this->graph_top_start + abs(sin($pre_rad)) * $pre_size)),
						array(round($this->graph_left_start + cos($rad) * $pre_size), round($this->graph_top_start + abs(sin($rad)) * $pre_size))
					),
					$this->get_paint_color($result_identifier),
					$this->graph_color_text,
					2,
					$this->result_objects[$i]->test_profile->get_title() . PHP_EOL . $this->result_objects[$i]->test_result_buffer->get_buffer_item($c)->get_result_identifier() . ' - ' . $this->result_objects[$i]->test_result_buffer->get_buffer_item($c)->get_result_value() . 'x Faster Than ' . $this->result_objects[$i]->test_result_buffer->get_buffer_item(($c_count - 1))->get_result_identifier());
			}
		}

		$this->graph_image->draw_arc($this->graph_left_start, $this->graph_top_start, round($unit_size), 10, 0.25, $this->graph_color_background, $this->graph_color_notches, 1, null);

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
					$this->graph_image->draw_line(
						round($this->graph_left_start + $cos_unit),
						round($this->graph_top_start + $sin_unit),
						round($this->graph_left_start + $cos_unit * $this->graph_maximum_value),
						round($this->graph_top_start + $sin_unit * $this->graph_maximum_value),
						$this->graph_color_alert,
						1);
				}

				$rad = deg2rad(360 - ((((($i - $last_hardware_type_i) / 2) + $last_hardware_type_i) / $result_object_count) * 90));
				$cos_unit = $this->graph_left_start + cos($rad) * $unit_size * 0.9;
				$sin_unit = $this->graph_top_start + abs(sin($rad)) * $unit_size * 0.9;

				$this->graph_image->write_text_right($last_hardware_type, $this->graph_font, $this->graph_font_size_bars, $this->graph_color_alert, $cos_unit, $sin_unit, $cos_unit, $sin_unit);

				array_push($hw_types, $last_hardware_type);
				$last_hardware_type = $hardware_type;
				$last_hardware_type_i = $i;
			}
		}

		$hw = $this->result_file->get_system_hardware();
		$sw = $this->result_file->get_system_software();
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
			$this->graph_image->write_text_right(implode('; ', $line), $this->graph_font, $this->graph_font_size_key, $this->get_paint_color($key), $this->graph_left_end - 4, $this->graph_top_end - ($i * $this->graph_font_size_key), $this->graph_left_end - 4, $this->graph_top_end - ($i * $this->graph_font_size_bars));
			$i++;
		}

		//$this->render_graph_base($this->graph_left_start, $this->graph_top_start, $this->graph_left_end, $this->graph_top_end);

		return $this->return_graph_image();
	}
}

?>
