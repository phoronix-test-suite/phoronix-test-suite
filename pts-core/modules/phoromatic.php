<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2022, Phoronix Media
	Copyright (C) 2009 - 2022, Michael Larabel

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

class phoromatic extends pts_module_interface
{
	const module_name = 'Phoromatic Client';
	const module_version = '1.2.0';
	const module_description = 'The Phoromatic client is used for connecting to a Phoromatic server (Phoromatic.com or a locally run server) to facilitate the automatic running of tests, generally across multiple test nodes in a routine manner. For more details visit http://www.phoromatic.com/. This module is intended to be used with Phoronix Test Suite 5.2+ clients and servers.';
	const module_author = 'Phoronix Media';

	private static $account_id = null;
	private static $server_address = null;
	private static $server_http_port = null;
	private static $use_https = false;
	private static $server_ws_port = null;
	private static $is_running_as_phoromatic_node = false;
	private static $log_file = null;
	private static $limit_network_communication = false;
	private static $system_id = null;
	private static $server_core_version = 0;
	private static $progressive_result_uploads = false;

	private static $p_save_identifier = null;
	private static $p_schedule_id = null;
	private static $p_trigger_id = null;
	private static $benchmark_ticket_id = null;
	private static $in_stress_mode = false;
	private static $has_run_server_setup_func = false;

	private static $test_run_manager = null;

