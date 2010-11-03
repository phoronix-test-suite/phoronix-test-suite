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
		new pts_argument_check(0, array("pts_types", "is_result_file"), null, "No result file was found.")
		);
	}
	public static function run($r)
	{
		$result = $r[0];

		$reference_test_globals = pts_result_comparisons::reference_tests_for_result($result);

		if(count($reference_test_globals) == 0)
		{
			echo "\nNo reference tests are available.\n\n";
			return false;
		}

		$comparable = array();
		foreach($reference_test_globals as $merge_select_object)
		{
			if(count($merge_select_object->get_selected_identifiers()) != 0)
			{
				array_push($comparable, array_pop($merge_select_object->get_selected_identifiers()));
			}
		}

		$merge_index = pts_user_io::prompt_text_menu("Select a reference system", $comparable, false, true);
		$merge_args = array($r[0]);
		array_push($merge_args, $reference_test_globals[$merge_index]);

		$merged_results = call_user_func(array("pts_merge", "merge_test_results_array"), $merge_args, array("is_reference_comparison" => 1));
		pts_client::save_test_result($r[0] . "/composite.xml", $merged_results);
		pts_client::display_web_page(PTS_SAVE_RESULTS_PATH . $r[0] . "/composite.xml");
	}
}

?>
