<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel
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
	if(pts_client::read_env("PTS_IGNORE_MODULES") == false && PTS_MODE == "CLIENT")
	{
		pts_load_modules();
		pts_module_process("__startup");
		define("PTS_STARTUP_TASK_PERFORMED", true);
		register_shutdown_function("pts_module_process", "__shutdown");
	}
}
function pts_auto_detect_modules()
{
	// Auto detect modules to load
	foreach(explode("\n", pts_file_get_contents(STATIC_DIR . "lists/module-variables.list")) as $module_var)
	{
		$module_var = pts_strings::trim_explode("=", $module_var);

		if(count($module_var) == 2)
		{
			list($env_var, $module) = $module_var;

			if(!pts_module_manager::is_module_attached($module) && ($e = pts_client::read_env($env_var)) != false && !empty($e))
			{
				pts_attach_module($module);
			}
		}
	}
}
function pts_module_variable($var)
{
	// For now this is just readung from the real env, but see TODO comment in pts_load_modules
	return trim(getenv($var));
}
function pts_load_modules()
{
	// Load the modules list

	// Check for modules to auto-load from the configuration file
	$load_modules = pts_config::read_user_config(P_OPTION_LOAD_MODULES, null);

	if(!empty($load_modules))
	{
		foreach(explode(",", $load_modules) as $module)
		{
			$module_r = pts_strings::trim_explode("=", $module);

			if(count($module_r) == 2)
			{
				// TODO: end up hooking this into pts_module_variable() rather than using the real env
				pts_client::set_environmental_variable($module_r[0], $module_r[1]);
			}
			else
			{
				pts_attach_module($module);
			}
		}
	}

	// Check for modules to load manually in PTS_MODULES
	if(($load_modules = pts_client::read_env("PTS_MODULES")) !== false)
	{
		foreach(pts_strings::trim_explode(",", $load_modules) as $module)
		{
			if(!pts_module_manager::is_module_attached($module))
			{
				pts_attach_module($module);
			}
		}
	}

	// Detect modules to load automatically
	pts_auto_detect_modules();

	// Clean-up modules list
	pts_module_manager::clean_module_list();

	// Reset counter
	pts_module_manager::set_current_module(null);

	// Load the modules
	$module_store_list = array();
	foreach(pts_module_manager::attached_modules() as $module)
	{
		eval("\$module_store_vars = " . $module . "::\$module_store_vars;");

		if(is_array($module_store_vars))
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
		$var_value = pts_client::read_env($var);

		if(!empty($var_value))
		{
			pts_module_manager::var_store_add($var, $var_value);
		}
	}
}
function pts_attach_module($module)
{
	// Attach a module
	if(!pts_is_module($module))
	{
		return false;
	}

	pts_load_module($module);
	pts_module_manager::attach_module($module);

	if(defined("PTS_STARTUP_TASK_PERFORMED"))
	{
		$pass_by_ref_null = null;
		pts_module_process("__startup", $pass_by_ref_null, $module);
	}
}
function pts_load_module($module)
{
	// Load the actual file needed that contains the module
	if(is_file(PTS_CORE_PATH . "definitions/module-" . $module . ".xml"))
	{
		pts_loader::load_definitions("module-" . $module . ".xml");
	}

	return (is_file(MODULE_DIR . $module . ".php") && include_once(MODULE_DIR . $module . ".php")) || (is_file(MODULE_LOCAL_DIR . $module . ".php") && include_once(MODULE_LOCAL_DIR . $module . ".php"));
}
function pts_module_processes()
{
	return array("__startup", "__pre_option_process", "__pre_install_process", "__pre_test_download", "__interim_test_download", "__post_test_download", "__pre_test_install", "__post_test_install", "__post_install_process", "__pre_run_process", "__pre_test_run", "__interim_test_run", "__post_test_run", "__post_run_process", "__post_option_process", "__shutdown");
}
function pts_module_events()
{
	return array("__event_global_upload", "__event_results_process", "__event_results_saved", "__event_user_error");
}
function pts_module_valid_user_command($module, $command = null)
{
	$valid = false;

	if($command == null && strpos($module, ".") != false)
	{
		list($module, $command) = explode(".", $module);

		if(!pts_module_manager::is_module_attached($module))
		{
			pts_attach_module($module);
		}

		$all_options = pts_module_call($module, "user_commands");

		$valid = count($all_options) > 0 && ((isset($all_options[$command]) && method_exists($module, $all_options[$command])) || in_array($command, array("help")));
	}

	return $valid;
}
function pts_module_run_user_command($module, $command, $arguments = null)
{
	$all_options = pts_module_call($module, "user_commands");
	
	if(isset($all_options[$command]) && method_exists($module, $all_options[$command]))
	{
		pts_module_call($module, $all_options[$command], $arguments);
	}
	else
	{
		// Not a valid command, list available options for the module 
		// or help or list_options was called
		$all_options = pts_module_call($module, "user_commands");

		echo "\nUser commands for the " . $module . " module:\n\n";

		foreach($all_options as $option)
		{
			echo "- " . $module . "." . str_replace('_', '-', $option) . "\n";
		}
		echo "\n";
	}
}
function pts_module_call($module, $process, &$object_pass = null)
{
	if(!class_exists($module))
	{
		return false;
	}

	if(method_exists($module, $process))
	{
		$module_val = call_user_func(array($module, $process), $object_pass);
	}
	else
	{
		eval("\$module_val = " . $module . "::" . $process . ";");
	}

	return $module_val;
}
function pts_module_process($process, &$object_pass = null, $select_modules = false)
{
	// Run a module process on all registered modules
	foreach(pts_module_manager::attached_modules($process, $select_modules) as $module)
	{
		pts_module_manager::set_current_module($module);

		$module_response = pts_module_call($module, $process, $object_pass);

		switch($module_response)
		{
			case PTS_MODULE_UNLOAD:
				// Unload the PTS module
				pts_module_manager::detach_module($module);
				break;
			case PTS_QUIT:
				// Stop the Phoronix Test Suite immediately
				pts_exit();
				break;
		}
	}
	pts_module_manager::set_current_module(null);
}
function pts_module_process_extensions($extensions)
{
	// Process extensions for modules
	if(!empty($extensions))
	{
		foreach(explode(";", $extensions) as $ev)
		{
			list($var, $value) = pts_strings::trim_explode("=", $ev);
			pts_client::set_environmental_variable($var, $value);
			pts_module_maanager::var_store_add($var, $value);
		}

		pts_auto_detect_modules();
	}
}
function pts_is_module($name)
{
	return is_file(MODULE_LOCAL_DIR . $name . ".php") || is_file(MODULE_DIR . $name . ".php");
}
function pts_available_modules()
{
	$modules = array_merge(pts_glob(MODULE_DIR . "*.php"), pts_glob(MODULE_LOCAL_DIR . "*.php"));
	$module_names = array();

	foreach($modules as $module)
	{
		array_push($module_names, basename($module, ".php"));
	}

	asort($module_names);

	return $module_names;
}
function pts_module_config_init($module_name, $set_options = null)
{
	// Validate the config files, update them (or write them) if needed, and other configuration file tasks
	pts_mkdir(MODULE_DATA_DIR . $module_name);
	$settings_to_write = array();

	if(is_file(MODULE_DATA_DIR . $module_name . "/module-settings.xml"))
	{
		$module_config_parser = new tandem_XmlReader(MODULE_DATA_DIR . $module_name . "/module-settings.xml");
		$option_identifier = $module_config_parser->getXMLArrayValues(P_MODULE_OPTION_IDENTIFIER);
		$option_value = $module_config_parser->getXMLArrayValues(P_MODULE_OPTION_VALUE);

		for($i = 0; $i < count($option_identifier); $i++)
		{
			$settings_to_write[$option_identifier[$i]] = $option_value[$i];
		}
	}

	foreach($set_options as $identifier => $value)
	{
		$settings_to_write[$identifier] = $value;
	}

	$config = new tandem_XmlWriter();
	$i = 0;

	foreach($settings_to_write as $identifier => $value)
	{
		$config->addXmlObject(P_MODULE_OPTION_IDENTIFIER, $i, $identifier);
		$config->addXmlObject(P_MODULE_OPTION_VALUE, $i, $value);
		$i++;
	}

	$config->saveXMLFile(MODULE_DATA_DIR . $module_name . "/module-settings.xml");
}

?>
