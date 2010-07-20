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

class download_test_files implements pts_option_interface
{
	public static function required_function_sets()
	{
		return array("install");
	}
	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, "!empty", null, "The name of a test, suite, or result file must be entered.")
		);
	}
	public static function run($r)
	{
		$tests = pts_contained_tests($r, true);

		if(count($tests) == 0)
		{
			echo "\n" . $r[0] . " is not recognized.\n";
		}
		else
		{
			foreach($tests as $this_test)
			{
				// Download Test Files
				echo "\n" . $this_test . ":\n";
				pts_setup_install_test_directory($this_test, false);
				pts_download_test_files($this_test);
			}
		}
	}
}

?>
