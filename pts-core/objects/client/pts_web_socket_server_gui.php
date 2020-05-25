<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2013 - 2016, Phoronix Media
	Copyright (C) 2013 - 2016, Michael Larabel
	pts-web-socket_server_gui: Build upon pts_web_socket with functionality for the web GUI

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

class pts_web_socket_server_gui extends pts_web_socket
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
							$json['pts']['msg']['results'][current($sections)][] = $result;
						}
					}
					$this->send_json_data($user->socket, $json);
					break;
				case 'user-svg-system-graphs':
				//	pts_client::timed_function(array($this, 'generate_system_svg_graphs'), array($user), 1, array($this, 'sensor_logging_continue'), array($user));
					$this->generate_system_svg_graphs($user, $args);
					break;
				case 'user-large-svg-system-graphs':
				//	pts_client::timed_function(array($this, 'generate_system_svg_graphs'), array($user), 1, array($this, 'sensor_logging_continue'), array($user));
					$this->generate_large_system_svg_graphs($user, $args);
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
						$json['pts']['msg']['tests'][] = $test;
						$tp = new pts_test_profile($test);
						$json['pts']['msg']['test_profiles'][] = base64_encode($tp->to_json());
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
					$json['pts']['msg']['version'] = pts_core::program_title(true);
					$this->send_json_data($user->socket, $json);
					break;
				case 'core-version':
					$version = PTS_CORE_VERSION;
					$this->send_data($user->socket, $version);
					break;
				case 'run-benchmark-queue':
					// BENCHMARK
					//$this->run_benchmark($user, $args);
					pts_client::fork(array($this, 'run_benchmark'), array($user, $args));
					break;
			}
		}
	}
	public function run_benchmark($user, $args)
	{
		$json_queue = json_decode(base64_decode($args), true);
		$json['pts']['msg']['name'] = 'run_benchmark_queue';

		if(!isset($json_queue['tests']) || count($json_queue['tests']) == 0)
		{
			$json['pts']['msg']['error'] = 'No tests in the queue.';
		}
		else if(!isset($json_queue['title']) || $json_queue['title'] == null)
		{
			$json['pts']['msg']['error'] = 'No test title/name provided.';
		}
		else if(!isset($json_queue['identifier']) || $json_queue['identifier'] == null)
		{
			$json['pts']['msg']['error'] = 'No test identifier provided.';
		}
		else
		{
			$json['pts']['msg']['go'] = 'Benchmarking.';
		}
		$this->send_json_data($user->socket, $json);

		if(isset($json['pts']['msg']['error']) && $json['pts']['msg']['error'] != null)
		{
			exit(1);
		}
		pts_client::$display = new pts_websocket_display_mode();
		pts_client::$display->set_web_socket($this, $user->id);
		$test_suite = array();
		$test_suite[0] = new pts_test_suite();
		foreach($json_queue['tests'] as $test)
		{
			$test_suite[0]->add_to_suite($test['test_profile_id'], $test['test_options_title'], $test['test_options_value']);
		}
		$test_run_manager = new pts_test_run_manager(false, true);
		pts_test_installer::standard_install($test_suite, false, true);
		if($test_run_manager->initial_checks($test_suite) == false)
		{
			$j['pts']['msg']['name'] = 'benchmark_state';
			$j['pts']['msg']['current_state'] = 'failed';
			$j['pts']['msg']['error'] = 'Failed to install test.';
			$this->send_json_data($user->socket, $j);
			exit(1);
		}
		if($test_run_manager->load_tests_to_run($test_suite))
		{
			// SETUP
			$test_run_manager->auto_upload_to_openbenchmarking();
			pts_openbenchmarking_client::override_client_setting('UploadSystemLogsByDefault', true);
			$test_run_manager->auto_save_results($json_queue['title'], $json_queue['identifier'], $json_queue['description'], true);

			// BENCHMARK
			$test_run_manager->pre_execution_process();
			$test_run_manager->call_test_runs();
			$test_run_manager->post_execution_process();

			$j['pts']['msg']['name'] = 'benchmark_state';
			$j['pts']['msg']['current_state'] = 'complete';
			$j['pts']['msg']['result_title'] = $test_run_manager->get_title();
			$j['pts']['msg']['result_file_name'] = $test_run_manager->get_file_name();
			$j['pts']['msg']['result_identifier'] = $test_run_manager->get_results_identifier();
			$j['pts']['msg']['result_url'] = $test_run_manager->get_results_url();
			$this->send_json_data($user->socket, $j);
		}
		// exit(0);
	}
	protected function search_pts(&$user, $search)
	{
		$json['pts']['msg']['name'] = 'search_results';

		if(strlen($search) < 3)
		{
			$json['pts']['status']['error'] = 'Longer search query needed; at least three characters required.';
			$this->send_json_data($user->socket, $json);
			return false;
		}
		else if(is_numeric($search))
		{
			$json['pts']['status']['error'] = 'An alpha-numeric string is needed to perform this search.';
			$this->send_json_data($user->socket, $json);
			return false;
		}

		$test_matches = pts_openbenchmarking_client::search_tests($search, true);
		$json['pts']['msg']['exact_hits'] = 0;
		$json['pts']['msg']['search_query'] = $search;

		if(count($test_matches) > 0)
		{
			$json['pts']['msg']['test_profiles'] = array();
			$json['pts']['msg']['exact_hits'] = 1;
			$json['pts']['msg']['tests'] = array();

			for($i = 0; $i < count($test_matches); $i++)
			{
				$json['pts']['msg']['tests'][] = $test_matches[$i];
				$tp = new pts_test_profile($test_matches[$i]);
				$json['pts']['msg']['test_profiles'][] = base64_encode($tp->to_json());
			}
		}
		else
		{
			// DO MORE BROAD SEARCH, NOT A TEST...
			$test_matches = pts_openbenchmarking_client::search_tests($search, false);
			$json['pts']['msg']['test_profiles'] = array();
			$json['pts']['msg']['tests'] = array();

			for($i = 0; $i < count($test_matches); $i++)
			{
				$json['pts']['msg']['tests'][] = $test_matches[$i];
				$tp = new pts_test_profile($test_matches[$i]);
				$json['pts']['msg']['test_profiles'][] = base64_encode($tp->to_json());
			}
			// SEARCH TEST PROFILES
		}

		$json['pts']['msg']['results'] = array();
		$json['pts']['msg']['result_files'] = array();
		if(count($test_matches) > 0)
		{
			$result_matches = pts_tests::search_test_results($search, 'RESULTS');

			foreach($result_matches as $result)
			{
				$result_file = new pts_result_file($result);
				$json['pts']['msg']['results'][] = $result;
				$json['pts']['msg']['result_files'][] = base64_encode($result_file->to_json());
			}
		}
		else
		{
			$result_matches = pts_tests::search_test_results($search, 'ALL');

			foreach($result_matches as $result)
			{
				$result_file = new pts_result_file($result);
				$json['pts']['msg']['results'][] = $result;
				$json['pts']['msg']['result_files'][] = base64_encode($result_file->to_json());
			}

		}

		$this->send_json_data($user->socket, $json);
	}
	protected function generate_system_svg_graphs(&$user, $sensors_to_watch = null)
	{
		if($this->sensor_logging == false)
		{
			return false;
		}

		$json['pts']['msg']['name'] = 'svg_graphs';
		$json['pts']['msg']['contents'] = null;
		foreach($this->sensor_logging->sensors_logging($sensors_to_watch) as $sensor)
		{
			$sensor_data = $this->sensor_logging->read_sensor_results($sensor, -300);
			if(count($sensor_data['results']) < 2)
			{
				continue;
			}
			else if($sensors_to_watch == null && max($sensor_data['results']) == min($sensor_data['results']))
			{
				continue;
			}

			$graph = new pts_sys_graph(array('title' => $sensor_data['name'], 'x_scale' => 's', 'y_scale' => $sensor_data['unit'], 'reverse_x_direction' => true, 'width' => 300, 'height' => 160));
			$graph->render_base();
			$svg_dom = $graph->render_graph_data($sensor_data['results']);
			if($svg_dom === false)
			{
				continue;
			}
			$output_type = 'SVG';
			$graph = $svg_dom->output(null, $output_type);
			$json['pts']['msg']['contents'] .= ' ' . substr($graph, strpos($graph, '<svg'));
		}
		$this->send_json_data($user->socket, $json);
	}
	protected function generate_large_system_svg_graphs(&$user, $sensors_to_watch = null)
	{
		if($this->sensor_logging == false)
		{
			return false;
		}

		$json['pts']['msg']['name'] = 'large_svg_graphs';
		$json['pts']['msg']['contents'] = null;
		foreach($this->sensor_logging->sensors_logging($sensors_to_watch) as $sensor)
		{
			$sensor_data = $this->sensor_logging->read_sensor_results($sensor, -1000);
			if(count($sensor_data['results']) < 2)
			{
				continue;
			}
			else if($sensors_to_watch == null && max($sensor_data['results']) == min($sensor_data['results']))
			{
				continue;
			}

			$graph = new pts_sys_graph(array('title' => $sensor_data['name'], 'x_scale' => 's', 'y_scale' => $sensor_data['unit'], 'reverse_x_direction' => true, 'width' => 800, 'height' => 300));
			$graph->render_base();
			$svg_dom = $graph->render_graph_data($sensor_data['results']);
			if($svg_dom === false)
			{
				continue;
			}
			$output_type = 'SVG';
			$graph = $svg_dom->output(null, $output_type);
			$json['pts']['msg']['contents'] .= substr($graph, strpos($graph, '<svg')) . '<br /><br />';
		}
		$json['pts']['msg']['contents'] = base64_encode($json['pts']['msg']['contents']);
		$this->send_json_data($user->socket, $json);
	}

}
