<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel

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
	var $result;
	var $result_scale;
	var $result_format;
	var $result_proportion;
	var $result_quantifier;
	var $trial_results;
	var $attributes;

	public function __construct($result = 0, $result_scale = "", $result_format = "")
	{
		$this->result = $result;
		$this->result_scale = $result_scale;
		$this->result_format = $result_format;

		$this->trial_results = array();
		$this->attributes = array();
		$this->result_quantifier = null;
		$this->result_proportion = null;
	}
	public function set_result($result)
	{
		$this->result = $result;
	}
	public function set_result_scale($result_scale)
	{
		$this->result_scale = $result_scale;
	}
	public function set_result_format($result_format)
	{
		$this->result_format = $result_format;
	}
	public function set_result_proportion($result_proportion)
	{
		$this->result_proportion = $result_proportion;
	}
	public function set_result_quantifier($result_quantifier)
	{
		$this->result_quantifier = $result_quantifier;
	}
	public function set_attribute($name, $value)
	{
		$this->attributes[$name] = $value;
	}
	public function get_result()
	{
		return $this->result;
	}
	public function get_result_scale()
	{
		return $this->result_scale;
	}
	public function get_result_format()
	{
		return $this->result_format;
	}
	public function get_result_proportion()
	{
		return $this->result_proportion;
	}
	public function get_trial_results()
	{
		return $this->trial_results;
	}
	public function get_trial_results_string()
	{
		return implode(":", $this->get_trial_results());
	}
	public function get_attribute($name)
	{
		return isset($this->attributes[$name]) ? $this->attributes[$name] : false;
	}
	public function add_trial_run_result($result)
	{
		$result = trim($result);

		if(!empty($result))
		{
			array_push($this->trial_results, $result);
		}
	}
	public function trial_run_count()
	{
		return count($this->trial_results);
	}
	public function get_result_format_string()
	{
		switch($this->get_result_format())
		{
			case "MAX":
				$return_str = "Maximum";
			case "MIN":
				$return_str = "Minimum";
			default:
				$return_str = "Average";
		}

		return $return_str;
	}
	public function calculate_end_result()
	{
		$END_RESULT = 0;

		if($this->result_format == "NO_RESULT")
		{
			// Nothing to do
		}
		else if($this->result_format == "LINE_GRAPH")
		{
			$END_RESULT = $this->trial_results[0];
		}
		else if($this->result_format == "PASS_FAIL" || $this->result_format == "MULTI_PASS_FAIL")
		{
			// Calculate pass/fail type
			$END_RESULT = -1;

			if(count($this->trial_results) == 1)
			{
				$END_RESULT = $this->trial_results[0];
			}
			else
			{
				foreach($this->trial_results as $result)
				{
					if($result == "FALSE" || $result == "0" || $result == "FAIL")
					{
						if($END_RESULT == -1 || $END_RESULT == "PASS")
						{
							$END_RESULT = "FAIL";
						}
					}
					else
					{
						if($END_RESULT == -1)
						{
							$END_RESULT = "PASS";
						}
					}
				}
			}
		}
		else
		{
			// Result is of a normal numerical type
			if($this->result_quantifier == "MAX")
			{
				$max_value = $this->trial_results[0];
				foreach($this->trial_results as $result)
				{
					if($result > $max_value)
					{
						$max_value = $result;
					}

				}
				$END_RESULT = $max_value;
			}
			else if($this->result_quantifier == "MIN")
			{
				$min_value = $this->trial_results[0];
				foreach($this->trial_results as $result)
				{
					if($result < $min_value)
					{
						$min_value = $result;
					}
				}
				$END_RESULT = $min_value;
			}
			else
			{
				// assume AVG (average)
				$TOTAL_RESULT = 0;
				$TOTAL_COUNT = 0;

				foreach($this->trial_results as $result)
				{
					if(is_numeric($result))
					{
						$TOTAL_RESULT += trim($result);
						$TOTAL_COUNT++;
					}
				}

				$END_RESULT = pts_trim_double($TOTAL_RESULT / ($TOTAL_COUNT > 0 ? $TOTAL_COUNT : 1), 2);
			}
		}

		$this->set_result($END_RESULT);
	}
}

?>
