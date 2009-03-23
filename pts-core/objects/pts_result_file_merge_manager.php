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
	var $test_results;

	public function __construct()
	{
		$this->test_results = array();
	}
	public function add_test_result_set($merge_test_objects_array, $select_identifiers = null)
	{
		foreach($merge_test_objects_array as $merge_test_object)
		{
			$this->add_test_result($merge_test_object, $select_identifiers);
		}
	}
	public function add_test_result($merge_test_object, $select_identifiers = null)
	{
		/*
		if(empty($merge_test_object->get_identifiers()) || is_array($merge_test_object->get_identifiers()) && count($merge_test_object->get_identifiers()) == 0)
		{
			return;
		}
		*/

		if($select_identifiers != null)
		{
			$select_identifiers = pts_to_array($select_identifiers);
		}

		$merged = false;
		for($i = 0; $i < count($this->test_results) && !$merged; $i++)
		{
			if($this->test_results[$i]->get_test_name() == $merge_test_object->get_test_name() && $this->test_results[$i]->get_arguments() == $merge_test_object->get_arguments() && $this->test_results[$i]->get_attributes() == $merge_test_object->get_attributes() && $this->test_results[$i]->get_version() == $merge_test_object->get_version())
			{
				$identifiers = $merge_test_object->get_identifiers();
				$values = $merge_test_object->get_values();
				$raw_values = $merge_test_object->get_raw_values();

				for($j = 0; $j < count($identifiers); $j++)
				{
					if($select_identifiers == null || in_array($identifiers[$j], $select_identifiers))
					{
						$this->test_results[$i]->add_identifier($identifiers[$j]);
						$this->test_results[$i]->add_value($values[$j]);
						$this->test_results[$i]->add_raw_value($raw_values[$j]);
					}
				}

				$merged = true;
			}
		}

		if(!$merged)
		{
			// Add Result
			array_push($this->test_results, $merge_test_object);
		}
	}
	public function get_results()
	{
		return $this->test_results;
	}
}

?>
