<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
	pts-functions_vars.php: Functions related to variables exposed to tests and/or end-users

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

function pts_env_variables()
{
	// The PTS environmental variables passed during the testing process, etc
	static $env_variables = null;

	if(empty($env_variables))
	{
		$env_variables = array(
		"PTS_VERSION" => PTS_VERSION,
		"PTS_CODENAME" => PTS_CODENAME,
		"PTS_DIR" => PTS_PATH,
		"FONT_DIR" => FONT_DIR,
		"PHP_BIN" => PHP_BIN,
		"NUM_CPU_CORES" => hw_cpu_core_count(),
		"NUM_CPU_JOBS" => hw_cpu_job_count(),
		"SYS_MEMORY" => hw_sys_memory_capacity(),
		"VIDEO_MEMORY" => hw_gpu_memory_size(),
		"VIDEO_WIDTH" => hw_gpu_screen_width(),
		"VIDEO_HEIGHT" => hw_gpu_screen_height(),
		"VIDEO_MONITOR_COUNT" => hw_gpu_monitor_count(),
		"VIDEO_MONITOR_LAYOUT" => hw_gpu_monitor_layout(),
		"VIDEO_MONITOR_SIZES" => hw_gpu_monitor_modes(),
		"OPERATING_SYSTEM" => pts_vendor_identifier(),
		"OS_VERSION" => sw_os_version(),
		"OS_ARCH" => sw_os_architecture(),
		"OS_TYPE" => OPERATING_SYSTEM,
		"THIS_RUN_TIME" => PTS_INIT_TIME
		);
	}

	return $env_variables;
}
function pts_user_runtime_variables($search_for = null)
{
	static $runtime_variables = null;

	if(empty($runtime_variables))
	{
		$runtime_variables = array(
		"VIDEO_RESOLUTION" => hw_gpu_current_mode(),
		"VIDEO_CARD" => hw_gpu_string(),
		"VIDEO_DRIVER" => sw_os_opengl(),
		"OPERATING_SYSTEM" => sw_os_release(),
		"PROCESSOR" => hw_cpu_string(),
		"MOTHERBOARD" => hw_sys_motherboard_string(),
		"CHIPSET" => hw_sys_chipset_string(),
		"KERNEL_VERSION" => sw_os_kernel(),
		"COMPILER" => sw_os_compiler(),
		"HOSTNAME" => sw_os_hostname()
		);
	}

	if($search_for != null)
	{
		foreach($runtime_variables as $key => $value)
		{
			if($key == $search_for)
			{
				return $value;
			}
		}
	}

	return $runtime_variables;
}
function pts_variables_export_string($vars = null)
{
	// Convert pts_env_variables() into shell export variable syntax
	$return_string = "";

	if($vars == null)
	{
		$vars = pts_env_variables();
	}
	else
	{
		$vars = array_merge(pts_env_variables(), $vars);
	}

	foreach($vars as $name => $var)
	{
		$return_string .= "export " . $name . "=" . $var . ";";
	}
	return $return_string . " ";
}
function pts_run_additional_vars($identifier)
{
	$extra_vars = array();

	$extra_vars["HOME"] = TEST_ENV_DIR . $identifier . "/";

	$ctp_extension_string = "";
	$extends = pts_test_extends_below($identifier);
	foreach($extends as $extended_test)
	{
		if(is_dir(TEST_ENV_DIR . $extended_test . "/"))
		{
			$ctp_extension_string .= TEST_ENV_DIR . $extended_test . ":";
		}
	}

	if(!empty($ctp_extension_string))
	{
		$extra_vars["PATH"] = $ctp_extension_string . "\$PATH";
	}

	if(count($extends) > 0)
	{
		$extra_vars["TEST_EXTENDS"] = TEST_ENV_DIR . $extends[0];
	}

	return $extra_vars;
}
function pts_swap_variables($user_str, $replace_function)
{
	if(!function_exists($replace_function))
	{
		return $user_str;
	}

	$offset = 0;
	while($offset < strlen($user_str) && ($s = strpos($user_str, "$", $offset)) !== false)
	{
		$s++;
		$var_name = substr($user_str, $s, (($e = strpos($user_str, " ", $s)) == false ? strlen($user_str) : $e) - $s);

		$var_replacement = call_user_func($replace_function, $var_name);
		$user_str = str_replace("$" . $var_name, $var_replacement, $user_str);

		$offset = $s + strlen($var_replacement);
	}

	return $user_str;
}

?>
