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
		$test_profile->block_test_extension_support();

		$this->add_test_information($test_profile);
		$this->add_test_data_section($test_profile);
		$this->add_test_settings($test_profile);
	}
	public function add_test_information(&$test_profile)
	{
		$this->xml_writer->addXmlNodeFromXGWNE('TestInformation/Title', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestInformation/AppVersion', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestInformation/Description', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestInformation/ResultScale', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestInformation/Proportion', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestInformation/ResultQuantifier', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestInformation/DisplayFormat', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestInformation/SubTitle', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestInformation/Executable', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestInformation/TimesToRun', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestInformation/IgnoreRuns', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestInformation/InstallationAgreement', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestInformation/PreInstallMessage', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestInformation/PostInstallMessage', $test_profile);
	}
	public function add_test_data_section(&$test_profile)
	{
		$this->xml_writer->addXmlNodeFromXGWNE('TestProfile/Version', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestProfile/SupportedPlatforms', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestProfile/SoftwareType', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestProfile/TestType', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestProfile/License', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestProfile/Status', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestProfile/SupportedArchitectures', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestProfile/ExternalDependencies', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestProfile/Extends', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestProfile/RequiresRoot', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestProfile/EnvironmentSize', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestProfile/EnvironmentTestingSize', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestProfile/EstimatedTimePerRun', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestProfile/ProjectURL', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestProfile/RequiresCoreVersionMin', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestProfile/RequiresCoreVersionMax', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestProfile/InternalTags', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestProfile/Maintainer', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestProfile/AllowResultsSharing', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestProfile/AutoSaveResults', $test_profile);
	}
	public function add_test_settings(&$test_profile)
	{
		$this->xml_writer->addXmlNodeFromXGWNE('TestSettings/Default/Arguments', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestSettings/Default/PostArguments', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestSettings/Default/AllowCacheShare', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestSettings/Default/MinimumLength', $test_profile);
		$this->xml_writer->addXmlNodeFromXGWNE('TestSettings/Default/MaximumLength', $test_profile);

		foreach($test_profile->get_test_option_objects(false) as $option)
		{
			$this->xml_writer->addXmlNode('TestSettings/Option/DisplayName', $option->get_name());
			$this->xml_writer->addXmlNodeWNE('TestSettings/Option/Identifier', $option->get_identifier());
			$this->xml_writer->addXmlNodeWNE('TestSettings/Option/ArgumentPrefix', $option->get_option_prefix());
			$this->xml_writer->addXmlNodeWNE('TestSettings/Option/ArgumentPostfix', $option->get_option_postfix());
			$this->xml_writer->addXmlNodeWNE('TestSettings/Option/DefaultEntry', $option->get_option_default_raw());

			foreach($option->get_options_array() as $item)
			{
				$this->xml_writer->addXmlNode('TestSettings/Option/Menu/Entry/Name', $item[0]);
				$this->xml_writer->addXmlNodeWNE('TestSettings/Option/Menu/Entry/Value', $item[1]);
				$this->xml_writer->addXmlNodeWNE('TestSettings/Option/Menu/Entry/Message', $item[2]);
			}
		}

	}
}

?>
