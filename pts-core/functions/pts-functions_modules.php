<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
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
define("PTS_QUIT", "PTS_QUIT");

function pts_module_startup_init()
{
	// Process initially called when PTS starts up

	if(getenv("PTS_IGNORE_MODULES") == false)
	{
		// Enable the toggling of the system screensaver by default.
		// To disable w/o code modification, set HALT_SCREENSAVER=NO environmental variable
		pts_attach_module("toggle_screensaver");
		pts_attach_module("update_checker"); // Check for new PTS versions

		pts_load_modules();
		pts_module_process("__startup");
		register_shutdown_function("pts_module_process", "__shutdown");
	}
}
function pts_auto_detect_modules($load_here = false)
{
	// Auto detect modules to load
	$module_variables_file = @file_get_contents(STATIC_DIR . "module-variables.txt");
	$module_variables = explode("\n", $module_variables_file);

	foreach($module_variables as $module_var)
	{
		$module_var = explode("=", $module_var);

		if(count($module_var) == 2)
		{
			$env_var = trim($module_var[0]);
			$module = trim($module_var[1]);

			if(!in_array($module, pts_attached_modules()) && ($e = getenv($env_var)) != false && !empty($e))
			{
				if(IS_DEBUG_MODE)
				{
					echo "Attempting To Add Module: " . $module . "\n";
				}

				pts_attach_module($module);

				if($load_here)
				{
					pts_load_module($module);
				}
			}
		}
	}
}
function pts_load_modules()
{
	// Load the modules list

	// Check for modules to auto-load from the configuration file
	if(strlen(($load_modules = pts_read_user_config(P_OPTION_LOAD_MODULES, ""))) > 0)
	{
		foreach(explode(",", $load_modules) as $module)
		{
			$module_r = explode("=", $module);

			if(count($module_r) == 2)
			{
				pts_set_environment_variable(trim($module_r[0]), trim($module_r[1]));
			}
			else
			{
				pts_attach_module($module);
			}
		}
	}

	// Check for modules to load manually in PTS_MODULES
	if(($load_modules = getenv("PTS_MODULES")) !== false)
	{
		foreach(explode(",", $load_modules) as $module)
		{
			$module = trim($module);

			if(!in_array($module, pts_attached_modules()))
			{
				pts_attach_module($module);
			}
		}
	}

	// Detect modules to load automatically
	pts_auto_detect_modules();

	// Clean-up modules list
	pts_module("CLEAN");

	// Reset counter
	pts_module_activity("CLEAR_CURRENT");

	// Load the modules
	$module_store_list = array();
	foreach(pts_attached_modules() as $module)
	{
		pts_load_module($module);

		$module_type = pts_module_type($module);
		if($module_type == "PHP" || $module_type == "PHP_LOCAL")
		{
			eval("\$module_store_vars = " . $module . "::\$module_store_vars;");
		}
		else
		{
			$module_store_vars = array();
		}

		if(is_array($module_store_vars) && count($module_store_vars) > 0)
		{
			foreach($module_store_vars as $store_var)
			{
				if(!in_array($store_var, $module_store_list))
				{
					array_push($module_store_list, $store_var);
				}
			}
		}
	}

	// Should any of the module options be saved to the results?
	foreach($module_store_list as $var)
	{
		$var_value = getenv($var);

		if($var_value != false && !empty($var_value))
		{
			pts_module_store_var("ADD", $var, $var_value);
		}
	}
}
function pts_attach_module($module)
{
	// Attach a module to be called routinely
	pts_module("ATTACH", trim($module));
}
function pts_load_module($module)
{
	// Load the actual file needed that contains the module
	$module_type = pts_module_type($module);
	return ($module_type == "PHP" && include(MODULE_DIR . $module . ".php")) || 
	($module_type == "PHP_LOCAL" && include(MODULE_LOCAL_DIR . $module . ".php"));
}
function pts_module_processes()
{
	return array("__startup", "__pre_option_process", "__pre_install_process", "__pre_test_install", "__post_test_install", "__post_install_process", 
			"__pre_run_process", "__pre_test_run", "__interim_test_run", "__post_test_run", "__post_run_process", "__post_option_process", "__shutdown");
}
function pts_module_events()
{
	return array("__event_global_upload");
}
function pts_module_call($module, $process, $object_pass = null)
{
	$module_type = pts_module_type($module);

	if($module_type == "PHP" || $module_type == "PHP_LOCAL")
	{
		$module_response = pts_php_module_call($module, $process, $object_pass);
	}
	else if($module_type == "SH")
	{
		$module_response = pts_sh_module_call($module, $process);
	}
	else
	{
		$module_response = "";
	}

	return $module_response;
}
function pts_sh_module_call($module, $process)
{
	$module_file = MODULE_DIR . $module . ".sh";
	$module_return = "";

	if(is_file($module_file))
	{
		$module_return = trim(shell_exec("sh " . $module_file . " " . $process . " 2>&1"));
	}

	return $module_return;
}
function pts_php_module_call($module, $process, $object_pass = null)
{
	if(method_exists($module, $process))
	{
		eval("\$module_val = " . $module . "::" . $process . "(\$object_pass);"); // TODO: This can be cleaned up once PHP 5.3.0+ is out there and adopted
	}
	else
	{
		eval("\$module_val = " . $module . "::" . $process . ";");
	}

	return $module_val;
}
function pts_module_process($process, $object_pass = null)
{
	// Run a module process on all registered modules
	pts_debug_message($process);
	foreach(pts_attached_modules() as $module_index => $module)
	{
		pts_module_activity("SET_CURRENT", $module);

		$MODULE_RESPONSE = pts_module_call($module, $process, $object_pass);

		if(!empty($MODULE_RESPONSE))
		{
			switch($MODULE_RESPONSE)
			{
				case PTS_MODULE_UNLOAD:
					// Unload the PTS module
					pts_module("DETACH", $module_index);
					break;
				case PTS_QUIT:
					// Stop the Phoronix Test Suite immediately
					pts_exit();
					break;
			}
		}
	}
	pts_module_activity("CLEAR_CURRENT");
}
function pts_module_process_extensions($extensions, &$write_to)
{
	// Process extensions for modules
	if(!empty($extensions))
	{
		$write_to = $extensions;
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
	static $cache;

	if(!isset($cache[$name]))
	{
		if(is_file(MODULE_LOCAL_DIR . $name . ".php"))
		{
			$type = "PHP_LOCAL";
		}
		else if(is_file(MODULE_DIR . $name . ".php"))
		{
			$type = "PHP";
		}
		else if(is_file(MODULE_DIR . $name . ".sh"))
		{
			$type = "SH";
		}
		else
		{
			$type = null;
		}

		$cache[$name] = $type;
	}

	return $cache[$name];
}
function pts_attached_modules()
{
	return pts_module("ATTACHED");
}
function pts_module_current()
{
	return pts_module_activity("READ_CURRENT");
}
function pts_module($process, $value = null)
{
	static $module_r;
	$return = false;

	if(empty($module_r))
	{
		$module_r = array();
	}

	switch($process)
	{
		case "ATTACH":
			array_push($module_r, $value);
			break;
		case "DETACH":
			unset($module_r[$value]);
			break;
		case "ATTACHED":
			$return = $module_r;
			break;
		case "CLEAN":
			array_unique($module_r);
			for($i = 0; $i < count($module_r); $i++)
			{
				if(!is_file(MODULE_DIR . $module_r[$i] . ".php"))
				{
					unset($module_r[$i]);
				}
			}
			break;
		case "CLEAR_ALL":
			$assignments = array();
			break;
		case "IS_SET":
			$return = isset($module_r[$i]);
			break;
	}

	return $return;
}
function pts_module_store_var($process, $var = null, $value = null)
{
	static $var_r;
	$return = false;

	if(empty($var_r))
	{
		$var_r = array();
	}

	switch($process)
	{
		case "ADD":
			array_push($var_r, $var . "=" . $value);
			break;
		case "TO_STRING":
			$return = implode(";", $var_r);
			break;
	}

	return $return;
}
function pts_module_activity($process, $value = null)
{
	static $current_module = false;
	$return = false;

	switch($process)
	{
		case "SET_CURRENT":
			$current_module = $value;
			break;
		case "READ_CURRENT":
			$return = $current_module;
			break;
		case "CLEAR_CURRENT":
			$current_module = false;
			break;
	}

	return $return;
}

?>
