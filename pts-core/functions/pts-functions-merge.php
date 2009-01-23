<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
	pts-functions-merge.php: Functions needed to merge test result files

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

	for($merge_pos = 0; $merge_pos < count($files_to_combine); $merge_pos++)
	{
		$this_result_file = new pts_result_file($files_to_combine[$merge_pos]);

		if($merge_pos == 0)
		{
			$results->addXmlObject(P_RESULTS_SUITE_TITLE, 0, $this_result_file->get_suite_title());
			$results->addXmlObject(P_RESULTS_SUITE_NAME, 0, $this_result_file->get_suite_name());
			$results->addXmlObject(P_RESULTS_SUITE_VERSION, 0, $this_result_file->get_suite_version());
			$results->addXmlObject(P_RESULTS_SUITE_DESCRIPTION, 0, $this_result_file->get_suite_description());
			$results->addXmlObject(P_RESULTS_SUITE_TYPE, 0, $this_result_file->get_suite_type());
			$results->addXmlObject(P_RESULTS_SUITE_EXTENSIONS, 0, $this_result_file->get_suite_extensions());
			$results->addXmlObject(P_RESULTS_SUITE_PROPERTIES, 0, $this_result_file->get_suite_properties());
		}

		$system_hardware = $this_result_file->get_system_hardware();
		$system_software = $this_result_file->get_system_software();
		$system_author = $this_result_file->get_system_author();
		$system_date = $this_result_file->get_system_date();
		$pts_version = $this_result_file->get_system_pts_version();
		$system_notes = $this_result_file->get_system_notes();
		$associated_identifiers = $this_result_file->get_system_identifiers();

		// Write the system hardware/software information

		for($i = 0; $i < count($system_hardware); $i++)
		{
			$USE_ID = pts_request_new_id();
			$results->addXmlObject(P_RESULTS_SYSTEM_HARDWARE, $USE_ID, $system_hardware[$i]);
			$results->addXmlObject(P_RESULTS_SYSTEM_SOFTWARE, $USE_ID, $system_software[$i]);
			$results->addXmlObject(P_RESULTS_SYSTEM_AUTHOR, $USE_ID, $system_author[$i]);
			$results->addXmlObject(P_RESULTS_SYSTEM_DATE, $USE_ID, $system_date[$i]);
			$results->addXmlObject(P_RESULTS_SYSTEM_NOTES, $USE_ID, $system_notes[$i]);
			$results->addXmlObject(P_RESULTS_SYSTEM_PTSVERSION, $USE_ID, $pts_version[$i]);
			$results->addXmlObject(P_RESULTS_SYSTEM_IDENTIFIERS, $USE_ID, $associated_identifiers[$i]);
		}

		$test_result_manager->add_test_result_set($this_result_file->get_result_objects());
	}

	// Write the actual test results
	$results_added = 0;
	foreach($test_result_manager->get_results() as $result_object)
	{
		$USE_ID = pts_request_new_id();
		$results->addXmlObject(P_RESULTS_TEST_TITLE, $USE_ID, $result_object->get_name());
		$results->addXmlObject(P_RESULTS_TEST_VERSION, $USE_ID, $result_object->get_version());
		$results->addXmlObject(P_RESULTS_TEST_ATTRIBUTES, $USE_ID, $result_object->get_attributes());
		$results->addXmlObject(P_RESULTS_TEST_SCALE, $USE_ID, $result_object->get_scale());
		$results->addXmlObject(P_RESULTS_TEST_PROPORTION, $USE_ID, $result_object->get_proportion());
		$results->addXmlObject(P_RESULTS_TEST_TESTNAME, $USE_ID, $result_object->get_test_name());
		$results->addXmlObject(P_RESULTS_TEST_ARGUMENTS, $USE_ID, $result_object->get_arguments());
		$results->addXmlObject(P_RESULTS_TEST_RESULTFORMAT, $USE_ID, $result_object->get_format());

		$identifiers = $result_object->get_identifiers();
		$values = $result_object->get_values();
		$raw_values = $result_object->get_values();

		for($i = 0; $i < count($identifiers); $i++)
		{
			$results->addXmlObject(P_RESULTS_RESULTS_GROUP_IDENTIFIER, $USE_ID, $identifiers[$i], 5, "o-$i-$results_added-r");
			$results->addXmlObject(P_RESULTS_RESULTS_GROUP_VALUE, $USE_ID, $values[$i], 5, "o-$i-$results_added-r");
			$results->addXmlObject(P_RESULTS_RESULTS_GROUP_RAW, $USE_ID, $raw_values[$i], 5, "o-$i-$results_added-r");
		}
		$results_added++;
	}

	return $results->getXML();
}

?>
