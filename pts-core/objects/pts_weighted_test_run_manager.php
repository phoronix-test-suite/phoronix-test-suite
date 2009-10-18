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

class pts_weighted_test_run_manager extends pts_test_run_manager
{
	private $weight_suite_identifier;
	private $weight_test_profile;
	private $weight_initial_value;
	private $weight_final_expression;

	public function set_weight_suite_identifier($suite_identifier)
	{
		$this->weight_suite_identifier = $suite_identifier;
	}
	public function get_weight_suite_identifier()
	{
		return $this->weight_suite_identifier;
	}
	public function set_weight_test_profile($test_profile_identifier)
	{
		$this->weight_test_profile = $test_profile_identifier;
	}
	public function get_weight_test_profile()
	{
		return $this->weight_test_profile;
	}
	public function set_weight_initial_value($initial_value)
	{
		$this->weight_initial_value = $initial_value;
	}
	public function get_weight_initial_value()
	{
		return $this->weight_initial_value;
	}
	public function set_weight_final_expression($final_expression)
	{
		$this->weight_final_expression = $final_expression;
	}
	public function get_weight_final_expression()
	{
		return $this->weight_final_expression;
	}
}

?>
