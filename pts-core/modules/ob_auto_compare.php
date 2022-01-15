<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2017 - 2022, Phoronix Media
	Copyright (C) 2017 - 2022, Michael Larabel

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

class ob_auto_compare extends pts_module_interface
{
	const module_name = 'OpenBenchmarking.org Auto Comparison';
	const module_version = '1.3.0';
	const module_description = 'This module prints comparable OpenBenchmarking.org results in the command-line for reference purposes as tests are being run. OpenBenchmarking.org is automatically queried for results to show based on the test comparison hash and the system type (mobile, desktop, server, cloud, workstation, etc). No other system information or result data is transmitted.';
	const module_author = 'Michael Larabel';

	private static $response_time = 0;
	protected static $current_result_file = null;

	private static $longest_args_string_length = 0;
	private static $archived_percentiles = array();

	public static function user_commands()
	{
		return array('debug' => 'debug_result_file');
	}
	public static function debug_result_file($r)
	{
		if(empty($r) || !pts_types::is_result_file($r[0]))
		{
			echo 'No result file supplied.';
			return;
		}
		$result_file = new pts_result_file($r[0]);
		self::$current_result_file = $r[0];
		foreach($result_file->get_result_objects() as $result_object)
		{
			echo trim($result_object->test_profile->get_title() . ' ' . $result_object->test_profile->get_app_version() . PHP_EOL . $result_object->get_arguments_description()) . PHP_EOL;
			echo 'COMPARISON HASH:' .  $result_object->get_comparison_hash(true, false) . PHP_EOL;
			echo 'SYSTEM TYPE: ' . phodevi_base::determine_system_type(phodevi::system_hardware(), phodevi::system_software()) . PHP_EOL;
			$auto_comparison_result_file = self::request_compare($result_object, phodevi_base::determine_system_type(phodevi::system_hardware(), phodevi::system_software()));

			if($auto_comparison_result_file && $auto_comparison_result_file instanceof pts_result_file)
			{
				$merge_ch = $auto_comparison_result_file->add_result($result_object);
				$ro =  $auto_comparison_result_file->get_result($merge_ch);
				$ro->sort_results_by_performance();
				$ro->test_result_buffer->buffer_values_reverse();
				echo pts_result_file_output::test_result_to_text($ro, 80, true, $result_file->get_system_identifiers());
				echo PHP_EOL . '     REFERENCE: ' . $auto_comparison_result_file->get_reference_id() . PHP_EOL;
			}

			echo PHP_EOL . PHP_EOL;
		}
		self::show_post_run_ob_percentile_summary();
	}
	protected static function request_compare($result_object, $system_type)
	{
		if(pts_client::terminal_width() < 50)
		{
			return false;
		}

		$result_file = false;
		$did_ob_comparison = self::request_compare_from_ob($result_object, $system_type);

		if(!$did_ob_comparison)
		{
			$comparison_hash = $result_object->get_comparison_hash(true, false);
			$result_file = self::request_compare_from_local_results($comparison_hash);
		}

		return $result_file;
	}
	protected static function request_compare_from_local_results($comparison_hash)
	{
		$saved_results = pts_results::saved_test_results();
		shuffle($saved_results);

		foreach($saved_results as $tr)
		{
			$result_file = new pts_result_file($tr, true, true);

			if(self::$current_result_file != null && self::$current_result_file == $result_file->get_identifier())
			{
				continue;
			}
			if($result_file->get_system_count() < 2)
			{
				continue;
			}

			if($result_file->get_result($comparison_hash) != false)
			{
				$result_file->set_reference_id($result_file->get_identifier());
				return $result_file;
			}
		}

		return null;
	}
	protected static function show_post_run_ob_percentile_summary()
	{
		// self::$archived_percentiles[$result_object->test_profile->get_test_hardware_type()][$result_object->test_profile->get_title()][$result_object->get_arguments_description()] = $this_result_percentile;
		if(!empty(self::$archived_percentiles))
		{
			$tab = '    ';
			echo PHP_EOL . $tab . pts_client::cli_colored_text('Percentile Classification Of Current Benchmark Run', 'blue', true) . PHP_EOL;
			foreach(self::$archived_percentiles as $subsystem => $results)
			{
				echo $tab . pts_client::cli_just_bold(strtoupper($subsystem)) . PHP_EOL;
				foreach($results as $test => $tests)
				{
					echo $tab . $tab . pts_client::cli_colored_text($test, 'cyan', true) . PHP_EOL;
					foreach($tests as $args => $values)
					{
						echo $tab . $tab . $tab . $args . (!empty($args) ? ':' : ' ') . ' ' . str_repeat(' ', self::$longest_args_string_length + 1 - strlen($args)) . pts_client::cli_just_bold(pts_strings::number_suffix_handler($values)) . PHP_EOL;
					}
				}
			}
			echo $tab . $tab . $tab . str_repeat(' ', self::$longest_args_string_length + 3) . pts_client::cli_just_italic('OpenBenchmarking.org Percentile') . PHP_EOL;
		}
	}
	protected static function request_compare_from_ob(&$result_object, $system_type)
	{
		$comparison_hash = $result_object->get_comparison_hash();
		$active_result = is_object($result_object->active) ? $result_object->active->get_result() : null;
		if(empty($active_result))
		{
			$v = $result_object->test_result_buffer->get_values();
			$active_result = array_pop($v);
		}
		if(!is_numeric($active_result) || $active_result == 0)
		{
			return false;
		}

		$ae_data = array();
		if(pts_network::internet_support_available() && self::$response_time < 15)
		{
			$ob_request_time = time();
			$json_response = pts_openbenchmarking::make_openbenchmarking_request('auto_compare_via_hash', array('comparison_hash' => $comparison_hash, 'system_type' => $system_type, 'test_profile' => $result_object->test_profile->get_identifier(false), 'comparison_hash_string' => $result_object->get_comparison_hash(true, false)));
			self::$response_time = time() - $ob_request_time;
			$json_response = json_decode($json_response, true);
			if(is_array($json_response) && isset($json_response['openbenchmarking']['result']['ae']))
			{
				$ae_data = &$json_response['openbenchmarking']['result']['ae'];
			}
		}
		else
		{
			// Recover from local results
			$ae_data = $result_object->test_profile->get_generated_data($result_object->get_comparison_hash(true, false));
		}

		if(!empty($ae_data))
		{
			$results_to_show = array();
			foreach($result_object->test_result_buffer->get_buffer_items() as $buffer_item)
			{
				if(is_numeric($buffer_item->get_result_value()))
				{
					$results_to_show[$buffer_item->get_result_identifier()] = $buffer_item->get_result_value();
				}
			}

			if($active_result > 0 && isset($ae_data['percentiles']) && !empty($ae_data['percentiles']))
			{
				$this_result_percentile = -1;
				foreach($ae_data['percentiles'] as $percentile => $v)
				{
					if($result_object->test_profile->get_result_proportion() == 'LIB')
					{
						if($v > $active_result)
						{
							$this_result_percentile = 100 - $percentile ;
							break;
						}
					}
					else if($v > $active_result)
					{
						$this_result_percentile = $percentile - 1;
						break;
					}
				}

				if($this_result_percentile > 0 && $this_result_percentile < 100)
				{
					self::$archived_percentiles[$result_object->test_profile->get_test_hardware_type()][$result_object->test_profile->get_title()][$result_object->get_arguments_description_shortened()] = $this_result_percentile;
					self::$longest_args_string_length = max(self::$longest_args_string_length, strlen($result_object->get_arguments_description_shortened()), strlen($result_object->test_profile->get_title()) - 3);
				}
			}

			// Show box plot
			if(isset($ae_data['percentiles']) && !empty($ae_data['percentiles']) && isset($ae_data['samples']))
			{
				pts_result_file_output::text_box_plut_from_ae($ae_data, $active_result, $results_to_show, $result_object);
				return true;
			}
		}

		return false;
	}
	public static function __pre_run_process($test_run_manager)
	{
		if(!$test_run_manager->is_interactive_mode())
		{
			return pts_module::MODULE_UNLOAD;
		}
		self::$current_result_file = $test_run_manager->get_file_name();
		self::$archived_percentiles = array();
	}
	public static function __event_post_run_stats($test_run_manager)
	{
		if($test_run_manager->result_file->get_system_count() == 1 && $test_run_manager->result_file->get_test_count() > 3 && !empty(self::$archived_percentiles))
		{
			self::show_post_run_ob_percentile_summary();
		}
	}
	public static function __test_run_success_inline_result($result_object)
	{
		// Passed is a copy of the successful pts_test_result after showing other inline metrics
		if($result_object->test_result_buffer->get_count() < 5)
		{
			$auto_comparison_result_file = self::request_compare($result_object, phodevi_base::determine_system_type(phodevi::system_hardware(), phodevi::system_software()));

			if($auto_comparison_result_file && $auto_comparison_result_file instanceof pts_result_file)
			{
				$merge_ch = $auto_comparison_result_file->add_result($result_object);
				$ro =  $auto_comparison_result_file->get_result($merge_ch);
				$ro->sort_results_by_performance();
				$ro->test_result_buffer->buffer_values_reverse();
				$is_ob_comparison = pts_openbenchmarking::is_string_openbenchmarking_result_id_compliant($auto_comparison_result_file->get_reference_id());
				echo PHP_EOL.pts_client::cli_just_bold('    ' . ($is_ob_comparison ? 'OpenBenchmarking.org ' : '') . 'Dynamic Comparison: ');
				echo pts_result_file_output::test_result_to_text($ro, pts_client::terminal_width(), true, $result_object->test_result_buffer->get_identifiers(), false);
				echo PHP_EOL . pts_client::cli_just_bold('    Result Perspective:') . ' ' . ($is_ob_comparison ? 'https://openbenchmarking.org/result/' : '') . $auto_comparison_result_file->get_reference_id() . PHP_EOL;
			}
		}
	}

}
?>
