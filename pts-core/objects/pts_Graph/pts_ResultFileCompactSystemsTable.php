<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2011 - 2012, Phoronix Media
	Copyright (C) 2011 - 2012, Michael Larabel
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

		pts_render::report_system_notes_to_table($result_file, $this);
	}
	public function renderChart($file = null)
	{
		$this->render_graph_start();
		$this->render_graph_finish();
		return $this->svg_dom->output($file);
	}
	public function render_graph_start()
	{
		$this->i['top_heading_height'] = 22 + self::$c['size']['headers'];

		$longest_component = pts_strings::find_longest_string($this->components);
		$component_header_height = $this->text_string_height($longest_component, ($this->i['identifier_size'] + 3)) + 6;

		$this->i['graph_width'] = 10 + max(
			$this->text_string_width($this->graph_title, self::$c['size']['headers']) - (isset($this->graph_title[30]) ? 20 : 0),
			$this->text_string_width($longest_component, ($this->i['identifier_size'] + (isset($longest_component[29]) ? 1.8 : 2)))
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

		$bottom_footer = 50 + $this->note_display_height(); // needs to be at least 86 to make room for PTS logo

		$this->i['graph_height'] =
			$this->i['top_heading_height'] +
			((count($this->components) + $intent_count) * $component_header_height) +
			$bottom_footer
			;

		// Do the actual work
		$this->render_graph_pre_init();
		$this->render_graph_init();

		// Header
		$this->svg_dom->add_element('rect', array('x' => 2, 'y' => 1, 'width' => ($this->i['graph_width'] - 3), 'height' => ($this->i['top_heading_height'] - 1), 'fill' => self::$c['color']['main_headers'], 'stroke' => self::$c['color']['border'], 'stroke-width' => 1));
		$this->svg_dom->add_element_with_value('text', $this->graph_title, array('x' => ($this->i['graph_width'] / 2), 'y' => (2 + self::$c['size']['headers']), 'font-size' => self::$c['size']['headers'], 'fill' => self::$c['color']['background'], 'text-anchor' => 'middle'));

		$this->svg_dom->add_anchored_element_with_value(self::$c['text']['watermark_url'], 'text', self::$c['text']['watermark'],
														array('x' => 4, 'y' => ($this->i['top_heading_height'] - 3),
															  'font-size' => 8,
															  'fill' => self::$c['color']['background'], 'text-anchor' => 'start'));
		$this->svg_dom->add_anchored_element_with_value('http://www.phoronix-test-suite.com/', 'text', $this->i['graph_version'],
														array('x' => ($this->i['graph_width'] - 4),
															  'y' => ($this->i['top_heading_height'] - 3),
															  'font-size' => 8, 'fill' => self::$c['color']['background'], 'text-anchor' => 'end'));

		// Body
		$offset = $this->i['top_heading_height'];
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
				$this->svg_dom->add_element('rect', array('x' => 0, 'y' => $offset, 'width' => $this->i['graph_width'], 'height' => ($next_offset - $offset), 'fill' => self::$c['color']['body_light']));
			}

			$this->svg_dom->draw_svg_line(0, $offset, $this->i['graph_width'], $offset, self::$c['color']['notches'], 1);

			if(isset($component[1]))
			{
				$this->svg_dom->add_element('rect', array('x' => 1, 'y' => ($offset + 1), 'width' => ($this->i['graph_width'] - 2), 'height' => ($next_offset - $offset - 1), 'fill' => 'none', 'stroke-width' => 1, 'stroke' => self::$c['color']['highlight']));
			}

			$text = $type . (isset($component[1]) && substr($type, -1) != 'y' ? 's' : null);
			$this->svg_dom->add_element_with_value('text', $text, array('x' => ($this->i['graph_width'] - 4), 'y' => ($offset + 9), 'font-size' => 7, 'fill' => self::$c['color']['text'], 'text-anchor' => 'end'));
			$offset += 2;

			foreach($component as $c)
			{
				$c = pts_result_file_analyzer::system_value_to_ir_value($c, $type);
				$this->svg_dom->add_anchored_element_with_value($c->get_attribute('href'), 'text', $c,
																array('x' => ($this->i['graph_width'] / 2),
																	   'y' => ($offset + $component_header_height - 5),
																	  'font-size' => $this->i['identifier_size'],
																	  'fill' => self::$c['color']['text'],
																	  'text-anchor' => 'middle'));
				$offset += $component_header_height;
			}

			$offset = $next_offset;
			$dash = !$dash;
		}


		// Footer
		$this->svg_dom->add_element('rect', array('x' => 1, 'y' => ($this->i['graph_height'] - $bottom_footer), 'width' => ($this->i['graph_width'] - 2), 'height' => $bottom_footer, 'fill' => self::$c['color']['main_headers']));
		$encoded_image = pts_svg_dom::embed_png_image(PTS_CORE_STATIC_PATH . 'images/pts-80x42-white.png');
		$this->svg_dom->add_anchored_element('http://www.phoronix-test-suite.com/', 'image',
											 array('xlink:href' => $encoded_image,
												   'x' => 10, 'y' => ($this->i['graph_height'] - 46),
												   'width' => 80, 'height' => 42));

		if(defined('OPENBENCHMARKING_IDS'))
		{
			$back_width = $this->i['graph_width'] - 4;
			$attributes = array('x' => $back_width, 'y' => $this->i['graph_height'] - 6, 'font-size' => 8,
								'fill' => self::$c['color']['background'], 'text-anchor' => 'end');

			$this->svg_dom->add_element('text', 'OPC Classification', $attributes);

			$attributes['y'] += -14;
			$this->svg_dom->add_anchored_element_with_value('http://openbenchmarking.org/system/' . OPENBENCHMARKING_IDS,
															'text', 'System Logs', $attributes);

			$attributes['y'] += 32 - $bottom_footer;
			$attributes['font-weight'] = 'bold';
			$this->svg_dom->add_anchored_element_with_value('http://openbenchmarking.org/result/' . OPENBENCHMARKING_IDS,
															'text', OPENBENCHMARKING_TITLE, $attributes);
		}

		if(!empty($this->i['notes']))
		{
			$estimated_height = 0;
			foreach($this->i['notes'] as $i => $note_r)
			{
				$this->svg_dom->add_textarea_element('- ' . $note_r['note'], array('x' => 4, 'y' => ($this->i['graph_height'] - $bottom_footer + $estimated_height + 21), 'font-size' => self::$c['size']['key'], 'fill' => self::$c['color']['background'], 'text-anchor' => 'start', 'xlink:title' => $note_r['hover-title']), $estimated_height);
			}
		}
	}
	public function render_graph_finish()
	{

	}
}

?>
