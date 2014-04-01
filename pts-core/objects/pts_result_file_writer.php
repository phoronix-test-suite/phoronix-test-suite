<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2012, Phoronix Media
	Copyright (C) 2010 - 2012, Michael Larabel

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

class pts_result_file_writer
{
	public $xml_writer = null;
	private $added_hashes = null;
	private $result_identifier = null;
	private $result_count = 0;

	public function __construct($result_identifier = null, &$xml_writer = null)
	{
		$this->result_identifier = $result_identifier;
		$this->added_hashes = array();

		if($xml_writer instanceof nye_XmlWriter)
		{
			$this->xml_writer = $xml_writer;
		}
		else
		{
			$this->xml_writer = new nye_XmlWriter((PTS_IS_CLIENT ? 'pts-results-viewer.xsl' : null));
		}
	}
	public function get_xml()
	{
		return $this->xml_writer->getXML();
	}
	public function get_result_identifier()
	{
		return $this->result_identifier;
	}
	public function save_xml($to_save)
	{
		return $this->xml_writer->saveXMLFile($to_save);
	}
	public function get_result_count()
	{
		return $this->result_count;
	}
	protected function add_result_from_result_object(&$result_object)
	{
		$this->xml_writer->addXmlNode('PhoronixTestSuite/Result/Identifier', $result_object->test_profile->get_identifier());
		$this->xml_writer->addXmlNode('PhoronixTestSuite/Result/Title', $result_object->test_profile->get_title());
		$this->xml_writer->addXmlNode('PhoronixTestSuite/Result/AppVersion', $result_object->test_profile->get_app_version());
		$this->xml_writer->addXmlNode('PhoronixTestSuite/Result/Arguments', $result_object->get_arguments());
		$this->xml_writer->addXmlNode('PhoronixTestSuite/Result/Description', $result_object->get_arguments_description());
		$this->xml_writer->addXmlNode('PhoronixTestSuite/Result/Scale', $result_object->test_profile->get_result_scale());
		$this->xml_writer->addXmlNode('PhoronixTestSuite/Result/Proportion', $result_object->test_profile->get_result_proportion());
		$this->xml_writer->addXmlNode('PhoronixTestSuite/Result/DisplayFormat', $result_object->test_profile->get_display_format());
		$this->result_count++;
	}
	public function add_result_from_result_object_with_value_string(&$result_object, $result_value, $result_value_raw = null, $json = null)
	{
		$this->add_result_from_result_object($result_object);
		$this->xml_writer->addXmlNode('PhoronixTestSuite/Result/Data/Entry/Identifier', $this->result_identifier);
		$this->xml_writer->addXmlNode('PhoronixTestSuite/Result/Data/Entry/Value', $result_value);
		$this->xml_writer->addXmlNode('PhoronixTestSuite/Result/Data/Entry/RawString', $result_value_raw);

		if(!defined('USER_PTS_CORE_VERSION') || USER_PTS_CORE_VERSION > 3722)
		{
			// Ensure that a supported result file schema is being written...
			// USER_PTS_CORE_VERSION is set by OpenBenchmarking.org so if the requested client is old, don't write this data to send back to their version
			$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/Result/Data/Entry/JSON', ($json ? json_encode($json) : null));
		}
	}
	public function add_result_from_result_object_with_value(&$result_object)
	{
		$buffer_items = $result_object->test_result_buffer->get_buffer_items();

		if(count($buffer_items) == 0)
		{
			return false;
		}

		$this->add_result_from_result_object($result_object);

		foreach($buffer_items as $i => &$buffer_item)
		{
			$this->xml_writer->addXmlNode('PhoronixTestSuite/Result/Data/Entry/Identifier', $buffer_item->get_result_identifier());
			$this->xml_writer->addXmlNode('PhoronixTestSuite/Result/Data/Entry/Value', $buffer_item->get_result_value());
			$this->xml_writer->addXmlNode('PhoronixTestSuite/Result/Data/Entry/RawString', $buffer_item->get_result_raw());

			if(!defined('USER_PTS_CORE_VERSION') || USER_PTS_CORE_VERSION > 3722)
			{
				// Ensure that a supported result file schema is being written...
				// USER_PTS_CORE_VERSION is set by OpenBenchmarking.org so if the requested client is old, don't write this data to send back to their version
				$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/Result/Data/Entry/JSON', ($buffer_item->get_result_json() ? json_encode($buffer_item->get_result_json()) : null));
			}
		}

		return true;
	}
	public function add_results_from_result_manager(&$result_manager)
	{
		foreach($result_manager->get_results() as $result_object)
		{
			$this->add_result_from_result_object_with_value($result_object);
		}
	}
	public function add_results_from_result_file(&$result_file)
	{
		foreach($result_file->get_result_objects() as $result_object)
		{
			$this->add_result_from_result_object_with_value($result_object);
		}
	}
	public function add_test_notes($test_notes, $json = null)
	{
		$this->xml_writer->addXmlNode('PhoronixTestSuite/System/Notes', $test_notes);
		$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/System/JSON', ($json ? json_encode($json) : null));
	}
	public function add_result_file_meta_data(&$object, $reference_id = null, $title = null, $description = null)
	{
		$this->xml_writer->addXmlNode('PhoronixTestSuite/Generated/Title', $title != null ? $title : $object->get_title());
		$this->xml_writer->addXmlNode('PhoronixTestSuite/Generated/LastModified', date('Y-m-d H:i:s'));
		$this->xml_writer->addXmlNode('PhoronixTestSuite/Generated/TestClient', pts_title(true));
		$this->xml_writer->addXmlNode('PhoronixTestSuite/Generated/Description', $description != null ? $description : $object->get_description());
		$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/Generated/Notes', $object->get_notes());
		$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/Generated/InternalTags', $object->get_internal_tags());
		$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/Generated/ReferenceID', ($reference_id != null ? $reference_id : $object->get_reference_id()));
		$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/Generated/PreSetEnvironmentVariables', $object->get_preset_environment_variables());
	}
	public function add_current_system_information()
	{
		$this->xml_writer->addXmlNode('PhoronixTestSuite/System/Identifier', $this->result_identifier);
		$this->xml_writer->addXmlNode('PhoronixTestSuite/System/Hardware', phodevi::system_hardware(true));
		$this->xml_writer->addXmlNode('PhoronixTestSuite/System/Software', phodevi::system_software(true));
		$this->xml_writer->addXmlNode('PhoronixTestSuite/System/User', pts_client::current_user());
		$this->xml_writer->addXmlNode('PhoronixTestSuite/System/TimeStamp', date('Y-m-d H:i:s'));
		$this->xml_writer->addXmlNode('PhoronixTestSuite/System/TestClientVersion', PTS_VERSION);
		//$this->xml_writer->addXmlNode('PhoronixTestSuite/System/Notes', pts_test_notes_manager::generate_test_notes($test_type));
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
		$system_json = $result_file->get_system_json();

		// Write the system hardware/software information
		foreach(array_keys($system_hardware) as $i)
		{
			if(!($is_pts_rms = ($result_merge_select instanceof pts_result_merge_select)) || $result_merge_select->get_selected_identifiers() == null || in_array($associated_identifiers[$i], $result_merge_select->get_selected_identifiers()))
			{
				// Prevents any information from being repeated
				$this_hash = md5($associated_identifiers[$i] . ';' . $system_hardware[$i] . ';' . $system_software[$i] . ';' . $system_date[$i]);

				if(!in_array($this_hash, $this->added_hashes))
				{
					if($is_pts_rms && ($renamed = $result_merge_select->get_rename_identifier()) != null)
					{
						$associated_identifiers[$i] = $renamed;
					}

					$this->xml_writer->addXmlNode('PhoronixTestSuite/System/Identifier', $associated_identifiers[$i]);
					$this->xml_writer->addXmlNode('PhoronixTestSuite/System/Hardware', $system_hardware[$i]);
					$this->xml_writer->addXmlNode('PhoronixTestSuite/System/Software', $system_software[$i]);
					$this->xml_writer->addXmlNode('PhoronixTestSuite/System/User', $system_user[$i]);
					$this->xml_writer->addXmlNode('PhoronixTestSuite/System/TimeStamp', $system_date[$i]);
					$this->xml_writer->addXmlNode('PhoronixTestSuite/System/TestClientVersion', $pts_version[$i]);
					$this->xml_writer->addXmlNode('PhoronixTestSuite/System/Notes', $system_notes[$i]);

					if(!defined('USER_PTS_CORE_VERSION') || USER_PTS_CORE_VERSION > 3722)
					{
						// Ensure that a supported result file schema is being written...
						// USER_PTS_CORE_VERSION is set by OpenBenchmarking.org so if the requested client is old, don't write this data to send back to their version
						$this->xml_writer->addXmlNodeWNE('PhoronixTestSuite/System/JSON', ($system_json[$i] ? json_encode($system_json[$i]) : null));
					}

					array_push($this->added_hashes, $this_hash);
				}
			}
		}
	}
}

?>
