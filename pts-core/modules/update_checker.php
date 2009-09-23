<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
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
	const module_name = "Update Checker";
	const module_version = "0.2.0";
	const module_description = "This module checks to see if the Phoronix Test Suite -- and its tests and suites -- are up to date.";
	const module_author = "Phoronix Media";

	public static function __startup()
	{
		// Once a day check for new version
		if(IS_FIRST_RUN_TODAY)
		{
			// Check For pts-core updates
			$latest_reported_version = trim(pts_http_get_contents("http://www.phoronix-test-suite.com/LATEST"));
			$current_e = explode(".", PTS_VERSION);
			$latest_e = explode(".", $latest_reported_version);

			if($latest_reported_version != PTS_VERSION && $latest_e[0] >= $current_e[0] && ($latest_e[1] > $current_e[1] || ($latest_e[1] == $current_e[1] && $latest_e[2] >= $current_e[2])))
			{
				// New version of PTS is available
				echo pts_string_header("An outdated version of the Phoronix Test Suite is installed.\nThe version in use is v" . PTS_VERSION . ", but the latest is v" . $latest_reported_version . ".\nVisit http://www.phoronix-test-suite.com/ to update this software.");
			}
		}

		return PTS_MODULE_UNLOAD; // This module doesn't have anything else to do
	}
}

?>
