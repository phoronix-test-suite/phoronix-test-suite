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

class pts_result_file_analyzer
{
	public static function generate_geometric_mean_result($result_file, $do_sort = false)
	{
		$results = array();
		$system_count = $result_file->get_system_count();
		foreach($result_file->get_result_objects() as $result)
		{
			if($result->test_profile->get_identifier() == null || $result->test_profile->get_display_format() != 'BAR_GRAPH' || $system_count > $result->test_result_buffer->get_count())
			{
				// Skip data where it's not a proper test, not a singular data value, or not all systems ran within the result file
				continue;
			}

			foreach($result->test_result_buffer->get_buffer_items() as $buffer_item)
			{
				$r = $buffer_item->get_result_value();
				if(!is_numeric($r) || $r == 0)
				{
					continue;
				}
				if($result->test_profile->get_result_proportion() == 'LIB')
				{
					// convert to HIB
					$r = (1 / $r) * 100;
				}

				$ri = $buffer_item->get_result_identifier();

				if(!isset($results[$ri]))
				{
					$results[$ri] = array();
				}
				$results[$ri][] = $r;
			}
		}

		foreach($results as $identifier => $values)
		{
			if(count($values) < 4)
			{
				// If small result file with not a lot of data, don't bother showing...
				unset($results[$identifier]);
			}
		}

		if(!empty($results))
		{
			$test_profile = new pts_test_profile();
			$test_result = new pts_test_result($test_profile);
			$test_result->test_profile->set_test_title('Geometric Mean Of All Test Results');
			$test_result->test_profile->set_identifier(null);
			$test_result->test_profile->set_version(null);
			$test_result->test_profile->set_result_proportion(null);
			$test_result->test_profile->set_display_format('BAR_GRAPH');
			$test_result->test_profile->set_result_scale('Geometric Mean');
			$test_result->test_profile->set_result_proportion('HIB');
			$test_result->set_used_arguments_description('Result Composite');
			$test_result->set_used_arguments('Geometric-Mean');
			$test_result->test_result_buffer = new pts_test_result_buffer();
			foreach($results as $identifier => $values)
			{
				$values = pts_math::geometric_mean($values);
				$test_result->test_result_buffer->add_test_result($identifier, pts_math::set_precision($values, 3));
			}

			if(!$result_file->is_multi_way_comparison() || $do_sort)
			{
				$test_result->sort_results_by_performance();
				$test_result->test_result_buffer->buffer_values_reverse();
			}
			return $test_result;
		}

		return false;
	}
	public static function generate_geometric_mean_result_per_test($result_file, $do_sort = false, $selector = null)
	{
		$geo_results = array();
		$results = array();
		$system_count = $result_file->get_system_count();
		foreach($result_file->get_result_objects() as $result)
		{
			if(($selector == null && $result->test_profile->get_identifier() == null) || $result->test_profile->get_display_format() != 'BAR_GRAPH' || $system_count > $result->test_result_buffer->get_count())
			{
				// Skip data where it's not a proper test, not a singular data value, or not all systems ran within the result file
				continue;
			}
			if($selector != null && strpos($result->get_arguments_description(), $selector) === false && strpos($result->test_profile->get_title(), $selector) === false && strpos($result->test_profile->get_result_scale(), $selector) === false)
			{
				continue;
			}

			foreach($result->test_result_buffer->get_buffer_items() as $buffer_item)
			{
				$r = $buffer_item->get_result_value();
				if(!is_numeric($r) || $r == 0)
				{
					continue;
				}
				if($result->test_profile->get_result_proportion() == 'LIB')
				{
					// convert to HIB
					$r = (1 / $r) * 100;
				}

				$ri = $buffer_item->get_result_identifier();

				if(!isset($results[$result->test_profile->get_title()]))
				{
					$results[$result->test_profile->get_title()] = array();
				}
				if(!isset($results[$result->test_profile->get_title()][$ri]))
				{
					$results[$result->test_profile->get_title()][$ri] = array();
				}
				$results[$result->test_profile->get_title()][$ri][] = $r;
			}
		}

		if(count($results) < 3)
		{
			return array();
		}

		foreach($results as $test => $test_results)
		{
			foreach($test_results as $identifier => $values)
			{
				if(false && count($values) < 4)
				{
					// If small result file with not a lot of data, don't bother showing...
					unset($results[$test][$identifier]);
				}
			}

			if(empty($results[$test]))
			{
				unset($results[$test]);
			}
		}

		foreach($results as $test_title => $test_results)
		{
			$test_profile = new pts_test_profile();
			$test_result = new pts_test_result($test_profile);
			$test_result->test_profile->set_test_title($test_title);
			$test_result->test_profile->set_identifier(null);
			$test_result->test_profile->set_version(null);
			$test_result->test_profile->set_result_proportion(null);
			$test_result->test_profile->set_display_format('BAR_GRAPH');
			$test_result->test_profile->set_result_scale('Geometric Mean');
			$test_result->test_profile->set_result_proportion('HIB');
			$test_result->set_used_arguments_description(($selector ? $selector . ' ' : null) . 'Geometric Mean');
			$test_result->set_used_arguments('Geometric-Mean');
			$test_result->test_result_buffer = new pts_test_result_buffer();
			foreach($test_results as $identifier => $values)
			{
				$values = pts_math::geometric_mean($values);
				$test_result->test_result_buffer->add_test_result($identifier, pts_math::set_precision($values, 3));
			}

			if(!$result_file->is_multi_way_comparison() || $do_sort)
			{
				$test_result->sort_results_by_performance();
				$test_result->test_result_buffer->buffer_values_reverse();
			}
			$geo_results[] = $test_result;
		}

		return $geo_results;
	}
	public static function generate_harmonic_mean_result($result_file, $do_sort = false)
	{
		$results = array();
		$system_count = $result_file->get_system_count();
		foreach($result_file->get_result_objects() as $result)
		{
			if($result->test_profile->get_identifier() == null || $result->test_profile->get_display_format() != 'BAR_GRAPH' || $result->test_profile->get_result_proportion() == 'LIB' || $system_count > $result->test_result_buffer->get_count())
			{
				// Skip data where it's not a proper test, not a singular data value, or not all systems ran within the result file, or lower is better for results
				continue;
			}
			$rs = $result->test_profile->get_result_scale();
			if(strpos($rs, '/') === false && stripos($rs, ' per ') === false && stripos($rs, 'FPS') === false && stripos($rs, 'bps') === false && stripos($rs, 'iops') === false)
			{
				// Harmonic mean is relevant for tests of rates, MB/s, FPS, ns/day, etc.
				continue;
			}
			foreach($result->test_result_buffer->get_buffer_items() as $buffer_item)
			{
				$ri = $buffer_item->get_result_identifier();

				if(!isset($results[$rs][$ri]))
				{
					$results[$rs][$ri] = array();
				}
				$results[$rs][$ri][] = $buffer_item->get_result_value();
			}
		}

		foreach($results as $result_scale => $group)
		{
			foreach($group as $identifier => $values)
			{
				if(count($values) < 4)
				{
					// If small result file with not a lot of data, don't bother showing...
					unset($results[$result_scale][$identifier]);
				}
			}
		}

		if(!empty($results))
		{
			$test_results = array();
			foreach($results as $result_scale => $group)
			{
				$parsed = array();
				foreach($group as $identifier => $values)
				{
					$parsed[$identifier] = pts_math::harmonic_mean($values);
				}
				if(empty($parsed) || count($parsed) < 2)
				{
					continue;
				}

				$test_profile = new pts_test_profile();
				$test_result = new pts_test_result($test_profile);
				$test_result->test_profile->set_test_title('Harmonic Mean Of ' . $result_scale . ' Test Results');
				$test_result->test_profile->set_identifier(null);
				$test_result->test_profile->set_version(null);
				$test_result->test_profile->set_result_proportion(null);
				$test_result->test_profile->set_display_format('BAR_GRAPH');
				$test_result->test_profile->set_result_scale($result_scale);
				$test_result->test_profile->set_result_proportion('HIB');
				$test_result->set_used_arguments_description('Harmonic Mean');
				$test_result->set_used_arguments('Harmonic-Mean - ' . $result_scale);
				$test_result->test_result_buffer = new pts_test_result_buffer();
				foreach($parsed as $identifier => $values)
				{
					$test_result->test_result_buffer->add_test_result($identifier, pts_math::set_precision($values, 3));
				}
				if(!$result_file->is_multi_way_comparison() || $do_sort)
				{
					$test_result->sort_results_by_performance();
					$test_result->test_result_buffer->buffer_values_reverse();
				}
				$test_results[] = $test_result;
			}
			return $test_results;
		}

		return array();
	}
	public static function display_result_file_stats_pythagorean_means($result_file, $highlight_identifier = null)
	{
		$ret = null;
		foreach(pts_result_file_analyzer::generate_harmonic_mean_result($result_file, true) as $harmonic_mean_result)
		{
			$ret .= pts_result_file_output::test_result_to_text($harmonic_mean_result, pts_client::terminal_width(), true, $highlight_identifier, true) . PHP_EOL;
		}

		$geometric_mean = pts_result_file_analyzer::generate_geometric_mean_result($result_file, true);
		if($geometric_mean)
		{
			$ret .= pts_result_file_output::test_result_to_text($geometric_mean, pts_client::terminal_width(), true, $highlight_identifier, true);
		}

		if($ret != null)
		{
			$ret .= PHP_EOL;
		}

		return $ret;
	}
	public static function display_results_wins_losses($result_file, $highlight_result_identifier = null, $prepend_lines = '   ')
	{
		$output = null;
		$result_file_identifiers_count = $result_file->get_system_count();
		$wins = array();
		$losses = array();
		$tests_counted = 0;

		$possible_evaluate_result_count = 0;
		foreach($result_file->get_result_objects() as $result)
		{
			if($result->test_profile->get_identifier() == null)
			{
				continue;
			}
			$possible_evaluate_result_count++;
			if($result->test_result_buffer->get_count() < 2 || $result->test_result_buffer->get_count() < floor($result_file_identifiers_count / 2))
			{
				continue;
			}

			$tests_counted++;
			$winner = $result->get_result_first();
			$loser = $result->get_result_last();

			if(!isset($wins[$winner]))
			{
				$wins[$winner] = 1;
			}
			else
			{
				$wins[$winner]++;
			}

			if(!isset($losses[$loser]))
			{
				$losses[$loser] = 1;
			}
			else
			{
				$losses[$loser]++;
			}
		}

		if(empty($wins) || empty($losses))
		{
			return;
		}

		arsort($wins);
		arsort($losses);

		$table = array();
		$table[] = array(pts_client::cli_colored_text('WINS:', 'green', true), '', '');
		$highlight_row = -1;
		foreach($wins as $identifier => $count)
		{
			$table[] = array($identifier . ': ', $count . ' ', ' [' . pts_math::set_precision($count / $tests_counted * 100, 1) . '%]');

			if($highlight_result_identifier && $highlight_result_identifier == $identifier)
			{
				$highlight_row = count($table) - 1;
			}
		}
		$table[] = array('', '', '');
		$table[] = array(pts_client::cli_colored_text('LOSSES: ', 'red', true), '', '');
		$highlight_row = -1;
		foreach($losses as $identifier => $count)
		{
			$table[] = array($identifier . ': ', $count, ' [' . pts_math::set_precision($count / $tests_counted * 100, 1) . '%]');

			if($highlight_result_identifier && $highlight_result_identifier == $identifier)
			{
				$highlight_row = count($table) - 1;
			}
		}
		$output .= pts_user_io::display_text_table($table, $prepend_lines, 0, 0, false, $highlight_row) . PHP_EOL;
		$output .= $prepend_lines . pts_client::cli_colored_text('TESTS COUNTED: ', 'cyan', true) . ($tests_counted == $possible_evaluate_result_count ? $tests_counted : $tests_counted . ' of ' . $possible_evaluate_result_count) .  PHP_EOL;
		return $output;
	}
	public static function display_results_baseline_two_way_compare($result_file, $drop_flat_results = false, $border_table = false, $rich_text = false, $prepend_to_lines = null)
	{
		$table = array(array('Test', 'Configuration', 'Relative'));
		$color_rows = array();

		foreach($result_file->get_result_objects() as $ro)
		{
			if($ro->test_profile->get_display_format() != 'BAR_GRAPH')
			{
				continue;
			}
			$analyze_ro = clone $ro;
			if($drop_flat_results)
			{
				$analyze_ro->remove_unchanged_results(0.3);
			}

			$buffer_identifiers = $analyze_ro->test_result_buffer->get_identifiers();
			if(count($buffer_identifiers) != 2)
			{
				continue;
			}

			$analyze_ro->normalize_buffer_values(pts_arrays::first_element($buffer_identifiers));
			$result = $analyze_ro->test_result_buffer->get_value_from_identifier(pts_arrays::last_element($buffer_identifiers));
			if(empty($result))
			{
				continue;
			}
			$result = round($result, 3);
			if($drop_flat_results && $result == 1)
			{
				continue;
			}
			if($rich_text && ($result < 0.97 || $result > 1.03))
			{
				$color_rows[count($table)] = $result < 1 ? 'red' : 'green';
			}
			$table[] = array($analyze_ro->test_profile->get_identifier_base_name(), $analyze_ro->get_arguments_description_shortened(), $result);
		}

		$bold_row = $rich_text ? 0 : -1;
		return count($table) < 2 ? null : PHP_EOL . pts_user_io::display_text_table($table, $prepend_to_lines, 0, 0, $border_table, $bold_row, $color_rows);
	}
	public static function analyze_result_file_intent(&$result_file, &$flagged_results = -1, $return_all_changed_indexes = false)
	{
		$identifiers = array();
		$hw = array();
		$sw = array();
		foreach($result_file->get_systems() as $system)
		{
			$identifiers[] = $system->get_identifier();
			$hw[] = $system->get_hardware();
			$sw[] = $system->get_software();
		}

		if(count($identifiers) < 2)
		{
			// Not enough tests to be valid for anything
			return false;
		}

		foreach($identifiers as $identifier)
		{
			if(pts_strings::string_only_contains($identifier, pts_strings::CHAR_NUMERIC | pts_strings::CHAR_DASH | pts_strings::CHAR_SPACE))
			{
				// All the identifiers are just dates or other junk
				return false;
			}
		}

		$hw_unique = array_unique($hw);
		$sw_unique = array_unique($sw);
		$desc = false;

		if(count($hw_unique) == 1 && count($sw_unique) == 1)
		{
			// The hardware and software is maintained throughout the testing, so if there's a change in results its something we aren't monitoring
			// TODO XXX: Not sure this below check is needed anymore...
			if(true || (count($hw) > 2 && $result_file->get_test_count() != count($hw)))
			{
				$desc = array('Unknown', implode(', ', $identifiers));
			}
		}
		else if(count($sw_unique) == 1)
		{
			// The software is being maintained, but the hardware is being flipped out
			$rows = array();
			$data = array();
			pts_result_file_analyzer::system_components_to_table($data, $identifiers, $rows, $hw);
			pts_result_file_analyzer::compact_result_table_data($data, $identifiers, true);
			$desc = pts_result_file_analyzer::analyze_system_component_changes($data, $rows, array(
				array('Processor', 'Motherboard', 'Chipset', 'Audio', 'Network'), // Processor comparison
				array('Processor', 'Motherboard', 'Chipset', 'Network'), // Processor comparison
				array('Processor', 'Chipset', 'Graphics'),
				array('Processor', 'Graphics'),
				array('Processor', 'Chipset'), // Processor comparison - Sandy/Ivy Bridge for Intel will change CPU/chipset reporting when still using same mobo
				array('Motherboard', 'Chipset'), // Motherboard comparison
				array('Motherboard', 'Chipset', 'Audio', 'Network'), // Also a potential motherboard comparison
				array('Graphics', 'Audio'), // GPU comparison
				), $return_all_changed_indexes);
		}
		else if(count($hw_unique) == 1)
		{
			// The hardware is being maintained, but the software is being flipped out
			$rows = array();
			$data = array();
			pts_result_file_analyzer::system_components_to_table($data, $identifiers, $rows, $sw);
			pts_result_file_analyzer::compact_result_table_data($data, $identifiers, true);
			$desc = pts_result_file_analyzer::analyze_system_component_changes($data, $rows, array(
				array('Display Driver', 'OpenGL'), array('OpenGL'), array('Display Driver') // Graphics driver comparisons
				), $return_all_changed_indexes);
		}
		else
		{
			// Both software and hardware are being flipped out
			$rows = array();
			$data = array();
			pts_result_file_analyzer::system_components_to_table($data, $identifiers, $rows, $hw);
			pts_result_file_analyzer::system_components_to_table($data, $identifiers, $rows, $sw);
			pts_result_file_analyzer::compact_result_table_data($data, $identifiers, true);
			$desc = pts_result_file_analyzer::analyze_system_component_changes($data, $rows, array(
				array('Memory', 'Graphics', 'Display Driver', 'OpenGL'),
				array('Graphics', 'Display Driver', 'OpenGL', 'Vulkan'), array('Graphics', 'Kernel', 'Display Driver', 'OpenGL', 'Vulkan'), array('Graphics', 'Display Driver', 'OpenGL', 'OpenCL', 'Vulkan'), array('Graphics', 'Display Driver', 'OpenCL'), array('Graphics', 'Monitor', 'Kernel', 'Display Driver', 'OpenGL'), array('Graphics', 'Monitor', 'Display Driver', 'OpenGL'), array('Graphics', 'Kernel', 'Display Driver', 'OpenGL'), array('Graphics', 'Display Driver', 'OpenGL'), array('Graphics', 'OpenGL'), array('Graphics', 'Kernel'), array('Graphics', 'Display Driver') // All potential graphics comparisons
			), $return_all_changed_indexes);
		}

		if($desc)
		{
			if($flagged_results === -1)
			{
				return $desc;
			}
			else
			{
				$mark_results = self::locate_interesting_results($result_file, $flagged_results);
				return array($desc[0], $desc[1], $mark_results);
			}
		}

		return false;
	}
	public static function locate_interesting_results(&$result_file, &$flagged_results = null)
	{
		$result_objects = array();

		if(!is_array($flagged_results))
		{
			$flagged_results = array();
			$system_id_keys = null;
			$result_object_index = -1;
			pts_ResultFileTable::result_file_to_result_table($result_file, $system_id_keys, $result_object_index, $flagged_results);
		}

		if(count($flagged_results) > 0)
		{
			asort($flagged_results);
			$flagged_results = array_slice(array_keys($flagged_results), -6);
			$flag_delta_objects = $result_file->get_result_objects($flagged_results);

			for($i = 0; $i < count($flagged_results); $i++)
			{
				$result_objects[$flagged_results[$i]] = $flag_delta_objects[$i];
				unset($flag_delta_objects[$i]);
			}
		}

		return $result_objects;
	}
	public static function analyze_system_component_changes($data, $rows, $supported_combos = array(), $return_all_changed_indexes = false)
	{
		$max_combo_count = 2;
		foreach($supported_combos as $combo)
		{
			if(($c = count($combo)) > $max_combo_count)
			{
				$max_combo_count = $c;
			}
		}

		$total_width = count($data);
		$first_objects = array_shift($data);
		$comparison_good = true;
		$comparison_objects = array();

		foreach($first_objects as $i => $o)
		{
			if($o->get_attribute('spans_col') == $total_width)
			{
				unset($first_objects[$i]);
			}
		}

		if(count($first_objects) <= $max_combo_count && count($first_objects) > 0)
		{
			$changed_indexes = array_keys($first_objects);
			$comparison_objects[] = ($return_all_changed_indexes ? array_map('strval', $first_objects) : implode('/', $first_objects));

			if(count($changed_indexes) <= $max_combo_count)
			{
				while($comparison_good && ($this_identifier = array_shift($data)) !== null)
				{
					if(empty($this_identifier))
					{
						continue;
					}

					$this_keys = array_keys($this_identifier);
					$do_push = false;

					if($this_keys != $changed_indexes)
					{
						foreach($this_keys as &$change)
						{
							$change = $rows[$change];
						}

						if(!in_array($this_keys, $supported_combos) && (count($this_keys) > 1 || array_search($this_keys[0], $supported_combos[0]) === false))
						{
							$comparison_good = false;
						}
						else
						{
							$do_push = true;
						}
					}
					else
					{
						$do_push = true;
					}

					if($do_push)
					{
						$comparison_objects[] = ($return_all_changed_indexes ? array_map('strval', $this_identifier) : implode('/', $this_identifier));
					}
				}
			}
			else
			{
				$comparison_good = false;
			}

			if($comparison_good)
			{
				$new_index = array();
				foreach($changed_indexes as &$change)
				{
					$new_index[$change] = $rows[$change];
				}
				$changed_indexes = $new_index;

				if(count($changed_indexes) == 1 || in_array(array_values($changed_indexes), $supported_combos))
				{
					if($return_all_changed_indexes == false)
					{
						$comparison_objects = implode(', ', $comparison_objects);
					}

					return array(($return_all_changed_indexes ? $changed_indexes : array_shift($changed_indexes)), $comparison_objects);
				}
			}
		}

		return false;
	}
	public static function system_components_to_table(&$table_data, &$columns, &$rows, $add_components)
	{
		$col_pos = 0;

		foreach($add_components as $info_string)
		{
			if(isset($columns[$col_pos]))
			{
				if(!isset($table_data[$columns[$col_pos]]))
				{
					$table_data[$columns[$col_pos]] = array();
				}

				foreach(explode(', ', $info_string) as $component)
				{
					$c_pos = strpos($component, ': ');

					if($c_pos !== false)
					{
						$index = substr($component, 0, $c_pos);
						$value = substr($component, ($c_pos + 2));

						if(($r_i = array_search($index, $rows)) === false)
						{
							$rows[] = $index;
							$r_i = count($rows) - 1;
						}
						$table_data[$columns[$col_pos]][$r_i] = self::system_value_to_ir_value($value, $index);
					}
				}
			}
			$col_pos++;
		}
	}
	public static function system_component_string_to_array($components, $do_check = false)
	{
		$component_r = array();
		$components = explode(', ', $components);

		foreach($components as &$component)
		{
			$component = explode(': ', $component);

			if(count($component) >= 2 && ($do_check == false || in_array($component[0], $do_check)))
			{
				$component_r[$component[0]] = $component[1];
			}
		}

		return $component_r;
	}
	public static function system_component_string_to_html($components)
	{
		$components = self::system_component_string_to_array($components);

		foreach($components as $type => &$component)
		{
			$component = self::system_value_to_ir_value($component, $type);
			$type = '<strong>' . $type . '</strong>';

			if(($href = $component->get_attribute('href')) != false)
			{
				$component = '<a href="' . $href . '">' . $component->get_value() . '</a>';
			}
			else
			{
				$component = $component->get_value();
			}

			$component = $type . ': ' . $component;
		}

		return implode(', ', $components);
	}
	public static function system_value_to_ir_value($value, $index)
	{
		// TODO XXX: Move this logic off to OpenBenchmarking.org script
		/*
		!in_array($index, array('Memory', 'System Memory', 'Desktop', 'Screen Resolution', 'System Layer')) &&
			$search_break_characters = array('@', '(', '/', '+', '[', '<', '*', '"');
			for($i = 0, $x = strlen($value); $i < $x; $i++)
			{
				if(in_array($value[$i], $search_break_characters))
				{
					$value = substr($value, 0, $i);
					break;
				}
			}
		*/
		$ir = new pts_graph_ir_value($value);

		if($value != 'Unknown' && $value != null)
		{
			$ir->set_attribute('href', 'http://openbenchmarking.org/s/' . $value);
		}

		return $ir;
	}
	public static function compact_result_table_data(&$table_data, &$columns, $unset_emptied_values = false)
	{
		// Let's try to compact the data
		$c_count = count($table_data);
		$c_index = 0;

		foreach(array_keys($table_data) as $c)
		{
			foreach(array_keys($table_data[$c]) as $r)
			{
				// Find next-to duplicates
				$match_to = &$table_data[$c][$r];

				if(($match_to instanceof pts_graph_ir_value) == false)
				{
					if($unset_emptied_values)
					{
						unset($table_data[$c][$r]);
					}

					continue;
				}

				$spans = 1;
				for($i = ($c_index + 1); $i < $c_count; $i++)
				{
					$id = $columns[$i];

					if(isset($table_data[$id][$r]) && $match_to == $table_data[$id][$r])
					{
						$spans++;

						if($unset_emptied_values)
						{
							unset($table_data[$id][$r]);
						}
						else
						{
							$table_data[$id][$r] = null;
						}
					}
					else
					{
						break;
					}
				}

				if($spans > 1)
				{
					$match_to->set_attribute('spans_col', $spans);
					$match_to->set_attribute('highlight', $spans < count($columns));
				}
			}

			$c_index++;
		}
	}
	public static function system_to_note_array(&$result_file_system, &$system_attributes)
	{
		$json = $result_file_system->get_json();
		$notes_string = $result_file_system->get_notes();
		$identifier = $result_file_system->get_identifier();

		if(isset($json['kernel-parameters']) && $json['kernel-parameters'] != null)
		{
			$system_attributes['Kernel'][$identifier] = $json['kernel-parameters'];
			unset($json['kernel-parameters']);
		}
		if(isset($json['environment-variables']) && $json['environment-variables'] != null)
		{
			$system_attributes['Environment'][$identifier] = $json['environment-variables'];
			unset($json['environment-variables']);
		}
		if(isset($json['compiler-configuration']) && $json['compiler-configuration'] != null)
		{
			$system_attributes['Compiler'][$identifier] = $json['compiler-configuration'];
			unset($json['compiler-configuration']);
		}
		if(isset($json['disk-scheduler']) && isset($json['disk-mount-options']))
		{
			$system_attributes['Disk'][$identifier] = $json['disk-scheduler'] . ' / ' . $json['disk-mount-options'];
			if(isset($json['disk-details']) && !empty($json['disk-details']))
			{
				$system_attributes['Disk'][$identifier] .= ' / ' . $json['disk-details'];
				unset($json['disk-details']);
			}
			unset($json['disk-scheduler']);
			unset($json['disk-mount-options']);
		}
		if(isset($json['cpu-scaling-governor']))
		{
			$system_attributes['Processor'][$identifier] = 'Scaling Governor: ' . $json['cpu-scaling-governor'];
			unset($json['cpu-scaling-governor']);
		}
		if(isset($json['cpu-smt']))
		{
			$system_attributes['Processor'][$identifier] = 'SMT (threads per core): ' . $json['cpu-smt'];
			unset($json['cpu-smt']);
		}
		if(isset($json['graphics-2d-acceleration']) || isset($json['graphics-aa']) || isset($json['graphics-af']))
		{
			$report = array();
			foreach(array('graphics-2d-acceleration', 'graphics-aa', 'graphics-af') as $check)
			{
				if(isset($json[$check]) && !empty($json[$check]))
				{
					$report[] = $json[$check];
					unset($json[$check]);
				}
			}
			$system_attributes['Graphics'][$identifier] = implode(' - ' , $report);
		}
		if(isset($json['graphics-compute-cores']))
		{
			$system_attributes['OpenCL'][$identifier] = 'GPU Compute Cores: ' . $json['graphics-compute-cores'];
			unset($json['graphics-compute-cores']);
		}
		if(!empty($notes_string))
		{
			$system_attributes['System'][$identifier] = $notes_string;
		}
		if(!empty($json) && is_array($json))
		{
			foreach($json as $key => $value)
			{
				if(!empty($value))
				{
					$system_attributes[ucwords(str_replace(array('_', '-'), ' ', $key))][$identifier] = $value;
				}
				unset($json[$key]);
			}
		}
	}
	public static function system_notes_to_formatted_array(&$result_file)
	{
		$system_attributes = array();

		foreach($result_file->get_systems() as $s)
		{
			pts_result_file_analyzer::system_to_note_array($s, $system_attributes);
		}

		if(isset($system_attributes['compiler']) && count($system_attributes['compiler']) == 1 && ($result_file->get_system_count() > 1 && ($intent = pts_result_file_analyzer::analyze_result_file_intent($result_file, $intent, true)) && isset($intent[0]) && is_array($intent[0]) && array_shift($intent[0]) == 'Compiler') == false)
		{
			// Only show compiler strings when it's meaningful (since they tend to be long strings)
			unset($system_attributes['compiler']);
		}

		return $system_attributes;
	}
}

?>
