<?php

/*
	Phoronix Test Suite "Trondheim"
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

function pts_module_start_process()
{
	$GLOBALS["PTS_MODULES"] = array();
	$GLOBALS["PTS_MODULE_CURRENT"] = FALSE;
	$GLOBALS["PTS_MODULE_VAR_STORE"] = array();

	pts_load_modules();
	pts_module_process("__startup");
	register_shutdown_function("pts_module_process", "__shutdown");
}
function pts_auto_detect_modules($load_here = FALSE)
{
	$modules_assoc = array("MONITOR" => "system_monitor", "FORCE_AA" => "graphics_override", "FORCE_AF" => "graphics_override");

	foreach($modules_assoc as $env_var => $module)
		if(!in_array($module, $GLOBALS["PTS_MODULES"]) && ($e = getenv($env_var)) != FALSE && !empty($e))
		{
			if(defined("PTS_DEBUG_MODE"))
				echo "Attempting To Add Module: " . $module . "\n";

			array_push($GLOBALS["PTS_MODULES"], $module);

			if($load_here)
				pts_load_module($module);
		}
}
function pts_load_modules()
{
	// Check for modules to load manually in PTS_MODULES
	if(($load_modules = getenv("PTS_MODULES")) !== FALSE)
		foreach(explode(",", $load_modules) as $module)
			array_push($GLOBALS["PTS_MODULES"], trim($module));

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

		eval("\$module_store_vars = " . $module . "::\$module_store_vars;");

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
function pts_load_module($module)
{
	@include(MODULE_DIR . $module . ".php");
}
function pts_module_process($process)
{
	foreach($GLOBALS["PTS_MODULES"] as $module)
	{
		$GLOBALS["PTS_MODULE_CURRENT"] = $module;
		eval($module . "::" . $process . "();"); // TODO: This can be cleaned up once PHP 5.3.0+ is out there and adopted
	}
	$GLOBALS["PTS_MODULE_CURRENT"] = FALSE;
}
function pts_module_process_extensions($extensions)
{
	if(!empty($extensions))
	{
		$GLOBALS["MODULE_STORE"] = $extensions;
		$extensions = explode(";", $extensions);

		foreach($extensions as $ev)
		{
			$ev_r = explode("=", $ev);

			if(getenv($ev_r[0]) == FALSE)
				putenv($ev);
		}

		pts_auto_detect_modules(true);
	}
}

?>
