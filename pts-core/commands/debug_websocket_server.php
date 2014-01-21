<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2013 - 2014, Phoronix Media
	Copyright (C) 2013 - 2014, Michael Larabel

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

class debug_websocket_server implements pts_option_interface
{
	const doc_skip = true;
	const doc_section = 'Other';
	const doc_description = 'Phoronix Test Suite WebSocket server testing.';

	public static function run($r)
	{
		//$URL = PTS_SAVE_RESULTS_PATH . $r[0];
	//$master = new websocket('localhost', 12345);
		if(getenv('PTS_WEBSOCKET_PORT') !== false)
		{
			$web_socket_port = getenv('PTS_WEBSOCKET_PORT');
		}
		if(!isset($web_socket_port) || !is_numeric($web_socket_port))
		{
			$web_socket_port = '80';
		}

		pts_web_socket::$debug_mode = true;
		$websocket = new pts_web_socket('localhost', $web_socket_port);
	}
}

?>
