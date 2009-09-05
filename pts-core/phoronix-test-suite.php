<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
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
define("PTS_PATH", dirname(dirname(__FILE__)) . "/");

// PTS_MODE types
// CLIENT = Standard Phoronix Test Suite Client
// LIB = Only load select PTS files
// SILENT = Load all normal pts-core files, but don't run client code
define("PTS_MODE", in_array(($m = getenv("PTS_MODE")), array("CLIENT", "LIB", "SILENT")) ? $m : "CLIENT");

require(PTS_PATH . "pts-core/library/pts-functions.php");

if(PTS_MODE != "CLIENT")
{
	// pts-core is acting as a library, return now since no need to run client code
	return;
}

pts_client_init(); // Initalize the Phoronix Test Suite (pts-core) client

$sent_command = strtolower(str_replace("-", "_", (isset($argv[1]) ? $argv[1] : null)));

if(!is_file(PTS_PATH . "pts-core/options/" . $sent_command . ".php"))
{
	$replaced = false;

	if(pts_module_valid_user_command($sent_command))
	{
		$replaced = true;
	}
	else
	{
		$alias_file = trim(file_get_contents(STATIC_DIR . "option-command-aliases.txt"));
		$alias_r = array_map("trim", explode("\n", $alias_file));

		for($i = 0; $i < count($alias_r) && !$replaced; $i++)
		{
			$line_r = array_map("trim", explode("=", $alias_r[$i]));

			if($line_r[0] == $sent_command && isset($line_r[1]))
			{
				$sent_command = trim($line_r[1]);
				$replaced = true;
			}
		}
	}

	if(!$replaced)
	{
		// Show general options, since there are no valid commands
		echo file_get_contents(STATIC_DIR . "general-options.txt");
		exit;
	}
}

define("PTS_USER_LOCK", PTS_USER_DIR . "run_lock");
$pts_fp = null;
if(!pts_create_lock(PTS_USER_LOCK, $pts_fp))
{
	echo pts_string_header("WARNING: It appears that the Phoronix Test Suite is already running.\nFor proper results, only run one instance at a time.");
}

register_shutdown_function("pts_shutdown");
pts_module_startup_init(); // Initialize the PTS module system

// Read passed arguments
$pass_args = array();
for($i = 2; $i < $argc; $i++)
{
	if(isset($argv[$i]))
	{
		array_push($pass_args, $argv[$i]);
	}
}

pts_user_agreement_check($sent_command);
pts_run_option_next($sent_command, $pass_args);

while(($current_option = pts_run_option_next(false)) != false)
{
	pts_run_option_command($current_option->get_command(), $current_option->get_arguments(), $current_option->get_preset_assignments()); // Run command
}

pts_release_lock($pts_fp, PTS_USER_LOCK);

?>
