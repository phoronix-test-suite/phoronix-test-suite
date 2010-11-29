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

class pts_test_result_parser_writer
{
	private $xml_writer = null;

	public function __construct()
	{
		$this->xml_writer = new nye_XmlWriter();
	}
	public function get_xml()
	{
		return $this->xml_writer->getXML();
	}
	public function save_xml($to_save)
	{
		return $this->xml_writer->saveXMLFile($to_save);
	}
	public function rebuild_parser_file($xml_file)
	{
		$xml_parser = new pts_parse_results_nye_XmlReader($xml_file);
		$result_template = $xml_parser->getXMLArrayValues(P_RESULTS_PARSER_TEMPLATE);
		$result_match_test_arguments = $xml_parser->getXMLArrayValues(P_RESULTS_PARSER_MATCH_TO_TEST_ARGUMENTS);
		$result_key = $xml_parser->getXMLArrayValues(P_RESULTS_PARSER_RESULT_KEY);
		$result_line_hint = $xml_parser->getXMLArrayValues(P_RESULTS_PARSER_LINE_HINT);
		$result_line_before_hint = $xml_parser->getXMLArrayValues(P_RESULTS_PARSER_LINE_BEFORE_HINT);
		$result_line_after_hint = $xml_parser->getXMLArrayValues(P_RESULTS_PARSER_LINE_AFTER_HINT);
		$result_before_string = $xml_parser->getXMLArrayValues(P_RESULTS_PARSER_RESULT_BEFORE_STRING);
		$strip_from_result = $xml_parser->getXMLArrayValues(P_RESULTS_PARSER_STRIP_FROM_RESULT);
		$strip_result_postfix = $xml_parser->getXMLArrayValues(P_RESULTS_PARSER_STRIP_RESULT_POSTFIX);
		$multi_match = $xml_parser->getXMLArrayValues(P_RESULTS_PARSER_MULTI_MATCH);
		$result_divide_by = $xml_parser->getXMLArrayValues(P_RESULTS_PARSER_DIVIDE_BY);
		$result_multiply_by = $xml_parser->getXMLArrayValues(P_RESULTS_PARSER_MULTIPLY_BY);

		foreach(array_keys($result_template) as $i)
		{
			$this->xml_writer->addXmlNode(P_RESULTS_PARSER_TEMPLATE, $result_template[$i]);
			$this->xml_writer->addXmlNodeWNE(P_RESULTS_PARSER_MATCH_TO_TEST_ARGUMENTS, $result_match_test_arguments[$i]);
			$this->xml_writer->addXmlNodeWNE(P_RESULTS_PARSER_RESULT_KEY, $result_key[$i]);
			$this->xml_writer->addXmlNodeWNE(P_RESULTS_PARSER_LINE_HINT, $result_line_hint[$i]);
			$this->xml_writer->addXmlNodeWNE(P_RESULTS_PARSER_LINE_BEFORE_HINT, $result_line_before_hint[$i]);
			$this->xml_writer->addXmlNodeWNE(P_RESULTS_PARSER_LINE_AFTER_HINT, $result_line_after_hint[$i]);
			$this->xml_writer->addXmlNodeWNE(P_RESULTS_PARSER_RESULT_BEFORE_STRING, $result_before_string[$i]);
			$this->xml_writer->addXmlNodeWNE(P_RESULTS_PARSER_STRIP_FROM_RESULT, $strip_from_result[$i]);
			$this->xml_writer->addXmlNodeWNE(P_RESULTS_PARSER_STRIP_RESULT_POSTFIX, $strip_result_postfix[$i]);
			$this->xml_writer->addXmlNodeWNE(P_RESULTS_PARSER_MULTI_MATCH, $multi_match[$i]);
			$this->xml_writer->addXmlNodeWNE(P_RESULTS_PARSER_DIVIDE_BY, $result_divide_by[$i]);
			$this->xml_writer->addXmlNodeWNE(P_RESULTS_PARSER_MULTIPLY_BY, $result_multiply_by[$i]);
		}

		$result_iqc_source_file = $xml_parser->getXMLArrayValues(P_IMAGE_PARSER_SOURCE_IMAGE);
		$result_match_test_arguments = $xml_parser->getXMLArrayValues(P_IMAGE_PARSER_MATCH_TO_TEST_ARGUMENTS);
		$result_iqc_image_x = $xml_parser->getXMLArrayValues(P_IMAGE_PARSER_IMAGE_X);
		$result_iqc_image_y = $xml_parser->getXMLArrayValues(P_IMAGE_PARSER_IMAGE_Y);
		$result_iqc_image_width = $xml_parser->getXMLArrayValues(P_IMAGE_PARSER_IMAGE_WIDTH);
		$result_iqc_image_height = $xml_parser->getXMLArrayValues(P_IMAGE_PARSER_IMAGE_HEIGHT);

		foreach(array_keys($result_iqc_source_file) as $i)
		{
			$this->xml_writer->addXmlNode(P_IMAGE_PARSER_SOURCE_IMAGE, $result_iqc_source_file[$i]);
			$this->xml_writer->addXmlNodeWNE(P_IMAGE_PARSER_MATCH_TO_TEST_ARGUMENTS, $result_match_test_arguments[$i]);
			$this->xml_writer->addXmlNodeWNE(P_IMAGE_PARSER_IMAGE_X, $result_iqc_image_x[$i]);
			$this->xml_writer->addXmlNodeWNE(P_IMAGE_PARSER_IMAGE_Y, $result_iqc_image_y[$i]);
			$this->xml_writer->addXmlNodeWNE(P_IMAGE_PARSER_IMAGE_WIDTH, $result_iqc_image_width[$i]);
			$this->xml_writer->addXmlNodeWNE(P_IMAGE_PARSER_IMAGE_HEIGHT, $result_iqc_image_height[$i]);
		}

		$monitor_sensor = $xml_parser->getXMLArrayValues(P_MONITOR_PARSER_SENSOR);
		$monitor_frequency = $xml_parser->getXMLArrayValues(P_MONITOR_PARSER_FREQUENCY);
		$monitor_report_as = $xml_parser->getXMLArrayValues(P_MONITOR_PARSER_REPORT);

		foreach(array_keys($monitor_sensor) as $i)
		{
			$this->xml_writer->addXmlNode(P_MONITOR_PARSER_SENSOR, $monitor_sensor[$i]);
			$this->xml_writer->addXmlNodeWNE(P_MONITOR_PARSER_FREQUENCY, $monitor_frequency[$i]);
			$this->xml_writer->addXmlNodeWNE(P_MONITOR_PARSER_REPORT, $monitor_report_as[$i]);
		}
	}
}

?>
