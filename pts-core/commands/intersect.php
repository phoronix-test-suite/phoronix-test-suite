<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2018, Phoronix Media
	Copyright (C) 2008 - 2018, Michael Larabel

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

class intersect implements pts_option_interface
{
	const doc_section = 'Information';
	const doc_description = 'This option will print the test profiles present in all passed result files / test suites. Two or more results/suites must be passed and printed will be all of the common test profiles.';

	public static function argument_checks()
	{
		return array(
		new pts_argument_check('VARIABLE_LENGTH', array('pts_types', 'identifier_to_object'), null)
		);
	}
	public static function run($to_union)
	{
		if(count($to_union) < 2)
		{
			trigger_error('Two or more results/suites must be passed to this sub-command.', E_USER_ERROR);
			return false;
		}
		$tests = array();
		foreach($to_union as $o)
		{
			$tests[] = pts_types::identifiers_to_test_profile_objects($o);
		}

		foreach($tests as &$test_stack)
		{
			foreach($test_stack as &$t)
			{
				$t = $t->get_identifier();
			}
		}

		foreach($tests[0] as $i => $val)
		{
			for($j = 1; $j < count($tests); $j++)
			{
				if(!in_array($val, $tests[$j]))
				{
					unset($tests[0][$i]);
				}
			}

		}

		if(empty($tests[0]))
		{
			echo PHP_EOL . 'No tests were found common to all passed parameters.' . PHP_EOL . PHP_EOL;
		}
		else
		{
			sort($tests[0]);
			$table = array();
			foreach($tests[0] as $test)
			{
				$test = new pts_test_profile($test);
				$table[] = array($test->get_identifier(false), pts_client::cli_just_bold($test->get_title()));
			}
			echo PHP_EOL . pts_client::cli_just_bold(count($tests[0]) . ' Tests Found In ' . implode(' + ', $to_union)) . PHP_EOL;
			echo pts_user_io::display_text_table($table) . PHP_EOL;
		}
	}
}

?>
