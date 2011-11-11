<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2011, Phoronix Media
	Copyright (C) 2008 - 2011, Michael Larabel

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

class run_test implements pts_option_interface
{
	const doc_section = 'Testing';
	const doc_use_alias = 'run';
	const doc_description = 'This option will run the selected test(s).';

	public static function argument_checks()
	{
		return array(
		new pts_argument_check('VARIABLE_LENGTH', array('pts_types', 'identifier_to_object'), null)
		);
	}
	public static function command_aliases()
	{
		return array('run');
	}
	public static function invalid_command($passed_args)
	{
		// TODO: possibly scan through $passed_args to find similarly named test results if it was mis-spelling or something...
		$showed_recent_results = pts_test_run_manager::recently_saved_test_results();

		if($showed_recent_results == false || true)
		{
			echo 'See available tests to run by visiting OpenBenchmarking.org or running:' . PHP_EOL . PHP_EOL;
			echo '    phoronix-test-suite list-tests' . PHP_EOL . PHP_EOL;
			echo 'Tests can be installed by running:' . PHP_EOL . PHP_EOL;
			echo '    phoronix-test-suite install <test-name>' . PHP_EOL;
		}
	}
	public static function run($to_run)
	{
		pts_test_run_manager::standard_run($to_run);
	}
}

?>
