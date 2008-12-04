<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel

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
	public static function run($r)
	{
		pts_load_function_set("install");
		$test = $r[0];

		if(empty($test))
		{
			echo "\nThe test or suite name to install must be supplied.\n";
		}
		else
		{
			$tests = pts_contained_tests(strtolower($test), true);

			if(count($tests) == 0)
			{
				echo "\n" . $test . " isn't recognized.\n";
			}
			else
			{
				foreach($tests as $this_test)
				{
					// Download Test Files
					pts_setup_install_test_directory($this_test, false);
					pts_download_test_files($this_test);
				}
			}
		}
	}
}

?>
