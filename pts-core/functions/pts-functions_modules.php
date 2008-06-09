<?php

/*
	Phoronix Test Suite "Trondheim"
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts-functions_modules.php: Functions related to PTS module loading/management.
		Modules are optional add-ons that don't fit the requirements for entrance into pts-core but provide added functionality.
*/

function pts_auto_modules_ready(&$modules_list)
{
	$modules_assoc = array("MONITOR" => "system_monitor");

	foreach($modules_assoc as $env_var => $module)
		if(!in_array($module, $modules_list) && ($e = getenv($env_var)) != FALSE && !empty($e))
			array_push($modules_list, $module);
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
		eval("$module::$process();"); // TODO: This can be cleaned up once PHP 5.3.0+ is out there and adopted
	}
	$GLOBALS["PTS_MODULE_CURRENT"] = FALSE;
}

?>
