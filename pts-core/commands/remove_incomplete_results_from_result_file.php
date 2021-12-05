<?php

/*
	Phoronix Test Suite
	Copyright (C) 2021, Phoronix Media
	Copyright (C) 2021, Michael Larabel

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

class remove_incomplete_results_from_result_file implements pts_option_interface
{
	const doc_section = 'Result Management';
	const doc_description = 'This option is used if there are test results (benchmarks) to be dropped from a given result file for having incomplete data, either a test run did not attempt to run that benchmark or failed to properly run. The user must specify a saved results file and the command will then attempt to find any results with incomplete/missing data and prompt the user with confirmation to remove them.';

	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array('pts_types', 'is_result_file'), null)
		);
	}
	public static function run($r)
	{
		$result_file = new pts_result_file($r[0]);
		$system_count = $result_file->get_system_count();
		$table = array();
		foreach($result_file->get_result_objects() as $id => $result)
		{
			if($result->test_result_buffer->get_count() < $system_count || $result->test_result_buffer->has_incomplete_result())
			{
				$table[] = array($result->test_profile->get_title(), $result->get_arguments_description(), $result->test_profile->get_result_scale());
				$result_file->remove_result_object_by_id($id);
			}
		}

		if(empty($table))
		{
			echo 'No incomplete results found.';
		}
		else
		{
			echo PHP_EOL . pts_client::cli_just_bold('Incomplete results to remove: ') . PHP_EOL;
			echo pts_user_io::display_text_table($table) . PHP_EOL . PHP_EOL;
			$do_save =  pts_user_io::prompt_bool_input('Save the result file changes?');
			if($do_save)
			{
				pts_client::save_test_result($result_file->get_file_location(), $result_file->get_xml());
				pts_client::display_result_view($result_file, false);
			}
		}
	}
}
?>
