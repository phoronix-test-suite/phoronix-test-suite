<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel
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

class pts_concise_display_mode implements pts_display_mode_interface
{
	private $run_process_tests_remaining_to_run;
	private $run_process_test_count;

	public function __construct()
	{

	}
	public function test_install_start($identifier)
	{
		echo "\n\t" . $identifier . ":\n";

		$test_install_position = pts_read_assignment("TEST_INSTALL_POSITION");
		$test_install_count = pts_read_assignment("TEST_INSTALL_COUNT");
		if($test_install_count > 1 && $test_install_position <= $test_install_count)
		{
			echo "\t\tTest Installation " . $test_install_position . " of " . $test_install_count . "\n";
		}
	}
	public function test_install_downloads($identifier, &$download_packages)
	{
		echo "\t\t" . count($download_packages) . " File" . (isset($download_packages[1]) ? "s" : "") . " Needed";

		if(($size = pts_estimated_download_size($identifier, 1048576)) > 0)
		{
			echo " / " . $size . " MB";

			/*
			// TODO: the below code is currently disabled as this size is taking into account download caches, etc. Need to take that out of there otherwise number is overinflated.
			if(($avg_speed = pts_read_assignment("DOWNLOAD_AVG_SPEED")) > 0)
			{
				$avg_time = ($size * 1048576) / $avg_speed;
				echo " / " . pts_format_time_string($avg_time, "SECONDS", true, 60);
			}
			*/
		}

		echo "\n";
	}
	public function test_install_download_file(&$pts_test_file_download, $process, $offset_length = -1)
	{
		$expected_time = 0;

		switch($process)
		{
			case "DOWNLOAD_FROM_CACHE":
				$process_string = "Downloading From Cache";
				break;
			case "LINK_FROM_CACHE":
				$process_string = "Linking From Cache";
				break;
			case "COPY_FROM_CACHE":
				$process_string = "Copying From Cache";
				break;
			case "DOWNLOAD":
				$process_string = "Downloading";

				if(($avg_speed = pts_read_assignment("DOWNLOAD_AVG_SPEED")) > 0 && ($this_size = $pts_test_file_download->get_filesize()) > 0)
				{
					$expected_time = $this_size / $avg_speed;
				}
				break;
		}

		$expected_time = is_numeric($expected_time) && $expected_time > 0 ? pts_format_time_string($expected_time, "SECONDS", false, 60) : null;

		echo "\t\t" . $process_string . ": " . $pts_test_file_download->get_filename();
		echo str_repeat(" ", ($offset_length > 0 ? ($offset_length - strlen($pts_test_file_download->get_filename())) : 0));
		echo " [" . pts_trim_double($pts_test_file_download->get_filesize() / 1048576, 2) . "MB" . ($expected_time != null ? " / ~" . $expected_time : null) . "]\n";
	}
	public function test_install_process($identifier)
	{
		if(($size = pts_estimated_environment_size($identifier)) > 0)
		{
			echo "\t\tInstallation Size: " . $size . " MB\n";
		}

		echo "\t\tInstalling Test\n";
		return;
	}
	public function test_install_output(&$to_output)
	{
		return;
	}
	public function test_install_error($error_string)
	{
		echo "\n" . $error_string . "\n";
	}
	public function test_run_process_start(&$test_run_manager)
	{
		$this->run_process_tests_remaining_to_run = array();

		foreach($test_run_manager->get_tests_to_run() as $test_run_request)
		{
			array_push($this->run_process_tests_remaining_to_run, $test_run_request->get_identifier());
		}

		$this->run_process_test_count = count($this->run_process_tests_remaining_to_run);
	}
	public function test_run_start(&$test_result)
	{
		echo "\n\n" . $test_result->get_attribute("TEST_TITLE") . ":\n\t" . $test_result->get_attribute("TEST_IDENTIFIER");

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

			if($this->run_process_test_count == $test_run_count && $test_run_position != $test_run_count && ($remaining_length = pts_estimated_run_time($this->run_process_tests_remaining_to_run)) > 1)
			{
				echo "\tEstimated Time Remaining: " . pts_format_time_string($remaining_length, "SECONDS", true, 60) . "\n";
			}

			array_shift($this->run_process_tests_remaining_to_run);
		}

		$estimated_length = pts_estimated_run_time($test_result->get_attribute("TEST_IDENTIFIER"));
		if($estimated_length > 1)
		{
			echo "\tEstimated Test Run-Time: " . pts_format_time_string($estimated_length, "SECONDS", true, 60) . "\n";
		}

		echo "\tExpected Trial Run Count: " . $test_result->get_attribute("TIMES_TO_RUN") . "\n";
	}
	public function test_run_instance_header(&$test_result, $current_run, $total_run_count)
	{
		echo "\t\tStarted Run " . $current_run . " @ " . date("H:i:s") . "\n";
	}
	public function test_run_output(&$to_output)
	{
		if(pts_is_assignment("DEBUG_TEST_PROFILE"))
		{
			echo $to_output;
		}

		return;
	}
	public function test_run_end(&$test_result)
	{
		if(in_array($test_result->get_result_format(), array("NO_RESULT", "LINE_GRAPH", "IMAGE_COMPARISON")))
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
