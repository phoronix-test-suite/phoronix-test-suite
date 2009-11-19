<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel

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

class scp_result_pusher extends pts_module_interface
{
	const module_name = "SCP Result Pusher";
	const module_version = "0.1.0";
	const module_description = "This module will push test results over scp to a remote destination.";
	const module_author = "Michael Larabel";

	public static function module_setup()
	{
		return array(
		new pts_module_option("scp_user", "Enter the scp user-name", "NOT_EMPTY"),
		new pts_module_option("scp_host", "Enter the scp host", "NOT_EMPTY"),
		new pts_module_option("scp_remote_dir", "Enter the remote directory for results", "NOT_EMPTY")
		);
	}

	//
	// PTS Module API Hooks
	//
	
	public static function __event_results_saved($results_identifier)
	{
		if(!pts_module::is_module_setup())
		{
			return PTS_MODULE_UNLOAD;
		}

		$scp_user = pts_module::read_option("scp_user");
		$scp_host = pts_module::read_option("scp_host");
		$scp_remote_dir = pts_module::read_option("scp_remote_dir");

		if(is_dir(SAVE_RESULTS_DIR . $results_identifier) && pts_executable_in_path("scp"))
		{
			echo shell_exec("scp -r " . SAVE_RESULTS_DIR . $results_identifier . " " . $scp_user . "@" . $scp_host . ":" . $scp_remote_dir);
		}
	}
}

?>
