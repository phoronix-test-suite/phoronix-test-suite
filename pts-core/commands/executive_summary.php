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

class executive_summary implements pts_option_interface
{
	const doc_section = 'Result Analysis';
	const doc_description = 'This option will attempt to auto-generate a textual executive summary for a result file to highlight prominent results / averages.';

	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array('pts_types', 'is_result_file'), null)
		);
	}
	public static function run($r)
	{
		$result_file = new pts_result_file($r[0]);
		$error = null;
		$exec_summary = pts_result_file_analyzer::generate_executive_summary($result_file, '', $error);

		if(!empty($exec_summary))
		{
			echo pts_client::cli_just_bold($result_file->get_title()) . PHP_EOL;
			echo PHP_EOL . implode(PHP_EOL . PHP_EOL, $exec_summary) . PHP_EOL;
		}
		else if($error)
		{
			echo PHP_EOL . $error . PHP_EOL;
		}
	}
}

?>
