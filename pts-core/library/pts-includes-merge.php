<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
	pts-includes-merge.php: Functions needed to merge test result files

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

function pts_merge_test_results()
{
	// Merge test results
	// Pass the result file names/paths for each test result file to merge
	$files_to_combine = func_get_args();

	$results = new tandem_XmlWriter();
	$test_result_manager = new pts_result_file_merge_manager();

	$results->setXslBinding("pts-results-viewer.xsl");
	$added_systems_hash = array();

	for($merge_pos = 0; $merge_pos < count($files_to_combine); $merge_pos++)
	{
		if(is_object($files_to_combine[$merge_pos]) && $files_to_combine[$merge_pos] instanceOf pts_result_merge_select)
		{
			$result_file = $files_to_combine[$merge_pos]->get_result_file();
			$selected_identifiers = $files_to_combine[$merge_pos]->get_selected_identifiers();

			if($selected_identifiers != null)
			{
				$selected_identifiers = pts_to_array($selected_identifiers);
			}
		}
		else
		{
			$result_file = $files_to_combine[$merge_pos];
			$selected_identifiers = null;
		}

		$this_result_file = new pts_result_file($result_file);

		if($merge_pos == 0)
		{
			pts_result_file_suite_info_to_xml($this_result_file, $results);
		}

		pts_result_file_system_info_to_xml($this_result_file, $results, $added_systems_hash, $selected_identifiers);
		$test_result_manager->add_test_result_set($this_result_file->get_result_objects(), $selected_identifiers);
	}

	// Write the actual test results
	$results_added = 0;
	foreach($test_result_manager->get_results() as $result_object)
	{
		$use_id = pts_request_new_id();
		$results->addXmlObject(P_RESULTS_TEST_TITLE, $use_id, $result_object->get_name());
		$results->addXmlObject(P_RESULTS_TEST_VERSION, $use_id, $result_object->get_version());
		$results->addXmlObject(P_RESULTS_TEST_ATTRIBUTES, $use_id, $result_object->get_attributes());
		$results->addXmlObject(P_RESULTS_TEST_SCALE, $use_id, $result_object->get_scale());
		$results->addXmlObject(P_RESULTS_TEST_PROPORTION, $use_id, $result_object->get_proportion());
		$results->addXmlObject(P_RESULTS_TEST_TESTNAME, $use_id, $result_object->get_test_name());
		$results->addXmlObject(P_RESULTS_TEST_ARGUMENTS, $use_id, $result_object->get_arguments());
		$results->addXmlObject(P_RESULTS_TEST_RESULTFORMAT, $use_id, $result_object->get_format());

		$identifiers = $result_object->get_identifiers();
		$values = $result_object->get_values();
		$raw_values = $result_object->get_raw_values();

		for($i = 0; $i < count($identifiers); $i++)
		{
			$results->addXmlObject(P_RESULTS_RESULTS_GROUP_IDENTIFIER, $use_id, $identifiers[$i], 5, "o-$i-$results_added-r");
			$results->addXmlObject(P_RESULTS_RESULTS_GROUP_VALUE, $use_id, $values[$i], 5, "o-$i-$results_added-r");
			$results->addXmlObject(P_RESULTS_RESULTS_GROUP_RAW, $use_id, $raw_values[$i], 5, "o-$i-$results_added-r");
		}
		$results_added++;
	}

	return $results->getXML();
}
function pts_result_file_suite_info_to_xml(&$pts_result_file, &$xml_writer)
{
	$xml_writer->addXmlObject(P_RESULTS_SUITE_TITLE, 0, $pts_result_file->get_suite_title());
	$xml_writer->addXmlObject(P_RESULTS_SUITE_NAME, 0, $pts_result_file->get_suite_name());
	$xml_writer->addXmlObject(P_RESULTS_SUITE_VERSION, 0, $pts_result_file->get_suite_version());
	$xml_writer->addXmlObject(P_RESULTS_SUITE_DESCRIPTION, 0, $pts_result_file->get_suite_description());
	$xml_writer->addXmlObject(P_RESULTS_SUITE_TYPE, 0, $pts_result_file->get_suite_type());
	$xml_writer->addXmlObject(P_RESULTS_SUITE_EXTENSIONS, 0, $pts_result_file->get_suite_extensions());
	$xml_writer->addXmlObject(P_RESULTS_SUITE_PROPERTIES, 0, $pts_result_file->get_suite_properties());
}
function pts_result_file_system_info_to_xml(&$pts_result_file, &$xml_writer, &$systems_hash, $selected_identifiers = null)
{
	$system_hardware = $pts_result_file->get_system_hardware();
	$system_software = $pts_result_file->get_system_software();
	$system_author = $pts_result_file->get_system_author();
	$system_date = $pts_result_file->get_system_date();
	$pts_version = $pts_result_file->get_system_pts_version();
	$system_notes = $pts_result_file->get_system_notes();
	$associated_identifiers = $pts_result_file->get_system_identifiers();

	// Write the system hardware/software information

	for($i = 0; $i < count($system_hardware); $i++)
	{
		if($selected_identifiers == null || in_array($associated_identifiers[$i], $selected_identifiers))
		{
			$this_hash = md5($associated_identifiers[$i] . ";" . $system_hardware[$i] . ";" . $system_software[$i] . ";" . $system_date[$i]);

			if(!in_array($this_hash, $systems_hash))
			{
				$use_id = pts_request_new_id();
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

?>
