<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2011, Phoronix Media
	Copyright (C) 2009 - 2011, Michael Larabel

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
	const doc_section = 'Result Management';
	const doc_description = 'This option will read a saved test results file and output the system hardware and software information to the terminal. The test results are also outputted.';

	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array('pts_types', 'is_result_file'), null)
		);
	}
	public static function run($r)
	{
		$result_file = new pts_result_file($r[0]);
		$result_output = null;

		$result_output .= $result_file->get_title() . PHP_EOL;
		$result_output .= $result_file->get_description() . PHP_EOL . PHP_EOL . PHP_EOL;

		$system_identifiers = $result_file->get_system_identifiers();
		$system_hardware = $result_file->get_system_hardware();
		$system_software = $result_file->get_system_software();

		for($i = 0; $i < count($system_identifiers); $i++)
		{
			$result_output .= $system_identifiers[$i] . ': ' . PHP_EOL . PHP_EOL;
			$result_output .= "\t" . $system_hardware[$i] . PHP_EOL . PHP_EOL . "\t" . $system_software[$i] . PHP_EOL . PHP_EOL;
		}

		foreach($result_file->get_result_objects() as $result_object)
		{
			$result_output .= trim($result_object->test_profile->get_title() . ' ' . $result_object->test_profile->get_app_version() . PHP_EOL . $result_object->get_arguments_description());

			foreach($result_object->test_result_buffer->get_buffer_items() as $buffer_item)
			{
				$result_output .= PHP_EOL . $buffer_item->get_result_identifier() . ': ' . $buffer_item->get_result_value();
			}

			$result_output .= PHP_EOL;
			for($i = 0; $i < count($test_identifiers); $i++)
			{
				$result_output .= $test_identifiers[$i] . ': ' . $test_values[$i] . PHP_EOL;
			}
			$result_output .= PHP_EOL;
		}

		/*
		$file = 'SAVE_TO';

		if(substr($file, -4) != '.txt')
		{
			$file .= '.txt';
		}
		file_put_contents($file, $result_output);
		*/

		echo $result_output;

	}
}

?>
