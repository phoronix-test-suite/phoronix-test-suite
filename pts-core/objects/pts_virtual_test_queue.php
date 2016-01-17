<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2014 - 2016, Phoronix Media
	Copyright (C) 2014 - 2016, Michael Larabel

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

class pts_virtual_test_queue
{
	private $tests;

	public function __construct()
	{
		$this->tests = array();
	}
	public function add_to_queue($test_identifier, $description, $value)
	{
		$this->tests[] = array(
			'test' => $test_identifier,
			'option' => $description,
			'option_value' => $value
			);
	}
	public function get_contained_test_profiles()
	{
		$contained = array();
		foreach($this->tests as $test)
		{
			$test_profile = new pts_test_profile($test['test']);
			if($test_profile->is_supported(false))
			{
				$contained[] = $test_profile;
			}
		}

		return $contained;
	}
	public function is_core_version_supported()
	{
		return true;
	}
	public function __toString()
	{
		return 'VIRTUAL TEST SUITE';
	}
	public function get_contained_test_result_objects()
	{
		$test_result_objects = array();

		foreach($this->tests as $test)
		{
			$obj = pts_types::identifier_to_object($test['test']);

			if($obj instanceof pts_test_profile)
			{
				$test_result = new pts_test_result($obj);
				$test_result->set_used_arguments_description($test['option']);
				$test_result->set_used_arguments($test['option_value']);
				$test_result_objects[] = $test_result;
			}
		}

		return $test_result_objects;
	}
}

?>
