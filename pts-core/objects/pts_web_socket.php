<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2013 - 2016, Phoronix Media
	Copyright (C) 2013 - 2016, Michael Larabel
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

class pts_web_socket
{
	private $socket_master;
	private $sockets = array();
	private $users = array();
	private $callback_on_data_receive = false;
	private $callback_on_hand_shake = false;
	public static $debug_mode = true;
	public static $mask_send = false;

	public function __construct($address = 'localhost', $port = 80, $callback_on_data_receive = null, $callback_on_hand_shake = null)
	{
		pcntl_signal(SIGCHLD, SIG_IGN);
		ob_implicit_flush();
		if($address == 'localhost')
		{
			$this->socket_master = socket_create_listen($port);

			if($this->socket_master === false)
			{
				echo PHP_EOL . 'WebSocket create_listen failed:' . socket_last_error() . PHP_EOL . PHP_EOL;
				return false;
			}
			socket_set_option($this->socket_master, SOL_SOCKET, SO_REUSEADDR, 1);
		}
		else
		{
			$this->socket_master = socket_create(AF_INET, SOCK_STREAM, getprotobyname('tcp'));
			socket_set_option($this->socket_master, SOL_SOCKET, SO_REUSEADDR, 1);
			socket_bind($this->socket_master, $address, $port);
			socket_listen($this->socket_master, 5); // TODO XXX potentially set the 'backlog' parameter
		}

		$this->sockets[] = $this->socket_master;
		$this->callback_on_data_receive = $callback_on_data_receive;
		$this->callback_on_hand_shake = $callback_on_hand_shake;
		echo 'WebSocket Server Active: ' . $address . ':' . $port . PHP_EOL;

		while(true)
		{
			$changed = $this->sockets;
			$null = null;
			if(socket_select($changed, $null, $null, null) < 1)
				continue;
			//if($s == false) echo socket_strerror(socket_last_error());

			foreach($changed as $socket)
			{
				if($socket == $this->socket_master)
				{
					$connection = socket_accept($this->socket_master);
					if($connection !== false)
					{
						if(self::$debug_mode)
						{
							$this->debug_msg($connection, 'Connecting');
						}
						$this->connect($connection);
					}
				}
				else
				{
				/*	$bytes = 0;
					$buffer = null;

					while(($recv_bytes = socket_recv($socket, $recv_buffer, 1024, 0)) > 0)
					{ echo rand(1, 9);
						$bytes += $recv_bytes;
						$buffer .= $recv_buffer;
					} */
					$bytes = socket_recv($socket, $buffer, 2048, 0);

					if($bytes == 0)
					{
						if(self::$debug_mode)
						{
							$this->debug_msg($socket, 'Disconnecting');
						}
						$this->disconnect($socket);
					}
					else
					{
						$user = false;
						foreach($this->users as &$u)
						{
							if($u->socket == $socket)
							{
								$user = $u;
								break;
							}
						}
						if($user === false)
						{
							continue;
						}
						else if($user->handshake == false)
						{
							$hshake = $this->process_hand_shake($user, $buffer);
							if($hshake && $this->callback_on_hand_shake != false && is_callable($this->callback_on_hand_shake))
							{
								$ret = call_user_func($this->callback_on_hand_shake, $user);
							}
						}
						else
						{
							if(function_exists('pcntl_fork')) {
								$id = pcntl_fork();
								if ($id == -1) {
									echo 'forking error';
								} else if ($id) {
									// parent process
									continue;
								}

								// child process
								$this->process_data($user, $buffer);
								posix_kill(posix_getpid(), SIGINT);
							}
							else
							{
								$this->process_data($user, $buffer);
							}
						}
					}
				}
			}
		}
	}
	protected function debug_msg(&$socket, $msg)
	{
		echo PHP_EOL;
		if($socket && is_resource($socket))
		{
			$address = null;
			@socket_getpeername($socket, $address);
			echo $address . ': ';
		}
		echo $msg . PHP_EOL;
	}
	protected function decode_data(&$user, &$data)
	{
		$msg_opcode = bindec(substr(sprintf('%08b', ord($data[0])), 4, 4));
		$data_length = ord($data[1]) & 127;

		if(self::$debug_mode)
		{
		//	$this->debug_msg($socket, 'RECEIVED: ' . $data);
		}

		// TODO XXX: sometimes the opcode is 8 (close)... figure out why....
		if($data_length === 126)
		{
			$mask = substr($data, 4, 4);
			$encoded_data = substr($data, 8);
		}
		else if($data_length === 127)
		{
			$mask = substr($data, 10, 4);
			$encoded_data = substr($data, 14);
		}
		else
		{
			$mask = substr($data, 2, 4);
			$encoded_data = substr($data, 6, $data_length);
		}

		$decoded_data = null;
		if(false && $user->user_agent == 'phoronix-test-suite')
		{
			/// XXX TODO: This might not be needed anymore if PTS websocket client is properly masking
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

		if(self::$debug_mode)
		{
			$this->debug_msg($socket, 'RECEIVED DECODED: ' . $decoded_data);
		}

		return $decoded_data;
	}
	protected function process_data(&$user, &$msg)
	{
		$decoded_msg = $this->decode_data($user, $msg);
		// echo 'DECODED MESSAGE =' . PHP_EOL; var_dump($decoded_msg);
		if($this->callback_on_data_receive != false && is_callable($this->callback_on_data_receive))
		{
			$ret = call_user_func($this->callback_on_data_receive, $user, $decoded_msg);
		}
		else
		{
			// Just return the message to the user if no callback function is hooked up
			$this->send_data($user->socket, $decoded_msg);
		}
	}
	protected function send_json_data($socket, $json)
	{
		$data = json_encode($json, JSON_UNESCAPED_SLASHES);
		$this->send_data($socket, $data);
	}
	protected function send_data($socket, $data)
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

		// MESSAGE DATA
		if(self::$mask_send) // XXX ugly hack
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
		return socket_write($socket, $encoded, strlen($encoded));
	}
	public function send_json_data_by_user_id($user_id, $msg)
	{
		foreach($this->users as &$u)
		{
			if($u->id == $user_id)
			{
				$this->send_json_data($u->socket, $msg);
				break;
			}
		}
	}
	private function connect($socket)
	{
		$user = new pts_web_socket_user();
		$user->id = uniqid();
		$user->socket = $socket;
		$this->users[] = $user;
		$this->sockets[] = $socket;

		return $user;
	}
	protected function disconnect($socket)
	{
		$found_user = false;
		foreach($this->users as $i => &$user)
		{
			if($user->socket == $socket)
			{
				$found_user = $i;
				break;
			}
		}

		if($found_user !== false)
		{
			array_splice($this->users, $found_user, 1);
		}

		$index = array_search($socket, $this->sockets);
		if($index !== false)
		{
			array_splice($this->sockets, $index, 1);
		}

		socket_close($socket);
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

		return array($resource, $host, $origin, $key, $version, $user_agent);
	}
}

?>
