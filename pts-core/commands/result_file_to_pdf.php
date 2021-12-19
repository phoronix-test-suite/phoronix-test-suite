<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2020, Phoronix Media
	Copyright (C) 2009 - 2020, Michael Larabel

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

class result_file_to_pdf implements pts_option_interface
{
	const doc_section = 'Result Export';
	const doc_description = 'This option will read a saved test results file and output the system hardware and software information along with the results to a PDF file. The outputted file appears in the user home directory or can otherwise be controlled via the OUTPUT_DIR and OUTPUT_FILE environment variables.';

	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array('pts_types', 'is_result_file'), null)
		);
	}
	public static function run($r)
	{
		if(!pts_svg_dom_gd::is_supported())
		{
			echo pts_client::cli_just_bold('PHP GD support and TTF support are required for the PDF output if wanting to display the benchmark result graphs.') . PHP_EOL . PHP_EOL;
			//return false;
		}

		$_REQUEST['force_format'] = 'PNG'; // Force to PNG renderer
		$_REQUEST['svg_dom_gd_no_interlacing'] = true; // Otherwise FPDF will fail
		$tdir = pts_client::create_temporary_directory();
		pts_svg_dom_gd::generate_result_file_graphs($r[0], $tdir);

		$result_file = new pts_result_file($r[0]);
		$result_output = pts_result_file_output::result_file_to_pdf($result_file, '', 'S');

		pts_client::save_output_handler($result_output, $r[0], 'pdf');
	}
}

?>
