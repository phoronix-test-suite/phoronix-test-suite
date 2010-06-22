<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel

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

class pts_result_file_analyze_manager
{
	private $test_results;
	private $relations;

	public function __construct()
	{
		$this->test_results = array();
		$this->relations = array();
	}
	public function add_test_result_set($test_object_array)
	{
		foreach($test_object_array as $mto)
		{
			$this->add_test_result($mto);
		}
	}
	public function add_test_result($mto)
	{
		$total_objects = count($this->test_results);
		$this->test_results[$total_objects] = $mto;

		$attributes = array_reverse(explode(" - ", $mto->get_attributes()));
		$attributes_clean = array();

		for($i = 0; $i < count($attributes); $i++)
		{
			$temp = pts_strings::trim_explode(":", $attributes[$i]);
			$attributes_clean[$temp[0]] = $temp[1];
		}

		if(!isset($this->relations[$mto->get_test_name()][$mto->get_version()]))
		{
			$this->relations[$mto->get_test_name()][$mto->get_version()] = array();
		}

		array_push($this->relations[$mto->get_test_name()][$mto->get_version()], array($total_objects, $attributes_clean));
	}
	public function get_results()
	{
		$return_results = array();
		$compared_to_index = array();

		foreach($this->relations as $test_name => $tests_of_same_name)
		{
			foreach($tests_of_same_name as $test_version => $tests_of_same_name_and_version)
			{
				if(count($tests_of_same_name_and_version) == 1)
				{
					// Stub, no similar results to analyze
					array_push($return_results, $this->test_results[$tests_of_same_name_and_version[0][0]]);
				}
				else if(in_array($this->test_results[$tests_of_same_name_and_version[0][0]]->get_format(), array("IMAGE_COMPARISON", "LINE_GRAPH")))
				{
					foreach($tests_of_same_name_and_version as $add)
					{
						array_push($return_results, $this->test_results[$add[0]]);
					}
				}
				else
				{
					foreach($tests_of_same_name_and_version as $test_info)
					{
						$similar_ids = array($test_info[0]);
						$similar_ids_names = array();
						$diff_index = null;
						$this_attributes = $test_info[1];

						foreach($tests_of_same_name_and_version as $compare_to)
						{
							if(in_array($compare_to[0], $similar_ids))
							{
								continue;
							}

							$diff = array_diff_assoc($this_attributes, $compare_to[1]);

							if(count($diff) == 1)
							{
								if($diff_index == null)
								{
									$this_index = pts_last_element_in_array(array_keys($diff));
									//$this_index_value = $diff[$this_index];
									$index_id = implode(",", array($test_name, $test_version, $this_index));

									if(in_array($index_id, $compared_to_index))
									{
										continue;
									}

									array_push($compared_to_index, $index_id);
									array_push($similar_ids_names, $this_attributes[$this_index]);
									$diff_index = $this_index;
								}

								if(isset($diff[$diff_index]))
								{
									array_push($similar_ids, $compare_to[0]);
									array_push($similar_ids_names, $compare_to[1][$diff_index]);
								}
							}
						}

						if(count($similar_ids) > 1)
						{
							$mto = $this->test_results[$similar_ids[0]];
							$results = array();

							foreach($mto->get_result_buffer()->get_identifiers() as $identifier)
							{
								$results[$identifier] = array();
							}

							foreach($similar_ids as $id)
							{
								$mto_read = $this->test_results[$id];
								$mto_identifiers = $mto_read->get_result_buffer()->get_identifiers();
								$mto_values = $mto_read->get_result_buffer()->get_values();

								foreach(array_keys($results) as $key)
								{
									for($i = 0; $i < count($mto_identifiers); $i++)
									{
										if($mto_identifiers[$i] == $key)
										{
											array_push($results[$key], $mto_values[$i]);
											break;
										}
									}
								}
							}

							$mto->flush_result_buffer();

							$do_line_graph = true;
							foreach($similar_ids_names as $id_name_check)
							{
								if(str_ireplace(array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, "x", "M", "K", "B", " "), "", $id_name_check) != null)
								{
									$do_line_graph = false;
									break;
								}
							}

							$mto->set_format(($do_line_graph ? "LINE_GRAPH" : "BAR_ANALYZE_GRAPH"));
							$mto->set_attributes($diff_index . " Analysis");
							$mto->set_scale($mto->get_scale() . " | " . implode(",", $similar_ids_names));

							foreach($results as $identifier => $values)
							{
								$mto->add_result_to_buffer($identifier, implode(",", $values), null);
							}

							array_push($return_results, $mto);
						}
					}
				}
			}
		}

		return $return_results;
	}
}

?>
