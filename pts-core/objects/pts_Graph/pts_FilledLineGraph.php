<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2011 - 2012, Phoronix Media
	Copyright (C) 2011 - 2012, Michael Larabel
	pts_FilledLineGraph.php: The filled line graph object

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

class pts_FilledLineGraph extends pts_LineGraph
{
	protected function renderGraphLines()
	{
		$identifiers_empty = count($this->graph_identifiers) == 0;
		$point_count = count($this->graph_data[0]);
		$varying_lengths = false;
		$prev_values = array();
		$prev_poly_points = array();

		foreach($this->graph_data as &$graph_r)
		{
			if(count($graph_r) != $point_count)
			{
				$varying_lengths = true;
				break;
			}
		}

		foreach(array_keys($this->graph_data) as $z => $i_o)
		{
			$paint_color = $this->get_paint_color((isset($this->graph_data_title[$i_o]) ? $this->graph_data_title[$i_o] : null));

			$point_counter = count($this->graph_data[$i_o]);
			$regression_plots = array();
			$poly_points = array();

			for($i = 0; $i < $point_counter; $i++)
			{
				$value = $this->graph_data[$i_o][$i];

				if(isset($prev_values[$i]))
				{
					$value += $prev_values[$i];
				}

				$prev_values[$i] = $value;

				$identifier = isset($this->graph_identifiers[$i]) ? $this->graph_identifiers[$i] : null;
				$data_string = isset($this->graph_data_title[$i_o]) ? $this->graph_data_title[$i_o] . ($identifier ? ' @ ' . $identifier : null) . ': ' . $value : null;

				$value_plot_top = $this->i['graph_top_end'] + 1 - ($this->i['graph_max_value'] == 0 ? 0 : round(($value / $this->i['graph_max_value']) * ($this->i['graph_top_end'] - $this->i['top_start'])));
				$px_from_left = round($this->i['left_start'] + ($this->i['identifier_width'] * ($i + 1)));

/*
				if(($i == ($point_counter - 1)) && $value == 0)
				{
					break;
				}
*/

				if($px_from_left > $this->i['graph_left_end'])
				{
					//$px_from_left = $this->i['graph_left_end'] - 1;
					break;
				}

				if($value_plot_top >= $this->i['graph_top_end'])
				{
					$value_plot_top = $this->i['graph_top_end'] - 1;
				}


				if($identifiers_empty && $i == 0)
				{
					array_push($poly_points, array($this->i['left_start'] + 1, ($this->i['graph_top_end'] + 1)));
					array_push($poly_points, array($this->i['left_start'] + 1, $value_plot_top, $data_string));
				}
				else if($identifiers_empty && $i == ($point_counter - 1))
				{
					array_push($poly_points, array($px_from_left, $value_plot_top, $data_string));
					if($varying_lengths && ($point_counter * 1.1) < $point_count)
					{
						// This plotting ended prematurely
						array_push($poly_points, array($px_from_left, $this->i['graph_top_end'] - 1, null));
					}
					else if($value > 0)
					{
						array_push($poly_points, array($this->i['graph_left_end'] - 1, $value_plot_top, null));
					}
				}
				else
				{
					if($i == 0)
					{
						array_push($poly_points, array($px_from_left, ($this->i['graph_top_end'] + 1)));
					}

					array_push($poly_points, array($px_from_left, $value_plot_top, $data_string));
				}

				//array_push($poly_tips, array($value, $this->graph_identifiers[$i]));
			}

			switch($z)
			{
				case 0:
					array_push($poly_points, array($px_from_left, ($this->i['graph_top_end'] + 1)));
					array_push($poly_points, $poly_points[0]);
					break;
				case 1:
					$prev_poly_points = array_slice($prev_poly_points, 0, -2);
				default:
					array_shift($poly_points);
					array_unshift($poly_points, $prev_poly_points[1]);

					foreach(array_reverse($prev_poly_points) as $p)
					{
						array_push($poly_points, $p);
					}
					break;
			}

			$svg_poly = array();
			foreach($poly_points as $point_pair)
			{
				array_push($svg_poly, implode(',', $point_pair));
			}
			$this->svg_dom->add_element('polygon', array('points' => implode(' ', $svg_poly), 'fill' => $paint_color, 'stroke' => self::$c['color']['main_headers'], 'stroke-width' => 1));
			$prev_poly_points = array_merge($poly_points, $prev_poly_points);
		}
	}
	protected function maximum_graph_value()
	{
		$values = array();

		foreach(array_keys($this->graph_data) as $z => $i_o)
		{
			for($i = 0; $i < count($this->graph_data[$i_o]); $i++)
			{
				if(!isset($values[$i]))
				{
					$values[$i] = 0;
				}

				$values[$i] += $this->graph_data[$i_o][$i];
			}
		}

		$maximum = max($values);
		$maximum = (floor(round($maximum * 1.285) / $this->i['mark_count']) + 1) * $this->i['mark_count'];
		$maximum = round(ceil($maximum / $this->i['mark_count']), (0 - strlen($maximum) + 2)) * $this->i['mark_count'];

		return $maximum;
	}
}

?>
