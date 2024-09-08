<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2019 - 2023, Phoronix Media
	Copyright (C) 2019 - 2023, Michael Larabel

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
ini_set('memory_limit', '16G');

class dump_ob_to_ae_db implements pts_option_interface
{
	const doc_skip = true;
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
		pts_file_io::recursively_find_files_in_directory($dir_to_recursively_scan, $xml_files, '.xml', array('embed'));
		$system_logs = array();

		foreach($xml_files as $file)
		{
			$result_reference = null; // TODO fill in OpenBenchmarking.org ID for that
			$rf = new pts_result_file($file, false, true);
			$systems = $rf->get_systems();
			$system_data = array();
			$timestamps = array();
			$system_types = array();
			$orig = array();
			foreach($systems as $system)
			{
				$orig[$system->get_identifier()] = array_merge(pts_result_file_analyzer::system_component_string_to_array($system->get_hardware()), pts_result_file_analyzer::system_component_string_to_array($system->get_software()));
				$system_data[$system->get_identifier()] = array_map(array('pts_strings', 'trim_search_query_leave_hdd_size'), $orig[$system->get_identifier()]);
				$timestamps[$system->get_identifier()] = strtotime($system->get_timestamp());
				$system_types[$system->get_identifier()] = phodevi_base::determine_system_type($system->get_hardware(), $system->get_software());

				if(isset($system_data[$system->get_identifier()]['System Layer']) && !empty($system_data[$system->get_identifier()]['System Layer']))
				{
					continue;
				}

				if(isset($system_data[$system->get_identifier()]['Processor']) && (!phodevi::is_fake_device($system_data[$system->get_identifier()]['Processor']) || stripos($system_data[$system->get_identifier()]['Processor'], 'AmpereOne') !== false))
				{
					if(stripos($system_data[$system->get_identifier()]['Processor'], 'ARMv') !== false || stripos($system_data[$system->get_identifier()]['Processor'], 'POWER9') !== false || stripos($system_data[$system->get_identifier()]['Processor'], 'AmpereOne') !== false)
					{
						if(($cores = $system->get_cpu_core_count()) != false && $cores > 1 && stripos($system_data[$system->get_identifier()]['Processor'], 'Core') === false)
						{
							$system_data[$system->get_identifier()]['Processor'] .= ' ' . $cores . '-Core';
						}
					}
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
							
							if(!isset($system_logs['Processor'][$processor][$file]))
							{
								$system_logs['Processor'][$processor][$file] = array();
							}
							pts_arrays::popularity_tracker($system_logs['Processor'][$processor][$file], $log_file);
						}
					}
					if(($cores = $system->get_cpu_core_count()) != false && $cores > 1)
					{
						if(!isset($system_logs['Processor'][$processor]['core-count']))
						{
							$system_logs['Processor'][$processor]['core-count'] = array();
						}
						pts_arrays::popularity_tracker($system_logs['Processor'][$processor]['core-count'], $cores);
					}
					if(($threads = $system->get_cpu_thread_count()) != false && $threads > 1 && $threads > $cores)
					{
						if(!isset($system_logs['Processor'][$processor]['thread-count']))
						{
							$system_logs['Processor'][$processor]['thread-count'] = array();
						}
						pts_arrays::popularity_tracker($system_logs['Processor'][$processor]['thread-count'], $threads);
					}
					if(($v = $system->get_cpu_clock()) != false)
					{
						if(!isset($system_logs['Processor'][$processor]['cpu-clock']))
						{
							$system_logs['Processor'][$processor]['cpu-clock'] = array();
						}
						pts_arrays::popularity_tracker($system_logs['Processor'][$processor]['cpu-clock'], $v);
					}
					$system_logs['Processor'][$processor]['occurences'] = (isset($system_logs['Processor'][$processor]['occurences']) ? $system_logs['Processor'][$processor]['occurences'] : 0) + 1;
				}
				if(isset($system_data[$system->get_identifier()]['Graphics']) && !phodevi::is_fake_device($system_data[$system->get_identifier()]['Graphics']))
				{
					$graphics = $system_data[$system->get_identifier()]['Graphics'];
					if(!isset($system_logs['Graphics'][$graphics]))
					{
						$system_logs['Graphics'][$graphics] = array();
					}
					foreach(array('glxinfo', 'vulkaninfo', 'clinfo') as $file)
					{
						$log_file = $system->log_files($file);
						if($log_file && !empty($log_file))
						{
							if(!isset($system_logs['Graphics'][$graphics][$file]))
							{
								$system_logs['Graphics'][$graphics][$file] = array();
							}
							pts_arrays::popularity_tracker($system_logs['Graphics'][$graphics][$file], $log_file);
						}
					}
					$system_logs['Graphics'][$graphics]['occurences'] = (isset($system_logs['Graphics'][$graphics]['occurences']) ? $system_logs['Graphics'][$graphics]['occurences'] : 0) + 1;
				}
				if(isset($system_data[$system->get_identifier()]['Motherboard']) && !phodevi::is_fake_device($system_data[$system->get_identifier()]['Motherboard']))
				{
					$mobo = $system_data[$system->get_identifier()]['Motherboard'];
					if(!isset($system_logs['Motherboard'][$mobo]))
					{
						$system_logs['Motherboard'][$mobo] = array();
					}
					foreach(array('lspci') as $file)
					{
						$log_file = $system->log_files($file);
						if($log_file && !empty($log_file))
						{
							if(!isset($system_logs['Motherboard'][$mobo][$file]))
							{
								$system_logs['Motherboard'][$mobo][$file] = array();
							}
							pts_arrays::popularity_tracker($system_logs['Motherboard'][$mobo][$file], $log_file);
						}
					}
					$system_logs['Motherboard'][$mobo]['occurences'] = (isset($system_logs['Motherboard'][$mobo]['occurences']) ? $system_logs['Motherboard'][$mobo]['occurences'] : 0) + 1;
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
						if(strpos($args_desc, ' GPU') || strpos($args_desc, 'GPU ') || strpos($args_desc, ' CUDA') || strpos($args_desc, ' OptiX') || strpos($args_desc, ' OpenCL') || strpos($args_desc, 'SYCL'))
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
						else if(strpos($args_desc, ' CPU'))
						{
							$hw_type = 'Processor';
						}
						else if($hw_type == 'Network' && (strpos($args_desc, 'localhost') || strpos($args_desc, '127.0.0.1')))
						{
							// loopback / local test so network adapter really not important, moreso the system/CPU
							$hw_type = 'System';
						}
						else if($hw_type == 'Other' || $hw_type == 'OS')
						{
							continue;
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

						// Don't report run-time if forced lower than expected...
						$time_consumed = count($buffer_item->get_run_times()) < $ro->test_profile->get_default_times_to_run() ? 0 : $buffer_item->get_run_time_total();
						$stddev = 0;
						if(($raws = $buffer_item->get_result_raw_array()) && count($raws) > 1)
						{
							$stddev_calc = pts_math::percent_standard_deviation($raws);
							if($stddev_calc > 0)
							{
								$stddev = round($stddev_calc, 2);
							}
						}
						$arch = '';
						if(isset($orig[$system_identifier]['Kernel']) && !empty($orig[$system_identifier]['Kernel']))
						{
							$kernel = $orig[$system_identifier]['Kernel'];
							if(($x = strrpos($kernel, '(')) !== false)
							{
								$kernel = substr($kernel, $x + 1);
								if(($x = strpos($kernel, ')')) !== false)
								{
									$arch = substr($kernel, 0, $x);
									if(strpos($arch, '86') === false && stripos($arch, 'amd64') === false && isset($system_data[$system_identifier]['Processor']) && !empty($system_data[$system_identifier]['Processor']) && strpos($system_data[$system_identifier]['Processor'], 'Unknown') === false)
									{
										$arch .= '=' . pts_strings::trim_search_query($system_data[$system_identifier]['Processor']);
									}
								}
							}
						}
						$component_value = $system_data[$system_identifier][$component];
						$related_component_value = isset($system_data[$system_identifier][$related_component]) ? $system_data[$system_identifier][$related_component] : null;
						$ae->insert_result_into_analytic_results($comparison_hash, $result_reference, $component_value, $component, $related_component_value, $related_component, $result, $timestamps[$system_identifier], $system_types[$system_identifier], $system_layer, $time_consumed, $stddev, $arch);
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
		foreach(array_keys($system_logs) as $category)
		{
			foreach(array_keys($system_logs[$category]) as $component)
			{
				foreach($system_logs[$category][$component] as $item => &$value)
				{
					if(is_array($value) && isset($value[0]['popularity']))
					{
						$most_popular = pts_arrays::get_most_popular_from_tracker($value);
						$system_logs[$category][$component][$item] = $most_popular;
					}
				}
			}
		}
		$ae->append_to_component_data($system_logs);
	}
}

?>
