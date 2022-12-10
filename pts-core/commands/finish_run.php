<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2020, Phoronix Media
	Copyright (C) 2009 - 2020, Michael Larabel

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

class finish_run implements pts_option_interface
{
	const doc_section = 'Testing';
	const doc_description = 'This option can be used if a test run had not properly finished running all tests within a saved results file. Using this option when specifying a saved results file where all tests had not completed will attempt to finish / resume testing on the remaining tests where there are missing results to be completed.';

	public static function command_aliases()
	{
		return array('resume_run');
	}
	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array('pts_results', 'is_saved_result_file'), null)
		);
	}
	public static function run($args)
	{
		$result_file = new pts_result_file($args[0]);
		$incomplete_identifiers = array();
		foreach($result_file->get_system_identifiers() as $si)
		{
			if($result_file->has_missing_or_incomplete_data($si))
			{
				$incomplete_identifiers[] = $si;
			}
		}
		if(count($incomplete_identifiers) == 0)
		{
			echo PHP_EOL . 'It appears that there are no incomplete test results within this saved file.' . PHP_EOL . PHP_EOL;
			return false;
		}
		$selected = pts_user_io::prompt_text_menu('Select which incomplete test run you would like to finish', $incomplete_identifiers);
		pts_env::set('TEST_RESULTS_IDENTIFIER', $selected);
		$run_manager = new pts_test_run_manager();
		$run_manager->standard_run($args[0]);
		pts_env::remove('TEST_RESULTS_IDENTIFIER');
	}
}
?>
