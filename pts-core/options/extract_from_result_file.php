<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2010, Phoronix Media
	Copyright (C) 2009 - 2010, Michael Larabel

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
	public static function required_function_sets()
	{
		return array("merge");
	}
	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, "pts_find_result_file", "result", "No result file was found.")
		);
	}
	public static function run($args)
	{
		$result = $args["result"];

		$result_file = new pts_result_file($result);
		$result_file_identifiers = $result_file->get_system_identifiers();

		if(count($result_file_identifiers) < 2)
		{
			echo "\nThere are not multiple test runs in this result file.\n";
			return false;
		}

		$extract_identifiers = explode(',', pts_text_select_menu("Select the test run to extract", $result_file_identifiers, true));
		$extract_selects = array();

		print_r($extract_identifiers);

		foreach($extract_identifiers as $extract_identifier)
		{
			array_push($extract_selects, new pts_result_merge_select($result, $extract_identifier));
		}

		print_r($extract_selects);

		do
		{
			echo "\nEnter new result file to extract to: ";
			$extract_to = pts_read_user_input();
		}
		while(empty($extract_to) || pts_is_test_result($extract_to));

		$extract_result = call_user_func_array("pts_merge_test_results", $extract_selects);
		pts_save_result($extract_to . "/composite.xml", $extract_result);
		pts_set_assignment_next("PREV_SAVE_RESULTS_IDENTIFIER", $extract_to);
		pts_display_web_browser(SAVE_RESULTS_DIR . $extract_to . "/composite.xml");
	}
}

?>
