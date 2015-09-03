<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2015, Phoronix Media
	Copyright (C) 2009 - 2015, Michael Larabel

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

class pts_test_result_buffer_active
{
	public $results;
	public $min_results;
	public $max_results;

	public function __construct()
	{
		$this->results = array();
		$this->min_results = array();
		$this->max_results = array();
	}
	public function add_trial_run_result($result, $min = null, $max = null)
	{
		array_push($this->results, $result);
		array_push($this->min_results, $min);
		array_push($this->max_results, $max);
	}
	public function get_trial_run_count()
	{
		return count($this->results);
	}
	public function get_values_as_string()
	{
		return implode(':', $this->results);
	}
}

?>
