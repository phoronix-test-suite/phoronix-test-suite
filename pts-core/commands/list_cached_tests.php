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

class list_cached_tests implements pts_option_interface
{
	const doc_section = 'Information';
	const doc_description = 'This option will list all test profiles where any needed test profiles are already cached or available from the local system under test. This is primarily useful if testing offline/behind-the-firewall and other use-cases where wanting to rely only upon local data.';

	public static function run($r)
	{
		pts_client::$display->generic_heading('Cached Tests');

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
			if($repo_index['tests'][$id]['test_type'] == 'Graphics' && !phodevi::is_display_server_active())
			{
				// Don't display graphics tests that can't run
				continue;
			}

			$show = false;
			foreach($repo_index['tests'][$id]['versions'] as $version)
			{
				if(!pts_openbenchmarking::is_test_profile_downloaded($identifier . '-' . $version))
				{
					// Without Internet, won't be able to download test, so don't show it
					continue;
				}
				$test_profile = new pts_test_profile($identifier . '-' . $version);
				if(pts_test_install_request::test_files_available_via_cache($test_profile) == false)
				{
					// only show tests where files are local or in an available cache
					continue;
				}
				$show = true;
				$identifier .= '-' . $version;
				break;
			}
			if($show == false)
			{
				continue;
			}

			$table[] = array($identifier, pts_client::cli_just_bold($repo_index['tests'][$id]['title']), $repo_index['tests'][$id]['test_type']);
			$test_count++;
		}

		foreach(pts_tests::local_tests() as $identifier)
		{
			$test_profile = new pts_test_profile($identifier);
			if(pts_test_install_request::test_files_available_via_cache($test_profile) == false)
			{
				// only show tests where files are local or in an available cache
				continue;
			}

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
