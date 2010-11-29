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

pts_load_xml_definitions("test-profile.xml");

class pts_test_profile_writer
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
	public function rebuild_test_profile(&$test_profile)
	{
		$this->add_test_information($test_profile->xml_parser);
		$this->add_test_data_section($test_profile->xml_parser);
		$this->add_test_settings($test_profile);
	}
	public function add_test_information(&$xml_reader)
	{
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_TITLE, $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_DESCRIPTION, $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_SCALE, $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_QUANTIFIER, $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_VERSION, $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_SUBTITLE, $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_DISPLAY_FORMAT, $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_PROPORTION, $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_EXECUTABLE, $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_RUNCOUNT, $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_IGNORERUNS, $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_INSTALLAGREEMENT, $xml_reader);
	}
	public function add_test_data_section(&$xml_reader)
	{
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_PTSVERSION, $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_HARDWARE_TYPE, $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_SOFTWARE_TYPE, $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_MAINTAINER, $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_LICENSE, $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_STATUS, $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_SUPPORTEDARCHS, $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_SUPPORTEDPLATFORMS, $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_EXDEP, $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_CTPEXTENDS, $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_ROOTNEEDED, $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_INSTALLAGREEMENT, $xml_reader);

		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_ENVIRONMENTSIZE, $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_ENVIRONMENT_TESTING_SIZE, $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_ESTIMATEDTIME, $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_PROJECTURL, $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_REQUIRES_COREVERSION_MIN, $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_REQUIRES_COREVERSION_MAX, $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_REFERENCE_SYSTEMS, $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_INTERNAL_TAGS, $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_ALLOW_RESULTS_SHARING, $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_AUTO_SAVE_RESULTS, $xml_reader);
	}
	public function add_test_settings(&$test_profile)
	{
		$xml_reader = &$test_profile->xml_parser;
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_DEFAULTARGUMENTS, $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_DEFAULT_POST_ARGUMENTS, $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_ALLOW_CACHE_SHARE, $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_MIN_LENGTH, $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE(P_TEST_MAX_LENGTH, $xml_reader);

		foreach($test_profile->get_test_option_objects() as $option)
		{
			$this->xml_writer->addXmlNode(P_TEST_OPTIONS_DISPLAYNAME, $option->get_name());
			$this->xml_writer->addXmlNodeWNE(P_TEST_OPTIONS_IDENTIFIER, $option->get_identifier());
			$this->xml_writer->addXmlNodeWNE(P_TEST_OPTIONS_ARGPREFIX, $option->get_option_prefix());
			$this->xml_writer->addXmlNodeWNE(P_TEST_OPTIONS_ARGPOSTFIX, $option->get_option_postfix());
			$this->xml_writer->addXmlNodeWNE(P_TEST_OPTIONS_DEFAULTENTRY, $option->get_option_default_raw());

			foreach($option->get_options_array() as $item)
			{
				$this->xml_writer->addXmlNode(P_TEST_OPTIONS_MENU_GROUP_NAME, $item[0]);
				$this->xml_writer->addXmlNodeWNE(P_TEST_OPTIONS_MENU_GROUP_VALUE, $item[1]);
				$this->xml_writer->addXmlNodeWNE(P_TEST_OPTIONS_MENU_GROUP_MESSAGE, $item[2]);
			}
		}

	}
}

?>
