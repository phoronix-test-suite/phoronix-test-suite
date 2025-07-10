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

class prune_zero_data implements pts_option_interface
{
	const doc_section = 'Result Management';
	const doc_description = 'This option is used if there are test results (benchmarks) to be where there is line graph data that currently has zero values (e.g. inaccurate/invalid sensor readings) and you wish to just drop those zero reading values from the result file.';

	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array('pts_types', 'is_result_file'), null)
		);
	}
	public static function run($r)
	{
		$result_file = new pts_result_file($r[0]);
		$result_count_with_zero_data = 0;
		$table = array();
		foreach($result_file->get_result_objects() as &$result_object)
		{
			foreach($result_object->test_result_buffer as &$buffers)
			{
				if(empty($buffers))
					continue;

				foreach($buffers as &$buffer_item)
				{
					$v = $buffer_item->get_result_value();

					// Example Hacky workaround to correct existing recorded data such as from this bug of double reporting CPU power - https://lore.kernel.org/lkml/20240719092545.50441-3-Dhananjay.Ugwekar@amd.com/
					/*
					if(stripos($v, ',') !== false && stripos($buffer_item->get_result_identifier(), 'Ryzen 9 79') !== false)
					{
						$encountered_zero = false;
						$v = explode(',', $v);
						$new_v = array();
						for($i = 0; $i < count($v); $i++)
						{
							$encountered_zero = true;
							$new_v[] = round($v[$i] / 2, 2);
						}
						if($encountered_zero)
						{
							$buffer_item->reset_result_value(implode(',', $new_v));
							$result_count_with_zero_data++;
							echo $result_object->test_profile->get_title() . ' ' . $result_object->get_arguments_description() . '!!!!' . PHP_EOL;
						}
					}
					else if(stripos($result_object->test_profile->get_result_scale(), 'Per Watt') !== false && stripos($buffer_item->get_result_identifier(), 'Ryzen 9 79') !== false)
					{
						$num_p = pts_math::get_precision($v);
						$v = round($v * 2, $num_p);
						$buffer_item->reset_result_value($v);
						$result_count_with_zero_data++;
						echo $result_object->test_profile->get_title() . ' ' . $result_object->get_arguments_description() . '!!!!' . PHP_EOL;
					}
					*/
					if(stripos($v, ',') !== false)
					{
						$encountered_zero = false;
						$v = explode(',', $v);
						$new_v = array();
						for($i = 0; $i < count($v); $i++)
						{
							if($v[$i] == 0 || $v[$i] == "0.0")
							{
								// Zero data
								$encountered_zero = true;
							}
							else
							{
								$new_v[] = $v[$i];
							}
						}
						if($encountered_zero)
						{
							$buffer_item->reset_result_value(implode(',', $new_v));
							$result_count_with_zero_data++;
							echo $result_object->test_profile->get_title() . ' ' . $result_object->get_arguments_description() . '   ' . PHP_EOL;
						}
					}
				}
			}
		}

		if($result_count_with_zero_data == 0)
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
