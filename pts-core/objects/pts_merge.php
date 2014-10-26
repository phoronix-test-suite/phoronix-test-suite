<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2014, Phoronix Media
	Copyright (C) 2008 - 2014, Michael Larabel

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

class pts_merge
{
	public static function merge_test_results_array($files_to_combine, $pass_attributes = null)
	{
		$result_file_writer = new pts_result_file_writer();
		self::merge_test_results_process($result_file_writer, $files_to_combine, $pass_attributes);

		return $result_file_writer->get_xml();
	}
	public static function merge_test_results()
	{
		// Merge test results
		// Pass the result file names/paths for each test result file to merge as each as a parameter of the array
		$files_to_combine = func_get_args();
		return self::merge_test_results_array($files_to_combine);
	}
	public static function merge_test_results_process(&$result_file_writer, &$files_to_combine, $pass_attributes = null)
	{
		$test_result_manager = new pts_result_file_merge_manager($pass_attributes);
		$has_written_suite_info = false;

		$result_files = array();
		$result_merge_selects = array();
		foreach($files_to_combine as &$file)
		{
			if(is_object($file) && $file instanceof pts_result_merge_select)
			{
				$result_merge_select = $file;
				$this_result_file = $result_merge_select->get_result_file();

				if(($this_result_file instanceof pts_result_file) == false)
				{
					$this_result_file = new pts_result_file($this_result_file);
				}
			}
			else if(is_object($file) && $file instanceof pts_result_file)
			{
				if(($t = $file->read_extra_attribute('rename_result_identifier')) != false)
				{
					// This code path is currently used by Phoromatic
					$result_merge_select = new pts_result_merge_select(null, null);
					$result_merge_select->rename_identifier($t);
				}
				else
				{
					$result_merge_select = null;
				}

				$this_result_file = $file;
			}
			else
			{
				$result_merge_select = new pts_result_merge_select($file, null);
				$this_result_file = new pts_result_file($result_merge_select->get_result_file());
			}

			if($this_result_file->get_test_count() == 0)
			{
				// No reason to print the system information if there are no contained results
				continue;
			}

			array_push($result_files, $this_result_file);
			array_push($result_merge_selects, $result_merge_select);
		}

		if(!isset($pass_attributes['only_render_results_xml']) && ($result_file_count = count($result_files)) > 0)
		{
			for($i = ($result_file_count - 1); $i >= 0; $i--)
			{
				$new_title = isset($pass_attributes['new_result_file_title']) && !empty($pass_attributes['new_result_file_title']) ? $pass_attributes['new_result_file_title'] : null;
				$ret = $result_file_writer->add_result_file_meta_data($result_files[$i], null, $new_title, null);

				if($ret)
				{
					break;
				}
			}
		}

		foreach($result_files as $i => &$result_file)
		{
			if(!isset($pass_attributes['only_render_results_xml']))
			{
				$result_file_writer->add_system_information_from_result_file($result_file, $result_merge_selects[$i]);
			}

			$test_result_manager->add_test_result_set($result_file->get_result_objects(), $result_merge_selects[$i]);
		}

		// Write the actual test results
		$result_file_writer->add_results_from_result_manager($test_result_manager);
	}
	public static function generate_analytical_batch_xml($analyze_file)
	{
		if(($analyze_file instanceof pts_result_file) == false)
		{
			$analyze_file = new pts_result_file($analyze_file);
		}

		$result_file_writer = new pts_result_file_writer();

		$result_file_writer->add_result_file_meta_data($analyze_file);
		$result_file_writer->add_system_information_from_result_file($analyze_file);

		$test_result_manager = new pts_result_file_analyze_manager();
		$test_result_manager->add_test_result_set($analyze_file->get_result_objects());
		$result_file_writer->add_results_from_result_manager($test_result_manager);
		unset($test_result_manager);

		return $result_file_writer->get_xml();
	}
}

?>
