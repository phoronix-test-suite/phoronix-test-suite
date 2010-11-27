<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010, Phoronix Media
	Copyright (C) 2010, Michael Larabel

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

pts_load_xml_definitions("result-file.xml");

class pts_result_file_writer
{
	private $xml_writer = null;
	private $added_hashes = null;
	private $result_identifier = null;

	public function __construct($result_identifier = null)
	{
		$this->result_identifier = $result_identifier;
		$this->added_hashes = array();

		$this->xml_writer = new nye_XmlWriter("pts-results-viewer.xsl");
	}
	public function get_xml()
	{
		return $this->xml_writer->getXML();
	}
	public function save_xml($to_save)
	{
		return $this->xml_writer->saveXMLFile($to_save);
	}
	protected function add_result_from_result_object(&$result_object)
	{
		$this->xml_writer->addXmlNode(P_RESULTS_TEST_IDENTIFIER, $result_object->test_profile->get_identifier());
		$this->xml_writer->addXmlNode(P_RESULTS_TEST_PROFILE_VERSION, $result_object->test_profile->get_test_profile_version());
		$this->xml_writer->addXmlNode(P_RESULTS_TEST_TITLE, $result_object->test_profile->get_title());
		$this->xml_writer->addXmlNode(P_RESULTS_TEST_VERSION, $result_object->test_profile->get_app_version());
		$this->xml_writer->addXmlNode(P_RESULTS_TEST_ARGS, $result_object->get_arguments());
		$this->xml_writer->addXmlNode(P_RESULTS_TEST_DESCRIPTION, $result_object->get_arguments_description());
		$this->xml_writer->addXmlNode(P_RESULTS_TEST_SCALE, $result_object->test_profile->get_result_scale());
		$this->xml_writer->addXmlNode(P_RESULTS_TEST_PROPORTION, $result_object->test_profile->get_result_proportion());
		$this->xml_writer->addXmlNode(P_RESULTS_TEST_DISPLAY_FORMAT, $result_object->test_profile->get_display_format());
	}
	public function add_result_from_result_object_with_value_string(&$result_object, $result_value, $result_value_raw = null)
	{
		$this->add_result_from_result_object($result_object);

		$this->xml_writer->addXmlNode(P_RESULTS_RESULTS_GROUP_IDENTIFIER, $this->result_identifier);
		$this->xml_writer->addXmlNode(P_RESULTS_RESULTS_GROUP_VALUE, $result_value);
		$this->xml_writer->addXmlNode(P_RESULTS_RESULTS_GROUP_RAW, $result_value_raw);
	}
	public function add_results_from_result_file(&$result_manager)
	{
		foreach($result_manager->get_results() as $result_object)
		{
			$buffer_items = $result_object->test_result_buffer->get_buffer_items();

			if(count($buffer_items) == 0)
			{
				continue;
			}

			$this->add_result_from_result_object($result_object);

			foreach($buffer_items as $i => &$buffer_item)
			{
				$this->xml_writer->addXmlNode(P_RESULTS_RESULTS_GROUP_IDENTIFIER, $buffer_item->get_result_identifier());
				$this->xml_writer->addXmlNode(P_RESULTS_RESULTS_GROUP_VALUE, $buffer_item->get_result_value());
				$this->xml_writer->addXmlNode(P_RESULTS_RESULTS_GROUP_RAW, $buffer_item->get_result_raw());
			}
		}
	}
	public function add_test_notes($test_notes)
	{
		$this->xml_writer->addXmlNode(P_RESULTS_SYSTEM_NOTES, $test_notes);
	}
	public function add_result_file_meta_data(&$object)
	{
		$this->xml_writer->addXmlNode(P_RESULTS_GENERATED_TITLE, $object->get_title());
		$this->xml_writer->addXmlNode(P_RESULTS_GENERATED_DESCRIPTION, $object->get_description());
		$this->xml_writer->addXmlNode(P_RESULTS_GENERATED_NOTES, $object->get_notes());
		$this->xml_writer->addXmlNode(P_RESULTS_GENERATED_TIMESTAMP, date("Y-m-d H:i:s"));
		$this->xml_writer->addXmlNode(P_RESULTS_GENERATED_CLIENT_STRING, pts_title(true));
		$this->xml_writer->addXmlNode(P_RESULTS_GENERATED_INTERNAL_TAGS, $object->get_internal_tags());
		$this->xml_writer->addXmlNode(P_RESULTS_GENERATED_REFERENCE_ID, $object->get_reference_id());
		$this->xml_writer->addXmlNode(P_RESULTS_GENERATED_PRESET_ENV_VARS, $object->get_preset_environment_variables());
	}
	public function add_current_system_information()
	{
		$this->xml_writer->addXmlNode(P_RESULTS_SYSTEM_IDENTIFIERS, $this->result_identifier);
		$this->xml_writer->addXmlNode(P_RESULTS_SYSTEM_HARDWARE, phodevi::system_hardware(true));
		$this->xml_writer->addXmlNode(P_RESULTS_SYSTEM_SOFTWARE, phodevi::system_software(true));
		$this->xml_writer->addXmlNode(P_RESULTS_SYSTEM_USER, pts_client::current_user());
		$this->xml_writer->addXmlNode(P_RESULTS_SYSTEM_DATE, date("Y-m-d H:i:s"));
		//$this->xml_writer->addXmlNode(P_RESULTS_SYSTEM_NOTES, pts_test_notes_manager::generate_test_notes($test_type));
		$this->xml_writer->addXmlNode(P_RESULTS_SYSTEM_PTSVERSION, PTS_VERSION);
	}
	public function add_system_information_from_result_file(&$result_file, $result_merge_select = null)
	{
		$system_hardware = $result_file->get_system_hardware();
		$system_software = $result_file->get_system_software();
		$system_user = $result_file->get_system_user();
		$system_date = $result_file->get_system_date();
		$pts_version = $result_file->get_system_pts_version();
		$system_notes = $result_file->get_system_notes();
		$associated_identifiers = $result_file->get_system_identifiers();

		// Write the system hardware/software information
		foreach(array_keys($system_hardware) as $i)
		{
			if(!($is_pts_rms = ($result_merge_select instanceOf pts_result_merge_select)) || $result_merge_select->get_selected_identifiers() == null || in_array($associated_identifiers[$i], $result_merge_select->get_selected_identifiers()))
			{
				// Prevents any information from being repeated
				$this_hash = md5($associated_identifiers[$i] . ";" . $system_hardware[$i] . ";" . $system_software[$i] . ";" . $system_date[$i]);

				if(!in_array($this_hash, $this->added_hashes))
				{
					if($is_pts_rms && ($renamed = $result_merge_select->get_rename_identifier()) != null)
					{
						$associated_identifiers[$i] = $renamed;
					}

					$this->xml_writer->addXmlNode(P_RESULTS_SYSTEM_IDENTIFIERS, $associated_identifiers[$i]);
					$this->xml_writer->addXmlNode(P_RESULTS_SYSTEM_PTSVERSION, $pts_version[$i]);
					$this->xml_writer->addXmlNode(P_RESULTS_SYSTEM_HARDWARE, $system_hardware[$i]);
					$this->xml_writer->addXmlNode(P_RESULTS_SYSTEM_SOFTWARE, $system_software[$i]);
					$this->xml_writer->addXmlNode(P_RESULTS_SYSTEM_USER, $system_user[$i]);
					$this->xml_writer->addXmlNode(P_RESULTS_SYSTEM_DATE, $system_date[$i]);
					$this->xml_writer->addXmlNode(P_RESULTS_SYSTEM_NOTES, $system_notes[$i]);

					array_push($this->added_hashes, $this_hash);
				}
			}
		}
	}
	public function save_result_file($save_name)
	{
		// Save the test file
		// TODO: clean this up with pts_client::save_test_result
		$j = 1;
		while(is_file(PTS_SAVE_RESULTS_PATH . $save_name . "/test-" . $j . ".xml"))
		{
			$j++;
		}

		$real_name = $save_name . "/test-" . $j . ".xml";

		pts_client::save_test_result($real_name, $this->xml_writer->getXML());

		if(!is_file(PTS_SAVE_RESULTS_PATH . $save_name . "/composite.xml"))
		{
			pts_client::save_test_result($save_name . "/composite.xml", file_get_contents(PTS_SAVE_RESULTS_PATH . $real_name), true, $this->result_identifier);
		}
		else
		{
			// Merge Results
			$merged_results = pts_merge::merge_test_results(file_get_contents(PTS_SAVE_RESULTS_PATH . $save_name . "/composite.xml"), file_get_contents(PTS_SAVE_RESULTS_PATH . $real_name));
			pts_client::save_test_result($save_name . "/composite.xml", $merged_results, true, $this->result_identifier);
		}

		return $real_name;
	}
}

?>
