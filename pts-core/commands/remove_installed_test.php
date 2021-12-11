<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2011, Phoronix Media
	Copyright (C) 2008 - 2011, Michael Larabel

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

class remove_installed_test implements pts_option_interface
{
	const doc_section = 'Test Installation';
	const doc_description = 'This option will permanently remove a installed test by the Phoronix Test Suite.';

	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array('pts_test_profile', 'is_test_profile'), null)
		);
	}
	public static function run($r)
	{
		$test_profile = new pts_test_profile($r[0]);

		if($test_profile->test_installation == false || $test_profile->test_installation->is_installed() == false)
		{
			echo PHP_EOL . 'This test is not installed.' . PHP_EOL;
			return false;
		}

		if(pts_user_io::prompt_bool_input('Are you sure you wish to remove the test ' . $test_profile, false))
		{
			pts_tests::remove_installed_test($test_profile);
			echo PHP_EOL . $test_profile . ' has been removed.' . PHP_EOL;
		}
		else
		{
			echo PHP_EOL;
		}
	}
}

?>
