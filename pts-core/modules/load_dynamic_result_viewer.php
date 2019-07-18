<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2019, Phoronix Media
	Copyright (C) 2019, Michael Larabel
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

	public static function __shutdown()
	{
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
		}
	}
	public static function __startup()
	{
		if(pts_client::create_lock(PTS_USER_PATH . 'result_viewer_lock') == false)
		{
			//trigger_error('The result viewer is already running.', E_USER_ERROR);
			return false;
		}
		if(PHP_VERSION_ID < 50400)
		{
			//echo 'Running an unsupported PHP version. PHP 5.4+ is required to use this feature.' . PHP_EOL . PHP_EOL;
			return false;
		}

		$remote_access = pts_config::read_user_config('PhoronixTestSuite/Options/ResultViewer/WebPort', 'RANDOM');
		$fp = false;
		$errno = null;
		$errstr = null;

		if($remote_access == 'RANDOM' || !is_numeric($remote_access))
		{
			do
			{
				if($fp)
					fclose($fp);

				$remote_access = rand(8000, 8999);
			}
			while(($fp = fsockopen('127.0.0.1', $remote_access, $errno, $errstr, 3)) != false);
		//	echo 'Port ' . $remote_access . ' chosen as random port for this instance. Change the default port via the Phoronix Test Suite user configuration file.' . PHP_EOL;
		}

		$remote_access = is_numeric($remote_access) && $remote_access > 1 ? $remote_access : false;
		$blocked_ports = array(2049, 3659, 4045, 6000, 9000);

		if(pts_config::read_bool_config('PhoronixTestSuite/Options/ResultViewer/LimitAccessToLocalHost', 'TRUE'))
		{
			$server_ip = 'localhost';
		}
		else
		{
			// Allows server to be web accessible
			$server_ip = '0.0.0.0';
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

		if(!phodevi::is_windows())
		{
			$descriptorspec = array(
			0 => array('pipe', 'r'),
			1 => array('pipe', 'w'),
			2 => array('pipe', 'w')
			);
			$cwd = getcwd();
			$env = array(
			'PTS_VIEWER_ACCESS_KEY' => (empty($ak) ? null : trim(hash('sha256', trim($ak)))),
			'PTS_VIEWER_RESULT_PATH' => PTS_SAVE_RESULTS_PATH,
			'PTS_VIEWER_PTS_PATH' => getenv('PTS_DIR'),
			);

			self::$process = proc_open(getenv('PHP_BIN') . ' -S ' . $server_ip . ':' . $web_port . ' -t ' . PTS_CORE_PATH . 'static/dynamic-result-viewer/ ', $descriptorspec, self::$pipes, $cwd, $env);
			pts_client::$web_result_viewer_active = $web_port;
		}
	}
}

?>
