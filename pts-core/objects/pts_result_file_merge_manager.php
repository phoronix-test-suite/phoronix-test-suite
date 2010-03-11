<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2010, Phoronix Media
	Copyright (C) 2009 - 2010, Michael Larabel

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
		$mto_test_name = $merge_test_object->get_test_name();

		if(isset($this->test_results[$mto_test_name]))
		{
			foreach($this->test_results[$mto_test_name] as &$mto_compare)
			{
				if(trim($mto_compare->get_arguments()) == trim($merge_test_object->get_arguments()) && $mto_compare->get_attributes() == $merge_test_object->get_attributes() && $mto_compare->get_version() == $merge_test_object->get_version() && $mto_compare->get_scale() == $merge_test_object->get_scale() && pts_version_comparable($mto_compare->get_test_profile_version(), $merge_test_object->get_test_profile_version()))
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

							if(!$this->result_already_contained($mto_compare, $buffer_item))
							{
								$mto_compare->add_result_to_buffer($this_identifier, $buffer_item->get_result_value(), $buffer_item->get_result_raw());
							}
						}
					}

					$merged = true;
					break;
				}
			}
		}
		else
		{
			$this->test_results[$mto_test_name] = array();
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
			if($skip_adding == false)
			{
				array_push($this->test_results[$mto_test_name], $merge_test_object);
			}
		}
	}
	protected function result_already_contained(&$mto_compare, &$buffer_item)
	{
		$contained = false;

		foreach($mto_compare as $check_buffer_item)
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
		$linear_array = array();

		foreach($this->test_results as $test_name => &$test_name_object_array)
		{
			foreach($test_name_object_array as $merge_object)
			{
				array_push($linear_array, $merge_object);
			}
		}

		return $linear_array;
	}
}

?>
