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

class benchmark implements pts_option_interface
{
	const doc_section = 'Testing';
	const doc_description = 'This option will install the selected test(s) (if needed) and will proceed to run the test(s). This option is equivalent to running phoronix-test-suite with the install option followed by the run option. Multiple arguments can be supplied to run additional tests at the same time and save the results into one file.';

	public static function command_aliases()
	{
		return array('test');
	}
	public static function argument_checks()
	{
		return array(
		new pts_argument_check('VARIABLE_LENGTH', array('pts_types', 'identifier_to_object'), null)
		);
	}
	public static function run($r)
	{
		pts_test_installer::standard_install($r);
		$run_manager = new pts_test_run_manager();
		$run_manager->standard_run($r);
	}
}

?>
