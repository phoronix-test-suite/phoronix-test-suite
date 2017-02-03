<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2017, Phoronix Media
	Copyright (C) 2009 - 2017, Michael Larabel

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
	//public $active = null;
	private $result = 0;
	private $result_min = 0;
	private $result_max = 0;

	public function __construct()
	{
		$this->results = array();
		$this->min_results = array();
		$this->max_results = array();
	}
	public function add_trial_run_result($result, $min = null, $max = null)
	{
		$this->results[] = $result;
		$this->min_results[] = $min;
		$this->max_results[] = $max;
	}
	public function get_values_as_string()
	{
		return implode(':', $this->results);
	}
	public function set_result($result)
	{
		$this->result = $result;
	}
	public function set_min_result($result)
	{
		$this->result_min = $result;
	}
	public function set_max_result($result)
	{
		$this->result_max = $result;
	}
	public function get_result()
	{
		return $this->result;
	}
	public function get_min_result()
	{
		return $this->result_min;
	}
	public function get_max_result()
	{
		return $this->result_max;
	}
	public function get_max_value()
	{
		$value = 0;

		foreach($this->results as $result)
		{
			if($result > $value)
			{
				$value = $result;
			}
		}

		return $value;
	}
	public function get_min_value()
	{
		$value = 0;

		foreach($this->results as $result)
		{
			if($result < $value || $value == 0)
			{
				$value = $result;
			}
		}

		return $value;
	}
}

?>
