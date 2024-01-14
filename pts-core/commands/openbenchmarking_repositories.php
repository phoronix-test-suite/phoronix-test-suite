<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2011, Phoronix Media
	Copyright (C) 2011, Michael Larabel

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

class openbenchmarking_repositories implements pts_option_interface
{
	const doc_section = 'OpenBenchmarking.org';
	const doc_description = 'This option will list the OpenBenchmarking.org repositories currently linked to this Phoronix Test Suite client instance.';

	public static function run($r)
	{
		echo PHP_EOL . 'Linked OpenBenchmarking.org Repositories:' . PHP_EOL . PHP_EOL;
		$repos = array();
		foreach(pts_openbenchmarking::linked_repositories() as $repo)
		{
			if($repo == 'local')
			{
				// Skip local since it's a fake repository
				continue;
			}

			$repo_index = pts_openbenchmarking::read_repository_index($repo);
			$test_count = count($repo_index['tests']);
			$suite_count = count($repo_index['suites']);
			$generated_time = date('F d H:i', $repo_index['main']['generated']);
			$repos[] = array(pts_client::cli_just_bold($repo), pts_strings::plural_handler($test_count, 'Test'), pts_strings::plural_handler($suite_count, 'Suite'), $generated_time, 'https://openbenchmarking.org/user/' . $repo);
		}
		echo pts_user_io::display_text_table($repos) . PHP_EOL;
	}
}

?>
