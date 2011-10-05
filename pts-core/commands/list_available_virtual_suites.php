<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2011, Phoronix Media
	Copyright (C) 2010 - 2011, Michael Larabel

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

class list_available_virtual_suites implements pts_option_interface
{
	const doc_section = 'Information';
	const doc_description = 'This option will list all available virtual test suites that can be dynamically created based upon the available tests from enabled OpenBenchmarking.org repositories.';

	public static function run($r)
	{
		pts_client::$display->generic_heading('Available Virtual Suites');

		foreach(pts_virtual_test_suite::available_virtual_suites() as $virtual_suite)
		{
			$size = count($virtual_suite->get_contained_test_profiles());

			if($size > 0)
			{
				echo sprintf('%-22ls - %-32ls %-9ls', $virtual_suite->get_identifier(), $virtual_suite->get_title(), $size . ' Tests') . PHP_EOL;
			}
		}
		echo PHP_EOL;
	}
}

?>
