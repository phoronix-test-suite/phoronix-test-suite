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

class pts_result_file
{
	var $system_hardware;
	var $system_software;
	var $system_author;
	var $system_notes;
	var $system_date;
	var $system_pts_version;
	var $system_identifiers;

	var $suite_name;
	var $suite_title;
	var $suite_version;
	var $suite_description;
	var $suite_extensions;
	var $suite_properties;
	var $suite_type;

	var $result_objects = array();

	public function __construct($result_file)
	{
		// TODO: don't do all this work in the __construct() but move it out to the get functions
		$xml_reader = new pts_results_tandem_XmlReader($result_file);
		$this->system_hardware = $xml_reader->getXMLArrayValues(P_RESULTS_SYSTEM_HARDWARE);
		$this->system_software = $xml_reader->getXMLArrayValues(P_RESULTS_SYSTEM_SOFTWARE);
		$this->system_author = $xml_reader->getXMLArrayValues(P_RESULTS_SYSTEM_AUTHOR);
		$this->system_notes = $xml_reader->getXMLArrayValues(P_RESULTS_SYSTEM_NOTES);
		$this->system_date = $xml_reader->getXMLArrayValues(P_RESULTS_SYSTEM_DATE);
		$this->system_pts_version = $xml_reader->getXMLArrayValues(P_RESULTS_SYSTEM_PTSVERSION);
		$this->system_identifiers = $xml_reader->getXMLArrayValues(P_RESULTS_SYSTEM_IDENTIFIERS);
		$results_raw = $xml_reader->getXMLArrayValues(P_RESULTS_RESULTS_GROUP);

		$this->suite_name = $xml_reader->getXMLValue(P_RESULTS_SUITE_NAME);
		$this->suite_version = $xml_reader->getXMLValue(P_RESULTS_SUITE_VERSION);
		$this->suite_title = $xml_reader->getXMLValue(P_RESULTS_SUITE_TITLE);
		$this->suite_description = $xml_reader->getXMLValue(P_RESULTS_SUITE_DESCRIPTION);
		$this->suite_extensions = $xml_reader->getXMLValue(P_RESULTS_SUITE_EXTENSIONS);
		$this->suite_properties = $xml_reader->getXMLValue(P_RESULTS_SUITE_PROPERTIES);
		$this->suite_type = $xml_reader->getXMLValue(P_RESULTS_SUITE_TYPE);

		// Start on results work

		$results_name = $xml_reader->getXMLArrayValues(P_RESULTS_TEST_TITLE);
		$results_version = $xml_reader->getXMLArrayValues(P_RESULTS_TEST_VERSION);
		$results_attributes = $xml_reader->getXMLArrayValues(P_RESULTS_TEST_ATTRIBUTES);
		$results_scale = $xml_reader->getXMLArrayValues(P_RESULTS_TEST_SCALE);
		$results_test_name = $xml_reader->getXMLArrayValues(P_RESULTS_TEST_TESTNAME);
		$results_arguments = $xml_reader->getXMLArrayValues(P_RESULTS_TEST_ARGUMENTS);
		$results_proportion = $xml_reader->getXMLArrayValues(P_RESULTS_TEST_PROPORTION);
		$results_format = $xml_reader->getXMLArrayValues(P_RESULTS_TEST_RESULTFORMAT);

		$results_identifiers = array();
		$results_values = array();
		$results_raw_values = array();

		foreach($results_raw as $result_raw)
		{
			$xml_results = new tandem_XmlReader($result_raw);
			array_push($results_identifiers, $xml_results->getXMLArrayValues(S_RESULTS_RESULTS_GROUP_IDENTIFIER));
			array_push($results_values, $xml_results->getXMLArrayValues(S_RESULTS_RESULTS_GROUP_VALUE));
			array_push($results_raw_values, $xml_results->getXMLArrayValues(S_RESULTS_RESULTS_GROUP_RAW));
		}

		for($i = 0; $i < count($results_name); $i++)
		{
			$test_object = new pts_result_file_merge_test($results_name[$i], $results_version[$i], $results_attributes[$i], $results_scale[$i], $results_test_name[$i], $results_arguments[$i], $results_proportion[$i], $results_format[$i], $results_identifiers[$i], $results_values[$i], $results_raw_values[$i]);

			array_push($this->result_objects, $test_object);
		}
	}

	public function get_system_hardware()
	{
		return $this->system_hardware;
	}
	public function get_system_software()
	{
		return $this->system_software;
	}
	public function get_system_author()
	{
		return $this->system_author;
	}
	public function get_system_notes()
	{
		return $this->system_notes;
	}
	public function get_system_date()
	{
		return $this->system_date;
	}
	public function get_system_pts_version()
	{
		return $this->system_pts_version;
	}
	public function get_system_identifiers()
	{
		return $this->system_identifiers;
	}
	public function get_suite_name()
	{
		return $this->suite_name;
	}
	public function get_suite_title()
	{
		return $this->suite_title;
	}
	public function get_suite_version()
	{
		return $this->suite_version;
	}
	public function get_suite_description()
	{
		return $this->suite_description;
	}
	public function get_suite_extensions()
	{
		return $this->suite_extensions;
	}
	public function get_suite_properties()
	{
		return $this->suite_properties;
	}
	public function get_suite_type()
	{
		return $this->suite_type;
	}
	public function get_result_objects()
	{
		return $this->result_objects;
	}
}

?>
