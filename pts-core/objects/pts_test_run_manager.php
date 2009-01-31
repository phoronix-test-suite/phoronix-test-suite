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

class pts_test_run_manager
{
	var $tests_to_run;
	var $instance_name;

	public function __construct($name = null)
	{
		$this->tests_to_run = array();
		$this->instance_name = $name;
	}
	public function add_individual_test_run($test_identifier, $arguments, $descriptions = "")
	{
		if(count($this->tests_to_run) == 0)
		{
			$this->instance_name = $this_identifier;
		}

		array_push($this->tests_to_run, new pts_test_run_request($test_identifier, $arguments, $descriptions));
	}
	public function add_single_test_run($test_identifier, $arguments, $descriptions)
	{
		$arguments = pts_to_array($arguments);
		$descriptions = pts_to_array($descriptions);

		for($i = 0; $i < count($arguments); $i++)
		{
			$this->add_individual_test_run($test_identifier, $arguments[$i], $descriptions[$i]);
		}
	}
	public function add_multi_test_run($test_identifier, $arguments, $descriptions)
	{
		$test_identifier = pts_to_array($test_identifier);
		$arguments = pts_to_array($arguments);
		$descriptions = pts_to_array($descriptions);

		for($i = 0; $i < count($test_identifier); $i++)
		{
			$this->add_individual_test_run($test_identifier[$i], $arguments[$i], $descriptions[$i]);
		}
	}
	public function get_tests_to_run()
	{
		return $this->tests_to_run;
	}
	public function get_instance_name()
	{
		return $this->instance_name;
	}
	public function get_test_count()
	{
		return count($this->get_tests_to_run());
	}
}

?>
