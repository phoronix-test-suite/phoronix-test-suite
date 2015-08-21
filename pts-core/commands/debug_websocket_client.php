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

class debug_websocket_client implements pts_option_interface
{
	const doc_skip = true;
	const doc_section = 'Other';
	const doc_description = 'Phoronix Test Suite WebSocket client testing.';

	public static function run($r)
	{
		if(getenv('PTS_WEBSOCKET_ADDRESS') !== false && getenv('PTS_WEBSOCKET_PORT') !== false)
		{
			$web_socket_port = getenv('PTS_WEBSOCKET_PORT');
			$web_socket_address = getenv('PTS_WEBSOCKET_ADDRESS');
		}
		else
		{
			echo PHP_EOL . 'Must define PTS_WEBSOCKET_ADDRESS and PTS_WEBSOCKET_PORT' . PHP_EOL;
			return false;
		}

		pts_web_socket_client::$debug_mode = true;
		$ws_client = new pts_web_socket_client($web_socket_address, $web_socket_port);

		$ws_client->send('TEST 123');
		$ws_client->send('BIG TEST SENDDDDDDDDDDDDDDDDDDDDDDDDDDDDD MESSSSSSSSSSSSSSSSSSSSSSSSSAGE');
		$ws_client->send('1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE 1234678910 VERY LONG MESSAGE END END END 321');
		$ws_client->send('TEST MESSAGE');

		while(1)
		{
			$msg = $ws_client->receive();
			if($msg != null)
			{
				echo PHP_EOL . 'RECEIVED: ' . $ws_client->receive() . PHP_EOL;
			}

			//if(date('s') % 5 == 0)
			//{
			//	$ws_client->send(rand(1, getrandmax()));
			//}
		}
		$ws_client->disconnect();
	}
}

?>
