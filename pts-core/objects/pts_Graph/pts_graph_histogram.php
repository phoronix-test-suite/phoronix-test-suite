<?php
/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2020, Phoronix Media
	Copyright (C) 2020, Michael Larabel

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

class pts_graph_histogram extends pts_graph_core
{
	protected $make_identifiers_web_links = false;
	protected $number_of_bins;
	protected $bin_increment;
	protected $val_min;
	protected $val_max;
	protected $bins;
	
	public function __construct(&$test_profile, $all_values, $extra_attributes = null)
	{
		$s = $test_profile->get_result_scale();
		$test_profile->set_result_scale(' ');
		$result = new pts_test_result($test_profile);
		$b = new pts_test_result_buffer();
		$result->set_test_result_buffer($b);
			
		$this->val_min = floor(min($all_values));
		$this->val_max = ceil(max($all_values));
		$result->set_used_arguments_description(count($all_values) . ' Results Range From ' . $this->val_min . ' To ' . $this->val_max . ' ' . $s);
		$range = abs($this->val_min - $this->val_max);
		$this->number_of_bins = min(50, max(12, ceil(sqrt($range))));
		$this->bin_increment = round($range / $this->number_of_bins, 3);
		if($this->bin_increment > 1)
		{
			$this->bin_increment = ceil($this->bin_increment);
		}
		
		$this->bins = array_fill(0, $this->number_of_bins, 0);
		sort($all_values);
		$current_bin = 0;
		$current_bin_count = 0;
		$next_bin_value = $this->val_min + $this->bin_increment;
		for($i = 0; $i < $this->number_of_bins; $i++)
		{
			$min_for_bin = $this->val_min + ($i * $this->bin_increment);
			$max_for_bin = $min_for_bin + $this->bin_increment;
			foreach($all_values as $x => $value)
			{
				if($value <= $max_for_bin && $value >= $min_for_bin)
				{
					$this->bins[$i]++;
					unset($all_values[$x]);
				}
				if($value > $max_for_bin)
				{
					break;
				}
			}
		}
		
		$extra_attributes['skip_multi_way_comparison_check'] = true;
		$extra_attributes['no_compact_results_var'] = true;
		$n = null;
		parent::__construct($result, $n, $extra_attributes);
		$this->update_graph_dimensions(920, 420, true);
		$this->results = array();
		$this->i['iveland_view'] = true;
		$this->i['graph_orientation'] = 'VERTICAL';
		$this->i['identifier_height'] = -1;
		$this->i['min_identifier_size'] = 6;
		$this->i['identifier_width'] = -1;
	}
	protected function render_graph_pre_init()
	{
		// Do some common work to this object
		$this->i['identifier_width'] = max(1, floor(($this->i['graph_left_end'] - $this->i['left_start']) / $this->number_of_bins));
	}
	protected function render_graph_identifiers()
	{
		$px_from_top_end = $this->i['graph_top_end'] + 5;
		$this->svg_dom->draw_svg_line($this->i['left_start'] + $this->i['identifier_width'], $this->i['graph_top_end'], $this->i['graph_left_end'] - ($this->i['graph_width'] % $this->i['identifier_width']), $this->i['graph_top_end'], self::$c['color']['notches'], 10, array('stroke-dasharray' => '1,' . ($this->i['identifier_width'] - 1)));

		for($i = 0; $i <= $this->number_of_bins; $i++)
		{
			$px_bound_left = $this->i['left_start'] + ($this->i['identifier_width'] * $i);
			$px_bound_right = $px_bound_left + $this->i['identifier_width'];
			if($i == ($this->number_of_bins) && $px_bound_right != $this->i['graph_left_end'])
			{
				$px_bound_right = $this->i['graph_left_end'];
			}

			$x = $px_bound_left;
			if(strlen($this->val_max) < 4)
			{
				$this->svg_dom->add_text_element($this->val_min + ($i * $this->bin_increment), array('x' => $x, 'y' => ($px_from_top_end + $this->i['identifier_size']), 'font-size' => $this->i['identifier_size'], 'fill' => self::$c['color']['headers'], 'text-anchor' => 'middle'));
			}
			else
			{
				$this->svg_dom->add_text_element($this->val_min + ($i * $this->bin_increment), array('x' => $x, 'y' => $px_from_top_end, 'font-size' => 9, 'fill' => self::$c['color']['headers'], 'text-anchor' => 'start', 'dominant-baseline' => 'middle', 'font-weight' => 'bold', 'transform' => 'rotate(90 ' . $x . ' ' . $px_from_top_end . ')'));
			}
		}
	}
	protected function maximum_graph_value($v = -1)
	{
		return parent::maximum_graph_value(max(max($this->bins), $this->i['mark_count']));
	}
	protected function render_graph_histogram()
	{
		$bar_width = floor($this->i['identifier_width']);
		$bar_font_size_ratio = 1;
		$paint_color = $this->get_paint_color('0');

		for($i = 0; $i < count($this->bins); $i++)
		{

			$value = $this->bins[$i];
			$graph_size = round(($value / $this->i['graph_max_value']) * ($this->i['graph_top_end'] - $this->i['top_start']));
			$value_plot_top = max($this->i['graph_top_end'] + 1 - $graph_size, 1);
			$px_bound_left = $this->i['left_start'] + ($this->i['identifier_width'] * $i);
			$px_bound_right = $px_bound_left + $bar_width;

			$this->svg_dom->add_element('rect', array('x' => ($px_bound_left + 1), 'y' => $value_plot_top, 'width' => $bar_width, 'height' => ($this->i['graph_top_end'] - $value_plot_top), 'fill' => $paint_color));
		}
		
		// write a new line along the bottom since the draw_rectangle_with_border above had written on top of it
		$this->svg_dom->draw_svg_line($this->i['left_start'], $this->i['graph_top_end'], $this->i['graph_left_end'], $this->i['graph_top_end'], self::$c['color']['notches'], 1);
	}
	protected function render_graph_result()
	{
		$this->render_graph_histogram();
	}
}

?>
