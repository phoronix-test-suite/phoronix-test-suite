<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2016, Phoronix Media
	Copyright (C) 2009 - 2016, Michael Larabel
	pts_ImageComparisonGraph.php: A graph object for image comparisons

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

class pts_graph_iqc extends pts_graph_core
{
	protected $img_width = 0;
	protected $img_height = 0;
	public function __construct(&$result_object, &$result_file = null, $extra_attributes = null)
	{
		parent::__construct($result_object, $result_file, $extra_attributes);
		$this->i['graph_value_type'] = 'ABSTRACT';
		$this->i['hide_graph_identifiers'] = true;
	}
	protected function render_graph_pre_init()
	{
		if(!function_exists('imagecreatefromstring'))
		{
			echo PHP_EOL . 'Currently you must have PHP-GD installed to utilize this feature.' . PHP_EOL;
			return false;
		}

		// Do some common work to this object
		$draw_count = count($this->test_result->test_result_buffer->buffer_items);
		$img_first = imagecreatefromstring(base64_decode($this->test_result->test_result_buffer->buffer_items[0]->get_result_value()));
		$this->img_width = imagesx($img_first);
		$this->img_height = imagesy($img_first);

		// Assume if the images are being rendered together they are same width and height
		$this->i['graph_height'] = 72 + ($draw_count * ($this->img_height + 22)); // 110 at top plus 20 px between images
		$this->i['graph_width'] = $this->i['graph_width'] < ($this->img_width + 20) ? $this->img_width + 20 : $this->i['graph_width'];

		$this->update_graph_dimensions($this->i['graph_width'], $this->i['graph_height']);
	}
	public function renderGraph()
	{
		if(!function_exists('imagecreatefromstring'))
		{
			echo PHP_EOL . 'Currently you must have PHP-GD installed to utilize this feature.' . PHP_EOL;
			return false;
		}

		$this->render_graph_pre_init();
		$this->render_graph_init();
		$this->render_graph_heading(false);
		$draw_count = count($this->test_result->test_result_buffer->buffer_items);

		for($i_o = 0; $i_o < $draw_count; $i_o++)
		{
			$from_left = ($this->i['graph_width'] / 2) - ($this->img_width / 2);
			$from_top = 60 + ($i_o * ($this->img_height + 22));

			$this->svg_dom->add_element('rect', array('x' => ($from_left - 1), 'y' => ($from_top - 1), 'width' => ($this->img_width + 2), 'height' => ($this->img_height + 2), 'fill' => self::$c['color']['body_light']));
			$this->svg_dom->add_element('image', array('xlink:href' => 'data:image/png;base64,' . $this->test_result->test_result_buffer->buffer_items[$i_o]->get_result_value(), 'x' => $from_left, 'y' => $from_top, 'width' => $this->img_width, 'height' => $this->img_height));
			$this->svg_dom->add_text_element($this->graph_identifiers[$i_o], array('x' => round($this->i['graph_width'] / 2), 'y' => ($from_top + $this->img_height + 3), 'font-size' => self::$c['size']['bars'], 'fill' => self::$c['color']['main_headers'], 'text-anchor' => 'middle', 'dominant-baseline' => 'text-before-edge'));
		}

		if(!empty(self::$c['text']['watermark']))
		{
			$this->svg_dom->add_text_element(self::$c['text']['watermark'], array('x' => (round($this->i['graph_width']) / 2), 'y' => ($this->i['graph_height'] - 15), 'font-size' => 10, 'fill' => self::$c['color']['text'], 'text-anchor' => 'middle', 'dominant-baseline' => 'text-before-edge'));
		}
	}
}

?>
