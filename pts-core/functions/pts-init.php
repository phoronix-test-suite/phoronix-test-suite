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

// Initalize common / needed PTS start-up work
pts_init();

function pts_directory()
{
	$dir = getenv("PTS_DIR");

	if($dir == ".")
		$dir = "";

	if(!empty($dir))
	{
		if(substr($dir, -1) != "/")
			$dir .= "/";
	}
	
	return $dir;
}
function pts_init()
{
	// Switch time-zone
	date_default_timezone_set("UTC");

	// PTS Defines
	define("PTS_DIR", pts_directory());
	define("PTS_TEMP_DIR", "/tmp/phoronix-test-suite/");
	define("PHP_BIN", getenv("PHP_BIN"));
	define("THIS_RUN_TIME", time());
	define("PTS_START_TIME", THIS_RUN_TIME);

	// Run in debug mode?
	if(getenv("DEBUG") == "1" || ($debug_file = getenv("DEBUG_FILE")) != FALSE)
	{
		define("PTS_DEBUG_MODE", 1);

		if($debug_file != FALSE)
		{
			define("PTS_DEBUG_FILE", $debug_file);
			$GLOBALS["DEBUG_CONTENTS"] = "";
		}
	}

	// Operating System Detection
	$supported_operating_systems = array("Linux", array("Solaris", "Sun"), "FreeBSD", "BSD");
	$uname_s = strtolower(trim(shell_exec("uname -s")));

	foreach($supported_operating_systems as $os_check)
	{
		if(!is_array($os_check))
			$os_check = array($os_check);

		$is_os = false;
		$os_title = $os_check[0];

		for($i = 0; $i < count($os_check) && !$is_os; $i++)
		{
			if(strpos($uname_s, strtolower($os_check[$i])) !== FALSE) // Check for OS
			{
				define("OPERATING_SYSTEM", $os_title);
				define("IS_" . strtoupper($os_title), true);
				$is_os = true;
			}
		}

		if(!$is_os)
			define("IS_" . strtoupper($os_title), false);
	}

	if(!defined("OPERATING_SYSTEM"))
	{
		define("OPERATING_SYSTEM", "Unknown");
		define("IS_UNKNOWN", true);
	}
	else
		define("IS_UNKNOWN", false);
}
function pts_extended_init()
{
	if(!is_dir(PTS_DOWNLOAD_CACHE_DIR))
	{
		mkdir(PTS_DOWNLOAD_CACHE_DIR);
		file_put_contents(PTS_DOWNLOAD_CACHE_DIR . "make-cache-howto", "A download cache is used for conserving time and bandwidth by eliminating the need for the Phoronix Test Suite to download files that have already been downloaded once. A download cache can also be transferred between PCs running the Phoronix Test Suite. For more information on this feature, view the included documentation. To generate a download cache, run:\n\nphoronix-test-suite make-download-cache\n");
	}

	// OpenGL / graphics detection
	$opengl_driver = opengl_version();

	if(strpos($opengl_driver, "NVIDIA") !== FALSE)
		define("IS_NVIDIA_GRAPHICS", true);
	else if(strpos($opengl_driver, "fglrx") !== FALSE)
		define("IS_ATI_GRAPHICS", true);
	else if(strpos($opengl_driver, "Mesa") !== FALSE)
		define("IS_MESA_GRAPHICS", true);

	if(!defined("IS_NVIDIA_GRAPHICS"))
		define("IS_NVIDIA_GRAPHICS", false);
	if(!defined("IS_ATI_GRAPHICS"))
		define("IS_ATI_GRAPHICS", false);
	if(!defined("IS_MESA_GRAPHICS"))
		define("IS_MESA_GRAPHICS", false);

	// Check for batch mode
	if(getenv("PTS_BATCH_MODE") != FALSE)
	{
		if(pts_read_user_config(P_OPTION_BATCH_CONFIGURED, "FALSE") == "FALSE")
			pts_exit(pts_string_header("The batch mode must first be configured\nRun: phoronix-test-suite batch-setup"));

		define("PTS_BATCH_MODE", "1");
		define("IS_BATCH_MODE", true);
	}
	else
		define("IS_BATCH_MODE", false);
}
function __autoload($to_load)
{
	if(is_file(PTS_DIR . "pts-core/objects/" . $to_load . ".php"))
		require_once(PTS_DIR . "pts-core/objects/" . $to_load . ".php");
}

?>
