<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2017, Phoronix Media
	Copyright (C) 2009 - 2017, Michael Larabel

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

class pts_validation
{
	public static function process_libxml_errors()
	{
		$error_queue = array();
		$errors = libxml_get_errors();

		foreach($errors as $i => &$error)
		{
			if(isset($error_queue[$error->line]))
			{
				// There's already been an error reported for this line
				unset($errors[$i]);
			}

			switch($error->code)
			{
				case 1840: // Not in enumeration
				case 1839: // Not in pattern
				case 1871: // Missing / invalid element
				case 1833: // Below the minInclusive value
					echo PHP_EOL . $error->message;
					echo 'Line ' . $error->line . ': ' . $error->file . PHP_EOL;
					$error_queue[$error->line] = true;
					unset($errors[$i]);
					break;
			}
		}

		if(count($errors) > 0 && PTS_IS_CLIENT)
		{
			// DEBUG
			print_r($errors);
		}

		libxml_clear_errors();
	}
	public static function test_profile_permitted_files()
	{
		$allowed_files = array('downloads.xml', 'test-definition.xml', 'results-definition.xml', 'install.sh', 'support-check.sh', 'pre.sh', 'post.sh', 'interim.sh', 'post-cache-share.sh');

		foreach(pts_types::operating_systems() as $os)
		{
			$os = strtolower($os[0]);
			$allowed_files[] = 'support-check_' . $os . '.sh';
			$allowed_files[] = 'install_' . $os . '.sh';
			$allowed_files[] = 'pre_' . $os . '.sh';
			$allowed_files[] = 'post_' . $os . '.sh';
			$allowed_files[] = 'interim_' . $os . '.sh';
		}

		return $allowed_files;
	}
	public static function check_xml_tags(&$obj, &$tags_to_check, &$append_missing_to)
	{
		foreach($tags_to_check as $tag_check)
		{
			$to_check = $obj->xml_parser->getXMLValue($tag_check[0]);

			if(empty($to_check))
			{
				$append_missing_to[] = $tag_check;
			}
		}
	}
	public static function print_issue($type, $problems_r)
	{
		foreach($problems_r as $error)
		{
			list($target, $description) = $error;

			echo PHP_EOL . $type . ': ' . $description . PHP_EOL;

			if(!empty($target))
			{
				echo 'TARGET: ' . $target . PHP_EOL;
			}
		}
	}
	public static function validate_test_suite(&$test_suite)
	{
		// Validate the XML against the XSD Schemas
		libxml_clear_errors();

		// First rewrite the main XML file to ensure it is properly formatted, elements are ordered according to the schema, etc...
		$valid = $test_suite->validate();

		if($valid == false)
		{
			echo PHP_EOL . 'Errors occurred parsing the main XML.' . PHP_EOL;
			pts_validation::process_libxml_errors();
			return false;
		}
		else
		{
			echo PHP_EOL . 'Test Suite XML Is Valid.' . PHP_EOL;
		}

		return true;
	}
	public static function validate_test_profile(&$test_profile)
	{

		if($test_profile->get_file_location() == null)
		{
			echo PHP_EOL . 'ERROR: The file location of the XML test profile source could not be determined.' . PHP_EOL;
			return false;
		}

		// Validate the XML against the XSD Schemas
		libxml_clear_errors();

		// Now re-create the pts_test_profile object around the rewritten XML
		$test_profile = new pts_test_profile($test_profile->get_identifier());
		$valid = $test_profile->validate();

		if($valid == false)
		{
			echo PHP_EOL . 'Errors occurred parsing the main XML.' . PHP_EOL;
			pts_validation::process_libxml_errors();
			return false;
		}

		// Rewrite the main XML file to ensure it is properly formatted, elements are ordered according to the schema, etc...
		$test_profile_writer = new pts_test_profile_writer();
		$test_profile_writer->rebuild_test_profile($test_profile);
		$test_profile_writer->save_xml($test_profile->get_file_location());

		// Now re-create the pts_test_profile object around the rewritten XML
		$test_profile = new pts_test_profile($test_profile->get_identifier());
		$valid = $test_profile->validate();

		if($valid == false)
		{
			echo PHP_EOL . 'Errors occurred parsing the main XML.' . PHP_EOL;
			pts_validation::process_libxml_errors();
			return false;
		}
		else
		{
			echo PHP_EOL . 'Test Profile XML Is Valid.' . PHP_EOL;
		}

		// Validate the downloads file
		$download_xml_file = $test_profile->get_file_download_spec();

		if(empty($download_xml_file) == false)
		{
			$writer = new pts_test_profile_downloads_writer();
			$writer->rebuild_download_file($test_profile);
			$writer->save_xml($download_xml_file);

			$dom = new DOMDocument();
			$dom->load($download_xml_file);
			$valid = $dom->schemaValidate(PTS_OPENBENCHMARKING_PATH . 'schemas/test-profile-downloads.xsd');

			if($valid == false)
			{
				echo PHP_EOL . 'Errors occurred parsing the downloads XML.' . PHP_EOL;
				pts_validation::process_libxml_errors();
				return false;
			}
			else
			{
				echo PHP_EOL . 'Test Downloads XML Is Valid.' . PHP_EOL;
			}


			// Validate the individual download files
			echo PHP_EOL . 'Testing File Download URLs.' . PHP_EOL;
			$files_missing = 0;
			$file_count = 0;

			foreach(pts_test_install_request::read_download_object_list($test_profile) as $download)
			{
				foreach($download->get_download_url_array() as $url)
				{
					$stream_context = pts_network::stream_context_create();
					stream_context_set_params($stream_context, array('notification' => 'pts_stream_status_callback'));
					$file_pointer = fopen($url, 'r', false, $stream_context);

					if($file_pointer == false)
					{
						echo 'File Missing: ' . $download->get_filename() . ' / ' . $url . PHP_EOL;
						$files_missing++;
					}
					else
					{
						fclose($file_pointer);
					}
					$file_count++;
				}
			}

			if($files_missing > 0) // && $file_count == $files_missing
			{
				return false;
			}
		}


		// Validate the parser file
		$parser_file = $test_profile->get_file_parser_spec();

		if(empty($parser_file) == false)
		{
			$writer = self::rebuild_result_parser_file($parser_file);
			$writer->saveXMLFile($parser_file);

			$dom = new DOMDocument();
			$dom->load($parser_file);
			$valid = $dom->schemaValidate(PTS_OPENBENCHMARKING_PATH . 'schemas/results-parser.xsd');

			if($valid == false)
			{
				echo PHP_EOL . 'Errors occurred parsing the results parser XML.' . PHP_EOL;
				pts_validation::process_libxml_errors();
				return false;
			}
			else
			{
				echo PHP_EOL . 'Test Results Parser XML Is Valid.' . PHP_EOL;
			}
		}

		// Make sure no extra files are in there
		$allowed_files = pts_validation::test_profile_permitted_files();

		foreach(pts_file_io::glob($test_profile->get_resource_dir() . '*') as $tp_file)
		{
			if(!is_file($tp_file) || !in_array(basename($tp_file), $allowed_files))
			{
				echo PHP_EOL . basename($tp_file) . ' is not allowed in the test package.' . PHP_EOL;
				return false;
			}
		}

		return true;
	}
	public static function rebuild_result_parser_file($xml_file)
	{
		$xml_writer = new nye_XmlWriter();
		$xml_parser = new nye_XmlReader($xml_file);
		$result_template = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ResultsParser/OutputTemplate');
		$result_match_test_arguments = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ResultsParser/MatchToTestArguments');
		$result_key = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ResultsParser/ResultKey');
		$result_line_hint = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ResultsParser/LineHint');
		$result_line_before_hint = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ResultsParser/LineBeforeHint');
		$result_line_after_hint = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ResultsParser/LineAfterHint');
		$result_before_string = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ResultsParser/ResultBeforeString');
		$result_after_string = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ResultsParser/ResultAfterString');
		$strip_from_result = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ResultsParser/StripFromResult');
		$strip_result_postfix = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ResultsParser/StripResultPostfix');
		$multi_match = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ResultsParser/MultiMatch');
		$file_format = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ResultsParser/FileFormat');
		$result_divide_by = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ResultsParser/DivideResultBy');
		$result_multiply_by = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ResultsParser/MultiplyResultBy');
		$result_scale = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ResultsParser/ResultScale');
		$result_proportion = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ResultsParser/ResultProportion');
		$result_precision = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ResultsParser/ResultPrecision');
		$result_args_desc = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ResultsParser/ArgumentsDescription');

		foreach(array_keys($result_template) as $i)
		{
			$xml_writer->addXmlNode('PhoronixTestSuite/ResultsParser/OutputTemplate', $result_template[$i]);
			$xml_writer->addXmlNodeWNE('PhoronixTestSuite/ResultsParser/MatchToTestArguments', $result_match_test_arguments[$i]);
			$xml_writer->addXmlNodeWNE('PhoronixTestSuite/ResultsParser/ResultKey', $result_key[$i]);
			$xml_writer->addXmlNodeWNE('PhoronixTestSuite/ResultsParser/LineHint', $result_line_hint[$i]);
			$xml_writer->addXmlNodeWNE('PhoronixTestSuite/ResultsParser/LineBeforeHint', $result_line_before_hint[$i]);
			$xml_writer->addXmlNodeWNE('PhoronixTestSuite/ResultsParser/LineAfterHint', $result_line_after_hint[$i]);
			$xml_writer->addXmlNodeWNE('PhoronixTestSuite/ResultsParser/ResultBeforeString', $result_before_string[$i]);
			$xml_writer->addXmlNodeWNE('PhoronixTestSuite/ResultsParser/ResultAfterString', $result_after_string[$i]);
			$xml_writer->addXmlNodeWNE('PhoronixTestSuite/ResultsParser/StripFromResult', $strip_from_result[$i]);
			$xml_writer->addXmlNodeWNE('PhoronixTestSuite/ResultsParser/StripResultPostfix', $strip_result_postfix[$i]);
			$xml_writer->addXmlNodeWNE('PhoronixTestSuite/ResultsParser/MultiMatch', $multi_match[$i]);
			$xml_writer->addXmlNodeWNE('PhoronixTestSuite/ResultsParser/DivideResultBy', $result_divide_by[$i]);
			$xml_writer->addXmlNodeWNE('PhoronixTestSuite/ResultsParser/MultiplyResultBy', $result_multiply_by[$i]);
			$xml_writer->addXmlNodeWNE('PhoronixTestSuite/ResultsParser/ResultScale', $result_scale[$i]);
			$xml_writer->addXmlNodeWNE('PhoronixTestSuite/ResultsParser/ResultProportion', $result_proportion[$i]);
			$xml_writer->addXmlNodeWNE('PhoronixTestSuite/ResultsParser/ResultPrecision', $result_precision[$i]);
			$xml_writer->addXmlNodeWNE('PhoronixTestSuite/ResultsParser/ArgumentsDescription', $result_args_desc[$i]);
			$xml_writer->addXmlNodeWNE('PhoronixTestSuite/ResultsParser/FileFormat', $file_format[$i]);
		}

		$result_iqc_source_file = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ImageParser/SourceImage');
		$result_match_test_arguments = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ImageParser/MatchToTestArguments');
		$result_iqc_image_x = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ImageParser/ImageX');
		$result_iqc_image_y = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ImageParser/ImageY');
		$result_iqc_image_width = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ImageParser/ImageWidth');
		$result_iqc_image_height = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ImageParser/ImageHeight');

		foreach(array_keys($result_iqc_source_file) as $i)
		{
			$xml_writer->addXmlNode('PhoronixTestSuite/ImageParser/SourceImage', $result_iqc_source_file[$i]);
			$xml_writer->addXmlNodeWNE('PhoronixTestSuite/ImageParser/MatchToTestArguments', $result_match_test_arguments[$i]);
			$xml_writer->addXmlNodeWNE('PhoronixTestSuite/ImageParser/ImageX', $result_iqc_image_x[$i]);
			$xml_writer->addXmlNodeWNE('PhoronixTestSuite/ImageParser/ImageY', $result_iqc_image_y[$i]);
			$xml_writer->addXmlNodeWNE('PhoronixTestSuite/ImageParser/ImageWidth', $result_iqc_image_width[$i]);
			$xml_writer->addXmlNodeWNE('PhoronixTestSuite/ImageParser/ImageHeight', $result_iqc_image_height[$i]);
		}

		$monitor_sensor = $xml_parser->getXMLArrayValues('PhoronixTestSuite/SystemMonitor/Sensor');
		$monitor_frequency = $xml_parser->getXMLArrayValues('PhoronixTestSuite/SystemMonitor/PollingFrequency');
		$monitor_report_as = $xml_parser->getXMLArrayValues('PhoronixTestSuite/SystemMonitor/Report');

		foreach(array_keys($monitor_sensor) as $i)
		{
			$xml_writer->addXmlNode('PhoronixTestSuite/SystemMonitor/Sensor', $monitor_sensor[$i]);
			$xml_writer->addXmlNodeWNE('PhoronixTestSuite/SystemMonitor/PollingFrequency', $monitor_frequency[$i]);
			$xml_writer->addXmlNodeWNE('PhoronixTestSuite/SystemMonitor/Report', $monitor_report_as[$i]);
		}

		$extra_data_id = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ExtraData/Identifier');

		foreach(array_keys($extra_data_id) as $i)
		{
			$xml_writer->addXmlNode('PhoronixTestSuite/ExtraData/Identifier', $extra_data_id[$i]);
		}

		return $xml_writer;
	}
}

?>
