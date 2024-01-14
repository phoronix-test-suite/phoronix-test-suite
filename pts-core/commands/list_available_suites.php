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

class list_available_suites implements pts_option_interface
{
	const doc_section = 'Information';
	const doc_description = 'This option will list all test suites that are available from the enabled OpenBenchmarking.org repositories.';

	public static function command_aliases()
	{
		return array('list_suites');
	}
	public static function run($r)
	{
		$available_suites = pts_test_suites::all_suites(true, true);
		pts_client::$display->generic_heading('Available Suites');
		$suites = array();

		if(count($available_suites) > 0)
		{
			$has_partially_supported_suite = false;
			foreach($available_suites as $identifier)
			{
				$suite_info = new pts_test_suite($identifier);
				$partially_supported = $suite_info->is_supported() == 1;

				if(!$has_partially_supported_suite && $partially_supported)
				{
					$has_partially_supported_suite = true;
				}

				if($suite_info->is_supported())
				{
					$identifier_prefix = $partially_supported ? '*' : ' ';

					if($suite_info->get_title() != null && !$suite_info->is_deprecated())
					{
						$suites[] = array($identifier_prefix . ' ' . $identifier, pts_client::cli_just_bold($suite_info->get_title()), $suite_info->get_suite_type());
					}
				}
			}
			echo pts_user_io::display_text_table($suites) . PHP_EOL . PHP_EOL;
			if($has_partially_supported_suite)
			{
				echo pts_client::cli_just_italic('* Indicates a partially supported suite.') . PHP_EOL;
			}
		}
		else
		{
			echo PHP_EOL . 'No suites found. Please check that you have Internet connectivity to download test suite data from OpenBenchmarking.org. The Phoronix Test Suite has documentation on configuring the network setup, proxy settings, and PHP network options. Please contact Phoronix Media if you continuing to experience problems.' . PHP_EOL . PHP_EOL;
		}
	}
}

?>
