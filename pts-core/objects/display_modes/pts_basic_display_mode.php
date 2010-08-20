<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2010, Phoronix Media
	Copyright (C) 2009 - 2010, Michael Larabel
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
	public function __construct()
	{

	}
	public function test_install_start($test_install_manager)
	{
		return;
	}
	public function test_install_begin($test_install_request)
	{
		return;
	}
	public function test_install_downloads($test_install_request)
	{
		echo self::string_header("Downloading Files: " . $test_install_request->test_profile->get_identifier());
	}
	public function test_install_download_file($process, &$pts_test_file_download)
	{
		switch($process)
		{
			case "DOWNLOAD_FROM_CACHE":
				echo "Downloading Cached File: " . $pts_test_file_download->get_filename() . "\n\n";
				break;
			case "LINK_FROM_CACHE":
				echo "Linking Cached File: " . $pts_test_file_download->get_filename() . "\n";
				break;
			case "COPY_FROM_CACHE":
				echo "Copying Cached File: " . $pts_test_file_download->get_filename() . "\n";
				break;
			case "DOWNLOAD":
				echo "\n\nDownloading File: " . $pts_test_file_download->get_filename() . "\n\n";
				break;
			case "FILE_FOUND":
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
	public function test_install_process($identifier)
	{
		echo self::string_header("Installing Test: " . $identifier);
	}
	public function test_install_output(&$to_output)
	{
		if(!isset($to_output[10240]) || pts_read_assignment("DEBUG_TEST_PROFILE"))
		{
			// Not worth printing files over 10kb to screen
			echo $to_output;
		}
	}
	public function test_install_error($error_string)
	{
		echo "\n" . $error_string . "\n";
	}
	public function test_install_prompt($prompt_string)
	{
		echo "\n" . $prompt_string;
	}
	public function test_run_process_start(&$test_run_manager)
	{
		return;
	}
	public function test_run_message($message_string)
	{
		echo "\n" . $message_string . "\n";
	}
	public function test_run_start(&$test_run_manager, &$test_result)
	{
		return;
	}
	public function test_run_instance_header(&$test_result, $current_run, $total_run_count)
	{
		echo self::string_header($test_result->test_profile->get_title() . " (Run " . $current_run . " of " . $total_run_count . ")");
	}
	public function test_run_instance_output(&$to_output)
	{
		echo $to_output;
	}
	public function test_run_instance_complete(&$test_result)
	{
		// Do nothing here
	}
	public function test_run_end(&$test_result)
	{
		$end_print = $test_result->test_profile->get_title() . ":\n" . $test_result->get_arguments_description();
		$end_print .= "\n" . ($test_result->get_arguments_description() != null ? "\n" : null);

		if(in_array($test_result->test_profile->get_result_format(), array("NO_RESULT", "LINE_GRAPH", "IMAGE_COMPARISON")))
		{
			return;
		}
		else if(in_array($test_result->test_profile->get_result_format(), array("PASS_FAIL", "MULTI_PASS_FAIL")))
		{
			$end_print .= "\nFinal: " . $test_result->get_result() . " (" . $test_result->test_profile->get_result_scale() . ")\n";
		}
		else
		{
			foreach($test_result->test_result_buffer->get_values() as $result)
			{
				$end_print .= $result . " " . $test_result->test_profile->get_result_scale() . "\n";
			}

			$end_print .= "\n" . pts_strings::result_format_to_string($test_result->test_profile->get_result_format()) . ": " . $test_result->get_result() . " " . $test_result->test_profile->get_result_scale();
		}

		echo self::string_header($end_print, "#");
	}
	public function test_run_error($error_string)
	{
		echo "\n" . $error_string . "\n\n";
	}
	public function test_run_instance_error($error_string)
	{
		echo "\n" . $error_string . "\n\n";
	}
	public function generic_prompt($prompt_string)
	{
		echo "\n" . $prompt_string;
	}
	public function generic_heading($string)
	{
		static $shown_pts = false;

		if($shown_pts == false && pts_client::get_command_exection_count() == 0)
		{
			$string = pts_title() . "\n" . $string;
		}

		echo self::string_header($string, '=');
	}
	public function generic_sub_heading($string)
	{
		echo $string . "\n";
	}
	public function generic_error($string)
	{
		echo self::string_header($string, '=');
	}
	public function generic_warning($string)
	{
		echo self::string_header($string, '=');
	}
	protected static function string_header($heading, $char = '=')
	{
		// Return a string header
		if(!isset($heading[1]))
		{
			return null;
		}

		$header_size = 40;

		foreach(explode("\n", $heading) as $line)
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

		return "\n" . str_repeat($char, $header_size) . "\n" . $heading . "\n" . str_repeat($char, $header_size) . "\n\n";
	}
}

?>
