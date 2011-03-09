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
		if(PTS_IS_CLIENT)
		{
			$identifier = pts_openbenchmarking::evaluate_string_to_qualifier($identifier);
		}

		$this->identifier = $identifier;
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
	public function requires_core_version_min()
	{
		return $this->xml_parser->getXMLValue(P_SUITE_REQUIRES_COREVERSION_MIN, 2950);
	}
	public function requires_core_version_max()
	{
		return $this->xml_parser->getXMLValue(P_SUITE_REQUIRES_COREVERSION_MAX, 9190);
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
	public function get_unique_test_names()
	{
		return array_unique($this->get_test_names());
	}
	public function get_contained_test_profiles()
	{
		$test_names = $this->xml_parser->getXMLArrayValues(P_SUITE_TEST_NAME);
		$test_profiles = array();

		foreach(array_keys($test_names) as $i)
		{
			$obj = pts_types::identifier_to_object($test_names[$i]);

			if($obj instanceof pts_test_profile)
			{
				array_push($test_profiles, $obj);
			}
			else if($obj instanceof pts_test_suite)
			{
				foreach($obj->get_contained_test_profiles() as $obj)
				{
					array_push($test_profiles, $obj);
				}
			}
		}

		return $test_profiles;
	}
}

?>
