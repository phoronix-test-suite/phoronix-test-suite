<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel
	pts-functions_shell.php: Functions for shell (and similar) commands that are abstracted

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

function pts_exec($exec, $extra_vars = null)
{
	// Same as shell_exec() but with the PTS env variables added in
	return shell_exec(pts_variables_export_string($extra_vars) . $exec);
}
function pts_remove($object, $ignore_files = null, $remove_root_directory = false)
{
	if(is_dir($object))
	{
		$object = pts_add_trailing_slash($object);
	}

	foreach(pts_glob($object . "*") as $to_remove)
	{
		if(is_file($to_remove))
		{
			if(is_array($ignore_files) && in_array(basename($to_remove), $ignore_files))
			{
				continue; // Don't remove the file
			}
			else
			{
				@unlink($to_remove);
			}
		}
		else if(is_dir($to_remove))
		{
			pts_remove($to_remove, $ignore_files, true);
		}
	}

	if($remove_root_directory && is_dir($object) && count(pts_glob($object . "/*")) == 0)
	{
		@rmdir($object);
	}
}
function pts_copy($from, $to)
{
	// Copies a file
	if(!is_file($to) || md5_file($from) != md5_file($to))
	{
		copy($from, $to);
	}
}
function pts_rename($from, $to)
{
	return rename($from, $to);
}
function pts_symlink($from, $to)
{
	return @symlink($from, $to);
}
function pts_move($from, $to)
{
	return rename($from, $to);
}
function pts_run_shell_script($file, $arguments = "")
{
	if(is_array($arguments))
	{
		$arguments = implode(" ", $arguments);
	}

	return shell_exec("sh " . $file . " ". $arguments . " 2>&1");
}
function pts_process_running_bool($process)
{
	if(IS_LINUX)
	{
		// Checks if process is running on the system
		$running = shell_exec("ps -C " . strtolower($process) . " 2>&1");
		$running = trim(str_replace(array("PID", "TTY", "TIME", "CMD"), "", $running));
	}
	else if(IS_SOLARIS)
	{
		// Checks if process is running on the system
		$ps = shell_exec("ps -ef 2>&1");
		$running = strpos($ps, " " . strtolower($process)) != false ? "TRUE" : null;
	}
	else if(pts_client::executable_in_path("ps") != false)
	{
		// Checks if process is running on the system
		$ps = shell_exec("ps -ax 2>&1");
		$running = strpos($ps, " " . strtolower($process)) != false ? "TRUE" : null;
	}
	else
	{
		$running = null;
	}

	return !empty($running);
}
function pts_set_environment_variable($name, $value)
{
	// Sets an environmental variable
	return getenv($name) == false && putenv($name . "=" . $value);
}

?>
