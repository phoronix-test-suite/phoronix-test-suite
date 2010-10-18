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

class reference_comparison implements pts_option_interface
{
	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, "pts_find_result_file", "result", "No result file was found.")
		);
	}
	public static function run($r)
	{
		$result = $r["result"];

		$reference_test_globals = pts_result_comparisons::reference_tests_for_result($result);

		if(count($reference_test_globals) == 0)
		{
			echo "\nNo reference tests are available.\n\n";
			return false;
		}

		$merge_args = array($r[0]);
		pts_client::set_test_flags();
		if((pts_c::$test_flags & pts_c::auto_mode))
		{
			$reference_comparisons = pts_read_assignment("REFERENCE_COMPARISONS");

			foreach($reference_comparisons as $comparison)
			{
				array_push($merge_args, $comparison);
			}
		}
		else
		{
			$comparable = array();

			foreach($reference_test_globals as $merge_select_object)
			{
				if(count($merge_select_object->get_selected_identifiers()) != 0)
				{
					array_push($comparable, array_pop($merge_select_object->get_selected_identifiers()));
				}

			}

			$merge_index = pts_user_io::prompt_text_menu("Select a reference system", $comparable, false, true);

			array_push($merge_args, $reference_test_globals[$merge_index]);
		}

		pts_set_assignment("REFERENCE_COMPARISON", true);
		$merged_results = call_user_func_array(array("pts_merge", "pts_merge_test_results_array"), $merge_args);

		pts_client::save_test_result($r[0] . "/composite.xml", $merged_results);

		if(($title = pts_read_assignment("PREV_SAVE_NAME_TITLE")) == false)
		{
			$result_file = new pts_result_file($r[0]);
			$title = $result_file->get_title();
		}

		pts_set_assignment_next("PREV_SAVE_NAME_TITLE", $title . (strpos($title, "Comparison") === false ? " Comparison" : null));

		pts_client::display_web_page(SAVE_RESULTS_DIR . $r[0] . "/composite.xml");
	}
}

?>
