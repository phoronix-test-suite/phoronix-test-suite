<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2015, Phoronix Media
	Copyright (C) 2010 - 2015, Michael Larabel

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
	const doc_section = 'Asset Creation';
	const doc_description = 'This option will check all download links within the specified test profile(s) to ensure there are no broken URLs.';

	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array('pts_types', 'identifier_to_object'), null)
		);
	}
	public static function run($r)
	{
		foreach(pts_types::identifiers_to_test_profile_objects($r, true, true) as $test_profile)
		{
			echo 'Checking: ' . $test_profile . PHP_EOL;

			foreach(pts_test_install_request::read_download_object_list($test_profile) as $test_file_download)
			{
				foreach($test_file_download->get_download_url_array() as $url)
				{
					$stream_context = pts_network::stream_context_create();
					stream_context_set_params($stream_context, array('notification' => 'pts_stream_status_callback'));
					$file_pointer = @fopen($url, 'r', false, $stream_context);
					//fread($file_pointer, 1024);

					if($file_pointer == false)
					{
						echo PHP_EOL . 'BAD URL: ' . $test_file_download->get_filename() . ' / ' . $url . PHP_EOL;
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
