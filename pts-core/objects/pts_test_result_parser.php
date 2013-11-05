<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2013, Phoronix Media
	Copyright (C) 2010 - 2013, Michael Larabel

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

	public static function system_monitor_task_check(&$test_profile)
	{
		$parse_xml_file = $test_profile->get_file_parser_spec();

		if($parse_xml_file == false)
		{
			return false;
		}

		self::$monitoring_sensors = array();
		$test_directory = $test_profile->get_install_dir();
		$results_parser_xml = new pts_parse_results_nye_XmlReader($parse_xml_file);
		$monitor_sensor = $results_parser_xml->getXMLArrayValues('PhoronixTestSuite/SystemMonitor/Sensor');
		$monitor_frequency = $results_parser_xml->getXMLArrayValues('PhoronixTestSuite/SystemMonitor/PollingFrequency');
		$monitor_report_as = $results_parser_xml->getXMLArrayValues('PhoronixTestSuite/SystemMonitor/Report');

		if(count($monitor_sensor) == 0)
		{
			// No monitoring to do
			return false;
		}

		if(self::$supported_sensors == null)
		{
			// Cache this since this shouldn't change between tests/runs
			self::$supported_sensors = phodevi::supported_sensors();
		}

		foreach(array_keys($monitor_sensor) as $i)
		{
			// TODO: Right now we are looping through SystemMonitor tags, but right now pts-core only supports providing one monitor sensor as the result
			$sensor = explode('.', $monitor_sensor[$i]);

			if($sensor == array('sys', 'time'))
			{
				// sys.time is a special case since we are just timing the test length and thus don't need to fork the thread
				$start_time = microtime(true);
				array_push(self::$monitoring_sensors, array(0, $sensor, null, $start_time));
				continue;
			}

			if(count($sensor) != 2 || !in_array($sensor, self::$supported_sensors))
			{
				// Not a sensor or it's not supported
				continue;
			}

			if(!is_numeric($monitor_frequency[$i]) || $monitor_frequency < 0.5)
			{
				// No polling frequency supplied
				continue;
			}

			$monitor_frequency[$i] *= 1000000; // Convert from seconds to micro-seconds

			if(!in_array($monitor_report_as[$i], array('ALL', 'MAX', 'MIN', 'AVG')))
			{
				// Not a valid reporting type
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
					array_push(self::$monitoring_sensors, array($pid, $sensor, $monitor_report_as[$i], $monitor_file));
					continue;
				}
				else
				{
					$sensor_values = array();

					while(is_file(PTS_USER_LOCK)) // TODO: or perhaps it may be okay to just do while(true) since posix_kill() is used when needed
					{
						array_push($sensor_values, phodevi::read_sensor($sensor));
						file_put_contents($monitor_file, implode("\n", $sensor_values));
						usleep($monitor_frequency[$i]);
					}

					exit(0);
				}
			}		
		}

		return count(self::$monitoring_sensors) > 0;
	}
	public static function system_monitor_task_post_test(&$test_profile)
	{
		$test_directory = $test_profile->get_install_dir();

		foreach(self::$monitoring_sensors as $sensor_r)
		{
			if($sensor_r[1] == array('sys', 'time'))
			{
				// sys.time is a special case
				$end_time = microtime(true);

				// Delta time
				$result_value = $end_time - $sensor_r[3];

				if($result_value < 3)
				{
					// The test ended too fast
					$result_value = null;
				}
			}
			else
			{
				// Kill the sensor monitoring thread
				if(function_exists('posix_kill') == false)
				{
					pts_client::$display->test_run_error('The PHP POSIX extension is required for this test.');
					return false;
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

			if($result_value != null)
			{
				// For now it's only possible to return one result per test
				return $result_value;
			}
		}

		return false;
	}
	public static function parse_result(&$test_run_request, $test_log_file)
	{
		$parse_xml_file = $test_run_request->test_profile->get_file_parser_spec();

		if($parse_xml_file == false)
		{
			return null;
		}

		$test_identifier = $test_run_request->test_profile->get_identifier();
		$extra_arguments = $test_run_request->get_arguments();
		$pts_test_arguments = trim($test_run_request->test_profile->get_default_arguments() . ' ' . str_replace($test_run_request->test_profile->get_default_arguments(), '', $extra_arguments) . ' ' . $test_run_request->test_profile->get_default_post_arguments());

		switch($test_run_request->test_profile->get_display_format())
		{
			case 'IMAGE_COMPARISON':
				$test_run_request->active_result = self::parse_iqc_result($test_run_request->test_profile, $parse_xml_file, $test_log_file, $pts_test_arguments, $extra_arguments);
				break;
			case 'PASS_FAIL':
			case 'MULTI_PASS_FAIL':
				$test_run_request->active_result = self::parse_generic_result($test_run_request, $parse_xml_file, $test_log_file, $pts_test_arguments, $extra_arguments);
				break;
			case 'BAR_GRAPH':
			default:
				$test_run_request->active_result = self::parse_numeric_result($test_run_request, $parse_xml_file, $test_log_file, $pts_test_arguments, $extra_arguments);

				if($test_run_request->test_profile->get_display_format() == 'BAR_GRAPH' && !is_numeric($test_run_request->active_result))
				{
					$test_run_request->active_result = false;
				}
				else
				{
					$test_run_request->active_min_result = self::parse_numeric_result($test_run_request, $parse_xml_file, $test_log_file, $pts_test_arguments, $extra_arguments, 'MIN');
					$test_run_request->active_max_result = self::parse_numeric_result($test_run_request, $parse_xml_file, $test_log_file, $pts_test_arguments, $extra_arguments, 'MAX');
				}
				break;
		}
	}
	public static function generate_extra_data(&$test_result, &$test_log_file = null)
	{
		$parse_xml_file = $test_result->test_profile->get_file_parser_spec();

		if($parse_xml_file == false)
		{
			return;
		}

		$results_parser_xml = new pts_parse_results_nye_XmlReader($parse_xml_file);
		$extra_data_identifiers = $results_parser_xml->getXMLArrayValues('PhoronixTestSuite/ExtraData/Identifier');
		$extra_results = array();

		foreach(array_keys($extra_data_identifiers) as $i)
		{
			$id = $extra_data_identifiers[$i];

			switch($id)
			{
				case 'com-speeds-frame-latency-totals':
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
								array_push($frame_all_times, $all);
							}
						}
						$log_file = strstr($log_file, 'bk:');
					}

					if(isset($frame_all_times[60]))
					{
						// Take off the first frame as it's likely to have taken much longer when game just starting off...
						array_shift($frame_all_times);
						$tp = clone $test_result->test_profile;
						$tp->set_result_scale('Milliseconds');
						$tp->set_result_proportion('LIB');
						$tp->set_display_format('LINE_GRAPH');
						$tp->set_identifier(null);
						$extra_result = new pts_test_result($tp);
						$extra_result->set_used_arguments_description('Total Frame Time');
						$extra_result->set_result(implode(',', $frame_all_times));
						array_push($extra_results, $extra_result);
						//$extra_result->set_used_arguments(phodevi::sensor_name($sensor) . ' ' . $test_result->get_arguments());
					}
					break;
			}
		}

		if(!empty($extra_results))
		{
			$test_result->secondary_linked_results = $extra_results;
		}
	}
	public static function calculate_end_result(&$test_result)
	{
		$trial_results = $test_result->test_result_buffer->get_values();

		if(count($trial_results) == 0)
		{
			$test_result->set_result(0);
			return false;
		}

		$END_RESULT = 0;

		switch($test_result->test_profile->get_display_format())
		{
			case 'NO_RESULT':
				// Nothing to do, there are no results
				break;
			case 'LINE_GRAPH':
			case 'FILLED_LINE_GRAPH':
			case 'TEST_COUNT_PASS':
				// Just take the first result
				$END_RESULT = $trial_results[0];
				break;
			case 'IMAGE_COMPARISON':
				// Capture the image
				$iqc_image_png = $trial_results[0];

				if(is_file($iqc_image_png))
				{
					$img_file_64 = base64_encode(file_get_contents($iqc_image_png, FILE_BINARY));
					$END_RESULT = $img_file_64;
					unlink($iqc_image_png);				
				}
				break;
			case 'PASS_FAIL':
			case 'MULTI_PASS_FAIL':
				// Calculate pass/fail type
				$END_RESULT = -1;

				if(count($trial_results) == 1)
				{
					$END_RESULT = $trial_results[0];
				}
				else
				{
					foreach($trial_results as $result)
					{
						if($result == 'FALSE' || $result == '0' || $result == 'FAIL')
						{
							if($END_RESULT == -1 || $END_RESULT == 'PASS')
							{
								$END_RESULT = 'FAIL';
							}
						}
						else
						{
							if($END_RESULT == -1)
							{
								$END_RESULT = 'PASS';
							}
						}
					}
				}
				break;
			case 'BAR_GRAPH':
			default:
				// Result is of a normal numerical type
				switch($test_result->test_profile->get_result_quantifier())
				{
					case 'MAX':
						$END_RESULT = max($trial_results);
						break;
					case 'MIN':
						$END_RESULT = min($trial_results);
						break;
					case 'AVG':
					default:
						// assume AVG (average)
						$is_float = false;
						$TOTAL_RESULT = 0;
						$TOTAL_COUNT = 0;

						foreach($trial_results as $result)
						{
							$result = trim($result);

							if(is_numeric($result))
							{
								$TOTAL_RESULT += $result;
								$TOTAL_COUNT++;

								if(!$is_float && strpos($result, '.') !== false)
								{
									$is_float = true;
								}
							}
						}

						$END_RESULT = pts_math::set_precision($TOTAL_RESULT / ($TOTAL_COUNT > 0 ? $TOTAL_COUNT : 1), $test_result->get_result_precision());

						if(!$is_float)
						{
							$END_RESULT = round($END_RESULT);
						}

						if(count($min = $test_result->test_result_buffer->get_min_values()) > 0)
						{
							$min = round(min($min), 2);

							if($min < $END_RESULT && is_numeric($min) && $min != 0)
							{
								$test_result->set_min_result($min);
							}
						}
						if(count($max = $test_result->test_result_buffer->get_max_values()) > 0)
						{
							$max = round(max($max), 2);

							if($max > $END_RESULT && is_numeric($max) && $max != 0)
							{
								$test_result->set_max_result($max);
							}
						}
						break;
				}
				break;
		}

		$test_result->set_result($END_RESULT);
	}
	protected static function parse_iqc_result(&$test_profile, $parse_xml_file, $log_file, $pts_test_arguments, $extra_arguments)
	{
		$results_parser_xml = new pts_parse_results_nye_XmlReader($parse_xml_file);
		$result_match_test_arguments = $results_parser_xml->getXMLArrayValues('PhoronixTestSuite/ImageParser/MatchToTestArguments');
		$result_iqc_source_file = $results_parser_xml->getXMLArrayValues('PhoronixTestSuite/ImageParser/SourceImage');
		$result_iqc_image_x = $results_parser_xml->getXMLArrayValues('PhoronixTestSuite/ImageParser/ImageX');
		$result_iqc_image_y = $results_parser_xml->getXMLArrayValues('PhoronixTestSuite/ImageParser/ImageY');
		$result_iqc_image_width = $results_parser_xml->getXMLArrayValues('PhoronixTestSuite/ImageParser/ImageWidth');
		$result_iqc_image_height = $results_parser_xml->getXMLArrayValues('PhoronixTestSuite/ImageParser/ImageHeight');

		$test_result = false;

		if(!extension_loaded('gd'))
		{
			// Needs GD library to work
			return false;
		}

		for($i = 0; $i < count($result_iqc_source_file); $i++)
		{
			if(!empty($result_match_test_arguments[$i]) && strpos($pts_test_arguments, $result_match_test_arguments[$i]) === false)
			{
				// This is not the ResultsParser XML section to use as the MatchToTestArguments does not match the PTS test arguments
				continue;
			}

			if(is_file($test_profile->get_install_dir() . $result_iqc_source_file[$i]))
			{
				$iqc_source_file = $test_profile->get_install_dir() . $result_iqc_source_file[$i];
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

			$img_sliced = imagecreatetruecolor($result_iqc_image_width[$i], $result_iqc_image_height[$i]);
			imagecopyresampled($img_sliced, $img, 0, 0, $result_iqc_image_x[$i], $result_iqc_image_y[$i], $result_iqc_image_width[$i], $result_iqc_image_height[$i], $result_iqc_image_width[$i], $result_iqc_image_height[$i]);
			$test_result = $test_profile->get_install_dir() . 'iqc.png';
			imagepng($img_sliced, $test_result);

			if($test_result != false)
			{
				break;
			}
		}

		return $test_result;
	}
	protected static function parse_numeric_result(&$test_run_request, $parse_xml_file, $log_file, $pts_test_arguments, $extra_arguments, $prefix = null)
	{
		return self::parse_result_process($test_run_request, $parse_xml_file, $log_file, $pts_test_arguments, $extra_arguments, true, $prefix);
	}
	protected static function parse_generic_result(&$test_run_request, $parse_xml_file, $log_file, $pts_test_arguments, $extra_arguments, $prefix = null)
	{
		return self::parse_result_process($test_run_request, $parse_xml_file, $log_file, $pts_test_arguments, $extra_arguments, false, $prefix);
	}
	protected static function parse_result_process(&$test_run_request, $parse_xml_file, $log_file, $pts_test_arguments, $extra_arguments, $is_numeric_check = true, $prefix = null)
	{
		$test_identifier = $test_run_request->test_profile->get_identifier();
		$results_parser_xml = new pts_parse_results_nye_XmlReader($parse_xml_file);
		$result_match_test_arguments = $results_parser_xml->getXMLArrayValues('PhoronixTestSuite/ResultsParser/MatchToTestArguments');
		$result_scale = $results_parser_xml->getXMLArrayValues('PhoronixTestSuite/ResultsParser/ResultScale');
		$result_proportion = $results_parser_xml->getXMLArrayValues('PhoronixTestSuite/ResultsParser/ResultProportion');
		$result_precision = $results_parser_xml->getXMLArrayValues('PhoronixTestSuite/ResultsParser/ResultPrecision');
		$result_template = $results_parser_xml->getXMLArrayValues('PhoronixTestSuite/ResultsParser/OutputTemplate');
		$result_key = $results_parser_xml->getXMLArrayValues('PhoronixTestSuite/ResultsParser/ResultKey');
		$result_line_hint = $results_parser_xml->getXMLArrayValues('PhoronixTestSuite/ResultsParser/LineHint');
		$result_line_before_hint = $results_parser_xml->getXMLArrayValues('PhoronixTestSuite/ResultsParser/LineBeforeHint');
		$result_line_after_hint = $results_parser_xml->getXMLArrayValues('PhoronixTestSuite/ResultsParser/LineAfterHint');
		$result_before_string = $results_parser_xml->getXMLArrayValues('PhoronixTestSuite/ResultsParser/ResultBeforeString');
		$result_divide_by = $results_parser_xml->getXMLArrayValues('PhoronixTestSuite/ResultsParser/DivideResultBy');
		$result_multiply_by = $results_parser_xml->getXMLArrayValues('PhoronixTestSuite/ResultsParser/MultiplyResultBy');
		$strip_from_result = $results_parser_xml->getXMLArrayValues('PhoronixTestSuite/ResultsParser/StripFromResult');
		$strip_result_postfix = $results_parser_xml->getXMLArrayValues('PhoronixTestSuite/ResultsParser/StripResultPostfix');
		$multi_match = $results_parser_xml->getXMLArrayValues('PhoronixTestSuite/ResultsParser/MultiMatch');
		$test_result = false;

		if($prefix != null && substr($prefix, -1) != '_')
		{
			$prefix .= '_';
		}

		for($i = 0; $i < count($result_template); $i++)
		{
			if(!empty($result_match_test_arguments[$i]) && strpos($pts_test_arguments, $result_match_test_arguments[$i]) === false)
			{
				// This is not the ResultsParser XML section to use as the MatchToTestArguments does not match the PTS test arguments
				continue;
			}

			if($result_key[$i] == null)
			{
				$result_key[$i] = '#_' . $prefix . 'RESULT_#';
			}
			else
			{
				switch($result_key[$i])
				{
					case 'PTS_TEST_ARGUMENTS':
						$result_key[$i] = '#_' . $prefix . str_replace(' ', '', $pts_test_arguments) . '_#';
						break;
					case 'PTS_USER_SET_ARGUMENTS':
						$result_key[$i] = '#_' . $prefix . str_replace(' ', '', $extra_arguments) . '_#';
						break;
				}
			}

			// The actual parsing here
			$start_result_pos = strrpos($result_template[$i], $result_key[$i]);

			if($prefix != null && $start_result_pos === false)
			{
				// XXX: technically the $prefix check shouldn't be needed, verify whether safe to have this check be unconditional on start_result_pos failing...
				return false;
			}

			$space_out_chars = array('(', ')', "\t");

			if((isset($result_template[$i][($start_result_pos - 1)]) && $result_template[$i][($start_result_pos - 1)] == '/') || (isset($result_template[$i][($start_result_pos + strlen($result_key[$i]))]) && $result_template[$i][($start_result_pos + strlen($result_key[$i]))] == '/'))
			{
				array_push($space_out_chars, '/');
			}

			$end_result_pos = $start_result_pos + strlen($result_key[$i]);
			$end_result_line_pos = strpos($result_template[$i], "\n", $end_result_pos);
			$result_template_line = substr($result_template[$i], 0, ($end_result_line_pos === false ? strlen($result_template[$i]) : $end_result_line_pos));
			$result_template_line = substr($result_template_line, strrpos($result_template_line, "\n"));
			$result_template_r = explode(' ', pts_strings::trim_spaces(str_replace($space_out_chars, ' ', str_replace('=', ' = ', $result_template_line))));
			$result_template_r_pos = array_search($result_key[$i], $result_template_r);

			if($result_template_r_pos === false)
			{
				// Look for an element that partially matches, if like a '.' or '/sec' or some other pre/post-fix is present
				foreach($result_template_r as $i => $r_check)
				{
					if(isset($result_key[$i]) && strpos($r_check, $result_key[$i]) !== false)
					{
						$result_template_r_pos = $i;
						break;
					}
				}
			}

			$search_key = null;
			$line_before_key = null;

			if(isset($result_line_hint[$i]) && $result_line_hint[$i] != null && strpos($result_template_line, $result_line_hint[$i]) !== false)
			{
				$search_key = $result_line_hint[$i];
			}
			else if(isset($result_line_before_hint[$i]) && $result_line_before_hint[$i] != null && strpos($result_template[$i], $result_line_hint[$i]) !== false)
			{
				$search_key = null; // doesn't really matter what this value is
			}
			else if(isset($result_line_after_hint[$i]) && $result_line_after_hint[$i] != null && strpos($result_template[$i], $result_line_hint[$i]) !== false)
			{
				$search_key = null; // doesn't really matter what this value is
			}
			else
			{
				foreach($result_template_r as $line_part)
				{
					if(strpos($line_part, ':') !== false && strlen($line_part) > 1)
					{
						// add some sort of && strrpos($result_template[$i], $line_part)  to make sure there isn't two of the same $search_key
						$search_key = $line_part;
						break;
					}
				}

				if($search_key == null && isset($result_key[$i]))
				{
					// Just try searching for the first part of the string
					/*
					for($i = 0; $i < $result_template_r_pos; $i++)
					{
						$search_key .= $result_template_r[$i] . ' ';
					}
					*/

					// This way if there are ) or other characters stripped, the below method will work where the above one will not
					$search_key = substr($result_template_line, 0, strpos($result_template_line, $result_key[$i]));
				}
			}

			if(is_file($log_file))
			{
				$result_output = file_get_contents($log_file);
			}
			else
			{
				// Nothing to parse
				return false;
			}

			$test_results = array();

			if($search_key != null || (isset($result_line_before_hint[$i]) && $result_line_before_hint[$i] != null) || (isset($result_line_after_hint[$i]) && $result_line_after_hint[$i]) != null || (isset($result_key[$i]) && $result_template_r[0] == $result_key[$i]))
			{
				$is_multi_match = !empty($multi_match[$i]) && $multi_match[$i] != 'NONE';

				do
				{
					$result_count = count($test_results);

					if($result_line_before_hint[$i] != null)
					{
						pts_client::test_profile_debug_message('Result Parsing Line Before Hint: ' . $result_line_before_hint[$i]);
						$result_line = substr($result_output, strpos($result_output, "\n", strrpos($result_output, $result_line_before_hint[$i])));
						$result_line = substr($result_line, 0, strpos($result_line, "\n", 1));
						$result_output = substr($result_output, 0, strrpos($result_output, "\n", strrpos($result_output, $result_line_before_hint[$i]))) . "\n";
					}
					else if($result_line_after_hint[$i] != null)
					{
						pts_client::test_profile_debug_message('Result Parsing Line After Hint: ' . $result_line_after_hint[$i]);
						$result_line = substr($result_output, 0, strrpos($result_output, "\n", strrpos($result_output, $result_line_before_hint[$i])));
						$result_line = substr($result_line, strrpos($result_line, "\n", 1) + 1);
						$result_output = null;
					}
					else if($search_key != null)
					{
						$search_key = trim($search_key);
						pts_client::test_profile_debug_message('Result Parsing Search Key: ' . $search_key);
						$result_line = substr($result_output, 0, strpos($result_output, "\n", strrpos($result_output, $search_key)));
						$start_of_line = strrpos($result_line, "\n");
						$result_output = substr($result_line, 0, $start_of_line) . "\n";
						$result_line = substr($result_line, $start_of_line + 1);
					}
					else
					{
						// Condition $result_template_r[0] == $result_key[$i], include entire file since there is nothing to search
						pts_client::test_profile_debug_message('No Result Parsing Hint, Including Entire Result Output');
						$result_line = trim($result_output);
					}
					pts_client::test_profile_debug_message('Result Line: ' . $result_line);

					$result_r = explode(' ', pts_strings::trim_spaces(str_replace($space_out_chars, ' ', str_replace('=', ' = ', $result_line))));
					$result_r_pos = array_search($result_key[$i], $result_r);

					if(!empty($result_before_string[$i]))
					{
						// Using ResultBeforeString tag
						$result_before_this = array_search($result_before_string[$i], $result_r);

						if($result_before_this !== false)
						{
							array_push($test_results, $result_r[($result_before_this - 1)]);
						}
					}
					else if(isset($result_r[$result_template_r_pos]))
					{
						array_push($test_results, $result_r[$result_template_r_pos]);
					}
				}
				while($is_multi_match && count($test_results) != $result_count && !empty($result_output));
			}

			foreach($test_results as $x => &$test_result)
			{
				if($strip_from_result[$i] != null)
				{
					$test_result = str_replace($strip_from_result[$i], null, $test_result);
				}
				if($strip_result_postfix[$i] != null && substr($test_result, 0 - strlen($strip_result_postfix[$i])) == $strip_result_postfix[$i])
				{
					$test_result = substr($test_result, 0, 0 - strlen($strip_result_postfix[$i]));
				}

				// Expand validity checking here
				if($is_numeric_check == true && is_numeric($test_result) == false)
				{
					unset($test_results[$x]);
					continue;
				}

				if($result_divide_by[$i] != null && is_numeric($result_divide_by[$i]) && $result_divide_by[$i] != 0)
				{
					$test_result = $test_result / $result_divide_by[$i];
				}
				if($result_multiply_by[$i] != null && is_numeric($result_multiply_by[$i]) && $result_multiply_by[$i] != 0)
				{
					$test_result = $test_result * $result_multiply_by[$i];
				}
			}

			if(empty($test_results))
			{
				continue;
			}

			switch($multi_match[$i])
			{
				case 'REPORT_ALL':
					$test_result = implode(',', $test_results);
					break;
				case 'AVERAGE':
				default:
					$test_result = array_sum($test_results) / count($test_results);
					break;
			}

			if($test_result != false)
			{
				if($result_scale[$i] != null)
				{
					$test_run_request->test_profile->set_result_scale($result_scale[$i]);
				}
				if($result_proportion[$i] != null)
				{
					$test_run_request->test_profile->set_result_proportion($result_proportion[$i]);
				}
				if($result_precision[$i] != null)
				{
					$test_run_request->set_result_precision($result_precision[$i]);
				}

				break;
			}
		}

		return $test_result;
	}
}

?>
