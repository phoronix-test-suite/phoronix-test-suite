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

class pts_test_nye_XmlReader extends nye_XmlReader
{
	protected $override_values;
	protected $block_test_extension_support = false;

	public function __construct($read_xml)
	{
		if(!isset($read_xml[512]) && !is_file($read_xml))
		{
			if(defined('PTS_TEST_PROFILE_PATH') && is_file(PTS_TEST_PROFILE_PATH . $read_xml . '/test-definition.xml'))
			{
				$read_xml = PTS_TEST_PROFILE_PATH . $read_xml . '/test-definition.xml';
			}
			else if(substr($read_xml, -4) == '.zip' && is_file($read_xml))
			{
				$zip = new ZipArchive();

				if($zip->open($read_xml) === true)
				{
					$read_xml = $zip->getFromName('test-definition.xml');
					$zip->close();
				}
			}
		}

		$this->override_values = array();
		parent::__construct($read_xml);
	}
	public function validate()
	{
		return $this->dom->schemaValidate(PTS_OPENBENCHMARKING_PATH . 'schemas/test-profile.xsd');
	}
	public function block_test_extension_support()
	{
		$this->block_test_extension_support = true;
	}
	public function overrideXMLValues($test_options)
	{
		foreach($test_options as $xml_tag => $value)
		{
			$this->overrideXMLValue($xml_tag, $value);
		}
	}
	public function overrideXMLValue($xml_tag, $value)
	{
		$this->override_values[$xml_tag] = $value;
	}
	public function getOverrideValues()
	{
		return $this->override_values;
	}
	public function getXMLValue($xml_tag, $fallback_value = false)
	{
		if(isset($this->override_values[$xml_tag]) && !empty($this->override_values[$xml_tag]))
		{
			return $this->override_values[$xml_tag];
		}
		else
		{
			$tag_name = basename($xml_tag);
			if(isset($this->override_values[$tag_name]) && !empty($this->override_values[$tag_name]))
			{
				return $this->override_values[$tag_name];
			}
		}

		return parent::getXmlValue($xml_tag, $fallback_value);
	}
	public function handleXmlZeroTagFallback($xml_tag, $fallback_value)
	{
		// Cascading Test Profiles for finding a tag within an XML file being extended by another XML file
		if($xml_tag == 'PhoronixTestSuite/TestProfile/Extends' || $this->block_test_extension_support)
		{
			// Otherwise we'd have an infinite loop
			return $fallback_value;
		}
		$test_extends = $this->getXmlValue('PhoronixTestSuite/TestProfile/Extends');

		if(!empty($test_extends) && PTS_IS_CLIENT)
		{
			$test_extends = pts_openbenchmarking::evaluate_string_to_qualifier($test_extends, true, 'test');
			$test_below_parser = new pts_test_nye_XmlReader($test_extends);
			$test_below_tag = $test_below_parser->getXMLValue($xml_tag);

			if(!empty($test_below_tag))
			{
				$fallback_value = $test_below_tag;
			}
		}

		return $fallback_value;
	}
}
?>
