<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2013 - 2015, Phoronix Media
	Copyright (C) 2013 - 2015, Michael Larabel
	pts-web-socket_server_phoromatic: Build upon pts_web_socket with functionality for the Phoromatic Server

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

class pts_web_socket_server_phoromatic extends pts_web_socket
{
	private $sensor_logging = false;
	private $phodevi_vfs = false;
	protected function add_to_status($current, &$json)
	{
		//$json['pts']['msg']['name'] = 'loading';
		$json['pts']['status']['current'] = $current;
		$json['pts']['status']['full'] = (!isset($json['pts']['status']['full']) ? null : $json['pts']['status']['full'] . PHP_EOL) . $json['pts']['status']['current'];
	}
	protected function process_hand_shake($user, $buffer)
	{
		$buffer_wrote = parent::process_hand_shake($user, $buffer);

		return $buffer_wrote;
		// TODO potentially don't need below code for this back-end XXX
		if($buffer_wrote > 0)
		{
			$resource = substr($user->res, strrpos($user->res, '/') + 1);

			switch(strstr($resource . ' ', ' ', true))
			{
				case 'start-user-session':
					$json = array();
					$json['pts']['msg']['name'] = 'user_session_start';
					$this->add_to_status('Starting Session', $json);
					$this->send_json_data($user->socket, $json);

					// Phodevi
					$this->add_to_status('Generating Phodevi Cache + VFS', $json);
					$this->send_json_data($user->socket, $json);
					phodevi::system_software(true);
					phodevi::system_hardware(true);
					$this->phodevi_vfs = new phodevi_vfs();
					$this->phodevi_vfs->list_cache_nodes();

					// Sensors
					$this->add_to_status('Starting Phodevi Sensor Handler', $json);
					$this->send_json_data($user->socket, $json);
					$this->sensor_logging = new phodevi_sensor_monitor(array('all'));
					$this->sensor_logging->sensor_logging_start();

					// Test Information
					$this->add_to_status('Downloading Test Information', $json);
					$this->send_json_data($user->socket, $json);
					pts_openbenchmarking::available_tests(true);

					// Complete
					$this->add_to_status('Session Startup Complete', $json);
					$this->send_json_data($user->socket, $json);
					//$this->disconnect($user->socket);
					break;
			}
			return true;
		}
	}
	protected function process_data(&$user, &$msg)
	{
		$decoded_msg = $this->decode_data($user, $msg);
		//echo 'DECODED: '; var_dump($decoded_msg);
		//echo 'DECODED MSG: ' . $decoded_msg . PHP_EOL;

		foreach(explode(' ;; ', $decoded_msg) as $single_msg)
		{
			$json = json_decode($single_msg, true);
			if($json && isset($json['phoromatic']['event']))
			{
				switch($json['phoromatic']['event'])
				{
					case 'ping':
						$j['phoromatic']['event'] = 'pong';
						$j['phoromatic']['time'] = time();
						$this->send_json_data($user->socket, $j);
						break;
					case 'pts-version':
						$j['phoromatic']['event'] = 'pts-version';
						$j['phoromatic']['response'] = pts_core::program_title(true);
						$this->send_json_data($user->socket, $j);
						break;
					case 'core-version':
						$version = PTS_CORE_VERSION;
						$this->send_data($user->socket, $version);
						break;
					case 'download-cache':
						$dc = json_decode(file_get_contents(phoromatic_server::find_download_cache()), true);
						$j['phoromatic']['event'] = 'download-cache';
						$j['phoromatic']['download-cache'] = $dc;
						$this->send_json_data($user->socket, $j);
						break;
					case 'check':
						$j['phoromatic']['event'] = 'check';
						$j['phoromatic']['task'] = 'quit-testing';
						$this->send_json_data($user->socket, $j);
						break;
				}
			}
		}
	}
}
