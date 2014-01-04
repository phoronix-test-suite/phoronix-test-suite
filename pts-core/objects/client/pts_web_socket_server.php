<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2013 - 2014, Phoronix Media
	Copyright (C) 2013 - 2014, Michael Larabel
	pts-web-socket_server: Build upon pts_web_socket

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

class pts_web_socket_server extends pts_web_socket
{
	private $sensor_logging = false;
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
					$this->add_to_status('Generating Phodevi Cache Information', $json);
					$this->send_json_data($user->socket, $json);
					phodevi::system_software(true);
					phodevi::system_hardware(true);

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
				default:
					$this->shared_events($user, $resource);
					break;
			}
			return true;
		}
	}
	protected function process_data(&$user, &$msg)
	{
		$decoded_msg = $this->decode_data($msg);
		$args = trim(strstr($decoded_msg, ' '));

		switch(strstr($decoded_msg . ' ', ' ', true))
		{
			case 'search':
				$this->search_pts($user, $args);
			case 'result_file':
				$result_file = new pts_result_file($args);
				$json['pts']['msg']['name'] = 'result_file';
				$json['pts']['msg']['result'] = $args;
				$json['pts']['msg']['result_file'] = base64_encode($result_file->to_json());
				$this->send_json_data($user->socket, $json);
			default:
				$this->shared_events($user, $decoded_msg);
				break;
		}
	}
	protected function shared_events(&$user, &$msg)
	{
		switch(strstr($msg . ' ', ' ', true))
		{
			case 'version':
				$version = pts_title(true);
				$this->send_data($user->socket, $version);
				break;
			case 'core-version':
				$version = PTS_CORE_VERSION;
				$this->send_data($user->socket, $version);
				break;
			case 'user-svg-system-graphs':
			//	pts_client::timed_function(array($this, 'generate_system_svg_graphs'), array($user), 1, array($this, 'sensor_logging_continue'), array($user));
				$this->generate_system_svg_graphs($user);
				break;
			case 'user-large-svg-system-graphs':
			//	pts_client::timed_function(array($this, 'generate_system_svg_graphs'), array($user), 1, array($this, 'sensor_logging_continue'), array($user));
				$this->generate_large_system_svg_graphs($user);
				break;
		}
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
				array_push($json['pts']['msg']['tests'], $test_matches[$i]);

				$tp = new pts_test_profile($test_matches[$i]);
				array_push($json['pts']['msg']['test_profiles'], base64_encode($tp->to_json()));
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
				array_push($json['pts']['msg']['tests'], $test_matches[$i]);

				$tp = new pts_test_profile($test_matches[$i]);
				array_push($json['pts']['msg']['test_profiles'], base64_encode($tp->to_json()));
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
				array_push($json['pts']['msg']['results'], $result);
				array_push($json['pts']['msg']['result_files'], base64_encode($result_file->to_json()));
			}
		}
		else
		{
			$result_matches = pts_tests::search_test_results($search, 'ALL');

			foreach($result_matches as $result)
			{
				$result_file = new pts_result_file($result);
				array_push($json['pts']['msg']['results'], $result);
				array_push($json['pts']['msg']['result_files'], base64_encode($result_file->to_json()));
			}

		}

		$this->send_json_data($user->socket, $json);
	}
	protected function generate_system_svg_graphs(&$user)
	{
		if($this->sensor_logging == false)
		{
			return false;
		}

		$json['pts']['msg']['name'] = 'svg_graphs';
		$json['pts']['msg']['contents'] = null;
		foreach($this->sensor_logging->sensors_logging() as $sensor)
		{
			$sensor_data = $this->sensor_logging->read_sensor_results($sensor, -300);
			if(count($sensor_data['results']) < 2 || max($sensor_data['results']) == min($sensor_data['results']))
			{
				continue;
			}

			$graph = new pts_sys_graph(array('title' => $sensor_data['name'], 'x_scale' => 's', 'y_scale' => $sensor_data['unit'], 'reverse_x_direction' => true, 'width' => 350, 'height' => 160));
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
	protected function generate_large_system_svg_graphs(&$user)
	{
		if($this->sensor_logging == false)
		{
			return false;
		}

		$json['pts']['msg']['name'] = 'large_svg_graphs';
		$json['pts']['msg']['contents'] = null;
		foreach($this->sensor_logging->sensors_logging() as $sensor)
		{
			$sensor_data = $this->sensor_logging->read_sensor_results($sensor, -400);
			if(count($sensor_data['results']) < 2 || max($sensor_data['results']) == min($sensor_data['results']))
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
