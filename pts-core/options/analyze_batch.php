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

class analyze_batch implements pts_option_interface
{
	public static function required_function_sets()
	{
		return array("merge");
	}
	public static function run($r)
	{
		if(($base_file = pts_find_result_file($r[0])) == false)
		{
			echo "\n" . $r[0] . " could not be found.\n";
		}
		else
		{
			$save_to = $r[1];

			if(!empty($save_to) && !is_dir(SAVE_RESULTS_DIR . $save_to))
			{
				$save_to .= "/composite.xml";
			}
			else
			{
				$save_to = null;
			}

			if(empty($save_to))
			{
				do
				{
					$rand_file = rand(1000, 9999);
					$save_to = "analyze-" . $rand_file . '/';
				}
				while(is_dir(SAVE_RESULTS_DIR . $save_to));

				$save_to .= "composite.xml";
			}

			// Analyze Results
			$SAVED_RESULTS = pts_generate_analytical_batch_xml($base_file);
			pts_save_result($save_to, $SAVED_RESULTS);
			pts_set_assignment_next("PREV_SAVE_RESULTS_IDENTIFIER", $save_to);
			echo "Results Saved To: " . SAVE_RESULTS_DIR . $save_to . "\n\n";
			pts_display_web_browser(SAVE_RESULTS_DIR . dirname($save_to) . "/index.html");
		}
	}
}

?>
