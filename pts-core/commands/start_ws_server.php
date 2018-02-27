<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2013 - 2015, Phoronix Media
	Copyright (C) 2013 - 2015, Michael Larabel

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

class start_ws_server implements pts_option_interface
{
	const doc_skip = true;
	const doc_section = 'Web / GUI Support';
	const doc_description = 'Manually start a WebSocket server for communication by remote Phoronix Test Suite GUIs, the Phoronix Test Suite Multi-System Commander, and other functionality. This function checks the PTS_WEBSOCKET_PORT and PTS_WEBSOCKET_SERVER environment variables for configuration.';

	public static function run($r)
	{
		if(getenv('PTS_WEBSOCKET_PORT') !== false)
		{
			$web_socket_port = getenv('PTS_WEBSOCKET_PORT');
		}
		if(!isset($web_socket_port) || !is_numeric($web_socket_port))
		{
			$web_socket_port = pts_config::read_user_config('PhoronixTestSuite/Options/Server/WebSocketPort', '80');
		}
		if(!isset($web_socket_port) || !is_numeric($web_socket_port))
		{
			$web_socket_port = '80';
		}

		/*
		if(getenv('PTS_PHOROMATIC_SERVER'))
		{
			if(function_exists('pcntl_fork'))
			{
				$pid = pcntl_fork();

				if($pid != -1)
				{
					if($pid)
					{
						$new_pid = $pid;
					}
					else
					{
						$event_server = new pts_phoromatic_event_server();
						exit(0);
					}
				}
			}
			else
			{
				echo PHP_EOL . 'Phoromatic Event Server Fails To Start Due To Lacking PCNTL Support.' . PHP_EOL . PHP_EOL;
			}
		} */

		switch(getenv('PTS_WEBSOCKET_SERVER'))
		{
			case 'PHOROMATIC':
				pts_web_socket::$mask_send = true;
				$websocket = new pts_web_socket_server_phoromatic('localhost', $web_socket_port);
				break;
			default:
			case 'GUI':
				$websocket = new pts_web_socket_server_gui('localhost', $web_socket_port);
				break;
		}
	}
}

?>
