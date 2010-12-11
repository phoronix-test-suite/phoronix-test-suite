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

class list_available_tests implements pts_option_interface
{
	const doc_section = 'Information';
	const doc_description = "This option will list all available test profiles that are available from the enabled OpenBenchmarking.org repositories.";

	public static function run($r)
	{
		pts_client::$display->generic_heading("Available Tests");
		foreach(pts_openbenchmarking_client::available_tests() as $identifier)
		{
			$repo = substr($identifier, 0, strpos($identifier, '/'));
			$id = substr($identifier, strlen($repo) + 1);
			$repo_index = pts_openbenchmarking::read_repository_index($repo);

			if(!in_array(OPERATING_SYSTEM, $repo_index['tests'][$id]['supported_platforms']) || empty($repo_index['tests'][$id]['title']))
			{
				// Don't show unsupported tests
				continue;
			}

			echo sprintf("%-28ls - %-35ls %-9ls\n", $identifier, $repo_index['tests'][$id]['title'], $repo_index['tests'][$id]['test_type']);
		}
		echo "\n";
	}
}

?>
