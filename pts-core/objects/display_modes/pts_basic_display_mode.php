<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel
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
	public function test_install_start($identifier)
	{
		return;
	}
	public function test_install_downloads($identifier, &$download_packages)
	{
		$download_append = "";
		if(($size = pts_estimated_download_size($identifier)) > 0)
		{
			$download_append = "\nEstimated Download Size: " . $size . " MB";
		}
		echo pts_string_header("Downloading Files: " . $identifier . $download_append);
	}
	public function test_install_download_file(&$pts_test_file_download, $process)
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
		}
	}
	public function test_install_process($identifier)
	{
		$install_header = "Installing Test: " . $identifier;

		if(($size = pts_estimated_environment_size($identifier)) > 0)
		{
			$install_header .= "\nEstimated Install Size: " . $size . " MB";
		}

		echo pts_string_header($install_header);
	}
	public function test_install_output(&$to_output)
	{
		if(!isset($to_output[10240]))
		{
			// Not worth printing files over 10kb to screen
			echo $to_output;
		}
	}
	public function test_run_process_start(&$test_run_manager)
	{
		return;
	}
	public function test_run_start(&$test_result)
	{
		return;
	}
	public function test_run_instance_header(&$test_result, $current_run, $total_run_count)
	{
		echo pts_string_header($test_result->get_attribute("TEST_TITLE") . " (Run " . $current_run . " of " . $total_run_count . ")");
	}
	public function test_run_output(&$to_output)
	{
		echo $to_output;
	}
	public function test_run_end(&$test_result)
	{
		$end_print = $test_result->get_attribute("TEST_TITLE") . ":\n" . $test_result->get_attribute("TEST_DESCRIPTION");
		$end_print .= "\n" . ($test_result->get_attribute("TEST_DESCRIPTION") != "" ? "\n" : "");

		if(in_array($test_result->get_result_format(), array("NO_RESULT", "LINE_GRAPH")))
		{
			return;
		}
		else if(in_array($test_result->get_result_format(), array("PASS_FAIL", "MULTI_PASS_FAIL")))
		{
			$end_print .= "\nFinal: " . $test_result->get_result() . " (" . $test_result->get_result_scale() . ")\n";
		}
		else
		{
			foreach($test_result->get_trial_results() as $result)
			{
				$end_print .= $result . " " . $test_result->get_result_scale() . "\n";
			}

			$end_print .= "\n" . $test_result->get_result_format_string() . ": " . $test_result->get_result() . " " . $test_result->get_result_scale();
		}

		echo pts_string_header($end_print, "#");
	}
	public function test_run_error($error_string)
	{
		echo "\n" . $error_string . "\n\n";
	}
}

?>
