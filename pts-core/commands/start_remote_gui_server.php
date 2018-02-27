<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2012 - 2015, Phoronix Media
	Copyright (C) 2012 - 2015, Michael Larabel

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

class start_remote_gui_server implements pts_option_interface
{
	const doc_skip = true;
	const doc_section = 'Web / GUI Support';
	const doc_description = 'Start the GUI web server and WebSocket server processes for remote (or local) access via the web-browser. The settings can be configured via the Phoronix Test Suite\'s XML configuration file.';

	public static function run($r)
	{
		pts_file_io::unlink(getenv('PTS_EXT_LAUNCH_SCRIPT_DIR') . '/web-server-launcher');
		if(PHP_VERSION_ID < 50400)
		{
			echo 'Running an unsupported PHP version. PHP 5.4+ is required to use this feature.' . PHP_EOL . PHP_EOL;
			return false;
		}

		$server_launcher = '#!/bin/sh' . PHP_EOL;
		$web_port = 0;
		$remote_access = pts_config::read_user_config('PhoronixTestSuite/Options/Server/RemoteAccessPort', 'FALSE');
		$remote_access = is_numeric($remote_access) && $remote_access > 1 ? $remote_access : false;
		$blocked_ports = array(2049, 3659, 4045, 6000);

		if($remote_access)
		{
			// ALLOWING SERVER TO BE REMOTELY ACCESSIBLE
			$server_ip = '0.0.0.0';
			$fp = false;
			$errno = null;
			$errstr = null;

			if(($fp = fsockopen('127.0.0.1', $remote_access, $errno, $errstr, 5)) != false)
			{
				trigger_error('Port ' . $remote_access . ' is already in use by another server process. Close that process or change the Phoronix Test Suite server port via ' . pts_config::get_config_file_location() . ' to proceed.', E_USER_ERROR);
				fclose($fp);
				return false;
			}
			else
			{
				$web_port = $remote_access;
				$web_socket_port = pts_config::read_user_config('PhoronixTestSuite/Options/Server/WebSocketPort', '');

				if($web_socket_port == null || !is_numeric($web_socket_port))
				{
					$web_socket_port = $web_port - 1;
				}
			}
		}
		else
		{
			echo PHP_EOL . PHP_EOL . 'You must first configure the remote GUI/WEBUI settings via:' . pts_config::get_config_file_location() . PHP_EOL . PHP_EOL;
			return false;
		}

		// WebSocket Server Setup
		$server_launcher .= 'export PTS_WEBSOCKET_PORT=' . $web_socket_port . PHP_EOL;
		$server_launcher .= 'export PTS_WEBSOCKET_SERVER=GUI' . PHP_EOL;
		$server_launcher .= 'cd ' . getenv('PTS_DIR') . ' && PTS_MODE="CLIENT" ' . getenv('PHP_BIN') . ' pts-core/phoronix-test-suite.php start-ws-server &' . PHP_EOL;
		$server_launcher .= 'websocket_server_pid=$!'. PHP_EOL;

		// HTTP Server Setup
		if(strpos(getenv('PHP_BIN'), 'hhvm'))
		{
			echo PHP_EOL . 'Unfortunately, the HHVM built-in web server has abandoned upstream. Users will need to use the PHP binary or other alternatives.' . PHP_EOL . PHP_EOL;
			return false;
		}
		else
		{
			$server_launcher .= getenv('PHP_BIN') . ' -S ' . $server_ip . ':' . $web_port . ' -t ' . PTS_CORE_PATH . 'web-interface/ 2> /dev/null  &' . PHP_EOL; //2> /dev/null
		}
		$server_launcher .= 'http_server_pid=$!'. PHP_EOL;
		$server_launcher .= 'sleep 1' . PHP_EOL;

		$server_launcher .= 'echo "The Web Interface Is Accessible At: http://localhost:' . $web_port . '"' . PHP_EOL;
		$server_launcher .= PHP_EOL . 'echo -n "Press [ENTER] to kill server..."' . PHP_EOL . 'read var_name';

		// Shutdown / Kill Servers
		$server_launcher .= PHP_EOL . 'kill $http_server_pid';
		$server_launcher .= PHP_EOL . 'kill $websocket_server_pid';
		$server_launcher .= PHP_EOL . 'rm -f ~/.phoronix-test-suite/run-lock*';
		file_put_contents(getenv('PTS_EXT_LAUNCH_SCRIPT_DIR') . '/web-server-launcher', $server_launcher);
	}
}

?>
