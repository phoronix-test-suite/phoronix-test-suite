<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
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

function pts_display_web_browser($URL, $alt_text = null, $default_open = false, $auto_open = false)
{
	if(pts_read_assignment("AUTOMATED_MODE") != false)
	{
		return;
	}

	// Launch the web browser
	if($alt_text == null)
	{
		$text = "Do you want to view the results in your web browser";
	}
	else
	{
		$text = $alt_text;
	}

	if($auto_open == false)
	{
		if(!$default_open)
		{
			$view_results = pts_bool_question($text . " (y/N)?", false, "OPEN_BROWSER");
		}
		else
		{
			$view_results = pts_bool_question($text . " (Y/n)?", true, "OPEN_BROWSER");
		}
	}
	else
	{
		$view_results = true;
	}

	if($view_results)
	{
		pts_run_shell_script(PTS_PATH . "pts-core/scripts/launch-browser.sh", "\"$URL\"");
	}
}
function pts_exec($exec, $extra_vars = null)
{
	// Same as shell_exec() but with the PTS env variables added in
	return shell_exec(pts_variables_export_string($extra_vars) . $exec);
}
function pts_download($download, $to)
{
	$to_file = basename($to);
	$to_dir = dirname($to);
	$download_output = "";
	$user_agent = pts_codename(true);

	if(strpos($to_file, ".") === false)
	{
		$to_file = basename($download);
	}
	else if(is_executable("/usr/bin/curl") || is_executable("/usr/local/bin/curl") || is_executable("/usr/pkg/bin/curl"))
	{
		// curl download
		$download_output = shell_exec("cd " . $to_dir . " && curl -L --fail --user-agent \"" . $user_agent . "\" " . $download . " > " . $to_file);
	}
	else if(is_executable("/usr/bin/wget") || is_executable("/usr/local/bin/wget") || is_executable("/usr/pkg/bin/wget"))
	{
		// wget download
		$download_output = shell_exec("cd " . $to_dir . " && wget --user-agent=\"" . $user_agent . "\" " . $download . " -O " . $to_file);
	}
	else if(IS_BSD && is_executable("/usr/bin/ftp"))
	{
		// NetBSD ftp(1) download; also speaks http, but not https
		$download_output = shell_exec("cd " . $to_dir . " && ftp -V " . $download . " -o " . $to_file);
	}
	else
	{
		$download_output = "No downloading application available.";
	}

	return $download_output;
}
function pts_remove($object, $ignore_files = null)
{
	if(is_dir($object) && substr($object, -1) != "/")
	{
		$object .= "/";
	}

	foreach(glob($object . "*") as $to_remove)
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
			pts_remove($to_remove, $ignore_files);

			//if(count(glob($to_remove . "/*")) == 0)
			//{
				@rmdir($object);
			//}
		}
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
function pts_move_file($from, $to, $change_directory = "")
{
	return shell_exec("cd " . $change_directory . " && mv " . $from . " " . $to . " 2>&1");
}
function pts_extract_file($file, $remove_afterwards = false)
{
	$file_name = basename($file);
	$file_path = dirname($file);

	switch(substr($file_name, strpos($file_name, ".") + 1))
	{
		case "tar":
			$extract_cmd = "tar -xf";
			break;
		case "tar.gz":
			$extract_cmd = "tar -zxf";
			break;
		case "tar.bz2":
			$extract_cmd = "tar -jxf";
			break;
		case "tar.bz2":
			$extract_cmd = "tar -jxf";
			break;
		case "zip":
			$extract_cmd = "zip -O";
			break;
		default:
			$extract_cmd = "";
			break;
	}

	shell_exec("cd " . $file_path . " && " . $extract_cmd . " " . $file_name . " 2>&1");

	if($remove_afterwards == true)
	{
		pts_remove($file);
	}
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
	// Checks if process is running on the system
	$running = shell_exec("ps -C " . strtolower($process) . " 2>&1");
	$running = trim(str_replace(array("PID", "TTY", "TIME", "CMD"), "", $running));

	$running = !empty($running) && !IS_MACOSX && !IS_SOLARIS;

	return $running;
}
function pts_process_running_string($process_arr)
{
	// Format a nice string that shows processes running
	$p = array();
	$p_string = "";

	$process_arr = pts_to_array($process_arr);

	foreach($process_arr as $p_name => $p_process)
	{
		$p_process = pts_to_array($p_process);

		foreach($p_process as $process)
		{
			if(pts_process_running_bool($process))
			{
				array_push($p, $p_name);
			}
		}
	}

	$p = array_keys(array_flip($p));

	if(($p_count = count($p)) > 0)
	{
		for($i = 0; $i < $p_count; $i++)
		{
			$p_string .= $p[$i];

			if($i != ($p_count - 1) && $p_count > 2)
			{
				$p_string .= ",";
			}
			$p_string .= " ";

			if($i == ($p_count - 2))
			{
				$p_string .= "and ";
			}
		}

		$p_string .= ($p_count == 1 ? "was" : "were");
		$p_string .= " running on this system";
	}

	return $p_string;
}
function pts_set_environment_variable($name, $value)
{
	// Sets an environmental variable
	if(getenv($name) == false)
	{
		putenv($name . "=" . $value);
	}
}

?>
