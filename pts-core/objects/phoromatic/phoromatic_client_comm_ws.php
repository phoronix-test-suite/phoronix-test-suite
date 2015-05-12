<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2015, Phoronix Media
	Copyright (C) 2015, Michael Larabel

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

class phoromatic_client_comm_ws implements phoromatic_client_comm_backend
{
	private $ws;
	private $receive_queue;

	public function __construct($ip, $port)
	{
		pts_web_socket_client::$debug_mode = true; // TODO XXX: disable this soon by default
		$this->ws = new pts_web_socket_client($ip, $port);
		$this->receive_queue = array();
	}
	public function disconnect()
	{
		$this->ws->disconnect();
		unset($this->ws);
	}
	public function send($arr)
	{
		return $this->ws->send(json_encode($arr, JSON_UNESCAPED_SLASHES));
	}
	public function receive_until($key)
	{
		if(isset($this->receive_queue[$key]))
		{
			$r = $this->receive_queue[$key];
			unset($this->receive_queue[$key]);
			return $r;
		}

		do
		{
			$response = $this->ws->receive();
			$decoded_json = json_decode($response, true);

			if($decoded_json != null)
			{
				$response = $decoded_json;
			}

			if(isset($response['phoromatic']['event']))
			{
				if($response['phoromatic']['event'] != $key)
					$this->receive_queue[$response['phoromatic']['event']] = $response;
				else
					return $response;
			}
		}
		while(1);
	}
}

?>
