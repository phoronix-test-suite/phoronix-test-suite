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

function display_web_browser($URL, $alt_text = null, $default_open = false)
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

	if(is_executable("/usr/bin/curl"))
	{
		// curl download
		$download_output = shell_exec("cd " . $to_dir . " && curl -L --fail --user-agent \"" . $user_agent . "\" " . $download . " > " . $to_file);
	}
	else
	{
		// wget download
		$download_output = shell_exec("cd " . $to_dir . " && wget --user-agent=\"" . $user_agent . "\" " . $download . " -O " . $to_file);
	}

	return $download_output;
}
function pts_remove($object)
{
	if(!file_exists($object))
	{
		return false;
	}

	if(is_file($object))
	{
		return unlink($object);
	}

	if(is_dir($object))
	{
		$directory = dir($object);
		while(($entry = $directory->read()) !== false)
		{
			if($entry != "." && $entry != "..")
			{
				pts_remove($object . "/" . $entry);
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

?>
