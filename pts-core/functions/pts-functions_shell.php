<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
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

function pts_display_web_browser($URL, $alt_text = null, $default_open = false)
{
	// Launch the web browser
	if($alt_text == null)
	{
		$text = "Do you want to view the results in your web browser";
	}
	else
	{
		$text = $alt_text;
	}

	if(!$default_open)
	{
		$view_results = pts_bool_question($text . " (y/N)?", false, "OPEN_BROWSER");
	}
	else
	{
		$view_results = pts_bool_question($text . " (Y/n)?", true, "OPEN_BROWSER");
	}

	if($view_results)
	{
		pts_run_shell_script("pts-core/scripts/launch-browser.sh", $URL);
	}
}
function pts_download($download, $to)
{
	$to_file = basename($to);
	$to_dir = dirname($to);
	$download_output = "";
	$user_agent = "PhoronixTestSuite/" . PTS_CODENAME;

	if(strpos($to_file, ".") === false)
	{
		$to_file = basename($download);
	}
	else if(is_executable("/usr/bin/curl") || is_executable("/usr/local/bin/curl"))
	{
		// curl download
		$download_output = shell_exec("cd " . $to_dir . " && curl -L --fail --user-agent \"" . $user_agent . "\" " . $download . " > " . $to_file);
	}
	else if(is_executable("/usr/bin/wget") || is_executable("/usr/local/bin/wget"))
	{
		// wget download
		$download_output = shell_exec("cd " . $to_dir . " && wget --user-agent=\"" . $user_agent . "\" " . $download . " -O " . $to_file);
	}
	else
	{
		$download_output = "No downloading application available.";
	}

	return $download_output;
}
function pts_remove($object, $ignore_files = null)
{
	if(!file_exists($object))
	{
		return false;
	}

	if(is_file($object))
	{
		if(is_array($ignore_files) && in_array(basename($object), $ignore_files))
		{
			return true;
		}
		else
		{
			return unlink($object);
		}
	}

	if(is_dir($object))
	{
		$directory = dir($object);
		while(($entry = $directory->read()) !== false)
		{
			if($entry != "." && $entry != "..")
			{
				pts_remove($object . "/" . $entry, $ignore_files);
			}
		}
		$directory->close();
	}

	return @rmdir($object);
}
function pts_copy($from, $to)
{
	// Copies a file
	if(!is_file($to) || md5_file($from) != md5_file($to))
	{
		copy($from, $to);
	}
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

	if(!is_array($process_arr))
	{
		$process_arr = array($process_arr);
	}

	foreach($process_arr as $p_name => $p_process)
	{
		if(!is_array($p_process))
		{
			$p_process = array($p_process);
		}

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

		if($p_count == 1)
		{
			$p_string .= "was";
		}
		else
		{
			$p_string .= "were";
		}

		$p_string .= " running on this system";
	}

	return $p_string;
}

?>
