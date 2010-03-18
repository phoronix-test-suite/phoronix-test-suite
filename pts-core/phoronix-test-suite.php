<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel
	phoronix-test-suite.php: The main code for initalizing the Phoronix Test Suite

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

setlocale(LC_NUMERIC, "C");
define("PTS_PATH", dirname(dirname(__FILE__)) . '/');

// PTS_MODE types
// CLIENT = Standard Phoronix Test Suite Client
// LIB = Only load select PTS files
// SILENT = Load all normal pts-core files, but don't run client code
define("PTS_MODE", in_array(($m = getenv("PTS_MODE")), array("CLIENT", "LIB", "SILENT")) ? $m : "CLIENT");

// Any PHP default memory limit should be fine for PTS, until you run image quality comparison tests that begins to consume memory
ini_set("memory_limit", "128M");

if(PTS_MODE == "CLIENT" && ($open_basedir = ini_get("open_basedir")))
{
	$is_in_allowed_dir = false;

	foreach(explode((IS_WINDOWS ? ';' : ':'), $open_basedir) as $allowed_dir)
	{
		if(strpos(PTS_PATH, $allowed_dir) === 0)
		{
			$is_in_allowed_dir = true;
			break;
		}
	}

	if(!$is_in_allowed_dir)
	{
		echo "\nERROR: Your php.ini configuration's open_basedir setting is preventing " . PTS_PATH . " from loading.\n\n";
		return false;
	}
}

require(PTS_PATH . "pts-core/library/pts-functions.php");

if(PTS_MODE != "CLIENT")
{
	// pts-core is acting as a library, return now since no need to run client code
	return;
}

if(ini_get("date.timezone") == null)
{
	date_default_timezone_set("UTC");
}

$sent_command = strtolower(str_replace("-", "_", (isset($argv[1]) ? $argv[1] : null)));
$quick_start_options = array("dump_possible_options", "task_cache_reference_comparison_xml");
define("QUICK_START", in_array($sent_command, $quick_start_options));

pts_client_init(); // Initalize the Phoronix Test Suite (pts-core) client

if(!is_file(PTS_PATH . "pts-core/options/" . $sent_command . ".php"))
{
	$replaced = false;

	if(pts_module_valid_user_command($sent_command))
	{
		$replaced = true;
	}
	else
	{
		$alias_file = pts_file_get_contents(STATIC_DIR . "lists/option-command-aliases.list");

		foreach(pts_trim_explode("\n", $alias_file) as $alias_line)
		{
			list($link_cmd, $real_cmd) = pts_trim_explode('=', $alias_line);

			if($link_cmd == $sent_command)
			{
				$sent_command = $real_cmd;
				$replaced = true;
				break;
			}
		}
	}

	if($replaced == false)
	{
		// Show general options, since there are no valid commands
		echo file_get_contents(STATIC_DIR . "general-options.txt");
		exit;
	}
}

define("PTS_USER_LOCK", PTS_USER_DIR . "run_lock");
$pts_fp = null;
$release_lock = true;

if(!QUICK_START)
{
	if(!pts_create_lock(PTS_USER_LOCK, $pts_fp))
	{
		echo pts_string_header("NOTICE: It appears that the Phoronix Test Suite is already running.\nFor proper results, only run one instance at a time.");
		$release_lock = false;
	}

	register_shutdown_function("pts_shutdown");

	if(($proxy_address = pts_config::read_user_config(P_OPTION_NET_PROXY_ADDRESS, false)) && ($proxy_port = pts_config::read_user_config(P_OPTION_NET_PROXY_PORT, false)))
	{
		define("NETWORK_PROXY", $proxy_address . ":" . $proxy_port);
		define("NETWORK_PROXY_ADDRESS", $proxy_address);
		define("NETWORK_PROXY_PORT", $proxy_port);
	}
	else if(($env_proxy = getenv("http_proxy")) != false && count($env_proxy = explode(':', $env_proxy)) == 2)
	{
		define("NETWORK_PROXY", $env_proxy[0] . ":" . $env_proxy[1]);
		define("NETWORK_PROXY_ADDRESS", $env_proxy[0]);
		define("NETWORK_PROXY_PORT", $env_proxy[1]);
	}

	define("NETWORK_TIMEOUT", pts_config::read_user_config(P_OPTION_NET_TIMEOUT, 20));

	if(ini_get("allow_url_fopen") == "Off")
	{
		echo "\nThe allow_url_fopen option in your PHP configuration must be enabled for network support.\n\n";
		define("NO_NETWORK_COMMUNICATION", true);
	}
	else if(pts_string_bool(pts_config::read_user_config(P_OPTION_NET_NO_NETWORK, "FALSE")))
	{
		define("NO_NETWORK_COMMUNICATION", true);
		echo "\nNetwork Communication Is Disabled For Your User Configuration.\n\n";
	}
	/* else
	{
		$server_response = pts_http_get_contents("http://www.phoronix-test-suite.com/PTS", false, false);

		if($server_response != "PTS")
		{
			define("NO_NETWORK_COMMUNICATION", true);
			echo "\nNetwork Communication Failed.\n\n";
		}
	}*/

	if(!defined("NO_NETWORK_COMMUNICATION") && ini_get("file_uploads") == "Off")
	{
		echo "\nThe file_uploads option in your PHP configuration must be enabled for network support.\n\n";
	}

	pts_module_startup_init(); // Initialize the PTS module system
}

// Read passed arguments
$pass_args = array();
for($i = 2; $i < $argc && isset($argv[$i]); $i++)
{
	array_push($pass_args, $argv[$i]);
}

if(!QUICK_START)
{
	pts_user_agreement_check($sent_command);
}

pts_run_option_next($sent_command, $pass_args);

while(($current_option = pts_command_run_manager::pull_next_run_command()) != null)
{
	pts_run_command($current_option->get_command(), $current_option->get_arguments(), $current_option->get_preset_assignments()); // Run command
}

if(!QUICK_START && $release_lock)
{
	pts_release_lock($pts_fp, PTS_USER_LOCK);
}

?>
