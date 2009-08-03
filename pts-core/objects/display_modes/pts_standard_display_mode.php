<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel
	pts_standard_display_mode.php: The standard display mode

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

class pts_standard_display_mode implements pts_display_mode_interface
{
	public function __construct()
	{

	}
	public function test_run_start(&$test_result)
	{
		return;
	}
	public function test_run_instance_header(&$test_result, $current_run, $total_run_count)
	{
		echo pts_string_header($test_result->get_attribute("TEST_TITLE") . " (Run " . $current_run . " of " . $total_run_count . ")");
	}
	public function test_run_output(&$to_output)
	{
		echo $to_output;
	}
	public function test_run_end(&$test_result)
	{
		$end_print = $test_result->get_attribute("TEST_TITLE") . ":\n" . $test_result->get_attribute("TEST_DESCRIPTION");
		$end_print .= "\n" . ($test_result->get_attribute("TEST_DESCRIPTION") != "" ? "\n" : "");

		if(in_array($test_result->get_result_format(), array("NO_RESULT", "LINE_GRAPH")))
		{
			return;
		}
		else if(in_array($test_result->get_result_format(), array("PASS_FAIL", "MULTI_PASS_FAIL")))
		{
			$end_print .= "\nFinal: " . $test_result->get_result() . " (" . $test_result->get_result_scale() . ")\n";
		}
		else
		{
			foreach($test_result->get_trial_results() as $result)
			{
				$end_print .= $result . " " . $test_result->get_result_scale() . "\n";
			}

			$end_print .= "\n" . $test_result->get_result_format_string() . ": " . $test_result->get_result() . " " . $test_result->get_result_scale();
		}

		echo pts_string_header($end_print, "#");
	}
	public function test_run_error($error_string)
	{
		echo "\n" . $error_string . "\n\n";
	}
}

?>
