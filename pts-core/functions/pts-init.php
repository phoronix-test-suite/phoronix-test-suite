<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts-init.php: Common start-up initialization functions for the Phoronix Test Suite.

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

function pts_init()
{
	pts_basic_init(); // Initalize common / needed PTS start-up work

	if(IS_SCTP_MODE)
	{
		include_once("pts-core/functions/pts-functions-sctp.php");
	}

	pts_config_init();
	define("TEST_ENV_DIR", pts_find_home(pts_read_user_config(P_OPTION_TEST_ENVIRONMENT, "~/.phoronix-test-suite/installed-tests/")));
	define("SAVE_RESULTS_DIR", pts_find_home(pts_read_user_config(P_OPTION_RESULTS_DIRECTORY, "~/.phoronix-test-suite/test-results/")));
	define("PTS_DOWNLOAD_CACHE_DIR", pts_find_home(pts_download_cache()));
	pts_extended_init();
}
function pts_directory()
{
	// Read PTS directory
	$dir = getenv("PTS_DIR");

	if($dir == ".")
	{
		$dir = "";
	}

	if(!empty($dir))
	{
		if(substr($dir, -1) != "/")
		{
			$dir .= "/";
		}
	}
	
	return $dir;
}
function pts_basic_init()
{
	// Initialize The Phoronix Test Suite

	// Set time-zone
	date_default_timezone_set("UTC");

	// PTS Defines
	define("PTS_TEMP_DIR", "/tmp/phoronix-test-suite/");
	define("PHP_BIN", getenv("PHP_BIN"));
	define("PTS_INIT_TIME", time());

	// Run in debug mode?
	if(($debug_file = getenv("DEBUG_FILE")) != false || getenv("DEBUG") == "1" || getenv("PTS_DEBUG") == "1")
	{
		define("PTS_DEBUG_MODE", 1);
		define("IS_DEBUG_MODE", true);

		if($debug_file != false)
		{
			define("PTS_DEBUG_FILE", $debug_file);
		}

		error_reporting(E_ALL | E_STRICT); // Set error reporting to all and strict
	}
	else
	{
		define("IS_DEBUG_MODE", false);
	}

	// Self-Contained Test Profile
	if(($sctp_file = getenv("SCTP_FILE")) != false && is_file($sctp_file))
	{
		define("SCTP_FILE", $sctp_file);
		define("IS_SCTP_MODE", true);
	}
	else
	{
		define("IS_SCTP_MODE", false);
	}

	// Operating System Detection
	$supported_operating_systems = array("Linux", array("Solaris", "Sun"), "BSD", array("MacOSX", "Darwin"));
	$uname_s = strtolower(php_uname("s"));

	foreach($supported_operating_systems as $os_check)
	{
		if(!is_array($os_check))
		{
			$os_check = array($os_check);
		}

		$is_os = false;
		$os_title = $os_check[0];

		for($i = 0; $i < count($os_check) && !$is_os; $i++)
		{
			if(strpos($uname_s, strtolower($os_check[$i])) !== false) // Check for OS
			{
				define("OPERATING_SYSTEM", $os_title);
				define("IS_" . strtoupper($os_title), true);
				$is_os = true;
			}
		}

		if(!$is_os)
		{
			define("IS_" . strtoupper($os_title), false);
		}
	}

	if(!defined("OPERATING_SYSTEM"))
	{
		define("OPERATING_SYSTEM", "Unknown");
		define("IS_UNKNOWN", true);
	}
	else
	{
		define("IS_UNKNOWN", false);
	}

	define("OS_PREFIX", strtolower(OPERATING_SYSTEM) . "_");
}
function pts_extended_init()
{
	// Extended Initalization Process

	// Create Other Directories
	if(!is_dir(PTS_DOWNLOAD_CACHE_DIR))
	{
		@mkdir(PTS_DOWNLOAD_CACHE_DIR);
		@file_put_contents(PTS_DOWNLOAD_CACHE_DIR . "make-cache-howto", "A download cache is used for conserving time and bandwidth by eliminating the need for the Phoronix Test Suite to download files that have already been downloaded once. A download cache can also be transferred between PCs running the Phoronix Test Suite. For more information on this feature, view the included documentation. To generate a download cache, run:\n\nphoronix-test-suite make-download-cache\n");
	}

	$directory_check = array(TEST_ENV_DIR, SAVE_RESULTS_DIR, XML_SUITE_LOCAL_DIR, TEST_RESOURCE_LOCAL_DIR, XML_PROFILE_LOCAL_DIR);
	foreach($directory_check as $dir)
	{
		if(!is_dir($dir))
		{
			@mkdir($dir);
		}
	}

	// OpenGL / graphics detection
	$graphics_detection = array("NVIDIA", array("ATI", "fglrx"), "Mesa");
	$opengl_driver = sw_os_opengl() . " " . sw_xorg_dri_driver();
	$found_gpu_match = false;

	foreach($graphics_detection as $gpu_check)
	{
		if(!is_array($gpu_check))
		{
			$gpu_check = array($gpu_check);
		}

		$is_this = false;
		$gpu_title = $gpu_check[0];

		for($i = 0; $i < count($gpu_check) && !$is_this; $i++)
		{
			if(stripos($opengl_driver, $gpu_check[$i]) !== false) // Check for GPU
			{
				define("IS_" . strtoupper($gpu_title) . "_GRAPHICS", true);
				$is_this = true;
				$found_gpu_match = true;
			}
		}

		if(!$is_this)
		{
			define("IS_" . strtoupper($gpu_title) . "_GRAPHICS", false);
		}
	}

	define("IS_UNKNOWN_GRAPHICS", ($found_gpu_match == false));

	// Check for batch mode
	if(getenv("PTS_BATCH_MODE") != false)
	{
		if(pts_read_user_config(P_OPTION_BATCH_CONFIGURED, "FALSE") == "FALSE")
		{
			pts_exit(pts_string_header("The batch mode must first be configured\nRun: phoronix-test-suite batch-setup"));
		}

		define("PTS_BATCH_MODE", "1");
		define("IS_BATCH_MODE", true);
	}
	else
	{
		define("IS_BATCH_MODE", false);
	}

	// Self-Contained Test Profile Routines
	if(IS_SCTP_MODE)
	{
		pts_generate_sctp_layer();
	}

	if(substr(pts_read_user_config(P_OPTION_TESTCORE_LASTTIME, date("Y-m-d")), 0, 10) != date("Y-m-d"))
	{
		define("IS_FIRST_RUN_TODAY", true);
	}
	else
	{
		define("IS_FIRST_RUN_TODAY", false);
	}
}
function __autoload($to_load)
{
	// Autoload needed objects
	if(is_file(PTS_DIR . "pts-core/objects/" . $to_load . ".php"))
	{
		include_once(PTS_DIR . "pts-core/objects/" . $to_load . ".php");
	}
}

?>
