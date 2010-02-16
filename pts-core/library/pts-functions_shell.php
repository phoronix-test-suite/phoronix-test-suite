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

function pts_display_web_browser($URL, $alt_text = null, $default_open = false, $auto_open = false)
{
	if(pts_read_assignment("AUTOMATED_MODE") || getenv("DISPLAY") == false)
	{
		return;
	}

	// Launch the web browser
	$text = ($alt_text == null ? "Do you want to view the results in your web browser" : $alt_text);

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
		static $browser = null;

		if($browser == null)
		{
			$config_browser = pts_read_user_config(P_OPTION_DEFAULT_BROWSER, null);

			if($config_browser != null && (is_executable($config_browser) || ($config_browser = pts_executable_in_path($config_browser))))
			{
				$browser = $config_browser;
			}
			else
			{
				$possible_browsers = array("xdg-open", "epiphany", "firefox", "mozilla", "x-www-browser", "open");

				foreach($possible_browsers as &$b)
				{
					if(($b = pts_executable_in_path($b)))
					{
						$browser = $b;
						break;
					}
				}
			}
		}

		shell_exec($browser . " \"" . $URL . "\" &");
	}
}
function pts_exec($exec, $extra_vars = null)
{
	// Same as shell_exec() but with the PTS env variables added in
	return shell_exec(pts_variables_export_string($extra_vars) . $exec);
}
function pts_download($download, $to, &$display_mode = null)
{
	if(getenv("PTS_USE_OLD_DOWNLOAD_CODE") == false)
	{
		pts_display_mode_holder($display_mode);

		if(function_exists("curl_init"))
		{
			pts_curl_download($download, $to);
		}
		else
		{
			pts_stream_download($download, $to);
		}

		//echo "\nPHP CURL must either be installed or you must adjust your PHP settings file to support opening FTP/HTTP streams.\n";
		//return false;

		$display_mode = pts_display_mode_holder();

		if($display_mode)
		{
			$display_mode->test_install_download_completed();
		}

		return;
	}

	// TODO: The below code will be dropped with Phoronix Test Suite 2.6
	// In Phoronix Test Suite 2.4 if you want to use it set PTS_USE_OLD_DOWNLOAD_CODE=1 as an environmental variable

	$to_file = basename($to);
	$to_dir = pts_add_trailing_slash(dirname($to));
	$download_output = null;
	$user_agent = pts_codename(true);
	$connection_timeout = NETWORK_TIMEOUT;

	if(strpos($to_file, ".") === false)
	{
		$to_file = basename($download);
	}

	else if(($curl = pts_executable_in_path("curl")) != false)
	{
		// curl download
		$download_output = shell_exec("cd " . $to_dir . " && " . $curl . (defined("NETWORK_PROXY") ? " -x " . NETWORK_PROXY : null) . " -L --fail --connect-timeout " . $connection_timeout . " --user-agent \"" . $user_agent . "\" " . $download . " > " . $to_file);
	}
	else if(($wget = pts_executable_in_path("wget")) != false)
	{
		// wget download
		$download_output = shell_exec((defined("NETWORK_PROXY") ? "export http_proxy=\"" . NETWORK_PROXY . "\"; export ftp_proxy=\"" . NETWORK_PROXY . "\"; " : null) . "cd " . $to_dir . " && " . $wget . " --timeout=" . $connection_timeout . " --tries=3 --user-agent=\"" . $user_agent . "\" " . $download . " -O " . $to_file);
	}
	else if(IS_BSD && ($ftp = pts_executable_in_path("ftp")) != false)
	{
		// NetBSD ftp(1) download; also speaks http, but not https
		$download_output = shell_exec("cd " . $to_dir . " && " . $ftp . " -V " . $download . " -o " . $to_file);
	}
	else
	{
		$download_output = "No download application available.";
	}

	return $download_output;
}
function pts_executable_in_path($executable)
{
	static $cache = null;

	if(!isset($cache[$executable]))
	{
		$paths = explode(":", (($path = getenv("PATH")) == false ? "/usr/bin:/usr/local/bin" : $path));
		$executable_path = false;

		foreach($paths as $path)
		{
			$path = pts_add_trailing_slash($path);

			if(is_executable($path . $executable))
			{
				$executable_path = $path . $executable;
				break;
			}
		}

		$cache[$executable] = $executable_path;
	}

	return $cache[$executable];
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
		rmdir($object);
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
function pts_extract($file)
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
		case "zip":
			$extract_cmd = "unzip -o";
			break;
		default:
			$extract_cmd = "";
			break;
	}

	shell_exec("cd " . $file_path . " && " . $extract_cmd . " " . $file_name . " 2>&1");
}
function pts_compress($to_compress, $compress_to)
{
	$compress_to_file = basename($compress_to);
	$compress_base_dir = dirname($to_compress);
	$compress_base_name = basename($to_compress);

	switch(substr($compress_to_file, strpos($compress_to_file, ".") + 1))
	{
		case "tar":
			$extract_cmd = "tar -cf " . $compress_to . " " . $compress_base_name;
			break;
		case "tar.gz":
			$extract_cmd = "tar -czf " . $compress_to . " " . $compress_base_name;
			break;
		case "tar.bz2":
			$extract_cmd = "tar -cjf " . $compress_to . " " . $compress_base_name;
			break;
		case "zip":
			$extract_cmd = "zip -r " . $compress_to . " " . $compress_base_name;
			break;
		default:
			$extract_cmd = null;
			break;
	}

	if($extract_cmd != null)
	{
		shell_exec("cd " . $compress_base_dir . " && " . $extract_cmd . " 2>&1");
	}
}
function pts_zip_archive_extract($zip_file, $extract_to)
{
	if(!class_exists("ZipArchive") || !is_readable($zip_file))
	{
		return false;
	}

	$zip = new ZipArchive();
	$res = $zip->open($zip_file);

	if($res === TRUE && is_writable($extract_to))
	{
		$zip->extractTo($extract_to);
		$zip->close();
		$success = true;
	}
	else
	{
		$success = false;
	}

	return $success;
}
function pts_zip_archive_create($zip_file, $add_files)
{
	if(!class_exists("ZipArchive"))
	{
		return false;
	}

	$zip = new ZipArchive();

	if($zip->open($zip_file, ZIPARCHIVE::CREATE) !== true)
	{
		$success = false;
	}
	else
	{
		foreach(pts_to_array($add_files) as $add_file)
		{
			pts_zip_archive_add($zip, $add_file, dirname($add_file));
		}

		$success = true;
	}

	return $success;
}
function pts_zip_archive_add(&$zip, $add_file, $base_dir = null)
{
	if(is_dir($add_file))
	{
		$zip->addEmptyDir(substr($add_file, strlen(pts_add_trailing_slash($base_dir))));

		foreach(pts_glob(pts_add_trailing_slash($add_file) . '*') as $new_file)
		{
			pts_zip_archive_add($zip, $new_file, $base_dir);
		}
	}
	else if(is_file($add_file))
	{
		$zip->addFile($add_file, substr($add_file, strlen(pts_add_trailing_slash($base_dir))));
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
	else if(pts_executable_in_path("ps") != false)
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
