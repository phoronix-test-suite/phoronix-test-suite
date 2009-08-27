<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel

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

class merge_results implements pts_option_interface
{
	public static function required_function_sets()
	{
		return array("merge");
	}
	public static function run($r)
	{
		$r = array_map("pts_find_result_file", $r);
		$result_files_to_merge = array();

		foreach($r as $result_file)
		{
			if($result_file != false)
			{
				array_push($result_files_to_merge, $result_file);
			}
		}

		if(count($result_files_to_merge) < 2)
		{
			echo "\nAt least two saved result names must be supplied.\n";
			return;
		}

		do
		{
			$rand_file = rand(1000, 9999);
			$merge_to_file = "merge-" . $rand_file . '/';
		}
		while(is_dir(SAVE_RESULTS_DIR . $merge_to_file));
		$merge_to_file .= "composite.xml";

		// Merge Results
		$merged_results = call_user_func_array("pts_merge_test_results", $result_files_to_merge);
		pts_save_result($merge_to_file, $merged_results);
		echo "Merged Results Saved To: " . SAVE_RESULTS_DIR . $merge_to_file . "\n\n";
			pts_display_web_browser(SAVE_RESULTS_DIR . dirname($merge_to_file) . "/index.html");
	}
}

?>
