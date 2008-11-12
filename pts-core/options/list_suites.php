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

class list_suites
{
	public static function run()
	{
		echo pts_string_header("Phoronix Test Suite - Suites");
		$has_partially_supported_suite = false;
		foreach(pts_available_suites_array() as $identifier)
		{
			$suite_info = new pts_test_suite_details($identifier);

			if($has_partially_supported_suite == false && $suite_info->partially_supported())
			{
				$has_partially_supported_suite = true;
			}

			echo $suite_info;
		}
		echo "\n";
		if($has_partially_supported_suite)
		{
			echo "* Indicates a partially supported suite.\n\n";
		}
	}
}

?>
