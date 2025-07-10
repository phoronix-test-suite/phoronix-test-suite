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

class prune_extra_data implements pts_option_interface
{
	const doc_section = 'Result Management';
	const doc_description = 'This option is used to remove any extra/erroneous data from appearing if any duplicate result entries or other data appears within a saved result file. This option is typically only used for testing or encountering any errored result files.';

	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array('pts_types', 'is_result_file'), null)
		);
	}
	public static function run($r)
	{
		$result_file = new pts_result_file($r[0]);
		$result_count_with_extra_data = 0;
		$table = array();
		$system_identifiers = $result_file->get_system_identifiers();
		foreach($result_file->get_result_objects() as &$result_object)
		{
			foreach($result_object->test_result_buffer as &$buffers)
			{
				if(empty($buffers))
					continue;

				foreach($buffers as &$buffer_item)
				{
					if(!in_array($buffer_item->get_result_identifier(), $system_identifiers))
					{
						$result_count_with_extra_data++;
						echo $result_object->test_profile->get_title() . ' ' . $result_object->get_arguments_description() . '!!!!' . $buffer_item->get_result_identifier() . PHP_EOL;
						$result_object->test_result_buffer->remove($buffer_item->get_result_identifier());
					}
				}
			}
		}

		if($result_count_with_extra_data == 0)
		{
			echo 'No result objects found with zero data.';
		}
		else
		{
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
