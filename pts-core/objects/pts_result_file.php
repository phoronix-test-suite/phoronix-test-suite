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

class pts_result_file
{
	private $save_identifier = null;
	private $result_objects = null;
	private $xml_parser = null;
	private $extra_attributes = null;
	private $is_multi_way_inverted = false;

	public function __construct($result_file)
	{
		$this->save_identifier = $result_file;
		$this->xml_parser = new pts_results_nye_XmlReader($result_file);
		$this->extra_attributes = array();
	}
	public function __toString()
	{
		return $this->get_identifier();
	}
	public static function is_test_result_file($identifier)
	{
		return is_file(PTS_SAVE_RESULTS_PATH . $identifier . "/composite.xml");
	}
	public function get_identifier()
	{
		return $this->save_identifier;
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
	public function get_system_user()
	{
		return $this->xml_parser->getXMLArrayValues(P_RESULTS_SYSTEM_USER);
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
	public function get_title()
	{
		return $this->xml_parser->getXMLValue(P_RESULTS_GENERATED_TITLE);
	}
	public function get_description()
	{
		return $this->xml_parser->getXMLValue(P_RESULTS_GENERATED_DESCRIPTION);
	}
	public function get_notes()
	{
		return $this->xml_parser->getXMLValue(P_RESULTS_GENERATED_NOTES);
	}
	public function get_internal_tags()
	{
		return $this->xml_parser->getXMLValue(P_RESULTS_GENERATED_INTERNAL_TAGS);
	}
	public function get_preset_environment_variables()
	{
		return $this->xml_parser->getXMLValue(P_RESULTS_GENERATED_PRESET_ENV_VARS);
	}
	public function get_test_titles()
	{
		return $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_TITLE);
	}
	public function get_unique_test_titles()
	{
		return array_unique($this->get_test_titles());
	}
	public function get_test_count()
	{
		return count($this->get_test_titles());
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
			$results_arguments = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_ARGS);
			$results_attributes = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_DESCRIPTION);
			$results_version = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_VERSION);

			for($i = 0; $i < count($results_name); $i++)
			{
				array_push($object_hashes, pts_test_profile::generate_comparison_hash($results_name[$i], $results_arguments[$i], $results_attributes[$i], $results_version[$i]));
			}
		}

		return $object_hashes;
	}
	public function is_results_tracker()
	{
		// If there are more than five results and the only changes in the system identifier names are numeric changes, assume it's a tracker
		// i.e. different dates or different versions of a package being tested

		static $is_tracker = -1;

		if($is_tracker === -1)
		{
			$identifiers = $this->get_system_identifiers();

			foreach($identifiers as &$identifier)
			{
				$identifier = pts_strings::remove_from_string($identifier, pts_strings::CHAR_NUMERIC | pts_strings::CHAR_DASH | pts_strings::CHAR_DECIMAL);
			}

			$is_tracker = count($identifiers) > 5 && count(array_unique($identifiers)) <= 1;
		}

		return $is_tracker;
	}
	public function is_multi_way_comparison()
	{
		static $is_multi_way = -1;

		if($is_multi_way === -1)
		{
			$hw = $this->get_system_hardware();
			$is_multi_way = pts_render::multi_way_identifier_check($this->get_system_identifiers(), $hw);
			$this->is_multi_way_inverted = $is_multi_way && $is_multi_way[1] ;
		}

		return $is_multi_way;
	}
	public function is_multi_way_inverted()
	{
		return $this->is_multi_way_inverted;
	}
	public function get_contained_test_profiles()
	{
		$test_profiles = array();

		foreach($this->get_result_objects() as $object)
		{
			array_push($test_profiles, $object->test_profile);
		}

		return $test_profiles;
	}
	public function get_result_objects($select_indexes = -1)
	{
		if($this->result_objects == null)
		{
			$this->result_objects = array();

			$results_name = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_TITLE);
			$results_version = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_VERSION);
			$results_profile_version = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_PROFILE_VERSION);
			$results_attributes = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_DESCRIPTION);
			$results_scale = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_SCALE);
			$results_test_name = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_IDENTIFIER);
			$results_arguments = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_ARGS);
			$results_proportion = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_PROPORTION);
			$results_format = $this->xml_parser->getXMLArrayValues(P_RESULTS_TEST_DISPLAY_FORMAT);

			$results_identifiers = $this->xml_parser->getXMLArrayValues(P_RESULTS_RESULTS_GROUP_IDENTIFIER, 0);
			$results_values = $this->xml_parser->getXMLArrayValues(P_RESULTS_RESULTS_GROUP_VALUE, 0);
			$results_raw = $this->xml_parser->getXMLArrayValues(P_RESULTS_RESULTS_GROUP_RAW, 0);

			for($i = 0; $i < count($results_name); $i++)
			{
				$test_profile = new pts_test_profile($results_test_name[$i]);
				$test_profile->set_test_title($results_name[$i]);
				$test_profile->set_version($results_version[$i]);
				$test_profile->set_test_profile_version($results_profile_version[$i]);
				$test_profile->set_result_scale($results_scale[$i]);
				$test_profile->set_result_proportion($results_proportion[$i]);
				$test_profile->set_display_format($results_format[$i]);

				$test_result = new pts_test_result($test_profile);
				$test_result->set_used_arguments_description($results_attributes[$i]);
				$test_result->set_used_arguments($results_arguments[$i]);

				$result_buffer = new pts_test_result_buffer();
				for($j = 0; $j < count($results_identifiers[$i]); $j++)
				{
					$result_buffer->add_test_result($results_identifiers[$i][$j], $results_values[$i][$j], $results_raw[$i][$j]);
				}

				$test_result->set_test_result_buffer($result_buffer);

				array_push($this->result_objects, $test_result);
			}
		}

		if($select_indexes != -1)
		{
			$objects = array();

			foreach(pts_arrays::to_array($select_indexes) as $index)
			{
				if(isset($this->result_objects[$index]))
				{
					array_push($objects, $this->result_objects[$index]);
				}
			}

			return $objects;
		}

		return $this->result_objects;
	}
}

?>
