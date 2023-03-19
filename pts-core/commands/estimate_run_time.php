<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2017 - 2021, Phoronix Media
	Copyright (C) 2017 - 2021, Michael Larabel

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

class estimate_run_time implements pts_option_interface
{
	const doc_section = 'Testing';
	const doc_description = 'This option will provide estimates for test run-time / length.';

	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array('pts_types', 'identifier_to_object'))
		);
	}
	public static function run($args)
	{
		echo PHP_EOL;

		if($args[0] == 'pts/all' || empty($args))
		{
			$args = pts_openbenchmarking::available_tests(false);
		}

		$tests = array();
		$total_time = 0;
		$test_count = 0;
		$already_added = array();
		foreach($args as $arg)
		{
			foreach(pts_types::identifiers_to_test_profile_objects($arg, false, false) as $t)
			{
				if($t->get_identifier() == null)
				{
					continue;
				}

				if($t->has_test_options() == false && in_array($t->get_identifier(), $already_added))
				{
					continue;
				}

				$tests[] = array($t->get_identifier(), pts_strings::format_time($t->get_estimated_run_time()));
				$total_time += $t->get_estimated_run_time();
				$test_count++;
				$already_added[] = $t->get_identifier();
			}
		}
		if($test_count > 0 && $total_time > 0)
		{
			echo pts_user_io::display_text_table($tests);
			echo PHP_EOL . PHP_EOL . pts_client::cli_just_bold('TOTAL TIME ESTIMATE: ') . pts_strings::format_time($total_time) . PHP_EOL . PHP_EOL;
		}
	}
}

?>
