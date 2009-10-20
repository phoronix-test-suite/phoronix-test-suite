<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel

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

class pts_test_result_details
{
	private $xml_parser;
	private $saved_results_file;
	private $saved_identifier;
	private $identifiers_r;

	public function __construct($saved_results_file, $identifier = null)
	{
		$this->xml_parser = new pts_results_tandem_XmlReader($saved_results_file);
		$this->saved_results_file = $saved_results_file;
		$this->saved_identifier = ($identifier == null ? pts_extract_identifier_from_path($saved_results_file) : $identifier);
		$raw_results = $this->xml_parser->getXMLArrayValues(P_RESULTS_RESULTS_GROUP);

		$results_xml = new tandem_XmlReader($raw_results[0]);
		$this->identifiers_r = $results_xml->getXMLArrayValues(S_RESULTS_RESULTS_GROUP_IDENTIFIER);
	}
	public function get_title()
	{
		return $this->xml_parser->getXMLValue(P_RESULTS_SUITE_TITLE);
	}
	public function get_saved_identifier()
	{
		return $this->saved_identifier;
	}
	public function get_suite()
	{
		return $this->xml_parser->getXMLValue(P_RESULTS_SUITE_NAME);
	}
	public function get_identifiers()
	{
		return $this->identifiers_r;
	}
	public function get_unique_tests()
	{
		return array_unique($this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_TITLE));
	}
	public function unique_tests_string()
	{
		$str = "Contained Tests: ";

		$i = 0;
		foreach($this->get_unique_tests() as $id)
		{
			if($i > 0)
			{
				$str .= ",";
			}

			$str .= " " . $id;
			$i++;
		}

		return $str;
	}
	public function identifiers_string()
	{
		$str = "Identifiers: ";

		$i = 0;
		foreach($this->get_identifiers() as $id)
		{
			if($i > 0)
			{
				$str .= ",";
			}

			$str .= " " . $id;
			$i++;
		}

		return $str;
	}
}

?>
