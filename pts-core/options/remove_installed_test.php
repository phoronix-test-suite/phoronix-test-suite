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

class remove_installed_test implements pts_option_interface
{
	public static function run($r)
	{
		$identifier = $r[0];
		if(is_file(TEST_ENV_DIR . $identifier . "/pts-install.xml"))
		{
			if(pts_bool_question("Are you sure you wish to remove the test " . $identifier . " (y/N)?", false))
			{
				pts_remove(TEST_ENV_DIR . $identifier);
				echo "\nThe " . $identifier . " test has been removed.\n\n";
			}
			else
			{
				echo "\n";
			}
		}
		else
		{
			echo "\n" . $identifier . " is not installed.\n\n";
		}
	}
}

?>
