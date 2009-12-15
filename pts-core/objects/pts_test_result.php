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
	private $result;
	private $result_scale;
	private $result_format;
	private $result_proportion;
	private $result_quantifier;
	private $trial_results;
	private $attributes;

	private $name;
	private $test_identifier;
	private $version;
	private $description;
	private $times_to_run;

	private $test_profile;
	private $used_arguments;

	// TODO: integrate pts_result_file_merge_test and pts_test_result_buffercapabilities into this
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

	// The future is set_test_profile and get_test_profile
	public function get_test_profile()
	{
		return $this->test_profile;
	}
	public function set_test_profile($test_profile)
	{
		$this->test_profile = $test_profile;
	}

	public function get_name()
	{
		return $this->name;
	}
	public function set_name($name)
	{
		$this->name = $name;
	}
	public function get_test_identifier()
	{
		return $this->test_identifier;
	}
	public function set_test_identifier($test_identifier)
	{
		$this->test_identifier = $test_identifier;
	}
	public function get_version()
	{
		return $this->version;
	}
	public function set_version($version)
	{
		$this->version = $version;
	}
	public function get_description()
	{
		return $this->description;
	}
	public function set_description($description)
	{
		$this->description = $description;
	}
	public function get_times_to_run()
	{
		return $this->times_to_run;
	}
	public function set_times_to_run($times_to_run)
	{
		$this->times_to_run = $times_to_run;
	}
	public function get_used_arguments()
	{
		return $this->used_arguments;
	}
	public function set_used_arguments($used_arguments)
	{
		$this->used_arguments = $used_arguments;
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
			case "NULL":
				$return_str = "";
			default:
				$return_str = "Average";
		}

		return $return_str;
	}
	public function calculate_end_result()
	{
		$END_RESULT = 0;

		switch($this->result_format)
		{
			case "NO_RESULT":
				// Nothing to do, there are no results
				break;
			case "LINE_GRAPH":
			case "TEST_COUNT_PASS":
				// Just take the first result
				$END_RESULT = $this->trial_results[0];
				break;
			case "IMAGE_COMPARISON":
				// Capture the image
				$iqc_image_png = $this->trial_results[0];

				if(is_file($iqc_image_png))
				{
					$img_file_64 = base64_encode(file_get_contents($iqc_image_png, FILE_BINARY));
					$END_RESULT = $img_file_64;
					unlink($iqc_image_png);				
				}
				break;
			case "PASS_FAIL":
			case "MULTI_PASS_FAIL":
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
				break;
			default:
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
				break;
		}

		$this->set_result($END_RESULT);
	}
}

?>
