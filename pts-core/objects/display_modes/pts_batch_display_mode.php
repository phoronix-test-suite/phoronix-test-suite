<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel
	pts_batch_display_mode.php: The batch display mode

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

class pts_batch_display_mode implements pts_display_mode_interface
{
	public function __construct()
	{

	}
	public function test_install_start($identifier)
	{
		echo "\t" . $identifier . ":\n";

		$test_install_position = pts_read_assignment("TEST_INSTALL_POSITION");
		$test_install_count = pts_read_assignment("TEST_INSTALL_COUNT");
		if($test_install_count > 1 && $test_install_position <= $test_install_count)
		{
			echo "\t\tTest Installation " . $test_install_position . " of " . $test_install_count . "\n";
		}

		if(($size = pts_estimated_environment_size($identifier)) > 0)
		{
			echo "\t\tInstall Size: " . $size . " MB\n";
		}
		echo "\n";
	}
	public function test_install_downloads($identifier, &$download_packages)
	{
		echo "\t\t" . count($download_packages) . " File" . (isset($download_packages[1]) ? "s" : "") . " To Download";

		if(($size = pts_estimated_download_size($identifier)) > 0)
		{
			echo " / " . $size . " MB";
		}

		echo "\n";
	}
	public function test_install_download_file(&$pts_test_file_download, $process)
	{
		echo "\t\t" . $pts_test_file_download->get_filename() . ": ";

		switch($process)
		{
			case "DOWNLOAD_FROM_CACHE":
				echo "Downloading From Cache";
				break;
			case "LINK_FROM_CACHE":
				echo "Linking From Cache";
				break;
			case "COPY_FROM_CACHE":
				echo "Copying From Cache";
				break;
			case "DOWNLOAD":
				echo "Downloading";
				break;
		}

		echo " [" . pts_trim_double($pts_test_file_download->get_filesize() / 1048576, 1) . "MB]\n";
	}
	public function test_install_process($identifier)
	{
		return;
	}
	public function test_install_output(&$to_output)
	{
		return;
	}
	public function test_run_start(&$test_result)
	{
		echo "\n" . $test_result->get_attribute("TEST_TITLE") . ":\n\t" . $test_result->get_attribute("TEST_IDENTIFIER");

		if(($test_description = $test_result->get_attribute("TEST_DESCRIPTION")) != false)
		{
			echo " [" . $test_description . "]";
		}

		echo "\n";

		$test_run_position = pts_read_assignment("TEST_RUN_POSITION");
		$test_run_count = pts_read_assignment("TEST_RUN_COUNT");
		if($test_run_count > 1 && $test_run_position <= $test_run_count)
		{
			echo "\tTest Run " . $test_run_position . " of " . $test_run_count . "\n";
		}

		$estimated_length = pts_estimated_run_time($test_result->get_attribute("TEST_IDENTIFIER"));
		if($estimated_length > 1)
		{
			echo "\tEstimated Run-Time: " . pts_format_time_string($estimated_length, "SECONDS", true, 60) . "\n";
		}

		echo "\tTrial Run Count: " . $test_result->get_attribute("TIMES_TO_RUN") . "\n";
	}
	public function test_run_instance_header(&$test_result, $current_run, $total_run_count)
	{
		echo "\t\tStarted Run " . $current_run . " @ " . date("H:i:s") . "\n";
	}
	public function test_run_output(&$to_output)
	{
		return;
	}
	public function test_run_end(&$test_result)
	{
		if(in_array($test_result->get_result_format(), array("NO_RESULT", "LINE_GRAPH")))
		{
			return;
		}
		else if(in_array($test_result->get_result_format(), array("PASS_FAIL", "MULTI_PASS_FAIL")))
		{
			$end_print .= "\t\tFinal: " . $test_result->get_result() . " (" . $test_result->get_result_scale() . ")\n";
		}
		else
		{
			$end_print = "\n\tTest Results:\n";

			foreach($test_result->get_trial_results() as $result)
			{
				$end_print .= "\t\t" . $result . "\n";
			}

			$end_print .= "\n\t" . $test_result->get_result_format_string() . ": " . $test_result->get_result() . " " . $test_result->get_result_scale() . "\n";
		}

		echo $end_print . "\n";
	}
	public function test_run_error($error_string)
	{
		echo "\n" . $error_string . "\n\n";
	}
}

?>
