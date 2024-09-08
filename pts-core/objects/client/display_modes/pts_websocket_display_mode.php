<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2020, Phoronix Media
	Copyright (C) 2009 - 2020, Michael Larabel
	pts_concise_display_mode.php: The batch / concise display mode

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

class pts_websocket_display_mode implements pts_display_mode_interface
{
	private $tab = '    ';

	// Download / progress bits
	private $progress_tab_count = 1;
	private $progress_line_prefix = null;
	private $progress_char_count = 0;
	private $progress_char_pos = 0;
	private $progress_string_length = 0;
	private $progress_last_float = -1;

	// Test install bits
	private $test_install_pos = 0;
	private $test_install_count = 0;

	// Run bits
	private $expected_trial_run_count = 0;
	private $trial_run_count_current = 0;

	private $web_socket_server = false;
	private $web_socket_user_id = false;

	public function __construct()
	{

	}
	public function test_install_message($msg_string)
	{
		return;
	}
	public function set_web_socket($ws_server, $ws_user_id)
	{
		$this->web_socket_server = $ws_server;
		$this->web_socket_user_id = $ws_user_id;
	}
	protected function web_socket_respond($json)
	{
		if($this->web_socket_server && $this->web_socket_user_id)
		{
			return $this->web_socket_server->send_json_data_by_user_id($this->web_socket_user_id, $json);
		}
		else
		{
			echo PHP_EOL . 'SOCKET PROBLEM' . PHP_EOL . PHP_EOL;
		}
	}
	protected function bytes_to_download_size($bytes)
	{
		$mb = pts_math::set_precision($bytes / 1048576, 2);

		if($mb > 99)
		{
			$mb = ceil($mb);
		}

		return $mb;
	}
	public function test_run_success_inline($test_result)
	{
		// empty
	}
	protected function update_install_status(&$m, $tr = null, $s = null)
	{
		static $test_install_manager = false;
		static $test_install_request = false;
		static $stats = false;

		if($m != null)
		{
			$test_install_manager = $m;
		}
		if($tr != null)
		{
			$test_install_request = $tr;
		}
		if($s != null)
		{
			if($stats == null || !is_array($stats))
			{
				$stats = $s;
			}
			else
			{
				$stats = array_merge($stats, $s);
			}
		}

		// GENERAL STUFF FOR CURRENT RUN
		$j['pts']['msg']['name'] = 'benchmark_state';
		$j['pts']['msg']['current_state'] = 'install';
		$j['pts']['msg']['current_test'] = $test_install_request ? base64_encode($test_install_request->test_profile->to_json()) : null;
		//$j['pts']['msg']['arguments_description'] = pts_client::swap_variables($test_result->get_arguments_description(), array('pts_client', 'environment_variables'));

		// CURRENT RUN QUEUE
		//$j['pts']['msg']['test_run_pos'] = $this->trial_run_count_current;
		//$j['pts']['msg']['test_run_total'] = $this->expected_trial_run_count;
		//$j['pts']['msg']['test_run_estimated_time'] = $test_result->get_estimated_run_time();

		// TOTAL QUEUE
		$j['pts']['msg']['test_install_pos'] = $this->test_install_pos;
		$j['pts']['msg']['test_install_total'] = $this->test_install_count;
		$j['pts']['msg']['test_install_disk_space_total'] = $stats['disk_space_total'];
		$j['pts']['msg']['test_install_download_total'] = $stats['download_total'];
		$j['pts']['msg']['test_install_cache_total'] = $stats['cache_total'];
		//$j['pts']['msg']['test_queue_estimated_run_time'] = $test_run_manager->get_estimated_run_time();

		// LATEST RESULT
		$this->web_socket_respond($j);
	}
	public function test_install_process(&$test_install_manager)
	{
		$this->test_install_pos = 0;
		$this->test_install_count = $test_install_manager->tests_to_install_count();

		$download_size = 0;
		$download_total = 0;
		$cache_total = 0;
		$cache_size = 0;
		$install_size = 0;

		$download_string_total = null;
		$cache_string_total = null;
		$disk_space_total = null;

		foreach($test_install_manager->get_test_run_requests() as $test_run_request)
		{
			$install_size += $test_run_request->test_profile->get_environment_size();

			foreach($test_run_request->get_download_objects() as $test_file_download)
			{
				switch($test_file_download->get_download_location_type())
				{
					case 'IN_DESTINATION_DIR':
						// We don't really care about these files here since they are good to go
						break;
					case 'LOCAL_DOWNLOAD_CACHE':
					case 'REMOTE_DOWNLOAD_CACHE':
					case 'LOOKASIDE_DOWNLOAD_CACHE':
						$cache_size += $test_file_download->get_filesize();
						$cache_total++;
						break;
					default:
						$download_size += $test_file_download->get_filesize();
						$download_total++;
						break;
				}
			}
		}

		if($download_total > 0)
		{
			$download_string_total = pts_strings::plural_handler($download_total, 'File');

			if($download_size > 0)
			{
				$download_string_total .= ' / ' . self::bytes_to_download_size($download_size) . 'MB';
			}
		}

		if($cache_total > 0)
		{
			$cache_string_total = pts_strings::plural_handler($cache_total, 'File');

			if($cache_size > 0)
			{
				$cache_string_total .= ' / ' . self::bytes_to_download_size($cache_size) . 'MB';
			}
		}

		if($install_size > 0)
		{
			$disk_space_total = ceil($install_size) . 'MB';
		}

		$stats = array('download_total' => $download_string_total, 'cache_total' => $cache_string_total, 'disk_space_total' => $disk_space_total);
		$this->update_install_status($test_install_manager, null, $stats);
	}
	public function test_install_start($identifier)
	{
		$null_ref_var = null;
		$this->update_install_status($null_ref_var, null);
	}
	public function test_install_downloads($test_install_request)
	{
		$stats['test_download_count'] = $test_install_request->get_download_objects();

		if(($size = $test_install_request->test_profile->get_download_size(false, 1048576)) > 0 && ($avg_speed = pts_client::get_average_download_speed()) > 0)
		{
			$stats['test_download_time'] = ($size * 1048576) / $avg_speed;
		}
		$null_ref_var = null;
		$this->update_install_status($null_ref_var, $test_install_request, $stats);
	}
	public function test_install_download_file($process, &$pts_test_file_download)
	{
		$expected_time = 0;
		$progress_prefix = null;

		switch($process)
		{
			case 'DOWNLOAD_FROM_CACHE':
				$process_string = 'Downloading From Cache';
				$progress_prefix = 'Downloading';
				break;
			case 'LINK_FROM_CACHE':
				$process_string = 'Linking From Cache';
				break;
			case 'COPY_FROM_CACHE':
				$process_string = 'Copying From Cache';
				$progress_prefix = 'Copying';
				break;
			case 'FILE_FOUND':
				$process_string = 'File Found';
				break;
			case 'DOWNLOAD':
				$process_string = 'Downloading';
				$progress_prefix = 'Downloading';
				if(($avg_speed = pts_client::get_average_download_speed()) > 0 && ($this_size = $pts_test_file_download->get_filesize()) > 0)
				{
					$expected_time = $this_size / $avg_speed;
				}
				break;
		}

		$expected_time = is_numeric($expected_time) && $expected_time > 0 ? pts_strings::format_time($expected_time, 'SECONDS', false, 60) : null;

		// TODO: handle if file-name is too long for terminal width
		$download_string = $this->tab . $this->tab . $process_string . ': ' . $pts_test_file_download->get_filename();
		$download_size_string = $pts_test_file_download->get_filesize() > 0 ? ' [' . self::bytes_to_download_size($pts_test_file_download->get_filesize()) . 'MB]' : null;
		$offset_length = pts_client::terminal_width() > 1 ? pts_client::terminal_width() : pts_test_file_download::$longest_file_name_length;
		$offset_length = $offset_length - strlen($download_string) - strlen($download_size_string) - 2;

		if($offset_length < 2)
		{
			$offset_length = 2;
		}

		$download_string .= str_repeat(' ', ($offset_length - 2));
		$download_string .= $download_size_string;
		echo $download_string . PHP_EOL;

		$this->progress_line_prefix = $expected_time != null ? 'Estimated Download Time: ' . $expected_time : $progress_prefix;
		$this->progress_last_float = -1;
		$this->progress_tab_count = 2;
		$this->progress_string_length = strlen($download_string);
	}
	public function display_interrupt_message($message)
	{
	}
	public function test_install_progress_start($process)
	{
	}
	public function test_install_progress_update($progress_float)
	{
		echo $progress_float . PHP_EOL;
	}
	public function test_install_progress_completed()
	{
		$this->progress_last_float == -1;
	}
	public function test_install_begin($test_install_request)
	{
		if(($size = $test_install_request->test_profile->get_environment_size(false)) > 0)
		{
			echo $this->tab . $this->tab . 'Installation Size: ' . $size . ' MB' . PHP_EOL;
		}

		echo $this->tab . $this->tab . 'Installing Test' . ' @ ' . date('H:i:s') . PHP_EOL;
		return;
	}
	public function test_install_output(&$to_output)
	{
		return;
	}
	public function test_install_error($error_string)
	{
		echo $this->tab . $this->tab . $this->tab . $error_string . PHP_EOL;
	}
	public function test_install_prompt($prompt_string)
	{
		echo $this->tab . $this->tab . $this->tab . $prompt_string;
	}
	public function test_run_process_start(&$test_run_manager)
	{
		return;
	}
	public function test_run_configure(&$test_profile)
	{
		echo 'ERROR!!!!! NOT EXPECTED IN THIS MODE: ' . __FUNCTION__;
		echo PHP_EOL . PHP_EOL . $test_profile->get_title() . ($test_profile->get_app_version() != null ? ' ' . $test_profile->get_app_version() : null) . ':' . PHP_EOL . $this->tab . $test_profile->get_identifier() . PHP_EOL;
		echo $this->tab . $test_profile->get_test_hardware_type() . ' Test Configuration';
		//echo PHP_EOL;
		//echo $this->tab . 'Test ' . $test_run_manager->get_test_run_position() . ' of ' . $test_run_manager->get_test_run_count_reported() . PHP_EOL;
	}
	protected function update_benchmark_status(&$m, &$tr)
	{
		static $test_run_manager = false;
		static $test_result = false;
		static $current_state = false;

		if($m != null)
		{
			$test_run_manager = $m;
		}
		if($tr != null)
		{
			$test_result = $tr;
		}

		// GENERAL STUFF FOR CURRENT RUN
		$j['pts']['msg']['name'] = 'benchmark_state';
		$j['pts']['msg']['current_state'] = 'benchmark';
		$j['pts']['msg']['current_test'] = base64_encode($test_result->test_profile->to_json());
		$j['pts']['msg']['arguments_description'] = pts_client::swap_variables($test_result->get_arguments_description(), array('pts_client', 'environment_variables'));

		// CURRENT RUN QUEUE
		$j['pts']['msg']['test_run_pos'] = $this->trial_run_count_current;
		$j['pts']['msg']['test_run_total'] = $this->expected_trial_run_count;
		$j['pts']['msg']['test_run_estimated_time'] = $test_result->get_estimated_run_time();

		if($j['pts']['msg']['test_run_pos'] > $j['pts']['msg']['test_run_total'])
		{
			// Don't let the run_pos go over run_total so instead dynamically increase run total and try to roughly compensate for increased dynamic run count
			$j['pts']['msg']['test_run_estimated_time'] = ($test_result->get_estimated_run_time() / $j['pts']['msg']['test_run_total']) * ($j['pts']['msg']['test_run_pos'] + 2);
			$j['pts']['msg']['test_run_total'] = $j['pts']['msg']['test_run_pos'] + 1;
		}

		// TOTAL QUEUE
		$j['pts']['msg']['test_queue_pos'] = $test_run_manager->get_test_run_position();
		$j['pts']['msg']['test_queue_total'] = $test_run_manager->get_test_run_count_reported();
		$j['pts']['msg']['test_queue_estimated_run_time'] = $test_run_manager->get_estimated_run_time();

		// LATEST RESULT
		$this->web_socket_respond($j);
	}
	public function test_run_start(&$test_run_manager, &$test_result)
	{
		$this->trial_run_count_current = 0;
		$this->expected_trial_run_count = $test_result->test_profile->get_times_to_run();

		$this->update_benchmark_status($test_run_manager, $test_result);
	}
	public function test_run_message($message_string)
	{
		//echo PHP_EOL . $this->tab . $this->tab . $message_string . ' @ ' . date('H:i:s');
	}
	public function test_run_instance_header(&$test_result)
	{
		$this->trial_run_count_current++;
		$test_run_manager = null;
		$this->update_benchmark_status($test_run_manager, $test_result);
	}
	public function test_run_instance_error($error_string)
	{ // TODO XXX: IMPLEMENT?!?
		echo PHP_EOL . $this->tab . $this->tab . $error_string;
	}
	public function test_run_instance_output(&$to_output)
	{
		return;
	}
	public function test_run_instance_complete(&$test_result)
	{
		// TODO XXX: IMPLEMENT?
		return;
	}
	public function test_run_end(&$test_result)
	{
		$test_run_manager = null;
		$this->update_benchmark_status($test_run_manager, $test_result);
		// TODO XXX: DO THIS
		echo PHP_EOL;

		if(!in_array($test_result->test_profile->get_display_format(), array('NO_RESULT', 'IMAGE_COMPARISON', 'PASS_FAIL', 'MULTI_PASS_FAIL', 'FILLED_LINE_GRAPH', 'LINE_GRAPH')))
		{
			// TODO XXX: At least implement line_graph support as may be fairly popular
			$end_print = PHP_EOL . $this->tab . 'Test Results:' . PHP_EOL;

			foreach($test_result->active->results as $result)
			{
				$end_print .= $this->tab . $this->tab . $result . PHP_EOL;
			}

			$end_print .= PHP_EOL . $this->tab . pts_strings::result_quantifier_to_string($test_result->test_profile->get_result_quantifier()) . ': ' . $test_result->active->get_result() . ' ' . $test_result->test_profile->get_result_scale();

			if($test_result->active->get_min_result())
			{
				$end_print .= PHP_EOL . $this->tab . 'Minimum: ' . $test_result->active->get_min_result();
			}
			if($test_result->active->get_max_result())
			{
				$end_print .= PHP_EOL . $this->tab . 'Maximum: ' . $test_result->active->get_max_result();
			}

			if($test_result->active->get_result() == 0)
			{
				$end_print .= PHP_EOL . $this->tab . 'This test failed to run properly.';
			}

			$end_print .= PHP_EOL;
		}

		echo $end_print;
	}
	public function test_run_error($error_string)
	{
		// TODO XXX: IMPLEMENT?
		echo $this->tab . $this->tab . $error_string . PHP_EOL;
	}
	public function generic_prompt($prompt_string)
	{
		echo $this->tab . $prompt_string;
	}
	public function generic_heading($string, $ending_line_break = true)
	{
		static $shown_pts = false;

		if($shown_pts == false)
		{
			$string = pts_core::program_title() . PHP_EOL . $string;
			$shown_pts = true;
		}

		if(!empty($string))
		{
			echo PHP_EOL;
			foreach(pts_strings::trim_explode(PHP_EOL, $string) as $line_count => $line_string)
			{
				// ($line_count > 0 ? $this->tab : null)
				echo $line_string . PHP_EOL;
			}

			if($ending_line_break)
			{
				echo PHP_EOL;
			}
		}
	}
	public function generic_sub_heading($string)
	{
		if(!empty($string))
		{
			// To generate the 'Phoronix Test Suite' heading string if not already done so
			pts_client::$display->generic_heading(null, false);

			foreach(pts_strings::trim_explode(PHP_EOL, $string) as $line_string)
			{
				echo $this->tab . $line_string . PHP_EOL;
			}
		}
	}
	public function triggered_system_error($level, $message, $file, $line)
	{
		echo '<script type="text/javascript">pts_add_web_notification(\'' . $level . '\', \'' . str_replace(PHP_EOL, '<br />', $message) . '\');</script>' . PHP_EOL;
	}
	public function get_tab()
	{
		return $this->tab;
	}
}

?>
