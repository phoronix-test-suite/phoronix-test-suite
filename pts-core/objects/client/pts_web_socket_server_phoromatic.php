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
		//echo 'DECODED MSG: ' . $decoded_msg . PHP_EOL;

		foreach(explode(' ;; ', $decoded_msg) as $single_msg)
		{
			//echo strstr($single_msg . ' ', ' ', true) . PHP_EOL;
			$args = trim(strstr($single_msg, ' '));
			switch(strstr($single_msg . ' ', ' ', true))
			{
				case 'search':
					$this->search_pts($user, $args);
				case 'result_file':
					foreach(explode(',', $args) as $result)
					{
						if($result == null)
						{
							continue;
						}

						$result_file = new pts_result_file($result);
						$json['pts']['msg']['name'] = 'result_file';
						$json['pts']['msg']['result'] = $result;
						$json['pts']['msg']['result_file'] = base64_encode($result_file->to_json());
						$this->send_json_data($user->socket, $json);
					}
					break;
				case 'results_by_date':
					$results = pts_tests::test_results_by_date();
					$json['pts']['msg']['name'] = 'results_by_date';
					$json['pts']['msg']['result_count'] = count($results);
					$json['pts']['msg']['results'] = base64_encode(json_encode($results));
					$this->send_json_data($user->socket, $json);
					break;
				case 'results_grouped_by_date':
					$results = pts_tests::test_results_by_date();
					$json['pts']['msg']['name'] = 'results_grouped_by_date';
					$json['pts']['msg']['result_count'] = count($results);
					$sections = array(
						mktime(date('H'), date('i') - 10, 0, date('n'), date('j')) => 'Just Now',
						mktime(0, 0, 0, date('n'), date('j')) => 'Today',
						mktime(0, 0, 0, date('n'), date('j') - date('N') + 1) => 'This Week',
						mktime(0, 0, 0, date('n'), 1) => 'This Month',
						mktime(0, 0, 0, date('n') - 1, 1) => 'Last Month',
						mktime(0, 0, 0, 1, 1) => 'This Year',
						mktime(0, 0, 0, 1, 1, date('Y') - 1) => 'Last Year',
						);

					$section = current($sections);
					foreach($results as $result_time => &$result)
					{
						if($result_time < key($sections))
						{
							while($result_time < key($sections) && $section !== false)
							{
								$section = next($sections);
							}

							if($section === false)
							{
								break;
							}
						}

						if(!isset($json['pts']['msg']['results'][current($sections)]))
						{
							$json['pts']['msg']['results'][current($sections)] = array();
						}

						if($result != null)
						{
							array_push($json['pts']['msg']['results'][current($sections)], $result);
						}
					}
					$this->send_json_data($user->socket, $json);
					break;
				case 'user-svg-system-graphs':
				//	pts_client::timed_function(array($this, 'generate_system_svg_graphs'), array($user), 1, array($this, 'sensor_logging_continue'), array($user));
				//	$this->generate_system_svg_graphs($user, $args);
					break;
				case 'user-large-svg-system-graphs':
				//	pts_client::timed_function(array($this, 'generate_system_svg_graphs'), array($user), 1, array($this, 'sensor_logging_continue'), array($user));
				//	$this->generate_large_system_svg_graphs($user, $args);
					break;
				case 'tests-by-popularity':
					$args = explode(' ', $args);
					$limit = isset($args[0]) && is_numeric($args[0]) ? $args[0] : 10;
					$test_type = isset($args[1]) && $args[1] != null ? $args[1] : null;
					$tests = pts_openbenchmarking_client::popular_tests($limit, $test_type);
					$json['pts']['msg']['name'] = 'tests_by_popularity';
					$json['pts']['msg']['test_count'] = count($tests);
					$json['pts']['msg']['test_type'] = $test_type;
					$json['pts']['msg']['tests'] = array();
					$json['pts']['msg']['test_profiles'] = array();

					foreach($tests as $test)
					{
						array_push($json['pts']['msg']['tests'], $test);
						$tp = new pts_test_profile($test);
						array_push($json['pts']['msg']['test_profiles'], base64_encode($tp->to_json()));
					}
					$this->send_json_data($user->socket, $json);
					break;
				case 'available-system-logs':
					if($this->phodevi_vfs instanceof phodevi_vfs)
					{
						$json['pts']['msg']['name'] = 'available_system_logs';
						$json['pts']['msg']['logs'] = $this->phodevi_vfs->list_cache_nodes($args);
						$this->send_json_data($user->socket, $json);
					}
					break;
				case 'fetch-system-log':
					if($this->phodevi_vfs instanceof phodevi_vfs && $args != null && $this->phodevi_vfs->cache_isset_names($args))
					{
						$json['pts']['msg']['name'] = 'fetch_system_log';
						$json['pts']['msg']['log_name'] = $args;
						$json['pts']['msg']['log'] = base64_encode($this->phodevi_vfs->__get($args));
						$this->send_json_data($user->socket, $json);
					}
					break;
				case 'pts-version':
					$json['pts']['msg']['name'] = 'pts_version';
					$json['pts']['msg']['version'] = pts_title(true);
					$this->send_json_data($user->socket, $json);
					break;
				case 'core-version':
					$version = PTS_CORE_VERSION;
					$this->send_data($user->socket, $version);
					break;
				case 'run-benchmark-queue':
					// BENCHMARK
					//$this->run_benchmark($user, $args);
					//pts_client::fork(array($this, 'run_benchmark'), array($user, $args));
					break;
			}
		}
	}
}
