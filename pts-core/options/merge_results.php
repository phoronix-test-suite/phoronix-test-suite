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

class merge_results
{
	public static function run($r)
	{
		include_once("pts-core/functions/pts-functions-merge.php");

		$BASE_FILE = $r[0];
		$MERGE_FROM_FILE = $r[1];
		$MERGE_TO = $r[2];

		if(empty($BASE_FILE) || empty($MERGE_FROM_FILE))
		{
			echo "\nTwo saved result profile names must be supplied.\n";
		}
		else
		{
			$BASE_FILE = pts_find_result_file($BASE_FILE);
			$MERGE_FROM_FILE = pts_find_result_file($MERGE_FROM_FILE);

			if($BASE_FILE == false || $MERGE_FROM_FILE == false)
			{
				echo "\n" . $r[0] . " or " . $r[1] . " couldn't be found.\n";
			}
			else
			{
				if(!empty($MERGE_TO) && !is_dir(SAVE_RESULTS_DIR . $MERGE_TO))
				{
					$MERGE_TO .= "/composite.xml";
				}
				else
				{
					$MERGE_TO = null;
				}

				if(empty($MERGE_TO))
				{
					do
					{
						$rand_file = rand(1000, 9999);
						$MERGE_TO = "merge-" . $rand_file . '/';
					}
					while(is_dir(SAVE_RESULTS_DIR . $MERGE_TO));

					$MERGE_TO .= "composite.xml";
				}

				// Merge Results
				$MERGED_RESULTS = pts_merge_test_results(file_get_contents($BASE_FILE), file_get_contents($MERGE_FROM_FILE));
				pts_save_result($MERGE_TO, $MERGED_RESULTS);
				echo "Merged Results Saved To: " . SAVE_RESULTS_DIR . $MERGE_TO . "\n\n";
				pts_display_web_browser(SAVE_RESULTS_DIR . $MERGE_TO);
			}
		}
	}
}

?>
