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

pts_load_xml_definitions("test-suite.xml");

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
	public function add_suite_information($name, $version, $maintainer, $type, $description)
	{
		$this->xml_writer->addXmlNode(P_SUITE_TITLE, $name);
		$this->xml_writer->addXmlNode(P_SUITE_VERSION, $version);
		$this->xml_writer->addXmlNode(P_SUITE_TYPE, $type);
		$this->xml_writer->addXmlNode(P_SUITE_DESCRIPTION, $description);
		$this->xml_writer->addXmlNode(P_SUITE_MAINTAINER, $maintainer);
	}
	public function add_to_suite($identifier, $version, $arguments, $description)
	{
		$this->xml_writer->addXmlNodeWNE(P_SUITE_TEST_NAME, $identifier);
		$this->xml_writer->addXmlNodeWNE(P_SUITE_TEST_PROFILE_VERSION, $version);
		$this->xml_writer->addXmlNodeWNE(P_SUITE_TEST_ARGUMENTS, $arguments);
		$this->xml_writer->addXmlNodeWNE(P_SUITE_TEST_DESCRIPTION, $description);
		//$this->xml_writer->addXmlNodeWNE(P_SUITE_TEST_MODE, $description);
		//$this->xml_writer->addXmlNodeWNE(P_SUITE_TEST_OVERRIDE_OPTIONS, $description);
	}
	public function add_to_suite_from_result_object(&$r_o)
	{
		$this->add_to_suite($r_o->test_profile->get_identifier(), $r_o->test_profile->get_test_profile_version(), $r_o->get_arguments(), $r_o->get_arguments_description());
	}
}

?>
