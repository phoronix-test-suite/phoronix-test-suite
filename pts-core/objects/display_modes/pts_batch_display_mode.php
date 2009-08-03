<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel
	pts_batch_display_mode.php: The batch display mode

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

class pts_batch_display_mode implements pts_display_mode_interface
{
	public function __construct()
	{

	}
	public function test_run_start(&$test_result)
	{
		echo "\n" . $test_result->get_attribute("TEST_TITLE") . ":\n\t" . $test_result->get_attribute("TEST_IDENTIFIER");

		if(($test_description = $test_result->get_attribute("TEST_DESCRIPTION")) != false)
		{
			echo ": " . $test_description;
		}

		echo "\n";

		$estimated_length = pts_estimated_run_time($test_result->get_attribute("TEST_IDENTIFIER"));
		if($estimated_length > 1)
		{
			echo "\tEstimated Run-Time: " . pts_format_time_string($estimated_length, "SECONDS", true, 60) . "\n";
		}

		echo "\tTrial Run Count: " . $test_result->get_attribute("TIMES_TO_RUN") . "\n";
	}
	public function test_run_instance_header(&$test_result, $current_run, $total_run_count)
	{
		echo "\t\tStarted Run " . $current_run . " @ " . date("H:i:s") . "\n";
	}
	public function test_run_output(&$to_output)
	{
		return;
	}
	public function test_run_end(&$test_result)
	{
		if(in_array($test_result->get_result_format(), array("NO_RESULT", "LINE_GRAPH")))
		{
			return;
		}
		else if(in_array($test_result->get_result_format(), array("PASS_FAIL", "MULTI_PASS_FAIL")))
		{
			$end_print .= "\t\tFinal: " . $test_result->get_result() . " (" . $test_result->get_result_scale() . ")\n";
		}
		else
		{
			$end_print = "\n\tTest Results:\n";

			foreach($test_result->get_trial_results() as $result)
			{
				$end_print .= "\t\t" . $result . "\n";
			}

			$end_print .= "\n\t" . $test_result->get_result_format_string() . ": " . $test_result->get_result() . " " . $test_result->get_result_scale() . "\n";
		}

		echo $end_print . "\n";
	}
	public function test_run_error($error_string)
	{
		echo "\n" . $error_string . "\n\n";
	}
}

?>
