<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010, Phoronix Media
	Copyright (C) 2010, Michael Larabel

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

class pts_result_parser
{
	public static function parse_result(&$test_profile, &$test_run_request, $parse_xml_file, $test_log_file)
	{
		$test_identifier = $test_run_request->get_identifier();
		$extra_arguments = $test_run_request->get_arguments();
		$pts_test_arguments = $pts_test_arguments = trim($test_profile->get_default_arguments() . " " . str_replace($test_profile->get_default_arguments(), "", $extra_arguments) . " " . $test_profile->get_default_post_arguments());
		switch($test_profile->get_result_format())
		{
			case "IMAGE_COMPARISON":
				$test_result = pts_result_parser::parse_iqc_result($test_identifier, $parse_xml_file, $test_log_file, $pts_test_arguments, $extra_arguments);
				break;
			case "PASS_FAIL":
			case "MULTI_PASS_FAIL":
				$test_result = pts_result_parser::parse_generic_result($test_identifier, $parse_xml_file, $test_log_file, $pts_test_arguments, $extra_arguments);
				break;
			default:
				$test_result = pts_result_parser::parse_numeric_result($test_identifier, $parse_xml_file, $test_log_file, $pts_test_arguments, $extra_arguments);
				break;
		}

		return $test_result;
	}
	protected static function parse_iqc_result($test_identifier, $parse_xml_file, $log_file, $pts_test_arguments, $extra_arguments)
	{
		$results_parser_xml = new pts_parse_results_tandem_XmlReader($parse_xml_file);
		$result_match_test_arguments = $results_parser_xml->getXMLArrayValues(P_RESULTS_PARSER_MATCH_TO_TEST_ARGUMENTS);
		$result_iqc_source_file = $results_parser_xml->getXMLArrayValues(P_RESULTS_PARSER_SOURCE_IMAGE);
		$result_iqc_image_x = $results_parser_xml->getXMLArrayValues(P_RESULTS_PARSER_IMAGE_X);
		$result_iqc_image_y = $results_parser_xml->getXMLArrayValues(P_RESULTS_PARSER_IMAGE_Y);
		$result_iqc_image_width = $results_parser_xml->getXMLArrayValues(P_RESULTS_PARSER_IMAGE_WIDTH);
		$result_iqc_image_height = $results_parser_xml->getXMLArrayValues(P_RESULTS_PARSER_IMAGE_HEIGHT);

		$test_result = false;

		if(!extension_loaded("gd"))
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

			if(is_file(TEST_ENV_DIR . $test_identifier . '/' . $result_iqc_source_file[$i]))
			{
				$iqc_source_file = TEST_ENV_DIR . $test_identifier . '/' . $result_iqc_source_file[$i];
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
			$test_result = TEST_ENV_DIR . $test_identifier . "/iqc.png";
			imagepng($img_sliced, $test_result);

			if($test_result != false)
			{
				break;
			}
		}

		return $test_result;
	}
	protected static function parse_numeric_result($test_identifier, $parse_xml_file, $log_file, $pts_test_arguments, $extra_arguments)
	{
		return self::parse_result_process($test_identifier, $parse_xml_file, $log_file, $pts_test_arguments, $extra_arguments, true);
	}
	protected static function parse_generic_result($test_identifier, $parse_xml_file, $log_file, $pts_test_arguments, $extra_arguments)
	{
		return self::parse_result_process($test_identifier, $parse_xml_file, $log_file, $pts_test_arguments, $extra_arguments, false);
	}
	protected static function parse_result_process($test_identifier, $parse_xml_file, $log_file, $pts_test_arguments, $extra_arguments, $is_numeric_check = true)
	{
		$results_parser_xml = new pts_parse_results_tandem_XmlReader($parse_xml_file);
		$result_match_test_arguments = $results_parser_xml->getXMLArrayValues(P_RESULTS_PARSER_MATCH_TO_TEST_ARGUMENTS);
		$result_template = $results_parser_xml->getXMLArrayValues(P_RESULTS_PARSER_TEMPLATE);
		$result_key = $results_parser_xml->getXMLArrayValues(P_RESULTS_PARSER_RESULT_KEY);
		$result_line_hint = $results_parser_xml->getXMLArrayValues(P_RESULTS_PARSER_LINE_HINT);
		$result_line_before_hint = $results_parser_xml->getXMLArrayValues(P_RESULTS_PARSER_LINE_BEFORE_HINT);
		$result_line_after_hint = $results_parser_xml->getXMLArrayValues(P_RESULTS_PARSER_LINE_AFTER_HINT);
		$result_before_string = $results_parser_xml->getXMLArrayValues(P_RESULTS_PARSER_RESULT_BEFORE_STRING);
		$result_divide_by = $results_parser_xml->getXMLArrayValues(P_RESULTS_PARSER_DIVIDE_BY);
		$result_multiply_by = $results_parser_xml->getXMLArrayValues(P_RESULTS_PARSER_MULTIPLY_BY);
		$strip_from_result = $results_parser_xml->getXMLArrayValues(P_RESULTS_PARSER_STRIP_FROM_RESULT);
		$strip_result_postfix = $results_parser_xml->getXMLArrayValues(P_RESULTS_PARSER_STRIP_RESULT_POSTFIX);
		$multi_match = $results_parser_xml->getXMLArrayValues(P_RESULTS_PARSER_MULTI_MATCH);
		$test_result = false;

		for($i = 0; $i < count($result_template); $i++)
		{
			if(!empty($result_match_test_arguments[$i]) && strpos($pts_test_arguments, $result_match_test_arguments[$i]) === false)
			{
				// This is not the ResultsParser XML section to use as the MatchToTestArguments does not match the PTS test arguments
				continue;
			}

			if($result_key[$i] == null)
			{
				$result_key[$i] = "#_RESULT_#";
			}
			else
			{
				switch($result_key[$i])
				{
					case "PTS_TEST_ARGUMENTS":
						$result_key[$i] = "#_" . str_replace(' ', '', $pts_test_arguments) . "_#";
						break;
					case "PTS_USER_SET_ARGUMENTS":
						$result_key[$i] = "#_" . str_replace(' ', '', $extra_arguments) . "_#";
						break;
				}
			}

			// The actual parsing here
			$start_result_pos = strrpos($result_template[$i], $result_key[$i]);
			$end_result_pos = $start_result_pos + strlen($result_key[$i]);
			$end_result_line_pos = strpos($result_template[$i], "\n", $end_result_pos);
			$result_template_line = substr($result_template[$i], 0, ($end_result_line_pos === false ? strlen($result_template[$i]) : $end_result_line_pos));
			$result_template_line = substr($result_template_line, strrpos($result_template_line, "\n"));
			$result_template_r = explode(' ', pts_strings::trim_spaces(str_replace(array('(', ')', "\t"), ' ', str_replace('=', ' = ', $result_template_line))));
			$result_template_r_pos = array_search($result_key[$i], $result_template_r);

			if($result_template_r_pos === false)
			{
				// Look for an element that partially matches, if like a '.' or '/sec' or some other pre/post-fix is present
				foreach($result_template_r as $i => $r_check)
				{
					if(strpos($check, $result_key[$i]) !== false)
					{
						$result_template_r_pos = $i;
						break;
					}
				}
			}

			$search_key = null;
			$line_before_key = null;

			if($result_line_hint[$i] != null && strpos($result_template_line, $result_line_hint[$i]) !== false)
			{
				$search_key = $result_line_hint[$i];
			}
			else if($result_line_before_hint[$i] != null && strpos($result_template[$i], $result_line_hint[$i]) !== false)
			{
				$search_key = null; // doesn't really matter what this value is
			}
			else if($result_line_after_hint[$i] != null && strpos($result_template[$i], $result_line_hint[$i]) !== false)
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

				if($search_key == null)
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

			if($search_key != null || $result_line_before_hint[$i] != null || $result_line_after_hint[$i] != null)
			{
				$is_multi_match = !empty($multi_match[$i]) && $multi_match[$i] != "NONE";
				$test_results = array();

				do
				{
					$result_count = count($test_results);

					if($result_line_before_hint[$i] != null)
					{
						pts_test_profile_debug_message("Result Parsing Line Before Hint: " . $result_line_before_hint[$i]);
						$result_line = substr($result_output, strpos($result_output, "\n", strrpos($result_output, $result_line_before_hint[$i])));
						$result_line = substr($result_line, 0, strpos($result_line, "\n", 1));
						$result_output = substr($result_output, 0, strrpos($result_output, "\n", strrpos($result_output, $result_line_before_hint[$i]))) . "\n";
					}
					if($result_line_after_hint[$i] != null)
					{
						pts_test_profile_debug_message("Result Parsing Line After Hint: " . $result_line_after_hint[$i]);
						$result_line = substr($result_output, 0, strrpos($result_output, "\n", strrpos($result_output, $result_line_before_hint[$i])));
						$result_line = substr($result_line, strrpos($result_line, "\n", 1) + 1);
						$result_output = null;
					}
					else
					{
						pts_test_profile_debug_message("Result Parsing Search Key: " . $search_key);
						$result_line = substr($result_output, 0, strpos($result_output, "\n", strrpos($result_output, $search_key)));
						$start_of_line = strrpos($result_line, "\n");
						$result_output = substr($result_line, 0, $start_of_line) . "\n";
						$result_line = substr($result_line, $start_of_line + 1);
					}

					pts_test_profile_debug_message("Result Line: " . $result_line);

					$result_r = explode(' ', pts_strings::trim_spaces(str_replace(array('(', ')', "\t"), ' ', str_replace('=', ' = ', $result_line))));
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
				case "REPORT_ALL":
					$test_result = implode(',', $test_results);
					break;
				case "AVERAGE":
				default:
					$test_result = array_sum($test_results) / count($test_results);
					break;
			}

			if($test_result != false)
			{
				break;
			}
		}

		return $test_result;
	}
}

?>
