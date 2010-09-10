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

class pts_test_suite
{
	private $identifier;
	private $xml_parser;

	public function __construct($identifier)
	{
		$this->xml_parser = new pts_suite_tandem_XmlReader($identifier);
		$this->identifier = $identifier;
	}
	public function is_supported()
	{
		$tests = pts_contained_tests($this->identifier, false, false, true);
		$supported_size = $original_size = count($tests);

		foreach($tests as &$test)
		{
			$test_profile = new pts_test_profile($test);

			if($test_profile->is_supported() == false)
			{
				$supported_size--;
			}
		}

		if($supported_size == 0)
		{
			$return_code = 0;
		}
		else if($supported_size != $original_size)
		{
			$return_code = 1;
		}
		else
		{
			$return_code = 2;
		}

		return $return_code;
	}
	public function get_reference_systems()
	{
		return pts_strings::comma_explode($this->xml_parser->getXMLValue(P_SUITE_REFERENCE_SYSTEMS));
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
	public function get_unique_test_count()
	{
		return count(pts_contained_tests($this->identifier));
	}
	public function get_maintainer()
	{
		return $this->xml_parser->getXMLValue(P_SUITE_MAINTAINER);
	}
	public function get_suite_type()
	{
		return $this->xml_parser->getXMLValue(P_SUITE_TYPE);
	}
	public function get_test_names()
	{
		return $this->xml_parser->getXMLArrayValues(P_SUITE_TEST_NAME);
	}
	public function pts_format_contained_tests_string()
	{
		$str = null;
		$this->pts_print_format_tests($this->identifier, $str);

		return $str;
	}
	public function is_core_version_supported()
	{
		// Check if the test suite's version is compatible with pts-core
		$supported = true;

		$requires_core_version = $this->xml_parser->getXMLValue(P_SUITE_REQUIRES_COREVERSION);

		if(!empty($requires_core_version))
		{
			$core_check = pts_strings::trim_explode('-', $requires_core_version);	
			$support_begins = $core_check[0];
			$support_ends = isset($core_check[1]) ? $core_check[1] : PTS_CORE_VERSION;
			$supported = PTS_CORE_VERSION >= $support_begins && PTS_CORE_VERSION <= $support_ends;
		}

		return $supported;
	}
	public function pts_print_format_tests($object, &$write_buffer, $steps = -1)
	{
		// Print out a text tree that shows the suites and tests within an object
		$steps++;
		if(pts_is_suite($object))
		{
			$xml_parser = new pts_suite_tandem_XmlReader($object);
			$tests_in_suite = array_unique($xml_parser->getXMLArrayValues(P_SUITE_TEST_NAME));

			if($steps > 0)
			{
				asort($tests_in_suite);
			}

			if($steps == 0)
			{
				$write_buffer .= $object . "\n";
			}
			else
			{
				$write_buffer .= str_repeat("  ", $steps) . "+ " . $object . "\n";
			}

			foreach($tests_in_suite as $test)
			{
				$write_buffer .= $this->pts_print_format_tests($test, $write_buffer, $steps);
			}
		}
		else
		{
			$write_buffer .= str_repeat("  ", $steps) . "* " . $object . "\n";
		}
	}
	public static function pts_format_tests_to_array($object)
	{
		// Print out a text tree that shows the suites and tests within an object
		$contained = array();

		if(pts_is_suite($object))
		{
			$xml_parser = new pts_suite_tandem_XmlReader($object);
			$tests_in_suite = array_unique($xml_parser->getXMLArrayValues(P_SUITE_TEST_NAME));
			$contained[$object] = array();

			foreach($tests_in_suite as $test)
			{
				array_push($contained[$object], self::pts_format_tests_to_array($test));
			}
		}
		else
		{
			$contained = $object;
		}

		return $contained;
	}
}

?>
