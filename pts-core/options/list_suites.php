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

class list_suites implements pts_option_interface
{
	public static function run($r)
	{
		echo pts_string_header("Phoronix Test Suite - Suites");
		$has_partially_supported_suite = false;
		foreach(pts_available_suites_array() as $identifier)
		{
			$suite_info = new pts_test_suite_details($identifier);

			if(!$has_partially_supported_suite && $suite_info->partially_supported())
			{
				$has_partially_supported_suite = true;
			}

			if(!$suite_info->not_supported())
			{
				if(getenv("PTS_DEBUG"))
				{
					echo sprintf("%-26ls - %-32ls %-4ls  %-12ls\n", $suite_info->get_identifier_prefix() . " " . $identifier, $suite_info->get_name(), $suite_info->get_version(), $suite_info->get_suite_type());
				}
				else if($suite_info->get_name() != null)
				{
					echo sprintf("%-24ls - %-32ls [Type: %s]\n", $suite_info->get_identifier_prefix() . " " . $identifier, $suite_info->get_name(), $suite_info->get_suite_type());
				}
			}
		}
		echo "\n";
		if($has_partially_supported_suite)
		{
			echo "* Indicates a partially supported suite.\n\n";
		}
	}
}

?>
