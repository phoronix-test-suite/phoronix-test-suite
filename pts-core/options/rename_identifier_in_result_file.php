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

class rename_identifier_in_result_file implements pts_option_interface
{
	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, "pts_find_result_file", "result_file", "No result file was found.")
		);
	}
	public static function run($r)
	{
		$result = $r["result_file"];

		$result_file = new pts_result_file($result);
		$result_file_identifiers = $result_file->get_system_identifiers();

		$rename_identifier = pts_text_select_menu("Select the test run to rename", $result_file_identifiers);
		$rename_identifier_new = pts_text_input("Enter the new identifier");
		$merge_selects = array();

		foreach($result_file_identifiers as $identifier)
		{
			$this_merge_select = new pts_result_merge_select($result, $identifier);

			if($identifier == $rename_identifier && $rename_identifier != $rename_identifier_new)
			{
				$this_merge_select->rename_identifier($rename_identifier_new);
			}

			array_push($merge_selects, $this_merge_select);
		}

		foreach(array("benchmark-logs", "system-logs", "installation-logs") as $dir_name)
		{
			if(is_dir(SAVE_RESULTS_DIR . $r[0] . "/" . $dir_name . "/" . $rename_identifier))
			{
				rename(SAVE_RESULTS_DIR . $r[0] . "/" . $dir_name . "/" . $rename_identifier, SAVE_RESULTS_DIR . $r[0] . "/" . $dir_name . "/" . $rename_identifier_new);
			}
		}

		$extract_result = pts_merge::merge_test_results_array($merge_selects);
		pts_save_result($r[0] . "/composite.xml", $extract_result);
		pts_set_assignment_next("PREV_SAVE_RESULTS_IDENTIFIER", $r[0]);
		pts_client::display_web_page(SAVE_RESULTS_DIR . $r[0] . "/composite.xml");
	}
}

?>
