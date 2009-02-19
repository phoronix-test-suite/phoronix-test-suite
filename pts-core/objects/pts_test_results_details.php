<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel

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

class pts_test_results_details
{
	var $saved_identifier;
	var $title;
	var $suite;
	var $identifiers_r;

	public function __construct($saved_results_file)
	{
		$this->saved_identifier = array_pop(explode("/", dirname($saved_results_file)));

		$xml_parser = new pts_results_tandem_XmlReader($saved_results_file);
		$this->title = $xml_parser->getXMLValue(P_RESULTS_SUITE_TITLE);
		$this->suite = $xml_parser->getXMLValue(P_RESULTS_SUITE_NAME);

		$raw_results = $xml_parser->getXMLArrayValues(P_RESULTS_RESULTS_GROUP);
		$results_xml = new tandem_XmlReader($raw_results[0]);
		$this->identifiers_r = $results_xml->getXMLArrayValues(S_RESULTS_RESULTS_GROUP_IDENTIFIER);
	}
	public function get_title()
	{
		return $this->title;
	}
	public function __toString()
	{
		$str = "";

		if(!empty($this->title))
		{
			$str .= $this->get_title() . "\n";
			$str .= sprintf("Saved Name: %-18ls Test: %-18ls \n", $this->saved_identifier, $this->suite);

			foreach($this->identifiers_r as $id)
			{
				$str .= "\t- " . $id . "\n";
			}
		}

		return $str;
	}
}

?>
