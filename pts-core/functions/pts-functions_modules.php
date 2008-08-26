<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts-functions_modules.php: Functions related to PTS module loading/management.
	Modules are optional add-ons that don't fit the requirements for entrance into pts-core but provide added functionality.

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

// PTS Module Return Types
define("PTS_MODULE_UNLOAD", "PTS_MODULE_UNLOAD");

function pts_module_start_process()
{
	// Process initially called when PTS starts up
	$GLOBALS["PTS_MODULES"] = array();
	$GLOBALS["PTS_MODULE_CURRENT"] = FALSE;
	$GLOBALS["PTS_MODULE_VAR_STORE"] = array();

	if(getenv("PTS_IGNORE_MODULES") !== FALSE)
		return;

	// Enable the toggling of the system screensaver by default.
	// To disable w/o code modification, set HALT_SCREENSAVER=NO environmental variable
	array_push($GLOBALS["PTS_MODULES"], "toggle_screensaver");
	array_push($GLOBALS["PTS_MODULES"], "gpu_error_counter"); // Check for GPU errors

	pts_load_modules();
	pts_module_process("__startup");
	register_shutdown_function("pts_module_process", "__shutdown");
}
function pts_auto_detect_modules($load_here = FALSE)
{
	// Auto detect modules to load
	$module_variables_file = @file_get_contents(MODULE_DIR . "module-variables.txt");
	$module_variables = explode("\n", $module_variables_file);

	foreach($module_variables as $module_var)
	{
		$module_var = explode("=", $module_var);

		if(count($module_var) == 2)
		{
			$env_var = trim($module_var[0]);
			$module = trim($module_var[1]);

			if(!in_array($module, $GLOBALS["PTS_MODULES"]) && ($e = getenv($env_var)) != FALSE && !empty($e))
			{
				if(IS_DEBUG_MODE)
					echo "Attempting To Add Module: " . $module . "\n";

				pts_attach_module($module);

				if($load_here)
					pts_load_module($module);
			}
		}
	}
}
function pts_load_modules()
{
	// Load the modules list

	// Check for modules to auto-load from the configuration file
	if(strlen(($load_modules = pts_read_user_config(P_OPTION_LOAD_MODULES, ""))) > 0)
		foreach(explode(",", $load_modules) as $module)
		{
			$module_r = explode("=", $module);

			if(count($module_r) == 2)
				pts_set_environment_variable(trim($module_r[0]), trim($module_r[1]));
			else
				pts_attach_module($module);
		}

	// Check for modules to load manually in PTS_MODULES
	if(($load_modules = getenv("PTS_MODULES")) !== FALSE)
		foreach(explode(",", $load_modules) as $module)
		{
			$module = trim($module);

			if(!in_array($module, $GLOBALS["PTS_MODULES"]))
				pts_attach_module($module);
		}

	// Detect modules to load automatically
	pts_auto_detect_modules();

	// Clean-up modules list
	array_unique($GLOBALS["PTS_MODULES"]);
	for($i = 0; $i < count($GLOBALS["PTS_MODULES"]); $i++)
		if(!is_file(MODULE_DIR . $GLOBALS["PTS_MODULES"][$i] . ".php"))
			unset($GLOBALS["PTS_MODULES"][$i]);

	// Reset counter
	$GLOBALS["PTS_MODULE_CURRENT"] = FALSE;

	// Load the modules
	$module_store_list = array();
	foreach($GLOBALS["PTS_MODULES"] as $module)
	{
		pts_load_module($module);

		if(pts_module_type($module) == "PHP")
			eval("\$module_store_vars = " . $module . "::\$module_store_vars;");
		else
			$module_store_vars = array();

		if(is_array($module_store_vars) && count($module_store_vars) > 0)
			foreach($module_store_vars as $store_var)
				if(!in_array($store_var, $module_store_list))
					array_push($module_store_list, $store_var);
	}

	// Should any of the module options be saved to the results?
	foreach($module_store_list as $var)
	{
		$var_value = getenv($var);

		if($var_value != FALSE && !empty($var_value))
			array_push($GLOBALS["PTS_MODULE_VAR_STORE"], $var . "=" . $var_value);
	}
}
function pts_attach_module($module)
{
	// Attach a module to be called routinely
	array_push($GLOBALS["PTS_MODULES"], trim($module));
}
function pts_load_module($module)
{
	// Load the actual file needed that contains the module
	if(pts_module_type($module) == "PHP")
		@include(MODULE_DIR . $module . ".php");
}
function pts_module_processes()
{
	return array("__startup", "__pre_install_process", "__pre_test_install", "__post_test_install", "__post_install_process", 
			"__pre_run_process", "__pre_test_run", "__interim_test_run", "__post_test_run", "__post_run_process", "__shutdown");
}
function pts_module_process($process)
{
	// Run a module process on all registered modules
	foreach($GLOBALS["PTS_MODULES"] as $module_index => $module)
	{
		$GLOBALS["PTS_MODULE_CURRENT"] = $module;
		$MODULE_RESPONSE = null;

		if(pts_module_type($module) == "PHP")
			eval("\$MODULE_RESPONSE = " . $module . "::" . $process . "();"); // TODO: This can be cleaned up once PHP 5.3.0+ is out there and adopted
		else
			shell_exec("sh " . MODULE_DIR . $module . ".sh " . $process);

		if(!empty($MODULE_RESPONSE))
		{
			switch($MODULE_RESPONSE)
			{
				case PTS_MODULE_UNLOAD:
					// Unload the PTS module
					unset($GLOBALS["PTS_MODULES"][$module_index]);
					break;
			}
		}
	}
	$GLOBALS["PTS_MODULE_CURRENT"] = FALSE;
}
function pts_module_process_extensions($extensions)
{
	// Process extensions for modules
	if(!empty($extensions))
	{
		$GLOBALS["MODULE_STORE"] = $extensions;
		$extensions = explode(";", $extensions);

		foreach($extensions as $ev)
		{
			$ev_r = explode("=", $ev);
			pts_set_environment_variable($ev_r[1], $ev_r[2]);
		}

		pts_auto_detect_modules(true);
	}
}
function pts_module_type($name)
{
	// Determine the code type of a module

	if(isset($GLOBALS["PTS_VAR_CACHE"]["MODULE_TYPES"][$name]))
	{
		$type = $GLOBALS["PTS_VAR_CACHE"]["MODULE_TYPES"][$name];
	}
	else
	{
		if(is_file(MODULE_DIR . $name . ".php"))
			$type = "PHP";
		else if(is_file(MODULE_DIR . $name . ".sh"))
			$type = "SH";
		else
			$type = null;

		$GLOBALS["PTS_VAR_CACHE"]["MODULE_TYPES"][$name] = $type;
	}

	return $type;
}

?>
