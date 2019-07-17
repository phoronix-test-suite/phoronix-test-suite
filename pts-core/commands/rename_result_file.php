<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2014 - 2019, Phoronix Media
	Copyright (C) 2014 - 2019, Michael Larabel

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

class rename_result_file implements pts_option_interface
{
	const doc_section = 'Result Management';
	const doc_description = 'This option is used if you wish to change the name of the saved name of a result file.';

	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array('pts_types', 'is_result_file'), null)
		);
	}
	public static function run($r)
	{
		$result = $r[0];

		if(is_dir(PTS_SAVE_RESULTS_PATH . $result))
		{
			do
			{
				$new_result_name = pts_user_io::prompt_user_input('Enter a new result file name for ' . $result);
				$clean_result_name = pts_test_run_manager::clean_save_name($new_result_name, true);
			}
			while($clean_result_name == null || is_dir(PTS_SAVE_RESULTS_PATH . $clean_result_name));
		}

		if(rename(PTS_SAVE_RESULTS_PATH . $result, PTS_SAVE_RESULTS_PATH . $clean_result_name))
		{
			echo PHP_EOL . 'Renamed ' . $result . ' to ' . $clean_result_name . '.' . PHP_EOL . PHP_EOL;
		}

		pts_client::display_result_view($clean_result_name, false);
	}
}

?>
