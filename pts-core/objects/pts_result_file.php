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
	private $result_objects = null;
	private $xml_parser = null;
	private $extra_attributes = null;

	public function __construct($result_file)
	{
		$this->xml_parser = new pts_results_tandem_XmlReader($result_file);
		$this->extra_attributes = array();
	}
	public function read_extra_attribute($key)
	{
		return isset($this->extra_attributes[$key]) ? $this->extra_attributes[$key] : false;
	}
	public function set_extra_attribute($key, $value)
	{
		$this->extra_attributes[$key] = $value;
	}
	public function get_system_hardware()
	{
		return $this->xml_parser->getXMLArrayValues(P_RESULTS_SYSTEM_HARDWARE);
	}
	public function get_system_software()
	{
		return $this->xml_parser->getXMLArrayValues(P_RESULTS_SYSTEM_SOFTWARE);
	}
	public function get_system_author()
	{
		return $this->xml_parser->getXMLArrayValues(P_RESULTS_SYSTEM_AUTHOR);
	}
	public function get_system_notes()
	{
		return $this->xml_parser->getXMLArrayValues(P_RESULTS_SYSTEM_NOTES);
	}
	public function get_system_date()
	{
		return $this->xml_parser->getXMLArrayValues(P_RESULTS_SYSTEM_DATE);
	}
	public function get_system_pts_version()
	{
		return $this->xml_parser->getXMLArrayValues(P_RESULTS_SYSTEM_PTSVERSION);
	}
	public function get_system_identifiers()
	{
		return $this->xml_parser->getXMLArrayValues(P_RESULTS_SYSTEM_IDENTIFIERS);
	}
	public function get_suite_name()
	{
		return $this->xml_parser->getXMLValue(P_RESULTS_SUITE_NAME);
	}
	public function get_title()
	{
		return $this->xml_parser->getXMLValue(P_RESULTS_SUITE_TITLE);
	}
	public function get_suite_version()
	{
		return $this->xml_parser->getXMLValue(P_RESULTS_SUITE_VERSION);
	}
	public function get_suite_description()
	{
		return $this->xml_parser->getXMLValue(P_RESULTS_SUITE_DESCRIPTION);
	}
	public function get_suite_extensions()
	{
		return $this->xml_parser->getXMLValue(P_RESULTS_SUITE_EXTENSIONS);
	}
	public function get_suite_properties()
	{
		return $this->xml_parser->getXMLValue(P_RESULTS_SUITE_PROPERTIES);
	}
	public function get_suite_type()
	{
		return $this->xml_parser->getXMLValue(P_RESULTS_SUITE_TYPE);
	}
	public function get_unique_test_titles()
	{
		return array_unique($this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_TITLE));
	}
	public function get_result_object_hashes()
	{
		$object_hashes = array();

		if($this->result_objects != null)
		{
			foreach($this->result_objects as $result_object)
			{
				array_push($object_hashes, $result_object->get_comparison_hash());
			}
		}
		else
		{
			$results_name = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_TITLE);
			$results_arguments = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_ARGUMENTS);
			$results_attributes = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_ATTRIBUTES);
			$results_version = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_VERSION);

			for($i = 0; $i < count($results_name); $i++)
			{
				array_push($object_hashes, pts_test_comparison_hash($results_name[$i], $results_arguments[$i], $results_attributes[$i], $results_version[$i]));
			}
		}

		return $object_hashes;
	}
	public function get_result_objects()
	{
		if($this->result_objects == null)
		{
			$this->result_objects = array();

			$results_name = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_TITLE);
			$results_version = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_VERSION);
			$results_profile_version = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_PROFILE_VERSION);
			$results_attributes = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_ATTRIBUTES);
			$results_scale = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_SCALE);
			$results_test_name = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_TESTNAME);
			$results_arguments = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_ARGUMENTS);
			$results_proportion = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_PROPORTION);
			$results_format = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_RESULTFORMAT);
			$results_raw = $this->xml_parser->getXMLArrayValues(P_RESULTS_RESULTS_GROUP);

			$result_buffers = array();

			$key_identifier = substr(P_RESULTS_RESULTS_GROUP_IDENTIFIER, strlen(P_RESULTS_RESULTS_GROUP) + 1);
			$key_value = substr(P_RESULTS_RESULTS_GROUP_VALUE, strlen(P_RESULTS_RESULTS_GROUP) + 1);
			$key_raw = substr(P_RESULTS_RESULTS_GROUP_RAW, strlen(P_RESULTS_RESULTS_GROUP) + 1);

			foreach(array_keys($results_raw) as $results_raw_key)
			{
				$result_buffer = new pts_test_result_buffer();
				$xml_results = new tandem_XmlReader($results_raw[$results_raw_key]);

				$identifiers = $xml_results->getXMLArrayValues($key_identifier);
				$values = $xml_results->getXMLArrayValues($key_value);
				$raw_values = $xml_results->getXMLArrayValues($key_raw);

				for($i = 0; $i < count($identifiers) && $i < count($values); $i++)
				{
					$result_buffer->add_test_result($identifiers[$i], $values[$i], $raw_values[$i]);
				}

				array_push($result_buffers, $result_buffer);
			}

			for($i = 0; $i < count($results_name); $i++)
			{
				$test_object = new pts_result_file_result_object($results_name[$i], $results_version[$i], $results_profile_version[$i], $results_attributes[$i], $results_scale[$i], $results_test_name[$i], $results_arguments[$i], $results_proportion[$i], $results_format[$i], $result_buffers[$i]);

				array_push($this->result_objects, $test_object);
			}
		}

		return $this->result_objects;
	}
}

?>
