<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2018, Phoronix Media
	Copyright (C) 2009 - 2018, Michael Larabel
	pts_display_mode_interface.php: The interface used by display mode objects

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

interface pts_display_mode_interface
{
	public function __construct();
	public function test_install_process(&$test_install_manager);
	public function test_install_begin($test_install_request);
	public function test_install_downloads($test_install_request);
	public function test_install_download_file($process, &$pts_test_file_download);
	public function test_install_progress_start($process);
	public function test_install_progress_update($progress_float);
	public function display_interrupt_message($message);
	public function test_install_progress_completed();
	public function test_run_process_start(&$test_run_manager);
	public function test_install_start($test_identifier);
	public function test_install_output(&$to_output);
	public function test_install_message($msg_string);
	public function test_install_error($error_string);
	public function test_install_prompt($prompt_string);
	public function test_run_configure(&$test_profile);
	public function test_run_start(&$test_run_manager, &$test_result);
	public function test_run_message($message_string);
	public function test_run_instance_header(&$test_result);
	public function test_run_instance_error($error_string);
	public function test_run_instance_output(&$to_output);
	public function test_run_instance_complete(&$test_result);
	public function test_run_end(&$test_result);
	public function test_run_success_inline($test_result);
	public function test_run_error($error_string);
	public function generic_prompt($prompt_string);
	public function generic_heading($string);
	public function generic_sub_heading($string);
	public function triggered_system_error($level, $message, $file, $line);
	public function get_tab();
}

?>
