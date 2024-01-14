<?php

/*
	Phoronix Test Suite
	Copyright (C) 2021, Phoronix Media
	Copyright (C) 2021, Michael Larabel

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

class list_test_errors implements pts_option_interface
{
	const doc_section = 'Debugging';
	const doc_description = 'This sub-command is complementary to list-failed-installs. Rather than listing test installation errors, list-test-errors is used for displaying past test run-time errors. This option will list all test profiles that produced an error previously when running the test profile / benchmark. If a test profile later successfully ran the test with any given option(s) without errors, the error is then removed from the archive. This option is intended to be helpful in debugging test profile issues later on for having a persistent collection of run-time errors.';

	public static function run($r)
	{
		foreach(pts_tests::installed_tests(true) as $test_profile)
		{
			$runtime_errors = $test_profile->test_installation->get_runtime_errors();
			if(!empty($runtime_errors))
			{
				echo pts_client::cli_just_bold($test_profile->get_identifier()) . ': ' . $test_profile->get_title();
				foreach($runtime_errors as $e)
				{
					echo trim((empty($e['description']) ? '' : pts_client::cli_just_italic($e['description']) . ' - ') . $e['date_time']) . PHP_EOL;
					foreach(array_unique($e['errors']) as $error)
					{
						echo pts_client::cli_colored_text('    ' . $error, 'red', true) . PHP_EOL;
					}
				}
				echo pts_client::cli_just_bold('    Install Directory: ') . $test_profile->test_installation->get_install_path() . PHP_EOL;
			}
		}
	}
}
?>
