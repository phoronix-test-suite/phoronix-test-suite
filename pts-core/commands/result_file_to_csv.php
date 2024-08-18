<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2019, Phoronix Media
	Copyright (C) 2009 - 2019, Michael Larabel

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

class result_file_to_csv implements pts_option_interface
{
	const doc_section = 'Result Export';
	const doc_description = 'This option will read a saved test results file and output the system hardware and software information along with the results to a CSV output (Comma Separated Values) file. This file can then be loaded into a spreadsheet for easy viewing. The outputted file appears in the user home directory or can otherwise be controlled via the OUTPUT_DIR and OUTPUT_FILE environment variables.';

	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array('pts_types', 'is_result_file'), null)
		);
	}
	public static function run($r)
	{
		$result_file = new pts_result_file($r[0]);
		$result_output = pts_result_file_output::result_file_to_csv($result_file);
		pts_client::save_output_handler($result_output, $r[0], 'csv');
	}
}

?>
