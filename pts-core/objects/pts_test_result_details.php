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
	var $saved_results_file;
	var $saved_identifier;
	var $title;
	var $suite;
	var $unique_tests_r;
	var $identifiers_r;

	public function __construct($saved_results_file, $identifier = null)
	{
		$xml_parser = new pts_results_tandem_XmlReader($saved_results_file);
		$this->saved_results_file = $saved_results_file;
		$this->saved_identifier = ($identifier == null ? pts_extract_identifier_from_path($saved_results_file) : $identifier);
		$this->title = $xml_parser->getXMLValue(P_RESULTS_SUITE_TITLE);
		$this->suite = $xml_parser->getXMLValue(P_RESULTS_SUITE_NAME);
		$this->unique_tests_r = array_unique($xml_parser->getXMLArrayValues(P_RESULTS_TEST_TITLE));
		$raw_results = $xml_parser->getXMLArrayValues(P_RESULTS_RESULTS_GROUP);
		$results_xml = new tandem_XmlReader($raw_results[0]);
		$this->identifiers_r = $results_xml->getXMLArrayValues(S_RESULTS_RESULTS_GROUP_IDENTIFIER);
	}
	public function get_title()
	{
		return $this->title;
	}
	public function get_suite()
	{
		return $this->suite;
	}
	public function get_identifiers()
	{
		return $this->identifiers_r;
	}
	public function get_unique_tests()
	{
		return $this->unique_tests_r;
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
	public function show_basic_details()
	{
		$str = "";

		if(!empty($this->title))
		{
			$str .= $this->get_title() . "\n";
			$str .= sprintf("Saved Name: %-18ls Test: %-18ls \n", $this->saved_identifier, $this->suite);

			foreach($this->get_identifiers() as $id)
			{
				$str .= "\t- " . $id . "\n";
			}
		}

		return $str;
	}
	public function __toString()
	{
		$str = "\nTitle: " . $this->get_title() . "\nIdentifier: " . $this->saved_identifier . "\nTest: " . $this->get_suite() . "\n";
		$str .= "\nTest Result Identifiers:\n";
		foreach($this->get_identifiers() as $id)
		{
			$str .= "- " . $id . "\n";
		}

		if(count($this->get_unique_tests()) > 1)
		{
			$str .= "\nContained Tests:\n";
			foreach($this->get_unique_tests() as $test)
			{
				$str .= "- " . $test . "\n";
			}
		}

		return $str;
	}
}

?>
