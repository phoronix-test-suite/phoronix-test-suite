<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2015, Phoronix Media
	Copyright (C) 2008 - 2015, Michael Larabel

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
	private $xml;
	private $raw_xml;

	public function __construct($result_file)
	{
		$this->save_identifier = $result_file;
		if(!isset($result_file[1024]) && defined('PTS_SAVE_RESULTS_PATH') && is_file(PTS_SAVE_RESULTS_PATH . $result_file . '/composite.xml'))
		{
			$result_file = PTS_SAVE_RESULTS_PATH . $result_file . '/composite.xml';
		}
		$this->extra_attributes = array();
		if(is_file($result_file))
		{
			$this->raw_xml = file_get_contents($result_file);
		}
		else
		{
			$this->raw_xml = $result_file;
		}

		$this->xml = simplexml_load_string($this->raw_xml);
	}
	public function getRawXml()
	{
		return $this->raw_xml;
	}
	public function __toString()
	{
		return $this->get_identifier();
	}
	public function sanitize_user_strings($value)
	{
		if(is_array($value))
		{
			return array_map(array($this, 'sanitize_user_strings'), $value);
		}
		else
		{
			return strip_tags($value);
		}
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
		$hw = array();
		foreach($this->xml->System as $sys)
		{
				array_push($hw, $sys->Hardware->__toString());
		}

		return $this->sanitize_user_strings($hw);
	}
	public function get_system_software()
	{
		$sw = array();
		foreach($this->xml->System as $sys)
		{
				array_push($sw, $sys->Software->__toString());
		}

		return $this->sanitize_user_strings($sw);
	}
	public function get_system_json()
	{
		$js = array();
		foreach($this->xml->System as $sys)
		{
				array_push($js, $sys->JSON);
		}

		return $this->sanitize_user_strings(array_map(array('pts_arrays', 'json_decode'), $js));
	}
	public function get_system_user()
	{
		$users = array();
		foreach($this->xml->System as $sys)
		{
				array_push($users, $sys->User->__toString());
		}

		return $this->sanitize_user_strings($users);
	}
	public function get_system_notes()
	{
		$notes = array();
		foreach($this->xml->System as $sys)
		{
				array_push($notes, $sys->Notes->__toString());
		}

		return $this->sanitize_user_strings($notes);
	}
	public function get_system_date()
	{
		$times = array();
		foreach($this->xml->System as $sys)
		{
				array_push($times, $sys->TimeStamp->__toString());
		}

		return $this->sanitize_user_strings($times);
	}
	public function get_system_pts_version()
	{
		$versions = array();
		foreach($this->xml->System as $sys)
		{
				array_push($versions, $sys->TestClientVersion->__toString());
		}

		return $this->sanitize_user_strings($versions);
	}
	public function get_system_identifiers()
	{
		$identifiers = array();
		foreach($this->xml->System as $sys)
			array_push($identifiers, $sys->Identifier->__toString());

		return $this->sanitize_user_strings($identifiers);
	}
	public function get_system_count()
	{
		return count($this->get_system_identifiers());
	}
	public function get_title()
	{
		return $this->sanitize_user_strings($this->xml->Generated->Title);
	}
	public function get_description()
	{
		return $this->sanitize_user_strings($this->xml->Generated->Description);
	}
	public function get_notes()
	{
		return $this->sanitize_user_strings($this->xml->Generated->Notes);
	}
	public function get_internal_tags()
	{
		return $this->sanitize_user_strings($this->xml->Generated->InternalTags);
	}
	public function get_reference_id()
	{
		return $this->sanitize_user_strings($this->xml->Generated->ReferenceID);
	}
	public function get_preset_environment_variables()
	{
		return $this->xml->Generated->PreSetEnvironmentVariables;
	}
	public function get_test_titles()
	{
		$titles = array();
		foreach($this->xml->Result as $result)
		{
			array_push($titles, $result->Title->__toString());
		}

		return $titles;
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
	public function get_contained_tests_hash($raw_output = true)
	{
		$result_object_hashes = $this->get_result_object_hashes();
		sort($result_object_hashes);
		return sha1(implode(',', $result_object_hashes), $raw_output);
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
	public function is_multi_way_comparison($identifiers = false)
	{
		static $is_multi_way = -1;

		if($is_multi_way === -1)
		{
			$hw = null; // XXX: this isn't used anymore at least for now $this->get_system_hardware();
			if($identifiers == false)
			{
				$identifiers = $this->get_system_identifiers();
			}

			$is_multi_way = count($identifiers) < 2 ? false : pts_render::multi_way_identifier_check($identifiers, $hw, $this);
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
		$ids = array();
		foreach($this->xml->Result as $result)
		{
			array_push($ids, $result->Identifier->__toString());
		}

		return $ids;
	}
	public function get_result_objects($select_indexes = -1)
	{
		if($this->result_objects == null)
		{
			$this->result_objects = array();

			foreach($this->xml->Result as $result)
			{
				$test_profile = new pts_test_profile(($result->Identifier != null ? $result->Identifier->__toString() : null));
				$test_profile->set_test_title($result->Title->__toString());
				$test_profile->set_version($result->AppVersion->__toString());
				$test_profile->set_result_scale($result->Scale->__toString());
				$test_profile->set_result_proportion($result->Proportion->__toString());
				$test_profile->set_display_format($result->DisplayFormat->__toString());

				$test_result = new pts_test_result($test_profile);
				$test_result->set_used_arguments_description($result->Description->__toString());
				$test_result->set_used_arguments($result->Arguments->__toString());

				$result_buffer = new pts_test_result_buffer();
				foreach($result->Data->Entry as $entry)
				{
					$result_buffer->add_test_result($entry->Identifier->__toString(), $entry->Value->__toString(), $entry->RawString->__toString(), (isset($entry->JSON) ? $entry->JSON->__toString() : null));
				}

				$test_result->set_test_result_buffer($result_buffer);
				array_push($this->result_objects, $test_result);
			}
		}

		if($select_indexes != -1 && $select_indexes !== null)
		{
			$objects = array();

			if($select_indexes == 'ONLY_CHANGED_RESULTS')
			{
				foreach($this->result_objects as &$result)
				{
					// Only show results where the variation was greater than or equal to 1%
					if(abs($result->largest_result_variation(0.01)) >= 0.01)
					{
						array_push($objects, $result);
					}
				}
			}
			else
			{
				foreach(pts_arrays::to_array($select_indexes) as $index)
				{
					if(isset($this->result_objects[$index]))
					{
						array_push($objects, $this->result_objects[$index]);
					}
				}
			}

			return $objects;
		}

		return $this->result_objects;
	}
	public function to_json()
	{
		$file = $this->raw_xml;
		$file = str_replace(array("\n", "\r", "\t"), '', $file);
		$file = trim(str_replace('"', "'", $file));
		$simple_xml = simplexml_load_string($file);
		return json_encode($simple_xml);
	}
}

?>
