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
		$select_identifiers = $result_merge_select instanceOf pts_result_merge_select ? $result_merge_select->get_selected_identifiers() : null;

		$merged = false;
		for($i = 0; $i < count($this->test_results) && !$merged; $i++)
		{
			if($this->test_results[$i]->get_test_name() == $merge_test_object->get_test_name() && trim($this->test_results[$i]->get_arguments()) == trim($merge_test_object->get_arguments()) && $this->test_results[$i]->get_attributes() == $merge_test_object->get_attributes() && $this->test_results[$i]->get_version() == $merge_test_object->get_version() && $this->test_results[$i]->get_scale() == $merge_test_object->get_scale())
			{
				foreach($merge_test_object->get_result_buffer()->get_buffer_items() as $buffer_item)
				{
					$this_identifier = $buffer_item->get_result_identifier();

					if($select_identifiers == null || in_array($this_identifier, $select_identifiers))
					{
						if($result_merge_select != null && ($renamed = $result_merge_select->get_rename_identifier()) != null)
						{
							$this_identifier = $renamed;
						}

						if(!$this->result_already_contained($i, $buffer_item))
						{
							$this->test_results[$i]->add_result_to_buffer($this_identifier, $buffer_item->get_result_value(), $buffer_item->get_result_raw());
						}
					}
				}

				$merged = true;
			}
		}

		if(!$merged)
		{
			$skip_adding = false;

			if($result_merge_select != null || is_array($select_identifiers))
			{
				if(PTS_MODE == "CLIENT" && pts_read_assignment("REFERENCE_COMPARISON") && is_array($select_identifiers))
				{
					$skip_adding = true;
				}

				$result_buffer = $merge_test_object->get_result_buffer();
				$merge_test_object->flush_result_buffer();

				foreach($result_buffer->get_buffer_items() as $buffer_item)
				{
					$this_identifier = $buffer_item->get_result_identifier();

					if($select_identifiers == null || in_array($this_identifier, $select_identifiers))
					{
						if(($renamed = $result_merge_select->get_rename_identifier()) != null)
						{
							$this_identifier = $renamed;
						}

						$merge_test_object->add_result_to_buffer($this_identifier, $buffer_item->get_result_value(), $buffer_item->get_result_raw());
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
	protected function result_already_contained($i, &$buffer_item)
	{
		$contained = false;

		foreach($this->test_results[$i] as $check_buffer_item)
		{
			if($buffer_item->get_result_identifier() == $check_buffer_item->get_result_identifier() && $buffer_item->get_result_value() == $check_buffer_item->get_result_value())
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
