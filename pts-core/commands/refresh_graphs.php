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

class refresh_graphs implements pts_option_interface
{
	const doc_section = 'Result Management';
	const doc_description = 'This option will re-render and save all result graphs within a saved file. This option can be used when making modifications to the graphing code or its color/option configuration file and testing the changes.';

	public static function command_aliases()
	{
		return array('refresh_graph');
	}
	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array('pts_types', 'is_result_file'), null)
		);
	}
	public static function run($r)
	{
		foreach($r as $identifier)
		{
			pts_client::regenerate_graphs($identifier);
			$graphs = pts_file_io::glob( PTS_SAVE_RESULTS_PATH . $identifier . '/result-graphs/*');
			$graph_bytes = 0;
			foreach($graphs as $graph)
			{
				$graph_bytes += filesize($graph);
			}

			$t = array();
			$t[] = array(pts_client::cli_just_bold('Result Graphs: '), count($graphs));
			$t[] = array(pts_client::cli_just_bold('Graph Size: '), $graph_bytes . ' Bytes');
			echo pts_user_io::display_text_table($t) . PHP_EOL;
			echo PHP_EOL . 'The ' . $identifier . ' result file graphs have been refreshed.' . PHP_EOL;
			pts_client::display_result_view($identifier, false);
		}
	}
}

?>
