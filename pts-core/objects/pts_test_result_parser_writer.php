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
		$result_template = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ResultsParser/OutputTemplate');
		$result_match_test_arguments = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ResultsParser/MatchToTestArguments');
		$result_key = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ResultsParser/ResultKey');
		$result_line_hint = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ResultsParser/LineHint');
		$result_line_before_hint = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ResultsParser/LineBeforeHint');
		$result_line_after_hint = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ResultsParser/LineAfterHint');
		$result_before_string = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ResultsParser/ResultBeforeString');
		$strip_from_result = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ResultsParser/StripFromResult');
		$strip_result_postfix = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ResultsParser/StripResultPostfix');
		$multi_match = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ResultsParser/MultiMatch');
		$result_divide_by = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ResultsParser/DivideResultBy');
		$result_multiply_by = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ResultsParser/MultiplyResultBy');

		foreach(array_keys($result_template) as $i)
		{
			$this->xml_writer->addXmlNode('PhoronixTestSuite/ResultsParser/OutputTemplate', $result_template[$i]);
			$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/ResultsParser/MatchToTestArguments', $result_match_test_arguments[$i]);
			$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/ResultsParser/ResultKey', $result_key[$i]);
			$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/ResultsParser/LineHint', $result_line_hint[$i]);
			$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/ResultsParser/LineBeforeHint', $result_line_before_hint[$i]);
			$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/ResultsParser/LineAfterHint', $result_line_after_hint[$i]);
			$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/ResultsParser/ResultBeforeString', $result_before_string[$i]);
			$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/ResultsParser/StripFromResult', $strip_from_result[$i]);
			$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/ResultsParser/StripResultPostfix', $strip_result_postfix[$i]);
			$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/ResultsParser/MultiMatch', $multi_match[$i]);
			$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/ResultsParser/DivideResultBy', $result_divide_by[$i]);
			$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/ResultsParser/MultiplyResultBy', $result_multiply_by[$i]);
		}

		$result_iqc_source_file = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ImageParser/SourceImage');
		$result_match_test_arguments = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ImageParser/MatchToTestArguments');
		$result_iqc_image_x = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ImageParser/ImageX');
		$result_iqc_image_y = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ImageParser/ImageY');
		$result_iqc_image_width = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ImageParser/ImageWidth');
		$result_iqc_image_height = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ImageParser/ImageHeight');

		foreach(array_keys($result_iqc_source_file) as $i)
		{
			$this->xml_writer->addXmlNode('PhoronixTestSuite/ImageParser/SourceImage', $result_iqc_source_file[$i]);
			$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/ImageParser/MatchToTestArguments', $result_match_test_arguments[$i]);
			$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/ImageParser/ImageX', $result_iqc_image_x[$i]);
			$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/ImageParser/ImageY', $result_iqc_image_y[$i]);
			$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/ImageParser/ImageWidth', $result_iqc_image_width[$i]);
			$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/ImageParser/ImageHeight', $result_iqc_image_height[$i]);
		}

		$monitor_sensor = $xml_parser->getXMLArrayValues('PhoronixTestSuite/SystemMonitor/Sensor');
		$monitor_frequency = $xml_parser->getXMLArrayValues('PhoronixTestSuite/SystemMonitor/PollingFrequency');
		$monitor_report_as = $xml_parser->getXMLArrayValues('PhoronixTestSuite/SystemMonitor/Report');

		foreach(array_keys($monitor_sensor) as $i)
		{
			$this->xml_writer->addXmlNode('PhoronixTestSuite/SystemMonitor/Sensor', $monitor_sensor[$i]);
			$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/SystemMonitor/PollingFrequency', $monitor_frequency[$i]);
			$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/SystemMonitor/Report', $monitor_report_as[$i]);
		}

		$extra_data_id = $xml_parser->getXMLArrayValues('PhoronixTestSuite/ExtraData/Identifier');

		foreach(array_keys($extra_data_id) as $i)
		{
			$this->xml_writer->addXmlNode('PhoronixTestSuite/ExtraData/Identifier', $extra_data_id[$i]);
		}
	}
}

?>
