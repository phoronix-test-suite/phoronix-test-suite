<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2014 - 2021, Phoronix Media
	Copyright (C) 2014 - 2021, Michael Larabel

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

class auto_sort_result_file implements pts_option_interface
{
	const doc_section = 'Result Management';
	const doc_description = 'This option is used if you wish to automatically attempt to sort the results by their result identifier string. Alternatively, if using the environment variable "SORT_BY" other sort modes can be used, such as SORT_BY=date / SORT_BY=date-desc for sorting by the test run-time/date.';

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

		$extract_selects = array();
		echo PHP_EOL . 'Automatically sorting the results...' . PHP_EOL;

		switch(pts_env::read('SORT_BY'))
		{
			case 'date':
			case 'date-asc':
				$result_file_identifiers = $result_file->get_system_identifiers_by_date();
				break;
			case 'date-desc':
				$result_file_identifiers = array_reverse($result_file->get_system_identifiers_by_date());
				break;
			case 'identifier':
			default:
				sort($result_file_identifiers);
				break;
		}

		$result_file->reorder_runs($result_file_identifiers);
		pts_client::save_test_result($result_file->get_file_location(), $result_file->get_xml());
		pts_client::display_result_view($result_file, false);
	}
}

?>
