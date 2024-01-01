<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2019, Phoronix Media
	Copyright (C) 2010 - 2019, Michael Larabel

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
	private static $monitoring_sensors = array();
	private static $did_dynamic_append_to_test_args = false;

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

			if(count($sensor) != 2 || !phodevi::is_sensor_supported($sensor))
			{
				// Not a sensor or it's not supported
				pts_test_result_parser::debug_message('No supported sensor found');
				continue;
			}

			if(!is_numeric($polling_freq) || $polling_freq < 0.5)
			{
				pts_test_result_parser::debug_message('No polling frequency defined, defaulting to 2 seconds');
				$polling_freq = 2;
			}

			$polling_freq *= 1000000; // Convert from seconds to micro-seconds
			if(!in_array($report_as, array('ALL', 'MAX', 'MIN', 'AVG')))
			{
				// Not a valid reporting type
				pts_test_result_parser::debug_message('No valid Report entry found.');
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
				$result_value = round($end_time - $sensor_r[3], 3);

				$minimal_test_time = pts_config::read_user_config('PhoronixTestSuite/Options/TestResultValidation/MinimalTestTime', 2);
				if($result_value < $minimal_test_time)
				{
					// The test ended too fast
					pts_test_result_parser::debug_message('Test Run-Time Too Short: ' . $result_value);
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
						$result_value = pts_math::arithmetic_mean($sensor_values);
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
				pts_test_result_parser::debug_message('Test Result Montioring Process Returning: ' . $result_value);
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
		$pts_test_arguments = trim($test_run_request->test_profile->get_default_arguments() . ' ' . ($test_run_request->test_profile->get_default_arguments() != null && !empty($extra_arguments) ? str_replace($test_run_request->test_profile->get_default_arguments(), '', $extra_arguments) : $extra_arguments) . ' ' . $test_run_request->test_profile->get_default_post_arguments());

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
			switch(($eid = $entry->get_identifier()))
			{
				case 'libframetime-output':
				case 'libframetime-output-no-limit':
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
								if($eid == 'libframetime-output-no-limit' || $frametime > 2000)
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
					break;
				case 'gpu-frames-space-delimited':
					// HITMAN 2 uses at least this method
					$log_file = pts_file_io::file_get_contents($test_log_file);
					$frame_all_times = array();
					if(($x = strpos($log_file, '---- GPU FRAMES ----')) !== false)
					{
						$log_file = trim(str_replace(PHP_EOL, ' ', substr($log_file, $x + strlen('---- GPU FRAMES ----'))));
						foreach(explode(' ', $log_file) as $inp)
						{
							if(is_numeric($inp) && $inp > 0)
							{
								$frame_all_times[] = round(1000 / $inp, 3); // since its reporting current frame
							}
						}
					}
					break;
				case 'valve-source-frame-times':
					// Counter-Strike: GO At least
					$log_file = pts_file_io::file_get_contents($test_log_file);
					$frame_all_times = array();
					if(($x = strpos($log_file, 'demo tick,frame start time,frame start delta')) !== false)
					{
						$log_file = substr($log_file, $x);
						foreach(explode(PHP_EOL, $log_file) as $line)
						{
							$line = explode(',', $line);
							if(isset($line[2]) && is_numeric($line[2]) && $line[2] > 0)
							{
								$frame_all_times[] = $line[2] * 1000;
							}
						}
					}
					break;
				case 'csv-f1-frame-times':
					// F1 2018
					$log_file = pts_file_io::file_get_contents($test_log_file);
					$frame_all_times = array();
					if(($x = strpos($log_file, 'Frame,Time (ms)')) !== false)
					{
						$log_file = substr($log_file, $x);
						foreach(explode(PHP_EOL, $log_file) as $line)
						{
							$line = explode(',', $line);
							if(isset($line[1]) && is_numeric($line[1]) && $line[1] > 0)
							{
								$frame_all_times[] = $line[1];
							}
						}
					}
					break;
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

		if($prefix != null && substr($prefix, -1) != '_')
		{
			$prefix .= '_';
		}

		$definitions = $test_run_request->test_profile->get_results_definition('ResultsParser');
		$all_parser_entries = $definitions->get_result_parser_definitions();
		$avoid_duplicates = array();
		self::$did_dynamic_append_to_test_args = false;
		$primary_result = null;
		foreach($all_parser_entries as $entry)
		{
			$tr = clone $test_run_request;
			if($entry->get_display_format() != null)
			{
				$tr->test_profile->set_display_format($entry->get_display_format());
			}
			$is_pass_fail_test = in_array($tr->test_profile->get_display_format(), array('PASS_FAIL', 'MULTI_PASS_FAIL'));
			$is_numeric_check = !$is_pass_fail_test;
			$min_result = null;
			$max_result = null;
			$min_test_result = false;
			$max_test_result = false;
			$test_result = self::parse_result_process_entry($tr, $log_file, $pts_test_arguments, $extra_arguments, $prefix, $entry, $is_pass_fail_test, $is_numeric_check, $all_parser_entries, $min_test_result, $max_test_result, $primary_result);
			if($test_result != false)
			{
				// Result found
				if(in_array($test_result, $avoid_duplicates) && self::$did_dynamic_append_to_test_args == false)
				{
					// Workaround for some tests like FIO that have test result parsers that could generate duplicates in handling old PTS versions while newer ones have K conversion, etc
					pts_test_result_parser::debug_message('Dropping Duplicate Test Result Match: ' . $test_result);
					continue;
				}
				$avoid_duplicates[] = $test_result;
				if($is_numeric_check)
				{
					// Check if this test reports a min result value
					$min_result = $min_test_result !== false && is_numeric($min_test_result) ? $min_test_result : self::parse_result_process_entry($tr, $log_file, $pts_test_arguments, $extra_arguments, 'MIN_', $entry, $is_pass_fail_test, $is_numeric_check, $all_parser_entries);
					// Check if this test reports a max result value
					$max_result = $max_test_result !== false && is_numeric($max_test_result) ? $max_test_result : self::parse_result_process_entry($tr, $log_file, $pts_test_arguments, $extra_arguments, 'MAX_', $entry, $is_pass_fail_test, $is_numeric_check, $all_parser_entries);
				}
				self::gen_result_active_handle($test_run_request, $tr)->add_trial_run_result($test_result, $min_result, $max_result);
				$produced_result = true;
				if($primary_result == null)
				{
					$primary_result = $tr;
				}
			}
		}

		return $produced_result;
	}
	protected static function parse_result_process_entry(&$test_run_request, $log_file, $pts_test_arguments, $extra_arguments, $prefix, &$e, $is_pass_fail_test, $is_numeric_check, &$all_parser_entries, &$min_test_result = false, &$max_test_result = false, $primary_result = null)
	{
		$test_result = false;
		$match_test_arguments = $e->get_match_to_test_args();
		$template = $e->get_output_template();
		$multi_match = $e->get_multi_match();

		if(!empty($match_test_arguments) && strpos($pts_test_arguments, $match_test_arguments) === false)
		{
			// This is not the ResultsParser XML section to use as the MatchToTestArguments does not match the PTS test arguments
			pts_test_result_parser::debug_message('Failed Initial Check For Matching: ' . $pts_test_arguments . ' not in ' . $match_test_arguments);
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
			//pts_test_result_parser::debug_message('Failed Additional Check');
			return false;
		}
		pts_test_result_parser::debug_message('Result Key: ' . $key_for_result);

		if(is_file($log_file))
		{
			if(filesize($log_file) > 52428800)
			{
				pts_test_result_parser::debug_message('File Too Big To Parse: ' . $log_file);
			}
			$output = file_get_contents($log_file);
		}
		else
		{
			pts_test_result_parser::debug_message('No Log File Found To Parse');
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
					pts_test_result_parser::debug_message('XML Trying ' . $p);
					if(isset($x[$p]))
					{
						$x = $x[$p];
					}
					else
					{
						pts_test_result_parser::debug_message('XML Failed To Find ' . $p);
						break;
					}
				}
				if(isset($x))
				{
					if(!is_array($x))
					{
						pts_test_result_parser::debug_message('XML Value Found: ' . $x);
						$test_results[] = trim($x);
					}
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
		pts_test_result_parser::debug_message('Template Line: ' . $template_line);
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
						pts_test_result_parser::debug_message('Result Parsing Line Before Hint: ' . $line_before_hint);
						$line = substr($output, strpos($output, "\n", strrpos($output, $line_before_hint)));
						$line = substr($line, 0, strpos($line, "\n", (isset($line[2]) ? 1 : 0)));
						$output = substr($output, 0, strrpos($output, "\n", strrpos($output, $line_before_hint))) . "\n";
					}
					else if($line_after_hint != null)
					{
						pts_test_result_parser::debug_message('Result Parsing Line After Hint: ' . $line_after_hint);
						$line = substr($output, 0, strrpos($output, "\n", strrpos($output, $line_after_hint)));
						$line = substr($line, strrpos($line, "\n", (isset($line[2]) ? 1 : 0)) + 1);
						$output = null;
					}
					else if($search_key != null)
					{
						if(trim($search_key) != null)
						{
							$search_key = trim($search_key);
						}
						pts_test_result_parser::debug_message('Result Parsing Search Key: "' . $search_key . '"');

						while(($line_x = strrpos($output, $search_key)) !== false)
						{
							$line = substr($output, 0, strpos($output, "\n", $line_x));
							$start_of_line = strrpos($line, "\n");
							$output = substr($line, 0, $start_of_line) . "\n";
							$possible_lines[] = substr($line, $start_of_line + 1);

							if((count($possible_lines) > 16 && $is_multi_match && phodevi::is_windows()) || $multi_match == 'REPORT_ALL')
							{
								// This vastly speeds up pts/dav1d result decoding on Windows as this expensive loop not used
								break;
							}
						}

						$line = !empty($possible_lines) ? array_shift($possible_lines) : null;
					}
					else
					{
						// Condition $template_r[0] == $key, include entire file since there is nothing to search
						pts_test_result_parser::debug_message('No Result Parsing Hint, Including Entire Result Output');
						$line = trim($output);
					}
					pts_test_result_parser::clean_result_line($e, $line);
					pts_test_result_parser::debug_message('Result Line: ' . $line);

					// FALLBACK HELPERS FOR BELOW
					$did_try_colon_fallback = false;

					do
					{
						$try_again = false;
						$r = $line != null ? explode(' ', pts_strings::trim_spaces(str_replace($space_out_chars, ' ', str_replace('=', ' = ', $line)))) : array();
						$r_pos = array_search($key_for_result, $r);

						if($e->get_result_before_string() != null)
						{
							// Using ResultBeforeString tag
							$before_this = array_search($e->get_result_before_string(), $r);
							if($before_this && isset($r[($before_this - 1)]))
							{
								$possible_res = $r[($before_this - 1)];
								self::strip_result_cleaner($possible_res, $e);
								if($before_this !== false && (!$is_numeric_check || self::valid_numeric_input_handler($possible_res, $line)))
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
									if(!$is_numeric_check || self::valid_numeric_input_handler($r[$f], $line))
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
							if(!$is_numeric_check || self::valid_numeric_input_handler($r[$template_r_pos], $line))
							{
								$test_results[] = $r[$template_r_pos];
							}
						}
						else
						{
							// POSSIBLE FALLBACKS TO TRY AGAIN
							if(!$did_try_colon_fallback && $line != null && strpos($line, ':') !== false)
							{
								$line = str_replace(':', ': ', $line);
								$did_try_colon_fallback = true;
								$try_again = true;
							}
						}
						if($try_again == false && empty($test_results) && !empty($possible_lines))
						{
							$line = array_shift($possible_lines);
							pts_test_result_parser::clean_result_line($e, $line);
							pts_test_result_parser::debug_message('Trying Backup Result Line: ' . $line);
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
			pts_test_result_parser::debug_message('No Test Results');
			return false;
		}

		$multiply_by = $e->get_multiply_result_by();
		$divide_by = $e->get_divide_result_by();
		$divide_divisor = $e->get_divide_result_divisor();

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
			if($divide_divisor != null && is_numeric($divide_divisor) && $divide_divisor != 0 && $test_result != 0)
			{
				$test_result = $divide_divisor / $test_result;
			}
			if($multiply_by != null && is_numeric($multiply_by) && $multiply_by != 0)
			{
				$test_result = $test_result * $multiply_by;
			}
		}

		if(empty($test_results))
		{
			pts_test_result_parser::debug_message('No Test Results #2');
			return false;
		}

		$test_results_group_precision = pts_math::get_precision($test_results);
		switch($multi_match)
		{
			case 'REPORT_ALL':
				$test_result = implode(',', $test_results);
				$e->set_display_format('LINE_GRAPH');
				$is_numeric_check = false;
				break;
			case 'GEOMETRIC_MEAN':
				if($is_numeric_check)
				{
					$test_result = round(pts_math::geometric_mean($test_results), $test_results_group_precision);
					if(count($test_results) > 1)
					{
						$min_test_result = min($test_results);
						$max_test_result = max($test_results);
					}
					break;
				}
			case 'HARMONIC_MEAN':
				if($is_numeric_check)
				{
					$test_result = round(pts_math::harmonic_mean($test_results), $test_results_group_precision);
					if(count($test_results) > 1)
					{
						$min_test_result = min($test_results);
						$max_test_result = max($test_results);
					}
					break;
				}
			case 'AVERAGE':
			case 'MEAN':
			default:
				if($is_numeric_check)
				{
					$test_result = round(pts_math::arithmetic_mean($test_results), $test_results_group_precision);
					if(count($test_results) > 1)
					{
						$min_test_result = min($test_results);
						$max_test_result = max($test_results);
					}
				}
				break;
		}

		if($is_pass_fail_test)
		{
			if(str_replace(array('PASS', 'FAIL', ','), '', $test_result) == null)
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
			if($e->get_display_format() != null)
			{
				$test_run_request->test_profile->set_display_format($e->get_display_format());
			}
			if($e->get_result_precision() != null)
			{
				$test_run_request->set_result_precision($e->get_result_precision());
			}
			if($e->get_arguments_description() != null)
			{
				$test_run_request->set_used_arguments_description($e->get_arguments_description());
			}
			if($e->get_result_importance() != null && strtolower($e->get_result_importance()) == 'secondary' && !empty($primary_result))
			{
				$test_run_request->set_parent_hash_from_result($primary_result);
			}
			if($e->get_append_to_arguments_description() != null)
			{
				foreach($all_parser_entries as $parser_entry)
				{
					if($parser_entry->get_append_to_arguments_description() != null)
					{
						$test_run_request->remove_from_used_arguments_description(' - ' . $parser_entry->get_append_to_arguments_description());
					}
				}

				$test_run_request->append_to_arguments_description(' - ' . $e->get_append_to_arguments_description());
				self::$did_dynamic_append_to_test_args = true;
			}
		}

		pts_test_result_parser::debug_message('Test Result Parser Returning: ' . $test_result);
		return $test_result;
	}
	protected static function clean_result_line(&$e, &$line)
	{
		// Any post-processing to clean-up / prepare the (possible) line of text where the result can be found
		if($e->get_turn_chars_to_space() != null && $line != null)
		{
			$line = str_replace($e->get_turn_chars_to_space(), ' ', $line);
		}
	}
	protected static function valid_numeric_input_handler(&$numeric_input, $line)
	{
		if(is_numeric($numeric_input))
		{
			return true;
		}
		else if(is_numeric(str_ireplace(array('m', 'h', 's'), '', $numeric_input)))
		{
			// XXhXXmXXs format
			$vtime = 0;
			$ni = $numeric_input;
			foreach(array(3600 => 'h', 60 => 'm', 1 => 's') as $m => $u)
			{
				if(($x = stripos($ni, $u)) !== false)
				{
					$extracted = substr($ni, 0, $x);
					$ni = substr($ni, ($x + 1));
					if(is_numeric($extracted))
					{
						$vtime += $extracted * $m;
					}
					else
					{
						return false;
					}
				}
			}
			if($vtime > 0 && is_numeric($vtime))
			{
				$numeric_input = $vtime;
				return true;
			}
		}
		else if(strpos($numeric_input, ':') !== false && strpos($numeric_input, '.') !== false && is_numeric(str_replace(array(':', '.'), '', $numeric_input)) && stripos($line, 'time') !== false)
		{
			// Convert e.g. 03:03.17 to seconds, relevant for at least pts/blender
			$seconds = 0;
			$formatted_time = $numeric_input;
			if(($c = strpos($formatted_time, ':')) !== false && strrpos($formatted_time, ':') == $c && is_numeric(substr($formatted_time, 0, $c)))
			{
				$seconds = (substr($formatted_time, 0, $c) * 60) + substr($formatted_time, ($c + 1));
			}
			if(!empty($seconds))
			{
				$numeric_input = $seconds;
				return true;
			}
		}
		else if(strpos($numeric_input, ':') !== false && strtolower(substr($numeric_input, -1)) == 's')
		{
			// e.g. 01h:04m:33s
			$seconds = 0;
			$invalid = false;
			foreach(explode(':', $numeric_input) as $time_segment)
			{
				$postfix = strtolower(substr($time_segment, -1));
				$value = substr($time_segment, 0, -1);
				if($value == 0 || !is_numeric($value))
				{
					continue;
				}
				switch($postfix)
				{
					case 'h':
						$seconds += ($value * 3600);
						break;
					case 'm':
						$seconds += ($value * 60);
						break;
					case 's':
						$seconds += $value;
						break;
					default:
						$invalid = true;
						break;
				}
			}
			if(!empty($seconds) && $seconds > 0 && !$invalid)
			{
				$numeric_input = $seconds;
				return true;
			}
		}

		return false;
	}
	protected static function strip_result_cleaner(&$test_result, &$e)
	{
		if($e->get_strip_from_result() != null)
		{
			$test_result = str_replace($e->get_strip_from_result(), '', $test_result);
		}
		if($e->get_strip_result_postfix() != null && substr($test_result, 0 - strlen($e->get_strip_result_postfix())) == $e->get_strip_result_postfix())
		{
			$test_result = substr($test_result, 0, 0 - strlen($e->get_strip_result_postfix()));
		}

		if(!is_numeric($test_result) && is_numeric(substr($test_result, 0, -1)))
		{
			// The test_result is numeric except for the last character, possible k/M prefix or so
			// Or do any other format conversion here
			// Made in PTS 9.4, this shouldn't break any existing result-definitions since it would have been non-numeric here already
			$result_without_last_char = substr($test_result, 0, -1);
			switch(strtolower(substr($test_result, -1)))
			{
				case 'k':
					$test_result = $result_without_last_char * 1000;
					break;
				case 'm':
					$test_result = $result_without_last_char * 1000000;
					break;
			}
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
		else if($line_before_hint != null && $line_hint != null && strpos($template, $line_hint) !== false)
		{
			$search_key = null; // doesn't really matter what this value is
		}
		else if($line_after_hint != null && $line_hint != null && strpos($template, $line_hint) !== false)
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

		if($template == 'libframetime-output' || $template == 'libframetime-output-no-limit')
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
						if($template == 'libframetime-output-no-limit' || $frametime > 2000)
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
					$val = pts_math::arithmetic_mean($frame_time_values);
					break;
			}

			if($val != 0)
			{
				$test_results[] = $val;
			}
		}

		return $returns;
	}
	public static function debug_message($message)
	{
		$reported = false;

		if(PTS_IS_CLIENT && pts_client::is_debug_mode())
		{
			if(($x = strpos($message, ': ')) !== false)
			{
				$message = pts_client::cli_colored_text(substr($message, 0, $x + 1), 'yellow', true) . pts_client::cli_colored_text(substr($message, $x + 1), 'yellow', false);
			}
			else
			{
				$message = pts_client::cli_colored_text($message, 'yellow', false);
			}
			pts_client::$display->test_run_instance_error($message);
			$reported = true;
		}

		return $reported;
	}
}

?>
