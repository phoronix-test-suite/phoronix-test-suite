<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel

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

class embedded extends pts_module_interface
{
	const module_name = "Quick Commands For Embedded / Mobile Systems";
	const module_version = "1.0.0";
	const module_description = "This module provides some convenient commands that are targeted for those running the Phoronix Test Suite on mobile / embedded devices where the hardware may be limited when it comes to speed and disk capacity.";
	const module_author = "Michael Larabel";

	public static function user_commands()
	{
		return array("benchmark" => "e_benchmark", "test_done" => "e_test_done");
	}
	public static function e_benchmark($args)
	{
		if(!isset($args[0]))
		{
			echo "\nNothing was passed.\n";
			return false;
		}

		$to_run = $args[0];
		$imported_profile = false;

		if(!pts_is_run_object($to_run) && pts_is_file_or_url($to_run))
		{
			// Passed is likely a profile package to import
			pts_run_option_next("import_profile_package", $r);
			$imported_profile = true;
		}

		pts_run_option_next("install_test", "prev-test-identifier", array("PREV_TEST_IDENTIFIER" => $to_run));
		pts_run_option_next("run_test", "prev-test-identifier");
		pts_run_option_next("embedded.test_done", array($imported_profile));
	}
	public static function e_test_done($args)
	{
		$imported_profile = $args[0];
		$test_identifiers = pts_to_array(pts_read_assignment("PREV_TEST_IDENTIFIER"));

		if(empty($test_identifiers))
		{
			return false;
		}

		if($imported_profile)
		{
			foreach($test_identifiers as $test_identifier)
			{
				pts_remove_test_profile($test_identifier);
			}
		}

		foreach($test_identifiers as $test_identifier)
		{
			// Un-install the tests
			pts_remove_installed_test($test_identifier);
		}

	}
}

?>
