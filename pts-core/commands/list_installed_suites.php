<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2020, Phoronix Media
	Copyright (C) 2009 - 2020, Michael Larabel

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

class list_installed_suites implements pts_option_interface
{
	const doc_section = 'Information';
	const doc_description = 'This option will list all suites that are currently installed on the system.';

	public static function run($r)
	{
		$installed_suites = array();

		foreach(pts_test_suites::all_suites() as $suite)
		{
			$suite = new pts_test_suite($suite);
			if($suite->needs_updated_install() == false)
			{
				$installed_suites[] = $suite;
			}
		}

		pts_client::$display->generic_heading(count($installed_suites) . ' Suites Installed');

		if(count($installed_suites) > 0)
		{
			foreach($installed_suites as $test_suite)
			{
				echo '- ' . $test_suite->get_title() . PHP_EOL;
			}
			echo PHP_EOL;
		}
	}
}

?>
