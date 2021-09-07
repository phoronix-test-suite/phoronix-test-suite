<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2013 - 2015, Phoronix Media
	Copyright (C) 2013 - 2015, Michael Larabel
	pts-web-socket: A simple WebSocket implementation, inspired by designs of https://github.com/varspool/Wrench and http://code.google.com/p/phpwebsocket/

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

// TODO XXX: Something is still not reliable in pts_web_socket_client or pts_web_socket with sometimes junk being passed

class pts_web_socket_client
{
	private $socket_master;
	private $user_master;
	public static $debug_mode = true;

	public function __construct($address = 'localhost', $port = 80)
	{
		ob_implicit_flush();
		$this->socket_master = socket_create(AF_INET, SOCK_STREAM, getprotobyname('tcp'));
		//socket_set_option($this->socket_master, SOL_SOCKET, SO_REUSEADDR, 1);
		//socket_bind($this->socket_master, $address, $port);
		//echo   socket_strerror(socket_last_error());
		//socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 2, 'usec' => 0));
		$sc = socket_connect($this->socket_master, $address, $port);

		//	socket_listen($this->socket_master);
		//echo   socket_strerror(socket_last_error());
		$this->make_hand_shake($this->socket_master);
		$this->connect($this->socket_master);
		//echo 'WebSocket Client Connected: ' . $address . ':' . $port . PHP_EOL;
		$p = $this->send('ping');
		return $sc && $p != null;
	}
	public function send($msg)
	{
		return $this->send_data($this->socket_master, $msg);
	}
	public function receive()
	{
		$buffer = null;
		$bytes = socket_recv($this->socket_master, $buffer, 8192, 0);

		if($bytes === false)
			return; // error

		if($bytes > 0)
		{
			return $this->decode_data($this->user_master, $buffer);
		}
		else if($bytes === false)
		{
			echo PHP_EOL . socket_strerror(socket_last_error()) . PHP_EOL;
		}
		else
		{
			// NO DATA RECEIVED
			// $this->disconnect($this->socket_master);
		}

	}
	protected function debug_msg(&$socket, $msg)
	{
		echo PHP_EOL;
		if($socket && is_resource($socket))
		{
			$address = null;
			socket_getpeername($socket, $address);
			echo $address . ': ';
		}
		echo $msg . PHP_EOL;
	}
	protected function decode_data(&$user, &$data)
	{
		$msg_opcode = bindec(substr(sprintf('%08b', ord($data[0])), 4, 4));
		$data_length = ord($data[1]) & 127;

		// TODO XXX: sometimes the opcode is 8 (close)... figure out why....
		if($data_length === 126)
		{
			$mask = substr($data, 4, 4);
			$encoded_data = substr($data, 4);
		}
		else if($data_length === 127)
		{
			$mask = substr($data, 10, 4);
			$encoded_data = substr($data, 4);
		}
		else
		{
			$mask = substr($data, 2, 4);
			$encoded_data = substr($data, 6, $data_length);
		}

		$decoded_data = null;
		if(false &&  $user->user_agent == 'phoronix-test-suite')
		{
			// The PTS WebSocket client isn't currently masking data due to bug it seems
			$decoded_data .= $encoded_data;
		}
		else
		{
			for($i = 0; $i < strlen($encoded_data); $i++)
			{
				$decoded_data .= $encoded_data[$i] ^ $mask[($i % 4)];
			}
		}

		return $decoded_data;
	}
	protected function send_json_data($socket, $json)
	{
		$data = json_encode($json, JSON_UNESCAPED_SLASHES);
		$this->send_data($socket, $data);
	}
	protected function send_data($socket, $data, $masked = true)
	{
		if(self::$debug_mode)
		{
			$this->debug_msg($socket, 'Sending: ' . $data);
		}

		$data_length = strlen($data);
		$encoded = null;

		// FRAME THE MESSAGE
		$encoded .= chr(0x81);
		if($data_length <= 125)
		{
			$encoded .= chr($data_length);
		}
		else if($data_length <= 65535)
		{
			$encoded .= chr(126) . chr($data_length >> 8) . chr($data_length & 0xFF);
		}
		else
		{
			$encoded .= chr(127) . pack('N', 0) . pack('N', $data_length);
		}

		// XXX:
		if($masked)
		{
			$mask = null;
			for($i = 0; $i < 4; $i++)
			{
				$mask .= chr(rand(0, 255));
			}
			$encoded .= $mask;

			// MESSAGE DATA
			for($i = 0; $i < strlen($data); $i++)
			{
				$encoded .= $data[$i] ^ $mask[$i % 4];
			}
		}
		else
		{
			$encoded .= $data;
		}

		// SEND
		$t = socket_write($socket, $encoded, strlen($encoded));
		usleep(100000); // XXX without this, doing lots of send() at once tends to result in only the first one getting through
		return $t;
	}
	public function send_json_data_by_user_id($user_id, $msg)
	{
		// XXX: dead code, this function likely not used at all...
		/*
		foreach($this->users as &$u)
		{
			if($u->id == $user_id)
			{
				$this->send_json_data($u->socket, $msg);
				break;
			}
		}
		*/
	}
	private function connect($socket)
	{
		$user = new pts_web_socket_user();
		$user->id = uniqid();
		$user->socket = $socket;
		return $user;
	}
	public function disconnect()
	{
		socket_close($this->socket_master);
	}
	protected function make_hand_shake(&$socket, $get = 'phoronix-test-suite')
	{
		$this->send_data($socket, 'GET /' . $get . ' HTTP/1.1
			Host: localhost
			Upgrade: websocket
			Connection: Upgrade
			Sec-WebSocket-Key: x3JJHMbDL1EzLkh9GBhXDw==
			Sec-WebSocket-Protocol: phoronixtestsuite
			Sec-WebSocket-Version: 13
			User-Agent: phoronix-test-suite
			Date: ' . date('D, d M Y H:i:s e'));

		$bytes = socket_recv($socket, $buffer, 2048, 0);
		$user = $this->connect($socket);
		return $this->process_hand_shake($user, $buffer);
	}
	protected function process_hand_shake($user, $buffer)
	{
		//echo 'HANDSHAKE = ' . PHP_EOL; var_dump($buffer);
		list($resource, $host, $origin, $key, $version, $user_agent) = $this->extract_headers($buffer);

		$protocol_handshake = array(
			'HTTP/1.1 101 WebSocket Protocol Handshake',
			'Date: ' . date('D, d M Y H:i:s e'),
			'Connection: Upgrade',
			'Upgrade: WebSocket',
			'Sec-WebSocket-Origin: ' . $origin,
			'Access-Control-Allow-Origin: ' . $origin,
			'Access-Control-Allow-Credentials: true',
			'Sec-WebSocket-Location: ws://' . $host . $resource,
		//	'Sec-WebSocket-Version: ' . $version,
		//	'Sec-WebSocket-Protocol: phoronixtestsuite',
			'Server: phoronix-test-suite',
			'Sec-WebSocket-Accept: ' . base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')))
			);

		$protocol_handshake = implode("\r\n", $protocol_handshake) . "\r\n" . "\r\n";
		//echo 'HANDSHAKE RESPONSE = ' . PHP_EOL; var_dump($protocol_handshake);

		$wrote = socket_write($user->socket, $protocol_handshake, strlen($protocol_handshake));
		$user->handshake = true;
		$user->res = $resource;
		$user->user_agent = $user_agent;
		return $wrote;
	}
	private function extract_headers($request)
	{
		$resource = strstr(substr(strstr($request, 'GET '), 4), ' HTTP/1.1', true);
		$host = trim(strstr(substr(strstr($request, 'Host: '), 6), PHP_EOL, true));
		$origin = trim(strstr(substr(strstr($request, 'Origin: '), 8), PHP_EOL, true));
		$key = trim(strstr(substr(strstr($request, 'Sec-WebSocket-Key: '), 19), PHP_EOL, true));
		$version = trim(strstr(substr(strstr($request, 'Sec-WebSocket-Version: '), 23), PHP_EOL, true));
		$user_agent = trim(strstr(substr(strstr($request, 'User-Agent: '), 12), PHP_EOL, true));
		if($user_agent == null)
		{
			$user_agent = trim(strstr(substr(strstr($request, 'Server: '), 8), PHP_EOL, true));
		}

		return array($resource, $host, $origin, $key, $version, $user_agent);
	}
}

?>
