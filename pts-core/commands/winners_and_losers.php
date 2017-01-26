<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2017, Phoronix Media
	Copyright (C) 2017, Michael Larabel

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

class winners_and_losers implements pts_option_interface
{
	const doc_section = 'Result Management';
	const doc_description = 'This option is used if you wish to analyze a result file to see which runs produced the most wins/losses of those result identifiers in the saved file.';

	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array('pts_types', 'is_result_file'), null)
		);
	}
	public static function run($args)
	{
		$result_file = new pts_result_file($args[0]);
		$result_file_identifiers = $result_file->get_system_identifiers();

		if(count($result_file_identifiers) < 2)
		{
			echo PHP_EOL . 'There are not multiple test runs in this result file.' . PHP_EOL;
			return false;
		}

		echo $result_file->get_title() . PHP_EOL;
		echo 'RESULT COUNT: ' . $result_file->get_test_count() . PHP_EOL . PHP_EOL;
		$winners = array();
		$losers = array();

		foreach($result_file->get_result_objects() as $result)
		{
			if($result->test_result_buffer->get_count() < 2)
			{
				continue;
			}

			$winner = $result->get_result_first();
			$loser = $result->get_result_last();

			if(!isset($winners[$winner]))
			{
				$winners[$winner] = 1;
			}
			else
			{
				$winners[$winner]++;
			}

			if(!isset($losers[$loser]))
			{
				$losers[$loser] = 1;
			}
			else
			{
				$losers[$loser]++;
			}
		}

		arsort($winners);
		arsort($losers);

		echo 'WINS:' . PHP_EOL;
		foreach($winners as $identifier => $count)
		{
			echo $identifier . ': ' . $count . PHP_EOL;
		}
		echo PHP_EOL . 'LOSSES: ' . PHP_EOL;
		foreach($losers as $identifier => $count)
		{
			echo $identifier . ': ' . $count . PHP_EOL;
		}
		echo PHP_EOL;
	}
	public static function invalid_command($passed_args = null)
	{
		pts_tests::recently_saved_results();
	}
}

?>
