<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2019, Phoronix Media
	Copyright (C) 2010 - 2019, Michael Larabel

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
	public static function result_file_to_csv(&$result_file, $delimiter = ',')
	{
		$csv_output = null;

		$csv_output .= $result_file->get_title() . PHP_EOL . PHP_EOL;

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
			$csv_output .= '"' . $result_object->test_profile->get_title() . ' - ' . $result_object->get_arguments_description() . '"';
			$csv_output .= $delimiter . $result_object->test_profile->get_result_proportion();

			foreach($columns as $column)
			{
				$buffer_item = $result_object->test_result_buffer->find_buffer_item($column);
				$value = $buffer_item != false ? $buffer_item->get_result_value() : null;
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
	public static function result_file_to_text(&$result_file, $terminal_width = 80, $stylize_output = false)
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
			$result_output .= self::test_result_to_text($result_object, $terminal_width, $stylize_output, null, true, true);
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
	public static function test_result_to_text($result_object, $terminal_width = 80, $stylize_output = false, $highlight_result = null, $show_title = true, $always_force_title = false)
	{
		$result_output = null;
		static $last_title_shown = null;
		if($show_title)
		{
			if($always_force_title || $last_title_shown != $result_object->test_profile->get_title())
			{
				$result_output .= PHP_EOL . '    ' . trim($result_object->test_profile->get_title() . ' ' . $result_object->test_profile->get_app_version());
				$last_title_shown = $result_object->test_profile->get_title();
			}
			$result_output .= PHP_EOL . '    ' . $result_object->get_arguments_description();
		}
		if($result_object->test_profile->get_result_scale() != null)
		{
			$scale_line = '    ' . $result_object->test_profile->get_result_scale();
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
				$result_line = '    ' . $buffer_item->get_result_identifier() . ' ';
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
						$whisker_bottom = pts_math::find_percentile($values, 0.02);
						$whisker_top = pts_math::find_percentile($values, 0.98);
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

						$box_left = round((pts_math::find_percentile($values, 0.25) / $max_value) * $box_plot_size);
						$box_middle = round((pts_math::find_percentile($values, 0.5) / $max_value) * $box_plot_size);
						$box_right = round((pts_math::find_percentile($values, 0.75) / $max_value) * $box_plot_size);
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
					$result_line .= str_repeat('=', max(0, round(($val / $max_value) * ($terminal_width - $current_line_length))));
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
	public static function result_file_to_detailed_html_table(&$result_file, $grid_class = 'grid', $extra_attributes = null)
	{
		$table = array();
		$systems = array_merge(array(' '), $result_file->get_system_identifiers());
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

			$hib = $ro->test_profile->get_result_proportion() == 'HIB';
			$row[0] = '<span><strong><a href="#r-' . $ro->get_comparison_hash(true, false) . '">' . $ro->test_profile->get_title() . '</a></strong><br />' . $ro->get_arguments_description() . ' (' . $ro->test_profile->get_result_scale() . ' ' . ($hib ? '&uarr;' : '&darr;') . ' )</span>';

			$best = $ro->get_result_first(false);
			$worst = $ro->get_result_last(false);

			$normalize_against = 0;
			if(isset($extra_attributes['highlight_graph_values']) && is_array($extra_attributes['highlight_graph_values']) && count($extra_attributes['highlight_graph_values']) == 1)
			{
				$normalize_against = $ro->get_result_value_from_name($extra_attributes['highlight_graph_values'][0]);
			}
			if($normalize_against == 0)
			{
				$normalize_against = $best;
			}

			foreach($ro->test_result_buffer->get_buffer_items() as $index => $buffer_item)
			{
				$identifier = $buffer_item->get_result_identifier();
				$value = $buffer_item->get_result_value();

				if(($x = array_search($identifier, $systems)) !== false)
				{
					if($value == $best)
					{
						$style = ' style="color: green;"';
					}
					else if($value == $worst)
					{
						$style = ' style="color: red;"';
					}
					else
					{
						$style = null;
					}

					if($value > 1000)
					{
						$value = round($value);
					}

					if($value == 0)
					{
						continue;
					}

					$row[$x] = '<strong' . $style. '>' . $value . '</strong>';
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

		if($geo = pts_result_file_analyzer::generate_geometric_mean_result($result_file))
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
		$pdf->Ln(15);
		//$pdf->WriteText('This file was automatically generated via the Phoronix Test Suite benchmarking software.', 'I');

		$pdf->AddPage();
		//$pdf->Ln(15);

		$pdf->SetSubject($result_file->get_title() . ' Benchmarks');
		//$pdf->SetKeywords(implode(', ', $identifiers));

		$pdf->WriteHeader('Test Systems:');
		$systems = $result_file->get_systems();
		for($i = 0; $i < count($systems); $i++)
		{
			$pdf->Ln(5);
			$pdf->WriteMiniHeader($systems[$i]->get_identifier());
			if(isset($systems[($i + 1)]) && $systems[($i + 1)]->get_hardware() == $systems[$i]->get_hardware() && $systems[($i + 1)]->get_software() == $systems[$i]->get_software())
			{
				continue;
			}

			$pdf->WriteText($systems[$i]->get_hardware());
			$pdf->WriteText($systems[$i]->get_software());

			$attributes = array();
			pts_result_file_analyzer::system_to_note_array($systems[$i], $attributes);
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
		foreach($result_file->get_result_objects() as $ro)
		{
			if($ro->test_profile->get_display_format() != 'BAR_GRAPH')
			{
				continue;
			}

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

					if($best != -1)
					{
						$extra_rows[0][0] = 'Normalized';
						$extra_rows[0][$x] = round(($hib ? ($value / $best) : ($best / $value)) * 100, 2) . '%';
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
		$pdf->ResultTable($columns, $table_data, $table_data_hints);

		$pdf->AddPage();
		$placement = 1;
		$i = 0;
		foreach($result_file->get_result_objects() as $key => $result_object)
		{
			$graph = pts_render::render_graph_process($result_object, $result_file, false, $extra_attributes);
			if($graph == false)
			{
				continue;
			}

			$graph->renderGraph();
			$tmp_file = sys_get_temp_dir() . '/' . microtime() . rand(0, 999) . '.png';
			$output = $graph->svg_dom->output($tmp_file);
			if(!is_file($tmp_file))
			{
				continue;
			}
			$pdf->Ln(4);
			$pdf->Image($tmp_file);
			unlink($tmp_file);

			if($placement == 2 || $result_object->test_result_buffer->get_count() > 12)
			{
				$placement = 0;
				//$pdf->AddPage();
			}
			$placement++;
			$i++;
		}
		$pdf->WriteText('This file was automatically generated via the Phoronix Test Suite benchmarking software on ' . date('l, j F Y H:i') . '.', 'I');
		ob_get_clean();
		$pdf->Output($dest, $output_name);
	}
}

?>
