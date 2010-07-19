<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel
	pts-functions.php: Include functions required for Phoronix Test Suite operation.

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

define("PTS_LIBRARY_PATH", PTS_PATH . "pts-core/library/");

// Temporarily throw these defines here
define("TYPE_CHAR_LETTER", (1 << 1));
define("TYPE_CHAR_NUMERIC", (1 << 2));
define("TYPE_CHAR_DECIMAL", (1 << 3));
define("TYPE_CHAR_SPACE", (1 << 4));
define("TYPE_CHAR_DASH", (1 << 5));
define("TYPE_CHAR_UNDERSCORE", (1 << 6));
define("TYPE_CHAR_COLON", (1 << 7));
define("TYPE_CHAR_COMMA", (1 << 8));

require(PTS_LIBRARY_PATH . "pts.php");

function pts_define_directories()
{
	// User's home directory for storing results, module files, test installations, etc.
	define("PTS_CORE_PATH", PTS_PATH . "pts-core/");

	if(PTS_MODE == "CLIENT")
	{
		define("PTS_USER_DIR", pts_client::user_home_directory() . ".phoronix-test-suite/");
		define("PTS_CORE_STORAGE", PTS_USER_DIR . "core.pt2so");
	}

	// Misc Locations
	define("MODULE_DIR", PTS_CORE_PATH . "modules/");
	define("MODULE_LOCAL_DIR", PTS_USER_DIR . "modules/");
	define("MODULE_DATA_DIR", PTS_USER_DIR . "modules-data/");
	define("DEFAULT_DOWNLOAD_CACHE_DIR", PTS_USER_DIR . "download-cache/");
	define("TEST_LIBRARIES_DIR", PTS_CORE_PATH . "test-libraries/");
	define("STATIC_DIR", PTS_CORE_PATH . "static/");
	define("COMMAND_OPTIONS_DIR", PTS_CORE_PATH . "options/");
	define("RESULTS_VIEWER_DIR", STATIC_DIR . "results-viewer/");

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

if(PTS_MODE == "CLIENT" || defined("PTS_AUTO_LOAD_OBJECTS"))
{
	function __autoload($to_load)
	{
		static $sub_objects = null;

		if($sub_objects == null)
		{
			$sub_objects = array();

			foreach(array_merge(glob(PTS_PATH . "pts-core/objects/*/*.php"), glob(PTS_PATH . "pts-core/objects/*/*/*.php")) as $file)
			{
				$object_name = basename($file, ".php");
				$sub_objects[$object_name] = $file;
			}
		}

		if(is_file(PTS_PATH . "pts-core/objects/" . $to_load . ".php"))
		{
			include(PTS_PATH . "pts-core/objects/" . $to_load . ".php");
		}
		else if(isset($sub_objects[$to_load]))
		{
			include($sub_objects[$to_load]);
			unset($sub_objects[$to_load]);
		}
	}
}
if(PTS_MODE == "LIB")
{
	// If a program using PTS as a library wants any of the below functions, they will need to load it manually
	return;
}

// Client Work
require(PTS_LIBRARY_PATH . "pts-functions_basic.php");
require(PTS_LIBRARY_PATH . "pts-functions_client.php");

// Load Main Functions
require(PTS_LIBRARY_PATH . "pts-functions_io.php");
require(PTS_LIBRARY_PATH . "pts-functions_global.php");
require(PTS_LIBRARY_PATH . "pts-functions_tests.php");
require(PTS_LIBRARY_PATH . "pts-functions_types.php");
require(PTS_LIBRARY_PATH . "pts-functions_vars.php");
require(PTS_LIBRARY_PATH . "pts-functions_modules.php");
require(PTS_LIBRARY_PATH . "pts-functions_assignments.php");


?>
