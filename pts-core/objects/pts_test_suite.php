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
	private $not_supported = false;
	private $only_partially_supported = false;
	private $identifier_show_prefix = null;

	public function __construct($identifier)
	{
		$this->xml_parser = new pts_suite_tandem_XmlReader($identifier);
		$this->identifier = $identifier;

		$suite_support_code = pts_suite_supported($identifier);

		$this->identifier_show_prefix = " ";

		switch($suite_support_code)
		{
			case 0:
				$this->not_supported = true;
				break;
			case 1:
				$this->identifier_show_prefix = "*";
				$this->only_partially_supported = true;
				break;
		}
	}
	public function get_identifier_prefix()
	{
		return $this->identifier_show_prefix;
	}
	public function partially_supported()
	{
		return $this->only_partially_supported;
	}
	public function not_supported()
	{
		return $this->not_supported;
	}
	public function get_description()
	{
		return $this->xml_parser->getXMLValue(P_SUITE_DESCRIPTION);
	}
	public function get_name()
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
	public function pts_format_contained_tests_string()
	{
		$str = null;
		$this->pts_print_format_tests($this->identifier, $str);

		return $str;
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
}

?>
