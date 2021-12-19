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

class result_file_to_suite implements pts_option_interface
{
	const doc_section = 'Result Export';
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

		$suite_name = pts_user_io::prompt_user_input('Enter name of suite to be created');
		$suite_test_type = pts_user_io::prompt_text_menu('Select test type', pts_types::subsystem_targets());
		$suite_maintainer = pts_user_io::prompt_user_input('Enter suite maintainer name');
		$suite_description = pts_user_io::prompt_user_input('Enter suite description');
		$bind_versions = pts_user_io::prompt_bool_input('Bind current test profile versions to test suite');

		$new_suite = new pts_test_suite();
		$new_suite->set_title($suite_name);
		$new_suite->set_version('1.0.0');
		$new_suite->set_maintainer($suite_maintainer);
		$new_suite->set_suite_type($suite_test_type);
		$new_suite->set_description($suite_description);


		$result_file = new pts_result_file($result_file);
		$new_suite->result_file_to_suite($result_file);

		// Finish it off
		if($new_suite->save_xml($suite_name, null, $bind_versions) != false)
		{
			echo PHP_EOL . PHP_EOL . 'Saved -- to run this suite, type: phoronix-test-suite benchmark ' . $new_suite->get_identifier() . PHP_EOL . PHP_EOL;
		}
	}
}

?>
