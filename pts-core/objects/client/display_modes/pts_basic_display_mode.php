<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2018, Phoronix Media
	Copyright (C) 2009 - 2018, Michael Larabel
	pts_basic_display_mode.php: The basic display mode

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

class pts_basic_display_mode implements pts_display_mode_interface
{
	// Run bits
	private $expected_trial_run_count = 0;
	private $trial_run_count_current = 0;

	public function __construct()
	{

	}
	public function test_install_message($msg_string)
	{
		echo PHP_EOL . $msg_string . PHP_EOL;
	}
	public function test_install_process(&$test_install_manager)
	{
		return;
	}
	public function test_install_begin($test_install_request)
	{
		return;
	}
	public function test_install_downloads($test_install_request)
	{
		echo self::string_header('Downloading Files: ' . $test_install_request->test_profile->get_identifier());
	}
	public function test_install_download_file($process, &$pts_test_file_download)
	{
		switch($process)
		{
			case 'DOWNLOAD_FROM_CACHE':
				echo 'Downloading Cached File: ' . $pts_test_file_download->get_filename() . PHP_EOL . PHP_EOL;
				break;
			case 'LINK_FROM_CACHE':
				echo 'Linking Cached File: ' . $pts_test_file_download->get_filename() . PHP_EOL;
				break;
			case 'COPY_FROM_CACHE':
				echo 'Copying Cached File: ' . $pts_test_file_download->get_filename() . PHP_EOL;
				break;
			case 'DOWNLOAD':
				echo PHP_EOL . PHP_EOL . 'Downloading File: ' . $pts_test_file_download->get_filename() . PHP_EOL . PHP_EOL;
				break;
			case 'FILE_FOUND':
				break;
		}
	}
	public function test_install_progress_start($process)
	{
		return;
	}
	public function test_install_progress_update($download_float)
	{
		return;
	}
	public function test_install_progress_completed()
	{
		return;
	}
	public function test_install_start($identifier)
	{
		echo self::string_header('Installing Test: ' . $identifier);
	}
	public function test_install_output(&$to_output)
	{
		if(!isset($to_output[10240]) || pts_client::is_debug_mode())
		{
			// Not worth printing files over 10kb to screen
			echo $to_output;
		}
	}
	public function test_install_error($error_string)
	{
		echo PHP_EOL . $error_string . PHP_EOL;
	}
	public function test_install_prompt($prompt_string)
	{
		echo PHP_EOL . $prompt_string;
	}
	public function test_run_process_start(&$test_run_manager)
	{
		return;
	}
	public function test_run_message($message_string)
	{
		echo PHP_EOL . $message_string . PHP_EOL;
	}
	public function test_run_start(&$test_run_manager, &$test_result)
	{
		$this->trial_run_count_current = 0;
		$this->expected_trial_run_count = $test_result->test_profile->get_times_to_run();
	}
	public function test_run_instance_header(&$test_result)
	{
		$this->trial_run_count_current++;
		echo self::string_header($test_result->test_profile->get_title() . ' (Run ' . $this->trial_run_count_current . ' of ' . $this->expected_trial_run_count . ')' . ($test_result->get_arguments_description() != '' ? PHP_EOL . $test_result->get_arguments_description() : ''));
	}
	public function display_interrupt_message($message)
	{
		echo $message;
	}
	public function triggered_system_error($level, $message, $file, $line)
	{
		echo PHP_EOL . '[' .$level . '] ' . $message;

		if($file != null)
		{
			' in ' . $file;
		}
		if($line != 0)
		{
			echo  ':' . $line;
		}

		echo PHP_EOL;
	}
	public function test_run_instance_output(&$to_output)
	{
		echo $to_output;
	}
	public function test_run_instance_complete(&$result)
	{
		// Do nothing here
	}
	public function test_run_end(&$test_result)
	{
		$end_print = $test_result->test_profile->get_title() . ':' . PHP_EOL . $test_result->get_arguments_description();
		$end_print .= PHP_EOL . ($test_result->get_arguments_description() != null ? PHP_EOL : null);

		if(in_array($test_result->test_profile->get_display_format(), array('NO_RESULT', 'FILLED_LINE_GRAPH', 'LINE_GRAPH', 'IMAGE_COMPARISON')))
		{
			return;
		}
		else if(in_array($test_result->test_profile->get_display_format(), array('PASS_FAIL', 'MULTI_PASS_FAIL')))
		{
			$end_print .= PHP_EOL . 'Final: ' . $test_result->active->get_result() . ' (' . $test_result->test_profile->get_result_scale() . ')' . PHP_EOL;
		}
		else
		{
			foreach($test_result->active->results as $result)
			{
				$end_print .= $result . ' ' . $test_result->test_profile->get_result_scale() . PHP_EOL;
			}

			$end_print .= PHP_EOL . pts_strings::result_quantifier_to_string($test_result->test_profile->get_result_quantifier()) . ': ' . $test_result->active->get_result() . ' ' . $test_result->test_profile->get_result_scale();
		}

		echo self::string_header($end_print, '#');
	}
	public function test_run_success_inline($test_result)
	{
		// empty
	}
	public function test_run_error($error_string)
	{
		echo PHP_EOL . $error_string . PHP_EOL . PHP_EOL;
	}
	public function test_run_instance_error($error_string)
	{
		echo PHP_EOL . $error_string . PHP_EOL . PHP_EOL;
	}
	public function generic_prompt($prompt_string)
	{
		echo PHP_EOL . $prompt_string;
	}
	public function generic_heading($string)
	{
		static $shown_pts = false;

		if($shown_pts == false)
		{
			$string = pts_core::program_title() . PHP_EOL . $string;
		}

		echo self::string_header($string, '=');
	}
	public function generic_sub_heading($string)
	{
		echo $string . PHP_EOL;
	}
	protected static function string_header($heading, $char = '=')
	{
		// Return a string header
		if(!isset($heading[1]))
		{
			return null;
		}

		$header_size = 40;

		foreach(explode(PHP_EOL, $heading) as $line)
		{
			if(isset($line[($header_size + 1)])) // Line to write is longer than header size
			{
				$header_size = strlen($line);
			}
		}

		if(($terminal_width = pts_client::terminal_width()) < $header_size && $terminal_width > 0)
		{
			$header_size = $terminal_width;
		}

		return PHP_EOL . str_repeat($char, $header_size) . PHP_EOL . $heading . PHP_EOL . str_repeat($char, $header_size) . PHP_EOL . PHP_EOL;
	}
	public function test_run_configure(&$test_profile)
	{
		echo PHP_EOL . PHP_EOL . $test_profile->get_title() . ($test_profile->get_app_version() != null ? ' ' . $test_profile->get_app_version() : null) . ':' . PHP_EOL . $test_profile->get_identifier() . PHP_EOL;
		echo $test_profile->get_test_hardware_type() . ' Test Configuration';
	}
	public function get_tab()
	{
		return null;
	}
}

?>
