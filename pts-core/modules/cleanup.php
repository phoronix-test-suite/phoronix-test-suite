<?php

/*
	Phoronix Test Suite
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

class cleanup extends pts_module_interface
{
	const module_name = 'System Maintenance / Cleanup';
	const module_version = '1.0.0';
	const module_description = 'This module can be used for system maintenance cleanup tasks around the Phoronix Test Suite. Currently implemented is support for automatically un-installing tests that have not been run in a period of time. When the module is loaded via the REMOVE_TESTS_OLDER_THAN environment variable, it will be automatically invoked at the end of running any benchmarks. Or this module can be manually invoked with the command: phoronix-test-suite cleanup.tests.';

	public static function module_environment_variables()
	{
		return array('REMOVE_TESTS_OLDER_THAN');
	}
	public static function user_commands()
	{
		return array('tests' => 'cleanup_tests');
	}
	public static function cleanup_tests()
	{
		echo PHP_EOL;
		$remove_tests_older_than = pts_env::read('REMOVE_TESTS_OLDER_THAN');
		if(is_numeric($remove_tests_older_than) && $remove_tests_older_than > 0)
		{
			echo pts_client::cli_just_italic('Per REMOVE_TESTS_OLDER_THAN, tests not run in the past ' . $remove_tests_older_than . ' days will be uninstalled.') . PHP_EOL;
		}
		else
		{
			$remove_tests_older_than = 30;
			echo pts_client::cli_just_italic('REMOVE_TESTS_OLDER_THAN is not set or invalid, will use default of ' . $remove_tests_older_than . ' days. Tests not run in that perio will be uninstalled.') . PHP_EOL;
		}

		foreach(pts_tests::tests_installations_with_metadata() as $test_profile)
		{
			if(!$test_profile->test_installation)
			{
				continue;
			}

			$status = $test_profile->test_installation->get_install_status();
			if($status == 'INSTALLED' || $status == 'INSTALL_FAILED')
			{
				$last_run = $test_profile->test_installation->get_last_run_date();
				echo pts_client::cli_just_bold($test_profile->get_identifier() . ' - Last Run: ') . $last_run . PHP_EOL;
				if(empty($last_run) || strtotime($test_profile->test_installation->get_last_run_date()) < (time() - (86400 * $remove_tests_older_than)))
				{
					echo pts_client::cli_colored_text('Removing ' . $test_profile->get_identifier() . '...', 'red', false) . PHP_EOL;
					pts_tests::remove_installed_test($test_profile);
				}
			}
		}
	}
	public static function __post_run_process()
	{
		self::cleanup_tests();
	}
}
?>
