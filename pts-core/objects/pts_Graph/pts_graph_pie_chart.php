<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2019, Phoronix Media
	Copyright (C) 2010 - 2019, Michael Larabel
	pts_PieChart.php: A pie chart object for pts_Graph

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

class pts_graph_pie_chart extends pts_graph_core
{
	public function __construct(&$result_object, &$result_file = null, $extra_attributes = null)
	{
		$extra_attributes['skip_multi_way_comparison_check'] = true;
		parent::__construct($result_object, $result_file, $extra_attributes);
		$this->i['iveland_view'] = true;
		$this->i['graph_value_type'] = 'ABSTRACT';
		$this->i['hide_graph_identifiers'] = false;
		$this->i['identifier_width'] = 0;
		$this->i['show_real_pie_values'] = !isset($extra_attributes['no_pie_values']);
		$this->update_graph_dimensions($this->i['graph_width'], $this->i['graph_height'] + 100);
	}
	protected function render_graph_pre_init()
	{
		$pie_slices = count($this->graph_identifiers);
		$this->i['pie_sum'] = 0;

		foreach($this->results as $identifier => &$group)
		{
			foreach($group as &$buffer_item)
			{

				$this->i['pie_sum'] += $buffer_item->get_result_value();
			}
		}

		if($pie_slices > 8)
		{
			$this->update_graph_dimensions($this->i['graph_width'], $this->i['graph_height'] + 100);
		}

	}
	protected function render_graph_identifiers()
	{
		$key_strings = array();
		foreach($this->results as $identifier => &$group)
		{
			foreach($group as &$buffer_item)
			{
				$percent = pts_math::set_precision($buffer_item->get_result_value() / $this->i['pie_sum'] * 100, 1);
				array_push($key_strings, ($this->i['show_real_pie_values'] ? $buffer_item->get_result_value() . ' [' . $percent . '%]' : $percent . '%'));
			}
		}

		$key_count = count($key_strings);
		$key_item_width = 25 + $this->text_string_width(pts_strings::find_longest_string($this->graph_identifiers), self::$c['size']['key']);
		$key_item_width_value = 18 + $this->text_string_width(pts_strings::find_longest_string($key_strings), self::$c['size']['key']);
		$keys_per_line = floor(($this->i['graph_left_end'] - $this->i['left_start'] - 14) / ($key_item_width + $key_item_width_value));

		if($keys_per_line < 1)
		{
			$keys_per_line = 1;
		}

		$key_line_height = 14;
		$this->i['top_start'] += 12;
		$c_y = $this->i['top_start'] - $key_line_height - 5;

		for($i = 0; $i < $key_count; $i++)
		{
			$this_color = $this->get_paint_color($this->graph_identifiers[$i]);

			if($i > 0 && $i % $keys_per_line == 0)
			{
				$c_y += $key_line_height;
				$this->i['top_start'] += $key_line_height;
			}

			$c_x = $this->i['left_start'] + 13 + (($key_item_width + $key_item_width_value) * ($i % $keys_per_line));

			$this->svg_dom->add_element('rect', array('x' => ($c_x - 13), 'y' => ($c_y - 5), 'width' => 10, 'height' => 10, 'fill' => $this_color, 'stroke' => self::$c['color']['notches'], 'stroke-width' => 1));
			$this->svg_dom->add_text_element($this->graph_identifiers[$i], array('x' => $c_x, 'y' => $c_y, 'font-size' => self::$c['size']['key'], 'fill' => $this_color, 'text-anchor' => 'start', 'dominant-baseline' => 'middle'));
			$this->svg_dom->add_text_element($key_strings[$i], array('x' => ($c_x + $key_item_width + 20), 'y' => $c_y, 'font-size' => self::$c['size']['key'], 'fill' => $this_color, 'text-anchor' => 'end', 'dominant-baseline' => 'middle'));
		}
	}
	public function render_graph_finish()
	{
		$this->render_graph_identifiers();
		$this->render_graph_heading(false);

		$radius = min(($this->i['graph_height'] - $this->i['top_start'] - $this->i['top_end_bottom']), ($this->i['graph_width'] - $this->i['left_start'] - $this->i['left_end_right'])) / 2;
		$center_x = ($this->i['graph_width'] / 2);
		$center_y = $this->i['top_start'] + (($this->i['graph_height'] - $this->i['top_start'] - $this->i['top_end_bottom']) / 2);
		$offset_percent = 0;

		foreach($this->results as $identifier => &$group)
		{
			foreach($group as &$buffer_item)
			{
				$percent = pts_math::set_precision($buffer_item->get_result_value() / $this->i['pie_sum'] * 100, 3);
				$this->svg_dom->draw_svg_arc($center_x, $center_y, $radius, $offset_percent / 100, $percent/ 100, array('fill' => $this->get_paint_color($buffer_item->get_result_identifier()), 'stroke' => self::$c['color']['border'], 'stroke-width' => 2));
				$offset_percent += $percent;
			}
		}

		if(!empty(self::$c['text']['watermark']))
		{
			$this->svg_dom->add_text_element(self::$c['text']['watermark'], array('x' => ($this->i['graph_width'] / 2), 'y' => ($this->i['graph_height'] - 15), 'font-size' => 10, 'fill' => self::$c['color']['text'], 'text-anchor' => 'middle', 'dominant-baseline' => 'text-before-edge'));
		}
	}
}

?>
