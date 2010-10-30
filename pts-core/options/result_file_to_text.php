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

class result_file_to_text implements pts_option_interface
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

		$result_output .= $result_file->get_title() . "\n";
		$result_output .= $result_file->get_suite_description() . "\n\n\n";

		$system_identifiers = $result_file->get_system_identifiers();
		$system_hardware = $result_file->get_system_hardware();
		$system_software = $result_file->get_system_software();

		for($i = 0; $i < count($system_identifiers); $i++)
		{
			$result_output .= $system_identifiers[$i] . ": \n\n";
			$result_output .= "\t" . $system_hardware[$i] . "\n\n\t" . $system_software[$i] . "\n\n";
		}

		foreach($result_file->get_result_objects() as $result_object)
		{
			$result_output .= trim($result_object->test_profile->get_title() . " " . $result_object->test_profile->get_version() . "\n" . $result_object->get_arguments_description());

			foreach($result_object->test_result_buffer->get_buffer_items() as $buffer_item)
			{
				$result_output .= $buffer_item->get_result_identifier() . ": " . $buffer_item->get_result_value() . "\n";
			}

			for($i = 0; $i < count($test_identifiers); $i++)
			{
				$result_output .= $test_identifiers[$i] . ": " . $test_values[$i] . "\n";
			}
			$result_output .= "\n";
		}

		/*
		$file = "SAVE_TO";

		if(substr($file, -4) != ".txt")
		{
			$file .= ".txt";
		}
		file_put_contents($file, $result_output);
		*/

		echo $result_output;

	}
}

?>
