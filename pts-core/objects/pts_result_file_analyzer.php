<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2011, Phoronix Media
	Copyright (C) 2010 - 2011, Michael Larabel

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
	public static function analyze_result_file_intent(&$result_file, &$flagged_results = null)
	{
		$identifiers = $result_file->get_system_identifiers();

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

		$hw = $result_file->get_system_hardware();
		$hw_unique = array_unique($hw);
		$sw = $result_file->get_system_software();
		$sw_unique = array_unique($sw);
		$desc = null;

		//print_r($table_data);

		if(count($hw_unique) == 1 && count($sw_unique) == 1)
		{
			// The hardware and software is maintained throughout the testing, so if there's a change in results its something we aren't monitoring
			$desc = array('Unknown', implode(', ', $identifiers));
		}
		else if(count($sw_unique) == 1)
		{
			// The software is being maintained, but the hardware is being flipped out
			$rows = array();
			$data = array();
			pts_result_file_analyzer::system_components_to_table($data, $identifiers, $rows, $hw);
			pts_result_file_analyzer::compact_result_table_data($data, $identifiers, true);

			$desc = pts_result_file_analyzer::analyze_system_component_changes('hardware', $data, $rows, array(array('Motherboard', 'Chipset')));
		}
		else if(count($hw_unique) == 1)
		{
			// The hardware is being maintained, but the software is being flipped out
			$rows = array();
			$data = array();
			pts_result_file_analyzer::system_components_to_table($data, $identifiers, $rows, $sw);
			pts_result_file_analyzer::compact_result_table_data($data, $identifiers, true);

			$desc = pts_result_file_analyzer::analyze_system_component_changes('software', $data, $rows, array(array('Display Driver', 'OpenGL'), array('OpenGL'), array('Display Driver')));
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
	public static function analyze_system_component_changes($type, $data, $rows, $supported_combos = array())
	{
		$max_combo_count = 2;
		foreach($supported_combos as $combo)
		{
			if(($c = count($combo)) > $max_combo_count)
			{
				$max_combo_count = $c;
			}
		}

		$first_objects = array_shift($data);
		$comparison_good = true;
		$comparison_objects = array();

		foreach($first_objects as $i => $o)
		{
			if($o->get_attribute('spans_col'))
			{
				unset($first_objects[$i]);
			}
		}

		if(count($first_objects) <= $max_combo_count && count($first_objects) > 0)
		{
			array_push($comparison_objects, implode('/', $first_objects));

			$first_objects = array_shift($data);
			$changed_indexes = array_keys($first_objects);
			array_push($comparison_objects, implode('/', $first_objects));

			if(count($changed_indexes) <= $max_combo_count)
			{
				while($comparison_good && ($this_identifier = array_shift($data)) != null)
				{
					$this_keys = array_keys($this_identifier);

					if($this_keys != $changed_indexes)
					{
						foreach($this_keys as &$change)
						{
							$change = $rows[$change];
						}

						if(!in_array($this_keys, $supported_combos))
						{
							$comparison_good = false;
						}
					}
					else
					{
						array_push($comparison_objects, implode('/', $this_identifier));
					}
				}
			}
			else
			{
				$comparison_good = false;
			}

			if($comparison_good)
			{
				foreach($changed_indexes as &$change)
				{
					$change = $rows[$change];
				}

				if(count($changed_indexes) == 1 || in_array($changed_indexes, $supported_combos))
				{
					$r = implode(', ', $comparison_objects);
					$category = array_shift($changed_indexes);

					return array($category, $r);
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
						array_push($rows, $index);
						$r_i = count($rows) - 1;
					}
					$table_data[$columns[$col_pos]][$r_i] = self::system_value_to_ir_value($value, $index);
				}
			}
			$col_pos++;
		}
	}
	public static function system_component_string_to_html($components)
	{
		$components = explode(', ', $components);

		foreach($components as &$component)
		{
			$component = explode(': ', $component);

			if(count($component) == 2)
			{
				$component[1] = self::system_value_to_ir_value($component[1], $component[0]);
				$component[0] = '<strong>' . $component[0] . '</strong>';

				if(($href = $component[1]->get_attribute('href')) != false)
				{
					$component[1] = '<a href="' . $href . '">' . $component[1]->get_value() . '</a>';
				}
				else
				{
					$component[1] = $component[1]->get_value();
				}
			}

			$component = implode(': ', $component);
		}

		return implode(', ', $components);
	}
	protected static function system_value_to_ir_value($value, $index)
	{
		$ir = new pts_graph_ir_value($value);

		if(!in_array($index, array('Memory', 'Desktop', 'Screen Resolution')) && $value != 'Unknown')
		{
			$ir->set_attribute('href', 'http://openbenchmarking.org/s/' . pts_strings::system_category_to_openbenchmark_category($index) . '/' . pts_strings::trim_search_query($value));
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
			for($r = 0, $r_count = count($table_data[$c]); $r < $r_count; $r++)
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
	public static function result_file_to_csv(&$result_file)
	{
		$csv_output = null;
		$delimiter = ',';

		$csv_output .= $result_file->get_title() . PHP_EOL . PHP_EOL;

		$columns = $result_file->get_system_identifiers();
		$rows = array();
		$table_data = array();

		pts_result_file_analyzer::system_components_to_table($table_data, $columns, $rows, $result_file->get_system_hardware());
		pts_result_file_analyzer::system_components_to_table($table_data, $columns, $rows, $result_file->get_system_software());

		$csv_output .= ' ';

		foreach($columns as $column)
		{
			$csv_output .= $delimiter . '"' . $column . '"';
		}
		$csv_output .= PHP_EOL;

		foreach($rows as $i => $row)
		{
			$csv_output .= $row;

			foreach($columns as $column)
			{
				$csv_output .= $delimiter . $table_data[$column][$i];
			}

			$csv_output .= PHP_EOL;
		}

		$csv_output .= PHP_EOL;
		$csv_output .= ' ';

		foreach($columns as $column)
		{
			$csv_output .= $delimiter . '"' . $column . '"';
		}
		$csv_output .= PHP_EOL;

		foreach($result_file->get_result_objects() as $result_object)
		{
			$csv_output .= '"' . $result_object->test_profile->get_title() . ' - ' . $result_object->get_arguments_description() . '"';

			foreach($result_object->test_result_buffer->get_values() as $value)
			{
				$csv_output .= $delimiter . $value;
			}
			$csv_output .= PHP_EOL;
		}
		$csv_output .= PHP_EOL;

		return $csv_output;
	}
}

?>
