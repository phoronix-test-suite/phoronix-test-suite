<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2013, Phoronix Media
	Copyright (C) 2008 - 2013, Michael Larabel

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

class build_suite implements pts_option_interface
{
	const doc_section = 'Other';
	const doc_description = 'This option will guide the user through the process of generating their own test suite, which they can then run. Optionally, passed as arguments can be the test(s) or suite(s) to add to the suite to be created, instead of being prompted through the process.';

	public static function run($r)
	{
		pts_client::$display->generic_heading('Test Suite Creation');

		$suite_name = pts_user_io::prompt_user_input('Enter name of suite');
		$suite_test_type = pts_user_io::prompt_text_menu('Select test type', pts_types::subsystem_targets());
		$suite_maintainer = pts_user_io::prompt_user_input('Enter suite maintainer name');
		$suite_description = pts_user_io::prompt_user_input('Enter suite description');

		$possible_suites = pts_openbenchmarking::available_suites();
		$possible_tests = pts_openbenchmarking::available_tests();

		$suite_writer = new pts_test_suite_writer();
		$suite_writer->add_suite_information($suite_name, '1.0.0', $suite_maintainer, $suite_test_type, $suite_description);

		foreach($r as $test_object)
		{
			$test_object = pts_types::identifier_to_object($test_object);

			if($test_object instanceof pts_test_profile)
			{
				list($args, $description) = pts_test_run_options::prompt_user_options($test_object);

				for($i = 0; $i < count($args); $i++)
				{
					// Not binding the test profile version to this suite, otherwise change false to true
					$suite_writer->add_to_suite($test_object->get_identifier(false), $args[$i], $description[$i]);
				}
			}
			else if($test_object instanceof pts_test_suite)
			{
				$suite_writer->add_to_suite($test_object->get_identifier(), null, null);
			}
		}

		$input_option = null;

		do
		{
			switch($input_option)
			{
				case 'Add Test':
					$test_to_add = pts_user_io::prompt_text_menu('Enter test name', $possible_tests);
					$test_profile = new pts_test_profile($test_to_add);

					list($args, $description) = pts_test_run_options::prompt_user_options($test_profile);

					for($i = 0; $i < count($args); $i++)
					{
						$suite_writer->add_to_suite($test_to_add, $args[$i], $description[$i]);
					}
					break;
				case 'Add Sub-Suite':
					$suite_to_add = pts_user_io::prompt_text_menu('Enter test suite', $possible_suites);
					$suite_writer->add_to_suite($suite_to_add, null, null);
					break;
			}
			echo PHP_EOL . 'Available Options:' . PHP_EOL;
			$input_option = pts_user_io::prompt_text_menu('Select next operation', array('Add Test', 'Add Sub-Suite', 'Save & Exit'));
		}
		while($input_option != 'Save & Exit');

		$suite_identifier = pts_test_run_manager::clean_save_name($suite_name);
		$save_to = PTS_TEST_SUITE_PATH . 'local/' . $suite_identifier . '/suite-definition.xml';
		mkdir(dirname($save_to));

		if($suite_writer->save_xml($save_to) != false)
		{
			echo PHP_EOL . PHP_EOL . 'Saved To: ' . $save_to . PHP_EOL . 'To run this suite, type: phoronix-test-suite benchmark ' . $suite_identifier . PHP_EOL . PHP_EOL;
		}
	}
}

?>
