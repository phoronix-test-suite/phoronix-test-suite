<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2024, Phoronix Media
	Copyright (C) 2024, Michael Larabel

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

class prune_empty_results implements pts_option_interface
{
	const doc_section = 'Result Management';
	const doc_description = 'This option is used if there are test results (benchmarks) to be dropped from a given result file that had no successful runs. I.e. if any run attempt(s) only errored out without producing any result for any of the test run(s) saved in that file. The user must specify a saved results file.';

	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array('pts_types', 'is_result_file'), null)
		);
	}
	public static function run($r)
	{
		$result_file = new pts_result_file($r[0]);
		$remove_count = 0;
		$table = array();
		foreach($result_file->get_result_objects() as $id => $result)
		{
			if(!$result->test_result_buffer->has_successful_run())
			{
				$table[] = array($result->test_profile->get_title(), $result->get_arguments_description(), $result->test_profile->get_result_scale());
				$result_file->remove_result_object_by_id($id);
				$remove_count++;
			}
		}

		if($remove_count == 0)
		{
			echo 'No empty result objects found.';
		}
		else
		{
			echo PHP_EOL . pts_client::cli_just_bold('Empty results to remove: ') . PHP_EOL;
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
