<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2013, Phoronix Media
	Copyright (C) 2008 - 2013, Michael Larabel
	pts_LineGraph.php: The line graph object that extends pts_Graph.php.

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

class pts_LineGraph extends pts_Graph
{
	public function __construct(&$result_object, &$result_file = null)
	{
		parent::__construct($result_object, $result_file);
		$this->i['show_graph_key'] = true;
		$this->i['show_background_lines'] = true;
		$this->i['iveland_view'] = true;
		$this->i['identifier_width'] = -1;
		$this->i['graph_max_value_multiplier'] = 1.38; // make room for the average/peak/low table embedded to the top
		$this->i['min_identifier_size'] = 6.5;
		$this->i['plot_overview_text'] = true;
		$this->i['display_select_identifiers'] = false;
	}
	protected function render_graph_pre_init()
	{
		// Do some common work to this object
		$graph_identifiers_count = count($this->graph_identifiers);

		if($graph_identifiers_count > 1)
		{
			$identifier_count = $graph_identifiers_count + 1;
		}
		else
		{
			$identifier_count = 0;

			foreach(array_keys($this->graph_data) as $i)
			{
				$identifier_count = max((count($this->graph_data[$i]) - 1), $identifier_count);
			}
		}

		$this->i['identifier_width'] = $identifier_count > 0 ? (($this->i['graph_left_end'] - $this->i['left_start']) / $identifier_count) : 1;

		$longest_string = pts_strings::find_longest_string($this->graph_identifiers);
		$this->i['identifier_size'] = $this->text_size_bounds($longest_string, $this->i['identifier_size'], $this->i['min_identifier_size'], $this->i['identifier_width'] - 4);

		if($this->i['identifier_size'] <= $this->i['min_identifier_size'])
		{
			list($text_width, $text_height) = pts_svg_dom::estimate_text_dimensions($longest_string, $this->i['min_identifier_size'] + 0.5);
			$this->i['bottom_offset'] += $text_width;
			$this->update_graph_dimensions($this->i['graph_width'], $this->i['graph_height'] + $text_width);

			if(($text_height + 4) > $this->i['identifier_width'] && $graph_identifiers_count > 3)
			{
				// Show the identifiers as frequently as they will fit
				$this->i['display_select_identifiers'] = ceil(($text_height + 4) / $this->i['identifier_width']);
			}
		}
	}
	protected function render_graph_identifiers()
	{
		if(!is_array($this->graph_identifiers))
		{
			return;
		}

		$px_from_top_end = $this->i['graph_top_end'] + 5;

		if($this->i['identifier_width'] > 2)
		{
			$this->svg_dom->draw_svg_line($this->i['left_start'] + $this->i['identifier_width'], $this->i['graph_top_end'], $this->i['graph_left_end'], $this->i['graph_top_end'], self::$c['color']['notches'], 10, array('stroke-dasharray' => '1,' . ($this->i['identifier_width'] - 1)));
		}
		else if($this->i['display_select_identifiers'])
		{
			$this->svg_dom->draw_svg_line($this->i['left_start'] + ($this->i['identifier_width'] * $this->i['display_select_identifiers']), $this->i['graph_top_end'], $this->i['graph_left_end'], $this->i['graph_top_end'], self::$c['color']['notches'], 10, array('stroke-dasharray' => '1,' . (($this->i['identifier_width'] * $this->i['display_select_identifiers']) - 1)));
		}

		foreach(array_keys($this->graph_identifiers) as $i)
		{
			if(is_array($this->graph_identifiers[$i]))
			{
				break;
			}

			if($this->i['display_select_identifiers'] && ($i % $this->i['display_select_identifiers']) != 0)
			{
				// $this->i['display_select_identifiers'] contains the value of how frequently to display identifiers
				continue;
			}

			$px_from_left = $this->i['left_start'] + ($this->i['identifier_width'] * ($i + (count($this->graph_identifiers) > 1 ? 1 : 0)));

			if($this->i['identifier_size'] <= $this->i['min_identifier_size'])
			{
				$this->svg_dom->add_text_element($this->graph_identifiers[$i], array('x' => $px_from_left, 'y' => ($px_from_top_end + 2), 'font-size' => 9, 'fill' => self::$c['color']['headers'], 'text-anchor' => 'start', 'transform' => 'rotate(90 ' . $px_from_left . ' ' . ($px_from_top_end + 2) . ')'));
			}
			else
			{
				$this->svg_dom->add_text_element($this->graph_identifiers[$i], array('x' => $px_from_left, 'y' => ($px_from_top_end + 10), 'font-size' => $this->i['identifier_size'], 'fill' => self::$c['color']['headers'], 'text-anchor' => 'middle'));
			}
		}
	}
	protected function get_special_paint_color($identifier)
	{
		// For now to try to improve the color handling of line graphs, first try to use a pre-defined pool of colors until falling back to the old color code once exhausted
		// Thanks to ua=42 in the Phoronix Forums for the latest attempt at improving the automated color handling
		static $line_color_cache = null;
		static $predef_line_colors = array('#000000', '#FFB300', '#803E75', '#FF6800', '#A6BDD7', '#C10020', '#CEA262', '#817066', '#007D34', '#F6768E', '#00538A', '#FF7A5C', '#53377A', '#FF8E00', '#B32851', '#F4C800', '#7F180D', '#93AA00', '#593315', '#F13A13', '#232C16');

		if(!isset($line_color_cache[$identifier]))
		{
			if(!empty($predef_line_colors))
			{
				$line_color_cache[$identifier] = array_shift($predef_line_colors);
			}
			else
			{
				$line_color_cache[$identifier] = $this->get_paint_color($identifier);
			}
		}

		return $line_color_cache[$identifier];
	}
	protected function renderGraphLines()
	{
		$calculations_r = array();
		$min_value = $this->graph_data[0][0];
		$max_value = $this->graph_data[0][0];
		$prev_value = $this->graph_data[0][0];

		foreach(array_keys($this->graph_data) as $i_o)
		{
			$paint_color = $this->get_special_paint_color((isset($this->graph_data_title[$i_o]) ? $this->graph_data_title[$i_o] : null));
			$calculations_r[$paint_color] = array();

			$point_counter = count($this->graph_data[$i_o]);
			$regression_plots = array();
			$poly_points = array();

			for($i = 0; $i < $point_counter; $i++)
			{
				$value = $this->graph_data[$i_o][$i];

				if($value < 0 || ($value == 0 && $this->graph_identifiers != null))
				{
					// Draw whatever is needed of the line so far, since there is no result here
					$this->draw_graph_line_process($poly_points, $paint_color, $regression_plots, $point_counter);
					continue;
				}

				$identifier = isset($this->graph_identifiers[$i]) ? $this->graph_identifiers[$i] : null;
				$std_error = isset($this->graph_data_raw[$i_o][$i]) ? pts_math::standard_error(pts_strings::colon_explode($this->graph_data_raw[$i_o][$i])) : 0;
				$data_string = isset($this->graph_data_title[$i_o]) ? $this->graph_data_title[$i_o] . ($identifier ? ' @ ' . $identifier : null) . ': ' . $value : null;

				$value_plot_top = $this->i['graph_top_end'] + 1 - ($this->i['graph_max_value'] == 0 ? 0 : round(($value / $this->i['graph_max_value']) * ($this->i['graph_top_end'] - $this->i['top_start'])));
				$px_from_left = round($this->i['left_start'] + ($this->i['identifier_width'] * ($i + (count($this->graph_identifiers) > 1 ? 1 : 0))));

				if($value > $max_value)
				{
					$max_value = $value;
				}
				else if($value < $min_value)
				{
					$min_value = $value;
				}

				if($px_from_left > $this->i['graph_left_end'])
				{
					//$px_from_left = $this->i['graph_left_end'] - 1;
					break;
				}

				if($value_plot_top >= $this->i['graph_top_end'])
				{
					$value_plot_top = $this->i['graph_top_end'] - 1;
				}

				array_push($poly_points, array($px_from_left, $value_plot_top, $data_string, $std_error));

				if(isset($this->d['regression_marker_threshold']) && $this->d['regression_marker_threshold'] > 0 && $i > 0 && abs(1 - ($value / $prev_value)) > $this->d['regression_marker_threshold'])
				{
					$regression_plots[($i - 1)] = $prev_identifier . ': ' . $prev_value;
					$regression_plots[$i] = $identifier . ': ' . $value;
				}

				array_push($calculations_r[$paint_color], $value);
				$prev_identifier = $identifier;
				$prev_value = $value;
			}

			$this->draw_graph_line_process($poly_points, $paint_color, $regression_plots, $point_counter);
		}

		if($this->i['plot_overview_text'])
		{
			$to_display = array();
			$to_display[self::$c['color']['notches']] = array();

			foreach(array_keys($calculations_r) as $color)
			{
				$to_display[$color] = array();
			}

			// in_array($this->graph_y_title, array('Percent', 'Milliwatts', 'Megabytes', 'Celsius', 'MB/s', 'Frames Per Second', 'Seconds', 'Iterations Per Minute'))
			if(!empty($calculations_r))
			{
				array_push($to_display[self::$c['color']['notches']], 'Average:');

				foreach($calculations_r as $color => &$values)
				{
					array_push($to_display[$color], (array_sum($values) / count($values)));
				}

				// in_array($this->graph_y_title, array('Megabytes', 'Milliwatts', 'Celsius', 'MB/s', 'Frames Per Second', 'Seconds', 'Iterations Per Minute'))
				if(($this->graph_y_title != 'Percent' || $max_value < 100) && $max_value != $min_value)
				{
					array_push($to_display[self::$c['color']['notches']], 'Peak:');

					foreach($calculations_r as $color => &$values)
					{
						array_push($to_display[$color], max($values));
					}
				}
				if($min_value > 0 && $max_value != $min_value)
				{
					array_push($to_display[self::$c['color']['notches']], 'Low:');

					foreach($calculations_r as $color => &$values)
					{
						array_push($to_display[$color], min($values));
					}
				}
				/*if($point_counter > 9 && !in_array($this->graph_y_title, array('Percent')))
				{
					array_push($to_display[self::$c['color']['body_text']], 'Last:');

					foreach($calculations_r as $color => &$values)
					{
						array_push($to_display[$color], $values[count($values) - 1]);
					}
				}*/

				// Do the actual rendering of avg / low / med high identifiers
				$from_left = $this->i['left_start'] + 6;
				foreach($to_display as $color_key => &$column)
				{
					$from_top = $this->i['top_start'] + 10;
					$longest_string_width = 0;
					$precision = isset($column[0]) && max($column) > 999 ? 0 : 1;

					foreach($column as &$write)
					{
						if(is_numeric($write))
						{
							$write = pts_math::set_precision($write, $precision);
						}

						$this->svg_dom->add_text_element($write, array('x' => $from_left, 'y' => $from_top, 'font-size' => 6.5, 'fill' => $color_key, 'text-anchor' => 'start'));
						$string_width = $this->text_string_width($write, 6.5);

						if($string_width > $longest_string_width)
						{
							$longest_string_width = $string_width;
						}

						$from_top += 10;
					}

					$from_left += $longest_string_width + 3;
				}
			}
		}
	}
	protected function draw_graph_line_process(&$poly_points, &$paint_color, &$regression_plots, $point_counter)
	{
		$poly_points_count = count($poly_points);

		if($poly_points_count == 0)
		{
			// There's nothing to draw
			return;
		}

		$svg_poly = array();
		foreach($poly_points as $x_y)
		{
			array_push($svg_poly, round($x_y[0]) . ',' . round($x_y[1]));
		}
		$svg_poly = implode(' ', $svg_poly);
		$this->svg_dom->add_element('polyline', array('points' => $svg_poly, 'fill' => 'none', 'stroke' => $paint_color, 'stroke-width' => 2));

		foreach($poly_points as $i => $x_y_pair)
		{
			if($x_y_pair[0] < ($this->i['left_start'] + 2) || $x_y_pair[0] > ($this->i['graph_left_end'] - 2))
			{
				// Don't draw anything on the left or right hand edges
				continue;
			}

			$plotted_error_bar = false;
			if($x_y_pair[3] > 0)
			{
				$std_error_width = 4;
				$std_error_rel_size = round(($x_y_pair[3] / $this->i['graph_max_value']) * ($this->i['graph_top_end'] - $this->i['top_start']));

				if($std_error_rel_size > 3)
				{
					$this->svg_dom->draw_svg_line($x_y_pair[0], $x_y_pair[1] + $std_error_rel_size, $x_y_pair[0], $x_y_pair[1] - $std_error_rel_size, $paint_color, 1);
					$this->svg_dom->draw_svg_line($x_y_pair[0] - $std_error_width, $x_y_pair[1] - $std_error_rel_size, $x_y_pair[0] + $std_error_width, $x_y_pair[1] - $std_error_rel_size, $paint_color, 1);
					$this->svg_dom->draw_svg_line($x_y_pair[0] - $std_error_width, $x_y_pair[1] + $std_error_rel_size, $x_y_pair[0] + $std_error_width, $x_y_pair[1] + $std_error_rel_size, $paint_color, 1);
					$plotted_error_bar = true;
				}
			}

			if(isset($regression_plots[$i]) && $i > 0)
			{
				$this->svg_dom->draw_svg_line($x_y_pair[0], $x_y_pair[1] + 6, $x_y_pair[0], $x_y_pair[1] - 6, self::$c['color']['alert'], 4, array('xlink:title' => $regression_plots[$i]));
			}

			if($point_counter < 6 || $plotted_error_bar || $i == 0 || $i == ($poly_points_count  - 1))
			{
				$this->svg_dom->add_element('ellipse', array('cx' => $x_y_pair[0], 'cy' => $x_y_pair[1], 'rx' => 3, 'ry' => 3, 'fill' => $paint_color, 'stroke' => $paint_color, 'stroke-width' => 1, 'xlink:title' => $x_y_pair[2]));
			}
		}

		$poly_points = array();
	}
	protected function render_graph_result()
	{
		$this->renderGraphLines();
	}
}

?>
