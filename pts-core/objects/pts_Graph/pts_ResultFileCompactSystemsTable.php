<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2011 - 2021, Phoronix Media
	Copyright (C) 2011 - 2021, Michael Larabel
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

class pts_ResultFileCompactSystemsTable extends pts_graph_core
{
	protected $components;
	protected $intent;

	public function __construct(&$result_file, $intent = false)
	{
		parent::__construct();

		$this->intent = is_array($intent) ? $intent : array(array(), array());
		$this->i['graph_title'] = $result_file->get_title();

		$hw = array();
		$sw = array();
		foreach($result_file->get_systems() as $system)
		{
			$hw[] = $system->get_hardware();
			$sw[] = $system->get_software();
		}
		$hw = pts_result_file_analyzer::system_component_string_to_array(array_shift($hw));
		$sw = pts_result_file_analyzer::system_component_string_to_array(array_shift($sw));
		$this->components = array_merge($hw, $sw);

		pts_Table::report_system_notes_to_table($result_file, $this);
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
			$this->text_string_width($this->i['graph_title'], self::$c['size']['headers']) - (isset($this->i['graph_title'][30]) ? 20 : 0),
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
					$dupes[] = $x;
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
		//$this->svg_dom->add_element('rect', array('x' => 0, 'y' => 0, 'width' => $this->i['graph_width'], 'height' => $this->i['graph_height'] - 2, 'fill' => self::$c['color']['background'], 'stroke' => self::$c['color']['body_light'], 'stroke-width' => 2));
		//$this->svg_dom->add_element('rect', array('x' => 2, 'y' => 1, 'width' => ($this->i['graph_width'] - 3), 'height' => ($this->i['top_heading_height'] - 1), 'fill' => self::$c['color']['main_headers'], 'stroke' => self::$c['color']['border'], 'stroke-width' => 1));
		$this->svg_dom->add_text_element($this->i['graph_title'], array('x' => ($this->i['graph_width'] / 2), 'y' => (2 + self::$c['size']['headers']), 'font-size' => self::$c['size']['headers'], 'fill' => self::$c['color']['main_headers'], 'text-anchor' => 'middle', 'font-weight' => 'bold'));

		$this->svg_dom->add_text_element(self::$c['text']['watermark'], array('x' => 4, 'y' => ($this->i['top_heading_height'] - 3), 'font-size' => 8, 'fill' => self::$c['color']['main_headers'], 'text-anchor' => 'start', 'xlink:href' => self::$c['text']['watermark_url'], 'font-weight' => 'bold'));
		$this->svg_dom->add_text_element($this->i['graph_version'], array('x' => ($this->i['graph_width'] - 4), 'y' => ($this->i['top_heading_height'] - 3), 'font-size' => 8, 'fill' => self::$c['color']['main_headers'], 'text-anchor' => 'end', 'font-weight' => 'bold'));

		// Body
		$offset = $this->i['top_heading_height'];
		$dash = false;
		$g1 = $this->svg_dom->make_g(array('fill' => self::$c['color']['body_light']));
		$g2 = $this->svg_dom->make_g(array('fill' => 'none', 'stroke-width' => 1, 'stroke' => self::$c['color']['highlight']));
		$g3 = $this->svg_dom->make_g(array('font-size' => $this->i['identifier_size'], 'fill' => self::$c['color']['text'], 'text-anchor' => 'middle', 'font-weight' => 'bold'));
		$g4 = $this->svg_dom->make_g(array('font-size' => 7, 'fill' => self::$c['color']['text'], 'text-anchor' => 'end'));
		$g_line = $this->svg_dom->make_g(array('stroke' => self::$c['color']['notches'], 'stroke-width' => 1));

		foreach($this->components as $type => $component)
		{
			if(is_array($this->intent[0]) && ($key = array_search($type, $this->intent[0])) !== false)
			{
				$component = array();
				foreach($this->intent[1] as $s)
				{
					if(isset($s[$key]))
					{
						$component[] = $s[$key];
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
				$this->svg_dom->add_element('rect', array('x' => 0, 'y' => $offset, 'width' => $this->i['graph_width'], 'height' => ($next_offset - $offset)), $g1);
			}

			$this->svg_dom->add_element('line', array('x1' => 0, 'y1' => $offset, 'x2' => $this->i['graph_width'], 'y2' => $offset), $g_line);


			if(isset($component[1]))
			{
				$this->svg_dom->add_element('rect', array('x' => 1, 'y' => ($offset + 1), 'width' => ($this->i['graph_width'] - 2), 'height' => ($next_offset - $offset - 1)), $g2);
			}

			$text = $type . (isset($component[1]) && substr($type, -1) != 'y' && substr($type, -1) != 's' ? 's' : null);
			$this->svg_dom->add_text_element($text, array('x' => ($this->i['graph_width'] - 4), 'y' => ($offset + 9)), $g4);
			$offset += 2;

			foreach($component as $c)
			{
				$c = pts_result_file_analyzer::system_value_to_ir_value($c, $type);
				$this->svg_dom->add_text_element($c, array('x' => ($this->i['graph_width'] / 2), 'y' => ($offset + $component_header_height - 5), 'xlink:href' => $c->get_attribute('href')), $g3);
				$offset += $component_header_height;
			}

			$offset = $next_offset;
			$dash = !$dash;
		}


		// Footer
		$this->svg_dom->add_element('rect', array('x' => 1, 'y' => ($this->i['graph_height'] - $bottom_footer), 'width' => ($this->i['graph_width'] - 2), 'height' => $bottom_footer, 'fill' => self::$c['color']['background']));

		if(defined('OPENBENCHMARKING_IDS'))
		{
			$back_width = $this->i['graph_width'] - 4;
			$g_ob = $this->svg_dom->make_g(array('text-anchor' => 'end', 'fill' => self::$c['color']['main_headers'], 'font-size' => 8));
			$this->svg_dom->add_text_element(OPENBENCHMARKING_TITLE, array('x' => $back_width, 'y' => ($this->i['graph_height'] - $bottom_footer + 12), 'font-weight' => 'bold', 'xlink:href' => 'https://openbenchmarking.org/result/' . OPENBENCHMARKING_IDS), $g_ob);
			$this->svg_dom->add_text_element('System Logs', array('x' => $back_width, 'y' => ($this->i['graph_height'] - 20), 'xlink:href' => 'https://openbenchmarking.org/system/' . OPENBENCHMARKING_IDS), $g_ob);
			//$this->svg_dom->add_text_element('OPC Classification', array('x' => $back_width, 'y' => ($this->i['graph_height'] - 6), 'xlink:href' => 'https://openbenchmarking.org/opc/' . OPENBENCHMARKING_IDS), $g_ob);
		}

		if(!empty($this->i['notes']))
		{
			$estimated_height = 0;
			foreach($this->i['notes'] as $i => $note_r)
			{
				$this->svg_dom->add_textarea_element('- ' . $note_r['note'], array('x' => 4, 'y' => ($this->i['graph_height'] - $bottom_footer + $estimated_height + 21), 'font-size' => self::$c['size']['key'], 'fill' => self::$c['color']['main_headers'], 'text-anchor' => 'start'), $estimated_height);
			}
		}
	}
	public function render_graph_finish()
	{

	}
}

?>
