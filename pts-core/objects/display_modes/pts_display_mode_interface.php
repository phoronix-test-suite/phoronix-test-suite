<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel
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
	public function test_install_process($identifier);
	public function test_install_downloads($identifier, &$download_packages);
	public function test_install_start($test_identifier);
	public function test_install_output(&$to_output);
	public function test_run_start(&$test_result);
	public function test_run_instance_header(&$test_result, $current_run, $total_run_count);
	public function test_run_output(&$to_output);
	public function test_run_end(&$test_result);
	public function test_run_error($error_string);
}

?>