	public static function module_info()
	{
		return 'The Phoromatic module contains the client support for interacting with Phoromatic and Phoromatic Tracker services.';
	}
	public static function user_commands()
	{
		return array('connect' => 'run_connection', 'explore' => 'explore_network', 'upload_result' => 'upload_unscheduled_result', 'set_root_admin_password' => 'set_root_admin_password', 'list_results' => 'recent_phoromatic_server_results', 'clone' => 'clone_phoromatic_server_result', 'export_results_for_account_schedules' => 'generate_export_result_schedule_dump');
	}
	public static function upload_unscheduled_result($args)
	{
		$server_setup = self::setup_server_addressing($args);

		if(!$server_setup)
			return false;

		$uploads = 0;
		foreach($args as $arg)
		{
			if(pts_types::is_result_file($arg))
			{
				$uploads++;
				echo PHP_EOL . 'Uploading: ' . $arg . PHP_EOL;
				$result_file = pts_types::identifier_to_object($arg);
				$server_response = self::upload_test_result($result_file);
				$server_response = json_decode($server_response, true);
				if(isset($server_response['phoromatic']['response']))
					echo '   Result Uploaded' . PHP_EOL;
				else
					echo '   Upload Failed' . PHP_EOL;
			}
		}

		if($uploads == 0)
			echo PHP_EOL . 'No Result Files Found To Upload.' . PHP_EOL;
	}
	public static function set_root_admin_password()
	{
		phoromatic_server::prepare_database();
		$root_admin_pw = phoromatic_server::read_setting('root_admin_pw');

		// DISABLED THE INITIAL CHECKING CODE SINCE REALLY WAS SILLY SINCE IF THEY ALREADY HAVE TERMINAL ACCESS, THEY CAN ACCESS FILE ANYWAY....
		if(false && $root_admin_pw != null)
		{
			do
			{
				$check_root_pw = pts_user_io::prompt_user_input('Please enter the existing root-admin password');
			}
			while(hash('sha256', 'PTS' . $check_root_pw) != $root_admin_pw);
		}

		echo PHP_EOL . 'The new root-admin password must be at least six characters long.' . PHP_EOL;
		do
		{
			$new_root_pw = pts_user_io::prompt_user_input('Please enter the new root-admin password');
		}
		while(strlen($new_root_pw) < 6);

		$new_root_pw = hash('sha256', 'PTS' . $new_root_pw);
		$root_admin_pw = phoromatic_server::save_setting('root_admin_pw', $new_root_pw);
	}
	public static function generate_export_result_schedule_dump($r)
	{
		phoromatic_server::prepare_database();
		if(isset($r[0]) && !empty($r[0]))
		{
			phoromatic_server::generate_result_export_dump($r[0]);
		}
	}
	public static function phoromatic_server_supports_https($address, $port)
	{
		// Use 3 second timeout for HTTPS request to ensure not wasting too much time...
		$response = pts_network::http_get_contents('https://' . $address . ':' . $port . '/server.php?phoromatic_info', false, false, false, false, 3);

		if(!empty($response) && ($response = json_decode($response, true)) != null && isset($response['pts']))
		{
			// Successfully obtaining data via HTTPS
			return true;
		}
		return false;
	}
	public static function explore_network()
	{
		pts_client::$display->generic_heading('Phoromatic Servers');
		$archived_servers = pts_client::available_phoromatic_servers();

		$server_count = 0;
		foreach($archived_servers as $archived_server)
		{
			$supports_https = $archived_server['protocol'] == 'https'; // Previous code: self::phoromatic_server_supports_https($archived_server['ip'], $archived_server['http_port']);
			$response = pts_network::http_get_contents(($supports_https ? 'https://' : 'http://') . $archived_server['ip'] . ':' . $archived_server['http_port'] . '/server.php?phoromatic_info');

			if(!empty($response))
			{
				$response = json_decode($response, true);
				if($response && isset($response['pts']))
				{
					$server_count++;
					echo PHP_EOL . 'IP: ' . $archived_server['ip'] . PHP_EOL;
					echo 'HTTP PORT: ' . $archived_server['http_port'] . PHP_EOL;
					echo 'PROTOCOL: ' . ($supports_https ? 'HTTPS': 'HTTP') . PHP_EOL;
					echo 'WEBSOCKET PORT: ' . $response['ws_port'] . PHP_EOL;
					echo 'SERVER: ' . $response['http_server'] . PHP_EOL;
					echo 'PHORONIX TEST SUITE: ' . $response['pts'] . ' [' . $response['pts_core'] . ']' . PHP_EOL;

					// TODO XXX fix/finish below code...
					if(false && ($ws = new phoromatic_client_comm_ws($archived_server['ip'], $response['ws_port'])))
					{
						// Query the WebSocket Server for some Phoromatic Server details
						$s = $ws->send(array('phoromatic' => array('event' => 'pts-version')));
						$s = $ws->send(array('phoromatic' => array('event' => 'download-cache')));
						$r = $ws->receive_until('download-cache');
						var_dump($r);
					}
					else
					{

						// Provide some other server info via HTTP

						$repo = pts_network::http_get_contents(($supports_https ? 'https://' : 'http://') . $archived_server['ip'] . ':' . $archived_server['http_port'] . '/download-cache.php?repo');
						echo 'DOWNLOAD CACHE: ';
						if(!empty($repo))
						{
							$repo = json_decode($repo, true);
							if($repo && isset($repo['phoronix-test-suite']['download-cache']))
							{
								$total_file_size = 0;
								foreach($repo['phoronix-test-suite']['download-cache'] as $file_name => $inf)
								{
									$total_file_size += $repo['phoronix-test-suite']['download-cache'][$file_name]['file_size'];
								}
								echo count($repo['phoronix-test-suite']['download-cache']) . ' FILES / ' . round($total_file_size / 1000000) . ' MB CACHE SIZE';
							}
						}
						else
						{
							echo 'N/A';
						}
					}

					echo PHP_EOL;

					$repo = pts_network::http_get_contents(($supports_https ? 'https://' : 'http://') . $archived_server['ip'] . ':' . $archived_server['http_port'] . '/openbenchmarking-cache.php?repos');
					echo 'SUPPORTED OPENBENCHMARKING.ORG REPOSITORIES:' . PHP_EOL;
					if(!empty($repo))
					{
						$repo = json_decode($repo, true);
						if($repo && is_array($repo['repos']))
						{
							foreach($repo['repos'] as $data)
							{
								echo '      ' . $data['title'] . ' - Last Generated: ' . date('d M Y H:i', $data['generated']) . PHP_EOL;
							}
						}
					}
					else
					{
						echo '      N/A' . PHP_EOL;
					}
				}
			}
		}

		if($server_count == 0)
		{
			echo PHP_EOL . 'No Phoromatic Servers detected.' . PHP_EOL . PHP_EOL;
		}
	}
	protected static function tick_thread($force_manual_poll = false)
	{
		static $last_phoromatic_log = 0;

		while(true)
		{
			$j = array();

			$log_size = pts_client::$pts_logger->get_log_file_size();
			if($log_size != $last_phoromatic_log)
			{
				$phoromatic_log = file_get_contents(pts_client::$pts_logger->get_log_file_location());
				$last_phoromatic_log = $log_size;
				$j['phoromatic']['client-log'] = $phoromatic_log;
			}

			foreach(phodevi::query_sensors() as $sensor)
			{
				$j['phoromatic']['stats']['sensors'][phodevi::sensor_name($sensor)] = array('value' => phodevi::read_sensor($sensor), 'unit' => phodevi::read_sensor_unit($sensor));
			}

			$j['phoromatic']['stats']['uptime'] = ceil(phodevi::system_uptime() / 60);

			$server_response = phoromatic::upload_to_remote_server(array(
					'r' => 'tick',
					'j' => json_encode($j),
					));

			if($force_manual_poll)
			{
				return;
			}

			$server_response = json_decode($server_response, true);
			if($server_response && isset($server_response['phoromatic']['tick_thread']))
			{
				switch($server_response['phoromatic']['tick_thread'])
				{
					case 'reboot':
						phodevi::reboot();
						break;
					case 'halt-testing':
						touch(PTS_USER_PATH . 'halt-testing');
						break;
				}
			}

			// Randomize the thread work a little bit to ensure not hitting the systems at the same time
			sleep(rand(60, 90));
		}
	}
	protected static function upload_to_remote_server($to_post, $server_address = null, $server_http_port = null, $account_id = null, $try_https = false)
	{
		static $last_communication_minute = null;
		static $communication_attempts = 0;

		if($last_communication_minute == date('i') && $communication_attempts > 8)
		{
				// Something is wrong, Phoromatic shouldn't be communicating with server more than four times a minute
				return false;
		}
		else
		{
			if(date('i') != $last_communication_minute)
			{
				$last_communication_minute = date('i');
				$communication_attempts = 0;
			}

			$communication_attempts++;
		}

		if($server_address == null && self::$server_address != null)
		{
			$server_address = self::$server_address;
		}
		if($server_http_port == null && self::$server_http_port != null)
		{
			$server_http_port = self::$server_http_port;
		}
		if($account_id == null && self::$account_id != null)
		{
			$account_id = self::$account_id;
		}

		if($try_https)
		{
			$try_https = self::phoromatic_server_supports_https($server_address, $server_http_port);
		}

		$to_post['aid'] = $account_id;
		$to_post['pts'] = PTS_VERSION;
		$to_post['pts_core'] = PTS_CORE_VERSION;
		$to_post['gsid'] = defined('PTS_GSID') ? PTS_GSID : null;
		$to_post['lip'] = phodevi::read_property('network', 'ip');
		$to_post['h'] = phodevi::system_hardware(true);
		$to_post['nm'] = phodevi::read_property('network', 'mac-address');
		$to_post['nw'] = implode(', ', pts_network::get_network_wol());
		$to_post['s'] = phodevi::system_software(true);
		$to_post['n'] = phodevi::read_property('system', 'hostname');
		$to_post['pp'] = json_encode(phodevi::read_all_properties());
		$to_post['msi'] = PTS_MACHINE_SELF_ID;
		return pts_network::http_upload_via_post((self::$use_https || $try_https ? 'https://' : 'http://') . $server_address . ':' . $server_http_port .  '/phoromatic.php', $to_post, false);
	}
	protected static function update_system_status($current_task, $estimated_time_remaining = 0, $percent_complete = 0, $for_schedule = null, $estimate_to_next_comm = 0)
	{
		static $last_msg = null;

		// Avoid an endless flow of "idling" messages, etc
		if($current_task != $last_msg)
			pts_client::$pts_logger && pts_client::$pts_logger->log($current_task);
		$last_msg = $current_task;

		if(self::$limit_network_communication)
		{
			static $last_comm_time = 0;
			if(time() > ($last_comm_time + 800 + rand(0, 180)))
			{
				// It's been at least half hour since last update, so report in state...
				$last_comm_time = time();
			}
			else
			{
				return;
			}
		}

		return phoromatic::upload_to_remote_server(array(
				'r' => 'update_system_status',
				'a' => $current_task,
				'time' => $estimated_time_remaining,
				'pc' => $percent_complete,
				'sched' => (!empty($for_schedule) ? $for_schedule : self::$p_schedule_id),
				'bid' => (!empty(self::$benchmark_ticket_id) ? self::$benchmark_ticket_id : 0),
				'o' => $estimate_to_next_comm
				));
	}
	protected static function set_server_info($address, $port, $account_id)
	{
		self::$server_address = $address;
		self::$server_http_port = $port;
		self::$account_id = $account_id;
		self::$use_https = self::phoromatic_server_supports_https(self::$server_address, self::$server_http_port);
	}
	protected static function setup_server_addressing($server_string = null)
	{
		self::$has_run_server_setup_func = true;

		if(isset($server_string[0]) && strpos($server_string[0], '/', strpos($server_string[0], ':')) > 6)
		{
			pts_client::$pts_logger && pts_client::$pts_logger->log('Attempting to connect to Phoromatic Server: ' . $server_string[0]);
			self::set_server_info(substr($server_string[0], 0, strpos($server_string[0], ':')), substr($server_string[0], strlen(substr($server_string[0], 0, strpos($server_string[0], ':'))) + 1, -1 - strlen(substr($server_string[0], strrpos($server_string[0], '/') + 1))), substr($server_string[0], strrpos($server_string[0], '/') + 1));
			pts_client::$display->generic_heading('Server IP: ' . self::$server_address . PHP_EOL . 'Server HTTP Port: ' . self::$server_http_port . PHP_EOL . 'Account ID: ' . self::$account_id);
			pts_client::register_phoromatic_server(self::$server_address, self::$server_http_port);
		}
		else if(($last_server = trim(pts_module::read_file('last-phoromatic-server'))) && !empty($last_server))
		{
			pts_client::$pts_logger && pts_client::$pts_logger->log('Attempting to connect to last server connection: ' . $last_server);
			$last_account_id = substr($last_server, strrpos($last_server, '/') + 1);
			$last_server_address = substr($last_server, 0, strpos($last_server, ':'));
			$last_server_http_port = substr($last_server, strlen($last_server_address) + 1, -1 - strlen($last_account_id));
			pts_client::$pts_logger && pts_client::$pts_logger->log('Last Server IP: ' . $last_server_address . ' Last Server HTTP Port: ' . $last_server_http_port . ' Last Account ID: ' . $last_account_id);

			for($i = 0; $i < 10; $i++)
			{
				$server_response = phoromatic::upload_to_remote_server(array(
					'r' => 'ping',
					), $last_server_address, $last_server_http_port, $last_account_id, true);

				$server_response = json_decode($server_response, true);
				if($server_response && isset($server_response['phoromatic']['ping']))
				{
					self::set_server_info($last_server_address, $last_server_http_port, $last_account_id);
					pts_client::$pts_logger && pts_client::$pts_logger->log('Phoromatic Server connection restored');
					pts_client::register_phoromatic_server(self::$server_address, self::$server_http_port);
					break;
				}
				else
				{
					pts_client::$pts_logger && pts_client::$pts_logger->log('Phoromatic Server connection failed');
					sleep((12 * ($i + 1)));
				}
			}
		}

		if(self::$server_address == null)
		{
			$archived_servers = pts_client::available_phoromatic_servers();
			if(!empty($archived_servers))
			{
				pts_client::$pts_logger && pts_client::$pts_logger->log('Attempting to auto-discover Phoromatic Servers');
				self::attempt_phoromatic_server_auto_discover($archived_servers);
			}
		}

		if(self::$server_address == null || self::$server_http_port == null || self::$account_id == null)
		{
			pts_client::$pts_logger && pts_client::$pts_logger->log('Phoromatic Server connection setup failed');
			echo PHP_EOL . 'You must pass the Phoromatic Server information as an argument to phoromatic.connect, or otherwise configure your network setup.' . PHP_EOL . '      e.g. phoronix-test-suite phoromatic.connect 192.168.1.2:5555/I0SSJY' . PHP_EOL . PHP_EOL;

			if(PTS_IS_DAEMONIZED_SERVER_PROCESS && !empty($archived_servers))
			{
				echo 'The Phoromatic client appears to be running as a system service/daemon so will attempt to continue auto-polling to find the Phoromatic Server.' . PHP_EOL . PHP_EOL;

				$success = false;
				do
				{
					pts_client::$pts_logger && pts_client::$pts_logger->log('Will auto-poll connected servers every 90 seconds looking for a claim by a Phoromatic Server');
					sleep(90);
					$success = self::attempt_phoromatic_server_auto_discover($archived_servers);
				}
				while($success == false);
			}
			else
			{
				return false;
			}
		}

		return true;
	}
	protected static function attempt_phoromatic_server_auto_discover(&$phoromatic_servers)
	{
		foreach($phoromatic_servers as &$archived_server)
		{
			pts_client::$pts_logger && pts_client::$pts_logger->log('Attempting to auto-discover Phoromatic Server on: ' . $archived_server['ip'] . ': ' . $archived_server['http_port']);
			$server_response = phoromatic::upload_to_remote_server(array(
				'r' => 'ping',
				), $archived_server['ip'], $archived_server['http_port'], null, true);

			$server_response = json_decode($server_response, true);
			if($server_response && isset($server_response['phoromatic']['account_id']))
			{
				self::set_server_info($archived_server['ip'], $archived_server['http_port'], $server_response['phoromatic']['account_id']);
				return true;
			}
		}

		return false;
	}
	protected static function setup_system_environment()
	{
		if(is_writable('/boot/grub/grubenv') && pts_client::executable_in_path('grub-editenv'))
		{
			// In case system fails or reboots in process and don't want to hang on GRUB recordfail
			shell_exec('grub-editenv /boot/grub/grubenv unset recordfail 2>&1');
		}
	}
	public static function run_connection($args)
	{
		if(pts_client::create_lock(PTS_USER_PATH . 'phoromatic_lock') == false)
		{
			trigger_error('Phoromatic is already running.', E_USER_ERROR);
			return false;
		}
		define('PHOROMATIC_PROCESS', true);

		if(pts_client::$pts_logger == false)
		{
			pts_client::$pts_logger = new pts_logger(null, null, true, true);
		}
		pts_client::$pts_logger->log(pts_core::program_title() . ' [' . PTS_CORE_VERSION . '] starting Phoromatic client');

		if(phodevi::system_uptime() < 60)
		{
			echo 'PHOROMATIC: Sleeping for 60 seconds as system freshly started.' . PHP_EOL;
			pts_client::$pts_logger->log('Sleeping for 60 seconds as system freshly started');
			sleep(60);
		}

		$server_setup = self::setup_server_addressing($args);
		//$http_comm = new phoromatic_client_comm_http();

		/*
		if(!$server_setup)
		{
			if(getenv('PTS_NO_REBOOT_ON_NETWORK_FAILURE') == false && PTS_IS_DAEMONIZED_SERVER_PROCESS)
			{
				phodevi::reboot();
			}

			return false;
		}
		*/

		$times_failed = 0;
		$has_success = false;
		$do_exit = false;
		$just_started = true;

		self::setup_system_environment();
		pts_client::$pts_logger->log('SYSTEM HARDWARE: ' . phodevi::system_hardware(true));
		pts_client::$pts_logger->log('SYSTEM SOFTWARE: ' . phodevi::system_software(true));

		while($do_exit == false)
		{
			$server_response = phoromatic::upload_to_remote_server(array(
				'r' => 'start',
				));

			if($server_response == false)
			{
				$times_failed++;

				pts_client::$pts_logger->log('Server response failed');

				if($times_failed >= 2)
				{
					trigger_error('Communication with server failed.', E_USER_ERROR);

					if(PTS_IS_DAEMONIZED_SERVER_PROCESS == false && $times_failed > 5)
					{
						return false;
					}
					else if(PTS_IS_DAEMONIZED_SERVER_PROCESS && $times_failed > 10)
					{
						if(getenv('PTS_NO_REBOOT_ON_NETWORK_FAILURE') == false)
						{
							phodevi::reboot();
						}
					}
				}
			}
			else if(substr($server_response, 0, 1) == '[')
			{
				// Likely a notice/warning from server
				echo PHP_EOL . substr($server_response, 0, strpos($server_response, PHP_EOL)) . PHP_EOL;
			}
			else if(substr($server_response, 0, 1) == '{')
			{
				$times_failed = 0;
				$json = json_decode($server_response, true);

				if($has_success == false)
				{
					$has_success = true;
					pts_module::save_file('last-phoromatic-server', self::$server_address . ':' . self::$server_http_port . '/' . self::$account_id);
				}

				if($json != null)
				{
					if(isset($json['phoromatic']['error']) && !empty($json['phoromatic']['error']))
					{
						trigger_error($json['phoromatic']['error'], E_USER_ERROR);
					}
					if(isset($json['phoromatic']['response']) && !empty($json['phoromatic']['response']))
					{
						echo PHP_EOL . $json['phoromatic']['response'] . PHP_EOL;
					}
					if(isset($json['phoromatic']['system_id']) && !empty($json['phoromatic']['system_id']))
					{
						if(self::$system_id != $json['phoromatic']['system_id'])
						{
							// The Phoromatic server/account's system ID for this given system
							self::$system_id = $json['phoromatic']['system_id'];

							// Save the system ID to text file if it's useful for other purposes...
							pts_module::set_option('system_id', self::$system_id);
						}
					}
					if(isset($json['phoromatic']['server_core_version']) && !empty($json['phoromatic']['server_core_version']))
					{
						if(self::$server_core_version != $json['phoromatic']['server_core_version'])
						{
							// The PTS core version of the Phoromatic Server
							self::$server_core_version = $json['phoromatic']['server_core_version'];
						}
					}
				}

				self::$limit_network_communication = isset($json['phoromatic']['settings']['LimitNetworkCommunication']) && pts_strings::string_bool($json['phoromatic']['settings']['LimitNetworkCommunication']);
				if(self::$limit_network_communication)
				{
					// Sleep to ensure network communication is somewhat random in case all systems started at same time
					sleep(rand(0, 20));
				}

				if($just_started)
				{
					if(PTS_IS_DAEMONIZED_SERVER_PROCESS && !self::$limit_network_communication && function_exists('pcntl_fork'))
					{
						$pid = pcntl_fork();
						if($pid == 0)
						{
							// Start the tick thread
							self::tick_thread();
						}
					}

					self::upload_test_install_manifest();
					$just_started = false;
				}

				pts_tests::clear_extra_env_vars();
				if(isset($json['phoromatic']['pre_set_sys_env_vars']) && !empty($json['phoromatic']['pre_set_sys_env_vars']))
				{
					// pre_set_sys_env_vars was added during PTS 5.8 development
					// Sets environment variables on client as specified via the Phoromatic Server's systems page
					foreach(explode(';', $json['phoromatic']['pre_set_sys_env_vars']) as $i => $v_string)
					{
						$var = explode('=', $v_string);
						if(count($var) == 2)
						{
							putenv($var[0] . '=' . $var[1]);
							pts_tests::add_extra_env_var($var[0], $var[1]);
						}
					}
				}

				$task = isset($json['phoromatic']['task']) ? $json['phoromatic']['task'] : null;

				if($task != 'idle')
				{
					pts_client::$pts_logger->log("Received " . $task . " command");
				}

				switch($task)
				{
					case 'install':
						phoromatic::update_system_status('Installing Tests');
						$phoromatic_suite = new pts_test_suite($json['phoromatic']['test_suite']);
						pts_test_installer::standard_install($phoromatic_suite, false, true);
						break;
					case 'benchmark':

						// Make sure all latest tests are available
						pts_openbenchmarking::refresh_repository_lists(null, true);

						$benchmark_timer = time();
						self::$is_running_as_phoromatic_node = true;
						$phoromatic_suite = new pts_test_suite($json['phoromatic']['test_suite']);
						$phoromatic_results_identifier = $json['phoromatic']['trigger_id'];
						self::$p_save_identifier = $json['phoromatic']['save_identifier'];
						self::$p_schedule_id = isset($json['phoromatic']['schedule_id']) ? $json['phoromatic']['schedule_id'] : false;
						self::$p_trigger_id = $json['phoromatic']['trigger_id'];
						self::$benchmark_ticket_id = isset($json['phoromatic']['benchmark_ticket_id']) ? $json['phoromatic']['benchmark_ticket_id'] : null;
						phoromatic::update_system_status('Running Benchmarks For: ' . self::$p_save_identifier);
						// PTS 10.8 result viewer should deal fine with binary logs so can just upload by default there...
						pts_client::$skip_log_file_type_checks = self::$server_core_version >= 10800 || (isset($json['phoromatic']['settings']['AllowAnyDataForLogFiles']) && pts_strings::string_bool($json['phoromatic']['settings']['AllowAnyDataForLogFiles']));
						self::$progressive_result_uploads = isset($json['phoromatic']['settings']['ProgressiveResultUploads']) && pts_strings::string_bool($json['phoromatic']['settings']['ProgressiveResultUploads']);
						pts_module::set_option('skip_log_file_type_checks', (pts_client::$skip_log_file_type_checks ? 1 : 0));

						if(pts_strings::string_bool($json['phoromatic']['settings']['RunInstallCommand']))
						{
							if(isset($json['phoromatic']['pre_install_set_context']))
							{
								phoromatic::set_user_context($json['phoromatic']['pre_install_set_context'], self::$p_trigger_id, self::$p_schedule_id, 'PRE_INSTALL');
							}

							pts_test_installer::standard_install($phoromatic_suite, pts_strings::string_bool($json['phoromatic']['settings']['ForceInstallTests']), true);
							if(isset($json['phoromatic']['post_install_set_context']))
							{
								phoromatic::set_user_context($json['phoromatic']['post_install_set_context'], self::$p_trigger_id, self::$p_schedule_id, 'POST_INSTALL');
							}
						}
						$env_vars = isset($json['phoromatic']['environment_variables']) ? pts_strings::parse_value_string_vars($json['phoromatic']['environment_variables']) : array();
						$is_stress_run = isset($env_vars['PTS_CONCURRENT_TEST_RUNS']) && $env_vars['PTS_CONCURRENT_TEST_RUNS'] > 1;

						// Do the actual running
						phodevi::clear_cache();
						$original_env_var_overrides = pts_env::get_overrides();
						$original_pts_modules = pts_module_manager::attached_modules();

						if(isset($json['phoromatic']['settings']['GlobalEnvironmentVariables']) && !empty($json['phoromatic']['settings']['GlobalEnvironmentVariables']))
						{
							// The global environment variables set on the Phoromatic Settings page, rather than individual schedule/benchmark ticket specific env vars
							pts_env::set_array(pts_strings::parse_value_string_vars($json['phoromatic']['settings']['GlobalEnvironmentVariables']));
						}

						if(!empty($env_vars))
						{
							pts_env::set_array($env_vars);
						}

						if($is_stress_run)
						{
							self::$test_run_manager = new pts_stress_run_manager(array(
								'UploadResults' => false,
								'SaveResults' => false,
								'PromptForTestDescription' => false,
								'RunAllTestCombinations' => false,
								'PromptSaveName' => false,
								'PromptForTestIdentifier' => false,
								'OpenBrowser' => false
								), true);

							if(self::$test_run_manager->initial_checks($phoromatic_suite, 'SHORT'))
							{
								if(self::$test_run_manager->load_tests_to_run($phoromatic_suite))
								{
									self::$test_run_manager->action_on_stress_log_set(array('phoromatic', 'upload_stress_log_sane'));
									self::$in_stress_mode = self::$p_save_identifier;
									self::$test_run_manager->multi_test_stress_run_execute($env_vars['PTS_CONCURRENT_TEST_RUNS'], $env_vars['TOTAL_LOOP_TIME']);
									self::$in_stress_mode = false;
									self::upload_stress_log(self::$test_run_manager->get_stress_log());
								}
							}
							self::$benchmark_ticket_id = null;
							break;
						}
						else
						{
							self::$test_run_manager = new pts_test_run_manager(array(
							'UploadResults' => (isset($json['phoromatic']['settings']['UploadResultsToOpenBenchmarking']) && pts_strings::string_bool($json['phoromatic']['settings']['UploadResultsToOpenBenchmarking'])),
							'SaveResults' => true,
							'RunAllTestCombinations' => false,
							'OpenBrowser' => false
							), true);
						}

						if(self::$test_run_manager->initial_checks($phoromatic_suite, 'SHORT'))
						{
							// Load the tests to run
							if(self::$test_run_manager->load_tests_to_run($phoromatic_suite))
							{
								phoromatic::update_system_status('Tests In Run Queue: ' . implode(', ', self::$test_run_manager->get_tests_to_run_identifiers()));
								if(isset($json['phoromatic']['pre_run_set_context']))
								{
									phoromatic::set_user_context($json['phoromatic']['pre_run_set_context'], self::$p_trigger_id, self::$p_schedule_id, 'PRE_RUN');
								}

								if(isset($json['phoromatic']['settings']['UploadResultsToOpenBenchmarking']) && pts_strings::string_bool($json['phoromatic']['settings']['UploadResultsToOpenBenchmarking']))
								{
									self::$test_run_manager->auto_upload_to_openbenchmarking();
									pts_openbenchmarking_client::override_client_setting('UploadSystemLogsByDefault', pts_strings::string_bool($json['phoromatic']['settings']['UploadSystemLogs']));
								}

								// Save results?

								// Run the actual tests
								self::$test_run_manager->auto_save_results(self::$p_save_identifier, $phoromatic_results_identifier, (isset($json['phoromatic']['test_description']) ? $json['phoromatic']['test_description'] : 'A Phoromatic run.'));
								self::$test_run_manager->pre_execution_process();
								self::$test_run_manager->call_test_runs();

								phoromatic::update_system_status('Benchmarks Completed For: ' .  self::$p_save_identifier);
								self::$test_run_manager->post_execution_process();
								$elapsed_benchmark_time = time() - $benchmark_timer;

								// Handle uploading data to server
								$result_file = new pts_result_file(self::$test_run_manager->get_file_name());
								$upload_system_logs = pts_strings::string_bool($json['phoromatic']['settings']['UploadSystemLogs']);
								pts_module::set_option('upload_system_logs', ($upload_system_logs ? 1 : 0));
								$upload_install_logs = isset($json['phoromatic']['settings']['UploadInstallLogs']) && pts_strings::string_bool($json['phoromatic']['settings']['UploadInstallLogs']);
								pts_module::set_option('upload_install_logs', ($upload_install_logs ? 1 : 0));
								$upload_run_logs = isset($json['phoromatic']['settings']['UploadRunLogs']) && pts_strings::string_bool($json['phoromatic']['settings']['UploadRunLogs']);
								pts_module::set_option('upload_run_logs', ($upload_run_logs ? 1 : 0));
								$server_response = self::upload_test_result($result_file, $upload_system_logs, self::$p_schedule_id, self::$p_save_identifier, self::$p_trigger_id, $elapsed_benchmark_time, self::$benchmark_ticket_id);
								//pts_client::$pts_logger->log('DEBUG RESPONSE MESSAGE: ' . $server_response);
								if(!pts_strings::string_bool($json['phoromatic']['settings']['ArchiveResultsLocally']))
								{
									pts_results::remove_saved_result_file(self::$test_run_manager->get_file_name());
								}
							}

							if(isset($json['phoromatic']['post_run_set_context']))
							{
								phoromatic::set_user_context($json['phoromatic']['post_run_set_context'], self::$p_trigger_id, self::$p_schedule_id, 'POST_RUN');
							}
						}
						self::$p_schedule_id = null;
						self::$is_running_as_phoromatic_node = false;
						self::$benchmark_ticket_id = null;

						// Restore any environment variables that may have been set within process / overridden
						if(!empty($original_env_var_overrides))
						{
							pts_env::set_array($original_env_var_overrides, true);
							$original_env_var_overrides = null;
						}
						// Unload any modules that were loaded just during this benchmarking run (i.e. by a passed environment variable from Phoromatic)
						pts_module_manager::detach_extra_modules($original_pts_modules);
						break;
					case 'reboot':
						echo PHP_EOL . 'Phoromatic received a remote command to reboot.' . PHP_EOL;
						phoromatic::update_system_status('Attempting System Reboot');
						self::tick_thread(true);
						phodevi::reboot();
						break;
					case 'shutdown-if-supports-wake':
						$supports_wol = false;
						foreach(pts_network::get_network_wol() as $net_device)
						{
							if(strpos($net_device, 'g') !== false)
							{
								$supports_wol = true;
								break;
							}
						}
						if(!$supports_wol)
							break;
					case 'shutdown':
						if(isset($json['phoromatic']['client_update_script']) && !empty($json['phoromatic']['client_update_script']))
						{
							self::run_client_update_script($json['phoromatic']['client_update_script']);
							sleep(10);
						}

						echo PHP_EOL . 'Phoromatic received a remote command to shutdown.' . PHP_EOL;
						phoromatic::update_system_status('Attempting System Shutdown');
						self::tick_thread(true);
						phodevi::shutdown();
						break;
					case 'maintenance':
						echo PHP_EOL . 'Idling, system maintenance mode set by Phoromatic Server.' . PHP_EOL;
						phoromatic::update_system_status('Maintenance Mode' . self::check_for_separate_pts_thread_process());
						sleep(60);
						break;
					case 'idle':
						if(isset($json['phoromatic']['client_update_script']) && !empty($json['phoromatic']['client_update_script']))
						{
							self::run_client_update_script($json['phoromatic']['client_update_script']);
						}
						//echo PHP_EOL . 'Idling, waiting for task.' . PHP_EOL;
						phoromatic::update_system_status('Idling, Waiting For Task' . self::check_for_separate_pts_thread_process());
						break;
					case 'exit':
						echo PHP_EOL . 'Phoromatic received a remote command to exit.' . PHP_EOL;
						phoromatic::update_system_status('Exiting Phoromatic');
						self::tick_thread(true);
						$do_exit = true;
						break;
				}
			}

			if(!$do_exit)
			{
				if($server_response == false)
					sleep(rand(10, 30));
				else if(self::$limit_network_communication)
					sleep(rand(60, 240));
				else
					sleep(60);
			}
		}

		pts_client::release_lock(PTS_USER_PATH . 'phoromatic_lock');
	}
	private static function check_for_separate_pts_thread_process()
	{
		$report = null;
		$log_file = pts_logger::default_log_file_path() . 'phoronix-test-suite-benchmark.log';
		if(is_file($log_file) && filemtime($log_file) > (time() - 1200))
		{
			$log_file = pts_file_io::file_get_contents($log_file);
			$log_file = substr($log_file, strrpos($log_file, PHP_EOL) + 1);
			if(($x = strpos($log_file, ']')) !== false)
			{
				$log_file = substr($log_file, ($x + 1));
			}
			$report .= '; Separate Process: ' . trim($log_file);
		}

		return $report;
	}
	public static function __post_test_run()
	{
		// Progressively upload result file at end of each test execution
		if(self::$progressive_result_uploads)
		{
			self::upload_progressive_test_result();
		}
	}
	private static function upload_progressive_test_result()
	{
		if(!self::$progressive_result_uploads || !(self::$test_run_manager->result_file instanceof pts_result_file))
		{
			return false;
		}

		$composite_xml = self::$test_run_manager->result_file->get_xml();
		return phoromatic::upload_to_remote_server(array(
			'r' => 'result_upload',
			'sched' => self::$p_schedule_id,
			'bid' => self::$benchmark_ticket_id,
			'o' => self::$p_save_identifier,
			'ts' => self::$p_trigger_id,
			'composite_xml' => base64_encode($composite_xml),
			'composite_xml_hash' => sha1($composite_xml),
			'progressive_upload' => 1
			));
	}
	private static function upload_test_result(&$result_file, $upload_system_logs = true, $schedule_id = 0, $save_identifier = null, $trigger = null, $elapsed_time = 0, $benchmark_ticket_id = null)
	{
		$system_logs = null;
		$system_logs_hash = null;
		if(self::$server_core_version < 10602)
		{
			// On newer PTS servers, upload as separate upload afterwards to better handle large log files that otherwise may go beyond max request size, etc

			// TODO: Potentially integrate this code below shared with pts_openbenchmarking_client into a unified function for validating system log files
			$system_log_dir = $result_file->get_system_log_dir();
			if(is_dir($system_log_dir) && ($upload_system_logs || pts_module::read_option('upload_system_logs', 0) != 0))
			{
				$is_valid_log = true;
				if(pts_client::$skip_log_file_type_checks == false)
				{
					// For security/malicious purposes, ensure system log directory only contains text files
					$is_valid_log = pts_file_io::directory_only_contains_text_files($system_log_dir);
				}

				if($is_valid_log)
				{
					$system_logs_zip = pts_client::create_temporary_file('.zip');
					pts_compression::zip_archive_create($system_logs_zip, $system_log_dir);

					if(filesize($system_logs_zip) == 0)
					{
						pts_client::$pts_logger && pts_client::$pts_logger->log('System log ZIP file failed to generate. Missing PHP ZIP support?');
					}
					else if(pts_client::$skip_log_file_type_checks == false && filesize($system_logs_zip) > 2097152)
					{
						// If it's over 2MB, probably too big
						pts_client::$pts_logger && pts_client::$pts_logger->log('System log ZIP file too big to upload');
					}
					else
					{
						$system_logs = base64_encode(file_get_contents($system_logs_zip));
						$system_logs_hash = sha1($system_logs);
					}
					unlink($system_logs_zip);
				}
			}
		}

		$composite_xml = $result_file->get_xml();
		$composite_xml_hash = sha1($composite_xml);
		$composite_xml_type = 'composite_xml';

		// Compress the result file XML if it's big
		if(isset($composite_xml[50000]) && function_exists('gzdeflate'))
		{
			$composite_xml_gz = gzdeflate($composite_xml);
			if($composite_xml_gz != false)
			{
				$composite_xml = $composite_xml_gz;
				$composite_xml_type = 'composite_xml_gz';
			}
		}

		// Upload to Phoromatic
		$times_tried = 0;
		do
		{
			if($times_tried > 0)
			{
				sleep(rand(5, 20));
			}

			$res = phoromatic::upload_to_remote_server(array(
				'r' => 'result_upload',
				//'ob' => $ob_data['id'],
				'sched' => $schedule_id,
				'bid' => $benchmark_ticket_id,
				'o' => $save_identifier,
				'ts' => $trigger,
				'et' => $elapsed_time,
				$composite_xml_type => base64_encode($composite_xml),
				'composite_xml_hash' => $composite_xml_hash,
				'system_logs_zip' => $system_logs,
				'system_logs_hash' => $system_logs_hash,
				'progressive_upload' => (self::$progressive_result_uploads ? 2 : 0)
				));

			$times_tried++;
		}
		while($res == false && $times_tried < 4);

		$server_response = json_decode($res, true);

		if(isset($server_response['phoromatic']['upload_id']) && !empty($server_response['phoromatic']['upload_id']))
		{
			// On newer PTS servers, upload as separate upload afterwards to better handle large log files that otherwise may go beyond max request size, etc

			$log_types = array();

			if($upload_system_logs)
			{
				$log_types['system-logs'] = $result_file->get_system_log_dir();
			}

			if(pts_module::read_option('upload_install_logs', 0) != 0)
			{
				$log_types['installation-logs'] = $result_file->get_test_installation_log_dir();
			}
			if(pts_module::read_option('upload_run_logs', 0) != 0)
			{
				$log_types['test-logs'] = $result_file->get_test_log_dir();
			}

			foreach($log_types as $index => $log_dir)
			{
				if(is_dir($log_dir))
				{
					$is_valid_log = true;
					if(pts_client::$skip_log_file_type_checks == false)
					{
						// For security/malicious purposes, ensure system log directory only contains text files
						$is_valid_log = pts_file_io::directory_only_contains_text_files($log_dir);
					}

					if($is_valid_log)
					{
						$system_logs_zip = pts_client::create_temporary_file('.zip');
						pts_compression::zip_archive_create($system_logs_zip, $log_dir);

						if(filesize($system_logs_zip) == 0)
						{
							pts_client::$pts_logger && pts_client::$pts_logger->log($index . ' log ZIP file failed to generate. Missing PHP ZIP support?');
						}
						else
						{
							$system_logs = base64_encode(file_get_contents($system_logs_zip));
							$system_logs_hash = sha1($system_logs);
						}
						unlink($system_logs_zip);

						phoromatic::upload_to_remote_server(array(
							'r' => 'result_log_upload',
							'i' => $server_response['phoromatic']['upload_id'],
							'system_logs_type' => $index,
							'system_logs_zip' => $system_logs,
							'system_logs_hash' => $system_logs_hash
							));
					}
				}
			}
		}

		return $res;
	}
	private static function upload_stress_log($stress_log)
	{
		// Upload Logs to Phoromatic
		if($stress_log == null || self::$benchmark_ticket_id == null)
		{
			return;
		}

		$times_tried = 0;
		do
		{
			if($times_tried > 0)
			{
				sleep(rand(5, 20));
			}

			$res = phoromatic::upload_to_remote_server(array(
				'r' => 'stress_log_upload',
				'bid' => self::$benchmark_ticket_id,
				'l' => pts_user_io::strip_ansi_escape_sequences($stress_log)
				));

			$times_tried++;
		}
		while($res == false && $times_tried < 4);

		return $res;
	}
	public static function upload_stress_log_sane($stress_log)
	{
		static $last_log_upload = 0;

		if(time() > ($last_log_upload + 60))
		{
			self::upload_stress_log($stress_log);
			$last_log_upload = time();
		}
	}
	public static function recent_phoromatic_server_results()
	{
		self::setup_server_addressing();
		$server_response = phoromatic::upload_to_remote_server(array('r' => 'list_results'), null, null, null, true);
		$server_response = json_decode($server_response, true);

		if(isset($server_response['phoromatic']['results']) && !empty($server_response['phoromatic']['results']))
		{
			foreach($server_response['phoromatic']['results'] as $pprid => $result)
			{
				echo pts_client::cli_just_bold($result['Title']) . ' ' . $pprid . ' ' . date('j M H:i', strtotime($result['UploadTime'])) . PHP_EOL;
				echo '   ' . $result['SystemName'] . ' ' . $result['GroupName'];
				echo PHP_EOL . PHP_EOL;
			}
		}
		else
			echo PHP_EOL . 'No Phoromatic Server results discovered.';

		echo PHP_EOL;
	}
	public static function clone_phoromatic_server_result($args)
	{
		self::setup_server_addressing();

		$id = $args[0];
		$server_response = phoromatic::upload_to_remote_server(array('r' => 'clone_result', 'i' => $id), null, null, null, true);
		$server_response = json_decode($server_response, true);

		if(isset($server_response['phoromatic']['result']['composite_xml']) && !empty($server_response['phoromatic']['result']['composite_xml']))
		{
			$composite_xml = base64_decode($server_response['phoromatic']['result']['composite_xml']);
			$result_file = new pts_result_file($composite_xml);
			// TODO XXX: Add system log downloading support
			pts_client::save_test_result($id . '/composite.xml', $result_file->get_xml(), true);
			echo PHP_EOL . 'Result File Saved As: ' . $id . PHP_EOL . PHP_EOL;
		}
		else
			echo PHP_EOL . 'No Phoromatic result found.' . PHP_EOL;
	}
	private static function run_client_update_script($update_script)
	{
		static $last_update_script_check_time = 0;

		// Don't keep checking it so check no more than every 30 minutes
		if($last_update_script_check_time < (time() - (60 * 30)) && !empty($update_script))
		{
			$last_update_script_check_time = time();
			$update_file = pts_client::create_temporary_file();
			$update_script = str_replace("\r", PHP_EOL, $update_script);
			file_put_contents($update_file, $update_script);
			phoromatic::update_system_status('Running Phoromatic Update Script');
			$env_vars = array();

			$append_cmd = '';
			if(phodevi::is_windows() && is_executable('C:\cygwin64\bin\bash.exe') && strpos($update_script, '#!/bin') !== false)
			{
				$cmd = 'C:\cygwin64\bin\bash.exe';
			}
			else if(phodevi::is_windows() && is_executable('C:\Windows\System32\cmd.exe'))
			{
				$cmd = 'C:\Windows\System32\cmd.exe /c';
			}
			else if(pts_client::executable_in_path('bash'))
			{
				$cmd = 'bash';
				$append_cmd = ' 2>&1';
			}
			else
			{
				$cmd = 'sh';
				$append_cmd = ' 2>&1';
			}

			pts_client::$pts_logger && pts_client::$pts_logger->log('Running Update Script: ' . $cmd . ' ' . $update_file . $append_cmd);
			$update_output = pts_client::shell_exec($cmd . ' ' . $update_file . $append_cmd, $env_vars);
			if(trim($update_output) != '')
			{
				pts_client::$pts_logger && pts_client::$pts_logger->log('Update Script Output: ' . trim($update_output));
			}
			unlink($update_file);
			phoromatic::update_system_status('Phoromatic Update Script Completed');
			self::tick_thread(true);
		}
	}
	private static function set_user_context($context_script, $trigger, $schedule_id, $process)
	{
		if(!empty($context_script))
		{
			$context_file = pts_client::create_temporary_file();
			file_put_contents($context_file, $context_script);
			chmod($context_file, 0755);

			pts_file_io::mkdir(pts_module::save_dir());
			$storage_path = pts_module::save_dir() . 'memory.pt2so';
			$storage_object = pts_storage_object::recover_from_file($storage_path);
			$notes_log_file = pts_module::save_dir() . sha1($trigger . $schedule_id . $process);

			// We check to see if the context was already set but the system rebooted or something in that script
			if($storage_object == false)
			{
				$storage_object = new pts_storage_object(true, true);
			}
			else if($storage_object->read_object('last_set_context_trigger') == $trigger && $storage_object->read_object('last_set_context_schedule') == $schedule_id && $storage_object->read_object('last_set_context_process') == $process)
			{
				// If the script already ran once for this trigger, don't run it again
				self::check_user_context_log($trigger, $schedule_id, $process, $notes_log_file, null);
				return false;
			}

			$storage_object->add_object('last_set_context_trigger', $trigger);
			$storage_object->add_object('last_set_context_schedule', $schedule_id);
			$storage_object->add_object('last_set_context_process', $process);
			$storage_object->save_to_file($storage_path);

			phoromatic::update_system_status('Setting context for: ' . $schedule_id . ' - ' . $trigger . ' - ' . $process);

			// Run the set context script
			$env_vars['PHOROMATIC_TRIGGER'] = $trigger;
			$env_vars['PHOROMATIC_SCHEDULE_ID'] = $schedule_id;
			$env_vars['PHOROMATIC_SCHEDULE_PROCESS'] = $process;
			$env_vars['PHOROMATIC_LOG_FILE'] = $notes_log_file;
			$log_output = pts_client::shell_exec($context_file . ' ' . $trigger . ' 2>&1', $env_vars);
			self::check_user_context_log($trigger, $schedule_id, $process, $notes_log_file, $log_output);

			// Just simply return true for now, perhaps check exit code status and do something
			return true;
		}

		return false;
	}
	private static function check_user_context_log($trigger, $schedule_id, $process, $log_file, $log_output)
	{
		if(is_file($log_file) && ($lf_conts = pts_file_io::file_get_contents($log_file)) != null)
		{
			$context_log = $lf_conts;
			unlink($log_file);
		}
		else
		{
			$context_log = trim($log_output);
		}

		if($context_log != null)
		{
			$server_response = phoromatic::upload_to_remote_server(array(
				'r' => 'user_context_log',
				'sched' => $schedule_id,
				'ts' => $trigger,
				'i' => $process,
				'o' => $context_log,
				));
		}
	}
	protected static function upload_test_install_manifest()
	{
		$json_to_upload = array();
		foreach(pts_tests::tests_installations_with_metadata() as $test_profile)
		{
			if($test_profile->test_installation)
			{
				$json_to_upload[$test_profile->get_identifier()] = $test_profile->test_installation->get_array();
			}
		}

		if(!empty($json_to_upload))
		{
			$server_response = phoromatic::upload_to_remote_server(array(
				'r' => 'test_install_manifest',
				'manifest' => json_encode($json_to_upload)
				));
		}
	}
	public static function __post_install_process()
	{
		self::upload_test_install_manifest();
	}
	public static function __post_run_process()
	{
		self::upload_test_install_manifest();
	}
	public static function __pre_test_install(&$test_install_request)
	{
	/*	if(self::$has_run_server_setup_func == false)
		{
			self::setup_server_addressing();
		}
	*/
	// XXX finish wiring in the above code to various parts for making auto-reporting from clients

		if(!self::$is_running_as_phoromatic_node)
		{
			return false;
		}

		static $last_update_time = 0;

		if(time() > ($last_update_time + 30))
		{
			phoromatic::update_system_status('Installing: ' . $test_install_request->test_profile->get_identifier());
			$last_update_time = time();
		}
	}
	public static function __pre_test_run($pts_test_result)
	{
		if(!self::$is_running_as_phoromatic_node)
		{
			return false;
		}

		if(self::$in_stress_mode)
		{
			static $time_in_stress_run = 0;

			$msg = 'Stress-Run Testing';
			if(($time_in_stress_run + (60 * 60)) > time())
			{
				// Don't report this same string so often...
				return;
			}
			$time_in_stress_run = time();
		}
		else
		{
			$msg = 'Running: ' . $pts_test_result->test_profile->get_identifier() . ($pts_test_result->get_arguments_description() != null ? ' [' . $pts_test_result->get_arguments_description() . ']' : null);
		}

		phoromatic::update_system_status($msg,
			ceil(self::$test_run_manager->get_estimated_run_time() / 60),
			self::$test_run_manager->get_percent_complete(),
			null,
			ceil($pts_test_result->get_estimated_run_time() / 60));
	}
	public static function __event_results_saved($test_run_manager)
	{
		/*if(pts_env::read('AUTO_UPLOAD_RESULTS_TO_PHOROMATIC') && pts_module::is_module_setup())
		{
			phoromatic::upload_unscheduled_test_results($test_run_manager->get_file_name());
		}*/
	}
	public static function __event_pre_run_error($error_obj)
	{
		list($test_profile, $error_msg) = $error_obj;

		$server_response = phoromatic::upload_to_remote_server(array(
			'r' => 'error_report',
			'sched' => self::$p_schedule_id,
			'ts' => self::$p_trigger_id,
			'err' => $error_msg,
			'ti' => $test_profile->get_identifier(),
			'o' => ''
			));
	}
	public static function __event_run_error($error_obj)
	{
		list($test_run_manager, $test_run_request, $error_msg) = $error_obj;

		if(stripos('Download Failed', $error_msg) !== false || stripos('check-sum of the downloaded file failed', $error_msg) !== false || stripos('attempting', $error_msg) !== false)
			return false;

		$server_response = phoromatic::upload_to_remote_server(array(
			'r' => 'error_report',
			'sched' => self::$p_schedule_id,
			'ts' => self::$p_trigger_id,
			'err' => $error_msg,
			'ti' => $test_run_request->test_profile->get_identifier(),
			'o' => $test_run_request->get_arguments_description()
			));
		//pts_client::$pts_logger && pts_client::$pts_logger->log('TEMP DEBUG MESSAGE: ' . $server_response);
	}
	public static function __event_reboot($passed_obj)
	{
		$reboot_msg = 'Attemtping system reboot';
		if($passed_obj != null)
		{
			if($passed_obj instanceof pts_test_result)
			{
				$reboot_msg = 'Attempting system reboot, requested by ' . $passed_obj->test_profile->get_identifier();
			}
			else if($passed_obj instanceof pts_test_profile)
			{
				$reboot_msg = 'Attempting system reboot, requested by ' . $passed_obj->get_identifier();
			}
		}
		phoromatic::update_system_status($reboot_msg);
	}
}

?>
