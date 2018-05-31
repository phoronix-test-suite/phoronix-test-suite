<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2018, Phoronix Media
	Copyright (C) 2008 - 2018, Michael Larabel
	update_checker.php: This module checks to see if the Phoronix Test Suite -- and its tests and suites -- are up to date.

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

class update_checker extends pts_module_interface
{
	const module_name = 'Update Checker';
	const module_version = '0.3.0';
	const module_description = 'This module checks to see if the Phoronix Test Suite -- and its tests and suites -- are up to date.';
	const module_author = 'Phoronix Media';

	public static function __pre_option_process()
	{
		// if first run of the day or if the PTS release build is more than 1 year old, start pestering user to upgrade...
		$do_check = IS_FIRST_RUN_TODAY || (time() > strtotime(PTS_RELEASE_DATE) + (86400 * 30 * 12));

		// Once a day check for new version
		if($do_check && pts_network::internet_support_available())
		{
			// Check For pts-core updates
			$latest_reported_version = pts_network::http_get_contents('http://www.phoronix-test-suite.com/LATEST_CORE');
			if(is_numeric($latest_reported_version) && $latest_reported_version > PTS_CORE_VERSION)
			{
				// New version of PTS is available
				pts_client::$display->generic_heading(strtoupper('An outdated version of the Phoronix Test Suite is installed.' . PHP_EOL . 'The version in use is ' . PTS_VERSION . ' (' . PTS_CORE_VERSION . '), but the latest is pts-core ' . $latest_reported_version . '.' . PHP_EOL . 'Visit https://www.phoronix-test-suite.com/ to update this software.'));
			}
		}

		return pts_module::MODULE_UNLOAD; // This module doesn't have anything else to do
	}
}

?>
