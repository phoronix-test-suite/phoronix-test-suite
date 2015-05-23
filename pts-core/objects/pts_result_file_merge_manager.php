<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2011, Phoronix Media
	Copyright (C) 2009 - 2011, Michael Larabel

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
	private $test_results = null;
	private $skip_subsystems = null;
	private $only_show_select_tests;
	private $sets_added = 0;
	private $is_reference_comparison;
	private $skip_adding_on_extra_sets;

	public function __construct($pass_attributes = null)
	{
		$this->test_results = array();

		if(isset($pass_attributes['subsystem_targets_to_skip']) && is_array($pass_attributes['subsystem_targets_to_skip']))
		{
			$this->skip_subsystems = $pass_attributes['subsystem_targets_to_skip'];
		}

		$this->is_reference_comparison = isset($pass_attributes['is_reference_comparison']);
		$this->skip_adding_on_extra_sets = isset($pass_attributes['skip_adding_on_extra_sets']);
		$this->only_show_select_tests = isset($pass_attributes['select_test_profiles']) ? $pass_attributes['select_test_profiles'] : null;
	}
	public function add_test_result_set($merge_test_objects_array, &$result_merge_select)
	{
		foreach($merge_test_objects_array as $merge_test_object)
		{
			$this->add_test_result($merge_test_object, $result_merge_select);
		}
		$this->sets_added += 1;
	}
	public function add_test_result($merge_test_object, &$result_merge_select)
	{
		$select_identifiers = $result_merge_select instanceof pts_result_merge_select ? $result_merge_select->get_selected_identifiers() : null;
		$mto_hash = $merge_test_object->get_comparison_hash();
		$merged = false;

		if($this->skip_subsystems != null)
		{
			// Check whether to omit rendering this test if only certain subsystem test types should be merged
			$test_subsystem = $merge_test_object->test_profile->get_test_hardware_type();

			if($test_subsystem != null && !in_array($test_subsystem, $this->skip_subsystems))
			{
				return false;
			}
		}
		if($this->only_show_select_tests != null && is_array($this->only_show_select_tests))
		{
			if(!in_array($merge_test_object->test_profile->get_identifier_base_name(), $this->only_show_select_tests))
			{
				return false;
			}
		}

		if(isset($this->test_results[$mto_hash]))
		{
			foreach($merge_test_object->test_result_buffer->get_buffer_items() as $buffer_item)
			{
				$this_identifier = $buffer_item->get_result_identifier();

				if($select_identifiers == null || in_array($this_identifier, $select_identifiers))
				{
					if($result_merge_select != null && ($renamed = $result_merge_select->get_rename_identifier()) != null)
					{
						$this_identifier = $renamed;
					}

					if($this->result_already_contained($this->test_results[$mto_hash], $buffer_item) == false)
					{
						$this->test_results[$mto_hash]->test_result_buffer->add_test_result($this_identifier, $buffer_item->get_result_value(), $buffer_item->get_result_raw(), $buffer_item->get_result_json());
					}
				}
			}
			$merged = true;
		}

		if($merged == false)
		{
			$skip_adding = false;

			if($result_merge_select != null || is_array($select_identifiers))
			{
				if($this->is_reference_comparison && is_array($select_identifiers))
				{
					$skip_adding = true;
				}
				else if($this->skip_adding_on_extra_sets && $this->sets_added > 0)
				{
					$skip_adding = true;
				}

				$result_buffer = $merge_test_object->test_result_buffer;
				$merge_test_object->test_result_buffer = new pts_test_result_buffer();

				foreach($result_buffer->get_buffer_items() as $buffer_item)
				{
					$this_identifier = $buffer_item->get_result_identifier();

					if($select_identifiers == null || in_array($this_identifier, $select_identifiers))
					{
						if(($renamed = $result_merge_select->get_rename_identifier()) != null)
						{
							$this_identifier = $renamed;
						}

						$merge_test_object->test_result_buffer->add_test_result($this_identifier, $buffer_item->get_result_value(), $buffer_item->get_result_raw(), $buffer_item->get_result_json());
					}
				}
			}
			else
			{
				if($this->skip_adding_on_extra_sets && $this->sets_added > 0)
				{
					$skip_adding = true;
				}
			}

			// Add Result
			if($skip_adding == false)
			{
				$this->test_results[$mto_hash] = $merge_test_object;
			}
		}
	}
	protected function result_already_contained(&$mto_compare, &$buffer_item)
	{
		$contained = false;

		foreach($mto_compare->test_result_buffer->get_buffer_items() as &$check_buffer_item)
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
