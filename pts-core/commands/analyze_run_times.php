<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2020, Phoronix Media
	Copyright (C) 2020, Michael Larabel

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

class analyze_run_times implements pts_option_interface
{
	const doc_section = 'Result Analysis';
	const doc_description = 'This option will read a saved test results file and print the statistics about how long the testing took to complete.';

	public static function command_aliases()
	{
		return array('analyze_run_time');
	}
	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array('pts_types', 'is_result_file'), null)
		);
	}
	public static function compare_test_time($a, $b)
	{
		if($a[0] == $b[0]) return 0;
		return $a[0] > $b[0] ? -1 : 1;
	}
	public static function run($r)
	{
		$result_file = new pts_result_file($r[0]);
		$accumulated_times = array();
		foreach($result_file->get_system_identifiers() as $si)
		{
			$accumulated_times[$si] = array();
		}

		$time_segments = array();
		foreach($result_file->get_result_objects() as $ro)
		{
			$table = array();
			$avg_time = array();
			foreach($ro->test_result_buffer->get_buffer_items() as $item)
			{
				$test_run_times = $item->get_run_times();
				if(!empty($test_run_times))
				{
					foreach($test_run_times as &$t)
					{
						$t = ceil($t);
					}
					$total_time = array_sum($test_run_times);
					$avg_time[] = $total_time;
					$accumulated_times[$item->get_result_identifier()][] = $total_time;
					$table[] = array($item->get_result_identifier() . ': ', pts_strings::format_time($total_time), '   ' . implode(' ', $test_run_times) . 's');
				}
			}

			$output = PHP_EOL . pts_client::cli_just_bold(trim($ro->test_profile->get_title() . PHP_EOL . $ro->get_arguments_description())) . PHP_EOL;
			$output .= pts_user_io::display_text_table($table) . PHP_EOL;
			if(($c = count($avg_time)) > 1)
			{
				$avg_time = array_sum($avg_time) / count($avg_time);
				if($c > 2)
					$output .= PHP_EOL . pts_client::cli_just_bold('Average Test Run Time: ') . pts_strings::format_time($avg_time) . PHP_EOL;
			}

			$time_segments[] = array($avg_time, $output);
		}

		usort($time_segments, array('analyze_run_times', 'compare_test_time'));
		foreach($time_segments as $r)
		{
			echo $r[1];
		}

		$table = array();
		$final_avg = array();
		foreach($accumulated_times as $identifier => $times)
		{
			if(count($times) > 1)
			{
				$final_avg[] = array_sum($times);
				$table[] = array($identifier, '    ' . pts_strings::format_time(array_sum($times)));
			}
		}
		if(!empty($table))
		{
			echo PHP_EOL . PHP_EOL . '#####' . PHP_EOL;
			if(count($table) > 1)
			{
				$table[] = array('Average:', '    ' . pts_strings::format_time(array_sum($final_avg) / count($final_avg)));
			}
			echo pts_client::cli_just_bold('ACCUMULATED TIME:') . PHP_EOL;
			echo pts_user_io::display_text_table($table) . PHP_EOL;
			echo '#####' . PHP_EOL;
		}
	}
}

?>
