<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2014 - 2018, Phoronix Media
	Copyright (C) 2014 - 2018, Michael Larabel

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

class make_openbenchmarking_cache implements pts_option_interface
{
	const doc_section = 'OpenBenchmarking.org';
	const doc_description = 'This option will attempt to cache the test profile/suite meta-data from OpenBenchmarking.org for all linked repositories. This is useful if you\'re going to be running the Phoronix Test Suite / Phoromatic behind a firewall or without any Internet connection. Those with unrestricted Internet access or not utilizing a large local deployment of the Phoronix Test Suite / Phoromatic shouldn\'t need to run this command.';

	public static function run($r)
	{
		pts_client::$display->generic_heading('Available Tests');
		$available_tests = pts_openbenchmarking::available_tests(false);
		$available_suites = pts_openbenchmarking::available_suites(false);
		$test_count = count($available_tests);
		$suite_count = count($available_suites);
		$total_count = $test_count + $suite_count;
		$total_cache_count = 0;
		$total_cache_size = 0;

		if($test_count == 0 || !pts_network::internet_support_available())
		{
			echo PHP_EOL . 'No tests found. Please check that you have Internet connectivity to download test profile data from OpenBenchmarking.org. The Phoronix Test Suite has documentation on configuring the network setup, proxy settings, and PHP network options. Please contact Phoronix Media if you continuing to experience problems.' . PHP_EOL . PHP_EOL;
			return false;
		}

		echo PHP_EOL . 'CACHE LOCATION: ' . PTS_OPENBENCHMARKING_SCRATCH_PATH . PHP_EOL;

		$terminal_width = pts_client::terminal_width();

		// Cache test profiles
		foreach($available_tests as $i => $identifier)
		{
			$repo = substr($identifier, 0, strpos($identifier, '/'));
			$test = substr($identifier, strlen($repo) + 1);
			$repo_index = pts_openbenchmarking::read_repository_index($repo);

			echo $i . '/' . $total_count . ': ' . ($repo_index['tests'][$test]['title'] != null ? $repo_index['tests'][$test]['title'] . ' [' . $repo_index['tests'][$test]['test_type'] . ']' : null) . PHP_EOL;
			$versions = $repo_index['tests'][$test]['versions'];

			if(isset($r[0]) && $r[0] == 'lean')
			{
				$versions = array(array_shift($versions));
			}

			foreach($versions as $version)
			{
				$qualified_identifier = $repo . '/' . $test . '-' . $version;
				echo $qualified_identifier;
				$success = pts_openbenchmarking::download_test_profile($repo . '/' . $test . '-' . $version, null, true);

				if(is_file(PTS_OPENBENCHMARKING_SCRATCH_PATH . $qualified_identifier . '.zip'))
				{
					$file_size = round(filesize(PTS_OPENBENCHMARKING_SCRATCH_PATH . $qualified_identifier . '.zip') / 1024, 2);
					$info = $file_size . 'KB - ' . sha1_file(PTS_OPENBENCHMARKING_SCRATCH_PATH . $qualified_identifier . '.zip');
					$r_size = $terminal_width - strlen($qualified_identifier) - 3 - strlen($info);
					if($r_size > 0)
					{
						echo ' ' . str_repeat('.', $terminal_width - strlen($qualified_identifier) - 3 - strlen($info)) . ' ' . $info . PHP_EOL;
					}
					$total_cache_count++;
					$total_cache_size += $file_size;
					$x = new pts_test_profile($qualified_identifier);
				}
				else
				{
					echo 'Failed Downloading: ' .  $qualified_identifier . '.zip';
				}
			}
			echo PHP_EOL;
		}

		// Cache test suites
		foreach($available_suites as $i => $identifier)
		{
			$repo = substr($identifier, 0, strpos($identifier, '/'));
			$test = substr($identifier, strlen($repo) + 1);
			$repo_index = pts_openbenchmarking::read_repository_index($repo);

			echo ($i + $test_count) . '/' . $total_count . ': ' . $repo_index['suites'][$test]['title'] . PHP_EOL;
			foreach($repo_index['suites'][$test]['versions'] as $version)
			{
				$qualified_identifier = $repo . '/' . $test . '-' . $version;
				echo $qualified_identifier;
				$success = pts_openbenchmarking::download_test_suite($repo . '/' . $test . '-' . $version, null, true);

				if(is_file(PTS_OPENBENCHMARKING_SCRATCH_PATH . $qualified_identifier . '.zip'))
				{
					$file_size = round(filesize(PTS_OPENBENCHMARKING_SCRATCH_PATH . $qualified_identifier . '.zip') / 1024, 2);
					$info = $file_size . 'KB - ' . sha1_file(PTS_OPENBENCHMARKING_SCRATCH_PATH . $qualified_identifier . '.zip');
					$dot_size = $terminal_width - strlen($qualified_identifier) - 3 - strlen($info);
					echo ' ' . str_repeat('.', ($dot_size >= 0 ? $dot_size : 0)) . ' ' . $info . PHP_EOL;
					$total_cache_count++;
					$total_cache_size += $file_size;
				}
				$x = new pts_test_suite($qualified_identifier);
				if(isset($r[0]) && $r[0] == 'lean')
				{
					break;
				}
			}
			echo PHP_EOL;
		}

		echo PHP_EOL . $total_cache_count . ' Files Cached' . PHP_EOL . $test_count . ' Test Profiles' . PHP_EOL . $suite_count . ' Test Suites' . PHP_EOL . $total_cache_size . 'KB Total Cache Size' . PHP_EOL . PHP_EOL;
	}
}

?>
