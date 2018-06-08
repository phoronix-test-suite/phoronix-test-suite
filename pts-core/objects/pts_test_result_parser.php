<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2018, Phoronix Media
	Copyright (C) 2010 - 2018, Michael Larabel

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

class pts_test_result_parser
{
	private static $supported_sensors = null;
	private static $monitoring_sensors = array();

	protected static function gen_result_active_handle(&$root_result, $test_result = null)
	{
		if($test_result == null)
		{
			$test_result = clone $root_result;
		}

		$tr_hash = $test_result->get_comparison_hash(true, false);
		if(!isset($root_result->generated_result_buffers[$tr_hash]))
		{
			$test_result->active = new pts_test_result_buffer_active();
			$root_result->generated_result_buffers[$tr_hash] = $test_result;
		}

		return $root_result->generated_result_buffers[$tr_hash]->active;
	}
	public static function system_monitor_task_check(&$test_run_request)
	{
		$definitions = $test_run_request->test_profile->get_results_definition('SystemMonitor');
		self::$monitoring_sensors = array();
		$test_directory = $test_run_request->test_profile->get_install_dir();

		foreach($definitions->get_system_monitor_definitions() as $sys_monitor)
		{
			$sensor = $sys_monitor->get_sensor();
			$polling_freq = $sys_monitor->get_polling_frequency();
			$report_as = $sys_monitor->get_report();

			// TODO: Right now we are looping through SystemMonitor tags, but right now pts-core only supports providing one monitor sensor as the result
			$sensor = explode('.', $sensor);

			if($sensor == array('sys', 'time'))
			{
				// sys.time is a special case since we are just timing the test length and thus don't need to fork the thread
				$start_time = microtime(true);
				self::$monitoring_sensors[] = array(0, $sensor, null, $start_time);
				continue;
			}

			if(self::$supported_sensors == null)
			{
				// Cache this since this shouldn't change between tests/runs
				self::$supported_sensors = phodevi::supported_sensors();
			}

			if(count($sensor) != 2 || !in_array($sensor, self::$supported_sensors))
			{
				// Not a sensor or it's not supported
				pts_client::test_profile_debug_message('No supported sensor found');
				continue;
			}

			if(!is_numeric($polling_freq) || $polling_freq < 0.5)
			{
				pts_client::test_profile_debug_message('No polling frequency defined, defaulting to 2 seconds');
				$polling_freq = 2;
			}

			$polling_freq *= 1000000; // Convert from seconds to micro-seconds
			if(!in_array($report_as, array('ALL', 'MAX', 'MIN', 'AVG')))
			{
				// Not a valid reporting type
				pts_client::test_profile_debug_message('No valid Report entry found.');
				continue;
			}
			if(!function_exists('pcntl_fork'))
			{
				pts_client::$display->test_run_instance_error('PHP with PCNTL support enabled is required for this test.');
				return false;
			}

			$monitor_file = tempnam($test_directory, '.monitor');
			$pid = pcntl_fork();

			if($pid != -1)
			{
				if($pid)
				{
					// Main process still
					self::$monitoring_sensors[] = array($pid, $sensor, $report_as, $monitor_file);
					continue;
				}
				else
				{
					$sensor_values = array();
					while(is_file($monitor_file)) // when test ends, it will remove file in case the posix_kill doesn't happen
					{
						$sensor_values[] = phodevi::read_sensor($sensor);
						file_put_contents($monitor_file, implode("\n", $sensor_values));
						usleep($polling_freq);
					}
					exit(0);
				}
			}
		}

		return count(self::$monitoring_sensors) > 0;
	}
	public static function system_monitor_task_post_test(&$test_run_request, $exit_status_pass = true)
	{
		$test_directory = $test_run_request->test_profile->get_install_dir();
		$did_post_result = false;

		foreach(self::$monitoring_sensors as $sensor_r)
		{
			if($sensor_r[1] == array('sys', 'time'))
			{
				// sys.time is a special case
				$end_time = microtime(true);

				// Delta time
				$result_value = $end_time - $sensor_r[3];

				$minimal_test_time = pts_config::read_user_config('PhoronixTestSuite/Options/TestResultValidation/MinimalTestTime', 2);
				if($result_value < $minimal_test_time)
				{
					// The test ended too fast
					pts_client::test_profile_debug_message('Test Run-Time Too Short: ' . $result_value);
					$result_value = null;
				}
			}
			else
			{
				// Kill the sensor monitoring thread
				if(function_exists('posix_kill') == false)
				{
					pts_client::$display->test_run_error('The PHP POSIX extension is required for this test.');
					return $did_post_result;
				}

				posix_kill($sensor_r[0], SIGTERM);

				$sensor_values = explode("\n", pts_file_io::file_get_contents($sensor_r[3]));
				pts_file_io::unlink($sensor_r[3]);

				if(count($sensor_values) == 0)
				{
					continue;
				}

				switch($sensor_r[2])
				{
					case 'MAX':
						$result_value = max($sensor_values);
						break;
					case 'MIN':
						$result_value = min($sensor_values);
						break;
					case 'AVG':
						$result_value = array_sum($sensor_values) / count($sensor_values);
						break;
					case 'ALL':
						$result_value = implode(',', $sensor_values);
						break;
					default:
						$result_value = null;
						break;
				}
			}

			if($result_value != null && $result_value > 0 && $exit_status_pass)
			{
				// For now it's only possible to return one result per test XXX actually with PTS7 this can be changed....
				// TODO XXX for some sensors may make sense for min/max values?
				pts_client::test_profile_debug_message('Test Result Montioring Process Returning: ' . $result_value);
				self::gen_result_active_handle($test_run_request)->add_trial_run_result($result_value);
				$did_post_result = true;
			}
		}

		return $did_post_result;
	}
	public static function parse_result(&$test_run_request, $test_log_file)
	{
		$produced_result = false;
		if($test_run_request->test_profile->get_file_parser_spec() == false)
		{
			return $produced_result;
		}

		$extra_arguments = $test_run_request->get_arguments();
		$pts_test_arguments = trim($test_run_request->test_profile->get_default_arguments() . ' ' . str_replace($test_run_request->test_profile->get_default_arguments(), '', $extra_arguments) . ' ' . $test_run_request->test_profile->get_default_post_arguments());

		switch($test_run_request->test_profile->get_display_format())
		{
			case 'IMAGE_COMPARISON':
				$produced_result = self::parse_iqc_result($test_run_request, $test_log_file, $pts_test_arguments, $extra_arguments);
				break;
			case 'PASS_FAIL':
			case 'MULTI_PASS_FAIL':
			case 'BAR_GRAPH':
			default:
				$produced_result = self::parse_result_process($test_run_request, $test_log_file, $pts_test_arguments, $extra_arguments);
				break;
		}
		return $produced_result;
	}
	public static function generate_extra_data(&$test_result, &$test_log_file = null)
	{
		$definitions = $test_result->test_profile->get_results_definition('ExtraData');
		foreach($definitions->get_system_monitor_definitions() as $entry)
		{
			$frame_all_times = array();
			switch($entry->get_identifier())
			{
				case 'libframetime-output':
					// libframetime output
					$line_values = explode(PHP_EOL, file_get_contents($test_log_file));
					if(!empty($line_values) && isset($line_values[3]))
					{
						foreach($line_values as &$v)
						{
							if(substr($v, -3) == ' us' && substr($v, 0, 10) == 'Frametime ')
							{
								$frametime = substr($v, 10);
								$frametime = substr($frametime, 0, -3);
								if($frametime > 2000)
								{
									$frametime = $frametime / 1000;
									$frame_all_times[] = $frametime;
								}
							}
						}
						$frame_all_times = pts_math::remove_outliers($frame_all_times);
					}
					break;
				case 'csv-dump-frame-latencies':
					// Civ Beyond Earth
					$csv_values = explode(',', pts_file_io::file_get_contents($test_log_file));
					if(!isset($csv_values[10]))
					{
						$csv_values = explode(PHP_EOL, pts_file_io::file_get_contents($test_log_file));
					}
					if(!empty($csv_values) && isset($csv_values[3]))
					{
						foreach($csv_values as $i => &$v)
						{
							if(!is_numeric($v))
							{
								unset($csv_values[$i]);
								continue;
							}
							$frame_all_times[] = $v;
						}
					}
					break;
				case 'com-speeds-frame-latency-totals':
					// id Tech Games
					$log_file = pts_file_io::file_get_contents($test_log_file);
					$frame_all_times = array();
					while(($log_file = strstr($log_file, 'frame:')))
					{
						if(($a = strpos($log_file, ' all: ')) !== false && $a < strpos($log_file, "\n"))
						{
							$all = ltrim(substr($log_file, $a + 6));
							$all = substr($all, 0, strpos($all, ' '));
							if(is_numeric($all) && $all > 0)
							{
								$frame_all_times[] = $all;
							}
						}
						$log_file = strstr($log_file, 'bk:');
					}
					break;
				case 'cpu-frames-space-delimited':
					// HITMAN on Linux uses at least this method
					$log_file = pts_file_io::file_get_contents($test_log_file);
					$frame_all_times = array();
					if(($x = strpos($log_file, '---- CPU FRAMES ----')) !== false)
					{
						$log_file = trim(str_replace(PHP_EOL, ' ', substr($log_file, $x + strlen('---- CPU FRAMES ----'))));
						foreach(explode(' ', $log_file) as $inp)
						{
							if(is_numeric($inp) && $inp > 0)
							{
								$frame_all_times[] = round(1000 / $inp, 3); // since its reporting current frame
							}
						}
					}
				case 'csv-individual-frame-times':
					// Thrones of Britannia on Linux uses at least this method
					$log_file = pts_file_io::file_get_contents($test_log_file);
					$frame_all_times = array();
					if(($x = strpos($log_file, 'individual frame times (ms):')) !== false)
					{
						$log_file = trim(str_replace(PHP_EOL, ' ', substr($log_file, $x + strlen('individual frame times (ms): '))));
						foreach(explode(', ', $log_file) as $inp)
						{
							if(is_numeric($inp) && $inp > 0)
							{
								$frame_all_times[] = $inp;
							}
							else
							{
								// hitting the end
								break;
							}
						}
					}
					break;
			}
			if(isset($frame_all_times[60]))
			{
				// Take off the first frame as it's likely to have taken much longer when game just starting off...
				// array_shift($frame_all_times);
				$tp = clone $test_result->test_profile;
				$tp->set_result_scale('Milliseconds');
				$tp->set_result_proportion('LIB');
				$tp->set_display_format('LINE_GRAPH');
				$tp->set_identifier(null);
				$extra_result = new pts_test_result($tp);
				$extra_result->active = new pts_test_result_buffer_active();
				$extra_result->set_used_arguments_description($test_result->get_arguments_description() . ' - Total Frame Time');
				$extra_result->set_used_arguments($test_result->get_arguments() . ' - ' . $extra_result->get_arguments_description()); // this formatting is weird but to preserve pre-PTS7 comparsions of extra results
				$extra_result->active->set_result(implode(',', $frame_all_times));
				self::gen_result_active_handle($test_result, $extra_result)->add_trial_run_result(implode(',', $frame_all_times));
				//$extra_result->set_used_arguments(phodevi::sensor_name($sensor) . ' ' . $test_result->get_arguments());
			}
		}
	}
	protected static function parse_iqc_result(&$test_run_request, $log_file, $pts_test_arguments, $extra_arguments)
	{
		if(!extension_loaded('gd'))
		{
			// Needs GD library to work
			return false;
		}

		$returns = false;
		$definitions = $test_run_request->test_profile->get_results_definition('ImageParser');
		foreach($definitions->get_system_monitor_definitions() as $entry)
		{
			$match_test_arguments = $entry->get_match_to_image_args();
			if(!empty($match_test_arguments) && strpos($pts_test_arguments, $match_test_arguments) === false)
			{
				// This is not the ResultsParser XML section to use as the MatchToTestArguments does not match the PTS test arguments
				continue;
			}
			$iqc_source_file = $entry->get_source_image();
			if(is_file($test_run_request->test_profile->get_install_dir() . $iqc_source_file))
			{
				$iqc_source_file = $test_run_request->test_profile->get_install_dir() . $iqc_source_file;
			}
			else
			{
				// No image file found
				continue;
			}
			$img = pts_image::image_file_to_gd($iqc_source_file);

			if($img == false)
			{
				return;
			}

			$img_sliced = imagecreatetruecolor($entry->get_image_width(), $entry->get_image_height());
			imagecopyresampled($img_sliced, $img, 0, 0, $entry->get_image_x(), $entry->get_image_y(), $entry->get_image_width(), $entry->get_image_height(), $entry->get_image_width(), $entry->get_image_height());
			$test_result = $test_run_request->test_profile->get_install_dir() . 'iqc.png';
			imagepng($img_sliced, $test_result);
			if($test_result != false)
			{
				self::gen_result_active_handle($test_run_request)->add_trial_run_result($test_result);
				$returns = true;
			}
		}

		return $returns;
	}
	protected static function parse_result_process(&$test_run_request, $log_file, $pts_test_arguments, $extra_arguments, $prefix = null)
	{
		$produced_result = false;
		$is_pass_fail_test = in_array($test_run_request->test_profile->get_display_format(), array('PASS_FAIL', 'MULTI_PASS_FAIL'));
		$is_numeric_check = !$is_pass_fail_test;

		if($prefix != null && substr($prefix, -1) != '_')
		{
			$prefix .= '_';
		}

		$definitions = $test_run_request->test_profile->get_results_definition('ResultsParser');
		foreach($definitions->get_result_parser_definitions() as $entry)
		{
			$tr = clone $test_run_request;
			$test_result = self::parse_result_process_entry($tr, $log_file, $pts_test_arguments, $extra_arguments, $prefix, $entry, $is_pass_fail_test, $is_numeric_check);
			if($test_result != false)
			{
				// Result found
				if($is_numeric_check)
				{
					// Check if this test reports a min result value
					$min_result = self::parse_result_process_entry($tr, $log_file, $pts_test_arguments, $extra_arguments, 'MIN_', $entry, $is_pass_fail_test, $is_numeric_check);
					// Check if this test reports a max result value
					$max_result = self::parse_result_process_entry($tr, $log_file, $pts_test_arguments, $extra_arguments, 'MAX_', $entry, $is_pass_fail_test, $is_numeric_check);
				}
				self::gen_result_active_handle($test_run_request, $tr)->add_trial_run_result($test_result, $min_result, $max_result);
				$produced_result = true;
			}
		}

		return $produced_result;
	}
	protected static function parse_result_process_entry(&$test_run_request, $log_file, $pts_test_arguments, $extra_arguments, $prefix, &$e, $is_pass_fail_test, $is_numeric_check)
	{
		$test_result = false;
		$match_test_arguments = $e->get_match_to_test_args();
		$template = $e->get_output_template();
		$multi_match = $e->get_multi_match();

		if(!empty($match_test_arguments) && strpos($pts_test_arguments, $match_test_arguments) === false)
		{
			// This is not the ResultsParser XML section to use as the MatchToTestArguments does not match the PTS test arguments
			pts_client::test_profile_debug_message('Failed Initial Check For Matching: ' . $pts_test_arguments . ' not in ' . $match_test_arguments);
			return false;
		}

		switch($e->get_result_key())
		{
			case 'PTS_TEST_ARGUMENTS':
				$key_for_result = '#_' . $prefix . str_replace(' ', '', $pts_test_arguments) . '_#';
				break;
			case 'PTS_USER_SET_ARGUMENTS':
				$key_for_result = '#_' . $prefix . str_replace(' ', '', $extra_arguments) . '_#';
				break;
			default:
				$key_for_result = '#_' . $prefix . 'RESULT_#';
				break;
		}
		// The actual parsing here
		$start_result_pos = strrpos($template, $key_for_result);
		$test_results = array();

		if($prefix != null && $start_result_pos === false && $template != 'csv-dump-frame-latencies' && $template != 'libframetime-output' && $e->get_file_format() == null)
		{
			// XXX: technically the $prefix check shouldn't be needed, verify whether safe to have this check be unconditional on start_result_pos failing...
			//pts_client::test_profile_debug_message('Failed Additional Check');
			return false;
		}
		pts_client::test_profile_debug_message('Result Key: ' . $key_for_result);

		if(is_file($log_file))
		{
			if(filesize($log_file) > 52428800)
			{
				pts_client::test_profile_debug_message('File Too Big To Parse: ' . $log_file);
			}
			$output = file_get_contents($log_file);
		}
		else
		{
			pts_client::test_profile_debug_message('No Log File Found To Parse');
			return false;
		}

		$space_out_chars = array('(', ')', "\t");
		switch($e->get_file_format())
		{
			case 'CSV':
				$space_out_chars[] = ',';
				break;
			case 'XML':
				$xml = simplexml_load_string($output, 'SimpleXMLElement', LIBXML_COMPACT | LIBXML_PARSEHUGE | LIBXML_NOCDATA);
				$xml = json_decode(json_encode((array)$xml), true);
				$x = $xml;
				foreach(explode('/', $template) as $p)
				{
					pts_client::test_profile_debug_message('XML Trying ' . $p);
					if(isset($x[$p]))
					{
						$x = $x[$p];
					}
					else
					{
						pts_client::test_profile_debug_message('XML Failed To Find ' . $p);
						break;
					}
				}
				if(isset($x))
				{
					pts_client::test_profile_debug_message('XML Value Found: ' . $x);
					$test_results[] = trim($x);
				}
				goto RESULTPOSTPROCESSING;
				break;
		}

		if((isset($template[($start_result_pos - 1)]) && $template[($start_result_pos - 1)] == '/') || (isset($template[($start_result_pos + strlen($key_for_result))]) && $template[($start_result_pos + strlen($key_for_result))] == '/'))
		{
			$space_out_chars[] = '/';
		}

		$end_result_pos = $start_result_pos + strlen($key_for_result);
		$end_result_line_pos = strpos($template, "\n", $end_result_pos);
		$template_line = substr($template, 0, ($end_result_line_pos === false ? strlen($template) : $end_result_line_pos));
		$template_line = substr($template_line, strrpos($template_line, "\n"));
		pts_client::test_profile_debug_message('Template Line: ' . $template_line);
		$template_r = explode(' ', pts_strings::trim_spaces(str_replace($space_out_chars, ' ', str_replace('=', ' = ', $template_line))));
		$template_r_pos = array_search($key_for_result, $template_r);

		if($template_r_pos === false)
		{
			// Look for an element that partially matches, if like a '.' or '/sec' or some other pre/post-fix is present
			foreach($template_r as $x => $r_check)
			{
				if(isset($key_for_result[$x]) && strpos($r_check, $key_for_result[$x]) !== false)
				{
					$template_r_pos = $x;
					break;
				}
			}
		}

		// Check if frame time parsing info
		if(self::check_for_frame_time_parsing($test_results, $template, $output, $prefix))
		{
			// Was frame time info, nothing else to do for parsing, $test_results already filled then
		}
		else
		{
			// Conventional searching for matching section for finding result
			if($e->get_delete_output_before() != null && ($x = strpos($output, $e->get_delete_output_before())) !== false)
			{
				$output = substr($output, $x);
			}
			if($e->get_delete_output_after() != null && ($x = strpos($output, $e->get_delete_output_after())) !== false)
			{
				$output = substr($output, 0, $x);
			}

			$line_before_hint = $e->get_line_before_hint();
			$line_after_hint = $e->get_line_after_hint();
			$line_hint = $e->get_line_hint();
			$search_key = self::determine_search_key($output, $line_hint, $line_before_hint, $line_after_hint, $template_line, $template, $template_r, $key_for_result); // SEARCH KEY

			if($search_key != null || $line_before_hint != null || $line_after_hint != null || $template_r[0] == $key_for_result)
			{
				$is_multi_match = !empty($multi_match) && $multi_match != 'NONE';

				do
				{
					$count = count($test_results);
					$possible_lines = array();

					if($line_before_hint != null)
					{
						pts_client::test_profile_debug_message('Result Parsing Line Before Hint: ' . $line_before_hint);
						$line = substr($output, strpos($output, "\n", strrpos($output, $line_before_hint)));
						$line = substr($line, 0, strpos($line, "\n", 1));
						$output = substr($output, 0, strrpos($output, "\n", strrpos($output, $line_before_hint))) . "\n";
					}
					else if($line_after_hint != null)
					{
						pts_client::test_profile_debug_message('Result Parsing Line After Hint: ' . $line_after_hint);
						$line = substr($output, 0, strrpos($output, "\n", strrpos($output, $line_before_hint)));
						$line = substr($line, strrpos($line, "\n", 1) + 1);
						$output = null;
					}
					else if($search_key != null)
					{
						if(trim($search_key) != null)
						{
							$search_key = trim($search_key);
						}
						pts_client::test_profile_debug_message('Result Parsing Search Key: "' . $search_key . '"');

						while(($line_x = strrpos($output, $search_key)) !== false)
						{
							$line = substr($output, 0, strpos($output, "\n", $line_x));
							$start_of_line = strrpos($line, "\n");
							$output = substr($line, 0, $start_of_line) . "\n";
							$possible_lines[] = substr($line, $start_of_line + 1);
						}

						$line = !empty($possible_lines) ? array_shift($possible_lines) : null;
					}
					else
					{
						// Condition $template_r[0] == $key, include entire file since there is nothing to search
						pts_client::test_profile_debug_message('No Result Parsing Hint, Including Entire Result Output');
						$line = trim($output);
					}
					if($e->get_turn_chars_to_space() != null)
					{
						$line = str_replace($e->get_turn_chars_to_space(), ' ', $line);
					}
					pts_client::test_profile_debug_message('Result Line: ' . $line);

					// FALLBACK HELPERS FOR BELOW
					$did_try_colon_fallback = false;

					do
					{
						$try_again = false;
						$r = explode(' ', pts_strings::trim_spaces(str_replace($space_out_chars, ' ', str_replace('=', ' = ', $line))));
						$r_pos = array_search($key_for_result, $r);

						if($e->get_result_before_string() != null)
						{
							// Using ResultBeforeString tag
							$before_this = array_search($e->get_result_before_string(), $r);
							if($before_this && isset($r[($before_this - 1)]))
							{
								$possible_res = $r[($before_this - 1)];
								self::strip_result_cleaner($possible_res, $e);
								if($before_this !== false && (!$is_numeric_check || is_numeric($possible_res)))
								{
									$test_results[] = $possible_res;
								}
							}
						}
						else if($e->get_result_after_string() != null)
						{
							// Using ResultBeforeString tag
							$after_this = array_search($e->get_result_after_string(), $r);

							if($after_this !== false)
							{
								$after_this++;
								for($f = $after_this; $f < count($r); $f++)
								{
									if(in_array($r[$f], array(':', ',', '-', '=')))
									{
										continue;
									}
									self::strip_result_cleaner($r[$f], $e);
									if(!$is_numeric_check || is_numeric($r[$f]))
									{
										$test_results[] = $r[$f];
									}
									break;
								}
							}
						}
						else if(isset($r[$template_r_pos]))
						{
							self::strip_result_cleaner($r[$template_r_pos], $e);
							if(!$is_numeric_check || is_numeric($r[$template_r_pos]))
							{
								$test_results[] = $r[$template_r_pos];
							}
							else if($is_numeric_check && strpos($r[$template_r_pos], ':') !== false && strpos($r[$template_r_pos], '.') !== false && is_numeric(str_replace(array(':', '.'), null, $r[$template_r_pos])) && stripos($line, 'time') !== false)
							{
								// Convert e.g. 03:03.17 to seconds, relevant for at least pts/blender
								$seconds = 0;
								$formatted_time = $r[$template_r_pos];
								if(($c = strpos($formatted_time, ':')) !== false && strrpos($formatted_time, ':') == $c && is_numeric(substr($formatted_time, 0, $c)))
								{
									$seconds = (substr($formatted_time, 0, $c) * 60) + substr($formatted_time, ($c + 1));
								}
								if(!empty($seconds))
								{
									$test_results[] = $seconds;
								}
							}
						}
						else
						{
							// POSSIBLE FALLBACKS TO TRY AGAIN
							if(!$did_try_colon_fallback && strpos($line, ':') !== false)
							{
								$line = str_replace(':', ': ', $line);
								$did_try_colon_fallback = true;
								$try_again = true;
							}
						}
						if($try_again == false && empty($test_results) && !empty($possible_lines))
						{
							$line = array_shift($possible_lines);
							pts_client::test_profile_debug_message('Trying Backup Result Line: ' . $line);
							$try_again = true;
						}
						else if(!empty($test_results) && $is_multi_match && !empty($possible_lines) && $search_key != null)
						{
							// Rebuild output if it was disassembled in search key code above
							$output = implode(PHP_EOL, array_reverse($possible_lines)) . PHP_EOL;
						}
					}
					while($try_again);
				}
				while($is_multi_match && count($test_results) != $count && !empty($output));
			}
		}

		RESULTPOSTPROCESSING:
		if(empty($test_results))
		{
			pts_client::test_profile_debug_message('No Test Results');
			return false;
		}

		$multiply_by = $e->get_multiply_result_by();
		$divide_by = $e->get_divide_result_by();

		foreach($test_results as $x => &$test_result)
		{
			self::strip_result_cleaner($test_result, $e);

			// Expand validity checking here
			if($is_numeric_check == true && is_numeric($test_result) == false)
			{
				// E.g. if output time as 06:12.32 (as in blender)
				if(substr_count($test_result, ':') == 1 && substr_count($test_result, '.') == 1 && strpos($test_result, '.') > strpos($test_result, ':'))
				{
					$minutes = substr($test_result, 0, strpos($test_result, ':'));
					$seconds = ' ' . substr($test_result, strpos($test_result, ':') + 1);
					$test_result = ($minutes * 60) + $seconds;
				}
			}
			if($is_numeric_check == true && is_numeric($test_result) == false)
			{
				unset($test_results[$x]);
				continue;
			}
			if($divide_by != null && is_numeric($divide_by) && $divide_by != 0)
			{
				$test_result = $test_result / $divide_by;
			}
			if($multiply_by != null && is_numeric($multiply_by) && $multiply_by != 0)
			{
				$test_result = $test_result * $multiply_by;
			}
		}

		if(empty($test_results))
		{
			pts_client::test_profile_debug_message('No Test Results #2');
			return false;
		}

		switch($multi_match)
		{
			case 'REPORT_ALL':
				$test_result = implode(',', $test_results);
				break;
			case 'AVERAGE':
				default:
				if($is_numeric_check)
					$test_result = array_sum($test_results) / count($test_results);
				break;
		}

		if($is_pass_fail_test)
		{
			if(str_replace(array('PASS', 'FAIL', ','), null, $test_result) == null)
			{
				// already a properly formatted multi-pass fail
			}
			else if($test_result == 'TRUE' || $test_result == 'PASSED')
			{
				// pass
				$test_result = 'PASS';
			}
			else
			{
				// fail
				$test_result = 'FAIL';
			}
		}
		else if($is_numeric_check && !is_numeric($test_result))
		{
			// Final check to ensure valid data
			$test_result = false;
		}

		if($test_result != false)
		{
			if($e->get_result_scale() != null)
			{
				$test_run_request->test_profile->set_result_scale($e->get_result_scale());
			}
			if($e->get_result_proportion() != null)
			{
				$test_run_request->test_profile->set_result_proportion($e->get_result_proportion());
			}
			if($e->get_result_precision() != null)
			{
				$test_run_request->set_result_precision($e->get_result_precision());
			}
			if($e->get_arguments_description() != null)
			{
				$test_run_request->set_used_arguments_description($e->get_arguments_description());
			}
			if($e->get_append_to_arguments_description() != null)
			{
				$test_run_request->append_to_arguments_description(' - ' . $e->get_append_to_arguments_description());
			}
		}

		pts_client::test_profile_debug_message('Test Result Parser Returning: ' . $test_result);
		return $test_result;
	}
	protected static function strip_result_cleaner(&$test_result, &$e)
	{
		if($e->get_strip_from_result() != null)
		{
			$test_result = str_replace($e->get_strip_from_result(), null, $test_result);
		}
		if($e->get_strip_result_postfix() != null && substr($test_result, 0 - strlen($e->get_strip_result_postfix())) == $e->get_strip_result_postfix())
		{
			$test_result = substr($test_result, 0, 0 - strlen($e->get_strip_result_postfix()));
		}
	}
	protected static function determine_search_key(&$output, $line_hint, $line_before_hint, $line_after_hint, $template_line, $template, $template_r, $key)
	{
		// Determine the search key to use
		$search_key = null;
		if(isset($line_hint) && $line_hint != null && strpos($template_line, $line_hint) !== false)
		{
			$search_key = $line_hint;
		}
		else if($line_before_hint != null && strpos($template, $line_hint) !== false)
		{
			$search_key = null; // doesn't really matter what this value is
		}
		else if($line_after_hint != null && strpos($template, $line_hint) !== false)
		{
			$search_key = null; // doesn't really matter what this value is
		}
		else
		{
			$first_portion_of_line = substr($template_line, 0, strpos($template_line, $key));
			if($first_portion_of_line != null && strpos($output, $first_portion_of_line) !== false)
			{
				$search_key = $first_portion_of_line;
			}

			if($search_key == null)
			{
				foreach($template_r as $line_part)
				{
					if(strpos($line_part, ':') !== false && strlen($line_part) > 1)
					{
						// add some sort of && strrpos($template, $line_part)  to make sure there isn't two of the same $search_key
						$search_key = $line_part;
						break;
					}
				}
			}

			if($search_key == null && $key != null)
			{
				// Just try searching for the first part of the string
				/*
				for($i = 0; $i < $template_r_pos; $i++)
				{
					$search_key .= $template_r . ' ';
				}
				*/

				// This way if there are ) or other characters stripped, the below method will work where the above one will not
				$search_key = substr($template_line, 0, strpos($template_line, $key));
			}
		}

		return $search_key;
	}
	protected static function check_for_frame_time_parsing(&$test_results, $template, $output, $prefix)
	{
		$frame_time_values = null;
		$returns = false;

		if($template == 'libframetime-output')
		{
			$returns = true;
			$frame_time_values = array();
			$line_values = explode(PHP_EOL, $output);
			if(!empty($line_values) && isset($line_values[3]))
			{
				foreach($line_values as &$v)
				{
					if(substr($v, -3) == ' us' && substr($v, 0, 10) == 'Frametime ')
					{
						$frametime = substr($v, 10);
						$frametime = substr($frametime, 0, -3);
						if($frametime > 2000)
						{
							$frametime = $frametime / 1000;
							$frame_time_values[] = $frametime;
						}
					}
				}
				$frame_time_values = pts_math::remove_outliers($frame_time_values);
			}
		}
		else if($template == 'csv-dump-frame-latencies')
		{
			$returns = true;
			$frame_time_values = explode(',', $output);
		}

		if(!empty($frame_time_values) && isset($frame_time_values[3]))
		{
			// Get rid of the first value as likely outlier
			array_shift($frame_time_values);
			foreach($frame_time_values as $f => &$v)
			{
				if(!is_numeric($v) || $v == 0)
				{
					unset($frame_time_values[$f]);
					continue;
				}
				$v = 1000 / $v;
			}
			switch($prefix)
			{
				case 'MIN_':
					$val = min($frame_time_values);
					break;
				case 'MAX_':
					$val = max($frame_time_values);
					break;
				case 'AVG_':
					default:
					$val = array_sum($frame_time_values) / count($frame_time_values);
					break;
			}

			if($val != 0)
			{
				$test_results[] = $val;
			}
		}

		return $returns;
	}
}

?>
