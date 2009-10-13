<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
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

function pts_client_init()
{
	pts_define_directories(); // Define directories
	pts_basic_init(); // Initalize common / needed PTS start-up work

	pts_core_storage_init();
	pts_config_init();
	define("TEST_ENV_DIR", pts_find_home(pts_read_user_config(P_OPTION_TEST_ENVIRONMENT, "~/.phoronix-test-suite/installed-tests/")));
	define("SAVE_RESULTS_DIR", pts_find_home(pts_read_user_config(P_OPTION_RESULTS_DIRECTORY, "~/.phoronix-test-suite/test-results/")));
	pts_extended_init();
}
function pts_basic_init()
{
	// Initialize The Phoronix Test Suite

	// PTS Defines
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

		error_reporting(E_ALL | E_NOTICE); // Set error reporting to all and strict
	}
	else
	{
		define("IS_DEBUG_MODE", false);
	}

	$dir_init = array(PTS_USER_DIR);
	foreach($dir_init as $dir)
	{
		pts_mkdir($dir);
	}

	phodevi::initial_setup();
	phodevi::restore_smart_cache(PTS_USER_DIR, PTS_VERSION);

	define("IS_PTS_LIVE", phodevi::read_property("system", "username") == "ptslive");

	if((IS_MACOSX || version_compare(PHP_VERSION, "5.3.0") === 1) && ini_get("date.timezone") == null)
	{
		date_default_timezone_set("UTC");
	}
}
function pts_extended_init()
{
	// Extended Initalization Process

	// Create Other Directories
	foreach(pts_download_cache_user_directories() as $dc_directory)
	{
		if(pts_mkdir($dc_directory))
		{
			@file_put_contents($dc_directory . "make-cache-howto", file_get_contents(STATIC_DIR . "make-download-cache-howto.txt"));
		}
	}

	$directory_check = array(TEST_ENV_DIR, SAVE_RESULTS_DIR, XML_SUITE_LOCAL_DIR, 
	TEST_RESOURCE_LOCAL_DIR, XML_PROFILE_LOCAL_DIR, MODULE_LOCAL_DIR);

	foreach($directory_check as $dir)
	{
		pts_mkdir($dir);
	}

	// Setup PTS Results Viewer
	pts_mkdir(SAVE_RESULTS_DIR . "pts-results-viewer");
	pts_copy(RESULTS_VIEWER_DIR . "pts.js", SAVE_RESULTS_DIR . "pts-results-viewer/pts.js");
	pts_copy(RESULTS_VIEWER_DIR . "pts-viewer.css", SAVE_RESULTS_DIR . "pts-results-viewer/pts-viewer.css");
	pts_copy(RESULTS_VIEWER_DIR . "pts-logo.png", SAVE_RESULTS_DIR . "pts-results-viewer/pts-logo.png");
}
function pts_core_storage_init()
{
	$pso = pts_storage_object::recover_from_file(PTS_CORE_STORAGE);

	if($pso == false)
	{
		$pso = new pts_storage_object(true, true);
	}

	// Last Run Processing
	$last_core_version = $pso->read_object("last_core_version");
	if($last_core_version == false)
	{
		// Compatibility for loading it from PTS 2.0 run and earlier
		$last_core_version = pts_read_user_config("PhoronixTestSuite/TestCore/LastRun/Version", PTS_VERSION);
	}
	// do something here with $last_core_version if you want that information

	$pso->add_object("last_core_version", PTS_VERSION); // PTS version last run

	// Last Run Processing
	$last_run = $pso->read_object("last_run_time");
	if($last_run == false)
	{
		// Compatibility for loading it from PTS 2.0 run and earlier
		$last_run = pts_read_user_config("PhoronixTestSuite/TestCore/LastRun/Time", date("Y-m-d H:i:s"));
	}
	define("IS_FIRST_RUN_TODAY", (substr($last_run, 0, 10) != date("Y-m-d")));

	$pso->add_object("last_run_time", date("Y-m-d H:i:s")); // Time PTS was last run

	// Phoronix Global - GSID
	$global_gsid = $pso->read_object("global_system_id");
	if($global_gsid == false)
	{
		// Compatibility for loading it from PTS 2.0 run and earlier
		$global_gsid = pts_read_user_config("PhoronixTestSuite/GlobalDatabase/GSID", null);
	}

	if(empty($global_gsid) || !pts_global_gsid_valid($global_gsid))
	{
		// Global System ID for anonymous uploads, etc
		$global_gsid = pts_global_request_gsid();
	}

	define("PTS_GSID", $global_gsid);
	$pso->add_object("global_system_id", $global_gsid); // GSID

	// User Agreement Checking
	$agreement_cs = $pso->read_object("user_agreement_cs");
	if($agreement_cs == false)
	{
		// Compatibility for loading it from PTS 2.0 run and earlier
		$agreement_cs = pts_read_user_config("PhoronixTestSuite/TestCore/UserInformation/AgreementCheckSum", null);
	}

	$pso->add_object("user_agreement_cs", $agreement_cs); // User agreement check-sum

	// Archive to disk
	$pso->save_to_file(PTS_CORE_STORAGE);
}
function pts_user_agreement_check($command)
{
	$pso = pts_storage_object::recover_from_file(PTS_CORE_STORAGE);
	$config_md5 = $pso->read_object("user_agreement_cs");
	$current_md5 = md5_file(PTS_PATH . "pts-core/user-agreement.txt");

	if($config_md5 != $current_md5 || pts_read_user_config(P_OPTION_USAGE_REPORTING, "UNKNOWN") == "UNKNOWN")
	{
		$prompt_in_method = pts_check_option_for_function($command, "pts_user_agreement_prompt");
		$user_agreement = file_get_contents(PTS_PATH . "pts-core/user-agreement.txt");

		if($prompt_in_method)
		{
			eval("\$user_agreement_return = " . $command . "::pts_user_agreement_prompt(\$user_agreement);");

			if(is_array($user_agreement_return))
			{
				if(count($user_agreement_return) == 2)
				{
					list($agree, $usage_reporting) = $user_agreement_return;
				}
				else
				{
					$agree = array_shift($user_agreement_return);
					$usage_reporting = -1;
				}
			}
			else
			{
				$agree = $user_agreement_return;
				$usage_reporting = -1;
			}
		}
		else
		{
			echo pts_string_header("Phoronix Test Suite - Welcome");
			echo wordwrap($user_agreement, 65);
			$agree = pts_bool_question("Do you agree to these terms and wish to proceed (Y/n)?", true);
			$usage_reporting = pts_bool_question("Do you wish to enable anonymous usage / statistics reporting (Y/n)?", true);
		}

		if(!is_bool($usage_reporting) && pts_read_user_config(P_OPTION_USAGE_REPORTING, null) == null)
		{
			// Ask user whether to enable anonymous usage reporting, if it wasn't done during the user agreement check
			// Currently it is done during the user agreement check for at least the CLI and GTK2 GUI
			$prompt_in_method = pts_check_option_for_function($command, "pts_usage_reporting_prompt");

			if($prompt_in_method)
			{
				eval("\$usage_reporting = " . $command . "::pts_usage_reporting_prompt();");
			}
		}

		if(is_bool($usage_reporting))
		{
			pts_user_config_init(array(P_OPTION_USAGE_REPORTING => ($usage_reporting ? "TRUE" : "FALSE")));
		}

		if($agree)
		{
			echo "\n";
			$pso->add_object("user_agreement_cs", $current_md5);
			$pso->save_to_file(PTS_CORE_STORAGE);
		}
		else
		{
			pts_exit(pts_string_header("In order to run the Phoronix Test Suite, you must agree to the listed terms."));
		}
	}
}

?>
