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

class result_file_stats implements pts_option_interface
{
	const doc_section = 'Result Analysis';
	const doc_description = 'This option is used if you wish to analyze a result file by seeing various statistics on the result data for result files containing at least two sets of data.';

	public static function command_aliases()
	{
		return array('winners_and_losers', 'result_stats');
	}
	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array('pts_types', 'is_result_file'), null)
		);
	}
	public static function run($args)
	{
		$result_file = new pts_result_file($args[0]);
		echo '   ' . pts_client::cli_colored_text($result_file->get_title(), 'gray', true) . PHP_EOL . PHP_EOL;

		if($result_file->get_system_count() < 2)
		{
			echo PHP_EOL . 'There are not multiple test runs in this result file.' . PHP_EOL;
			return false;
		}

		echo pts_result_file_analyzer::display_results_wins_losses($result_file);
		echo pts_result_file_analyzer::display_result_file_stats_pythagorean_means($result_file);
	}
}

?>
