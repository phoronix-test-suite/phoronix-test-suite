<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts-generic-classes.php: Some generic classes for the Phoronix Test Suite

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
	var $result_quantifier;
	var $trial_results;

	public function __construct($result = 0, $result_scale = "", $result_format = "")
	{
		$this->result = $result;
		$this->result_scale = $result_scale;
		$this->result_format = $result_format;

		$this->trial_results = array();
		$this->result_quantifier = null;
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
	public function set_result_quantifier($result_quantifier)
	{
		$this->result_quantifier = $result_quantifier;
	}
	public function get_result()
	{
		return $this->result;
	}
	public function get_result_scale()
	{
		return $this->result_scale;
	}
	public function add_trial_run_result($result)
	{
		if(!empty($result))
		{
			array_push($this->trial_results, $result);
		}
	}
	public function calculate_end_result(&$return_string)
	{
		$END_RESULT = 0;
		if($this->result_format == "NO_RESULT")
		{
			// Nothing to do
			$return_string = null;
		}
		else if($this->result_format == "PASS_FAIL" || $this->result_format == "MULTI_PASS_FAIL")
		{
			// Calculate pass/fail type
			$return_string .= "(" . $this->result_scale . ")\n";
			$END_RESULT = -1;
			$i = 1;

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
						$this_result = "FAIL";

						if($END_RESULT == -1 || $END_RESULT == "PASS")
						{
							$END_RESULT = "FAIL";
						}
					}
					else
					{
						$this_result = "PASS";

						if($END_RESULT == -1)
						{
							$END_RESULT = "PASS";
						}
					}

					$return_string .= "Trial $i: " . $this_result . "\n";
					$i++;
				}
			}

			$return_string .= "\nFinal: " . $END_RESULT . "\n";
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

					$return_string .= $result . " " . $this->result_scale . "\n";
				}
				$return_string .= "\nMaximum: " . $max_value . " " . $this->result_scale;
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

					$return_string .= $result . " " . $this->result_scale . "\n";
				}
				$return_string .= "\nMinimum: " . $min_value . " " . $this->result_scale;
				$END_RESULT = $min_value;
			}
			else
			{
				// assume AVG (average)
				$TOTAL_RESULT = 0;
				foreach($this->trial_results as $result)
				{
					$TOTAL_RESULT += trim($result);
					$return_string .= $result . " " . $this->result_scale . "\n";
				}

				if(count($this->trial_results) > 0)
				{
					$END_RESULT = pts_trim_double($TOTAL_RESULT / count($this->trial_results), 2);
				}
				else
				{
					$END_RESULT = pts_trim_double($TOTAL_RESULT, 2);
				}

				$return_string .= "\nAverage: " . $END_RESULT . " " . $this->result_scale;
			}
		}
		$this->set_result($END_RESULT);
	}
}

?>
