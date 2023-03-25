<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2023, Phoronix Media
	Copyright (C) 2023, Michael Larabel

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

class print_tests implements pts_option_interface
{
	const doc_section = 'Information';
	const doc_description = 'This option will print the test identifiers of the specified result file(s), test suite(s), OpenBenchmarking.org ID(s), or other runnable object(s).';

	public static function argument_checks()
	{
		return array(
			new pts_argument_check('VARIABLE_LENGTH', array('pts_types', 'identifier_to_object'), null)
			);
	}
	public static function run($to_print)
	{
		$tests = pts_types::identifiers_to_test_profile_objects($to_print, false, true);
		sort($tests);
		echo implode(PHP_EOL, $tests) . PHP_EOL;
	}
}
