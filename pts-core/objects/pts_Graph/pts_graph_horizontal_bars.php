<?php
/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2021, Phoronix Media
	Copyright (C) 2008 - 2021, Michael Larabel
	pts_HorizontalBarGraph.php: The horizontal bar graph object that extends pts_Graph.php

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

class pts_graph_horizontal_bars extends pts_graph_core
{
	protected $make_identifiers_web_links = false;
	public function __construct(&$result_object, &$result_file = null, $extra_attributes = null)
	{
		parent::__construct($result_object, $result_file, $extra_attributes);
		$this->i['iveland_view'] = true;
		$this->i['graph_orientation'] = 'HORIZONTAL';
		$this->i['identifier_height'] = -1;

		$this->i['skip_headers'] = false;

		if(isset($extra_attributes['make_identifiers_web_links']) && !empty($extra_attributes['make_identifiers_web_links']))
		{
			$this->make_identifiers_web_links = $extra_attributes['make_identifiers_web_links'];
		}
	}
	protected function render_graph_pre_init()
	{
		// Do some common work to this object
		$this->i['identifier_height'] = floor(($this->i['graph_top_end'] - $this->i['top_start']) / count($this->graph_identifiers));
	}
	protected function render_graph_identifiers()
	{
		$px_from_top_end = $this->i['graph_top_end'] + 5;
		if(count($this->graph_identifiers) > 1)
		{
			$this->svg_dom->draw_svg_line($this->i['left_start'], $this->i['top_start'] + $this->i['identifier_height'], $this->i['left_start'], $this->i['graph_top_end'] - ($this->i['graph_height'] % $this->i['identifier_height']), self::$c['color']['notches'], 11, array('stroke-dasharray' => 1 . ',' . ($this->i['identifier_height'] - 1)));
		}
		$middle_of_vert = round($this->i['top_start'] + ($this->i['is_multi_way_comparison'] ? 5 : 0) - ($this->i['identifier_height'] * 0.5) - 2);

		$g = array('font-size' => $this->i['identifier_size'] + 1, 'fill' => self::$c['color']['headers'], 'font-weight' => 'bold', 'text-anchor' => 'end');
		if($this->i['is_multi_way_comparison'])
		{
			$g['font-size']--;
			unset($g['font-weight']);
		}
		$g = $this->svg_dom->make_g($g);
		foreach($this->graph_identifiers as $identifier)
		{
			$middle_of_vert += $this->i['identifier_height'];
			if($this->i['is_multi_way_comparison'])
			{
				foreach(array_reverse(explode(' - ', $identifier)) as $i => $identifier_line)
				{
					$x = 8 + ($i * (1.2 * $this->i['identifier_size']));
					$this->svg_dom->add_text_element($identifier_line, array('x' => $x, 'y' => $middle_of_vert, 'text-anchor' => 'middle', 'font-weight' => 'bold', 'transform' => 'rotate(90 ' . $x . ' ' . $middle_of_vert . ')'), $g);
				}
			}
			else
			{
				$attrs = array('y' => $middle_of_vert, 'x' => ($this->i['left_start'] - 5));
				if($this->make_identifiers_web_links)
				{
					$attrs['xlink:href'] = $this->make_identifiers_web_links . $identifier;
				}
				$this->svg_dom->add_text_element($identifier, $attrs, $g);
			}
		}
	}
	protected function calc_offset(&$r, $a)
	{
		if(($s = array_search($a, $r)) !== false)
		{
			return $s;
		}
		else
		{
			$r[] = $a;
			return (count($r) - 1);
		}
	}
	protected function render_graph_bars()
	{
		$bar_count = count($this->results);
		$separator_height = ($a = (6 - (floor($bar_count / 2) * 2))) > 0 ? $a : 0;
		$bar_height = floor(($this->i['identifier_height'] - ($this->i['is_multi_way_comparison'] ? 4 : 0) - $separator_height - ($bar_count * $separator_height)) / $bar_count);
		$this->i['graph_max_value'] = $this->i['graph_max_value'] != 0 ? $this->i['graph_max_value'] : 1;
		$work_area_width = $this->i['graph_left_end'] - $this->i['left_start'];

		$group_offsets = array();
		$id_offsets = array();
		$g_bars = $this->svg_dom->make_g(array('stroke' => self::$c['color']['body_light'], 'stroke-width' => 1));
		$g_se = $this->svg_dom->make_g(array('font-size' => ($this->i['identifier_size'] - 2), 'fill' => self::$c['color']['text'], 'text-anchor' => 'end'));
		$g_values = $this->svg_dom->make_g(array('font-size' => $this->i['identifier_size'], 'fill' => self::$c['color']['body_text'], 'font-weight' => 'bold', 'text-anchor' => 'end'));
		$g_note = null;
		$g_identifier_note = null;

		foreach($this->results as $identifier => &$group)
		{
			$paint_color = $this->get_paint_color($identifier);
			foreach($group as &$buffer_item)
			{
				// if identifier is 0, not a multi-way comparison or anything special
				if($identifier == 0 && !$this->i['is_multi_way_comparison'])
				{
					// See if the result identifier matches something to be color-coded better
					$paint_color = self::identifier_to_branded_color($buffer_item->get_result_identifier(), $this->get_paint_color($identifier));
				}

				$value = $buffer_item->get_result_value();
				$i_o = $this->calc_offset($group_offsets, $identifier);

				if($this->i['is_multi_way_comparison'])
					$i = $this->calc_offset($id_offsets, $buffer_item->get_result_identifier());
				else
					$i = $this->calc_offset($id_offsets, $buffer_item->get_result_identifier() . ' ' . $value);

				$graph_size = max(0, round(($value / $this->i['graph_max_value']) * $work_area_width));
				$value_end_right = max($this->i['left_start'] + $graph_size, 1);
				$px_bound_top = $this->i['top_start'] + ($this->i['is_multi_way_comparison'] ? 5 : 0) + ($this->i['identifier_height'] * $i) + ($bar_height * $i_o) + ($separator_height * ($i_o + 1));
				$px_bound_bottom = $px_bound_top + $bar_height;
				$middle_of_bar = round($px_bound_top + ($bar_height / 2) + ($this->i['identifier_size'] - 4));

				$std_error = -1;
				if(($raw_values = $buffer_item->get_result_raw_array()))
				{
					switch(count($raw_values))
					{
						case 0:
							$std_error = -1;
							break;
						case 1:
							$std_error = 0;
							break;
						default:
							$std_error = pts_math::standard_error($raw_values);
							break;
					}
				}

				$this->svg_dom->add_element('rect', array('x' => $this->i['left_start'], 'y' => $px_bound_top, 'height' => $bar_height, 'width' => $graph_size, 'fill' => $this->adjust_color($buffer_item->get_result_identifier(), $paint_color)), $g_bars);

				if($std_error != -1 && ($std_error > 0 || ($std_error == 0 && count($raw_values) > 1)) && $value != null)
				{
					$std_error_height = 8;
					if($std_error > 0 && is_numeric($std_error))
					{
						$std_error_rel_size = round(($std_error / $this->i['graph_max_value']) * ($this->i['graph_left_end'] - $this->i['left_start']));
						if($std_error_rel_size > 4)
						{
							$std_error_base_left = ($value_end_right - $std_error_rel_size);
							$std_error_base_right = ($value_end_right + $std_error_rel_size);

							$g = $this->svg_dom->make_g(array('stroke' => self::$c['color']['notches'], 'stroke-width' => 1));
							$this->svg_dom->add_element('line', array('x1' => $std_error_base_left, 'y1' => $px_bound_top, 'x2' => $std_error_base_left, 'y2' => $px_bound_top + $std_error_height), $g);
							$this->svg_dom->add_element('line', array('x1' => $std_error_base_right, 'y1' => $px_bound_top, 'x2' => $std_error_base_right, 'y2' => $px_bound_top + $std_error_height), $g);
							$this->svg_dom->add_element('line', array('x1' => $std_error_base_left, 'y1' => $px_bound_top, 'x2' => $std_error_base_right, 'y2' => $px_bound_top), $g);
						}
					}
					$bar_offset_34 = round($middle_of_bar + ($this->i['is_multi_way_comparison'] ? 0 : ($bar_height / 5) + 1));
					$this->svg_dom->add_text_element('SE +/- ' . pts_math::set_precision($std_error, max(2, pts_math::get_precision($value))) . ', N = ' . count($raw_values), array('y' => $bar_offset_34, 'x' => ($this->i['left_start'] - 5)), $g_se);
				}

				if((self::text_string_width($value, $this->i['identifier_size']) + 2) < $graph_size)
				{
					if(isset($this->d['identifier_notes'][$buffer_item->get_result_identifier()]) && $this->i['compact_result_view'] == false && !$this->i['is_multi_way_comparison'])
					{
						// TODO see if the is_multi_way_comparison check can be dropped...
						if($g_identifier_note == null)
						{
							$g_identifier_note = $this->svg_dom->make_g(array('font-size' => (self::$c['size']['key'] - 2), 'fill' => self::$c['color']['body_text'], 'text-anchor' => 'start'));
						}
						$this->svg_dom->add_text_element($this->d['identifier_notes'][$buffer_item->get_result_identifier()], array('x' => ($this->i['left_start'] + 4), 'y' => ($px_bound_top + self::$c['size']['key'])), $g_identifier_note);
					} /*
					else
					{
						// TODO XXX this code shouldn't really be used anymore since the identifier_notes above is more standardized...
						$data = $buffer_item->get_result_json();
						$note = null;
						if(isset($data['min-result']) && is_numeric($data['min-result']))
						{
							if(isset($data['max-result']) && is_numeric($data['max-result']))
							{
								$note = 'MIN: ' . $data['min-result'] . ' / MAX: ' . $data['max-result'];
							}
							else
							{
								$note = 'MIN: ' . $data['min-result'];
							}
						}

						if(!empty($note))
						{
							if($g_note == null)
							{
								$g_note = $this->svg_dom->make_g(array('font-size' => (self::$c['size']['key'] - 2), 'fill' => self::$c['color']['body_text'], 'text-anchor' => 'start', 'font-weight' => 'bold'));
							}
							if(self::text_string_width($note, self::$c['size']['key']) > ($graph_size * 0.9))
							{
								// TODO decide if note should be relocated in front of bar or something?
							}
							else
							{
								$this->svg_dom->add_text_element($note, array('x' => ($this->i['left_start'] + 4), 'y' => $px_bound_top + self::$c['size']['key']), $g_note);
							}
						}
					} */

					$this->svg_dom->add_text_element($value, array('x' => ($value_end_right - 5), 'y' => $middle_of_bar), $g_values);
				}
				else if($value > 0)
				{
					// Write it in front of the result
					$this->svg_dom->add_text_element($value, array('x' => ($value_end_right + 6), 'y' => $middle_of_bar, 'fill' => self::$c['color']['text'], 'text-anchor' => 'start'), $g_values);
				}
			}
		}
	}
	protected function render_graph_result()
	{
		$this->render_graph_bars();
	}
}

?>
