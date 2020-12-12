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

class list_all_tests implements pts_option_interface
{
	const doc_section = 'Information';
	const doc_description = 'This option will list all test profiles that are available from the enabled OpenBenchmarking.org repositories. Unlike the other test listing options, list-all-tests will show deprecated tests, potentially broken tests, or other tests not recommended for all environments. The only check in place is ensuring the test profiles are at least compatible with the operating system in use.';

	public static function run($r)
	{
		pts_client::$display->generic_heading('Available Tests');
		$list_all_tests = pts_client::get_sent_command() == 'list_all_tests';
		$only_show_available_cached_tests = !$list_all_tests && pts_network::internet_support_available() == false;

		if($only_show_available_cached_tests)
		{
			echo 'Internet support is not available/enabled, so the Phoronix Test Suite is only listing test profiles where any necessary test assets are already downloaded to the system or available via a network download cache. To override this behavior, use the ' . pts_client::cli_just_bold('phoronix-test-suite list-all-tests') . ' option.' . PHP_EOL . PHP_EOL;
			pts_client::execute_command('list_cached_tests');
			return true;
		}

		$test_count = 0;
		$table = array();
		foreach(pts_openbenchmarking::available_tests(false) as $identifier)
		{
			$repo = substr($identifier, 0, strpos($identifier, '/'));
			$id = substr($identifier, strlen($repo) + 1);
			$repo_index = pts_openbenchmarking::read_repository_index($repo);
			if((!empty($repo_index['tests'][$id]['supported_platforms']) && !in_array(phodevi::os_under_test(), $repo_index['tests'][$id]['supported_platforms'])) || empty($repo_index['tests'][$id]['title']))
			{
				// Don't show unsupported tests
				continue;
			}
			if($list_all_tests == false && !empty($repo_index['tests'][$id]['status']) && $repo_index['tests'][$id]['status'] != 'Verified')
			{
				// Don't show unsupported tests
				continue;
			}
			$table[] = array($identifier, pts_client::cli_just_bold($repo_index['tests'][$id]['title']), $repo_index['tests'][$id]['test_type']);
			$test_count++;
		}

		foreach(pts_tests::local_tests() as $identifier)
		{
			$test_profile = new pts_test_profile($identifier);

			if($test_profile->get_title() != null && $test_profile->is_supported(false))
			{
				$table[] = array($test_profile->get_identifier(), pts_client::cli_just_bold($test_profile->get_title()), $test_profile->get_test_hardware_type());
				$test_count++;
			}
		}

		if($test_count == 0)
		{
			echo PHP_EOL . 'No tests found. Please check that you have Internet connectivity to download test profile data from OpenBenchmarking.org. The Phoronix Test Suite has documentation on configuring the network setup, proxy settings, and PHP network options. Please contact Phoronix Media if you continuing to experience problems.' . PHP_EOL . PHP_EOL;
		}
		else
		{
			echo pts_user_io::display_text_table($table) . PHP_EOL;
		}
	}
}

?>
