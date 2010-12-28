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

class pts_test_result
{
	// Note in most pts-core code the initialized var is called $result_object
	// Note in pts-core code the initialized var is also called $test_run_request
	private $result;
	private $used_arguments;
	private $used_arguments_description;

	public $test_profile;
	public $test_result_buffer;

	public function __construct(&$test_profile)
	{
		$this->test_profile = $test_profile;
		$this->result = 0;
	}
	public function set_test_result_buffer($test_result_buffer)
	{
		$this->test_result_buffer = $test_result_buffer;
	}
	public function set_used_arguments_description($arguments_description)
	{
		$this->used_arguments_description = $arguments_description;
	}
	public function set_used_arguments($used_arguments)
	{
		$this->used_arguments = $used_arguments;
	}
	public function get_arguments()
	{
		return $this->used_arguments;
	}
	public function get_arguments_description()
	{
		return $this->used_arguments_description;
	}
	public function set_result($result)
	{
		$this->result = $result;
	}
	public function get_result()
	{
		return $this->result;
	}
	public function get_comparison_hash($show_version_and_attributes = true)
	{
		return $show_version_and_attributes ? pts_test_profile::generate_comparison_hash($this->test_profile->get_identifier(false), $this->get_arguments(), $this->get_arguments_description(), $this->test_profile->get_app_version()) : pts_test_profile::generate_comparison_hash($this->test_profile->get_identifier(false), $this->get_arguments());
	}
	public function __toString()
	{
		return $this->test_profile->get_identifier(false) . ' ' . $this->get_arguments() . ' ' . $this->get_arguments_description() . ' ' . $this->test_profile->get_override_values();
	}
}

?>
