<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2011, Phoronix Media
	Copyright (C) 2011, Michael Larabel
	pts_Table.php: A charting table object for pts_Graph

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

class pts_ResultFileCompactSystemsTable extends pts_Graph
{
	protected $components;
	protected $intent;

	public function __construct(&$result_file, $intent = false)
	{
		parent::__construct();

		$this->intent = is_array($intent) ? $intent : array(array(), array());
		$this->graph_title = $result_file->get_title();

		$hw = $result_file->get_system_hardware();
		$sw = $result_file->get_system_software();
		$hw = pts_result_file_analyzer::system_component_string_to_array(array_shift($hw));
		$sw = pts_result_file_analyzer::system_component_string_to_array(array_shift($sw));
		$this->components = array_merge($hw, $sw);
	}
	public function renderChart($file = null)
	{
		$this->saveGraphToFile($file);
		$this->render_graph_start();
		return $this->render_graph_finish();
	}
	public function render_graph_start()
	{
		$this->graph_top_heading_height = 22 + $this->c['size']['headers'];

		$longest_component = pts_strings::find_longest_string($this->components);
		$component_header_height = $this->text_string_height($longest_component, ($this->c['size']['identifiers'] + 3)) + 4;

		$this->c['graph']['width'] = 10 + max(
			$this->text_string_width($this->graph_title, $this->c['size']['headers']) - (isset($this->graph_title[30]) ? 20 : 0),
			$this->text_string_width($longest_component, ($this->c['size']['identifiers'] + (isset($longest_component[29]) ? 1.8 : 2)))
			);

		$intent_count = 0;
		$dupes = array();
		if($this->intent[1] && is_array($this->intent[1]))
		{
			foreach($this->intent[1] as $x)
			{
				if(!in_array($x, $dupes))
				{
					$intent_count += count($x);
					array_push($dupes, $x);
				}
			}

			$intent_count -= count($this->intent[0]);
		}
		unset($dupes);

		$bottom_footer = 50; // needs to be at least 86 to make room for PTS logo
		$this->c['graph']['height'] =
			$this->graph_top_heading_height +
			((count($this->components) + $intent_count) * $component_header_height) +
			$bottom_footer
			;

		// Do the actual work
		$this->requestRenderer('SVG');
		$this->render_graph_pre_init();
		$this->render_graph_init(array('cache_font_size' => true));

		// Header
		$this->svg_dom->add_element('rect', array('x' => 2, 'y' => 1, 'width' => ($this->c['graph']['width'] - 3), 'height' => ($this->graph_top_heading_height - 1), 'fill' => $this->c['color']['main_headers'], 'stroke' => $this->c['color']['border'], 'stroke-width' => 1));
		$this->svg_dom->add_text_element($this->graph_title, array('x' => ($this->c['graph']['width']), 'y' => 2, 'font-size' => $this->c['size']['headers'], 'fill' => $this->c['color']['background'], 'text-anchor' => 'middle', 'dominant-baseline' => 'text-before-edge'));

		$this->svg_dom->add_text_element($this->c['text']['watermark'], array('x' => 4, 'y' => ($this->graph_top_heading_height - 6), 'font-size' => 8, 'fill' => $this->c['color']['background'], 'text-anchor' => 'start', 'dominant-baseline' => 'middle', 'xlink:show' => 'new', 'xlink:href' => $this->c['text']['watermark_url']));
		$this->svg_dom->add_text_element($this->c['text']['graph_version'], array('x' => ($this->c['graph']['width'] - 4), 'y' => ($this->graph_top_heading_height - 6), 'font-size' => 8, 'fill' => $this->c['color']['background'], 'text-anchor' => 'end', 'dominant-baseline' => 'middle', 'xlink:show' => 'new', 'xlink:href' => 'http://www.phoronix-test-suite.com/'));

		// Body
		$offset = $this->graph_top_heading_height;
		$dash = false;

		foreach($this->components as $type => $component)
		{
			if(is_array($this->intent[0]) && ($key = array_search($type, $this->intent[0])) !== false)
			{
				$component = array();
				foreach($this->intent[1] as $s)
				{
					if(isset($s[$key]))
					{
						array_push($component, $s[$key]);
					}
				}

				// Eliminate duplicates from printing
				$component = array_unique($component);
				$next_offset = $offset + ($component_header_height * count($component));
			}
			else
			{
				$next_offset = $offset + $component_header_height;
				$component = array($component);
			}

			if($dash)
			{
				$this->svg_dom->add_element('rect', array('x' => 0, 'y' => $offset, 'width' => $this->c['graph']['width'], 'height' => $component_header_height, 'fill' => $this->c['color']['body_light']));
			}

			$this->svg_dom->draw_svg_line(0, $offset, $this->c['graph']['width'], $offset, $this->c['color']['notches'], 1);

			if(isset($component[1]))
			{
				$this->svg_dom->add_element('rect', array('x' => 0, 'y' => ($offset + 1), 'width' => $this->c['graph']['width'], 'height' => ($component_header_height - 1), 'fill' => $this->c['color']['highlight']));
			}

			$text = $type . (isset($component[1]) && substr($type, -1) != 'y' ? 's' : null);
			$this->svg_dom->add_text_element($text, array('x' => ($this->c['graph']['width'] - 4), 'y' => ($offset + 7), 'font-size' => 7, 'fill' => $this->c['color']['text'], 'text-anchor' => 'end', 'dominant-baseline' => 'middle'));
			$offset += 2;

			foreach($component as $c)
			{
				$c = pts_result_file_analyzer::system_value_to_ir_value($c, $type);
				$this->svg_dom->add_text_element($c, array('x' => ($this->c['graph']['width'] / 2), 'y' => $offset, 'font-size' => $this->c['size']['identifiers'], 'fill' => $this->c['color']['text'], 'text-anchor' => 'middle', 'dominant-baseline' => 'text-before-edge', 'xlink:title' => $type . ': ' . $c, 'font-weight' => 'bold'));
				$offset += $component_header_height;
			}

			$offset = $next_offset;
			$dash = !$dash;
		}


		// Footer
		$this->svg_dom->add_element('rect', array('x' => 1, 'y' => ($this->c['graph']['height'] - $bottom_footer), 'width' => ($this->c['graph']['width'] - 2), 'height' => $bottom_footer, 'fill' => $this->c['color']['main_headers']));
		$this->svg_dom->add_element('image', array('xlink:href' => 'http://www.phoronix-test-suite.com/external/pts-logo-80x42-white.png', 'x' => 10, 'y' => ($this->c['graph']['height'] - 48), 'width' => 80, 'height' => 42));

		if(defined('OPENBENCHMARKING_IDS') && $this->getRenderer() == 'SVG')
		{
			$back_width = $this->c['graph']['width'] - 4;
			$this->svg_dom->add_text_element(OPENBENCHMARKING_TITLE, array('x' => $back_width, 'y' => ($this->c['graph']['height'] - 38), 'font-size' => 8, 'fill' => $this->c['color']['background'], 'text-anchor' => 'end', 'dominant-baseline' => 'middle', 'font-weight' => 'bold', 'xlink:show' => 'new', 'xlink:href' => 'http://openbenchmarking.org/result/' . OPENBENCHMARKING_IDS));
			$this->svg_dom->add_text_element('System Logs', array('x' => $back_width, 'y' => ($this->c['graph']['height'] - 24), 'font-size' => 8, 'fill' => $this->c['color']['background'], 'text-anchor' => 'end', 'dominant-baseline' => 'middle', 'xlink:show' => 'new', 'xlink:href' => 'http://openbenchmarking.org/system/' . OPENBENCHMARKING_IDS));
			$this->svg_dom->add_text_element('OPC Classification', array('x' => $back_width, 'y' => ($this->c['graph']['height'] - 10), 'font-size' => 8, 'fill' => $this->c['color']['background'], 'text-anchor' => 'end', 'dominant-baseline' => 'middle', 'xlink:show' => 'new', 'xlink:href' => 'http://openbenchmarking.org/opc/' . OPENBENCHMARKING_IDS));
		}
	}
	public function render_graph_finish()
	{
		return $this->return_graph_image();
	}
}

?>
