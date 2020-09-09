<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2019, Phoronix Media
	Copyright (C) 2019, Michael Larabel

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

class start_result_viewer implements pts_option_interface
{
	const doc_section = 'Result Viewer';
	const doc_description = 'Start the web-based result viewer.';

	public static function command_aliases()
	{
		return array('start_results_viewer');
	}
	public static function run($r)
	{
		if(pts_client::$web_result_viewer_active)
		{
			echo PHP_EOL . pts_client::cli_just_bold('Result Viewer: ') . 'http://localhost:' . pts_client::$web_result_viewer_active;
		}
		if(pts_client::$web_result_viewer_access_key)
		{
			echo PHP_EOL . pts_client::cli_just_bold('Access Key: ') . pts_client::$web_result_viewer_access_key;
		}

		if(!isset($r[0]) || $r[0] != 'daemon')
		{
			echo PHP_EOL . 'Press any key when done accessing the viewer to end the process...';
			pts_user_io::read_user_input();
		}
		else
		{
			echo PHP_EOL . 'Press CTRL^C when done accessing the viewer to end the process...';
			while(true)
			{
				sleep(60);
			}
                }
	}
}

?>
