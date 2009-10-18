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

class pts_result_file_merge_manager
{
	private $test_results;

	public function __construct()
	{
		$this->test_results = array();
	}
	public function add_test_result_set($merge_test_objects_array, &$result_merge_select)
	{
		foreach($merge_test_objects_array as $merge_test_object)
		{
			$this->add_test_result($merge_test_object, $result_merge_select);
		}
	}
	public function add_test_result($merge_test_object, &$result_merge_select)
	{
		/*
		if(empty($merge_test_object->get_identifiers()) || is_array($merge_test_object->get_identifiers()) && count($merge_test_object->get_identifiers()) == 0)
		{
			return;
		}
		*/

		$select_identifiers = $result_merge_select->get_selected_identifiers();

		$merged = false;
		for($i = 0; $i < count($this->test_results) && !$merged; $i++)
		{
			if($this->test_results[$i]->get_test_name() == $merge_test_object->get_test_name() && trim($this->test_results[$i]->get_arguments()) == trim($merge_test_object->get_arguments()) && $this->test_results[$i]->get_attributes() == $merge_test_object->get_attributes() && $this->test_results[$i]->get_version() == $merge_test_object->get_version())
			{
				$identifiers = $merge_test_object->get_identifiers();
				$values = $merge_test_object->get_values();
				$raw_values = $merge_test_object->get_raw_values();

				for($j = 0; $j < count($identifiers); $j++)
				{
					if($select_identifiers == null || in_array($identifiers[$j], $select_identifiers))
					{
						if(($renamed = $result_merge_select->get_rename_identifier()) != null)
						{
							$identifiers[$j] = $renamed;
						}

						if(!$this->result_already_contained($i, $identifiers[$j], $values[$j]))
						{
							$this->test_results[$i]->add_identifier($identifiers[$j]);
							$this->test_results[$i]->add_value($values[$j]);
							$this->test_results[$i]->add_raw_value($raw_values[$j]);
						}
					}
				}

				$merged = true;
			}
		}

		if(!$merged)
		{
			$skip_adding = false;

			if(is_array($select_identifiers))
			{
				if(pts_read_assignment("REFERENCE_COMPARISON"))
				{
					$skip_adding = true;
				}

				$identifiers = $merge_test_object->get_identifiers();
				$values = $merge_test_object->get_values();
				$raw_values = $merge_test_object->get_raw_values();

				$merge_test_object->flush_result_data();

				for($j = 0; $j < count($identifiers); $j++)
				{
					if(in_array($identifiers[$j], $select_identifiers))
					{
						if(($renamed = $result_merge_select->get_rename_identifier()) != null)
						{
							$identifiers[$j] = $renamed;
						}

						$merge_test_object->add_identifier($identifiers[$j]);
						$merge_test_object->add_value($values[$j]);
						$merge_test_object->add_raw_value($raw_values[$j]);
					}
				}
			}

			// Add Result
			if(!$skip_adding)
			{
				array_push($this->test_results, $merge_test_object);
			}
		}
	}
	protected function result_already_contained($test_results_location, $identifier, $value)
	{
		$contained = false;
		$keys = array_keys($this->test_results[$test_results_location]->get_identifiers(), $identifier);
		$result_values = $this->test_results[$test_results_location]->get_values();

		foreach($keys as $key)
		{
			if($result_values[$key] == $value)
			{
				$contained = true;
				break;
			}
		}

		return $contained;
	}
	public function get_results()
	{
		return $this->test_results;
	}
}

?>
