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
	protected $result_file;
	protected $intent;

	public function __construct(&$result_file, $intent = false)
	{
		parent::__construct();
		$this->result_file = $result_file;
		$this->intent = is_array($intent) ? $intent : array(array(), array());

		$this->graph_title = $result_file->get_title();
	}
	public function renderChart($file = null)
	{
		$this->saveGraphToFile($file);
		$this->render_graph_start();
		return $this->render_graph_finish();
	}
	public function render_graph_start()
	{
		$this->graph_top_heading_height = 8 + $this->graph_font_size_heading;

		$hw = $this->result_file->get_system_hardware();
		$sw = $this->result_file->get_system_software();
		$hw = pts_result_file_analyzer::system_component_string_to_array(array_shift($hw));
		$sw = pts_result_file_analyzer::system_component_string_to_array(array_shift($sw));
		$components = array_merge($hw, $sw);
		$longest_component = $this->find_longest_string($components);
		$component_header_height = $this->text_string_height($longest_component, $this->graph_font, ($this->graph_font_size_identifiers + 1.5));

		$this->graph_attr_width = 10 + max(
			$this->text_string_width($this->graph_title, $this->graph_font, $this->graph_font_size_heading),
			$this->text_string_width($longest_component, $this->graph_font, ($this->graph_font_size_identifiers + 2))
			);

		$bottom_footer = 50; // needs to be at least 86 to make room for PTS logo
		$this->graph_attr_height =
			$this->graph_top_heading_height +
			((count($components) + ($this->intent[1] ? (count($this->intent[0]) * count($this->intent[1])) - 1 : 0)) * $component_header_height) +
			$bottom_footer
			;

		// Do the actual work
		$this->requestRenderer('SVG');
		$this->render_graph_pre_init();
		$this->render_graph_init(array('cache_font_size' => true));

		// Header
		$this->graph_image->draw_rectangle(2, 1, $this->graph_attr_width - 1, $this->graph_top_heading_height, $this->graph_color_main_headers);
		$this->graph_image->write_text_center($this->graph_title, $this->graph_font, $this->graph_font_size_heading, $this->graph_color_background, 0, 2, $this->graph_attr_width, 2);

		// Body
		$offset = $this->graph_top_heading_height;
		$dash = false;
		foreach($components as $type => $component)
		{
			if(($key = array_search($type, $this->intent[0])) !== false)
			{
				$component = array();
				foreach($this->intent[1] as $s)
				{
					array_push($component, $s[$key]);
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
				$this->graph_image->draw_rectangle(0, $offset, $this->graph_attr_width, $next_offset, $this->graph_color_body_light);
			}

			$this->graph_image->draw_line(0, $offset, $this->graph_attr_width, $offset, $this->graph_color_notches, 1);

			if(isset($component[1]))
			{
				$this->graph_image->draw_rectangle_border(0, $offset, $this->graph_attr_width, $next_offset, $this->graph_color_highlight);
			}

			$this->graph_image->write_text_right($type . (isset($component[1]) ? 's' : null), $this->graph_font, 7, $this->graph_color_text, 0, $offset + 6, $this->graph_attr_width - 4, $offset + 6);
			$offset += 2;

			foreach($component as $c)
			{
				$c = pts_result_file_analyzer::system_value_to_ir_value($c, $type);
				$c->set_attribute('title', $type . ': ' . $c);
				$c->set_attribute('font-weight', 'bold');
				$this->graph_image->write_text_center($c, $this->graph_font, $this->graph_font_size_identifiers, $this->graph_color_text, 0, $offset, $this->graph_attr_width, $offset);
				$offset += $component_header_height;
			}

			$offset = $next_offset;
			$dash = !$dash;
		}


		// Footer
		$this->graph_image->draw_rectangle(1, ($this->graph_attr_height - $bottom_footer), $this->graph_attr_width - 1, $this->graph_attr_height, $this->graph_color_main_headers);
		$this->graph_image->image_copy_merge(new pts_graph_ir_value($this->graph_image->png_image_to_type('http://www.phoronix-test-suite.com/external/pts-logo-80x42-white.png'), array('href' => 'http://www.phoronix-test-suite.com/')), 10, ($this->graph_attr_height - 48), 0, 0, 80, 42);

		$this->graph_image->write_text_right(new pts_graph_ir_value($this->graph_watermark_text, array('href' => $this->graph_watermark_url)), $this->graph_font, 8, $this->graph_color_background, ($this->graph_attr_width - 6), ($this->graph_attr_height - 24), ($this->graph_attr_width - 6), ($this->graph_attr_height - 24));
		$this->graph_image->write_text_right(new pts_graph_ir_value($this->graph_version, array('href' => 'http://www.phoronix-test-suite.com/')), $this->graph_font, 8, $this->graph_color_background, ($this->graph_attr_width - 6), ($this->graph_attr_height - 10), ($this->graph_attr_width - 6), ($this->graph_attr_height - 10));
	}
	public function render_graph_finish()
	{
		$this->graph_image->draw_rectangle_border(1, 1, $this->graph_attr_width, $this->graph_attr_height, $this->graph_color_border);
		return $this->return_graph_image();
	}
}

?>
