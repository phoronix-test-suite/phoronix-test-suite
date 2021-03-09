<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2021, Phoronix Media
	Copyright (C) 2010 - 2021, Michael Larabel

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

class pts_result_file_output
{
	public static function result_file_to_json(&$result_file)
	{
		$json = array();
		$json['title'] = $result_file->get_title();

		$json['results'] = array();
		foreach($result_file->get_result_objects() as $result_object)
		{
			$r = array(
				'test' => $result_object->test_profile->get_identifier(),
				'arguments' => $result_object->get_arguments_description(),
				'units' => $result_object->test_profile->get_result_scale(),
				);

			foreach($result_object->test_result_buffer as &$buffers)
			{
				foreach($buffers as &$buffer)
				{
					$r['results'][$buffer->get_result_identifier()] = array(
						'value' => $buffer->get_result_value(),
						'all_results' => $buffer->get_result_raw()
						);
				}
			}

			$json['results'][] = $r;
		}

		return json_encode($json, JSON_PRETTY_PRINT);
	}
	public static function result_file_to_suite_xml(&$result_file)
	{
		$new_suite = new pts_test_suite();
		$new_suite->set_title($result_file->get_title() . ' Suite');
		$new_suite->set_version('1.0.0');
		$new_suite->set_maintainer(' ');
		$new_suite->set_suite_type('System');
		$new_suite->set_description('Test suite extracted from ' . $result_file->get_title() . '.');
		$new_suite->result_file_to_suite($result_file);

		return $new_suite->get_xml(null, true, true);
	}
	public static function result_file_to_csv(&$result_file, $delimiter = ',', $extra_attributes = null)
	{
		$csv_output = null;

		$csv_output .= $result_file->get_title() . PHP_EOL . $result_file->get_description() . PHP_EOL . PHP_EOL;

		$columns = array();
		$hw = array();
		$sw = array();
		foreach($result_file->get_systems() as $system)
		{
			$columns[] = $system->get_identifier();
			$hw[] = $system->get_hardware();
			$sw[] = $system->get_software();
		}
		$rows = array();
		$table_data = array();

		pts_result_file_analyzer::system_components_to_table($table_data, $columns, $rows, $hw);
		pts_result_file_analyzer::system_components_to_table($table_data, $columns, $rows, $sw);

		$csv_output .= ' ' . $delimiter;

		foreach($columns as $column)
		{
			$csv_output .= $delimiter . '"' . $column . '"';
		}
		$csv_output .= PHP_EOL;

		foreach($rows as $i => $row)
		{
			$csv_output .= $row . $delimiter;

			foreach($columns as $column)
			{
				$csv_output .= $delimiter . (isset($table_data[$column][$i]) ? $table_data[$column][$i] : null);
			}

			$csv_output .= PHP_EOL;
		}

		$csv_output .= PHP_EOL;
		$csv_output .= ' ' . $delimiter;

		foreach($columns as $column)
		{
			$csv_output .= $delimiter . '"' . $column . '"';
		}
		$csv_output .= PHP_EOL;

		foreach($result_file->get_result_objects() as $result_object)
		{
			pts_render::attribute_processing_on_result_object($result_object, $result_file, $extra_attributes);
			$csv_output .= '"' . $result_object->test_profile->get_title() . ' - ' . $result_object->get_arguments_description()  . ' (' . $result_object->test_profile->get_result_scale_shortened() . ')' . '"';
			$csv_output .= $delimiter . $result_object->test_profile->get_result_proportion();

			foreach($columns as $column)
			{
				$buffer_item = $result_object->test_result_buffer->find_buffer_item($column);
				$value = $buffer_item != false ? $buffer_item->get_result_value() : null;
				if(strpos($value, ',') !== false)
				{
					$value = explode(',', $value);
					$value = round(array_sum($value) / count($value), 2);
				}
				$csv_output .= $delimiter . $value;
			}
			$csv_output .= PHP_EOL;
		}
		$csv_output .= PHP_EOL;

		return $csv_output;
	}
	public static function result_file_raw_to_csv(&$result_file, $delimiter = ',')
	{
		$csv_output = null;
		$csv_output .= $result_file->get_title() . $delimiter . PHP_EOL . PHP_EOL;

		foreach($result_file->get_result_objects() as $result_object)
		{
			$csv_output .= '"' . $result_object->test_profile->get_title() . $result_object->test_profile->get_app_version() . ' - ' . $result_object->get_arguments_description() . '"' . $delimiter . PHP_EOL;

			switch($result_object->test_profile->get_result_proportion())
			{
				case 'HIB':
					$csv_output .= 'Higher Results Are Better' . PHP_EOL;
					break;
				case 'LIB':
					$csv_output .= 'Lower Results Are Better' . PHP_EOL;
					break;
			}
			$csv_output .= PHP_EOL;
			foreach($result_object->test_result_buffer->get_buffer_items() as $index => $buffer_item)
			{
				$identifier = $buffer_item->get_result_identifier();
				$raw = $buffer_item->get_result_raw();

				$csv_output .= '"' . $identifier . '"' . $delimiter . str_replace(':', $delimiter, $raw) . PHP_EOL;
			}
			$csv_output .= PHP_EOL;
		}
		$csv_output .= PHP_EOL;

		return $csv_output;
	}
	public static function result_file_to_text(&$result_file, $terminal_width = 79, $stylize_output = false)
	{
		$result_output = null;

		$result_output .= $result_file->get_title() . PHP_EOL;
		$result_output .= $result_file->get_description() . PHP_EOL . PHP_EOL . PHP_EOL;

		$system_identifiers = array();
		$system_hardware = array();
		$system_software = array();
		foreach($result_file->get_systems() as $system)
		{
			$system_identifiers[] = $system->get_identifier();
			$system_hardware[] = $system->get_hardware();
			$system_software[] = $system->get_software();
		}

		for($i = 0; $i < count($system_identifiers); $i++)
		{
			$result_output .= $system_identifiers[$i] . ': ' . PHP_EOL . PHP_EOL;
			$result_output .= "\t" . $system_hardware[$i] . PHP_EOL . PHP_EOL . "\t" . $system_software[$i] . PHP_EOL . PHP_EOL;
		}

		foreach($result_file->get_result_objects() as $result_object)
		{
			$result_output .= self::test_result_to_text($result_object, $terminal_width, $stylize_output, null, true, true, ($terminal_width > 80 ? '    ' : ''));
			$result_output .= PHP_EOL . PHP_EOL;
		}

		return $result_output;
	}
	public static function result_file_confidence_text(&$result_file, $terminal_width = 80, $stylize_output = false)
	{
		$result_output = null;

		$result_output .= $result_file->get_title() . PHP_EOL;
		$result_output .= $result_file->get_description() . PHP_EOL . PHP_EOL . PHP_EOL;

		$system_identifiers = array();
		$system_hardware = array();
		$system_software = array();
		foreach($result_file->get_systems() as $system)
		{
			$system_identifiers[] = $system->get_identifier();
			$system_hardware[] = $system->get_hardware();
			$system_software[] = $system->get_software();
		}

		for($i = 0; $i < count($system_identifiers); $i++)
		{
			$result_output .= $system_identifiers[$i] . ': ' . PHP_EOL . PHP_EOL;
			$result_output .= "\t" . $system_hardware[$i] . PHP_EOL . PHP_EOL . "\t" . $system_software[$i] . PHP_EOL . PHP_EOL;
		}

		foreach($result_file->get_result_objects() as $result_object)
		{
			$raw_values = array();

			foreach($result_object->test_result_buffer as &$buffers)
			{
				foreach($buffers as &$buffer_item)
				{
					$v = $buffer_item->get_result_value();
					$a = $buffer_item->get_result_raw_array();
					if(!is_numeric($v) || empty($v) || empty($a) || strpos($v, ',') !== false)
					{
						continue;
					}
					if(pts_math::arithmetic_mean($a) == 0)
					{
						continue;
					}
					$raw_values[$buffer_item->get_result_identifier()] = $a;
				}
			}

			if(empty($raw_values))
			{
				continue;
			}

			$result_output .= PHP_EOL . '    ' . trim($result_object->test_profile->get_title() . ' ' . $result_object->test_profile->get_app_version());
			$result_output .= PHP_EOL . '    ' . $result_object->get_arguments_description();

			$identifiers = $result_object->test_result_buffer->get_identifiers();
			$longest_identifier_length = strlen(pts_strings::find_longest_string($identifiers)) + 1;
			foreach($raw_values as $identifier => $raw)
			{
				$passes = true;
				$p = pts_math::get_precision($raw);
				$tsl = pts_math::three_sigma_limits($raw, $p);
				$std_dev = round(pts_math::percent_standard_deviation($raw), $p);
				$add_output = PHP_EOL . '    ' . $identifier . PHP_EOL;
				$add_output .= '    ' . '    ' . 'Values: ' . implode(', ', $raw) . PHP_EOL;
				$add_output .= '    ' . '    ' . 'Arithmetic Mean: ' . round(pts_math::arithmetic_mean($raw), $p) . PHP_EOL;
				$add_output .= '    ' . '    ' . 'Std Deviation: ' . $std_dev . '%' . PHP_EOL;
				$add_output .= '    ' . '    ' . 'Three-Sigma Limits: ' . implode(' ', $tsl) . PHP_EOL;
				if($std_dev > 3.0)
				{
					$passes = false;
				}
				$outside_limits = array();
				foreach($raw as $num)
				{
					if($num < $tsl[0] || $num > $tsl[1])
					{
						$outside_limits[] = $num;
					}
				}
				if(!empty($outside_limits))
				{
					$passes = false;
					$add_output .= '    ' . '    ' . '    Results Outside Limits: ' . implode(', ', $outside_limits) . PHP_EOL;
				}

				$result_output .= pts_client::cli_colored_text($add_output, ($passes ? 'green' : 'red'));
			}

			//$result_output .= PHP_EOL . PHP_EOL;
		}

		return $result_output;
	}
	public static function test_result_to_text($result_object, $terminal_width = 80, $stylize_output = false, $highlight_result = null, $show_title = true, $always_force_title = false, $prepend_line = '    ')
	{
		$result_output = null;
		static $last_title_shown = null;
		if($show_title)
		{
			if($always_force_title || $last_title_shown != $result_object->test_profile->get_title())
			{
				$result_output .= PHP_EOL . $prepend_line . trim($result_object->test_profile->get_title() . ' ' . $result_object->test_profile->get_app_version());
				$last_title_shown = $result_object->test_profile->get_title();
			}
			$result_output .= PHP_EOL . $prepend_line . $result_object->get_arguments_description();
		}
		if($result_object->test_profile->get_result_scale() != null)
		{
			$scale_line = $prepend_line . $result_object->test_profile->get_result_scale();
			if($result_object->test_profile->get_result_proportion() == 'LIB')
			{
				$scale_line .= ' < Lower Is Better';
			}
			else if($result_object->test_profile->get_result_proportion() == 'HIB')
			{
				$scale_line .= ' > Higher Is Better';
			}

			$result_output .= PHP_EOL . ($stylize_output && PTS_IS_CLIENT ? pts_client::cli_just_italic($scale_line) : $scale_line);
		}

		$identifiers = $result_object->test_result_buffer->get_identifiers();
		$longest_identifier_length = strlen(pts_strings::find_longest_string($identifiers)) + 1;

		$result_object->test_result_buffer->adjust_precision();
		$is_line_graph = false;
		foreach($result_object->test_result_buffer as &$buffers)
		{
			if(empty($buffers))
				continue;

			$max_value = 0;
			$min_value = -1;
			$largest_min_value = 0;
			$longest_result = 0;
			foreach($buffers as &$buffer_item)
			{
				$v = $buffer_item->get_result_value();
				if(($vl = strlen($v)) > $longest_result)
				{
					$longest_result = $vl;
				}

				if(stripos($v, ',') !== false)
				{
					$v = explode(',', $v);
					$max_value = max($max_value, max($v) * 1.03);
					$min_value = $min_value == -1 ? min($v) : min($min_value, min($v));
					$largest_min_value = max($largest_min_value, min($v));
					$is_line_graph = true;
				}
				else if($v > $max_value)
				{
					$max_value = $v;
				}
				else if($v < $min_value)
				{
					$min_value = $v;
				}
			}
			// First run through the items to see if it makes sense applying colors (e.g. multiple matches)
			$buffer_count = 0;
			foreach($buffers as &$buffer_item)
			{
				$brand_color = pts_render::identifier_to_brand_color($buffer_item->get_result_identifier(), null);
				if($brand_color != null)
				{
					// Quite simple handling, could do better
					$buffer_count++;
				}
			}
			$do_color = $buffer_count > 1 ? true : false;

			$longest_result++;
			$precision = ($max_value > 100 || ($min_value > 29 && $max_value > 79) ? 0 : 1);
			if($is_line_graph)
			{
				$largest_min_value = pts_math::set_precision($largest_min_value, $precision);
				$min_value = pts_math::set_precision($min_value, $precision);
				$largest_min_length = strlen($largest_min_value);
				$max_value_length = strlen(pts_math::set_precision($max_value, $precision));
			}
			foreach($buffers as &$buffer_item)
			{
				$val = $buffer_item->get_result_value();
				$result_line = $prepend_line . $buffer_item->get_result_identifier() . ' ';
				$result_length_offset = $longest_identifier_length - strlen($buffer_item->get_result_identifier());
				if($result_length_offset > 0)
				{
					$result_line .= str_repeat('.', $result_length_offset) . ' ';
				}

				if($is_line_graph)
				{
					// LINE GRAPH
					$values = explode(',', $val);
					$formatted_min = pts_math::set_precision(min($values), $precision);
					$formatted_avg = pts_math::set_precision(pts_math::arithmetic_mean($values), $precision);
					$min_value_offset = $largest_min_length - strlen($formatted_min);
					$min_value_offset = $min_value_offset > 0 ? str_repeat(' ', $min_value_offset) : null;
					$avg_value_offset = $max_value_length - strlen($formatted_avg);
					$avg_value_offset = $avg_value_offset > 0 ? str_repeat(' ', $avg_value_offset) : null;
					$result_line .= 'MIN: ' . $formatted_min . $min_value_offset . '  AVG: ' . $formatted_avg . $avg_value_offset . '  MAX: ' . pts_math::set_precision(max($values), $precision) . ' ';

					if($terminal_width > (strlen($result_line) * 2) && $buffer_count > 1)
					{
						$box_plot = str_repeat(' ', ($terminal_width - strlen($result_line)));
						$box_plot_size = strlen($box_plot);
						$box_plot = str_split($box_plot);

						// BOX PLOT
						sort($values, SORT_NUMERIC);
						$whisker_bottom = pts_math::find_percentile($values, 0.02, true);
						$whisker_top = pts_math::find_percentile($values, 0.98, true);
						$unique_values = array_unique($values);
						foreach($unique_values as &$val)
						{
							if(($val < $whisker_bottom || $val > $whisker_top) && $val > 0.1)
							{
								$x = floor($val / $max_value * $box_plot_size);
								if(isset($box_plot[$x]))
									$box_plot[$x] = '.';
							}
						}
						$whisker_start_char = round($whisker_bottom / $max_value * $box_plot_size);
						$whisker_end_char = round($whisker_top / $max_value * $box_plot_size);

						for($i = $whisker_start_char; $i <= $whisker_end_char && $i < ($box_plot_size - 1); $i++)
						{
							$box_plot[$i] = '-';
						}

						$box_left = round((pts_math::find_percentile($values, 0.25, true) / $max_value) * $box_plot_size);
						$box_middle = round((pts_math::find_percentile($values, 0.5, true) / $max_value) * $box_plot_size);
						$box_right = round((pts_math::find_percentile($values, 0.75, true) / $max_value) * $box_plot_size);
						for($i = $box_left; $i <= $box_right; $i++)
						{
							$box_plot[$i] = '#';
						}
						$box_plot[$whisker_start_char] = '|';
						$box_plot[$whisker_end_char] = '|';
						$box_plot[$box_middle] = 'X';

						// END OF BOX PLOT
						//$box_plot[0] = '[';
						//$box_plot[($box_plot_size - 1)] = ']';
						$result_line .= substr(implode('', $box_plot), 0, $box_plot_size);
					}
				}
				else if(is_numeric($val))
				{
					// STANDARD NUMERIC RESULT
					$result_line .= $val;
					$repeat_length = $longest_result - strlen($val);
					$result_line .= ($repeat_length >= 0 ? str_repeat(' ', $repeat_length) : null)  . '|';
					$current_line_length = strlen($result_line);
					if($max_value > 0)
					{
						$result_line .= str_repeat('=', max(0, round(($val / $max_value) * ($terminal_width - $current_line_length))));
					}
				}
				else if($result_object->test_profile->get_display_format() == 'PASS_FAIL')
				{
					if($stylize_output && PTS_IS_CLIENT)
					{
						switch($val)
						{
							case 'PASS':
								$val = pts_client::cli_colored_text($val, 'green', true);
								break;
							case 'FAIL':
								$val = pts_client::cli_colored_text($val, 'red', true);
								break;
						}
					}
					$result_line .= $val;
				}

				if($stylize_output && PTS_IS_CLIENT)
				{
					$do_bold = false;
					// See if should bold the line
					if($highlight_result == $buffer_item->get_result_identifier())
					{
						$do_bold = true;
					}
					else if(is_array($highlight_result) && in_array($buffer_item->get_result_identifier(), $highlight_result))
					{
						$do_bold = true;
					}

					// Determine if color
					if($do_color)
					{
						$brand_color = pts_render::identifier_to_brand_color($buffer_item->get_result_identifier(), null);
						if($brand_color != null)
						{
							$brand_color = pts_client::hex_color_to_string($brand_color);
						}
					}
					else
					{
						$brand_color = false;
					}

					if($brand_color)
					{
						$result_line = pts_client::cli_colored_text($result_line, $brand_color, $do_bold);
					}
					else if($do_bold)
					{
						$result_line = pts_client::cli_just_bold($result_line);
					}
				}

				$result_output .= PHP_EOL . $result_line;
			}
		}
		return $result_output;
	}
	public static function result_file_to_detailed_html_table(&$result_file, $grid_class = 'grid', $extra_attributes = null, $detailed_table = true)
	{
		$table = array();
		$systems = array_merge(array(' '), $result_file->get_system_identifiers());
		$systems_count = count($systems) - 1;
		$systems_format = $systems;
		$af = function(&$value) { $value = '<strong style="writing-mode: vertical-rl; text-orientation: mixed;">' . strtoupper($value) . '</strong>'; };
		array_walk($systems_format, $af);
		$table[] = $systems_format;

		foreach($result_file->get_result_objects() as $ro)
		{
			if($ro == false || $ro->test_profile->get_display_format() != 'BAR_GRAPH' || $ro->test_profile->get_identifier() == null)
			{
				continue;
			}

			$table[] = array_fill(0, count($systems), ' ');
			$row = &$table[count($table) - 1];
			if($detailed_table)
			{
				$table[] = array_fill(0, count($systems), ' ');
				$nor = &$table[count($table) - 1];
				$nor[0] = ' &nbsp; &nbsp; Normalized';
				$table[] = array_fill(0, count($systems), ' ');
				$samples = &$table[count($table) - 1];
				$samples[0] = ' &nbsp; &nbsp; Samples';
				if($ro->test_result_buffer->has_run_with_multiple_samples())
				{
					$table[] = array_fill(0, count($systems), ' ');
					$dev = &$table[count($table) - 1];
					$dev[0] = ' &nbsp; &nbsp; Standard Deviation';
					$table[] = array_fill(0, count($systems), ' ');
					$err = &$table[count($table) - 1];
					$err[0] = ' &nbsp; &nbsp; Standard Error';
				}
			}

			$hib = $ro->test_profile->get_result_proportion() == 'HIB';
			$row[0] = '<span><strong><a href="#r-' . $ro->get_comparison_hash(true, false) . '">' . $ro->test_profile->get_title() . '</a></strong><br />' . $ro->get_arguments_description_shortened(($systems_count > 11 ? true : false)) . ' (' . $ro->test_profile->get_result_scale_shortened() . ' ' . ($hib ? '&uarr;' : '&darr;') . ' )</span>';

			$best = $ro->get_result_first(false);
			$worst = $ro->get_result_last(false);
			$median = $ro->test_result_buffer->get_median();

			$normalize_against = 0;
			if(isset($extra_attributes['highlight_graph_values']) && is_array($extra_attributes['highlight_graph_values']) && count($extra_attributes['highlight_graph_values']) == 1)
			{
				$normalize_against = $ro->get_result_value_from_name($extra_attributes['highlight_graph_values'][0]);
			}
			if($normalize_against == 0)
			{
				$normalize_against = $best;
			}

			$result_buffer_count = $ro->test_result_buffer->get_count();
			foreach($ro->test_result_buffer->get_buffer_items() as $index => $buffer_item)
			{
				$identifier = $buffer_item->get_result_identifier();
				$value = $buffer_item->get_result_value();

				if(($x = array_search($identifier, $systems)) !== false)
				{
					$style = null;
					if($result_buffer_count > 1)
					{
						if($value == $best)
						{
							$style = ' style="font-weight: bold; color: #009900;"';
						}
						else if($value == $worst)
						{
							$style = ' style="font-weight: bold; color: #FF0000;"';
						}
						/* else if($hib && $value > $median)
						{
							$style = ' style="color: ' . pts_graph_core::shift_color('#009900', (($value - $median) / ($best - $median))) . ';"';
						}
						else if($hib && $value < ($best - $median))
						{
							$style = ' style="color: ' . pts_graph_core::shift_color('#FF0000', 1 - (abs($value - $median) / abs($best - $median))) . ';"';
						} */
					}

					if($value > 1000)
					{
						$value = round($value);
					}

					if($value == 0)
					{
						continue;
					}

					$row[$x] = '<span' . $style. '>' . round($value, 2) . '</span>';
					$nor[$x] = round(($hib ? ($value / $normalize_against) : ($normalize_against / $value)) * 100, 2) . '%';
					$samples[$x] = $buffer_item->get_sample_count();
					if($samples[$x] > 1)
					{
						$raw = $buffer_item->get_result_raw_array();
						$dev[$x] = round(pts_math::standard_deviation($raw), 4);
						$err[$x] = round(pts_math::standard_error($raw), 4);
					}
				}
			}
		}

		// disable this for now
		if(false && $geo = pts_result_file_analyzer::generate_geometric_mean_result($result_file))
		{
			$table[] = array_fill(0, count($systems), ' ');
			$row = &$table[count($table) - 1];
			$row[0] = '<strong>GEOMETRIC MEAN</strong>';
			foreach($geo->test_result_buffer->get_buffer_items() as $index => $buffer_item)
			{
				$identifier = $buffer_item->get_result_identifier();
				$value = $buffer_item->get_result_value();

				if(($x = array_search($identifier, $systems)) !== false)
				{
					$row[$x] = '<strong>' . $value . '</strong>';
				}
			}
		}

		$html = '<div class="' . $grid_class .'" style="grid-template-columns: max-content ' . str_repeat('max-content ', count($systems) - 1) . '">';

		if(count($table) < 2)
		{
			return null;
		}
		foreach($table as $i => &$row)
		{
			foreach($row as $c)
			{
				$html .= '<span>' . $c . '</span>';
			}
		}
		$html .= '</div>' . PHP_EOL;

		return $html;
	}
	public static function diff_in_system($from, $to)
	{
		$from = explode(', ', $from);
		$to = explode(', ', $to);
		$changed = array();
		foreach($from as $i => &$word)
		{
			if(isset($to[$i]) && $to[$i] != $from[$i])
			{
				list($component_type, $component) = explode(': ', $to[$i]);
				if(in_array($component_type, array('Audio', 'Monitor')))
				{
					continue;
				}
				$changed[$component_type] = $component;
			}
		}
		return !empty($changed) ? $changed : false;
	}
	public static function result_file_to_system_html(&$result_file)
	{
		$html = null;
		$systems = $result_file->get_systems();
		$system_count = count($systems);
		$prev_notes = null;
		$prev_sw = null;
		$prev_hw = null;

		foreach($systems as $i => $system)
		{
			$html .= '<h2>' . $system->get_identifier() . '</h2>';
			if(isset($systems[($i + 1)]) && $systems[($i + 1)]->get_hardware() == $system->get_hardware() && $systems[($i + 1)]->get_software() == $system->get_software())
			{
				//continue;
			}
			else
			{
				$hw = $system->get_hardware();
				$sw = $system->get_software();

				if($hw != $prev_hw)
				{
					if(($diff = self::diff_in_system($prev_hw, $hw)) && count($diff) < 4 && $sw == $prev_sw)
					{
						foreach($diff as $type => $c)
						{
							$html .= '<p>Changed <strong>' . $type . '</strong> to <strong>' . $c . '</strong>.</p>';
						}
					}
					else
					{
						$html .= '<p>' . pts_strings::highlight_diff_two_structured_strings(pts_strings::highlight_words_with_colon($hw), pts_strings::highlight_words_with_colon($prev_hw)) . '</p>';
					}
					$prev_hw = $hw;
				}
				if($sw != $prev_sw)
				{
					$html .= '<p>' . pts_strings::highlight_words_with_colon($sw) . '</p>';
					$prev_sw = $sw;
				}
			}

			if(isset($systems[($i + 1)]) && $systems[($i + 1)]->get_json() == $system->get_json() && $systems[($i + 1)]->get_notes() == $system->get_notes())
			{
			
			}
			else
			{
				$attributes = array();
				pts_result_file_analyzer::system_to_note_array($system, $attributes);
				if(!empty($attributes))
				{
					$notes = '<p class="mini"><em>';
					foreach($attributes as $section => $data)
					{
						foreach($data as $c => $val)
						{
							$notes .= '<strong>' .$section . ' Notes:</strong> ' . $val . '<br />';
						}
					}
					$notes .= '</em></p>';

					if($notes != $prev_notes)
					{
						$html .= $notes;
						$prev_notes = $notes;
					}
				}
			}
		}

		return $html;
	}
	public static function result_file_to_pdf(&$result_file, $dest, $output_name, $extra_attributes = null)
	{
		//ini_set('memory_limit', '1024M');
		ob_start();
		$_REQUEST['force_format'] = 'PNG'; // Force to PNG renderer
		$_REQUEST['svg_dom_gd_no_interlacing'] = true; // Otherwise FPDF will fail
		$pdf = new pts_pdf_template($result_file->get_title(), null);

		$pdf->AddPage();
		$pdf->Image(PTS_CORE_STATIC_PATH . 'images/pts-308x160.png', 69, 85, 73, 38);
		$pdf->Ln(120);
		$pdf->WriteStatementCenter('www.phoronix-test-suite.com');
		$pdf->Ln(15);
		$pdf->WriteBigHeaderCenter($result_file->get_title());
		$pdf->WriteText($result_file->get_description());

		// Executive Summary
		$highlight_result = null;
		if(isset($extra_attributes['highlight_graph_values']) && is_array($extra_attributes['highlight_graph_values']) && count($extra_attributes['highlight_graph_values']) == 1)
		{
			$highlight_result = $extra_attributes['highlight_graph_values'][0];
		}
		$exec_summary = pts_result_file_analyzer::generate_executive_summary($result_file, $highlight_result);

		if(!empty($exec_summary))
		{
			$pdf->CreateBookmark('Automated Executive Summary', 0);
			$pdf->WriteText('Automated Executive Summary', 'B');
			$pdf->WriteText(implode(PHP_EOL . PHP_EOL, $exec_summary), 'I');
		}

		$pdf->Ln(5);
		//$pdf->WriteText('This file was automatically generated via the Phoronix Test Suite benchmarking software.', 'I');

		//$pdf->AddPage();
		$pdf->Ln(15);

		$pdf->SetSubject($result_file->get_title() . ' Benchmarks');
		//$pdf->SetKeywords(implode(', ', $identifiers));

		$pdf->WriteHeader('Test Systems:');
		$pdf->CreateBookmark('System Information', 0);
		$systems = $result_file->get_systems();
		$system_count = count($systems);

		foreach($systems as $i => $system)
		{
			$pdf->Ln(5);
			$pdf->CreateBookmark($system->get_identifier(), 1);
			$pdf->WriteMiniHeader($system->get_identifier());
			if(isset($systems[($i + 1)]) && $systems[($i + 1)]->get_hardware() == $system->get_hardware() && $systems[($i + 1)]->get_software() == $system->get_software())
			{
				continue;
			}

			$pdf->WriteText($system->get_hardware());
			$pdf->WriteText($system->get_software());

			$attributes = array();
			pts_result_file_analyzer::system_to_note_array($system, $attributes);
			foreach($attributes as $section => $data)
			{
				//$pdf->WriteMiniText($section . ' Notes');
				foreach($data as $c => $val)
				{
					$pdf->WriteMiniText($section . ' Notes: ' . $val);
				}
			}
			$pdf->Ln();
		}

		//$pdf->AddPage();
		$columns = $result_file->get_system_identifiers();
		array_unshift($columns, ' ');
		$table_data = array();
		$table_data_hints = array();
		$row = 0;
		$last_test_profile = null;
		foreach($result_file->get_result_objects() as $ro)
		{
			if($ro->test_profile->get_display_format() != 'BAR_GRAPH')
			{
				continue;
			}
			if($last_test_profile != null && $last_test_profile != $ro->test_profile->get_identifier())
			{
				$geo_for_test = pts_result_file_analyzer::generate_geometric_mean_result($result_file, false, $last_test_profile);

				if(false && $geo_for_test)
				{
					$table_data[$row][0] = $geo_for_test->test_profile->get_title();
					$table_data_hints[$row][0] = 'divide';
					for($i = 1; $i < count($columns); $i++)
					{
						$table_data[$row][$i] = ' ';
					}

					$best = $geo_for_test->get_result_first(false);
					$worst = $geo_for_test->get_result_last(false);

					foreach($geo_for_test->test_result_buffer->get_buffer_items() as $index => $buffer_item)
					{
						$identifier = $buffer_item->get_result_identifier();
						$value = $buffer_item->get_result_value();

						if(($x = array_search($identifier, $columns)) !== false)
						{
							$table_data_hints[$row][$x] = 'divide';
							switch($value)
							{
								case $best:
									$table_data_hints[$row][$x] = 'green';
									break;
								case $worst:
									$table_data_hints[$row][$x] = 'red';
									break;
							}

							if($value > 1000)
							{
								$value = round($value);
							}
							$table_data[$row][$x] = $value;
						}
					}
					$row++;
				}
			}
			$last_test_profile = $ro->test_profile->get_identifier();

			$table_data[$row][0] = $ro->test_profile->get_title() . ($ro->get_arguments_description() != null ? ' - ' : null) . $ro->get_arguments_description_shortened() . ' (' . $ro->test_profile->get_result_scale_shortened() . ')';
			for($i = 1; $i < count($columns); $i++)
			{
				$table_data[$row][$i] = ' ';
			}

			$hib = $ro->test_profile->get_result_proportion() == 'HIB';
			$best = $ro->get_result_first(false);
			$worst = $ro->get_result_last(false);
			if($best == $worst)
			{
				$best = -1;
				$worst = -1;
			}
			$normalize_against = 0;
			if(isset($extra_attributes['highlight_graph_values']) && is_array($extra_attributes['highlight_graph_values']) && count($extra_attributes['highlight_graph_values']) == 1)
			{
				$normalize_against = $ro->get_result_value_from_name($extra_attributes['highlight_graph_values'][0]);
			}
			if($normalize_against == 0)
			{
				$normalize_against = $best;
			}
			$extra_rows = array(array(), array());
			foreach($ro->test_result_buffer->get_buffer_items() as $index => $buffer_item)
			{
				$identifier = $buffer_item->get_result_identifier();
				$value = $buffer_item->get_result_value();

				if(($x = array_search($identifier, $columns)) !== false)
				{
					switch($value)
					{
						case $best:
							$table_data_hints[$row][$x] = 'green';
							break;
						case $worst:
							$table_data_hints[$row][$x] = 'red';
							break;
					}

					if($normalize_against != -1)
					{
						$extra_rows[0][0] = 'Normalized';
						$extra_rows[0][$x] = round(($hib ? ($value / $normalize_against) : ($normalize_against / $value)) * 100, 2) . '%';
					}

					$raw = $buffer_item->get_result_raw_array();
					if(count($raw) > 1)
					{
						$extra_rows[1][0] = 'Standard Deviation';
						$extra_rows[1][$x] = round(pts_math::percent_standard_deviation($raw), 1) . '%';
					}

					if($value > 1000)
					{
						$value = round($value);
					}
					$table_data[$row][$x] = $value;
				}
			}
			foreach($extra_rows as $extra_row)
			{
				if(empty($extra_row))
				{
					continue;
				}
				$row++;
				for($i = 0; $i < count($columns); $i++)
				{
					$table_data[$row][$i] = ' ';
					$table_data_hints[$row][$i] = 'small';
				}
				foreach($extra_row as $x => $value)
				{
					$table_data[$row][$x] = $value;
					$table_data_hints[$row][$x] = 'small';
				}
			}
			$row++;
		}
		$pdf->CreateBookmark('Result Overview Table', 0);
		$pdf->ResultTable($columns, $table_data, $table_data_hints);

		$pdf->AddPage();

		/*
		if($result_file->get_system_count() == 2)
		{
			$graph = new pts_graph_run_vs_run($result_file);

			if($graph)
			{
				//$graph = pts_render::render_graph_process($graph, $result_file, $extra_attributes);
				self::add_graph_result_object_to_pdf($pdf, $graph);
			}
		}
		else if(!$result_file->is_multi_way_comparison())
		{
			foreach(array('', 'Per Watt', 'Per Dollar') as $selector)
			{
				$graph = new pts_graph_radar_chart($result_file, $selector);

				if($graph)
				{
					//$graph = pts_render::render_graph_process($graph, $result_file, $extra_attributes);
					self::add_graph_result_object_to_pdf($pdf, $graph);
				}
			}
		}
		*/

		$last_result_title = null;
		$extra_attributes['pdf_generation'] = true;
		foreach($result_file->get_result_objects() as $key => $result_object)
		{
			if($last_result_title != $result_object->test_profile->get_title())
			{
				$last_result_title = $result_object->test_profile->get_title();
				$pdf->CreateBookmark($last_result_title, 0);
			}
			if($system_count > 2)
			{
				$result_object->sort_results_by_performance();
			}
			$graph = pts_render::render_graph_process($result_object, $result_file, false, $extra_attributes);
			self::add_graph_result_object_to_pdf($pdf, $graph);
			if($result_object->get_annotation() != null)
			{
				$pdf->WriteText($result_object->get_annotation());
			}
		}


		$geo_mean_for_suites = pts_result_file_analyzer::generate_geometric_mean_result_for_suites_in_result_file($result_file, true, 18);
		if(!empty($geo_mean_for_suites))
		{
			$pdf->AddPage();
			$pdf->CreateBookmark('Geometric Means', 0);
			$pdf->WriteText('These geometric means are based upon test groupings / test suites for this result file.');
			foreach($geo_mean_for_suites as $result_object)
			{
				$pdf->CreateBookmark(str_replace('Geometric Mean Of ', '', $result_object->test_profile->get_title()), 1);
				$graph = pts_render::render_graph_process($result_object, $result_file, false, $extra_attributes);
				self::add_graph_result_object_to_pdf($pdf, $graph);
				if($result_object->get_annotation() != null)
				{
					$pdf->WriteText($result_object->get_annotation());
				}
			}
		}

		$pdf->WriteText('This file was automatically generated via the Phoronix Test Suite benchmarking software on ' . date('l, j F Y H:i') . '.', 'I');
		ob_get_clean();
		$pdf->Output($dest, $output_name);
	}
	protected static function add_graph_result_object_to_pdf(&$pdf, &$graph)
	{
		if($graph == false)
		{
			return false;
		}

		$graph->renderGraph();
		$tmp_file = sys_get_temp_dir() . '/' . microtime() . rand(0, 999) . '.png';
		$output = $graph->svg_dom->output($tmp_file);
		if(!is_file($tmp_file))
		{
			return false;
		}
		//$pdf->Ln(1);
		$pdf->Image($tmp_file);
		unlink($tmp_file);
	}
}

?>
