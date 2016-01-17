<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2016, Phoronix Media
	Copyright (C) 2008 - 2016, Michael Larabel

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
			$ob_identifier = pts_openbenchmarking::evaluate_string_to_qualifier($identifier, true, 'suite');

			if($ob_identifier != false)
			{
				$identifier = $ob_identifier;
			}
		}

		$this->identifier = $identifier;
		$this->xml_parser = new pts_suite_nye_XmlReader($identifier);
	}
	public function __toString()
	{
		return $this->get_identifier() . ' [v' . $this->get_version() . ']';
	}
	public function get_identifier($bind_version = true)
	{
		$identifier = $this->identifier;

		if($bind_version == false && ($c = strrpos($identifier, '-')))
		{
			if(pts_strings::is_version(substr($identifier, ($c + 1))))
			{
				$identifier = substr($identifier, 0, $c);
			}
		}

		return $identifier;
	}
	public function get_identifier_base_name()
	{
		$identifier = basename($this->identifier);

		if(($s = strrpos($identifier, '-')) !== false)
		{
			$post_dash = substr($identifier, ($s + 1));

			// If the version is attached, remove it
			if(pts_strings::is_version($post_dash))
			{
				$identifier = substr($identifier, 0, $s);
			}
		}

		return $identifier;
	}
	public function requires_core_version_min()
	{
		return $this->xml_parser->getXMLValue('PhoronixTestSuite/SuiteInformation/RequiresCoreVersionMin', 2950);
	}
	public function requires_core_version_max()
	{
		return $this->xml_parser->getXMLValue('PhoronixTestSuite/SuiteInformation/RequiresCoreVersionMax', 9190);
	}
	public function get_description()
	{
		return $this->xml_parser->getXMLValue('PhoronixTestSuite/SuiteInformation/Description');
	}
	public function get_title()
	{
		return $this->xml_parser->getXMLValue('PhoronixTestSuite/SuiteInformation/Title');
	}
	public function get_version()
	{
		return $this->xml_parser->getXMLValue('PhoronixTestSuite/SuiteInformation/Version');
	}
	public function get_maintainer()
	{
		return $this->xml_parser->getXMLValue('PhoronixTestSuite/SuiteInformation/Maintainer');
	}
	public function get_suite_type()
	{
		return $this->xml_parser->getXMLValue('PhoronixTestSuite/SuiteInformation/TestType');
	}
	public function get_pre_run_message()
	{
		return $this->xml_parser->getXMLValue('PhoronixTestSuite/SuiteInformation/PreRunMessage');
	}
	public function get_post_run_message()
	{
		return $this->xml_parser->getXMLValue('PhoronixTestSuite/SuiteInformation/PostRunMessage');
	}
	public function get_run_mode()
	{
		return $this->xml_parser->getXMLValue('PhoronixTestSuite/SuiteInformation/RunMode');
	}
	public function get_test_names()
	{
		return $this->xml_parser->getXMLArrayValues('PhoronixTestSuite/Execute/Test');
	}
	public function get_unique_test_names()
	{
		return array_unique($this->get_test_names());
	}
	public function get_contained_test_profiles()
	{
		$test_names = $this->xml_parser->getXMLArrayValues('PhoronixTestSuite/Execute/Test');
		$test_profiles = array();

		foreach(array_keys($test_names) as $i)
		{
			$obj = pts_types::identifier_to_object($test_names[$i]);

			if($obj instanceof pts_test_profile)
			{
				$test_profiles[] = $obj;
			}
			else if($obj instanceof pts_test_suite)
			{
				foreach($obj->get_contained_test_profiles() as $obj)
				{
					$test_profiles[] = $obj;
				}
			}
		}

		return $test_profiles;
	}
}

?>
