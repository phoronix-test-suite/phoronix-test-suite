<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2019, Phoronix Media
	Copyright (C) 2008 - 2019, Michael Larabel

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

class compare_results_to_baseline implements pts_option_interface
{
	const doc_section = 'Result Management';
	const doc_description = 'This option allows you to specify a result as a baseline (first parameter) and a second result file (second parameter) that will offer some analysis for showing how the second result compares to the first in matching tests.';

	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array('pts_types', 'is_result_file'), null),
		new pts_argument_check(1, array('pts_types', 'is_result_file'), null)
		);
	}
	public static function run($r)
	{
		if(count($r) != 2)
		{
			echo PHP_EOL . 'Two results must be specified: phoronix-test-suite compare-results-to-baseline <baseline result file> <compare result file>' . PHP_EOL;
			return false;
		}
		$baseline = new pts_result_file($r[0]);
		echo 'Baseline: ' . $baseline->get_identifier() . PHP_EOL;
		if(count($baseline->get_system_identifiers()) != 1)
		{
			echo PHP_EOL . 'This feature requires only one system/result to be present in the result file.' . PHP_EOL;
			return false;
		}
		$baseline->rename_run(null, 'Baseline');

		$result = new pts_result_file($r[1]);
		echo 'Result: ' . $result->get_identifier() . PHP_EOL;
		if(count($result->get_system_identifiers()) != 1)
		{
			echo PHP_EOL . 'This feature requires only one system/result to be present in the result file.' . PHP_EOL;
			return false;
		}
		$result->rename_run(null, 'Result');

		$baseline->add_to_result_file($result, true);
		echo pts_result_file_analyzer::display_results_baseline_two_way_compare($baseline, false, true);
	}
}

?>
