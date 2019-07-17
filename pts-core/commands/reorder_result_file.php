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

class reorder_result_file implements pts_option_interface
{
	const doc_section = 'Result Management';
	const doc_description = 'This option is used if you wish to manually change the order in which test results are shown in the Phoronix Test Suite Results Viewer and the contained graphs. The user must specify a saved results file and then they will be prompted to select the results identifiers one at a time in the order they would like them to be displayed from left to right.';

	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array('pts_types', 'is_result_file'), null)
		);
	}
	public static function command_aliases()
	{
		return array('sort_result_file');
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

		echo PHP_EOL . 'Enter The New Order To Display The New Results, From Left To Right.' . PHP_EOL;

		$sorted_identifiers = array();
		do
		{
			$extract_identifier = pts_user_io::prompt_text_menu('Select the test run to be showed next', $result_file_identifiers);
			$sorted_identifiers[] = $extract_identifier;

			$old_identifiers = $result_file_identifiers;
			$result_file_identifiers = array();

			foreach($old_identifiers as $identifier)
			{
				if($identifier != $extract_identifier)
				{
					$result_file_identifiers[] = $identifier;
				}
			}
		}
		while(count($result_file_identifiers) > 0);

		$result_file->reorder_runs($sorted_identifiers);
		pts_client::save_test_result($result_file->get_file_location(), $result_file->get_xml());
		pts_client::display_result_view($result_file, false);
	}
}

?>
