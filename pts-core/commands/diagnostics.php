<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2020, Phoronix Media
	Copyright (C) 2008 - 2020, Michael Larabel

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

class diagnostics implements pts_option_interface
{
	const doc_section = 'Debugging';
	const doc_description = 'This option will print information that is useful to developers when debugging problems with the Phoronix Test Suite and/or test profiles and test suites.';

	public static function run($r)
	{
		foreach(pts_define(-1) as $constant => $constant_value)
		{
			if(strpos($constant, 'GSID') !== false || strpos($constant, '_ID') !== false)
			{
				continue;
			}

			echo pts_client::cli_just_bold($constant) . ' = ' . $constant_value . PHP_EOL;
		}

		echo PHP_EOL . 'Variables That Can Be Used As Result Identifiers / File Names:' . PHP_EOL;
		foreach(pts_test_run_manager::user_run_save_variables() as $var => $var_value)
		{
			echo pts_client::cli_just_bold($var) . ' = ' . $var_value . PHP_EOL;
		}
		echo PHP_EOL . 'Environment Variables (accessible via test scripts):' . PHP_EOL;
		foreach(pts_client::environment_variables() as $var => $var_value)
		{
			echo pts_client::cli_just_bold($var) . ' = ' . $var_value . PHP_EOL;
		}
		echo PHP_EOL;
	}
}

?>
