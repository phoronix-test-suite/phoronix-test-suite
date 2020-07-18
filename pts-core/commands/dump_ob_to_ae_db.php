<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2019 - 2020, Phoronix Media
	Copyright (C) 2019 - 2020, Michael Larabel

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
ini_set('memory_limit', '8G');

class dump_ob_to_ae_db implements pts_option_interface
{
	public static function run($r)
	{
		$dir_to_recursively_scan = $r[0];
		if(!is_dir($dir_to_recursively_scan))
		{
			echo $dir_to_recursively_scan . ' is not a dir.';
			return false;
		}
		$storage_dir = $r[1];
		if(!is_dir($storage_dir))
		{
			echo $storage_dir . ' is not a dir.';
			return false;
		}

		$ae = new pts_ae_data($storage_dir);
		$xml_files = array();
		pts_file_io::recursively_find_files_in_directory($dir_to_recursively_scan, $xml_files, '.xml');
		$system_logs = array();

		foreach($xml_files as $file)
		{
			$result_reference = null; // TODO fill in OpenBenchmarking.org ID for that
			$rf = new pts_result_file($file, false, true);
			$systems = $rf->get_systems();
			$system_data = array();
			$timestamps = array();
			$system_types = array();
			foreach($systems as $system)
			{
				$system_data[$system->get_identifier()] = array_map(array('pts_strings', 'trim_search_query'), array_merge(pts_result_file_analyzer::system_component_string_to_array($system->get_hardware()), pts_result_file_analyzer::system_component_string_to_array($system->get_software())));
				$timestamps[$system->get_identifier()] = strtotime($system->get_timestamp());
				$system_types[$system->get_identifier()] = phodevi_base::determine_system_type($system->get_hardware(), $system->get_software());

				if(isset($system_data[$system->get_identifier()]['Processor']) && !phodevi::is_fake_device($system_data[$system->get_identifier()]['Processor']))
				{
					$processor = $system_data[$system->get_identifier()]['Processor'];
					if(!isset($system_logs['Processor'][$processor]))
					{
						$system_logs['Processor'][$processor] = array();
					}
					foreach(array('cpuinfo', 'lscpu') as $file)
					{
						$log_file = $system->log_files($file);
						if($log_file && !empty($log_file))
						{
							if(($x = strpos($log_file, PHP_EOL . PHP_EOL)) !== false)
							{
								$log_file = substr($log_file, 0, $x);
							}
							
							if(!isset($system_logs['Processor'][$processor][$file]) || strlen($log_file) > strlen($system_logs['Processor'][$processor][$file]))
							{
								$system_logs['Processor'][$processor][$file] = $log_file;
							}
						}
					}
					if(($v = $system->get_cpu_core_count()) != false)
					{
						if(!isset($system_logs['Processor'][$processor]['core-count']) || $v > $system_logs['Processor'][$processor]['core-count'])
						{
							$system_logs['Processor'][$processor]['core-count'] = $v;
						}
					}
					if(($v = $system->get_cpu_thread_count()) != false)
					{
						if(!isset($system_logs['Processor'][$processor]['thread-count']) || $v > $system_logs['Processor'][$processor]['thread-count'])
						{
							$system_logs['Processor'][$processor]['thread-count'] = $v;
						}
					}
					if(($v = $system->get_cpu_clock()) != false)
					{
						if(!isset($system_logs['Processor'][$processor]['cpu-clock']) || $v > $system_logs['Processor'][$processor]['cpu-clock'])
						{
							$system_logs['Processor'][$processor]['cpu-clock'] = $v;
						}
					}
					$system_logs['Processor'][$processor]['occurences'] = (isset($system_logs['Processor'][$processor]['occurences']) ? $system_logs['Processor'][$processor]['occurences'] : 0) + 1;
				}
			}

			foreach($rf->get_result_objects() as $ro)
			{
				if($ro->test_profile->get_identifier() == null)
				{
					continue;
				}
				$comparison_hash = $ro->get_comparison_hash(true, false);
				$inserts = 0;
				foreach($ro->test_result_buffer as &$buffers)
				{
					if(empty($buffers))
						continue;

					foreach($buffers as &$buffer_item)
					{
						$result = $buffer_item->get_result_value();
						if(stripos($result, ',') !== false || !is_numeric($result))
						{
							continue;
						}
						$system_identifier = $buffer_item->get_result_identifier();
						$system_layer = isset($system_data[$system_identifier]['System Layer']) ? $system_data[$system_identifier]['System Layer'] : null;


						$hw_type = $ro->test_profile->get_test_hardware_type();
						$args_desc = $ro->get_arguments_description();

						// Since some tests could stress multiple subsystems, see what the argument descriptions string says
						if(strpos($args_desc, ' GPU') || strpos($args_desc, ' CUDA') || strpos($args_desc, ' OpenCL'))
						{
							$hw_type = 'Graphics';
						}
						else if(strpos($args_desc, ' RAM') || (strpos($args_desc, ' Memory') && strpos($args_desc, 'Hash Memory') === false ))
						{
							$hw_type = 'Memory';
						}
						else if(strpos($args_desc, ' Disk'))
						{
							$hw_type = 'Disk';
						}
						else if($hw_type == 'Network' && (strpos($args_desc, 'localhost') || strpos($args_desc, '127.0.0.1')))
						{
							// loopback / local test so network adapter really not important, moreso the system/CPU
							$hw_type = 'System';
						}

						switch($hw_type)
						{
							case 'Processor':
								$component = 'Processor';
								$related_component = 'OS';
								break;
							case 'System':
								$component = 'Processor';
								$related_component = 'Motherboard';
								break;
							case 'Graphics':
								$component = 'Graphics';
								$related_component = 'OpenGL';
								break;
							case 'Disk':
								$component = 'Disk';
								$related_component = 'File-System';
								break;
							case 'Network':
								$component = 'Processor';
							// TODO XXX	$component = 'Network';
								$related_component = 'OS';
								break;
							case 'Memory':
								$component = 'Memory';
								$related_component = 'Processor';
								break;
							default:
								$component = 'Processor';
								$related_component = 'OS';
								break;
						}

						if(!isset($system_data[$system_identifier][$component]) || empty($system_data[$system_identifier][$component]))
						{
							continue;
						}
						$component_value = $system_data[$system_identifier][$component];
						$related_component_value = isset($system_data[$system_identifier][$related_component]) ? $system_data[$system_identifier][$related_component] : null;
						$ae->insert_result_into_analytic_results($comparison_hash, $result_reference, $component_value, $component, $related_component_value, $related_component, $result, $timestamps[$system_identifier], $system_types[$system_identifier], $system_layer);
						$inserts++;
					}

				}

				if($inserts > 0)
				{
					$ae->insert_composite_hash_entry_by_result_object($comparison_hash, $ro);
				}
			}
			
		}

		$ae->rebuild_composite_listing();
		$ae->append_to_component_data($system_logs);
	}
}

?>
