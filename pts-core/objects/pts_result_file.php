<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2022, Phoronix Media
	Copyright (C) 2008 - 2022, Michael Larabel

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
	protected $save_identifier = null;
	protected $result_objects = null;
	protected $is_multi_way_inverted = false;
	protected $file_location = false;

	private $title = null;
	private $description = null;
	private $notes = null;
	private $internal_tags = null;
	private $reference_id = null;
	private $preset_environment_variables = null;
	public $systems = null;
	private $is_tracker = -1;
	private $last_modified = null;
	private $ro_relation_map = null;

	public function __construct($result_file = null, $read_only_result_objects = false, $parse_only_qualified_result_objects = false)
	{
		$this->save_identifier = $result_file;
		$this->systems = array();
		$this->result_objects = array();
		$this->ro_relation_map = array();

		if($result_file == null)
		{
			return;
		}
		else if(is_file($result_file))
		{
			$this->file_location = $result_file;
			$result_file = file_get_contents($result_file);
		}
		else if(!isset($result_file[1024]) && defined('PTS_SAVE_RESULTS_PATH') && is_file(PTS_SAVE_RESULTS_PATH . $result_file . '/composite.xml'))
		{
			$this->file_location = PTS_SAVE_RESULTS_PATH . $result_file . '/composite.xml';
			$result_file = file_get_contents($this->file_location);
		}

		$xml = simplexml_load_string($result_file, 'SimpleXMLElement', LIBXML_COMPACT | LIBXML_PARSEHUGE);
		if(isset($xml->Generated))
		{
			$this->title = self::clean_input($xml->Generated->Title);
			$this->description = self::clean_input($xml->Generated->Description);
			$this->notes = self::clean_input($xml->Generated->Notes);
			$this->internal_tags = self::clean_input($xml->Generated->InternalTags);
			$this->reference_id = self::clean_input($xml->Generated->ReferenceID);
			$this->preset_environment_variables = self::clean_input($xml->Generated->PreSetEnvironmentVariables);
			$this->last_modified = self::clean_input($xml->Generated->LastModified);
		}

		if(isset($xml->System))
		{
			foreach($xml->System as $s)
			{
				$this->systems[] = new pts_result_file_system(self::clean_input($s->Identifier->__toString()), self::clean_input($s->Hardware->__toString()), self::clean_input($s->Software->__toString()), json_decode(self::clean_input($s->JSON), true), self::clean_input($s->User->__toString()), self::clean_input($s->Notes->__toString()), self::clean_input($s->TimeStamp->__toString()), self::clean_input($s->TestClientVersion->__toString()), $this);
			}
		}

		if(isset($xml->Result))
		{
			foreach($xml->Result as $result)
			{
				if($parse_only_qualified_result_objects && ($result->Identifier == null || $result->Identifier->__toString() == null))
				{
					continue;
				}

				$test_profile = new pts_test_profile(($result->Identifier != null ? $result->Identifier->__toString() : null), null, !$read_only_result_objects);
				$test_profile->set_test_title($result->Title->__toString());
				$test_profile->set_version($result->AppVersion->__toString());
				$test_profile->set_result_scale($result->Scale->__toString());
				$test_profile->set_result_proportion($result->Proportion->__toString());
				$test_profile->set_display_format($result->DisplayFormat->__toString());

				$test_result = new pts_test_result($test_profile);
				$test_result->set_used_arguments_description($result->Description->__toString());
				$test_result->set_used_arguments($result->Arguments->__toString());
				$test_result->set_annotation((isset($result->Annotation) ? $result->Annotation->__toString() : null));
				$parent = (isset($result->Parent) ? $result->Parent->__toString() : null);
				$test_result->set_parent_hash($parent);

				$result_buffer = new pts_test_result_buffer();
				foreach($result->Data->Entry as $entry)
				{
					$result_buffer->add_test_result($entry->Identifier->__toString(), $entry->Value->__toString(), $entry->RawString->__toString(), (isset($entry->JSON) ? $entry->JSON->__toString() : null));
				}
				$test_result->set_test_result_buffer($result_buffer);
				$this_ch = $test_result->get_comparison_hash(true, false);
				$this->result_objects[$this_ch] = $test_result;

				if($parent)
				{
					if(!isset($this->ro_relation_map[$parent]))
					{
						$this->ro_relation_map[$parent] = array();
					}
					$this->ro_relation_map[$parent][] = $this_ch;
				}
			}
		}

		unset($xml);
	}
	public function __clone()
	{
		foreach($this->result_objects as $i => $v)
		{
			$this->result_objects[$i] = clone $this->result_objects[$i];
		}
	}
	public function get_relation_map($parent = null, $relations_recurse = true)
	{
		if($parent)
		{
			if(!$relations_recurse)
			{
				return isset($this->ro_relation_map[$parent]) ? $this->ro_relation_map[$parent] : array();
			}
			else
			{
				$children = array();
				if(isset($this->ro_relation_map[$parent]))
				{
					$children = $this->ro_relation_map[$parent];
					foreach($children as $child)
					{
						$sub_child = $this->get_relation_map($child, true);
						if(!empty($sub_child))
						{
							foreach($sub_child as $sc)
							{
								$children[] = $sc;
							}
						}
					}
				}

				return $children;
			}
		}
		else
		{
			return $this->ro_relation_map;
		}
	}
	public function get_file_location()
	{
		if($this->file_location)
		{
			return $this->file_location;
		}
		else if($this->save_identifier)
		{
			return PTS_SAVE_RESULTS_PATH . $this->save_identifier . '/composite.xml';
		}

		return '';
	}
	public function get_result_dir()
	{
		$composite_xml_dir = $this->get_file_location() != null ? dirname($this->get_file_location()) : '';
		return empty($composite_xml_dir) || !is_dir($composite_xml_dir) ? false : $composite_xml_dir . '/';
	}
	public function get_system_log_dir($result_identifier = null, $dir_check = true)
	{
		$log_dir = $this->get_result_dir();
		if($log_dir == false)
		{
			return false;
		}

		$sdir = $log_dir . '/system-logs/';

		if($result_identifier == null)
		{
			return $sdir;
		}
		else
		{
			$sdir = $sdir . pts_strings::simplify_string_for_file_handling($result_identifier) . '/';
			return !$dir_check || is_dir($sdir) ? $sdir : false;
		}
	}
	public function get_test_log_dir(&$result_object = null)
	{
		$log_dir = $this->get_result_dir();
		if($log_dir == false)
		{
			return false;
		}

		return $log_dir . '/test-logs/' . ($result_object != null ? $result_object->get_comparison_hash(true, false) . '/' : null);
	}
	public function get_test_installation_log_dir()
	{
		$log_dir = $this->get_result_dir();
		if($log_dir == false)
		{
			return false;
		}

		return $log_dir . '/installation-logs/';
	}
	public function save()
	{
		if($this->get_file_location() && is_file($this->get_file_location()))
		{
			return file_put_contents($this->get_file_location(), $this->get_xml());
		}
	}
	public function get_last_modified()
	{
		return $this->last_modified;
	}
	public function validate()
	{
		$dom = new DOMDocument();
		$dom->loadXML($this->get_xml());
		return $dom->schemaValidate(pts_openbenchmarking::openbenchmarking_standards_path() . 'schemas/result-file.xsd');
	}
	public function __toString()
	{
		return $this->get_identifier();
	}
	protected static function clean_input($value)
	{
		return strip_tags($value);
		/*
		if(is_array($value))
		{
			return array_map(array($this, 'clean_input'), $value);
		}
		else
		{
			return strip_tags($value);
		}
		*/
	}
	public function get_identifier()
	{
		return $this->save_identifier;
	}
	public function add_system($system)
	{
		if(!in_array($system, $this->systems))
		{
			$this->systems[] = $system;
		}
	}
	public function get_systems()
	{
		return $this->systems;
	}
	public function get_system_hardware()
	{
		// XXX this is deprecated
		$hw = array();
		foreach($this->systems as &$s)
		{
			$hw[] = $s->get_hardware();
		}
		return $hw;
	}
	public function get_system_software()
	{
		// XXX this is deprecated
		$sw = array();
		foreach($this->systems as &$s)
		{
			$sw[] = $s->get_software();
		}
		return $sw;
	}
	public function get_system_identifiers()
	{
		// XXX this is deprecated
		$ids = array();
		foreach($this->systems as &$s)
		{
			$ids[] = $s->get_identifier();
		}
		return $ids;
	}
	public function get_system_identifiers_by_date()
	{
		$by_date = array();
		foreach($this->get_systems() as $s)
		{
			$by_date[$s->get_identifier()] = strtotime($s->get_timestamp());
		}
		asort($by_date);
		return array_keys($by_date);
	}
	public function is_system_identifier_in_result_file($identifier)
	{
		foreach($this->systems as &$s)
		{
			if($s->get_identifier() == $identifier)
			{
				return true;
			}
		}

		return false;
	}
	public function system_logs_available()
	{
		$has_system_logs = false;
		$system_log_dir_or_zip = is_dir($this->get_system_log_dir(null, true)) || is_file($this->get_result_dir() . 'system-logs.zip');

		if($system_log_dir_or_zip)
		{
			if($this->get_system_count() == 1)
			{
				// If just one system in result file and there is a log, safe to assume it's for the associated run...
				$has_system_logs = true;
			}
			else
			{
				foreach($this->systems as &$s)
				{
					if($s->has_log_files())
					{
						$has_system_logs = true;
						break;
					}
				}
			}
		}

		return $has_system_logs;
	}
	public function identifiers_with_system_logs()
	{
		$identifiers = array();
		$system_log_dir = $this->get_system_log_dir(null, true);
		if($system_log_dir && is_dir($system_log_dir))
		{
			foreach(pts_file_io::glob($system_log_dir . '/*') as $identifier_dir)
			{
				$identifiers[] = basename($identifier_dir);
			}
		}
		else if($this->get_result_dir() && is_file($this->get_result_dir() . 'system-logs.zip'))
		{
			$zip = new ZipArchive();
			$res = $zip->open($this->get_result_dir() . 'system-logs.zip');

			if($res === true)
			{
				for($i = 0; $i < $zip->numFiles; $i++)
				{
					$index = explode('/', $zip->getNameIndex($i));
					if(!empty($index[1]) && !in_array($index[1], $identifiers))
					{
						$identifiers[] = $index[1];
					}
				}
				$zip->close();
			}
		}
		return $identifiers;
	}
	public function get_system_count()
	{
		return count($this->systems);
	}
	public function set_title($new_title)
	{
		if($new_title != null)
		{
			$this->title = $new_title;
		}
	}
	public function get_title()
	{
		return $this->title;
	}
	public function append_description($append_description)
	{
		if($append_description != null && strpos($this->description, $append_description) === false)
		{
			$this->description .= PHP_EOL . $append_description;
		}
	}
	public function set_description($new_description)
	{
		if($new_description != null)
		{
			$this->description = $new_description;
		}
	}
	public function get_description()
	{
		return $this->description;
	}
	public function set_notes($notes)
	{
		if($notes != null)
		{
			$this->notes = $notes;
		}
	}
	public function get_notes()
	{
		return $this->notes;
	}
	public function set_internal_tags($tags)
	{
		if($tags != null)
		{
			$this->internal_tags = $tags;
		}
	}
	public function get_internal_tags()
	{
		return $this->internal_tags;
	}
	public function set_reference_id($new_reference_id)
	{
		if($new_reference_id != null)
		{
			$this->reference_id = $new_reference_id;
		}
	}
	public function get_reference_id()
	{
		return $this->reference_id;
	}
	public function set_preset_environment_variables($env)
	{
		if($env != null)
		{
			$this->preset_environment_variables = $env;
		}
	}
	public function get_preset_environment_variables()
	{
		return $this->preset_environment_variables;
	}
	public function get_test_count()
	{
		return count($this->result_objects);
	}
	public function get_qualified_test_count()
	{
		$q_count = 0;
		foreach($this->result_objects as &$ro)
		{
			if($ro->test_profile->get_identifier() != null)
			{
				$q_count++;
			}
		}
		return $q_count;
	}
	public function has_matching_test_and_run_identifier(&$test_result, $run_identifier_to_check)
	{
		$result_object = $this->get_result($test_result->get_comparison_hash(true, false));
		if($result_object && in_array($run_identifier_to_check, $result_object->test_result_buffer->get_identifiers()) && $result_object->test_result_buffer->get_result_from_identifier($run_identifier_to_check) != '')
		{
			return true;
		}

		return false;
	}
	public function has_missing_or_incomplete_data($run_identifier_to_check)
	{
		foreach($this->result_objects as &$result_object)
		{
			if(!in_array($run_identifier_to_check, $result_object->test_result_buffer->get_identifiers()) || $result_object->test_result_buffer->get_result_from_identifier($run_identifier_to_check) == '')
			{
				return true;
			}
		}
		return false;
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

		foreach($this->result_objects as &$result_object)
		{
			$object_hashes[] = $result_object->get_comparison_hash();
		}

		return $object_hashes;
	}
	public function is_results_tracker()
	{
		// If there are more than five results and the only changes in the system identifier names are numeric changes, assume it's a tracker
		// i.e. different dates or different versions of a package being tested
		if($this->is_tracker === -1)
		{
			$identifiers = $this->get_system_identifiers();

			if(isset($identifiers[5]))
			{
				// dirty SHA1 hash check
				$is_sha1_hash = strlen($identifiers[0]) == 40 && strpos($identifiers[0], ' ') === false;
				$has_sha1_shorthash = false;

				foreach($identifiers as $i => &$identifier)
				{
					$has_sha1_shorthash = ($i == 0 || $has_sha1_shorthash) && isset($identifier[7]) && pts_strings::string_only_contains(substr($identifier, -8), pts_strings::CHAR_NUMERIC | pts_strings::CHAR_LETTER) && strpos($identifier, ' ') === false;
					$identifier = pts_strings::remove_from_string($identifier, pts_strings::CHAR_NUMERIC | pts_strings::CHAR_DASH | pts_strings::CHAR_DECIMAL);
				}

				$this->is_tracker = count(array_unique($identifiers)) <= 1 || $is_sha1_hash || $has_sha1_shorthash;

				if($this->is_tracker)
				{
					$hw = $this->get_system_hardware();

					if(isset($hw[1]) && count($hw) == count(array_unique($hw)))
					{
						// it can't be a results tracker if the hardware is always different
						$this->is_tracker = false;
					}
				}

				if($this->is_tracker == false)
				{
					// See if only numbers are changing between runs
					foreach($identifiers as $i => &$identifier)
					{
						if(($x = strpos($identifier, ': ')) !== false)
						{
							$identifier = substr($identifier, ($x + 2));
						}
						if($i > 0 && pts_strings::remove_from_string($identifier, pts_strings::CHAR_NUMERIC | pts_strings::CHAR_DECIMAL) != pts_strings::remove_from_string($identifiers[($i - 1)], pts_strings::CHAR_NUMERIC | pts_strings::CHAR_DECIMAL))
						{
							return false;
						}
					}
					$this->is_tracker = true;
				}
			}
			else
			{
				// Definitely not a tracker as not over 5 results
				$this->is_tracker = false;
			}
		}

		return $this->is_tracker;
	}
	public function is_multi_way_comparison($identifiers = false, $extra_attributes = null)
	{
		if(isset($extra_attributes['force_tracking_line_graph']))
		{
			// Phoromatic result tracker
			$is_multi_way = true;
			$this->is_multi_way_inverted = true;
		}
		else
		{
			$hw = null; // XXX: this isn't used anymore at least for now on system hardware
			if($identifiers == false)
			{
				$identifiers = $this->get_system_identifiers();
			}
			$is_multi_way = count($identifiers) < 2 ? false : pts_render::multi_way_identifier_check($identifiers);
			$this->is_multi_way_inverted = $is_multi_way && $is_multi_way[1];
		}

		return $is_multi_way;
	}
	public function is_multi_way_inverted()
	{
		return $this->is_multi_way_inverted;
	}
	public function get_contained_test_profiles($unique = false)
	{
		$test_profiles = array();

		foreach($this->result_objects as $object)
		{
			$test_profiles[] = $object->test_profile;
		}
		if($unique)
		{
			$test_profiles = array_unique($test_profiles);
		}

		return $test_profiles;
	}
	public function override_result_objects($result_objects)
	{
		$this->result_objects = $result_objects;
	}
	public function get_result($ch)
	{
		return isset($this->result_objects[$ch]) ? $this->result_objects[$ch] : false;
	}
	public function remove_result_object_by_id($index_or_indexes, $delete_child_objects = true)
	{
		$did_remove = false;
		foreach(pts_arrays::to_array($index_or_indexes) as $index)
		{
			if(isset($this->result_objects[$index]))
			{
				unset($this->result_objects[$index]);
				$did_remove = true;

				if($delete_child_objects)
				{
					foreach($this->get_relation_map($index) as $child_ro)
					{
						if(isset($this->result_objects[$child_ro]))
						{
							unset($this->result_objects[$child_ro]);
						}
					}
				}
			}
		}
		return $did_remove;
	}
	public function remove_noisy_results($noise_level_percent = 6)
	{
		foreach($this->result_objects as $i => &$ro)
		{
			if($ro->has_noisy_result($noise_level_percent))
			{
				$this->remove_result_object_by_id($i);
			}
		}
	}
	public function normalize_results($normalize_against = false)
	{
		foreach($this->result_objects as $i => &$ro)
		{
			$ro->normalize_buffer_values($normalize_against);
		}
	}
	public function reduce_precision()
	{
		foreach($this->result_objects as $i => &$ro)
		{
			$ro->test_result_buffer->reduce_precision();
		}
	}
	public function update_annotation_for_result_object_by_id($index, $annotation)
	{
		if(isset($this->result_objects[$index]))
		{
			$this->result_objects[$index]->set_annotation($annotation);
			return true;
		}
		return false;
	}
	public function get_result_object_keys()
	{
		return array_keys($this->result_objects);
	}
	public function get_result_object_by_hash($h)
	{
		return isset($this->result_objects[$h]) ? $this->result_objects[$h] : false;
	}
	public function get_result_objects($select_indexes = -1)
	{
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
						$objects[] = $result;
					}
				}
			}
			else
			{
				foreach(pts_arrays::to_array($select_indexes) as $index)
				{
					if(isset($this->result_objects[$index]))
					{
						$objects[] = $this->result_objects[$index];
					}
				}
			}

			return $objects;
		}

		$skip_objects = defined('SKIP_RESULT_OBJECTS') ? explode(',', SKIP_RESULT_OBJECTS) : false;
		if($skip_objects)
		{
			$ros = $this->result_objects;
			foreach($ros as $index => $ro)
			{
				foreach($skip_objects as $skip)
				{
					if(stripos($ro->test_profile->get_identifier(), $skip) !== false || stripos($ro->get_arguments_description(), $skip) !== false)
					{
						unset($ros[$index]);
						break;
					}
				}
			}

			return $ros;
		}

		return $this->result_objects;
	}
	public function to_json()
	{
		$file = $this->get_xml();
		$file = str_replace(array("\n", "\r", "\t"), '', $file);
		$file = trim(str_replace('"', "'", $file));
		$simple_xml = simplexml_load_string($file);
		return json_encode($simple_xml);
	}
	public function avoid_duplicate_identifiers()
	{
		// avoid duplicate test identifiers
		$identifiers = $this->get_system_identifiers();
		if(count($identifiers) < 2)
		{
			return;
		}
		foreach(pts_arrays::duplicates_in_array($identifiers) as $duplicate)
		{
			while($this->is_system_identifier_in_result_file($duplicate))
			{
				$i = 0;
				do
				{
					$i++;
					$new_identifier = $duplicate . ' #' . $i;
				}
				while($this->is_system_identifier_in_result_file($new_identifier));
				$this->rename_run($duplicate, $new_identifier, false);
			}
		}
	}
	public function rename_run($from, $to, $rename_logs = true)
	{
		$renamed = false;
		if($from == 'PREFIX')
		{
			foreach($this->systems as &$s)
			{
				$s->set_identifier($to . ': ' . $s->get_identifier());
				$renamed = true;
			}
		}
		else if($from == null)
		{
			if(count($this->systems) == 1)
			{
				foreach($this->systems as &$s)
				{
					$s->set_identifier($to);
					$renamed = true;
					break;
				}
			}
		}
		else
		{
			$found = false;
			foreach($this->systems as &$s)
			{
				if($s->get_identifier() == $from)
				{
					$found = true;
					$s->set_identifier($to);
					$renamed = true;
					break;
				}
			}
			if($found && $rename_logs && ($d = $this->get_system_log_dir($from, true)))
			{
				$d = dirname(dirname($d)) . '/';

				foreach(array('test-logs', 'system-logs', 'installation-logs') as $dir_name)
				{
					if(is_dir($d . $dir_name . '/' . $from))
					{
						rename($d . $dir_name . '/' . $from, $d . $dir_name . '/' . $to);
					}
				}
			}
		}

		foreach($this->result_objects as &$result)
		{
			$result->test_result_buffer->rename($from, $to);
		}

		return $renamed;
	}
	public function reorder_runs($new_order)
	{
		foreach($new_order as $identifier)
		{
			foreach($this->systems as $i => $s)
			{
				if($s->get_identifier() == $identifier)
				{
					$c = $s;
					unset($this->systems[$i]);
					$this->systems[] = $c;
					break;
				}
			}
		}

		foreach($this->result_objects as &$result)
		{
			$result->test_result_buffer->reorder($new_order);
		}
	}
	public function remove_run($remove)
	{
		$did_remove = false;
		$remove = pts_arrays::to_array($remove);
		foreach($this->systems as $i => &$s)
		{
			if(in_array($s->get_identifier(), $remove))
			{
				unset($this->systems[$i]);
				$did_remove = true;
			}
		}

		foreach($this->result_objects as &$result)
		{
			$result->test_result_buffer->remove($remove);
		}
		return $did_remove;
	}
	public function add_to_result_file(&$result_file, $only_merge_results_already_present = false)
	{
		foreach($result_file->get_systems() as $s)
		{
			if(!in_array($s, $this->systems))
			{
				$this->systems[] = $s;
			}
		}

		foreach($result_file->get_result_objects() as $result)
		{
			$this->add_result($result, $only_merge_results_already_present);
		}
	}
	public function result_hash_exists(&$result_object)
	{
		$ch = $result_object->get_comparison_hash(true, false);
		return isset($this->result_objects[$ch]) && isset($this->result_objects[$ch]->test_result_buffer);
	}
	public function add_result(&$result_object, $only_if_result_already_present = false)
	{
		if($result_object == null)
		{
			return false;
		}

		$ch = $result_object->get_comparison_hash(true, false);
		if(isset($this->result_objects[$ch]) && isset($this->result_objects[$ch]->test_result_buffer))
		{
			if($result_object->get_annotation() != null)
			{
				$this->result_objects[$ch]->append_annotation($result_object->get_annotation());
			}
			foreach($result_object->test_result_buffer->get_buffer_items() as $bi)
			{
				if($bi->get_result_value() === null)
				{
					continue;
				}

				$this->result_objects[$ch]->test_result_buffer->add_buffer_item($bi);
			}
		}
		else if($only_if_result_already_present == false)
		{
			$this->result_objects[$ch] = $result_object;
		}

		$parent = $result_object->get_parent_hash();
		if($parent)
		{
			if(!isset($this->ro_relation_map[$parent]))
			{
				$this->ro_relation_map[$parent] = array();
			}
			$this->ro_relation_map[$parent][] = $ch;
		}

		return $ch;
	}
	public function add_result_return_object(&$result_object, $only_if_result_already_present = false)
	{
		$ch = $this->add_result($result_object, $only_if_result_already_present);
		return isset($this->result_objects[$ch]) ? $this->result_objects[$ch] : false;
	}
	public function get_xml($to = null, $force_nice_formatting = false)
	{
		$xml_writer = new nye_XmlWriter(null, $force_nice_formatting);
		$xml_writer->addXmlNode('PhoronixTestSuite/Generated/Title', $this->get_title());
		$xml_writer->addXmlNode('PhoronixTestSuite/Generated/LastModified', date('Y-m-d H:i:s', pts_client::current_time()));
		$xml_writer->addXmlNode('PhoronixTestSuite/Generated/TestClient', pts_core::program_title());
		$xml_writer->addXmlNode('PhoronixTestSuite/Generated/Description', $this->get_description());
		$xml_writer->addXmlNodeWNE('PhoronixTestSuite/Generated/Notes', $this->get_notes());
		$xml_writer->addXmlNodeWNE('PhoronixTestSuite/Generated/InternalTags', $this->get_internal_tags());
		$xml_writer->addXmlNodeWNE('PhoronixTestSuite/Generated/ReferenceID', $this->get_reference_id());
		$xml_writer->addXmlNodeWNE('PhoronixTestSuite/Generated/PreSetEnvironmentVariables', $this->get_preset_environment_variables());

		// Write the system hardware/software information
		foreach($this->get_systems() as $s)
		{
			$xml_writer->addXmlNode('PhoronixTestSuite/System/Identifier', $s->get_identifier());
			$xml_writer->addXmlNode('PhoronixTestSuite/System/Hardware', $s->get_hardware());
			$xml_writer->addXmlNode('PhoronixTestSuite/System/Software', $s->get_software());
			$xml_writer->addXmlNode('PhoronixTestSuite/System/User', $s->get_username());
			$xml_writer->addXmlNode('PhoronixTestSuite/System/TimeStamp', $s->get_timestamp());
			$xml_writer->addXmlNode('PhoronixTestSuite/System/TestClientVersion', $s->get_client_version());
			$xml_writer->addXmlNode('PhoronixTestSuite/System/Notes', $s->get_notes());

			if(!defined('USER_PTS_CORE_VERSION') || USER_PTS_CORE_VERSION > 3722)
			{
				// Ensure that a supported result file schema is being written...
				// USER_PTS_CORE_VERSION is set by OpenBenchmarking.org so if the requested client is old, don't write this data to send back to their version
				$xml_writer->addXmlNodeWNE('PhoronixTestSuite/System/JSON', ($s->get_json() ? json_encode($s->get_json()) : null));
			}
		}

		// Write the results
		foreach($this->result_objects as $result_object)
		{
			$buffer_items = $result_object->test_result_buffer->get_buffer_items();

			if(count($buffer_items) == 0)
			{
				continue;
			}

			$xml_writer->addXmlNode('PhoronixTestSuite/Result/Identifier', $result_object->test_profile->get_identifier());
			$xml_writer->addXmlNode('PhoronixTestSuite/Result/Title', $result_object->test_profile->get_title());
			$xml_writer->addXmlNode('PhoronixTestSuite/Result/AppVersion', $result_object->test_profile->get_app_version());
			$xml_writer->addXmlNode('PhoronixTestSuite/Result/Arguments', $result_object->get_arguments());
			$xml_writer->addXmlNode('PhoronixTestSuite/Result/Description', $result_object->get_arguments_description());
			$xml_writer->addXmlNode('PhoronixTestSuite/Result/Scale', $result_object->test_profile->get_result_scale());
			$xml_writer->addXmlNode('PhoronixTestSuite/Result/Proportion', $result_object->test_profile->get_result_proportion());
			$xml_writer->addXmlNode('PhoronixTestSuite/Result/DisplayFormat', $result_object->test_profile->get_display_format());
			$xml_writer->addXmlNodeWNE('PhoronixTestSuite/Result/Annotation', $result_object->get_annotation());
			$xml_writer->addXmlNodeWNE('PhoronixTestSuite/Result/Parent', $result_object->get_parent_hash());

			foreach($buffer_items as $i => &$buffer_item)
			{
				$xml_writer->addXmlNode('PhoronixTestSuite/Result/Data/Entry/Identifier', $buffer_item->get_result_identifier());
				$xml_writer->addXmlNode('PhoronixTestSuite/Result/Data/Entry/Value', $buffer_item->get_result_value());
				$xml_writer->addXmlNode('PhoronixTestSuite/Result/Data/Entry/RawString', $buffer_item->get_result_raw());

				if(!defined('USER_PTS_CORE_VERSION') || USER_PTS_CORE_VERSION > 3722)
				{
					// Ensure that a supported result file schema is being written...
					// USER_PTS_CORE_VERSION is set by OpenBenchmarking.org so if the requested client is old, don't write this data to send back to their version
					$xml_writer->addXmlNodeWNE('PhoronixTestSuite/Result/Data/Entry/JSON', ($buffer_item->get_result_json() ? json_encode($buffer_item->get_result_json()) : null));
				}
			}
		}

		return $to == null ? $xml_writer->getXML() : $xml_writer->saveXMLFile($to);
	}
	public function merge($result_merges_to_combine, $pass_attributes = 0, $add_prefix = null, $merge_meta = false, $only_prefix_on_collision = false)
	{
		if(!is_array($result_merges_to_combine) || empty($result_merges_to_combine))
		{
			return false;
		}

		foreach($result_merges_to_combine as &$result_file)
		{
			if(!($result_file instanceof pts_result_file))
			{
				if(defined('PTS_SAVE_RESULTS_PATH') && is_file(PTS_SAVE_RESULTS_PATH . $result_file . '/composite.xml'))
				{
					$result_file = new pts_result_file(PTS_SAVE_RESULTS_PATH . $result_file . '/composite.xml', true);
				}
				else
				{
					continue;
				}
			}

			if($add_prefix)
			{
				if($only_prefix_on_collision)
				{
					$this_identifiers = $this->get_system_identifiers();
					foreach($result_file->systems as &$s)
					{
						if(in_array($s->get_identifier(), $this_identifiers))
						{
							$s->set_identifier($add_prefix . ': ' . $s->get_identifier());
						}
					}
				}
				else
				{
					$result_file->rename_run('PREFIX', $add_prefix);
				}
			}

			if($this->get_title() == null && $result_file->get_title() != null)
			{
				$this->set_title($result_file->get_title());
			}

			if($this->get_description() == null && $result_file->get_description() != null)
			{
				$this->set_description($result_file->get_description());
			}

			$this->add_to_result_file($result_file);

			if($merge_meta)
			{
				if($result_file->get_title() != null && stripos($this->get_title(), $result_file->get_title()) === false)
				{
					$this->set_title($this->get_title() . ', ' . $result_file->get_title());
				}
				if($result_file->get_description() != null && stripos($this->get_description(), $result_file->get_description()) === false)
				{
					$this->set_description($this->get_description() . PHP_EOL . PHP_EOL . $result_file->get_title() . ': ' . $result_file->get_description());
				}
			}
			unset($result_file);
		}
	}
	public function contains_system_hardware($search)
	{
		foreach($this->get_system_hardware() as $h)
		{
			if(stripos($h, $search) !== false)
			{
				return true;
			}
		}
		return false;
	}
	public function contains_system_software($search)
	{
		foreach($this->get_system_software() as $s)
		{
			if(stripos($s, $search) !== false)
			{
				return true;
			}
		}
		return false;
	}
	public function contains_test($search)
	{
		foreach($this->get_contained_test_profiles() as $test_profile)
		{
			if(stripos($test_profile->get_identifier(), $search) !== false || stripos($test_profile->get_title(), $search) !== false)
			{
				return true;
			}
		}
		return false;
	}
	public function sort_result_object_order_by_spread($asc = false)
	{
		uasort($this->result_objects, array('pts_result_file', 'result_spread_comparison'));

		if($asc == false)
		{
			$this->result_objects = array_reverse($this->result_objects, true);
		}
	}
	public static function result_spread_comparison($a, $b)
	{
		return strcmp($a->get_spread(), $b->get_spread());
	}
	public function sort_result_object_order_by_common_suites_workloads()
	{
		uasort($this->result_objects, array('pts_result_file', 'result_common_suite_comparison'));
		foreach($this->result_objects as $i => $ro)
		{
			if($ro->test_profile->get_identifier() == null || $ro->get_parent_hash() != null)
			{
				$v = $ro;
				unset($this->result_objects[$i]);
				$this->result_objects[$i] = $v;
			}
		}
	}
	public static function result_common_suite_comparison($a, $b)
	{
		if($a->test_profile->get_identifier() == null)
		{
			return $b->test_profile->get_identifier() == null ? 0 : -1;
		}
		$a_suites = pts_test_suites::test_to_common_suites($a->test_profile);
		$b_suites = pts_test_suites::test_to_common_suites($b->test_profile);
		if(empty($a_suites))
		{
			return 999;
		}
		else if($a_suites == $b_suites)
		{
			return strnatcmp(strtolower($a->test_profile->get_title()) . ' ' . $a->test_profile->get_app_version(), strtolower($b->test_profile->get_title()) . ' ' . $b->test_profile->get_app_version());
		}
		else
		{
			return strnatcmp($a_suites, $b_suites);
			/*$a_suites .= ' ';
			$b_suites .= ' ';
			$first_suite_cmp = strcmp(substr($a_suites, 0, strpos($a_suites, ' ')), substr($b_suites, 0, strpos($b_suites, ' ')));
			return $first_suite_cmp != 0 ? $first_suite_cmp : strcmp($a_suites, $b_suites);*/
		}
	}
	public function sort_result_object_order_by_title($asc = true)
	{
		uasort($this->result_objects, array('pts_result_file', 'result_title_comparison'));

		if($asc == false)
		{
			$this->result_objects = array_reverse($this->result_objects, true);
		}
	}
	public static function result_title_comparison($a, $b)
	{
		return strcmp(strtolower($a->test_profile->get_title()) . ' ' . $a->test_profile->get_app_version(), strtolower($b->test_profile->get_title()) . ' ' . $b->test_profile->get_app_version());
	}
	public function sort_result_object_order_by_result_scale($asc = true)
	{
		uasort($this->result_objects, array('pts_result_file', 'result_scale_comparison'));

		if($asc == false)
		{
			$this->result_objects = array_reverse($this->result_objects, true);
		}
	}
	public static function result_scale_comparison($a, $b)
	{
		return strcmp($a->test_profile->get_result_proportion() . ' ' . strtolower($a->test_profile->get_result_scale()) . ' ' . $a->test_profile->get_identifier(), $b->test_profile->get_result_proportion() . ' ' . strtolower($b->test_profile->get_result_scale()) . ' ' . $a->test_profile->get_identifier());
	}
	public function get_test_run_times()
	{
		$run_times = array();
		foreach($this->get_system_identifiers() as $si)
		{
			$run_times[$si] = 0;
		}
		foreach($this->result_objects as &$ro)
		{
			foreach($ro->get_run_times() as $si => $elapsed_time)
			{
				if(isset($run_times[$si]) && $elapsed_time > 0)
				{
					$run_times[$si] += $elapsed_time;
				}
			}
		}

		return $run_times;
	}
	public function sort_result_object_order_by_run_time($asc = false)
	{
		uasort($this->result_objects, array('pts_result_file', 'result_run_time_comparison'));

		if($asc == false)
		{
			$this->result_objects = array_reverse($this->result_objects, true);
		}
	}
	public static function result_run_time_comparison($a, $b)
	{
		$a = $a->get_run_time_avg();
		$b = $b->get_run_time_avg();

		if($a == $b)
		{
			return 0;
		}

		return $a < $b ? -1 : 1;
	}
	public function get_install_log_for_test(&$test_profile, $read_file = false, $cleanse_file = true)
	{
		// if $read_file is false, index will be returned. if $read_file is -2, will return whether log files simply exist
		$files = array();
		static $logs_exist_for_test; // caching helper
		$test_file_name_chunk = $test_profile->get_identifier_simplified() . '.log';

		if($read_file == -2 && isset($logs_exist_for_test[$test_file_name_chunk]))
		{
			return $logs_exist_for_test[$test_file_name_chunk];
		}

		if($this->get_test_installation_log_dir() && count($d = pts_file_io::glob($this->get_test_installation_log_dir() . '*/' . $test_file_name_chunk)) > 0)
		{
			$logs_exist_for_test[$test_file_name_chunk] = true;
			if($read_file == -2)
			{
				return true;
			}

			foreach($d as $file)
			{
				$basename_file = basename(dirname($file));
				if($read_file !== false && $basename_file == $read_file)
				{
					$file = file_get_contents($file);
					return $cleanse_file ? phodevi_vfs::cleanse_file($file, $basename_file) : $file;
				}
				$files[] = $basename_file;
			}
		}
		else if($this->get_result_dir() && is_file($this->get_result_dir() . 'installation-logs.zip') && extension_loaded('zip'))
		{
			$logs_exist_for_test[$test_file_name_chunk] = true;
			if($read_file == -2)
			{
				// TODO: could make this more accurate to ensure a precise match, but could become expensive
				return true;
			}

			$zip = new ZipArchive();
			$res = $zip->open($this->get_result_dir() . 'installation-logs.zip');

			if($res === true)
			{
				$search_for_file_name_length = strlen($test_file_name_chunk);
				for($i = 0; $i < $zip->numFiles; $i++)
				{
					$index = $zip->getNameIndex($i);
					if(isset($index[$search_for_file_name_length]) && substr($index, (0 - $search_for_file_name_length)) == $test_file_name_chunk)
					{
						$basename_file = basename(dirname($index));

						if($basename_file != null)
						{
							if($read_file !== false && $basename_file == $read_file)
							{
								$c = $zip->getFromName($index);
								$contents = $cleanse_file ? phodevi_vfs::cleanse_file($c, $basename_file) : $c;
								$zip->close();
								return $contents;
							}
							$files[] = $basename_file;
						}
					}
				}
				$zip->close();
			}
		}

		$logs_exist_for_test[$test_file_name_chunk] = !empty($files);
		if($read_file == -2)
		{
			return false;
		}

		return $read_file !== false ? false : $files;
	}
	public function get_test_run_log_for_result(&$result_object, $read_file = false, $cleanse_file = true)
	{
		// if $read_file is false, index will be returned. if $read_file is -2, will return whether log files simply exist
		$files = array();
		static $logs_exist_for_test; // caching helper
		$ro_hash = $result_object->get_comparison_hash(true, false);

		if($read_file == -2 && isset($logs_exist_for_test[$ro_hash]))
		{
			return $logs_exist_for_test[$ro_hash];
		}

		if(($test_log_dir = $this->get_test_log_dir($result_object)) && count($d = pts_file_io::glob($test_log_dir . '*.log')) > 0)
		{
			$logs_exist_for_test[$ro_hash] = true;
			if($read_file == -2)
			{
				return true;
			}

			foreach($d as $file)
			{
				$basename_file = basename($file);
				if($read_file !== false && $basename_file == $read_file)
				{
					$file = file_get_contents($file);
					return $cleanse_file ? phodevi_vfs::cleanse_file($file, $basename_file) : $file;
				}
				$files[] = $basename_file;
			}
		}
		else if($this->get_result_dir() && is_file($this->get_result_dir() . 'test-logs.zip') && extension_loaded('zip'))
		{
			$logs_exist_for_test[$ro_hash] = true;
			if($read_file == -2)
			{
				// TODO: could make this more accurate to ensure a precise match, but could become expensive
				return true;
			}

			$zip = new ZipArchive();
			$res = $zip->open($this->get_result_dir() . 'test-logs.zip');

			if($res === true)
			{
				$log_path = 'test-logs/' . $ro_hash . '/';
				$log_path_l = strlen($log_path);
				for($i = 0; $i < $zip->numFiles; $i++)
				{
					$index = $zip->getNameIndex($i);
					if(isset($index[$log_path_l]) && substr($index, 0, $log_path_l) == $log_path)
					{
						$basename_file = substr($index, $log_path_l);
						if($basename_file != null)
						{
							if($read_file !== false && $basename_file == $read_file)
							{
								$c = $zip->getFromName($index);
								$contents = $cleanse_file ? phodevi_vfs::cleanse_file($c, $basename_file) : $c;
								$zip->close();
								return $contents;
							}
							$files[] = $basename_file;
						}
					}
				}
				$zip->close();
			}
		}

		$logs_exist_for_test[$ro_hash] = !empty($files);
		if($read_file == -2)
		{
			return false;
		}

		return $read_file !== false ? false : $files;
	}
}

?>
