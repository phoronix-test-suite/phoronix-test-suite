<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel

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

class pts_test_suite_parser
{
	protected $identifier;
	public $xml_parser;

	public function __construct($identifier)
	{
		$this->identifier = $identifier;
		if(!is_file($identifier) && is_file(PTS_TEST_SUITE_PATH . $identifier . ".xml"))
		{
			$identifier = PTS_TEST_SUITE_PATH . $identifier . ".xml";
		}

		$this->xml_parser = new pts_suite_nye_XmlReader($identifier);
	}
	public function __toString()
	{
		return $this->get_identifier() . ' [v' . $this->get_version() . ']';
	}
	public function get_identifier()
	{
		return $this->identifier;
	}
	public function get_reference_systems()
	{
		return pts_strings::comma_explode($this->xml_parser->getXMLValue(P_SUITE_REFERENCE_SYSTEMS));
	}
	public function get_core_version_requirement()
	{
		return $this->xml_parser->getXMLValue(P_SUITE_REQUIRES_COREVERSION);
	}
	public function get_description()
	{
		return $this->xml_parser->getXMLValue(P_SUITE_DESCRIPTION);
	}
	public function get_title()
	{
		return $this->xml_parser->getXMLValue(P_SUITE_TITLE);
	}
	public function get_version()
	{
		return $this->xml_parser->getXMLValue(P_SUITE_VERSION);
	}
	public function get_maintainer()
	{
		return $this->xml_parser->getXMLValue(P_SUITE_MAINTAINER);
	}
	public function get_suite_type()
	{
		return $this->xml_parser->getXMLValue(P_SUITE_TYPE);
	}
	public function get_pre_run_message()
	{
		return $this->xml_parser->getXMLValue(P_SUITE_PRERUNMSG);
	}
	public function get_post_run_message()
	{
		return $this->xml_parser->getXMLValue(P_SUITE_POSTRUNMSG);
	}
	public function get_run_mode()
	{
		return $this->xml_parser->getXMLValue(P_SUITE_RUNMODE);
	}
	public function get_test_names()
	{
		return $this->xml_parser->getXMLArrayValues(P_SUITE_TEST_NAME);
	}
	public function get_contained_test_profiles()
	{
		$test_names = $this->xml_parser->getXMLArrayValues(P_SUITE_TEST_NAME);
		$test_versions = $this->xml_parser->getXMLArrayValues(P_SUITE_TEST_PROFILE_VERSION);
		$test_profiles = array();

		foreach(array_keys($test_names) as $i)
		{
			array_push($test_profiles, new pts_test_profile($test_names[$i]));
		}

		return $test_profiles;
	}
}

?>
