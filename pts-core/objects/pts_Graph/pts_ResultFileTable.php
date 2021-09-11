<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2019, Phoronix Media
	Copyright (C) 2009 - 2019, Michael Larabel
	pts_ResultFileTable.php: The result file table object

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

class pts_ResultFileTable extends pts_Table
{
	public $flagged_results = array();

	public function __construct(&$result_file, $system_id_keys = null, $result_object_index = -1, $extra_attributes = null)
	{
		list($rows, $columns, $table_data) = pts_ResultFileTable::result_file_to_result_table($result_file, $system_id_keys, $result_object_index, $this->flagged_results, $extra_attributes);
		parent::__construct($rows, $columns, $table_data, $result_file);
		$this->result_object_index = $result_object_index;

		if($result_object_index == -1)
		{
			$this->i['graph_title'] = $result_file->get_title();
		}
		else
		{
			$result_object = $result_file->get_result_objects($result_object_index);
			if(isset($result_object[0]))
			{
				$this->i['graph_title'] = $result_object[0]->test_profile->get_title();
				$this->graph_sub_titles[] = $result_object[0]->get_arguments_description();
			}
		}

		// where to start the table values
		$this->longest_row_identifier = null;
		$longest_row_title_length = 0;
		foreach($this->rows as $result_test)
		{
			if(($len = strlen($result_test)) > $longest_row_title_length)
			{
				$this->longest_row_identifier = $result_test;
				$longest_row_title_length = $len;
			}
		}
		$this->column_heading_vertical = false;
		//$this->longest_column_identifier = max(pts_strings::find_longest_string($this->columns), pts_strings::find_longest_string($result_file->get_system_identifiers()));
	}
	public static function result_file_to_result_table(&$result_file, &$system_id_keys = null, &$result_object_index = -1, &$flag_delta_results = false, $extra_attributes = null)
	{
		$result_table = array();
		$result_tests = array();
		$result_counter = 0;

		foreach($result_file->get_system_identifiers() as $sys_identifier)
		{
			$result_table[$sys_identifier] = null;
		}

		foreach($result_file->get_result_objects($result_object_index) as $ri => $result_object)
		{
			if($result_object->test_profile->get_identifier() == null)
			{
				continue;
			}

			if($extra_attributes != null)
			{
				if(isset($extra_attributes['reverse_result_buffer']))
				{
					$result_object->test_result_buffer->buffer_values_reverse();
				}
				if(isset($extra_attributes['normalize_result_buffer']))
				{
					if(isset($extra_attributes['highlight_graph_values']) && is_array($extra_attributes['highlight_graph_values']) && count($extra_attributes['highlight_graph_values']) == 1)
					{
						$normalize_against = $extra_attributes['highlight_graph_values'][0];
					}
					else
					{
						$normalize_against = false;
					}

					$result_object->normalize_buffer_values($normalize_against);
				}
			}

			if($result_object_index != -1)
			{
				if(is_array($result_object_index))
				{
					$result_tests[$result_counter] = new pts_graph_ir_value($result_object->get_arguments_description());
				}
				else
				{
					$result_tests[$result_counter] = new pts_graph_ir_value('Results');
				}
			}
			else
			{
				if($result_object->test_profile->get_identifier() != null)
				{
					$result_tests[$result_counter] = new pts_graph_ir_value($result_object->test_profile->get_identifier_base_name() . ': ' . $result_object->get_arguments_description_shortened(false));
					$result_tests[$result_counter]->set_attribute('title', $result_object->get_arguments_description());
					$result_tests[$result_counter]->set_attribute('href', 'https://openbenchmarking.org/test/' . $result_object->test_profile->get_identifier());
				}
				else if($result_object->test_profile->get_title() != null)
				{
					$result_tests[$result_counter] = new pts_graph_ir_value($result_object->test_profile->get_title() . ': ' . $result_object->get_arguments_description());
				}
			}

			if(false && $result_object->test_profile->get_identifier() == null)
			{
				if($result_object->test_profile->get_display_format() == 'BAR_GRAPH')
				{
					//$result_tests[$result_counter]->set_attribute('alert', true);
					foreach($result_object->test_result_buffer->get_buffer_items() as $index => $buffer_item)
					{
						$identifier = $buffer_item->get_result_identifier();
						$value = $buffer_item->get_result_value();
						$result_table[$identifier][$result_counter] = new pts_graph_ir_value($value, array('alert' => true));
					}
					$result_counter++;
				}
				continue;
			}

			$values_in_buffer = $result_object->test_result_buffer->get_values();
			$has_numeric = false;
			foreach($values_in_buffer as $i => $vb)
			{
				if(is_numeric($vb))
				{
					$has_numeric = true;
					break;
				}
				else
				{
					unset($values_in_buffer[$i]);
				}
			}
			if(!$has_numeric)
			{
				continue;
			}

			switch($result_object->test_profile->get_display_format())
			{
				case 'BAR_GRAPH':
					$best_value = 0;
					$worst_value = 0;

					if(!defined('PHOROMATIC_TRACKER') && count($result_object->test_result_buffer->get_values()) > 1)
					{
						switch($result_object->test_profile->get_result_proportion())
						{
							case 'HIB':
								$best_value = max($result_object->test_result_buffer->get_values());
								$worst_value = min($result_object->test_result_buffer->get_values());
								break;
							case 'LIB':
								$best_value = min($result_object->test_result_buffer->get_values());
								$worst_value = max($result_object->test_result_buffer->get_values());
								break;
						}
					}

					$prev_value = 0;
					$prev_identifier = null;
					$prev_identifier_0 = null;

					sort($values_in_buffer);
					$min_value_in_buffer = $values_in_buffer[0];

					if(empty($min_value_in_buffer))
					{
						// Go through the values until something not 0, otherwise down in the code will be a divide by zero
						for($i = 1; $i < count($values_in_buffer) && empty($min_value_in_buffer); $i++)
						{
							$min_value_in_buffer = $values_in_buffer[$i];
						}
					}

					$max_value_in_buffer = $values_in_buffer[(count($values_in_buffer) - 1)];

					foreach($result_object->test_result_buffer->get_buffer_items() as $index => $buffer_item)
					{
						$identifier = $buffer_item->get_result_identifier();
						$value = $buffer_item->get_result_value();

						if(!is_numeric($value))
						{
							continue;
						}

						$raw_values = pts_strings::colon_explode($buffer_item->get_result_raw());
						$percent_std = pts_math::set_precision(pts_math::percent_standard_deviation($raw_values), 2);
						$std_error = pts_math::set_precision(pts_math::standard_error($raw_values), 2);
						$delta = 0;

						if(defined('PHOROMATIC_TRACKER'))
						{
							$identifier_r = pts_strings::colon_explode($identifier);

							if($identifier_r[0] == $prev_identifier_0 && $prev_value != 0)
							{
								$delta = pts_math::set_precision(abs(1 - ($value / $prev_value)), 4);

								if($delta > 0.02 && $delta > pts_math::standard_deviation($raw_values))
								{
									switch($result_object->test_profile->get_result_proportion())
									{
										case 'HIB':
											if($value < $prev_value)
											{
												$delta = 0 - $delta;
											}
											break;
										case 'LIB':
											if($value > $prev_value)
											{
												$delta = 0 - $delta;
											}
											break;
									}
								}
								else
								{
									$delta = 0;
								}
							}

							$prev_identifier_0 = $identifier_r[0];
							$highlight = false;
							$alert = false;
						}
						else
						{
							if($result_file->is_multi_way_comparison())
							{
								// TODO: make it work better for highlighting multiple winners in multi-way comparisons
								$highlight = false;
								$alert = false;

								// TODO: get this working right
								if(false && $index % 2 == 1 && $prev_value != 0)
								{
									switch($result_object->test_profile->get_result_proportion())
									{
										case 'HIB':
											if($value > $prev_value)
											{
												$highlight = true;
											}
											else
											{
												$result_table[$prev_identifier][$result_counter]->set_attribute('highlight', true);
												$result_table[$prev_identifier][$result_counter]->set_attribute('delta', -1);
											}
											break;
										case 'LIB':
											if($value < $prev_value)
											{
												$highlight = true;
											}
											else
											{
												$result_table[$prev_identifier][$result_counter]->set_attribute('highlight', true);
												$result_table[$prev_identifier][$result_counter]->set_attribute('delta', -1);
											}
											break;
									}
								}
							}
							else
							{
								$alert = $worst_value == $value;
								$highlight = $best_value == $value;
							}

							if($min_value_in_buffer != $max_value_in_buffer)
							{
								switch($result_object->test_profile->get_result_proportion())
								{
									case 'HIB':
										$delta = pts_math::set_precision($value / $min_value_in_buffer, 2);
										break;
									case 'LIB':
										$delta = pts_math::set_precision(1 - ($value / $max_value_in_buffer) + 1, 2);
										break;
								}
							}
						}

						$attributes = array(
							'std_percent' => $percent_std,
							'std_error' => $std_error,
							'delta' => $delta,
							'highlight' => $highlight,
							'alert' => $alert
							);

						if($delta > $percent_std && $flag_delta_results !== false)
						{
							$flag_delta_results[$ri] = $delta;
						}

						$result_table[$identifier][$result_counter] = new pts_graph_ir_value($value, $attributes);
						$prev_identifier = $identifier;
						$prev_value = $value;
					}
					break;
				case 'LINE_GRAPH':
				case 'FILLED_LINE_GRAPH':
					$result_tests[$result_counter] = new pts_graph_ir_value($result_object->test_profile->get_title() . ' (Avg)');

					foreach($result_object->test_result_buffer->get_buffer_items() as $index => $buffer_item)
					{
						$identifier = $buffer_item->get_result_identifier();
						$values = pts_strings::comma_explode($buffer_item->get_result_value());
						$avg_value = pts_math::set_precision(pts_math::arithmetic_mean($values), 2);
						$result_table[$identifier][$result_counter] = new pts_graph_ir_value($avg_value);
					}
					break;
			}

			$result_counter++;
		}

		if($result_counter == 1)
		{
			// This should provide some additional information under normal modes
			$has_written_std = false;
			$has_written_diff = false;
			$has_written_error = false;

			foreach($result_table as $identifier => $info)
			{
				if(!isset($info[($result_counter - 1)]))
				{
					continue;
				}

				$std_percent = $info[($result_counter - 1)]->get_attribute('std_percent');
				$std_error = $info[($result_counter - 1)]->get_attribute('std_error');
				$delta = $info[($result_counter - 1)]->get_attribute('delta');

				if($delta != 0)
				{
					$result_table[$identifier][] = new pts_graph_ir_value($delta . 'x');
					$has_written_diff = true;
				}
				if($std_error != 0)
				{
					$result_table[$identifier][] = new pts_graph_ir_value($std_error);
					$has_written_error = true;
				}
				if($std_percent != 0)
				{
					$result_table[$identifier][] = new pts_graph_ir_value($std_percent . '%');
					$has_written_std = true;
				}
			}

			if($has_written_diff)
			{
				$result_tests[] = new pts_graph_ir_value('Difference');
			}
			if($has_written_error)
			{
				$result_tests[] = new pts_graph_ir_value('Standard Error');
			}
			if($has_written_std)
			{
				$result_tests[] = new pts_graph_ir_value('Standard Deviation');
			}
		}

		if(defined('PHOROMATIC_TRACKER'))
		{
			// Resort the results by SYSTEM, then date
			$systems_table = array();
			$sorted_table = array();

			foreach($result_table as $system_identifier => &$identifier_table)
			{
				$identifier = pts_strings::colon_explode($system_identifier);

				if(!isset($systems_table[$identifier[0]]))
				{
					$systems_table[$identifier[0]] = array();
				}

				$systems_table[$identifier[0]][$system_identifier] = $identifier_table;
			}

			$result_table = array();
			$result_systems = array();

			foreach($systems_table as &$group)
			{
				foreach($group as $identifier => $table)
				{
					$result_table[$identifier] = $table;

					$identifier = pts_strings::colon_explode($identifier);
					$show_id = isset($identifier[1]) ? $identifier[1] : $identifier[0];/*

					if($system_id_keys != null && ($s = array_search($identifier[0], $system_id_keys)) !== false)
					{
						$system_id = $s;
					}
					else
					{
						$system_id = null;
					}*/

					$result_systems[] = $show_id;
				}
			}
		}
		else
		{
			$result_systems = array();

			foreach(array_keys($result_table) as $id)
			{
				$result_systems[] = $id;
			}
		}

		return array($result_tests, $result_systems, $result_table);
	}
}

?>
