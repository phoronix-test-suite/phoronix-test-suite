<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel

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

class install_test implements pts_option_interface
{
	public static function required_function_sets()
	{
		return array("execution");
	}
	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, "!empty", null, "The name of a test, suite, or result file must be entered.")
		);
	}
	public static function run($items_to_install)
	{
		// Refresh the pts_client::$display in case we need to run in debug mode
		pts_client::init_display_mode();

		$items_to_install = array_unique(array_map("strtolower", $items_to_install));

		// Create a lock
		$lock_path = pts_client::temporary_directory() . "/phoronix-test-suite.active";
		pts_client::create_lock($lock_path);

		// Any external dependencies?
		$satisfied_tests = array(); // Tests with no dependencies or with all dependencies installed
		$install_passed = pts_external_dependencies::install_dependencies($items_to_install, $satisfied_tests);

		/*
		if($install_passed == false)
		{
			echo "\nInstallation of needed test dependencies failed.\n\n";
			$user_error = new pts_user_error("Installation of test dependencies for " . implode(", ", array_diff($items_to_install, $satisfied_tests)) . " failed.");
			pts_module_manager::module_process("__event_user_error", $user_error);

			if(count($satisfied_tests) > 0)
			{
				echo "Only installing:\n\n" . implode("\n- ", $satisfied_tests) . "\n";
				$items_to_install = $satisfied_tests;
			}
			else
			{
				return false;
			}
		}
		*/

		// Install tests
		if(!is_writable(TEST_ENV_DIR))
		{
			echo "\nERROR: The test installation directory is not writable.\nLocation: " . TEST_ENV_DIR . "\n";
			return false;
		}

		pts_test_installer::start_install($items_to_install);

		if($items_to_install = array("prev-test-identifier"))
		{
			$items_to_install = pts_virtual_suite_tests("prev-test-identifier");
		}

		pts_client::release_lock($lock_path);
		pts_set_assignment_next("PREV_TEST_IDENTIFIER", $items_to_install);
	}
}

?>
