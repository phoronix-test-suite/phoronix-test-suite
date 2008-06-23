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

function pts_auto_modules_ready(&$modules_list)
{
	$modules_assoc = array("MONITOR" => "system_monitor");

	foreach($modules_assoc as $env_var => $module)
		if(!in_array($module, $modules_list) && ($e = getenv($env_var)) != FALSE && !empty($e))
		{
			if(defined("PTS_DEBUG_MODE"))
				echo "Attempting To Add Module: " . $module . "\n";

			array_push($modules_list, $module);
		}
}
function pts_load_modules(&$modules_list)
{
	// TODO: Detect other modules to load
	// pts_auto_modules_ready($modules_list);

	// Clean-up modules list
	array_unique($modules_list);
	for($i = 0; $i < count($modules_list); $i++)
		if(!is_file(MODULE_DIR . $modules_list[$i] . ".php"))
			unset($modules_list[$i]);

	// Load the modules
	foreach($modules_list as $module)
		include(MODULE_DIR . $module . ".php");
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

?>
