<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2014, Phoronix Media
	Copyright (C) 2008 - 2014, Michael Larabel

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
	private $extra_attributes = null;
	private $is_multi_way_inverted = false;
	public $xml_parser = null;

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
		return is_file(PTS_SAVE_RESULTS_PATH . $identifier . '/composite.xml');
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
		return $this->xml_parser->getXMLArrayValues('PhoronixTestSuite/System/Hardware');
	}
	public function get_system_software()
	{
		return $this->xml_parser->getXMLArrayValues('PhoronixTestSuite/System/Software');
	}
	public function get_system_json()
	{
		return array_map(array('pts_arrays', 'json_decode'), $this->xml_parser->getXMLArrayValues('PhoronixTestSuite/System/JSON'));
	}
	public function get_system_user()
	{
		return $this->xml_parser->getXMLArrayValues('PhoronixTestSuite/System/User');
	}
	public function get_system_notes()
	{
		return $this->xml_parser->getXMLArrayValues('PhoronixTestSuite/System/Notes');
	}
	public function get_system_date()
	{
		return $this->xml_parser->getXMLArrayValues('PhoronixTestSuite/System/TimeStamp');
	}
	public function get_system_pts_version()
	{
		return $this->xml_parser->getXMLArrayValues('PhoronixTestSuite/System/TestClientVersion');
	}
	public function get_system_identifiers()
	{
		return $this->xml_parser->getXMLArrayValues('PhoronixTestSuite/System/Identifier');
	}
	public function get_system_count()
	{
		return count($this->get_system_identifiers());
	}
	public function get_title()
	{
		return $this->xml_parser->getXMLValue('PhoronixTestSuite/Generated/Title');
	}
	public function get_description()
	{
		return $this->xml_parser->getXMLValue('PhoronixTestSuite/Generated/Description');
	}
	public function get_notes()
	{
		return $this->xml_parser->getXMLValue('PhoronixTestSuite/Generated/Notes');
	}
	public function get_internal_tags()
	{
		return $this->xml_parser->getXMLValue('PhoronixTestSuite/Generated/InternalTags');
	}
	public function get_reference_id()
	{
		return $this->xml_parser->getXMLValue('PhoronixTestSuite/Generated/ReferenceID');
	}
	public function get_preset_environment_variables()
	{
		return $this->xml_parser->getXMLValue('PhoronixTestSuite/Generated/PreSetEnvironmentVariables');
	}
	public function get_test_titles()
	{
		return $this->xml_parser->getXMLArrayValues('PhoronixTestSuite/Result/Title');
	}
	public function get_unique_test_titles()
	{
		return array_unique($this->get_test_titles());
	}
	public function get_test_count()
	{
		return count($this->get_test_titles());
	}
	public function get_unique_test_count()
	{
		return count($this->get_unique_test_titles());
	}
	public function get_contained_tests_hash()
	{
		$result_object_hashes = $this->get_result_object_hashes();
		sort($result_object_hashes);
		return sha1(implode(',', $result_object_hashes), true);
	}
	public function get_result_object_hashes()
	{
		$object_hashes = array();

		foreach($this->get_result_objects() as $result_object)
		{
			array_push($object_hashes, $result_object->get_comparison_hash());
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

			if(isset($identifiers[4]))
			{
				// dirty SHA1 hash check
				$is_sha1_hash = strlen($identifiers[0]) == 40 && strpos($identifiers[0], ' ') === false;
				$has_sha1_shorthash = false;

				foreach($identifiers as $i => &$identifier)
				{
					$has_sha1_shorthash = ($i == 0 || $has_sha1_shorthash) && isset($identifier[7]) && pts_strings::string_only_contains(substr($identifier, -8), pts_strings::CHAR_NUMERIC | pts_strings::CHAR_LETTER) && strpos($identifier, ' ') === false;
					$identifier = pts_strings::remove_from_string($identifier, pts_strings::CHAR_NUMERIC | pts_strings::CHAR_DASH | pts_strings::CHAR_DECIMAL);
				}

				$is_tracker = count(array_unique($identifiers)) <= 1 || $is_sha1_hash || $has_sha1_shorthash;

				if($is_tracker)
				{
					$hw = $this->get_system_hardware();

					if(isset($hw[1]) && count($hw) == count(array_unique($hw)))
					{
						// it can't be a results tracker if the hardware is always different
						$is_tracker = false;
					}
				}
			}
			else
			{
				// Definitely not a tracker as not over 5 results
				$is_tracker = false;
			}
		}

		return $is_tracker;
	}
	public function is_multi_way_comparison()
	{
		static $is_multi_way = -1;

		if($is_multi_way === -1)
		{
			$hw = $this->get_system_hardware();
			$is_multi_way = count($hw) < 2 ? false : pts_render::multi_way_identifier_check($this->get_system_identifiers(), $hw, $this);
			$this->is_multi_way_inverted = $is_multi_way && $is_multi_way[1];
		}

		return $is_multi_way;
	}
	public function invert_multi_way_invert()
	{
		$this->is_multi_way_inverted = ($this->is_multi_way_inverted == false);
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
	public function override_result_objects($result_objects)
	{
		$this->result_objects = $result_objects;
	}
	public function get_result_identifiers()
	{
		return $this->xml_parser->getXMLArrayValues('PhoronixTestSuite/Result/Identifier');;
	}
	public function get_result_objects($select_indexes = -1)
	{
		if($this->result_objects == null)
		{
			$this->result_objects = array();

			$results_name = $this->xml_parser->getXMLArrayValues('PhoronixTestSuite/Result/Title');
			$results_version = $this->xml_parser->getXMLArrayValues('PhoronixTestSuite/Result/AppVersion');
			$results_attributes = $this->xml_parser->getXMLArrayValues('PhoronixTestSuite/Result/Description');
			$results_scale = $this->xml_parser->getXMLArrayValues('PhoronixTestSuite/Result/Scale');
			$results_test_name = $this->xml_parser->getXMLArrayValues('PhoronixTestSuite/Result/Identifier');
			$results_arguments = $this->xml_parser->getXMLArrayValues('PhoronixTestSuite/Result/Arguments');
			$results_proportion = $this->xml_parser->getXMLArrayValues('PhoronixTestSuite/Result/Proportion');
			$results_format = $this->xml_parser->getXMLArrayValues('PhoronixTestSuite/Result/DisplayFormat');

			$results_identifiers = $this->xml_parser->getXMLArrayValues('PhoronixTestSuite/Result/Data/Entry/Identifier', 0);
			$results_values = $this->xml_parser->getXMLArrayValues('PhoronixTestSuite/Result/Data/Entry/Value', 0);
			$results_raw = $this->xml_parser->getXMLArrayValues('PhoronixTestSuite/Result/Data/Entry/RawString', 0);
			$results_json = $this->xml_parser->getXMLArrayValues('PhoronixTestSuite/Result/Data/Entry/JSON', 0);

			for($i = 0; $i < count($results_name); $i++)
			{
				$test_profile = new pts_test_profile($results_test_name[$i]);
				$test_profile->set_test_title($results_name[$i]);
				$test_profile->set_version($results_version[$i]);
				$test_profile->set_result_scale($results_scale[$i]);
				$test_profile->set_result_proportion($results_proportion[$i]);
				$test_profile->set_display_format($results_format[$i]);

				$test_result = new pts_test_result($test_profile);
				$test_result->set_used_arguments_description($results_attributes[$i]);
				$test_result->set_used_arguments($results_arguments[$i]);

				$result_buffer = new pts_test_result_buffer();
				for($j = 0; $j < count($results_identifiers[$i]); $j++)
				{
					$result_buffer->add_test_result($results_identifiers[$i][$j], $results_values[$i][$j], $results_raw[$i][$j], (isset($results_json[$i][$j]) ? $results_json[$i][$j] : null));
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
	public function to_json()
	{
		$file = $this->xml_parser->getFileLocation();

		if(is_file($file))
		{
			$file = file_get_contents($file);
			$file = str_replace(array("\n", "\r", "\t"), '', $file);
			$file = trim(str_replace('"', "'", $file));
			$simple_xml = simplexml_load_string($file);
			return json_encode($simple_xml);
		}
	}
}

?>
