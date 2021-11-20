<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2019, Phoronix Media
	Copyright (C) 2019, Michael Larabel

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

class strict_run implements pts_option_interface
{
	const doc_section = 'Testing';
	const doc_description = 'This option is equivalent to the `run` option except it enables various options to run benchmarks an extended number of times for ensuring better statistical accuracy if enforcing strict controls over the data quality, in some cases running the benchmarks for 20+ times.';

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
		pts_env::set('FORCE_TIMES_TO_RUN_MULTIPLE', 2);
		pts_env::set('FORCE_MIN_TIMES_TO_RUN', 20);
		pts_env::set('FORCE_MIN_TIMES_TO_RUN_CUTOFF', 5);
		pts_env::set('FORCE_ABSOLUTE_MIN_TIMES_TO_RUN', 3);
		//pts_test_installer::standard_install($r);
		$run_manager = new pts_test_run_manager();
		$run_manager->standard_run($r);
	}
}

?>
