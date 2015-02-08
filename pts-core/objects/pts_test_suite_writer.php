<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2015, Phoronix Media
	Copyright (C) 2010 - 2015, Michael Larabel

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

class pts_test_suite_writer
{
	private $xml_writer = null;
	private $result_identifier = null;

	public function __construct($result_identifier = null, &$xml_writer = null)
	{
		$this->result_identifier = $result_identifier;

		if($xml_writer instanceof nye_XmlWriter)
		{
			$this->xml_writer = $xml_writer;
		}
		else
		{
			$this->xml_writer = new nye_XmlWriter();
		}
	}
	public function get_xml()
	{
		return $this->xml_writer->getXML();
	}
	public function save_xml($to_save)
	{
		return $this->xml_writer->saveXMLFile($to_save);
	}
	public function clean_save_name_string($input)
	{
		$input = strtolower($input);
		$input = pts_strings::remove_redundant(pts_strings::keep_in_string(str_replace(' ', '-', trim($input)), pts_strings::CHAR_LETTER | pts_strings::CHAR_NUMERIC | pts_strings::CHAR_DASH), '-');

		if(strlen($input) > 126)
		{
			$input = substr($input, 0, 126);
		}

		return $input;
	}
	public function add_suite_information_from_reader(&$xml_reader)
	{
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/SuiteInformation/Title', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/SuiteInformation/Version', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/SuiteInformation/TestType', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/SuiteInformation/Description', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/SuiteInformation/Maintainer', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/SuiteInformation/PreRunMessage', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/SuiteInformation/PostRunMessage', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/SuiteInformation/RunMode', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/SuiteInformation/RequiresCoreVersionMin', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/SuiteInformation/RequiresCoreVersionMax', $xml_reader);
	}
	public function add_suite_information($name, $version, $maintainer, $type, $description)
	{
		$this->xml_writer->addXmlNode('PhoronixTestSuite/SuiteInformation/Title', $name);
		$this->xml_writer->addXmlNode('PhoronixTestSuite/SuiteInformation/Version', $version);
		$this->xml_writer->addXmlNode('PhoronixTestSuite/SuiteInformation/TestType', $type);
		$this->xml_writer->addXmlNode('PhoronixTestSuite/SuiteInformation/Description', $description);
		$this->xml_writer->addXmlNode('PhoronixTestSuite/SuiteInformation/Maintainer', $maintainer);
	}
	public function add_to_suite_from_reader(&$xml_reader)
	{
		$test_names = $xml_reader->getXMLArrayValues('PhoronixTestSuite/Execute/Test');
		$sub_arguments = $xml_reader->getXMLArrayValues('PhoronixTestSuite/Execute/Arguments');
		$sub_arguments_description = $xml_reader->getXMLArrayValues('PhoronixTestSuite/Execute/Description');
		$sub_modes = $xml_reader->getXMLArrayValues('PhoronixTestSuite/Execute/Mode');
		$override_test_options = $xml_reader->getXMLArrayValues('PhoronixTestSuite/Execute/OverrideTestOptions');

		for($i = 0; $i < count($test_names); $i++)
		{
			$identifier = pts_openbenchmarking::evaluate_string_to_qualifier($test_names[$i]);

			if(empty($identifier))
			{
				echo PHP_EOL . $test_names[$i] . ' fails.' . PHP_EOL;
				exit;
			}
			$identifier = substr($identifier, 0, strrpos($identifier, '-')); // strip the version for now

			$this->add_to_suite($identifier, $sub_arguments[$i], $sub_arguments_description[$i], $sub_modes[$i], $override_test_options[$i]);
		}
	}
	public function add_to_suite($identifier, $arguments, $description, $mode = null, $override = null)
	{
		$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/Execute/Test', $identifier);
		$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/Execute/Arguments', $arguments);
		$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/Execute/Description', $description);
		$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/Execute/Mode', $mode);
		$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/Execute/OverrideTestOptions', $override);
	}
	public function add_to_suite_from_result_object(&$r_o)
	{
		$this->add_to_suite($r_o->test_profile->get_identifier(), $r_o->get_arguments(), $r_o->get_arguments_description());
	}
}

?>
