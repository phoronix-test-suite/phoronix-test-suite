<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2013, Phoronix Media
	Copyright (C) 2013, Michael Larabel

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


class pts_sys_graph
{
	protected $data = array(
		'title' => null,
		'x_scale' => null,
		'y_scale' => null,
		'y_max' => null,
		'reverse_x_direction' => false,
		);
	protected $attr = array(
		'width' => 500,
		'height' => 250,
		'background_color' => '#FFF',
		'border_color' => '#000',
		'shade_color' => '#efefef',
		'stroke_color' => '#949494',
		'paint_color' => '#044374',
		'text_color' => '#1F1F1F',
		'text_size' => 13,
		'text_size_sub' => 10,
		);
	protected $computed = array(
		'graph_area_y_end' => 40,
		'graph_area_y_start' => null,
		'graph_area_x_end' => null,
		'graph_area_x_start' => 40,
		'center_x' => null,
		'center_y' => null,
		'tick_frequency_x' => null,
		'tick_frequency_y' => null,
		'graph_area_width' => null,
		'graph_area_height' => null,
		);
	public $svg_dom;

	public function __construct($data)
	{
		$this->plot_data = array();
		foreach($data as $name => $value)
		{
		   $this->__set($name, $value);
		}
	}
	public function __set($name, $value)
	{
		$this->data[$name] = $value;
	}
	public function __get($name)
	{
		return isset($this->data[$name]) ? $this->data[$name] : false;
	}
	public function render_base()
	{
		$this->computed['tick_frequency_x'] = 10;
		$this->computed['tick_frequency_y'] = 5;

		$this->computed['graph_area_width'] = $this->attr['width'] - ($this->computed['graph_area_x_start'] * 2);
		$this->computed['graph_area_width'] = $this->computed['graph_area_width'] - ($this->computed['graph_area_width'] % $this->computed['tick_frequency_x']);
		$this->computed['graph_area_height'] = $this->attr['height'] - ($this->computed['graph_area_y_end'] * 2);
		$this->computed['graph_area_height'] = $this->computed['graph_area_height'] - ($this->computed['graph_area_height'] % $this->computed['tick_frequency_y']);

		$this->computed['center_y'] = round($this->attr['height']);
		$this->computed['center_x'] = round($this->attr['width']);
		$this->computed['graph_area_y_start'] = $this->computed['graph_area_y_end'] + $this->computed['graph_area_height'];
		$this->computed['graph_area_x_end'] = $this->computed['graph_area_x_start'] + $this->computed['graph_area_width'];

		// Render Base
		$this->svg_dom = new pts_svg_dom($this->attr['width'], $this->attr['height']);
		$this->svg_dom->add_element('rect', array('x' => 0, 'y' => 0, 'width' => $this->attr['width'], 'height' => $this->attr['height'], 'fill' => $this->attr['background_color']));
		$this->svg_dom->add_element('rect', array('x' => $this->computed['graph_area_x_start'], 'y' => $this->computed['graph_area_y_end'], 'width' => $this->computed['graph_area_width'], 'height' => $this->computed['graph_area_height'], 'fill' => $this->attr['shade_color'], 'stroke-width' => 1, 'stroke' => $this->attr['stroke_color']));

		// Plot Y
		$y_width = ($this->computed['graph_area_height'] / $this->computed['tick_frequency_y']);
		for($i = $this->computed['graph_area_y_start'] - $y_width; $i > $this->computed['graph_area_y_end']; $i -= $y_width)
		{
		$this->svg_dom->draw_svg_line($this->computed['graph_area_x_start'] - 5, $i, $this->computed['graph_area_x_end'], $i, $this->attr['stroke_color'], 1, array('stroke-dasharray' => '5,10'));
		}

		// Plot X
		$x_width = ($this->computed['graph_area_width'] / $this->computed['tick_frequency_x']);
		for($i = $this->computed['graph_area_x_start'] + $x_width; $i < $this->computed['graph_area_x_end']; $i += $x_width)
		{
		$this->svg_dom->draw_svg_line($i, $this->computed['graph_area_y_start'], $i, $this->computed['graph_area_y_end'], $this->attr['stroke_color'], 1, array('stroke-dasharray' => '5,10'));
		}

		// Text
		$this->svg_dom->add_text_element($this->data['y_scale'], array('x' => $this->computed['graph_area_x_end'], 'y' => $this->computed['graph_area_y_end'] - 5, 'font-size' => $this->attr['text_size_sub'], 'fill' => $this->attr['text_color'], 'text-anchor' => 'end', 'alignment-baseline' => 'above-edge'));
	}
	public function render_graph_data(&$graph_data)
	{
		if(count($graph_data) < 2)
		{
			return false;
		}

		$svg_dom = clone $this->svg_dom;
		if($this->data['y_max'] > 1)
		{
			$max_value = $this->data['y_max'];
		}
		else
		{
			$max_value = ceil(max($graph_data) * 1.25);
			$max_value = $max_value + ($max_value % $this->computed['tick_frequency_y']);
		}
		$vals_per_pixel = $max_value / $this->computed['graph_area_height'];


		for($i = $this->computed['graph_area_y_start']; $i > $this->computed['graph_area_y_end']; $i -= ($this->computed['graph_area_height'] / $this->computed['tick_frequency_y']))
		{
			$val = round($max_value - ($i - $this->computed['graph_area_y_end']) * $vals_per_pixel, 1);
			if($val <= 0)
			{
				continue;
			}

			$svg_dom->add_text_element($val, array('x' => $this->computed['graph_area_x_start'] - 8, 'y' => $i, 'font-size' => $this->attr['text_size_sub'], 'fill' => $this->attr['text_color'], 'text-anchor' => 'end', 'alignment-baseline' => 'middle'));
		}

		$graph_data_count = count($graph_data);
		for($i = $this->computed['graph_area_x_start']; $i < $this->computed['graph_area_x_end']; $i += ($this->computed['graph_area_width'] / $this->computed['tick_frequency_x']))
		{
			if($this->data['reverse_x_direction'] == true)
			{
				$val = round(($this->computed['graph_area_x_end'] - $i) / $this->computed['graph_area_width'] * $graph_data_count, 1);
			}
			else
			{
				$val = round(($i - $this->computed['graph_area_x_start']) / $this->computed['graph_area_width'] * $graph_data_count, 1);
			}
			if($val <= 0)
			{
				continue;
			}

			$this->svg_dom->add_text_element($val . $this->data['x_scale'], array('x' => $i, 'y' => $this->computed['graph_area_y_start'] + 8, 'font-size' => ($this->attr['text_size_sub'] - 1), 'fill' => $this->attr['text_color'], 'alignment-baseline' => 'after-edge', 'text-anchor' => 'middle'));
		}

		$pixels_per_increment = $this->computed['graph_area_width'] / $graph_data_count;
		$svg_poly = array();
		for($i = 0; $i < $graph_data_count; $i++)
		{
			$x = (($pixels_per_increment * $i) + $this->computed['graph_area_x_start']);
			$y = ($this->computed['graph_area_y_start'] - ($graph_data[$i] / $vals_per_pixel));
			array_push($svg_poly, $x . ',' . $y);
		}
		if($x < ($this->computed['graph_area_x_end'] - 1))
		{
			array_push($svg_poly, ($this->computed['graph_area_x_end'] - 1) . ',' . $y);
		}
		$svg_poly = implode(' ', $svg_poly);
		$svg_dom->add_element('polyline', array('points' => $svg_poly, 'fill' => 'none', 'stroke' => $this->attr['paint_color'], 'stroke-width' => ($graph_data_count < ($this->computed['graph_area_width'] / 2)) ? 2 : 1));

		$title_extra = null;
		if(max($graph_data) < 1000)
		{
			$title_extra .= ' (Min: ' . min($graph_data) . ' / Avg: ' . round(array_sum($graph_data) / count($graph_data)) . ' / Max: ' . max($graph_data) . ' / Last: ' . end($graph_data) . ')';
		}

		$this->svg_dom->add_text_element($this->data['title'] . $title_extra, array('x' => $this->computed['graph_area_x_start'], 'y' => $this->computed['graph_area_y_end'] - 5, 'font-size' => $this->attr['text_size'], 'fill' => $this->attr['text_color'], 'text-anchor' => 'start', 'alignment-baseline' => 'above-edge', 'font-weight' => 'bold'));

		return $svg_dom;
	}
}
?>
