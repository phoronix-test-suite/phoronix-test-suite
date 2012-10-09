<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2012, Phoronix Media
	Copyright (C) 2012, Michael Larabel

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

class auto_compare implements pts_option_interface
{
	const doc_section = 'Testing';
	const doc_description = 'This option will autonomously determine the most relevant test(s) to run for any selected sub-system(s). The tests to run are determined via OpenBenchmarking.org integration with the global results pool. Related test results from OpenBenchmarking.org are also merged to provide a straight-forward and effective means of carrying out a system comparison.';

	public static function invalid_command($passed_args = null)
	{
		pts_tests::invalid_command_helper($passed_args);
	}
	public static function run($r)
	{
		$subsystem_under_test = pts_user_io::prompt_text_menu('Sub-System To Test', array('Processor', 'Graphics', 'Disk'));
		$system_info = array_merge(phodevi::system_hardware(false), phodevi::system_software(false));
		$to_include = array();
		$to_exclude = array();

		if(isset($system_info[$subsystem_under_test]))
		{
			$compare_component = $system_info[$subsystem_under_test];
		}
		else
		{
			return;
		}

		switch($subsystem_under_test)
		{
			case 'Processor':
				self::system_component_to_format($system_info, $to_include, array('OS', 'Compiler', 'Kernel', 'Motherboard'));
				break;
			case 'Graphics':
				self::system_component_to_format($system_info, $to_include, array('OS', 'Display Driver', 'OpenGL', 'Processor', 'Kernel', 'Desktop'));
				break;
			case 'OS':
				self::system_component_to_format($system_info, $to_include, array('Processor', 'Motherboard', 'Graphics', 'Disk'));
				self::system_component_to_format($system_info, $to_exclude, array('OS'));
				break;
			case 'Disk':
				self::system_component_to_format($system_info, $to_include, array('Processor', 'OS', 'Chipset', 'Motherboard', 'Kernel'));
				break;
		}

		$payload = array(
			'subsystem_under_test' => $subsystem_under_test,
			'component_under_test' => $compare_component,
			'include_components' => implode(',', $to_include),
			'exclude_components' => implode(',', $to_exclude)
			);

		echo PHP_EOL . 'Querying test data from OpenBenchmarking.org...' . PHP_EOL;
		$json = pts_openbenchmarking::make_openbenchmarking_request('auto_generate_comparison', $payload);
		$json = json_decode($json, true);

		if(isset($json['auto_compare']['public_ids']) && isset($json['auto_compare']['count']) && $json['auto_compare']['count'] > 0)
		{
			echo 'Found ' . $json['auto_compare']['count'] . ' comparable results on OpenBenchmarking.org with a ' . $json['auto_compare']['accuracy'] . '% accuracy.' . PHP_EOL;

			$compare_results = array();

			foreach($json['auto_compare']['public_ids'] as $public_id)
			{
				$result_xml = pts_openbenchmarking::clone_openbenchmarking_result($public_id, true);

				if($result_xml)
				{
					$result_file = new pts_result_file($result_xml);
					$result_objects = $result_file->get_result_objects();

					foreach($result_objects as $i => &$result_object)
					{
						if($result_object->test_profile->get_test_hardware_type() != $subsystem_under_test)
						{
							unset($result_objects[$i]);
						}
					}

					if(count($result_objects) == 0)
					{
						continue;
					}
					$result_file->override_result_objects($result_objects);

					array_push($compare_results, $result_file);
				}
			}

			if(count($compare_results) > 0)
			{
				$result_xml = pts_merge::merge_test_results_array($compare_results);

				if(count($compare_results) > 2)
				{
					$result_file = new pts_result_file($result_xml);
					$result_objects = $result_file->get_result_objects();
					$system_count = $result_file->get_system_count();
					$result_count = count($result_objects);
					$result_match_count = array();

					if($result_count > 3)
					{
						foreach($result_objects as $i => &$result_object)
						{
							$result_match_count[$i] = $result_object->test_result_buffer->get_count();
						}

						arsort($result_match_count);
						$biggest_size = pts_arrays::first_element($result_match_count);
						if($biggest_size == $system_count || $biggest_size > 3)
						{
							foreach($result_match_count as $key => $value)
							{
								if($value < 2)
								{
									unset($result_objects[$key]);
								}
							}
						}

						$result_file->override_result_objects($result_objects);
						$result_xml = pts_merge::merge_test_results_array(array($result_file));
					}
				}

				pts_client::save_test_result('auto-comparison/composite.xml', $result_xml);
			}
		}

		pts_test_installer::standard_install(array('auto-comparison'));
		pts_test_run_manager::standard_run(array('auto-comparison'));
	}
	protected static function system_component_to_format(&$system_info, &$to_array, $component_types)
	{
		foreach($component_types as $component_type)
		{
			if(isset($system_info[$component_type]))
			{
				$value = pts_strings::trim_search_query($system_info[$component_type]);

				if($value != null)
				{
					array_push($to_array, $component_type . ':' . $value);
				}
			}
		}
	}
}

?>
