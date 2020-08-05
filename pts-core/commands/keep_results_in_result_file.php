<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2020, Phoronix Media
	Copyright (C) 2020, Michael Larabel

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

class keep_results_in_result_file implements pts_option_interface
{
	const doc_section = 'Result Management';
	const doc_description = 'This option is the inverse of the remove-results-from-result-file sub-command. If you wish to remove all results but those listed from a given result file, this option can be used. The user must specify a saved results file and then they will be prompted to provide a string to search for in keeping those results in that given result file but removing all other data.';

	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array('pts_types', 'is_result_file'), null)
		);
	}
	public static function run($r)
	{
		$result_file = new pts_result_file($r[0]);

		$remove_query = pts_user_io::prompt_user_input('Enter the title / arguments to search for to keep in the result file. Multiple search queries can be delimited by comma.');
		$rqs = pts_strings::trim_explode(',', $remove_query);
		$remove_table = array();
		$keep_table = array();
		foreach($result_file->get_result_objects() as $id => $result)
		{
			$has_match = false;
			foreach($rqs as $rq)
			{
				if(stripos($result->test_profile->get_title(), $rq) !== false || stripos($result->test_profile->get_result_scale(), $rq) !== false || stripos($result->get_arguments_description(), $rq) !== false)
				{
					$has_match = true;
					break;
				}
			}
			if($has_match)
			{
				$keep_table[] = array($result->test_profile->get_title(), $result->get_arguments_description(), $result->test_profile->get_result_scale());
			}
			else
			{
				$remove_table[] = array($result->test_profile->get_title(), $result->get_arguments_description(), $result->test_profile->get_result_scale());
				$result_file->remove_result_object_by_id($id);
			}
		}

		if(count($remove_table) == 0)
		{
			echo 'No matching result objects found for removal.';
		}
		else
		{
			echo PHP_EOL . pts_client::cli_just_bold('Results to remove: ') . PHP_EOL;
			echo pts_user_io::display_text_table($remove_table) . PHP_EOL . PHP_EOL;
			echo PHP_EOL . pts_client::cli_just_bold('Results to keep: ') . PHP_EOL;
			echo pts_user_io::display_text_table($keep_table) . PHP_EOL . PHP_EOL;
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
