<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2014, Phoronix Media
	Copyright (C) 2010 - 2014, Michael Larabel
	pts_OverviewGraph.php: A graping object to create an "overview" / mini graphs of a pts_result_file for pts_Graph

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

class pts_OverviewGraph extends pts_Graph
{
	protected $result_file;

	protected $system_identifiers;
	protected $test_titles;
	protected $graphs_per_row;
	protected $graph_item_width;

	protected $graph_row_height = 120;
	protected $graph_row_count;

	public $skip_graph = false;

	public function __construct($result_file)
	{
		$result_object = null;
		parent::__construct($result_object, $result_file);

		// System Identifiers
		if($result_file->is_multi_way_comparison())
		{
			// Multi way comparisons currently render the overview graph as blank
			$this->skip_graph = true;
			return;
		}

		$this->system_identifiers = $result_file->get_system_identifiers();
		if(count($this->system_identifiers) < 2)
		{
			// No point in generating this when there is only one identifier
			$this->skip_graph = true;
			return;
		}

		$result_objects = array();
		foreach($result_file->get_result_objects() as $result_object)
		{
			if($result_object->test_profile->get_display_format() == 'BAR_GRAPH')
			{
				array_push($result_objects, $result_object);
			}
		}

		$result_object_count = count($result_objects);
		if($result_object_count < 3)
		{
			// No point in generating this if there aren't many tests
			$this->skip_graph = true;
			return;
		}
		$result_file->override_result_objects($result_objects);

		// Test Titles
		$this->i['identifier_size'] = 6.5;
		$this->i['graph_width'] = 1000;

		$titles = $result_file->get_test_titles();
		list($longest_title_width, $longest_title_height) = pts_svg_dom::estimate_text_dimensions(pts_strings::find_longest_string($titles), $this->i['identifier_size']);

		$this->i['left_start'] += 20;
		$this->graphs_per_row = min((count($this->system_identifiers) > 10 ? 6 : 10), floor(($this->i['graph_width'] - $this->i['left_start'] - $this->i['left_end_right']) / ($longest_title_width + 4)));
		$this->graph_item_width = floor(($this->i['graph_width'] - $this->i['left_start'] - $this->i['left_end_right']) / $this->graphs_per_row);
		$this->graph_row_count = ceil($result_object_count / $this->graphs_per_row);

		$this->i['top_start'] += 20 + (count($this->system_identifiers) / 3 * $this->i['identifier_size']);
		$height = $this->i['top_start'] + ($this->graph_row_count * ($this->graph_row_height + 15));

		$this->graph_title = $result_file->get_title();
		$this->graph_y_title = null;
		$this->i['graph_proportion'] = 'HIB';
		$this->i['show_background_lines'] = true;

		$this->update_graph_dimensions($this->i['graph_width'], $height, true);
		$this->result_file = $result_file;

		return true;
	}
	public function doSkipGraph()
	{
		return $this->skip_graph;
	}
	public function renderGraph()
	{
		$this->graph_data_title = &$this->system_identifiers;
		$this->i['graph_max_value'] = 1.0;
		$l_height = 15;
		$this->i['key_line_height'] = $l_height;

		if(($key_count = count($this->graph_data_title)) > 8)
		{
			$this->update_graph_dimensions(-1, $this->i['graph_height'] + (floor(($key_count - 8) / 4) * 14), true);
		}

		// Do the actual work
		$this->render_graph_init();
		$this->render_graph_key();

		for($i = 0; $i < $this->graph_row_count; $i++)
		{
			$this->render_graph_base($this->i['left_start'], $this->i['top_start'] + ($i * ($this->graph_row_height + $l_height)), $this->i['graph_left_end'], $this->i['top_start'] + ($i * ($this->graph_row_height + $l_height)) + $this->graph_row_height);
			$this->render_graph_value_ticks($this->i['left_start'], $this->i['top_start'] + ($i * ($this->graph_row_height + $l_height)), $this->i['graph_left_end'], $this->i['top_start'] + ($i * ($this->graph_row_height + $l_height)) + $this->graph_row_height, false);
		}

		$row = 0;
		$col = 0;

		$bar_count = count($this->system_identifiers);
		$inter_width = $this->graph_item_width * 0.1;
		$bar_width = floor(($this->graph_item_width - ($inter_width * 2)) / $bar_count);
		$has_graphed_a_bar = false;

		foreach($this->result_file->get_result_objects() as $i => $result_object)
		{
			$top_start = $this->i['top_start'] + ($row * ($this->graph_row_height + $l_height));
			$top_end = round($this->i['top_start'] + ($row * ($this->graph_row_height + $l_height)) + $this->graph_row_height);
			$px_bound_left = $this->i['left_start'] + ($this->graph_item_width * ($col % $this->graphs_per_row));

			$this->svg_dom->add_element_with_value('text', $result_object->test_profile->get_title(), array('x' => ($px_bound_left + ($this->graph_item_width * 0.5)), 'y' => ($top_end + 3), 'font-size' => $this->i['identifier_size'], 'fill' => self::$c['color']['headers'], 'text-anchor' => 'middle', 'dominant-baseline' => 'text-before-edge'));

			if($result_object->test_profile->get_display_format() == 'BAR_GRAPH')
			{
				$all_values = $result_object->test_result_buffer->get_values();

				switch($result_object->test_profile->get_result_proportion())
				{
					case 'HIB':
						$divide_value = max($all_values);
						break;
					case 'LIB':
						$divide_value = min($all_values);
						break;
				}

				foreach($result_object->test_result_buffer->get_buffer_items() as $x => $buffer_item)
				{
					$paint_color = $this->get_paint_color($buffer_item->get_result_identifier());

					switch($result_object->test_profile->get_result_proportion())
					{
						case 'HIB':
							$value = $buffer_item->get_result_value() / $divide_value;
							break;
						case 'LIB':
							$value = $divide_value / $buffer_item->get_result_value();
							break;
					}

					$graph_size = round(($value / $this->i['graph_max_value']) * ($top_end - $top_start));
					$value_plot_top = $top_end + 1 - $graph_size;

					$px_left = $px_bound_left + $inter_width + ($bar_width * $x);
					$this->svg_dom->add_element('rect', array('x' => $px_left, 'y' => $value_plot_top, 'width' => $bar_width, 'height' => ($top_end - $value_plot_top), 'fill' => $paint_color, 'stroke' => self::$c['color']['body_light'], 'stroke-width' => 1));
				}

				$has_graphed_a_bar = true;
			}

			if(($i + 1) % $this->graphs_per_row == 0 && $i != 0)
			{
				$this->svg_dom->draw_svg_line($this->i['left_start'] + $this->graph_item_width, $top_end, $this->i['graph_left_end'] - ($this->i['graph_width'] % $this->graph_item_width), $top_end, self::$c['color']['notches'], 10, array('stroke-dasharray' => '1,' . ($this->graph_item_width - 1)));
				$this->svg_dom->draw_svg_line($this->i['left_start'], $top_end, $this->i['graph_left_end'], $top_end, self::$c['color']['notches'], 1);

				$row++;
			}
			$col++;
		}

		if($has_graphed_a_bar == false)
		{
			// Don't show an empty overview graph...
			$this->skip_graph = true;
		}


		//$this->render_graph_base($this->i['left_start'], $this->i['top_start'], $this->i['graph_left_end'], $this->i['graph_top_end']);
		$this->render_graph_heading();
		//$this->render_graph_watermark();
	}
}

?>
