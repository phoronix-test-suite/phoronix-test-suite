<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010, Phoronix Media
	Copyright (C) 2010, Michael Larabel

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

class debug_test_download_links implements pts_option_interface
{
	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, "!empty", null, "The name of a test or suite must be entered.")
		);
	}
	public static function run($r)
	{
		foreach(pts_contained_tests($r[0], true, true, true) as $test_to_check)
		{
			echo "Checking: " . $test_to_check . "\n";

			foreach(pts_test_install_request::read_download_object_list($test_to_check) as $test_file_download)
			{
				foreach($test_file_download->get_download_url_array() as $url)
				{
					$stream_context = pts_network::stream_context_create();
					stream_context_set_params($stream_context, array("notification" => "pts_stream_status_callback"));
					$file_pointer = @fopen($url, 'r', false, $stream_context);
					//fread($file_pointer, 1024);

					if($file_pointer == false)
					{
						echo "\nDOWNLOAD: " . $test_file_download->get_filename() . " / " . $url . "\n";
					}
					else
					{
						@fclose($file_pointer);
					}

				}
			}
		}
	}
}

?>
