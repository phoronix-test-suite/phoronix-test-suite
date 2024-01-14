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

class list_test_status implements pts_option_interface
{
	const doc_section = 'Information';
	const doc_description = 'This sub-command provides a verbose look at all tests installed/uninstalled on the system and whether any errors were encountered at install-time or run-time and other test installation/runtime metrics for complementing other Phoronix Test Suite sub-command outputs.';

	public static function run($r)
	{
		foreach(pts_tests::tests_installations_with_metadata() as $test_profile)
		{
			$status = $test_profile->test_installation->get_install_status();
			if($status == 'INSTALLED')
			{
				$status = pts_client::cli_colored_text($status . ' @ ' . $test_profile->test_installation->get_install_date(), 'green', false);
			}
			else if($status == 'INSTALL_FAILED')
			{
				$status = pts_client::cli_colored_text('INSTALL FAILED', 'red', true);
			}
			echo pts_client::cli_just_bold($test_profile->get_identifier() . str_repeat(' ' , 32 - strlen($test_profile->get_identifier())) . $status) . '  ' .  pts_strings::plural_handler($test_profile->test_installation->get_run_count(), 'Time') . ' Run' . PHP_EOL;
			$runtime_errors = $test_profile->test_installation->get_runtime_errors();
			$install_errors = $test_profile->test_installation->get_install_errors();
			if(!empty($runtime_errors))
			{
				foreach($runtime_errors as $e)
				{
					echo '    ' . trim((empty($e['description']) ? '' : pts_client::cli_just_italic($e['description']) . ' - ') . 'Last Attempted: ' . $e['date_time']) . PHP_EOL;
					foreach(array_unique($e['errors']) as $error)
					{
						echo pts_client::cli_colored_text('    ' . $error, 'red', true) . PHP_EOL;
					}
				}
			}
			if(!empty($install_errors))
			{
				foreach($install_errors as $install_error)
				{
					echo pts_client::cli_colored_text('    ' . $install_error, 'red', true) . PHP_EOL;
				}
			}
		}
	}
}
?>
