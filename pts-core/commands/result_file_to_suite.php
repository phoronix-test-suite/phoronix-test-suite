<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2015, Phoronix Media
	Copyright (C) 2008 - 2015, Michael Larabel

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

class result_file_to_suite implements pts_option_interface
{
	const doc_section = 'Asset Creation';
	const doc_description = 'This option will guide the user through the process of generating their own test suite, which they can then run, that is based upon an existing test results file.';

	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array('pts_types', 'is_result_file'), null)
		);
	}
	public static function run($r)
	{
		$result_file = false;
		if(count($r) != 0)
		{
			$result_file = $r[0];
		}

		$suite_name = pts_user_io::prompt_user_input('Enter name of suite');
		$suite_test_type = pts_user_io::prompt_text_menu('Select test type', pts_types::subsystem_targets());
		$suite_maintainer = pts_user_io::prompt_user_input('Enter suite maintainer name');
		$suite_description = pts_user_io::prompt_user_input('Enter suite description');

		$suite_writer = new pts_test_suite_writer();
		$suite_writer->add_suite_information($suite_name, '1.0.0', $suite_maintainer, $suite_test_type, $suite_description);

		// Read results file
		$result_file = new pts_result_file($result_file);

		foreach($result_file->get_result_objects() as $result_object)
		{
			$suite_writer->add_to_suite_from_result_object($result_object);
		}

		// Finish it off
		$suite_identifier = pts_test_run_manager::clean_save_name($suite_name);
		mkdir(PTS_TEST_SUITE_PATH . 'local/' . $suite_identifier);
		$save_to = PTS_TEST_SUITE_PATH . 'local/' . $suite_identifier . '/suite-definition.xml';

		if($suite_writer->save_xml($save_to) != false)
		{
			echo PHP_EOL . PHP_EOL . 'Saved To: ' . $save_to . PHP_EOL . 'To run this suite, type: phoronix-test-suite benchmark ' . $suite_identifier . PHP_EOL . PHP_EOL;
		}
	}
	public static function invalid_command($passed_args = null)
	{
		pts_tests::recently_saved_results();
	}
}

?>
