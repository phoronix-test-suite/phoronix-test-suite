<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2011 - 2019, Phoronix Media
	Copyright (C) 2011 - 2019, Michael Larabel

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

class openbenchmarking_changes implements pts_option_interface
{
	const doc_section = 'OpenBenchmarking.org';
	const doc_description = 'This option will list recent changes to test profiles of enabled OpenBenchmarking.org repositories.';

	public static function command_aliases()
	{
		return array('recently_updated_tests');
	}
	public static function run($r)
	{
		pts_client::$display->generic_heading('Recently Updated OpenBenchmarking.org Tests');
		$recently_updated = array();

		foreach(pts_openbenchmarking::linked_repositories() as $repo)
		{
			if($repo == 'local')
			{
				// Skip local since it's a fake repository
				continue;
			}

			$repo_index = pts_openbenchmarking::read_repository_index($repo);
			$changes[$repo] = pts_openbenchmarking_client::fetch_repository_changelog($repo);

			if(isset($repo_index['tests']) && is_array($repo_index['tests']))
			{
				foreach(array_keys($repo_index['tests']) as $identifier)
				{
					if($repo_index['tests'][$identifier]['last_updated'] > (time() - (90 * 86400)))
					{
						$recently_updated[$repo . '/' . $identifier] = $repo_index['tests'][$identifier];
					}
				}
			}
		}

		if(count($recently_updated) > 0)
		{
			// sort by date
			uasort($recently_updated, array('openbenchmarking_changes', 'compare_time_stamps'));
			// so that tests are shown from newest to oldest
			$recently_updated = array_reverse($recently_updated);

			$longest_identifier_length = array_keys($recently_updated);
			$longest_identifier_length = strlen(pts_strings::find_longest_string($longest_identifier_length)) + 1;

			foreach($recently_updated as $test_profile => $repo_data)
			{
				echo sprintf('%-' . $longest_identifier_length . 'ls - %-35ls', $test_profile, pts_client::cli_just_bold($repo_data['title'])) . PHP_EOL;
				$br = explode('/', $test_profile);

				if(isset($changes[$br[0]]['tests'][$br[1]]['changes']))
				{
					foreach($changes[$br[0]]['tests'][$br[1]]['changes'] as $test_profile_version => $data)
					{
						echo pts_client::cli_colored_text('v' . $test_profile_version, 'green', true) . ' [' . date('d M Y', $data['last_updated']) . ']' . PHP_EOL;
						echo '  - ' . $data['commit_description'] . PHP_EOL;
					}
				}
				else
				{
					echo 'Last Updated: ' . date('d F Y', $repo_data['last_updated']) . PHP_EOL;
				}
				echo PHP_EOL;
				// $repo_data['test_type']
			}
		}
		else
		{
			echo PHP_EOL . 'No updated tests were found.' . PHP_EOL;
		}
	}
	protected static function compare_time_stamps($a, $b)
	{
		return $a['last_updated'] < $b['last_updated'] ? -1 : 1;
	}
}

?>
