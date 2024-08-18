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

class extract_from_result_file implements pts_option_interface
{
	const doc_section = 'Result Management';
	const doc_description = 'This option will extract a single set of test results from a saved results file that contains multiple test results that have been merged. The user is then prompted to specify a new result file name and select which result identifier to extract.';

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

		$extract_identifiers = pts_user_io::prompt_text_menu('Select the test run(s) to extract', $result_file_identifiers, true);
		$remove_identifiers = array_diff($result_file_identifiers, $extract_identifiers);
		$result_file->remove_run($remove_identifiers);

		do
		{
			echo PHP_EOL . 'Enter new result file to extract to: ';
			$extract_to = pts_user_io::read_user_input();
			$extract_to = pts_test_run_manager::clean_save_name($extract_to);
		}
		while(empty($extract_to));

		pts_client::save_test_result($extract_to . '/composite.xml', $result_file->get_xml());
		pts_client::display_result_view($extract_to, false);
	}
}

?>
