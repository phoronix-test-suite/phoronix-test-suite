<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2012, Phoronix Media
	Copyright (C) 2012, Michael Larabel
	pts_BlockDiagramGraph.php: Create block diagrams

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

class pts_BlockDiagramGraph extends pts_Graph
{
	public function __construct(&$result_file)
	{
		$result_object = null;
		parent::__construct($result_object, $result_file);

		$this->i['graph_width'] = 1000;
		$this->i['graph_height'] = 600;


		return true;
	}
	protected function render_graph_heading($with_version = true)
	{
		return;
	}
	public function renderGraph()
	{
		$this->i['top_heading_height'] = max(self::$c['size']['headers'] + 22 + self::$c['size']['key'], 48);
		$this->i['top_start'] = $this->i['top_heading_height'] + 50;
		$this->update_graph_dimensions($this->i['graph_width'], $this->i['graph_height'] + $this->i['top_start'], true);

		// Do the actual work
		$this->render_graph_init();
		$this->graph_key_height();
		$this->render_graph_key();
		$this->render_graph_heading();

		$data = array(1, 2, 3, 6, 7, 8, 9, 10);

		$center_block_x = round($this->i['graph_width'] / 2);
		$center_block_y = round($this->i['graph_height'] / 2);

		for($ring = 0, $blocks_per_ring = (count($data) <= 5 ? 5 : 4), $ring_size = ceil(count($data) / $blocks_per_ring); $ring < $ring_size; $ring++)
		{
			$depth = ($ring + 1) * (min($center_block_x, $center_block_y) / ($ring_size + 0.25));

			for($i = ($ring * $blocks_per_ring), $i_size = $i + $blocks_per_ring; $i < $i_size; $i++)
			{
				$this_degree = (360 / $blocks_per_ring) * (($i % $blocks_per_ring) + ($ring / $ring_size));
				$this_block_x = round($center_block_x + (cos(deg2rad($this_degree)) * $depth));
				$this_block_y = round($center_block_y - (sin(deg2rad($this_degree)) * $depth));

				$this->svg_dom->draw_svg_line($center_block_x, $center_block_y, $this_block_x, $this_block_y, self::$c['color']['notches'], 2);

				$this->svg_dom->add_element('rect', array('x' => ($this_block_x - 20), 'y' => ($this_block_y - 20), 'width' => 40, 'height' => 40, 'fill' => self::$c['color']['alert']));
			}
		}

		$this->svg_dom->add_element('rect', array('x' => ($center_block_x - 50), 'y' => ($center_block_y - 50), 'width' => 100, 'height' => 100, 'fill' => self::$c['color']['alert']));



	}
}

?>
