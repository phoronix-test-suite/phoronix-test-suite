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
	public function rebuild_test_profile($test_profile)
	{
		$test_profile->xml_parser->block_test_extension_support();

		$this->add_test_information($test_profile->xml_parser);
		$this->add_test_data_section($test_profile->xml_parser);
		$this->add_test_settings($test_profile);
	}
	public function add_test_information(&$xml_reader)
	{
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestInformation/Title', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestInformation/AppVersion', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestInformation/Description', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestInformation/ResultScale', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestInformation/Proportion', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestInformation/ResultQuantifier', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestInformation/DisplayFormat', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestInformation/SubTitle', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestInformation/Executable', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestInformation/TimesToRun', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestInformation/IgnoreRuns', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestInformation/InstallationAgreement', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestInformation/PreInstallMessage', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestInformation/PostInstallMessage', $xml_reader);
	}
	public function add_test_data_section(&$xml_reader)
	{
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestProfile/Version', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestProfile/SupportedPlatforms', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestProfile/SoftwareType', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestProfile/TestType', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestProfile/License', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestProfile/Status', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestProfile/SupportedArchitectures', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestProfile/ExternalDependencies', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestProfile/Extends', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestProfile/RequiresRoot', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestProfile/EnvironmentSize', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestProfile/EnvironmentTestingSize', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestProfile/EstimatedTimePerRun', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestProfile/ProjectURL', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestProfile/RequiresCoreVersionMin', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestProfile/RequiresCoreVersionMax', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestProfile/InternalTags', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestProfile/Maintainer', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestProfile/AllowResultsSharing', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestProfile/AutoSaveResults', $xml_reader);
	}
	public function add_test_settings(&$test_profile)
	{
		$xml_reader = &$test_profile->xml_parser;
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestSettings/Default/Arguments', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestSettings/Default/PostArguments', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestSettings/Default/AllowCacheShare', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestSettings/Default/MinimumLength', $xml_reader);
		$this->xml_writer->addXmlNodeFromReaderWNE('PhoronixTestSuite/TestSettings/Default/MaximumLength', $xml_reader);

		foreach($test_profile->get_test_option_objects(false) as $option)
		{
			$this->xml_writer->addXmlNode('PhoronixTestSuite/TestSettings/Option/DisplayName', $option->get_name());
			$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/TestSettings/Option/Identifier', $option->get_identifier());
			$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/TestSettings/Option/ArgumentPrefix', $option->get_option_prefix());
			$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/TestSettings/Option/ArgumentPostfix', $option->get_option_postfix());
			$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/TestSettings/Option/DefaultEntry', $option->get_option_default_raw());

			foreach($option->get_options_array() as $item)
			{
				$this->xml_writer->addXmlNode('PhoronixTestSuite/TestSettings/Option/Menu/Entry/Name', $item[0]);
				$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/TestSettings/Option/Menu/Entry/Value', $item[1]);
				$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/TestSettings/Option/Menu/Entry/Message', $item[2]);
			}
		}

	}
}

?>
