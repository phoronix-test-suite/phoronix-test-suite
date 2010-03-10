<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel

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
		return array("install", "execution");
	}
	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, "!pts_empty", null, "The name of a test, suite, or result file must be entered.")
		);
	}
	public static function run($items_to_install)
	{
		$items_to_install = array_unique(array_map("strtolower", $items_to_install));
		echo "\n";
		$display_mode = pts_get_display_mode_object();

		// Create a lock
		$lock_pointer = null;
		$lock_path = sys_get_temp_dir() . "/phoronix-test-suite.active";
		$lock_release = pts_create_lock($lock_path, $lock_pointer);

		// Any external dependencies?
		$satisfied_tests = array(); // Tests with no dependencies or with all dependencies installed
		if(!pts_install_package_on_distribution($display_mode, $items_to_install, $satisfied_tests))
		{
			echo "\nInstallation of needed test dependencies failed.\n\n";
			$user_error = new pts_user_error("Installation of test dependencies for " . implode(", ", array_diff($items_to_install, $satisfied_tests)) . " failed.");
			pts_module_process("__event_user_error", $user_error);

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

		// Install tests
		pts_start_install($items_to_install, $display_mode);

		if($items_to_install = array("prev-test-identifier"))
		{
			$items_to_install = pts_virtual_suite_tests("prev-test-identifier");
		}

		if($lock_release)
		{
			pts_release_lock($lock_pointer, $lock_path);
		}

		pts_set_assignment_next("PREV_TEST_IDENTIFIER", $items_to_install);
	}
}

?>
