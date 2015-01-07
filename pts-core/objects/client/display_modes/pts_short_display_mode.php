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

class pts_short_display_mode implements pts_display_mode_interface
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

	public function __construct()
	{

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
	public function test_install_process($test_install_manager)
	{
		$this->test_install_pos = 0;
		$this->test_install_count = $test_install_manager->tests_to_install_count();

		echo PHP_EOL;
		echo $this->tab . pts_strings::plural_handler($this->test_install_count, 'Test') . ' To Install' . PHP_EOL;

		$download_size = 0;
		$download_total = 0;
		$cache_total = 0;
		$cache_size = 0;
		$install_size = 0;

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
			echo $this->tab . $this->tab . pts_strings::plural_handler($download_total, 'File') . ' To Download';

			if($download_size > 0)
			{
				echo ' [' . self::bytes_to_download_size($download_size) . 'MB]';
			}
			echo PHP_EOL;
		}

		if($cache_total > 0)
		{
			echo $this->tab . $this->tab . pts_strings::plural_handler($cache_total, 'File') . ' In Cache';

			if($cache_size > 0)
			{
				echo ' [' . self::bytes_to_download_size($cache_size) . 'MB]';
			}
			echo PHP_EOL;
		}

		if($install_size > 0)
		{
			echo $this->tab . $this->tab . ceil($install_size) . 'MB Of Disk Space Is Needed' . PHP_EOL;
		}

		echo PHP_EOL;
	}
	public function test_install_start($identifier)
	{
		$this->test_install_pos++;
		echo $this->tab . $identifier . ':' . PHP_EOL;
		echo $this->tab . $this->tab . 'Test Installation ' . $this->test_install_pos . ' of ' . $this->test_install_count . PHP_EOL;
	}
	public function test_install_downloads($test_install_request)
	{
		$identifier = $test_install_request->test_profile->get_identifier();
		$download_packages = $test_install_request->get_download_objects();

		echo $this->tab . $this->tab . count($download_packages) . ' File' . (isset($download_packages[1]) ? 's' : null) . ' Needed';

		if(($size = $test_install_request->test_profile->get_download_size(false, 1048576)) > 0)
		{
			if($size > 99)
			{
				$size = ceil($size);
			}

			echo ' [' . $size . ' MB';

			if(($avg_speed = pts_download_speed_manager::get_average_download_speed()) > 0)
			{
				$avg_time = ($size * 1048576) / $avg_speed;
				echo ' / ' . pts_strings::format_time($avg_time, 'SECONDS', true, 60);
			}

			echo ']';
		}

		echo PHP_EOL;
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
				if(($avg_speed = pts_download_speed_manager::get_average_download_speed()) > 0 && ($this_size = $pts_test_file_download->get_filesize()) > 0)
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
		if($message == null)
		{
			return;
		}

		$terminal_width = pts_client::terminal_width() > 1 ? pts_client::terminal_width() : $terminal_width;
		$text_width = $terminal_width - (strlen($this->tab) * 3);
		echo PHP_EOL . $this->tab . $this->tab . wordwrap('[NOTICE] ' . $message, $text_width, PHP_EOL . $this->tab . $this->tab) . PHP_EOL;
	}
	public function test_install_progress_start($process)
	{
		$this->progress_line_prefix = $process;
		$this->progress_last_float = -1;
		$this->progress_tab_count = 1;
		$this->progress_string_length = pts_client::terminal_width() > 1 ? pts_client::terminal_width() - 4 : 20;
		return;
	}
	public function test_install_progress_update($progress_float)
	{
		if($this->progress_last_float == -1)
		{
			$progress_prefix = str_repeat($this->tab, $this->progress_tab_count) . $this->progress_line_prefix . ' ';
			echo $progress_prefix;
			$this->progress_char_count = $this->progress_string_length - strlen($progress_prefix);
			$this->progress_char_pos = 0;
		}

		$char_current = floor($progress_float * $this->progress_char_count);

		if($char_current > $this->progress_char_pos && $char_current <= $this->progress_char_count)
		{
			echo str_repeat('.', $char_current - $this->progress_char_pos);
			$this->progress_char_pos = $char_current;
		}

		$this->progress_last_float = $progress_float;
	}
	public function test_install_progress_completed()
	{
		echo $this->progress_last_float != -1 ? str_repeat('.', $this->progress_char_count - $this->progress_char_pos) . PHP_EOL : null;
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
		echo PHP_EOL . PHP_EOL . $test_profile->get_title() . ($test_profile->get_app_version() != null ? ' ' . $test_profile->get_app_version() : null) . ':' . PHP_EOL . $this->tab . $test_profile->get_identifier() . PHP_EOL;
		echo $this->tab . $test_profile->get_test_hardware_type() . ' Test Configuration';
		//echo PHP_EOL;
		//echo $this->tab . 'Test ' . $test_run_manager->get_test_run_position() . ' of ' . $test_run_manager->get_test_run_count_reported() . PHP_EOL;
	}
	public function test_run_start(&$test_run_manager, &$test_result)
	{
		echo PHP_EOL . $test_result->test_profile->get_identifier();

		if(($test_description = $test_result->get_arguments_description()) != false)
		{
			echo ': ' . pts_client::swap_variables($test_description, array('pts_client', 'environmental_variables'));
		}

		echo PHP_EOL;
		echo $test_result->test_profile->get_identifier() . ': Test ' . $test_run_manager->get_test_run_position() . ' of ' . $test_run_manager->get_test_run_count_reported() . PHP_EOL;

		$this->trial_run_count_current = 0;
		$this->expected_trial_run_count = $test_result->test_profile->get_times_to_run();
	}
	public function test_run_instance_error($error_string)
	{
		return;
	}
	public function test_run_instance_output(&$to_output)
	{
		return;
	}
	public function test_run_message($message_string)
	{
		return;
	}
	public function test_run_error($error_string)
	{
		return;
	}
	public function test_run_instance_header(&$test_result)
	{
		$this->trial_run_count_current++;
		echo $test_result->test_profile->get_identifier() . ': Started Run ' . $this->trial_run_count_current . ' @ ' . date('H:i:s') . PHP_EOL;
	}
	public function test_run_instance_complete(&$test_result)
	{
		return;
	}
	public function test_run_end(&$test_result)
	{
		if(in_array($test_result->test_profile->get_display_format(), array('NO_RESULT', 'IMAGE_COMPARISON')))
		{
			$end_print = null;
		}
		else if(in_array($test_result->test_profile->get_display_format(), array('PASS_FAIL', 'MULTI_PASS_FAIL')))
		{
			$end_print = 'Final: ' . $test_result->get_result() . ' (' . $test_result->test_profile->get_result_scale() . ')';
		}
		else if(in_array($test_result->test_profile->get_display_format(), array('FILLED_LINE_GRAPH', 'LINE_GRAPH')))
		{
			$values = explode(',', $test_result->get_result());
			$end_print = null;

			if(count($values) > 1)
			{
				$avg = pts_math::set_precision(array_sum($values) / count($values), 2);
				$min = pts_math::set_precision(min($values), 2);
				$max = pts_math::set_precision(max($values), 2);
				$end_print .= 'AVG: ' . $avg . ' (' . $test_result->test_profile->get_result_scale() . ') / ';
				$end_print .= 'MIN: ' . $min . ' (' . $test_result->test_profile->get_result_scale() . ') / ';
				$end_print .= 'MAX: ' . $max . ' (' . $test_result->test_profile->get_result_scale() . ') / ';
			}
		}
		else
		{
			$end_print = pts_strings::result_quantifier_to_string($test_result->test_profile->get_result_quantifier()) . ': ' . $test_result->get_result() . ' ' . $test_result->test_profile->get_result_scale();
		}

		echo $test_result->test_profile->get_identifier() . ': ' . $end_print;
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
			$string = pts_title() . PHP_EOL . $string;
			$shown_pts = true;
		}

		if(!empty($string))
		{
			echo PHP_EOL;
			foreach(pts_strings::trim_explode(PHP_EOL, $string) as $line_count => $line_string)
			{
				// ($line_count > 0 ? $this->tab : null) .
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
		echo PHP_EOL . '[' . $level . '] ';
		if(strpos($message, PHP_EOL) === false)
		{
			echo $message . ' ';
		}
		else
		{
			foreach(pts_strings::trim_explode(PHP_EOL, $message) as $line_count => $line_string)
			{
				// ($line_count > 0 ? $this->tab : null)
				echo $line_string . PHP_EOL . str_repeat(' ', strlen($level) + 3);
			}
		}

		if($file != null)
		{
			echo 'in ' . basename($file, '.php');
		}
		if($line != 0)
		{
			echo  ':' . $line;
		}

		echo PHP_EOL;
	}
	public function get_tab()
	{
		return $this->tab;
	}
}

?>
