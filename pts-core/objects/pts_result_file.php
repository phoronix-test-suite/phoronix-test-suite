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
	var $result_objects = null;
	var $xml_reader = null;

	public function __construct($result_file)
	{
		$this->xml_reader = new pts_results_tandem_XmlReader($result_file);
	}
	public function get_system_hardware()
	{
		return $this->xml_reader->getXMLArrayValues(P_RESULTS_SYSTEM_HARDWARE);
	}
	public function get_system_software()
	{
		return $this->xml_reader->getXMLArrayValues(P_RESULTS_SYSTEM_SOFTWARE);
	}
	public function get_system_author()
	{
		return $this->xml_reader->getXMLArrayValues(P_RESULTS_SYSTEM_AUTHOR);
	}
	public function get_system_notes()
	{
		return $this->xml_reader->getXMLArrayValues(P_RESULTS_SYSTEM_NOTES);
	}
	public function get_system_date()
	{
		return $this->xml_reader->getXMLArrayValues(P_RESULTS_SYSTEM_DATE);
	}
	public function get_system_pts_version()
	{
		return $this->xml_reader->getXMLArrayValues(P_RESULTS_SYSTEM_PTSVERSION);
	}
	public function get_system_identifiers()
	{
		return $this->xml_reader->getXMLArrayValues(P_RESULTS_SYSTEM_IDENTIFIERS);
	}
	public function get_suite_name()
	{
		return $this->xml_reader->getXMLValue(P_RESULTS_SUITE_NAME);
	}
	public function get_suite_title()
	{
		return $this->xml_reader->getXMLValue(P_RESULTS_SUITE_TITLE);
	}
	public function get_suite_version()
	{
		return $this->xml_reader->getXMLValue(P_RESULTS_SUITE_VERSION);
	}
	public function get_suite_description()
	{
		return $this->xml_reader->getXMLValue(P_RESULTS_SUITE_DESCRIPTION);
	}
	public function get_suite_extensions()
	{
		return $this->xml_reader->getXMLValue(P_RESULTS_SUITE_EXTENSIONS);
	}
	public function get_suite_properties()
	{
		return $this->xml_reader->getXMLValue(P_RESULTS_SUITE_PROPERTIES);
	}
	public function get_suite_type()
	{
		return $this->xml_reader->getXMLValue(P_RESULTS_SUITE_TYPE);
	}
	public function get_result_objects()
	{
		if($this->result_objects == null)
		{
			$this->result_objects = array();

			$results_name = $this->xml_reader->getXMLArrayValues(P_RESULTS_TEST_TITLE);
			$results_version = $this->xml_reader->getXMLArrayValues(P_RESULTS_TEST_VERSION);
			$results_attributes = $this->xml_reader->getXMLArrayValues(P_RESULTS_TEST_ATTRIBUTES);
			$results_scale = $this->xml_reader->getXMLArrayValues(P_RESULTS_TEST_SCALE);
			$results_test_name = $this->xml_reader->getXMLArrayValues(P_RESULTS_TEST_TESTNAME);
			$results_arguments = $this->xml_reader->getXMLArrayValues(P_RESULTS_TEST_ARGUMENTS);
			$results_proportion = $this->xml_reader->getXMLArrayValues(P_RESULTS_TEST_PROPORTION);
			$results_format = $this->xml_reader->getXMLArrayValues(P_RESULTS_TEST_RESULTFORMAT);

			$results_raw = $this->xml_reader->getXMLArrayValues(P_RESULTS_RESULTS_GROUP);

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

		return $this->result_objects;
	}
}

?>
