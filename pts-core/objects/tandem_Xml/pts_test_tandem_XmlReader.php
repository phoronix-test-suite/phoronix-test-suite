<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2004 - 2009, Michael Larabel
	pts_test_tandem_XmlReader.php: The XML reading object for the Phoronix Test Suite with optimizations for handling test profiles

	Additional Notes: A very simple XML parser with a few extras... Does not currently support attributes on tags, etc.
	A work in progress. This was originally designed for just some select needs in the past. No XML validation is done with this parser, etc.

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

class pts_test_tandem_XmlReader extends tandem_XmlReader
{
	protected $override_values = null;

	public function __construct($read_xml, $cache_support = true)
	{
		if(!is_file($read_xml) || substr($read_xml, -3) != "xml")
		{
			$read_xml = pts_location_test($read_xml);
		}

		parent::__construct($read_xml, $cache_support);
	}
	public function overrideXMLValues($test_options)
	{
		$this->override_values = $test_options;
	}
	function getXMLValue($xml_tag, $fallback_value = false)
	{
		if(!empty($this->override_values))
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
		}

		return parent::getXmlValue($xml_tag, $fallback_value);
	}
	function handleXmlZeroTagFallback($xml_tag)
	{
		// Cascading Test Profiles for finding a tag within an XML file being extended by another XML file
		$fallback_value = $this->tag_fallback_value;

		if(!empty($this->xml_file_name))
		{
			$test_extends = $this->getValue(P_TEST_CTPEXTENDS, null, null, true, true);

			if(!empty($test_extends) && pts_is_test($test_extends))
			{
				$test_below_parser = new pts_test_tandem_XmlReader($test_extends);
				$test_below_tag = $test_below_parser->getXMLValue($xml_tag);

				if(!empty($test_below_tag))
				{
					$fallback_value = $test_below_tag;
				}
			}
		}

		return $fallback_value;
	}
}
?>
