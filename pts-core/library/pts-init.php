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

	if(QUICK_START)
	{
		return true;
	}

	pts_basic_init(); // Initalize common / needed PTS start-up work

	pts_core_storage_init();
	pts_config_init();
	define("TEST_ENV_DIR", pts_find_home(pts_read_user_config(P_OPTION_TEST_ENVIRONMENT, "~/.phoronix-test-suite/installed-tests/")));
	define("SAVE_RESULTS_DIR", pts_find_home(pts_read_user_config(P_OPTION_RESULTS_DIRECTORY, "~/.phoronix-test-suite/test-results/")));
	pts_extended_init();

	return true;
}
function pts_basic_init()
{
	// Initialize The Phoronix Test Suite

	// PTS Defines
	define("PHP_BIN", getenv("PHP_BIN"));
	define("PTS_INIT_TIME", time());

	$dir_init = array(PTS_USER_DIR);
	foreach($dir_init as $dir)
	{
		pts_mkdir($dir);
	}

	phodevi::initial_setup();

	define("IS_PTS_LIVE", phodevi::read_property("system", "username") == "ptslive");
}
function pts_extended_init()
{
	// Extended Initalization Process
	$directory_check = array(TEST_ENV_DIR, SAVE_RESULTS_DIR, XML_SUITE_LOCAL_DIR, 
	TEST_RESOURCE_LOCAL_DIR, XML_PROFILE_LOCAL_DIR, MODULE_LOCAL_DIR, DEFAULT_DOWNLOAD_CACHE_DIR);

	foreach($directory_check as $dir)
	{
		pts_mkdir($dir);
	}

	// Setup PTS Results Viewer
	pts_mkdir(SAVE_RESULTS_DIR . "pts-results-viewer");
	pts_copy(RESULTS_VIEWER_DIR . "pts.js", SAVE_RESULTS_DIR . "pts-results-viewer/pts.js");
	pts_copy(RESULTS_VIEWER_DIR . "pts-viewer.css", SAVE_RESULTS_DIR . "pts-results-viewer/pts-viewer.css");
	pts_copy(RESULTS_VIEWER_DIR . "pts-logo.png", SAVE_RESULTS_DIR . "pts-results-viewer/pts-logo.png");

	// Setup ~/.phoronix-test-suite/xsl/
	pts_mkdir(PTS_USER_DIR . "xsl/");
	pts_copy(STATIC_DIR . "xsl/pts-test-installation-viewer.xsl", PTS_USER_DIR . "xsl/" . "pts-test-installation-viewer.xsl");
	pts_copy(STATIC_DIR . "xsl/pts-user-config-viewer.xsl", PTS_USER_DIR . "xsl/" . "pts-user-config-viewer.xsl");
	pts_copy(STATIC_DIR . "images/pts-308x160.png", PTS_USER_DIR . "xsl/" . "pts-logo.png");
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

	// Phodevi Cache Handling
	$phodevi_cache = $pso->read_object("phodevi_smart_cache");

	if($phodevi_cache instanceOf phodevi_cache && getenv("NO_PHODEVI_CACHE") != 1)
	{
		$phodevi_cache = $phodevi_cache->restore_cache(PTS_USER_DIR, PTS_VERSION);
		phodevi::set_device_cache($phodevi_cache);
	}

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
