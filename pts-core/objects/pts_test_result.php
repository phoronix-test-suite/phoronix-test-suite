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
	private $result;
	private $trial_results;
	private $attributes;
	private $used_arguments;
	private $used_arguments_description;

	private $test_profile;

	public function __construct(&$test_profile)
	{
		$this->test_profile = $test_profile;
		$this->trial_results = array();
	}
	public function set_used_arguments_description($arguments_description)
	{
		$this->used_arguments_description = $arguments_description;
	}
	public function set_used_arguments($used_arguments)
	{
		$this->used_arguments = $used_arguments;
	}
	public function set_result($result)
	{
		$this->result = $result;
	}

	public function get_test_profile()
	{
		return $this->test_profile;
	}
	public function get_used_arguments()
	{
		return $this->used_arguments;
	}
	public function get_used_arguments_description()
	{
		return $this->used_arguments_description;
	}

	public function get_result()
	{
		return $this->result;
	}
	public function get_trial_results()
	{
		return $this->trial_results;
	}
	public function get_trial_results_string()
	{
		return implode(':', $this->get_trial_results());
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
	public function calculate_end_result()
	{
		$END_RESULT = 0;

		if(count($this->trial_results) == 0)
		{
			$this->set_result(0);
			return false;
		}

		switch($this->test_profile->get_result_format())
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
			case "BAR_GRAPH":
			default:
				// Result is of a normal numerical type
				switch($this->get_test_profile()->get_result_quantifier())
				{
					case "MAX":
						$max_value = $this->trial_results[0];
						foreach($this->trial_results as $result)
						{
							if($result > $max_value)
							{
								$max_value = $result;
							}

						}
						$END_RESULT = $max_value;
						break;
					case "MIN":
						$min_value = $this->trial_results[0];
						foreach($this->trial_results as $result)
						{
							if($result < $min_value)
							{
								$min_value = $result;
							}
						}
						$END_RESULT = $min_value;
						break;
					default:
						// assume AVG (average)
						$is_float = false;
						$TOTAL_RESULT = 0;
						$TOTAL_COUNT = 0;

						foreach($this->trial_results as $result)
						{
							$result = trim($result);

							if(is_numeric($result))
							{
								$TOTAL_RESULT += $result;
								$TOTAL_COUNT++;

								if(!$is_float && strpos($result, '.') !== false)
								{
									$is_float = true;
								}
							}
						}

						$END_RESULT = pts_trim_double($TOTAL_RESULT / ($TOTAL_COUNT > 0 ? $TOTAL_COUNT : 1), 2);

						if(!$is_float)
						{
							$END_RESULT = round($END_RESULT);
						}
						break;
				}
				break;
		}

		$this->set_result($END_RESULT);
	}
}

?>
