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

		$this->xml_writer = new tandem_XmlWriter();
		$this->xml_writer->setXslBinding("pts-results-viewer.xsl");

		if(PTS_IS_CLIENT)
		{
			$this->xml_writer->addXmlObject(P_RESULTS_GENERATED_TIMESTAMP, -2, date("Y-m-d H:i:s"));
			$this->xml_writer->addXmlObject(P_RESULTS_GENERATED_CLIENT_STRING, -2, pts_title(true));
		}
	}
	public function save_xml($to_save)
	{
		$this->xml_writer->saveXMLFile($to_save);
	}
	public function get_xml()
	{
		return $this->xml_writer->getXML();
	}
	public function add_result_from_result_object(&$result_object, $result_value, $result_value_raw = null)
	{
		$tandem_id = $this->xml_writer->request_unique_id();

		$this->xml_writer->addXmlObject(P_RESULTS_TEST_IDENTIFIER, $tandem_id, $result_object->test_profile->get_identifier());
		$this->xml_writer->addXmlObject(P_RESULTS_TEST_PROFILE_VERSION, $tandem_id, $result_object->test_profile->get_test_profile_version());
		$this->xml_writer->addXmlObject(P_RESULTS_TEST_TITLE, $tandem_id, $result_object->test_profile->get_title());
		$this->xml_writer->addXmlObject(P_RESULTS_TEST_ARGS, $tandem_id, $result_object->get_arguments());
		$this->xml_writer->addXmlObject(P_RESULTS_TEST_ARGS_DESCRIPTION, $tandem_id, $result_object->get_arguments_description());
		$this->xml_writer->addXmlObject(P_RESULTS_TEST_VERSION, $tandem_id, $result_object->test_profile->get_version());
		$this->xml_writer->addXmlObject(P_RESULTS_TEST_SCALE, $tandem_id, $result_object->test_profile->get_result_scale());
		$this->xml_writer->addXmlObject(P_RESULTS_TEST_PROPORTION, $tandem_id, $result_object->test_profile->get_result_proportion());
		$this->xml_writer->addXmlObject(P_RESULTS_TEST_DISPLAY_FORMAT, $tandem_id, $result_object->test_profile->get_display_format());

		$this->xml_writer->addXmlObject(P_RESULTS_RESULTS_GROUP_IDENTIFIER, $tandem_id, $this->result_identifier, 4);
		$this->xml_writer->addXmlObject(P_RESULTS_RESULTS_GROUP_VALUE, $tandem_id, $result_value, 4);
		$this->xml_writer->addXmlObject(P_RESULTS_RESULTS_GROUP_RAW, $tandem_id, $result_value_raw, 4);
	}
	public function add_results_from_result_file(&$result_manager)
	{
		$results_added = 0;

		foreach($result_manager->get_results() as $result_object)
		{
			$buffer_items = $result_object->test_result_buffer->get_buffer_items();

			if(count($buffer_items) == 0)
			{
				continue;
			}

			$use_id = $this->xml_writer->request_unique_id();
			$this->xml_writer->addXmlObject(P_RESULTS_TEST_TITLE, $use_id, $result_object->test_profile->get_title());
			$this->xml_writer->addXmlObject(P_RESULTS_TEST_VERSION, $use_id, $result_object->test_profile->get_version());
			$this->xml_writer->addXmlObject(P_RESULTS_TEST_PROFILE_VERSION, $use_id, $result_object->test_profile->get_test_profile_version());
			$this->xml_writer->addXmlObject(P_RESULTS_TEST_ARGS, $use_id, $result_object->get_arguments());
			$this->xml_writer->addXmlObject(P_RESULTS_TEST_ARGS_DESCRIPTION, $use_id, $result_object->get_arguments_description());
			$this->xml_writer->addXmlObject(P_RESULTS_TEST_SCALE, $use_id, $result_object->test_profile->get_result_scale());
			$this->xml_writer->addXmlObject(P_RESULTS_TEST_PROPORTION, $use_id, $result_object->test_profile->get_result_proportion());
			$this->xml_writer->addXmlObject(P_RESULTS_TEST_IDENTIFIER, $use_id, $result_object->test_profile->get_identifier());
			$this->xml_writer->addXmlObject(P_RESULTS_TEST_DISPLAY_FORMAT, $use_id, $result_object->test_profile->get_display_format());

			foreach($buffer_items as $i => &$buffer_item)
			{
				$this->xml_writer->addXmlObject(P_RESULTS_RESULTS_GROUP_IDENTIFIER, $use_id, $buffer_item->get_result_identifier(), 4, "o-$i-$results_added-r");
				$this->xml_writer->addXmlObject(P_RESULTS_RESULTS_GROUP_VALUE, $use_id, $buffer_item->get_result_value(), 4, "o-$i-$results_added-r");
				$this->xml_writer->addXmlObject(P_RESULTS_RESULTS_GROUP_RAW, $use_id, $buffer_item->get_result_raw(), 4, "o-$i-$results_added-r");
			}

			$results_added++;
		}
	}
	public function add_test_notes($test_notes)
	{
		$this->xml_writer->addXmlObject(P_RESULTS_SYSTEM_NOTES, 0, $test_notes, 0);
	}
	public function add_result_file_meta_data(&$test_run_manager)
	{
		$this->xml_writer->addXmlObject(P_RESULTS_GENERATED_TITLE, -2, $test_run_manager->get_file_name_title());
		$this->xml_writer->addXmlObject(P_RESULTS_GENERATED_DESCRIPTION, -2, $test_run_manager->get_run_description());
		$this->xml_writer->addXmlObject(P_RESULTS_GENERATED_NOTES, -2, $test_run_manager->get_run_notes());
		$this->xml_writer->addXmlObject(P_RESULTS_GENERATED_PRESET_ENV_VARS, -2, pts_module_manager::var_store_string());
	}
	public function add_result_file_meta_data_from_result_file(&$result_file)
	{
		$this->xml_writer->addXmlObject(P_RESULTS_GENERATED_TITLE, -2, $result_file->get_title());
		$this->xml_writer->addXmlObject(P_RESULTS_GENERATED_DESCRIPTION, -2, $result_file->get_description());
		$this->xml_writer->addXmlObject(P_RESULTS_GENERATED_NOTES, -2, $result_file->get_notes());
		$this->xml_writer->addXmlObject(P_RESULTS_GENERATED_PRESET_ENV_VARS, -2, $result_file->get_preset_environment_variables());
	}
	public function add_current_system_information()
	{
		$this->xml_writer->addXmlObject(P_RESULTS_SYSTEM_IDENTIFIERS, 0, $this->result_identifier);
		$this->xml_writer->addXmlObject(P_RESULTS_SYSTEM_HARDWARE, 0, phodevi::system_hardware(true));
		$this->xml_writer->addXmlObject(P_RESULTS_SYSTEM_SOFTWARE, 0, phodevi::system_software(true));
		$this->xml_writer->addXmlObject(P_RESULTS_SYSTEM_USER, 0, pts_client::current_user());
		$this->xml_writer->addXmlObject(P_RESULTS_SYSTEM_DATE, 0, date("Y-m-d H:i:s"));
		//$this->xml_writer->addXmlObject(P_RESULTS_SYSTEM_NOTES, 0, pts_test_notes_manager::generate_test_notes($test_type));
		$this->xml_writer->addXmlObject(P_RESULTS_SYSTEM_PTSVERSION, 0, PTS_VERSION);
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

					$use_id = $this->xml_writer->request_unique_id();
					$this->xml_writer->addXmlObject(P_RESULTS_SYSTEM_IDENTIFIERS, $use_id, $associated_identifiers[$i]);
					$this->xml_writer->addXmlObject(P_RESULTS_SYSTEM_PTSVERSION, $use_id, $pts_version[$i]);
					$this->xml_writer->addXmlObject(P_RESULTS_SYSTEM_HARDWARE, $use_id, $system_hardware[$i]);
					$this->xml_writer->addXmlObject(P_RESULTS_SYSTEM_SOFTWARE, $use_id, $system_software[$i]);
					$this->xml_writer->addXmlObject(P_RESULTS_SYSTEM_USER, $use_id, $system_user[$i]);
					$this->xml_writer->addXmlObject(P_RESULTS_SYSTEM_DATE, $use_id, $system_date[$i]);
					$this->xml_writer->addXmlObject(P_RESULTS_SYSTEM_NOTES, $use_id, $system_notes[$i]);

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
