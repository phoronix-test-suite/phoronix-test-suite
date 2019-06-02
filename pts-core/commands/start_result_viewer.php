<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2019, Phoronix Media
	Copyright (C) 2019, Michael Larabel

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

class start_result_viewer implements pts_option_interface
{
	const doc_section = 'Result Viewer';
	const doc_description = 'Start the web-based result viewer.';

	public static function command_aliases()
	{
		return array('start_results_viewer');
	}
	public static function run($r)
	{
		if(pts_client::create_lock(PTS_USER_PATH . 'result_viewer_lock') == false)
		{
			trigger_error('The result viewer is already running.', E_USER_ERROR);
			return false;
		}

		pts_file_io::unlink(getenv('PTS_EXT_LAUNCH_SCRIPT_DIR') . '/web-server-launcher');
		if(PHP_VERSION_ID < 50400)
		{
			echo 'Running an unsupported PHP version. PHP 5.4+ is required to use this feature.' . PHP_EOL . PHP_EOL;
			return false;
		}

		$server_launcher = '#!/bin/sh' . PHP_EOL;
		$web_port = 0;
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
			while(($fp = fsockopen('127.0.0.1', $remote_access, $errno, $errstr, 5)) != false);
			echo 'Port ' . $remote_access . ' chosen as random port for this instance. Change the default port via the Phoronix Test Suite user configuration file.' . PHP_EOL;
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
			trigger_error('Port ' . $remote_access . ' is already in use by another server process. Close that process or change the Phoronix Test Suite server port via' . pts_config::get_config_file_location() . ' to proceed.', E_USER_ERROR);
			return false;
		}
		else
		{
			$web_port = $remote_access;
		}

		// Setup server logger
		define('PHOROMATIC_SERVER', true);
		// Just create the logger so now it will flush it out
		echo pts_core::program_title(true) . ' starting web-based result viewer on ' . $web_port . PHP_EOL;
		echo 'Phoronix Test Suite Configuration File: ' . pts_config::get_config_file_location() . PHP_EOL;

		// HTTP Server Setup
		// PHP Web Server
		echo PHP_EOL . 'Launching with PHP built-in web server.' . PHP_EOL;
		$ak = pts_config::read_user_config('PhoronixTestSuite/Options/ResultViewer/AccessKey', '');
		$server_launcher .= 'export PTS_VIEWER_ACCESS_KEY="' . (empty($ak) ? null : trim(hash('sha256', trim($ak)))) . '"' . PHP_EOL;
		$server_launcher .= 'export PTS_VIEWER_RESULT_PATH="' . PTS_SAVE_RESULTS_PATH . '"' . PHP_EOL;
		$server_launcher .= 'export PTS_VIEWER_PTS_PATH="' . getenv('PTS_DIR') . '"' . PHP_EOL;
		$server_launcher .= getenv('PHP_BIN') . ' -S ' . $server_ip . ':' . $web_port . ' -t ' . PTS_CORE_PATH . 'static/dynamic-result-viewer/ &' . PHP_EOL; //2> /dev/null
		$server_launcher .= 'http_server_pid=$!'. PHP_EOL;
		$server_launcher .= 'sleep 1' . PHP_EOL;
		$server_launcher .= 'echo "The result viewer is now accessible at: http://localhost:' . $web_port . '"' . PHP_EOL;

		// Wait for input to shutdown process..
		if(!PTS_IS_DAEMONIZED_SERVER_PROCESS)
		{
			$server_launcher .= PHP_EOL . 'echo -n "Press [ENTER] to kill server..."' . PHP_EOL;
			$server_launcher .= PHP_EOL . 'read var_name';
		}
		else
		{
			$server_launcher .= PHP_EOL . 'while [ ! -f "/var/lib/phoronix-test-suite/end-phoromatic-server" ];';
			$server_launcher .= PHP_EOL . 'do';
			$server_launcher .= PHP_EOL . 'sleep 3';
			$server_launcher .= PHP_EOL . 'done';
			$server_launcher .= PHP_EOL . 'rm -f /var/lib/phoronix-test-suite/end-phoromatic-server' . PHP_EOL;
		}

		// Shutdown / Kill Servers
		$server_launcher .= PHP_EOL . 'kill $http_server_pid';
		$server_launcher .= PHP_EOL . 'rm -f ~/.phoronix-test-suite/run-lock*';
		file_put_contents(getenv('PTS_EXT_LAUNCH_SCRIPT_DIR') . '/web-server-launcher', $server_launcher);
	}
}

?>
