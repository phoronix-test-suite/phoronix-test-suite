<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2019 - 2021, Phoronix Media
	Copyright (C) 2019 - 2021, Michael Larabel
	toggle_screensaver.php: A module to toggle the screensaver while tests are running on GNOME

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

class load_dynamic_result_viewer extends pts_module_interface
{
	const module_name = 'Dynamic Result Viewer';
	const module_version = '1.0.0';
	const module_description = 'This module pre-loads the HTTP dynamic result viewer for Phoronix Test Suite data.';
	const module_author = 'Phoronix Media';

	protected static $process = null;
	protected static $pipes;
	protected static $random_id;
	protected static $num_php_service_workers = 4;

	public static function __shutdown()
	{
		if(is_resource(self::$process) || phodevi::is_windows())
		{
			if(pts_client::$has_used_modern_result_viewer && pts_client::$last_browser_launch_time > (time() - 10))
			{
				// Likely got connected to an existing browser process, so wait longer before quitting (killing the web server process)
				if(pts_client::$last_browser_duration < 2)
				{
					echo '     ' . pts_client::cli_just_bold('Result File URL: ') . pts_client::$last_result_view_url . PHP_EOL;
					echo pts_client::cli_just_italic('     [ Hit ENTER when finished viewing the results to end the result viewer process. ]') . PHP_EOL;
					pts_user_io::read_user_input();
					sleep(1);
				}
				else
				{
					sleep(3);
				}
			}
		}

		if(is_resource(self::$process))
		{
			foreach(self::$pipes as $i => $pipe)
			{
				fclose(self::$pipes[$i]);
			}
			$ps = proc_get_status(self::$process);
			if(isset($ps['pid']) && function_exists('posix_kill'))
			{
				 posix_kill($ps['pid'], 9);
			}
			else
			{
				proc_terminate(self::$process);
			}
			proc_close(self::$process);
			if(isset($ps['pid']))
			{
				sleep(1);
				pts_client::kill_process_with_children_processes($ps['pid']);
			}

			// Fallback for sometimes the child process not getting killed
			// Check next few PIDs to see if still children running
			for($pid_plus = 1; $pid_plus <= ((self::$num_php_service_workers * 2) + 1); $pid_plus++)
			{
				foreach(pts_file_io::glob('/proc/' . ($ps['pid'] + $pid_plus) . '/comm') as $proc_check)
				{
					$proc = dirname($proc_check);
					if(strpos(pts_file_io::file_get_contents($proc . '/comm'), 'php') !== false)
					{
						if(is_file($proc . '/environ') && strpos(pts_file_io::file_get_contents($proc . '/environ'), 'PTS_VIEWER_ID=' . self::$random_id) !== false)
						{
							if(pts_client::executable_in_path('kill'))
							{
								shell_exec('kill -9 ' . basename($proc));
							}
							if(function_exists('posix_kill'))
							{
								posix_kill(basename($proc), 9);
							}
						}
					}
				}
			}
		}
		pts_client::release_lock(PTS_USER_PATH . 'result_viewer_lock');
	}
	public static function user_commands()
	{
		return array('start' => 'start_result_viewer');
	}
	public static function __startup()
	{
		if(pts_client::create_lock(PTS_USER_PATH . 'result_viewer_lock') == false)
		{
			if(is_file(PTS_USER_PATH . 'result_viewer_lock'))
			{
				$possible_port = pts_file_io::file_get_contents(PTS_USER_PATH . 'result_viewer_lock');
				if(is_numeric($possible_port) && pts_client::test_for_result_viewer_connection($possible_port))
				{
					pts_client::$web_result_viewer_active = $possible_port;
					return;
				}
			}
			//trigger_error('The result viewer is already running.', E_USER_ERROR);
			return false;
		}
		if(PHP_VERSION_ID < 50400)
		{
			//echo 'Running an unsupported PHP version. PHP 5.4+ is required to use this feature.' . PHP_EOL . PHP_EOL;
			return false;
		}

		if(!phodevi::is_windows())
		{
			// Result viewer on Windows should be fired off from separate process in Windows bat file
			self::start_result_viewer();
		}
	}
	public static function start_result_viewer()
	{
		$remote_access = pts_config::read_user_config('PhoronixTestSuite/Options/ResultViewer/WebPort', 'RANDOM');
		$errno = null;
		$errstr = null;

		if($remote_access == 'RANDOM' || !is_numeric($remote_access))
		{
			$remote_access = pts_network::find_available_port();
		//	echo 'Port ' . $remote_access . ' chosen as random port for this instance. Change the default port via the Phoronix Test Suite user configuration file.' . PHP_EOL;
		}

		$remote_access = is_numeric($remote_access) && $remote_access > 1 ? $remote_access : false;

		$access_limited_to_localhost = true;
		if(pts_config::read_bool_config('PhoronixTestSuite/Options/ResultViewer/LimitAccessToLocalHost', 'TRUE'))
		{
			$server_ip = 'localhost';
		}
		else
		{
			// Allows server to be web accessible
			$server_ip = '0.0.0.0';
			$access_limited_to_localhost = false;
		}
		if(($fp = fsockopen('127.0.0.1', $remote_access, $errno, $errstr, 5)) != false)
		{
			fclose($fp);
			//trigger_error('Port ' . $remote_access . ' is already in use by another server process. Close that process or change the Phoronix Test Suite server port via' . pts_config::get_config_file_location() . ' to proceed.', E_USER_ERROR);
			return false;
		}
		else
		{
			$web_port = $remote_access;
		}

		// Setup server logger
		//echo PHP_EOL . 'Launching with PHP built-in web server.' . PHP_EOL;
		$ak = pts_config::read_user_config('PhoronixTestSuite/Options/ResultViewer/AccessKey', '');

		if(empty($ak))
		{
			$access_key = null;
		}
		else if(function_exists('hash'))
		{
			$access_key = trim(hash('sha256', trim($ak)));
		}
		else
		{
			$access_key = trim(sha1(trim($ak)));
		}
		self::$random_id = rand(100, getrandmax());

		$descriptorspec = array(
		0 => array('pipe', 'r'),
		1 => array('pipe', 'w'),
		2 => array('pipe', 'w')
		);
		$cwd = getcwd();
		$env = array(
		'PTS_VIEWER_ACCESS_KEY' => $access_key,
		'PTS_VIEWER_RESULT_PATH' => PTS_SAVE_RESULTS_PATH,
		'PTS_VIEWER_PTS_PATH' => PTS_PATH,
		'PTS_VIEWER_CONFIG_FILE' => pts_config::get_config_file_location(),
		'PTS_VIEWER_ID' => self::$random_id,
		'PTS_CORE_STORAGE' => PTS_CORE_STORAGE,
		'PHP_CLI_SERVER_WORKERS' => self::$num_php_service_workers,
		);

		pts_storage_object::set_in_file(PTS_CORE_STORAGE, 'last_web_result_viewer_active_port', $web_port);
		pts_client::write_to_lock(PTS_USER_PATH . 'result_viewer_lock', $web_port);
		pts_client::$web_result_viewer_active = $web_port;
		pts_client::$web_result_viewer_access_key = $ak;

		if(($ip = phodevi::read_property('network', 'ip')) && !$access_limited_to_localhost)
		{
			echo pts_client::cli_just_bold('Result Viewer: http://' . $ip . ':' . $web_port) . PHP_EOL;
			if(!empty($ak))
			{
				echo PHP_EOL . pts_client::cli_just_bold('Result Viewer Access Key: ' . $ak) . PHP_EOL;
			}
		}

		if(phodevi::is_windows())
		{
			$env_string = '';
			foreach($env as $key => $val)
			{
				$env_string.= '' . $key . '=' . $val . PHP_EOL;
			}
			file_put_contents(getenv('TEMP') . '/pts-env-web', $env_string);
			//echo $server_ip . ':' . $web_port;
			exec(getenv('PHP_BIN') . ' -S ' . $server_ip . ':' . $web_port . ' -t ' . str_replace('/', '\\', PTS_CORE_PATH) . 'static\dynamic-result-viewer\ > NUL');
		}
		else
		{
			self::$process = proc_open(getenv('PHP_BIN') . ' -S ' . $server_ip . ':' . $web_port . ' -t ' . PTS_CORE_PATH . 'static/dynamic-result-viewer/ ', $descriptorspec, self::$pipes, $cwd, $env);
		}
	}
}

?>
