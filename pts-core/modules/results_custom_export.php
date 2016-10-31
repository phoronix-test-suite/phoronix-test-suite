<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2016, Phoronix Media
	Copyright (C) 2016, Michael Larabel

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

class results_custom_export extends pts_module_interface
{
	const module_name = 'Custom Result Export Methods';
	const module_version = '1.0.0';
	const module_description = 'A simple example module about interfacing with Phoronix Test Suite core for dumping result files in a custom format.';
	const module_author = 'Michael Larabel';

	public static function module_info()
	{
		return null;
	}
	public static function user_commands()
	{
		return array('nf' => 'nf_format');
	}
	public static function nf_format($r)
	{
		if(pts_types::is_result_file($r[0]))
		{
			$result_file = new pts_result_file($r[0]);
			$result_output = $result_file->get_title() . PHP_EOL;
			$system_identifiers = array();
			$system_hardware = array();
			$system_software = array();
			$test_times = array();
			foreach($result_file->get_systems() as $system)
			{
				$system_identifiers[] = $system->get_identifier();
				$system_hardware[] = $system->get_hardware();
				$system_software[] = $system->get_software();
				$test_times[$system->get_identifier()] = date('Y-m-d', strtotime($system->get_timestamp()));
			}

			/*
			// Show the system identifiers?
			for($i = 0; $i < count($system_identifiers); $i++)
			{
				$result_output .= $system_identifiers[$i] . ': ' . PHP_EOL . PHP_EOL;
				$result_output .= "\t" . $system_hardware[$i] . PHP_EOL . PHP_EOL . "\t" . $system_software[$i] . PHP_EOL . PHP_EOL;
			}*/

			$result_lines = array();

			foreach($result_file->get_result_objects() as $result_object)
			{
				foreach($result_object->test_result_buffer->buffer_items as &$buffer)
				{
					// DUMP in DATE/HARDWARE_TYPE/TEST_TYPE/TEST/ARGUMENTS/IDENTIFIER format
					$l = array(
						$test_times[$buffer->get_result_identifier()],
						$result_object->test_profile->get_test_hardware_type(),
						$result_object->test_profile->get_test_software_type(),
						$result_object->test_profile->get_title() . ' ' . $result_object->test_profile->get_app_version(),
						$result_object->get_arguments_description(),
						$buffer->get_result_identifier()
						);
					$result_lines[implode('/', $l)] = $buffer->get_result_value();
				}
			}

			ksort($result_lines);
			$longest_key = max(array_map('strlen', array_keys($result_lines))) + 1;
			foreach($result_lines as $key => $value)
			{
				$result_output .= sprintf('%-' . $longest_key . 'ls = %s', $key, $value) . PHP_EOL;
			}
			echo $result_output . PHP_EOL;
		}
		else
		{
			echo 'Not a recognized result file: ' . $r . PHP_EOL;
		}
	}
}

?>
