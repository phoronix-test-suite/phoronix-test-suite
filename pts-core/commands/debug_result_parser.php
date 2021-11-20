<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2017, Phoronix Media
	Copyright (C) 2017, Michael Larabel

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

class debug_result_parser implements pts_option_interface
{
	const doc_section = 'Asset Creation';
	const doc_description = 'This option is intended for use by test profile writers and is used for debugging a result parser. No test execution is done, but there must already be PTS-generated .log files present within the test\'s installation directory.';

	public static function argument_checks()
	{
		return array(
		new pts_argument_check('VARIABLE_LENGTH', array('pts_types', 'identifier_to_object'), null)
		);
	}
	public static function run($r)
	{
		// Make sure you're debugging the latest test script...
		//pts_test_installer::standard_install($r);
		// For debugging, usually running just once is sufficient, unless FORCE_TIMES_TO_RUN is preset
		pts_env::set('FORCE_TIMES_TO_RUN', 1);
		// Run the test(s) in debug mode
		pts_client::set_debug_mode(true);
		$test_run_manager = new pts_test_run_manager();
		$test_run_manager->DEBUG_no_test_execution_just_result_parse = true;
		$test_run_manager->standard_run($r);
	}
}

?>
