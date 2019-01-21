<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2017 - 2019, Phoronix Media
	Copyright (C) 2017 - 2019, Michael Larabel

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
	const module_version = '1.2.0';
	const module_description = 'This module prints comparable OpenBenchmarking.org results in the command-line for reference purposes as tests are being run. OpenBenchmarking.org is automatically queried for results to show based on the test comparison hash and the system type (mobile, desktop, server, cloud, workstation, etc). No other system information or result data is transmitted..';
	const module_author = 'Michael Larabel';

	private static $response_time = 0;
	protected static $current_result_file = null;

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

			if($auto_comparison_result_file instanceof pts_result_file)
			{
				$merge_ch = $auto_comparison_result_file->add_result($result_object);
				$ro =  $auto_comparison_result_file->get_result($merge_ch);
				$ro->sort_results_by_performance();
				$ro->test_result_buffer->buffer_values_reverse();
				echo pts_result_file_output::test_result_to_text($ro, 80, true, $result_file->get_system_identifiers());
				echo PHP_EOL . '     REFERENCE: ' . $auto_comparison_result_file->get_reference_id() . PHP_EOL;
			}
			else
			{
				echo 'NO MATCHES';
			}

			echo PHP_EOL . PHP_EOL;
		}
	}
	protected static function request_compare(&$result_object, $system_type)
	{
		$result_file = null;
//XXX reset check
		if(false) // default to see if local comparison first
		{
			$comparison_hash = $result_object->get_comparison_hash(true, false);
			$result_file = self::request_compare_from_local_results($comparison_hash);
		}

		if(empty($result_file) && pts_network::internet_support_available())
		{
			$comparison_hash = $result_object->get_comparison_hash();
			$result_file = self::request_compare_from_ob($result_object, $comparison_hash, $system_type);
		}

		return $result_file;
	}
	protected static function request_compare_from_local_results($comparison_hash)
	{
		$saved_results = pts_client::saved_test_results();
		shuffle($saved_results);

		foreach($saved_results as $tr)
		{
			$result_file = new pts_result_file($tr);

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
	protected static function request_compare_from_ob(&$result_object, $comparison_hash, $system_type)
	{
		if(!pts_network::internet_support_available() || self::$response_time > 8)
		{
			// If no network or OB requests are being slow...
			return false;
		}

		$rf = null;
		$ob_request_time = time();
		$ch = $result_object->get_comparison_hash(true, false);
		$test_profile = $result_object->test_profile->get_identifier(false);
		$json_response = pts_openbenchmarking::make_openbenchmarking_request('auto_compare_via_hash', array('comparison_hash' => $comparison_hash, 'system_type' => $system_type, 'test_profile' => $test_profile, 'comparison_hash_string' => $ch));
		self::$response_time = time() - $ob_request_time;

//echo PHP_EOL . 'ch: ' . $ch . ' b: ' . base64_encode($test_profile) . PHP_EOL;
		$json_response = json_decode($json_response, true);

		if(is_array($json_response))
		{
			if(isset($json_response['openbenchmarking']['result']['composite_xml']))
			{
				$composite_xml = $json_response['openbenchmarking']['result']['composite_xml'];
				if(!empty($composite_xml))
				{
					$result_file = new pts_result_file($composite_xml);
					$result_file->set_reference_id($json_response['openbenchmarking']['result']['public_id']);
					$rf = $result_file;
				}
			}

			$active_result = is_object($result_object->active) ? $result_object->active->get_result() : null;
			if(is_numeric($active_result) && $active_result > 0 && isset($json_response['openbenchmarking']['result']['ae']['percentiles']) && !empty($json_response['openbenchmarking']['result']['ae']['percentiles']) && isset($json_response['openbenchmarking']['result']['ae']['samples']))
			{
//echo 2222 . PHP_EOL;
				$percentiles = $json_response['openbenchmarking']['result']['ae']['percentiles'];
				$sample_count = $json_response['openbenchmarking']['result']['ae']['samples'];

				$box_plot = str_repeat(' ', pts_client::terminal_width() - 4);
				$box_plot_size = strlen($box_plot);
				$box_plot = str_split($box_plot);
				$max_value = array_pop($percentiles);

				// BOX PLOT
				$whisker_bottom = $percentiles[2];
				$whisker_top = $percentiles[98];
				$whisker_start_char = round($whisker_bottom / $max_value * $box_plot_size);
				$whisker_end_char = round($whisker_top / $max_value * $box_plot_size);

				for($i = $whisker_start_char; $i <= $whisker_end_char && $i < ($box_plot_size - 1); $i++)
				{
					$box_plot[$i] = '-';
				}

				$box_left = floor(($percentiles[25] / $max_value) * $box_plot_size);
				$box_middle = round(($percentiles[50] / $max_value) * $box_plot_size);
				$box_right = ceil(($percentiles[75] / $max_value) * $box_plot_size);
				for($i = $box_left; $i <= $box_right; $i++)
				{
					$box_plot[$i] = '#';
				}
				$box_plot[$whisker_start_char] = '|';
				$box_plot[$whisker_end_char] = '|';
				$box_plot[$box_middle] = '!';

				$this_result = round($active_result / $max_value * $box_plot_size);
				$box_plot[$this_result] = pts_client::cli_colored_text('X', 'red', true);

				// END OF BOX PLOT
				if($result_object->test_profile->get_result_proportion() == 'LIB')
				{
					$box_plot = array_reverse($box_plot);
				}
				$box_plot[0] = '[';
				$box_plot[($box_plot_size - 1)] = ']';

				$this_result_percentile = -1;
				foreach($percentiles as $percentile => $v)
				{
					if($v < $active_result)
					{
						$this_result_percentile = $percentile - 1;
					}
				}
				if($result_object->test_profile->get_result_proportion() == 'LIB')
				{
					$this_result_percentile = 100 - $this_result_percentile;
				}

				if($active_result < $max_value)
				{
					echo '    ' . implode('', $box_plot) . PHP_EOL;

//var_dump($json_response['openbenchmarking']['result']['ae']['reference_results']);

					echo '    ' . pts_client::cli_just_italic('A score of ' . pts_client::cli_just_bold($active_result) . ' compared to ' . $sample_count . ' samples from OpenBenchmarking.org where the median result is ' . pts_client::cli_just_bold($percentiles[50]) . ' would put this run in the ' . pts_client::cli_just_bold(pts_strings::number_suffix_handler($this_result_percentile)) . ' percentile.') . PHP_EOL;
				}
			}
		}

		return $rf;
	}
	public static function __pre_run_process($test_run_manager)
	{
		if(!$test_run_manager->is_interactive_mode())
		{
			return pts_module::MODULE_UNLOAD;
		}
		self::$current_result_file = $test_run_manager->get_file_name();
	}
	public static function __test_run_success_inline_result($result_object)
	{
		// Passed is a copy of the successful pts_test_result after showing other inline metrics
		if($result_object->test_result_buffer->get_count() < 3)
		{
			$auto_comparison_result_file = self::request_compare($result_object, phodevi_base::determine_system_type(phodevi::system_hardware(), phodevi::system_software()));

			if($auto_comparison_result_file instanceof pts_result_file)
			{
				$merge_ch = $auto_comparison_result_file->add_result($result_object);
				$ro =  $auto_comparison_result_file->get_result($merge_ch);
				$ro->sort_results_by_performance();
				$ro->test_result_buffer->buffer_values_reverse();
				$is_ob_comparison = pts_openbenchmarking::is_string_openbenchmarking_result_id_compliant($auto_comparison_result_file->get_reference_id());
				echo PHP_EOL.pts_client::cli_just_bold('    ' . ($is_ob_comparison ? 'OpenBenchmarking.org ' : '') . 'Dynamic Comparison: ');
				echo pts_result_file_output::test_result_to_text($ro, pts_client::terminal_width(), true, $result_object->test_result_buffer->get_identifiers());
				echo PHP_EOL . pts_client::cli_just_bold('    Result Perspective:') . ' ' . ($is_ob_comparison ? 'https://openbenchmarking.org/result/' : '') . $auto_comparison_result_file->get_reference_id() . PHP_EOL;
			}
		}
	}

}
?>
