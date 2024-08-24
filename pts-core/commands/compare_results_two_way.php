<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2019, Phoronix Media
	Copyright (C) 2019, Michael Larabel

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

class compare_results_two_way implements pts_option_interface
{
	const doc_section = 'Result Management';
	const doc_description = 'This option allows you to specify a result file and from there to compare two individual runs within that result file for looking at wins/losses and other metrics in a head-to-head type comparison.';
	static $longest_identifier = 0;

	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array('pts_types', 'is_result_file'), null),
		);
	}
	protected static function sort_two_way_data_desc($a, $b)
	{
		self::$longest_identifier = max(self::$longest_identifier, strlen($a[0]->test_profile->get_title()), strlen($b[0]->test_profile->get_title()), strlen($a[0]->get_arguments_description()), strlen($b[0]->get_arguments_description()));
		$a = $a[1];
		$b = $b[1];

		if($a == $b) return 0;
		return $a > $b ? -1 : 1;
	}
	protected static function sort_two_way_data_asc($a, $b)
	{
		$a = $a[1];
		$b = $b[1];

		if($a == $b) return 0;
		return $a < $b ? -1 : 1;
	}
	public static function run($r)
	{
		$result_file = new pts_result_file($r[0]);
		$result_file_identifiers = $result_file->get_system_identifiers();

		if(count($result_file_identifiers) < 2)
		{
			echo PHP_EOL . 'There must be at least two systems in the result file.' . PHP_EOL . PHP_EOL;
			return false;
		}

		$first_run = pts_user_io::prompt_text_menu('Select the first test run for head-to-head/two-way comparison', $result_file_identifiers, false);
		unset($result_file_identifiers[array_search($first_run, $result_file_identifiers)]);
		$second_run = pts_user_io::prompt_text_menu('Select the second test run for head-to-head/two-way comparison', $result_file_identifiers, false);

		$wins = array();
		$losses = array();
		$too_close = array();

		foreach($result_file->get_result_objects() as $ro)
		{
			$ro->set_result_precision(3);
			if($ro->normalize_buffer_values($first_run) == false)
			{
				continue;
			}

			$first_run_result = $ro->get_result_value_from_name($first_run);
			if(empty($first_run_result) || !is_numeric($first_run_result))
			{
				continue;
			}
			$second_run_result = $ro->get_result_value_from_name($second_run);
			if(empty($second_run_result) || !is_numeric($second_run_result))
			{
				continue;
			}

			if($second_run_result > 1.01)
			{
				$wins[] = array($ro, $second_run_result);
			}
			else if($second_run_result < 0.99)
			{
				$losses[] = array($ro, $second_run_result);
			}
			else
			{
				$too_close[] = array($ro, $second_run_result);
			}
		}

		usort($wins, array('compare_results_two_way', 'sort_two_way_data_desc'));
		usort($losses, array('compare_results_two_way', 'sort_two_way_data_desc'));

		// , $second_run . pts_client::cli_colored_text(' Draws', 'gray', true) => $too_close
		echo pts_client::cli_just_bold($second_run . ' vs. ' . $first_run . ' Baseline') . PHP_EOL . PHP_EOL;
		foreach(array(pts_client::cli_colored_text($second_run . ' Wins', 'green', true) => $wins, pts_client::cli_colored_text($second_run . ' Losses', 'red', 'true') => $losses) as $group => $data)
		{
			echo $group . PHP_EOL;
			foreach($data as $result_data)
			{
				echo pts_client::cli_just_bold($result_data[0]->test_profile->get_title()) . str_repeat(' ', self::$longest_identifier - strlen($result_data[0]->test_profile->get_title())) . ' ' . $result_data[1] . 'x' . PHP_EOL;

				if($result_data[0]->get_arguments_description() != null)
				{
					echo pts_client::cli_just_italic($result_data[0]->get_arguments_description()) . PHP_EOL;
				}
			}
			echo pts_client::cli_just_underline(count($data) . ' Results') . PHP_EOL . PHP_EOL;
		}

return;


		echo pts_result_file_analyzer::display_results_baseline_two_way_compare($baseline, false, true);
	}
}

?>
