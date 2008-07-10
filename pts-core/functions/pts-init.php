<?php

/*
	Phoronix Test Suite "Trondheim"
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
	$uname_o = strtolower(trim(shell_exec("uname -o")));

	if(strpos($uname_o, "linux") !== FALSE)
	{
		define("OPERATING_SYSTEM", "Linux");
		define("IS_LINUX", true);
	}
	else if(strpos($uname_o, "solaris") !== FALSE)
	{
		define("OPERATING_SYSTEM", "Solaris");
		define("IS_SOLARIS", true);
	}
	else
	{
		define("OPERATING_SYSTEM", "Unknown");
		define("IS_UNKNOWN", true);
	}

	// Set the OSes that aren't the OS being used...
	if(!defined("IS_LINUX"))
		define("IS_LINUX", false);
	if(!defined("IS_SOLARIS"))
		define("IS_SOLARIS", false);
	if(!defined("IS_UNKNOWN"))
		define("IS_UNKNOWN", false);
}
function pts_extended_init()
{
	if(!is_dir(PTS_DOWNLOAD_CACHE_DIR))
	{
		mkdir(PTS_DOWNLOAD_CACHE_DIR);
		file_put_contents(PTS_DOWNLOAD_CACHE_DIR . "make-cache-howto", "A download cache is used for conserving time and bandwidth by eliminating the need for the Phoronix Test Suite to download files that have already been downloaded once. A download cache can also be transferred between PCs running the Phoronix Test Suite. For more information on this feature, view the included documentation. To generate a download cache, run:\n\nphoronix-test-suite make-download-cache\n");
	}

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
}
function __autoload($to_load)
{
	if(is_file(PTS_DIR . "pts-core/objects/" . $to_load . ".php"))
		require_once(PTS_DIR . "pts-core/objects/" . $to_load . ".php");
}

?>
