<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel

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

class finish_run implements pts_option_interface
{
	public static function run($r)
	{
		if(!pts_is_test_result($r[0]))
		{
			echo "\nThe name of a test result file must be passed as an argument.\n";
			return false;
		}

		$result_file = new pts_result_file($r[0]);

		$system_identifiers = $result_file->get_system_identifiers();
		$test_positions = array();

		$pos = 0;
		foreach($result_file->get_result_objects() as $result_object)
		{
			$this_result_object_identifiers = $result_object->get_identifiers();

			foreach($system_identifiers as $system_identifier)
			{
				if(!in_array($system_identifier, $this_result_object_identifiers))
				{
					if(!isset($test_positions[$system_identifier]))
					{
						$test_positions[$system_identifier] = array();
					}

					array_push($test_positions[$system_identifier], $pos);
				}
			}

			$pos++;
		}

		$incomplete_identifiers = array_keys($test_positions);

		if(count($incomplete_identifiers) == 0)
		{
			echo "\nIt appears that there are no incomplete test results within this saved file.\n\n";
			return false;
		}

		$selected = pts_text_select_menu("Select which incomplete test run you would like to finish", $incomplete_identifiers);

		pts_run_option_next("run_test", $r, array("FINISH_INCOMPLETE_RUN" => true, "TESTS_TO_COMPLETE" => $test_positions[$selected], "AUTO_TEST_RESULTS_IDENTIFIER" => $selected));
	}
}

?>
