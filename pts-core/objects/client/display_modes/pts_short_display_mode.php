<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2015 - 2019, Phoronix Media
	Copyright (C) 2015 - 2019, Michael Larabel

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

class pts_short_display_mode extends pts_concise_display_mode
{
	private $longest_test_identifier_length = 0;

	public function test_run_process_start(&$test_run_manager)
	{
		foreach($test_run_manager->get_tests_to_run() as $test)
		{
			$ti = $test->test_profile->get_identifier();

			if(strlen($ti) > $this->longest_test_identifier_length)
			{
				$this->longest_test_identifier_length = strlen($ti);
			}
		}

		return;
	}
	protected function print_test_identifier_prefix($test)
	{
		$ti = $test->test_profile->get_identifier();
		return pts_client::cli_just_bold($ti) . str_repeat(' ', ($this->longest_test_identifier_length - strlen($ti))) . ': ';
	}
	public function test_run_start(&$test_run_manager, &$test_result)
	{
		echo $this->print_test_identifier_prefix($test_result);

		$after_print = pts_client::cli_colored_text('Test Started', 'green', true);
		if(($test_description = $test_result->get_arguments_description()) != false)
		{
			$after_print .= ' - ' . pts_client::swap_variables($test_description, array('pts_client', 'environment_variables'));
		}
		echo $after_print .= PHP_EOL;

		$this->trial_run_count_current = 0;
		$this->expected_trial_run_count = $test_result->test_profile->get_times_to_run();
	}
	public function test_run_success_inline($test_result)
	{
		// empty
	}
	public function test_run_instance_error($error_string)
	{
		return;
	}
	public function test_run_instance_output(&$to_output)
	{
		return;
	}
	public function test_run_message($message_string)
	{
		return;
	}
	public function test_install_message($msg_string)
	{
		return;
	}
	public function test_run_error($error_string)
	{
		return;
	}
	public function test_run_instance_header(&$test_result)
	{
		$this->trial_run_count_current++;
		//echo $this->print_test_identifier_prefix($test_result) . 'Started Run ' . $this->trial_run_count_current . ' @ ' . date('H:i:s') . PHP_EOL;
	}
	public function test_run_instance_complete(&$test_result)
	{
		return;
	}
	public function test_run_end(&$test_result)
	{
		if(in_array($test_result->test_profile->get_display_format(), array('NO_RESULT', 'IMAGE_COMPARISON')))
		{
			$end_print = null;
		}
		else if(in_array($test_result->test_profile->get_display_format(), array('PASS_FAIL', 'MULTI_PASS_FAIL')))
		{
			$end_print = 'Final: ' . $test_result->active->get_result() . ' (' . $test_result->test_profile->get_result_scale() . ')';
		}
		else if(in_array($test_result->test_profile->get_display_format(), array('FILLED_LINE_GRAPH', 'LINE_GRAPH')))
		{
			$values = explode(',', $test_result->active->get_result());
			$end_print = null;

			if(count($values) > 1)
			{
				$avg = pts_math::set_precision(pts_math::arithmetic_mean($values), 2);
				$min = pts_math::set_precision(min($values), 2);
				$max = pts_math::set_precision(max($values), 2);
				$end_print .= 'AVG: ' . $avg . ' (' . $test_result->test_profile->get_result_scale() . ') / ';
				$end_print .= 'MIN: ' . $min . ' (' . $test_result->test_profile->get_result_scale() . ') / ';
				$end_print .= 'MAX: ' . $max . ' (' . $test_result->test_profile->get_result_scale() . ') / ';
			}
		}
		else
		{
			$end_print = pts_client::cli_just_bold(pts_strings::result_quantifier_to_string($test_result->test_profile->get_result_quantifier()) . ': ') . $test_result->active->get_result() . ' ' . $test_result->test_profile->get_result_scale();
		}

		echo $this->print_test_identifier_prefix($test_result) . $end_print . PHP_EOL;
		echo $this->print_test_identifier_prefix($test_result) . pts_client::cli_colored_text('Test Ended', 'red', true) . PHP_EOL;
	}
}

?>
