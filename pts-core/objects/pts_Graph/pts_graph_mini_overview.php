<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2020, Phoronix Media
	Copyright (C) 2010 - 2020, Michael Larabel

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

// Draw mini graphs for all of the results in a given result file

class pts_graph_mini_overview extends pts_graph_core
{
	private $result_objects = array();
	private $systems = array();
	private $selection_view = null;

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

		usort($result_objects, array('pts_graph_run_vs_run', 'cmp_result_object_sort'));
		$longest_header = 0;
		$all_max = array();
		foreach($result_objects as &$r)
		{
			if($this->selection_view == null && $r->test_profile->get_identifier() == null)
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

		if(count($this->result_objects) < 12)
		{
			// No point in generating this if there aren't many valid tests
			return false;
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
		$this->i['graph_title'] = ($this->selection_view ? $this->selection_view . ' ' : null) . 'Result Overview';
		$this->i['iveland_view'] = true;
		$this->i['show_graph_key'] = true;
		$this->i['is_multi_way_comparison'] = false;
		$this->i['graph_width'] = round($this->i['graph_width'] * 2, PHP_ROUND_HALF_EVEN);
		$this->i['top_start'] += $this->graph_key_height();
		$this->i['per_graph_height'] = 250;
		$this->i['per_graph_width'] = 250;
		$this->i['graphs_per_row'] = floor($this->i['graph_width'] / ($this->i['per_graph_width'] - 20));
		$this->i['per_graph_width'] = floor(($this->i['graph_width'] - 20) / $this->i['graphs_per_row']);
		$this->i['graph_height'] = $this->i['top_start'] + (ceil(count($this->result_objects) / $this->i['graphs_per_row']) * ($this->i['per_graph_height']));
		$this->update_graph_dimensions($this->i['graph_width'], $this->i['graph_height'], true);
		//$this->results = $this->systems;
		return true;
	}
	protected function render_graph_heading($with_version = true)
	{
		$this->svg_dom->add_element('path', array('d' => 'm74 22v9m-5-16v16m-5-28v28m-23-2h12.5c2.485281 0 4.5-2.014719 4.5-4.5s-2.014719-4.5-4.5-4.5h-8c-2.485281 0-4.5-2.014719-4.5-4.5s2.014719-4.5 4.5-4.5h12.5m-21 5h-11m11 13h-2c-4.970563 0-9-4.029437-9-9v-20m-24 40v-20c0-4.970563 4.0294373-9 9-9 4.970563 0 9 4.029437 9 9s-4.029437 9-9 9h-9', 'stroke' => self::$c['color']['main_headers'], 'stroke-width' => 4, 'fill' => 'none', 'xlink:href' => 'https://www.phoronix-test-suite.com/', 'transform' => 'translate(' . 10 . ',' . round($this->i['top_heading_height'] / 40 + 1) . ')'));
		$this->svg_dom->add_text_element($this->i['graph_title'], array('x' => 100, 'y' => (4 + self::$c['size']['headers']), 'font-size' => self::$c['size']['headers'], 'fill' => self::$c['color']['main_headers'], 'text-anchor' => 'start'));
		$this->svg_dom->add_text_element($this->i['graph_version'], array('x' => 100, 'y' => (self::$c['size']['headers'] + 16), 'font-size' => self::$c['size']['key'], 'fill' => self::$c['color']['main_headers'], 'text-anchor' => 'start', 'href' => 'http://www.phoronix-test-suite.com/'));
	}
	public function renderGraph()
	{
		//$this->update_graph_dimensions($this->i['graph_width'], $this->i['graph_height'] + $this->i['top_start'], true);
		$this->render_graph_init();
		//$this->graph_key_height();
		$this->render_graph_key();
		$this->render_graph_heading();

		$i = 0;
		$row = 1;
		$col = -1;

		$g_text = $this->svg_dom->make_g(array('font-size' => self::$c['size']['tick_mark'], 'fill' => self::$c['color']['notches']));
		$g_bars = $this->svg_dom->make_g(array('stroke' => self::$c['color']['body_light'], 'stroke-width' => 1));
		$graph_width_half = round($this->i['per_graph_width'] / 2);

		foreach($this->result_objects as &$r)
		{
			$col++;
			if($col == $this->i['graphs_per_row'])
			{
				$row++;
				$col = 0;
			}
			$graph_start_left = 10 + ($col * $this->i['per_graph_width']);
			$graph_end_left = $graph_start_left + $this->i['per_graph_width'];
			$graph_start_top = $this->i['top_start'] + ($row * $this->i['per_graph_height']);
			$identifier_offset = 0;
			$bar_width = floor(($this->i['per_graph_width'] - 20) / count($r['rel']));
			foreach($r['rel'] as $identifier => $value)
			{
				$bar_height = ($value / $r['max']) * ($this->i['per_graph_height'] - 30);
				$bar_left = $graph_start_left + ($identifier_offset * $bar_width);
				$this->svg_dom->add_element('rect', array('x' => $bar_left, 'y' => ($graph_start_top - 20 - $bar_height), 'height' => $bar_height, 'width' => $bar_width, 'fill' => $this->get_paint_color($identifier)), $g_bars);

				$identifier_offset++;
			}

			$this->svg_dom->draw_svg_line(($graph_start_left + 5), ($graph_start_top - 20), ($graph_end_left - 5), ($graph_start_top - 20), self::$c['color']['notches'], 1);
			$this->svg_dom->add_text_element($r['ro']->test_profile->get_title(), array('x' => $graph_start_left + $graph_width_half, 'y' => $graph_start_top - 10, 'text-anchor' => 'middle', 'font-weight' => 'bold'), $g_text);
			$this->svg_dom->add_text_element($r['ro']->get_arguments_description_shortened(), array('x' => $graph_start_left + $graph_width_half, 'y' => $graph_start_top , 'text-anchor' => 'middle'), $g_text);
			$i++;
		}

		return true;
	}
}

?>
