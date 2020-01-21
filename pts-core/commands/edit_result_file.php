<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2014 - 2020, Phoronix Media
	Copyright (C) 2014 - 2020, Michael Larabel

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

class edit_result_file implements pts_option_interface
{
	const doc_section = 'Result Management';
	const doc_description = 'This option is used if you wish to edit the title and description of an existing result file.';

	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array('pts_types', 'is_result_file'), null)
		);
	}
	public static function run($r)
	{
		$result_file = new pts_result_file($r[0]);
		$result_title = $result_file->get_title();
		echo PHP_EOL . 'Current Result Title: ' . $result_title . PHP_EOL;
		$new_title = pts_user_io::prompt_user_input('Enter New Title');
		if(!empty($new_title))
		{
			$result_file->set_title($new_title);
		}
		$result_description = $result_file->get_description();
		echo PHP_EOL . 'Current Result Description: ' . $result_description . PHP_EOL;
		$new_description = pts_user_io::prompt_user_input('Enter New Description');
		if(!empty($new_description))
		{
			$result_file->set_description($new_description);
		}
		$result_file->save();
		pts_client::display_result_view($result_file, false);
	}
}

?>
