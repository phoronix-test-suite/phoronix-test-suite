<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2020, Phoronix Media
	Copyright (C) 2008 - 2020, Michael Larabel

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
	const doc_section = 'Asset Creation';
	const doc_description = 'This option will guide the user through the process of generating their own test suite, which they can then run. Optionally, passed as arguments can be the test(s) or suite(s) to add to the suite to be created, instead of being prompted through the process.';

	public static function run($r)
	{
		pts_client::$display->generic_heading('Test Suite Creation');

		$suite_name = pts_user_io::prompt_user_input('Enter name of suite');
		$suite_test_type = pts_user_io::prompt_text_menu('Select test type', pts_types::subsystem_targets());
		$suite_maintainer = pts_user_io::prompt_user_input('Enter suite maintainer name');
		$suite_description = pts_user_io::prompt_user_input('Enter suite description');
		$bind_versions = pts_user_io::prompt_bool_input('Bind current test profile versions to test suite');

		$possible_suites = pts_openbenchmarking::available_suites(false);
		$possible_tests = array_merge(pts_tests::local_tests(), pts_openbenchmarking::available_tests(false, false, false, true));

		$new_suite = new pts_test_suite();
		$new_suite->set_title($suite_name);
		$new_suite->set_version('1.0.0');
		$new_suite->set_maintainer($suite_maintainer);
		$new_suite->set_suite_type($suite_test_type);
		$new_suite->set_description($suite_description);

		foreach($r as $test_object)
		{
			$test_object = pts_types::identifier_to_object($test_object);

			if($test_object instanceof pts_test_profile)
			{
				$opts = pts_test_run_options::prompt_user_options($test_object);
				if($opts == false)
				{
					continue;
				}
				list($args, $description) = $opts;

				for($i = 0; $i < count($args); $i++)
				{
					// Not binding the test profile version to this suite, otherwise change false to true
					$new_suite->add_to_suite($test_object, $args[$i], $description[$i]);
				}
			}
			else if($test_object instanceof pts_test_suite)
			{
				$new_suite->add_suite_tests_to_suite($test_object);
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

					$opts = pts_test_run_options::prompt_user_options($test_profile);
					if($opts != false)
					{
						list($args, $description) = $opts;

						for($i = 0; $i < count($args); $i++)
						{
							$new_suite->add_to_suite($test_profile, $args[$i], $description[$i]);
						}
					}
					break;
				case 'Add Sub-Suite':
					$suite_to_add = pts_user_io::prompt_text_menu('Enter test suite', $possible_suites);
					$test_suite = new pts_test_suite($suite_to_add);
					$new_suite->add_suite_tests_to_suite($test_suite);
					break;
			}
			echo PHP_EOL . 'Available Options:' . PHP_EOL;
			$input_option = pts_user_io::prompt_text_menu('Select next operation', array('Add Test', 'Add Sub-Suite', 'Save & Exit'));
		}
		while($input_option != 'Save & Exit');

		if($new_suite->save_xml($suite_name, null, $bind_versions) != false)
		{
			echo PHP_EOL . PHP_EOL . 'Saved -- to run this suite, type: phoronix-test-suite benchmark ' . $new_suite->get_identifier() . PHP_EOL . PHP_EOL;
		}
	}
}

?>
