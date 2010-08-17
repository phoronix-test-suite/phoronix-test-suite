<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel

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

class pts_result_file
{
	private $result_objects = null;
	private $xml_parser = null;
	private $extra_attributes = null;
	private $is_multi_way_inverted = false;

	public function __construct($result_file)
	{
		$this->xml_parser = new pts_results_tandem_XmlReader($result_file);
		$this->extra_attributes = array();
	}
	public function read_extra_attribute($key)
	{
		return isset($this->extra_attributes[$key]) ? $this->extra_attributes[$key] : false;
	}
	public function set_extra_attribute($key, $value)
	{
		$this->extra_attributes[$key] = $value;
	}
	public function get_system_hardware()
	{
		return $this->xml_parser->getXMLArrayValues(P_RESULTS_SYSTEM_HARDWARE);
	}
	public function get_system_software()
	{
		return $this->xml_parser->getXMLArrayValues(P_RESULTS_SYSTEM_SOFTWARE);
	}
	public function get_system_author()
	{
		return $this->xml_parser->getXMLArrayValues(P_RESULTS_SYSTEM_AUTHOR);
	}
	public function get_system_notes()
	{
		return $this->xml_parser->getXMLArrayValues(P_RESULTS_SYSTEM_NOTES);
	}
	public function get_system_date()
	{
		return $this->xml_parser->getXMLArrayValues(P_RESULTS_SYSTEM_DATE);
	}
	public function get_system_pts_version()
	{
		return $this->xml_parser->getXMLArrayValues(P_RESULTS_SYSTEM_PTSVERSION);
	}
	public function get_system_identifiers()
	{
		return $this->xml_parser->getXMLArrayValues(P_RESULTS_SYSTEM_IDENTIFIERS);
	}
	public function get_suite_name()
	{
		return $this->xml_parser->getXMLValue(P_RESULTS_SUITE_NAME);
	}
	public function get_title()
	{
		return $this->xml_parser->getXMLValue(P_RESULTS_SUITE_TITLE);
	}
	public function get_suite_version()
	{
		return $this->xml_parser->getXMLValue(P_RESULTS_SUITE_VERSION);
	}
	public function get_suite_description()
	{
		return $this->xml_parser->getXMLValue(P_RESULTS_SUITE_DESCRIPTION);
	}
	public function get_suite_extensions()
	{
		return $this->xml_parser->getXMLValue(P_RESULTS_SUITE_EXTENSIONS);
	}
	public function get_suite_properties()
	{
		return $this->xml_parser->getXMLValue(P_RESULTS_SUITE_PROPERTIES);
	}
	public function get_suite_type()
	{
		return $this->xml_parser->getXMLValue(P_RESULTS_SUITE_TYPE);
	}
	public function get_test_titles()
	{
		return $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_TITLE);
	}
	public function get_unique_test_titles()
	{
		return array_unique($this->get_test_titles());
	}
	public function get_test_count()
	{
		return count($this->get_test_titles());
	}
	public function get_result_object_hashes()
	{
		$object_hashes = array();

		if($this->result_objects != null)
		{
			foreach($this->result_objects as $result_object)
			{
				array_push($object_hashes, $result_object->get_comparison_hash());
			}
		}
		else
		{
			$results_name = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_TITLE);
			$results_arguments = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_ARGUMENTS);
			$results_attributes = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_ATTRIBUTES);
			$results_version = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_VERSION);

			for($i = 0; $i < count($results_name); $i++)
			{
				array_push($object_hashes, pts_test_profile::generate_comparison_hash($results_name[$i], $results_arguments[$i], $results_attributes[$i], $results_version[$i]));
			}
		}

		return $object_hashes;
	}
	public function is_results_tracker()
	{
		// If there are more than five results and the only changes in the system identifier names are numeric changes, assume it's a tracker
		// i.e. different dates or different versions of a package being tested

		static $is_tracker = -1;

		if($is_tracker === -1)
		{
			$identifiers = $this->get_system_identifiers();

			foreach($identifiers as &$identifier)
			{
				$identifier = pts_strings::remove_from_string($identifier, TYPE_CHAR_NUMERIC);
			}

			$is_tracker = count($identifiers) > 5 && count(array_unique($identifiers)) == 1;
		}

		return $is_tracker;
	}
	public function is_multi_way_comparison()
	{
		static $is_multi_way = -1;

		if($is_multi_way === -1)
		{
			$systems = array();
			$targets = array();
			$is_multi_way = true;
			$prev_system = null;

			foreach($this->get_system_identifiers() as $identifier)
			{
				if(strpos($identifier, ": ") == false)
				{
					$is_multi_way = false;
					break;
				}

				$identifier_r = pts_strings::trim_explode(': ', $identifier);

				if(count($identifier_r) != 2)
				{
					$is_multi_way = false;
					break;
				}

				if($prev_system != null && $prev_system != $identifier_r[0] && isset($systems[$identifier_r[0]]))
				{
					// The results aren't ordered
					$is_multi_way = false;
					break;
				}
				$prev_system = $identifier_r[0];

				$systems[$identifier_r[0]] = !isset($systems[$identifier_r[0]]) ? 1 : $systems[$identifier_r[0]] + 1;
				$targets[$identifier_r[1]] = !isset($targets[$identifier_r[1]]) ? 1 : $targets[$identifier_r[1]] + 1;	
			}

			if($is_multi_way)
			{
				if(count($systems) < 3 && count($systems) != count($targets))
				{
					$is_multi_way = false;
				}
			}

			if($is_multi_way)
			{
				$targets_count = count($targets);
				$systems_count = count($systems);

				if($targets_count > $systems_count)
				{
					$this->is_multi_way_inverted = true;
				}
				else
				{
					$hardware = array_unique($this->get_system_hardware());
					//$software = array_unique($this->get_system_software());

					if($targets_count != $systems_count && count($hardware) == $systems_count)
					{
						$this->is_multi_way_inverted = true;
					}

				}
			}

			// TODO: figure out what else is needed to reasonably determine if the result file is a multi-way comparison
		}

		return $is_multi_way;
	}
	public function is_multi_way_inverted()
	{
		return $this->is_multi_way_inverted;
	}
	public function get_result_table($system_id_keys = null, $result_object_index = -1)
	{
		$result_table = array();
		$result_tests = array();
		$max_value = 0;
		$result_counter = 0;

		foreach($this->get_system_identifiers() as $sys_identifier)
		{
			$result_table[$sys_identifier] = null;
		}

		foreach($this->get_result_objects($result_object_index) as $result_object)
		{
			$result_tests[$result_counter][0] = $result_object->test_result->test_profile->get_test_title();
			$result_tests[$result_counter][1] = $result_object->test_result->get_used_arguments_description();

			if($result_object_index != -1)
			{
				if(is_array($result_object_index))
				{
					$result_tests[$result_counter][0] = $result_tests[$result_counter][1];
				}
				else
				{
					$result_tests[$result_counter][0] = "Results";
				}
				//$result_tests[$result_counter][0] .= ': ' . $result_tests[$result_counter][1];
			}

			switch($result_object->test_result->test_profile->get_result_format())
			{
				case "BAR_GRAPH":
					$best_value = 0;

					if(!defined("PHOROMATIC_TRACKER") && count($result_object->get_result_buffer()->get_values()) > 1)
					{
						switch($result_object->test_result->test_profile->get_result_proportion())
						{
							case "HIB":
								$best_value = max($result_object->get_result_buffer()->get_values());
								break;
							case "LIB":
								$best_value = min($result_object->get_result_buffer()->get_values());
								break;
						}
					}

					$prev_value = 0;
					$prev_identifier = null;
					$prev_identifier_0 = null;

					$values_in_buffer = $result_object->get_result_buffer()->get_values();
					sort($values_in_buffer);
					$min_value_in_buffer = $values_in_buffer[0];
					$max_value_in_buffer = $values_in_buffer[(count($values_in_buffer) - 1)];

					foreach($result_object->get_result_buffer()->get_buffer_items() as $index => $buffer_item)
					{
						$identifier = $buffer_item->get_result_identifier();
						$value = $buffer_item->get_result_value();
						$raw_values = explode(':', $buffer_item->get_result_raw());
						$percent_std = pts_math::set_precision(pts_math::percent_standard_deviation($raw_values), 2);
						$std_error = pts_math::set_precision(pts_math::standard_error($raw_values), 2);
						$delta = 0;

						if($value > $max_value)
						{
							$max_value = $value;
						}

						if(defined("PHOROMATIC_TRACKER"))
						{
							$identifier_r = explode(':', $identifier);

							if($identifier_r[0] == $prev_identifier_0 && $prev_value != 0)
							{
								$delta = pts_math::set_precision(abs(1 - ($value / $prev_value)), 4);

								if($delta > 0.02 && $delta > pts_math::standard_deviation($raw_values))
								{
									switch($result_object->test_result->test_profile->get_result_proportion())
									{
										case "HIB":
											if($value < $prev_value)
											{
												$delta = 0 - $delta;
											}
											break;
										case "LIB":
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
						}
						else
						{
							if(false && PTS_MODE == "CLIENT" && $this->is_multi_way_comparison())
							{
								// TODO: make it work better for highlighting multiple winners in multi-way comparisons
								$highlight = false;

								if($index % 2 == 1 && $prev_value != 0)
								{
									switch($result_object->test_result->test_profile->get_result_proportion())
									{
										case "HIB":
											if($value > $prev_value)
											{
												$highlight = true;
											}
											else
											{
												$result_table[$prev_identifier][$result_counter]->set_highlight(true);
												$result_table[$prev_identifier][$result_counter]->set_delta(-1);
											}
											break;
										case "LIB":
											if($value < $prev_value)
											{
												$highlight = true;
											}
											else
											{
												$result_table[$prev_identifier][$result_counter]->set_highlight(true);
												$result_table[$prev_identifier][$result_counter]->set_delta(-1);
											}
											break;
									}
								}
							}
							else
							{
								$highlight = $best_value == $value;
							}

							if($min_value_in_buffer != $max_value_in_buffer)
							{
								switch($result_object->test_result->test_profile->get_result_proportion())
								{
									case "HIB":
										$delta = pts_math::set_precision($value / $min_value_in_buffer, 2);
										break;
									case "LIB":
										$delta = pts_math::set_precision(1 - ($value / $max_value_in_buffer) + 1, 2);
										break;
								}
							}
						}

						$result_table[$identifier][$result_counter] = new pts_result_table_value($value, $percent_std, $std_error, $delta, $highlight);
						$prev_identifier = $identifier;
						$prev_value = $value;
					}
					break;
				case "LINE_GRAPH":
					$result_tests[$result_counter][0] = $result_object->test_result->test_profile->get_test_title() . " (Avg)";
					$result_tests[$result_counter][1] = null;

					foreach($result_object->get_result_buffer()->get_buffer_items() as $index => $buffer_item)
					{
						$identifier = $buffer_item->get_result_identifier();
						$values = explode(',', $buffer_item->get_result_value());
						$avg_value = pts_math::set_precision(array_sum($values) / count($values), 2);

						if($avg_value > $max_value)
						{
							$max_value = $avg_value;
						}

						$result_table[$identifier][$result_counter] = new pts_result_table_value($avg_value);
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

				$std_percent = $info[($result_counter - 1)]->get_standard_deviation_percent();
				$std_error = $info[($result_counter - 1)]->get_standard_error();
				$delta = $info[($result_counter - 1)]->get_delta();

				if($delta != 0)
				{
					array_push($result_table[$identifier], new pts_result_table_value($delta . 'x'));
					$has_written_diff = true;
				}
				if($std_error != 0)
				{
					array_push($result_table[$identifier], new pts_result_table_value($std_error));
					$has_written_error = true;
				}
				if($std_percent != 0)
				{
					array_push($result_table[$identifier], new pts_result_table_value($std_percent . "%"));
					$has_written_std = true;
				}
			}

			if($has_written_diff)
			{
				array_push($result_tests, array("Difference", null));
			}
			if($has_written_error)
			{
				array_push($result_tests, array("Standard Error", null));
			}
			if($has_written_std)
			{
				array_push($result_tests, array("Standard Deviation", null));
			}

			$max_value += 100; // to make room for % sign in display
		}

		if(defined("PHOROMATIC_TRACKER"))
		{
			// Resort the results by SYSTEM, then date
			$systems_table = array();
			$sorted_table = array();

			foreach($result_table as $system_identifier => &$identifier_table)
			{
				$identifier = array_map("trim", explode(':', $system_identifier));

				if(!isset($systems_table[$identifier[0]]))
				{
					$systems_table[$identifier[0]] = array();
				}

				$systems_table[$identifier[0]][$system_identifier] = $identifier_table;
			}

			$result_table = array();
			$result_systems = array();
			$longest_system_identifier = null;
			$longest_system_identifier_length = 0;

			foreach($systems_table as &$group)
			{
				foreach($group as $identifier => $table)
				{
					$result_table[$identifier] = $table;

					$identifier = array_map("trim", explode(':', $identifier));
					$show_id = isset($identifier[1]) ? $identifier[1] : $identifier[0];

					if(($le = strlen($show_id)) > $longest_system_identifier_length)
					{
						$longest_system_identifier_length = $le;
						$longest_system_identifier = $show_id;
					}


					if($system_id_keys != null && ($s = array_search($identifier[0], $system_id_keys)) !== false)
					{
						$system_id = $s;
					}
					else
					{
						$system_id = null;
					}

					array_push($result_systems, array($show_id, $system_id));
				}
			}
		}
		else
		{
			$result_systems = array();
			$longest_system_identifier = null;
			$longest_system_identifier_length = 0;

			foreach(array_keys($result_table) as $id)
			{
				if(($le = strlen($id)) > $longest_system_identifier_length)
				{
					$longest_system_identifier_length = $le;
					$longest_system_identifier = $id;
				}

				array_push($result_systems, array($id, null));
			}
		}

		if(is_numeric($max_value))
		{
			$max_value += 0.01;
		}

		return array($result_tests, $result_systems, $result_table, $max_value, $longest_system_identifier);
	}
	public function get_result_objects($select_indexes = -1)
	{
		if($this->result_objects == null)
		{
			$this->result_objects = array();

			$results_name = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_TITLE);
			$results_version = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_VERSION);
			$results_profile_version = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_PROFILE_VERSION);
			$results_attributes = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_ATTRIBUTES);
			$results_scale = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_SCALE);
			$results_test_name = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_TESTNAME);
			$results_arguments = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_ARGUMENTS);
			$results_proportion = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_PROPORTION);
			$results_format = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_RESULTFORMAT);
			$results_raw = $this->xml_parser->getXMLArrayValues(P_RESULTS_RESULTS_GROUP);

			$result_buffers = array();

			$key_identifier = substr(P_RESULTS_RESULTS_GROUP_IDENTIFIER, strlen(P_RESULTS_RESULTS_GROUP) + 1);
			$key_value = substr(P_RESULTS_RESULTS_GROUP_VALUE, strlen(P_RESULTS_RESULTS_GROUP) + 1);
			$key_raw = substr(P_RESULTS_RESULTS_GROUP_RAW, strlen(P_RESULTS_RESULTS_GROUP) + 1);

			foreach(array_keys($results_raw) as $results_raw_key)
			{
				$result_buffer = new pts_test_result_buffer();
				$xml_results = new tandem_XmlReader($results_raw[$results_raw_key]);

				$identifiers = $xml_results->getXMLArrayValues($key_identifier);
				$values = $xml_results->getXMLArrayValues($key_value);
				$raw_values = $xml_results->getXMLArrayValues($key_raw);

				for($i = 0; $i < count($identifiers) && $i < count($values); $i++)
				{
					$result_buffer->add_test_result($identifiers[$i], $values[$i], $raw_values[$i]);
				}

				array_push($result_buffers, $result_buffer);
			}

			for($i = 0; $i < count($results_name); $i++)
			{
				$test_object = new pts_result_file_result_object($results_name[$i], $results_version[$i], $results_profile_version[$i], $results_attributes[$i], $results_scale[$i], $results_test_name[$i], $results_arguments[$i], $results_proportion[$i], $results_format[$i], $result_buffers[$i]);

				array_push($this->result_objects, $test_object);
			}
		}

		if($select_indexes != -1)
		{
			$objects = array();

			foreach(pts_arrays::to_array($select_indexes) as $index)
			{
				if(isset($this->result_objects[$index]))
				{
					array_push($objects, $this->result_objects[$index]);
				}
			}

			return $objects;
		}

		return $this->result_objects;
	}
}

?>
