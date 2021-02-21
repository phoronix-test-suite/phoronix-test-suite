<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2020, Phoronix Media
	Copyright (C) 2008 - 2020, Michael Larabel

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

class list_saved_results implements pts_option_interface
{
	const doc_section = 'Information';
	const doc_description = 'This option will list all of the saved test results found on the system.';

	public static function command_aliases()
	{
		return array('list_results', 'list_test_results');
	}
	public static function run($r)
	{
		$saved_results = pts_results::saved_test_results();
		pts_client::$display->generic_heading(count($saved_results) . ' Saved Results');

		if(count($saved_results) > 0)
		{
			foreach($saved_results as $saved_results_identifier)
			{
				$result_file = new pts_result_file($saved_results_identifier);

				if(($title = $result_file->get_title()) != null)
				{
					echo pts_client::cli_just_bold($saved_results_identifier) . ' ' . pts_client::cli_just_italic($title) . PHP_EOL;

					foreach($result_file->get_system_identifiers() as $id)
					{
						if(!empty($id))
						{
							echo "\t" . '- ' . $id . PHP_EOL;
						}
					}
					echo PHP_EOL;
				}
			}
		}
	}
}

?>
