<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel
	pts-functions_client.php: General functions that are specific to the Phoronix Test Suite client

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

// Phoronix Test Suite - Functions

function pts_run_option_next($command, $pass_args = null, $set_assignments = "")
{
	return pts_command_execution_manager::add_to_queue($command, $pass_args, $set_assignments);
}
function pts_xml_read_single_value($file, $xml_option)
{
 	$xml_parser = new tandem_XmlReader($file);
	return $xml_parser->getXMLValue($xml_option);
}
function pts_proximity_match($search, $match_to)
{
	// Proximity search in $search string for * against $match_to
	$search = explode("*", $search);
	$is_match = true;

	if(count($search) == 1)
	{
		$is_match = false;
	}

	for($i = 0; $i < count($search) && $is_match && !empty($search[$i]); $i++)
	{
		if(($match_point = strpos($match_to, $search[$i])) !== false && ($i > 0 || $match_point == 0))
		{
			$match_to = substr($match_to, ($match_point + strlen($search[$i])));
		}
		else
		{
			$is_match = false;
		}
	}

	return $is_match;
}
function pts_get_display_mode_object()
{
	switch((($env_mode = pts_read_assignment("DISPLAY_MODE")) != false || ($env_mode = pts_client::read_env("PTS_DISPLAY_MODE")) != false ? $env_mode : pts_config::read_user_config(P_OPTION_DISPLAY_MODE, "DEFAULT")))
	{
		case "BASIC":
			$display_mode = new pts_basic_display_mode();
			break;
		case "BATCH":
		case "CONCISE":
			$display_mode = new pts_concise_display_mode();
			break;
		default:
			$display_mode = new pts_concise_display_mode();
			break;
	}

	return $display_mode;
}
function pts_display_mode_holder(&$display_mode = null)
{
	static $current_mode = false;

	if($display_mode == null)
	{
		return $current_mode;
	}
	else
	{
		$current_mode = $display_mode;
	}
}
function pts_find_home($path)
{
	// Find home directory if needed
	if(strpos($path, "~/") !== false)
	{
		$home_path = pts_user_home();
		$path = str_replace("~/", $home_path, $path);
	}

	return pts_add_trailing_slash($path);
}
function pts_user_home()
{
	// Gets the system user's home directory
	if(function_exists("posix_getpwuid") && function_exists("posix_getuid"))
	{
		$userinfo = posix_getpwuid(posix_getuid());
		$userhome = $userinfo["dir"];
	}
	else if(($home = pts_client::read_env("HOME")) || ($home = pts_client::read_env("HOMEPATH")))
	{
		$userhome = $home;
	}
	else
	{
		echo "\nERROR: Can't find home directory!\n";
		$userhome = null;
	}

	return $userhome . "/";
}
function pts_remove_installed_test($identifier)
{
	pts_remove(TEST_ENV_DIR . $identifier, null, true);
}
function pts_current_user()
{
	// Current system user
	return ($pts_user = pts_config::read_user_config(P_OPTION_GLOBAL_USERNAME, "Default User")) != "Default User" ? $pts_user : phodevi::read_property("system", "username");
}

?>
