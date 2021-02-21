<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2017 - 2020, Phoronix Media
	Copyright (C) 2017 - 2020, Michael Larabel

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
		self::show_post_run_ob_percentile_summary();
	}
	protected static function request_compare($result_object, $system_type)
	{
		$result_file = null;

		if(pts_network::internet_support_available())
		{
			$comparison_hash = $result_object->get_comparison_hash();
			$result_file = self::request_compare_from_ob($result_object, $comparison_hash, $system_type);
		}

		if(empty($result_file))
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
	protected static function request_compare_from_ob(&$result_object, $comparison_hash, $system_type)
	{
		$terminal_width = pts_client::terminal_width();
		if(!pts_network::internet_support_available() || self::$response_time > 15 || $terminal_width < 52)
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
		$json_response = json_decode($json_response, true);

		if(is_array($json_response))
		{
			$other_data_in_result_file = array();
			$active_result = is_object($result_object->active) ? $result_object->active->get_result() : null;
			if(empty($active_result))
			{
				$v = $result_object->test_result_buffer->get_values();
				$active_result = array_pop($v);
			}
			foreach($result_object->test_result_buffer->get_buffer_items() as $buffer_item)
			{
				$other_data_in_result_file[$buffer_item->get_result_identifier()] = $buffer_item->get_result_value();
			}

			if(is_numeric($active_result) && $active_result > 0 && isset($json_response['openbenchmarking']['result']['ae']['percentiles']) && !empty($json_response['openbenchmarking']['result']['ae']['percentiles']) && isset($json_response['openbenchmarking']['result']['ae']['samples']))
			{
				$percentiles = $json_response['openbenchmarking']['result']['ae']['percentiles'];
				$sample_count = $json_response['openbenchmarking']['result']['ae']['samples'];
				$first_appeared = $json_response['openbenchmarking']['result']['ae']['first_appeared'];

				if(empty($first_appeared) || !is_numeric($first_appeared) || $first_appeared < 1298678400)
				{
					// OpenBenchmarking launch date so anything below that would be incorrect timing
					$first_appeared = strtotime('2011-02-26');
				}

				if($first_appeared > (time() - (86400 * 270)))
				{
					// If data is less than 9 months or so, don't bother putting year
					$first_appeared = date('j F', $first_appeared);
				}
				else
				{
					$first_appeared = date('j F Y', $first_appeared);
				}

				$box_plot = str_repeat(' ', $terminal_width - 4);
				$box_plot_size = strlen($box_plot);
				$box_plot = str_split($box_plot);
				$max_value = max(array_pop($percentiles), $active_result);
				if($result_object->test_profile->get_result_proportion() == 'HIB')
				{
					$max_value = $max_value * 1.02;
				}
				$results_at_pos = array(0, 1, ($box_plot_size - 1));

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
				$box_plot[min($whisker_end_char, ($box_plot_size - 1))] = '|';
				$box_plot[$box_middle] = '!';

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

				if($active_result < $max_value)
				{
					$box_plot_complement = array();
					for($i = 0; $i < 6; $i++)
					{
						$box_plot_complement[$i] = str_repeat(' ', $terminal_width - 4);
						$box_plot_complement[$i] = str_split($box_plot_complement[$i]);

					}

					$reference_results_added = 0;
					$this_percentile = pts_strings::number_suffix_handler($this_result_percentile);

					$rr = array();
					if(is_array($json_response['openbenchmarking']['result']['ae']['reference_results']))
					{
						$st = phodevi_base::determine_system_type(phodevi::system_hardware(), phodevi::system_software());
						foreach($json_response['openbenchmarking']['result']['ae']['reference_results'] as $component => $value)
						{
							$this_type = phodevi_base::determine_system_type($component, $component);
							if($this_type == $st)
							{
								$rr[$component] = $value;
								unset($json_response['openbenchmarking']['result']['ae']['reference_results'][$component]);
							}
						}
						foreach($json_response['openbenchmarking']['result']['ae']['reference_results'] as $component => $value)
						{
							$rr[$component] = $value;
						}
					}
					foreach(array_merge(array('This Result' . ($this_percentile > 0 && $this_percentile < 100 ? ' (' . $this_percentile . ' Percentile)' : null) => ($active_result > 99 ? round($active_result) : $active_result)), $other_data_in_result_file, $rr) as $component => $value)
					{
						if($value > $max_value)
						{
							continue;
						}
						$this_result_pos = round($value / $max_value * $box_plot_size);
						if(in_array($this_result_pos, $results_at_pos) || (strpos($component, 'This Result') === false && !in_array((isset($box_plot[$this_result_pos]) ? $box_plot[$this_result_pos] : null), array(' ', '-', '#'))))
						{
							continue;
						}

						$skip_result = false;
						foreach(array(' Sample', 'Confidential') as $avoid_strings)
						{
							// Extra protection
							if(stripos($component, $avoid_strings) !== false)
							{
								$skip_result = true;
								break;
							}
						}
						if($skip_result)
						{
							continue;
						}

						// Blocks other entries from overwriting or being immediately adjacent to one another
						$results_at_pos[] = $this_result_pos;
						$results_at_pos[] = $this_result_pos - 1;
						$results_at_pos[] = $this_result_pos + 1;

						if($terminal_width <= 80)
						{
							// Try to shorten up some components/identifiers if terminal narrow to fit in more data
							$component = str_replace(array('AMD ', 'Intel ', 'NVIDIA ', 'Radeon ', 'GeForce ', '  '), ' ', str_replace(' x ', ' x  ', $component));
							$component = str_replace('Ryzen Threadripper', 'Threadripper', $component);
							$component = trim($component);
						}

						foreach(array('-Core', ' with ') as $cutoff)
						{
							// On AMD product strings, trip the XX-Core from string to save space...
							// Similarly some "APU with Radeon" text also chop off
							if(($cc = strpos($component, $cutoff)) !== false)
							{
								$component = substr($component, 0, $cc);
								$component = substr($component, 0, strrpos($component, ' '));
							}
						}

						if(empty($component))
						{
							continue;
						}

						if($result_object->test_profile->get_result_proportion() == 'LIB')
						{
							$this_result_pos = $box_plot_size - $this_result_pos;
						}

						$string_to_show_length = strlen('^ ' . $component . ': ' . $value);

						if($this_result_pos - $string_to_show_length - 3 > 4)
						{
							// print to left
							$string_to_print = $component . ': ' . $value . ' ^';
							$write_pos = ($this_result_pos - strlen($string_to_print) + 1);
						}
						else if($this_result_pos + $string_to_show_length < ($terminal_width - 3))
						{
							// print to right of line
							$string_to_print = '^ ' . $component . ': ' . $value;
							$write_pos = $this_result_pos;
						}
						else
						{
							continue;
						}

						// validate no overwrites
						$complement_line = ($reference_results_added % 5);
						if($complement_line == 0 && strpos($component, 'This Result') === false)
						{
							$complement_line = 1;
						}
						$no_overwrites = true;
						for($i = $write_pos; $i < ($write_pos + $string_to_show_length) + 1 && isset($box_plot_complement[$complement_line][$i]); $i++)
						{
							if($box_plot_complement[$complement_line][$i] != ' ')
							{
								$no_overwrites = false;
								break;
							}
						}
						if($no_overwrites == false)
						{
							continue;
						}
						// end

						$brand_color = null;
						if(strpos($component, 'This Result') !== false)
						{
							$brand_color = 'cyan';
							$string_to_print = pts_client::cli_colored_text($string_to_print, 'cyan', true);
							$box_plot[$this_result_pos] = pts_client::cli_colored_text('X', 'cyan', true);
						}
						else if(in_array($component, array_keys($other_data_in_result_file)))
						{
							$string_to_print = pts_client::cli_colored_text($string_to_print, 'white', true);
						}
						else if(($brand_color = pts_render::identifier_to_brand_color($component, null)) != null)
						{
							$brand_color = pts_client::hex_color_to_string($brand_color);
							$string_to_print = pts_client::cli_colored_text($string_to_print, $brand_color, false);
						}

						for($i = $write_pos; $i < ($write_pos + $string_to_show_length) && $i < count($box_plot_complement[$complement_line]); $i++)
						{
							$box_plot_complement[$complement_line][$i] = '';
						}

						$box_plot_complement[$complement_line][$write_pos] = $string_to_print;
						$box_plot[$this_result_pos] = pts_client::cli_colored_text('*', $brand_color, false);
						$reference_results_added++;
					}

					echo PHP_EOL;
					echo '    Comparison to ' . pts_client::cli_just_bold(number_format($sample_count)) . ' OpenBenchmarking.org samples since ' . pts_client::cli_just_bold($first_appeared) . '; median result: ' . pts_client::cli_just_bold(round($percentiles[50], ($percentiles[50] < 100 ? 2 : 0))) . '. Box plot of samples:' . PHP_EOL;
					echo '    ' . implode('', $box_plot) . PHP_EOL;
					foreach($box_plot_complement as $line_r)
					{
						$line = rtrim(implode('', $line_r));
						if(!empty($line))
						{
							echo '    ' . $line . PHP_EOL;
						}
					}
					$rf = 2; // No reason to show OpenBenchmarking.org Dynamic Comparison as this display is arguably much better
				}
			}

			if($rf == null && isset($json_response['openbenchmarking']['result']['composite_xml']))
			{
				$composite_xml = $json_response['openbenchmarking']['result']['composite_xml'];
				if(!empty($composite_xml))
				{
					$result_file = new pts_result_file($composite_xml);
					$result_file->set_reference_id($json_response['openbenchmarking']['result']['public_id']);
					$rf = $result_file;
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

			if($auto_comparison_result_file instanceof pts_result_file)
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
