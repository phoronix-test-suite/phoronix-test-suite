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

class result_file_to_csv implements pts_option_interface
{
	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, "pts_find_result_file", "result_file", "No result file was found.")
		);
	}
	public static function run($r)
	{
		$result_file = new pts_result_file($r["result_file"]);
		$result_output = null;

		$result_output .= $result_file->get_title() . "\n" . $result_file->get_suite_name() . " - " . $result_file->get_suite_version() . "\n";
		$result_output .= $result_file->get_suite_description() . "\n\n";

		$system_identifiers = $result_file->get_system_identifiers();
		$system_hardware = $result_file->get_system_hardware();
		$system_software = $result_file->get_system_software();

		for($i = 0; $i < count($system_identifiers); $i++)
		{
			$result_output .= $system_identifiers[$i] . "\n";
			$result_output .= $system_hardware[$i] . "\n" . $system_software[$i] . "\n\n";
		}

		$test_object = array_pop($result_file->get_result_objects());

		foreach($test_object->get_result_buffer()->get_identifiers() as $identifier)
		{
			$result_output .= "," . $identifier;
		}
		$result_output .= "\n";

		foreach($result_file->get_result_objects() as $result_object)
		{
			$result_output .= $result_object->test_result->test_profile->get_title() . " - " . $result_object->test_result->get_used_arguments_description();

			foreach($result_object->get_result_buffer()->get_values() as $value)
			{
				$result_output .= "," . $value;
			}
			$result_output .= "\n";
		}

		if(pts_is_assignment("SAVE_TO"))
		{
			$file = pts_read_assignment("SAVE_TO");

			if(substr($file, -4) != ".csv")
			{
				$file .= ".csv";
			}

			pts_set_assignment_next("PREV_CSV_FILE", $file);
			file_put_contents($file, $result_output);
		}
		else
		{
			echo $result_output;
		}
	}
}

?>
