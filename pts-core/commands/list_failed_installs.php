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

class list_failed_installs implements pts_option_interface
{
	const doc_section = 'Debugging';
	const doc_description = 'This option will list all test profiles that were attempted to be installed on the local system but failed to be installed. Where applicable, the possible error(s) from the test installation are also reported to assist in debugging.';

	public static function run($r)
	{
		$failed_installs = pts_tests::tests_failed_install();
		pts_client::$display->generic_heading(count($failed_installs) . ' Tests Failed To Install');

		if(count($failed_installs) > 0)
		{
			foreach($failed_installs as $test_profile)
			{
				echo pts_client::cli_just_bold(sprintf('%s-36ls - %s-30ls' . PHP_EOL, $test_profile->get_identifier(), $test_profile->get_title()));
				$install_errors = $test_profile->test_installation->get_install_errors();
				if(!empty($install_errors))
				{
					foreach($install_errors as $install_error)
					{
						echo pts_client::cli_colored_text('    ' . $install_error, 'red') . PHP_EOL;
					}
				}
				echo '    Log File: ' . $test_profile->test_installation->get_install_log_location() . PHP_EOL;
			}
		}
	}
}
?>
