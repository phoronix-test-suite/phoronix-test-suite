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

class analyze_result_file implements pts_option_interface
{
	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, "pts_find_result_file", "base_file", "No result file was found.")
		);
	}
	public static function run($args)
	{
		// TODO: add more stats output of information to analyze-result-file
		$result_file = new pts_result_file($args["base_file"]);
		pts_client::$display->generic_heading($result_file->get_title());

		foreach($result_file->get_system_identifiers() as $system_identifier)
		{
			$wins[$system_identifier] = 0;
		}

		foreach($result_file->get_result_objects() as $result_object)
		{
			$win = null;
			$win_value = -1;

			$identifiers = $result_object->get_result_buffer()->get_identifiers();
			$values = $result_object->get_result_buffer()->get_values();

			for($i = 0; $i < count($values); $i++)
			{
				if($result_object->get_proportion() == "HIB" && $values[$i] > $win_value)
				{
					$win = $identifiers[$i];
					$win_value = $values[$i];
				}
				else if($result_object->get_proportion() == "LIB" && ($values[$i] < $win_value || $win_value == -1))
				{
					$win = $identifiers[$i];
					$win_value = $values[$i];
				}
			}

			if($win != null && $win_value != -1)
			{
				$wins[$win] += 1;
			}
		}

		echo "\nNumber of Test Wins\n";

		foreach($wins as $system_identifier => $win_count)
		{
			echo "- " . $system_identifier . ": " . $win_count . "\n";
		}

		echo "\n";
	}
}

?>
