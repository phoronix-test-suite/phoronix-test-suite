<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2016, Phoronix Media
	Copyright (C) 2010 - 2016, Michael Larabel

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
				array('Graphics', 'Display Driver', 'OpenGL', 'Vulkan'), array('Graphics', 'Display Driver', 'OpenGL', 'OpenCL', 'Vulkan'), array('Graphics', 'Monitor', 'Kernel', 'Display Driver', 'OpenGL'), array('Graphics', 'Monitor', 'Display Driver', 'OpenGL'), array('Graphics', 'Kernel', 'Display Driver', 'OpenGL'), array('Graphics', 'Display Driver', 'OpenGL'), array('Graphics', 'OpenGL'), array('Graphics', 'Kernel'), array('Graphics', 'Display Driver') // All potential graphics comparisons
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
}

?>
