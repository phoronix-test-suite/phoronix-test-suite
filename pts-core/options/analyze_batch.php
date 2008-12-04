<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel

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
	public static function run($r)
	{
		pts_load_function_set("merge");

		$BASE_FILE = pts_find_result_file($r[0]);
		$SAVE_TO = $r[1];

		if($BASE_FILE == false)
		{
			echo "\n" . $r[0] . " couldn't be found.\n";
		}
		else
		{
			if(!empty($SAVE_TO) && !is_dir(SAVE_RESULTS_DIR . $SAVE_TO))
			{
				$SAVE_TO .= "/composite.xml";
			}
			else
			{
				$SAVE_TO = null;
			}

			if(empty($SAVE_TO))
			{
				do
				{
					$rand_file = rand(1000, 9999);
					$SAVE_TO = "analyze-" . $rand_file . '/';
				}
				while(is_dir(SAVE_RESULTS_DIR . $SAVE_TO));

				$SAVE_TO .= "composite.xml";
			}

			// Analyze Results
			$SAVED_RESULTS = pts_merge_batch_tests_to_line_comparison(@file_get_contents($BASE_FILE));
			pts_save_result($SAVE_TO, $SAVED_RESULTS);
			echo "Results Saved To: " . SAVE_RESULTS_DIR . $SAVE_TO . "\n\n";
			pts_display_web_browser(SAVE_RESULTS_DIR . $SAVE_TO);
		}
	}
}

?>
