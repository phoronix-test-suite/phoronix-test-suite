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

class result_file_to_csv implements pts_option_interface
{
	const doc_section = 'Result Management';
	const doc_description = "This option will read a saved test results file and output the system hardware and software information along with the results to a CSV output. The CSV (Comma Separated Values) output can then be loaded into a spreadsheet for easy viewing.";

	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array("pts_types", "is_result_file"), null, "No result file was found.")
		);
	}
	public static function run($r)
	{
		$result_file = new pts_result_file($r[0]);
		$result_output = null;

		$result_output .= $result_file->get_title() . "\n";
		$result_output .= $result_file->get_description() . "\n\n";

		$system_identifiers = $result_file->get_system_identifiers();
		$system_hardware = $result_file->get_system_hardware();
		$system_software = $result_file->get_system_software();

		for($i = 0; $i < count($system_identifiers); $i++)
		{
			$result_output .= $system_identifiers[$i] . "\n";
			$result_output .= $system_hardware[$i] . "\n" . $system_software[$i] . "\n\n";
		}

		$test_object = array_pop($result_file->get_result_objects());

		foreach($test_object->test_result_buffer->get_identifiers() as $identifier)
		{
			$result_output .= "," . $identifier;
		}
		$result_output .= "\n";

		foreach($result_file->get_result_objects() as $result_object)
		{
			$result_output .= $result_object->test_profile->get_title() . " - " . $result_object->get_arguments_description();

			foreach($result_object->test_result_buffer->get_values() as $value)
			{
				$result_output .= "," . $value;
			}
			$result_output .= "\n";
		}

		// To save the result:
		/*
		$file = // the path;

		if(substr($file, -4) != ".csv")
		{
			$file .= ".csv";
		}

		file_put_contents($file, $result_output);
		*/

		echo $result_output;
	}
}

?>
