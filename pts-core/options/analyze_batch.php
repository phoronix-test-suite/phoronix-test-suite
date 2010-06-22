<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel

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

class analyze_batch implements pts_option_interface
{
	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, "pts_find_result_file", "base_file", "No result file was found.")
		);
	}
	public static function run($args)
	{
		$base_file = $args["base_file"];

		do
		{
			$rand_file = rand(1000, 9999);
			$save_to = "analyze-" . $rand_file . '/';
		}
		while(is_dir(SAVE_RESULTS_DIR . $save_to));
		$save_to .= "composite.xml";

		// Analyze Results
		$SAVED_RESULTS = pts_merge::generate_analytical_batch_xml($base_file);
		pts_save_result($save_to, $SAVED_RESULTS);
		pts_set_assignment_next("PREV_SAVE_RESULTS_IDENTIFIER", $save_to);
		echo "Results Saved To: " . SAVE_RESULTS_DIR . $save_to . "\n\n";
		pts_client::display_web_page(SAVE_RESULTS_DIR . dirname($save_to) . "/index.html");
	}
}

?>
