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

class pts_merge
{
	public static function merge_test_results_array($files_to_combine, $pass_attributes = null)
	{
		$results_xml = new pts_results_tandem_XmlWriter();
		self::merge_test_results_process($results_xml, $files_to_combine, $pass_attributes);

		return $results_xml->getXML();
	}
	public static function merge_test_results()
	{
		// Merge test results
		// Pass the result file names/paths for each test result file to merge as each as a parameter of the array
		$files_to_combine = func_get_args();
		return self::merge_test_results_array($files_to_combine);
	}
	public static function merge_test_results_process(&$results_xml, &$files_to_combine, $pass_attributes = null)
	{
		$test_result_manager = new pts_result_file_merge_manager($pass_attributes);

		$results_xml->setXslBinding("pts-results-viewer.xsl");
		$has_written_suite_info = false;
		$added_systems_hash = array();

		foreach($files_to_combine as &$file)
		{
			if(is_object($file) && $file instanceOf pts_result_merge_select)
			{
				$result_merge_select = $file;
				$this_result_file = new pts_result_file($result_merge_select->get_result_file());

			}
			else if(is_object($file) && $file instanceOf pts_result_file)
			{
				if(($t = $file->read_extra_attribute("rename_result_identifier")) != false)
				{
					// This code path is currently used by Phoromatic
					$result_merge_select = new pts_result_merge_select(null, null);
					$result_merge_select->rename_identifier($t);
				}
				else
				{
					$result_merge_select = null;
				}

				$this_result_file = $file;
			}
			else
			{
				$result_merge_select = new pts_result_merge_select($file, null);
				$this_result_file = new pts_result_file($result_merge_select->get_result_file());
			}

			if($this_result_file->get_test_count() == 0)
			{
				// Why print the system information if there are no contained results?
				continue;
			}

			if(!isset($pass_attributes["only_render_results_xml"]))
			{
				if($has_written_suite_info == false)
				{
					self::result_file_suite_info_to_xml($this_result_file, $results_xml);
					$has_written_suite_info = true;
				}

				self::result_file_system_info_to_xml($this_result_file, $results_xml, $added_systems_hash, $result_merge_select);
			}

			$test_result_manager->add_test_result_set($this_result_file->get_result_objects(), $result_merge_select);
		}

		// Write the actual test results
		self::result_file_results_to_xml($test_result_manager, $results_xml);
	}
	public static function generate_analytical_batch_xml($analyze_file)
	{
		$results = new pts_results_tandem_XmlWriter();
		$results->setXslBinding("pts-results-viewer.xsl");

		$test_result_manager = new pts_result_file_analyze_manager();
		$result_file = new pts_result_file($analyze_file);
		$added_systems_hash = array();

		self::result_file_suite_info_to_xml($result_file, $results);
		self::result_file_system_info_to_xml($result_file, $results, $added_systems_hash, null);

		$test_result_manager->add_test_result_set($result_file->get_result_objects());
		self::result_file_results_to_xml($test_result_manager, $results);

		return $results->getXML();
	}
	protected static function result_file_suite_info_to_xml(&$pts_result_file, &$xml_writer)
	{
		$xml_writer->addXmlObject(P_RESULTS_SUITE_TITLE, 0, $pts_result_file->get_title());
		$xml_writer->addXmlObject(P_RESULTS_SUITE_NAME, 0, $pts_result_file->get_suite_name());
		$xml_writer->addXmlObject(P_RESULTS_SUITE_VERSION, 0, $pts_result_file->get_suite_version());
		$xml_writer->addXmlObject(P_RESULTS_SUITE_DESCRIPTION, 0, $pts_result_file->get_suite_description());
		$xml_writer->addXmlObject(P_RESULTS_SUITE_TYPE, 0, $pts_result_file->get_suite_type());
		$xml_writer->addXmlObject(P_RESULTS_SUITE_EXTENSIONS, 0, $pts_result_file->get_suite_extensions());
		$xml_writer->addXmlObject(P_RESULTS_SUITE_PROPERTIES, 0, $pts_result_file->get_suite_properties());
	}
	protected static function result_file_system_info_to_xml(&$pts_result_file, &$xml_writer, &$systems_hash, $result_merge_select)
	{
		$system_hardware = $pts_result_file->get_system_hardware();
		$system_software = $pts_result_file->get_system_software();
		$system_author = $pts_result_file->get_system_author();
		$system_date = $pts_result_file->get_system_date();
		$pts_version = $pts_result_file->get_system_pts_version();
		$system_notes = $pts_result_file->get_system_notes();
		$associated_identifiers = $pts_result_file->get_system_identifiers();

		// Write the system hardware/software information

		foreach(array_keys($system_hardware) as $i)
		{
			if(!($is_pts_rms = ($result_merge_select instanceOf pts_result_merge_select)) || $result_merge_select->get_selected_identifiers() == null || in_array($associated_identifiers[$i], $result_merge_select->get_selected_identifiers()))
			{
				// Prevents any information from being repeated
				$this_hash = md5($associated_identifiers[$i] . ";" . $system_hardware[$i] . ";" . $system_software[$i] . ";" . $system_date[$i]);

				if(!in_array($this_hash, $systems_hash))
				{
					if($is_pts_rms && ($renamed = $result_merge_select->get_rename_identifier()) != null)
					{
						$associated_identifiers[$i] = $renamed;
					}

					$use_id = $xml_writer->request_unique_id();
					$xml_writer->addXmlObject(P_RESULTS_SYSTEM_HARDWARE, $use_id, $system_hardware[$i]);
					$xml_writer->addXmlObject(P_RESULTS_SYSTEM_SOFTWARE, $use_id, $system_software[$i]);
					$xml_writer->addXmlObject(P_RESULTS_SYSTEM_AUTHOR, $use_id, $system_author[$i]);
					$xml_writer->addXmlObject(P_RESULTS_SYSTEM_DATE, $use_id, $system_date[$i]);
					$xml_writer->addXmlObject(P_RESULTS_SYSTEM_NOTES, $use_id, $system_notes[$i]);
					$xml_writer->addXmlObject(P_RESULTS_SYSTEM_PTSVERSION, $use_id, $pts_version[$i]);
					$xml_writer->addXmlObject(P_RESULTS_SYSTEM_IDENTIFIERS, $use_id, $associated_identifiers[$i]);

					array_push($systems_hash, $this_hash);
				}
			}
		}
	}
	protected static function result_file_results_to_xml(&$result_manager, &$xml_writer)
	{
		$results_added = 0;
		foreach($result_manager->get_results() as $result_object)
		{
			$buffer_items = $result_object->test_result_buffer->get_buffer_items();

			if(count($buffer_items) == 0)
			{
				continue;
			}

			$use_id = $xml_writer->request_unique_id();
			$xml_writer->addXmlObject(P_RESULTS_TEST_TITLE, $use_id, $result_object->test_profile->get_title());
			$xml_writer->addXmlObject(P_RESULTS_TEST_VERSION, $use_id, $result_object->test_profile->get_version());
			$xml_writer->addXmlObject(P_RESULTS_TEST_PROFILE_VERSION, $use_id, $result_object->test_profile->get_test_profile_version());
			$xml_writer->addXmlObject(P_RESULTS_TEST_ATTRIBUTES, $use_id, $result_object->get_used_arguments_description());
			$xml_writer->addXmlObject(P_RESULTS_TEST_SCALE, $use_id, $result_object->test_profile->get_result_scale());
			$xml_writer->addXmlObject(P_RESULTS_TEST_PROPORTION, $use_id, $result_object->test_profile->get_result_proportion());
			$xml_writer->addXmlObject(P_RESULTS_TEST_TESTNAME, $use_id, $result_object->test_profile->get_identifier());
			$xml_writer->addXmlObject(P_RESULTS_TEST_ARGUMENTS, $use_id, $result_object->get_used_arguments());
			$xml_writer->addXmlObject(P_RESULTS_TEST_RESULTFORMAT, $use_id, $result_object->test_profile->get_result_format());

			foreach($buffer_items as $i => &$buffer_item)
			{
				$xml_writer->addXmlObject(P_RESULTS_RESULTS_GROUP_IDENTIFIER, $use_id, $buffer_item->get_result_identifier(), 5, "o-$i-$results_added-r");
				$xml_writer->addXmlObject(P_RESULTS_RESULTS_GROUP_VALUE, $use_id, $buffer_item->get_result_value(), 5, "o-$i-$results_added-r");
				$xml_writer->addXmlObject(P_RESULTS_RESULTS_GROUP_RAW, $use_id, $buffer_item->get_result_raw(), 5, "o-$i-$results_added-r");
			}

			$results_added++;
		}
	}
}

?>
