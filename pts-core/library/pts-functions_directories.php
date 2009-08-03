<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
	pts-functions_directories.php: Functions needed for directories in the Phoronix Test Suite

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

function pts_define_directories()
{
	// User's home directory for storing results, module files, test installations, etc.
	define("PTS_CORE_PATH", PTS_PATH . "pts-core/");

	if(function_exists("pts_user_home"))
	{
		define("PTS_USER_DIR", pts_user_home() . ".phoronix-test-suite/");
		define("PTS_CORE_STORAGE", PTS_USER_DIR . "core.pt2so");
	}

	// Distribution External Dependency Locations
	define("XML_DISTRO_DIR", PTS_PATH . "pts/distro-xml/");
	define("SCRIPT_DISTRO_DIR", PTS_PATH . "pts/distro-scripts/");

	// Misc Locations
	define("ETC_DIR", PTS_PATH . "pts/etc/");
	define("MODULE_DIR", PTS_CORE_PATH . "modules/");
	define("MODULE_LOCAL_DIR", PTS_USER_DIR . "modules/");
	define("RESULTS_VIEWER_DIR", PTS_CORE_PATH . "results-viewer/");
	define("TEST_LIBRARIES_DIR", PTS_CORE_PATH . "test-libraries/");
	define("STATIC_DIR", PTS_CORE_PATH . "static/");
	define("OPTIONS_DIR", PTS_CORE_PATH . "options/");

	// Test & Suite Locations
	define("XML_PROFILE_DIR", PTS_PATH . "pts/test-profiles/");
	define("XML_PROFILE_CTP_BASE_DIR", PTS_PATH . "pts/base-test-profiles/");
	define("XML_SUITE_DIR", PTS_PATH . "pts/test-suites/");
	define("TEST_RESOURCE_DIR", PTS_PATH . "pts/test-resources/");
	define("TEST_RESOURCE_CTP_BASE_DIR", PTS_PATH . "pts/base-test-resources/");
	define("XML_PROFILE_LOCAL_DIR", PTS_USER_DIR . "test-profiles/");
	define("XML_SUITE_LOCAL_DIR", PTS_USER_DIR . "test-suites/");
	define("TEST_RESOURCE_LOCAL_DIR", PTS_USER_DIR . "test-resources/");
}

?>
