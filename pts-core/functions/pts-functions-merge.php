<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts-functions-merge.php: Functions needed to merge test results.

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

function pts_find_file($file)
{
	if(is_file($file))
		$USE_FILE = $file;
	else if(is_file(SAVE_RESULTS_DIR . $file . "/composite.xml"))
		$USE_FILE = SAVE_RESULTS_DIR . $file . "/composite.xml";
	else if(trim(@file_get_contents("http://www.phoronix-test-suite.com/global/profile-check.php?id=" . $file)) == "REMOTE_FILE")
		$USE_FILE = "http://www.phoronix-test-suite.com/global/pts-results-viewer.php?id=" . $file;
	else
	{
		pts_exit("File: " . $file . " couldn't be found. Exiting...");
	}

	return $USE_FILE;
}
function pts_merge_test_results($OLD_RESULTS, $NEW_RESULTS)
{
	// RE-READ LATEST RESULTS
	$new_xml_reader = new tandem_XmlReader($NEW_RESULTS);
	$new_system_hardware = $new_xml_reader->getXMLArrayValues(P_RESULTS_SYSTEM_HARDWARE);
	$new_system_software = $new_xml_reader->getXMLArrayValues(P_RESULTS_SYSTEM_SOFTWARE);
	$new_system_author = $new_xml_reader->getXMLArrayValues(P_RESULTS_SYSTEM_AUTHOR);
	$new_system_notes = $new_xml_reader->getXMLArrayValues(P_RESULTS_SYSTEM_NOTES);
	$new_system_date = $new_xml_reader->getXMLArrayValues(P_RESULTS_SYSTEM_DATE);
	$new_pts_version = $new_xml_reader->getXMLArrayValues(P_RESULTS_SYSTEM_PTSVERSION);
	$new_associated_identifiers = $new_xml_reader->getXMLArrayValues(P_RESULTS_SYSTEM_IDENTIFIERS);
	$new_results_raw = $new_xml_reader->getXMLArrayValues(P_RESULTS_RESULTS_GROUP);

	$new_suite_name = $new_xml_reader->getXMLValue(P_RESULTS_SUITE_NAME);
	$new_suite_version = $new_xml_reader->getXMLValue(P_RESULTS_SUITE_VERSION);
	$new_suite_title = $new_xml_reader->getXMLValue(P_RESULTS_SUITE_TITLE);
	$new_suite_description = $new_xml_reader->getXMLValue(P_RESULTS_SUITE_DESCRIPTION);
	$new_suite_extensions = $new_xml_reader->getXMLValue(P_RESULTS_SUITE_EXTENSIONS);
	$new_suite_properties = $new_xml_reader->getXMLValue(P_RESULTS_SUITE_PROPERTIES);
	$new_suite_type = $new_xml_reader->getXMLValue(P_RESULTS_SUITE_TYPE);

	$new_results_name = $new_xml_reader->getXMLArrayValues(P_RESULTS_TEST_TITLE);
	$new_results_version = $new_xml_reader->getXMLArrayValues(P_RESULTS_TEST_VERSION);
	$new_results_attributes = $new_xml_reader->getXMLArrayValues(P_RESULTS_TEST_ATTRIBUTES);
	$new_results_scale = $new_xml_reader->getXMLArrayValues(P_RESULTS_TEST_SCALE);
	$new_results_testname = $new_xml_reader->getXMLArrayValues(P_RESULTS_TEST_TESTNAME);
	$new_results_arguments = $new_xml_reader->getXMLArrayValues(P_RESULTS_TEST_ARGUMENTS);
	$new_results_proportion = $new_xml_reader->getXMLArrayValues(P_RESULTS_TEST_PROPORTION);
	$new_results_result_format = $new_xml_reader->getXMLArrayValues(P_RESULTS_TEST_RESULTFORMAT);

	$new_results_identifiers = array();
	$new_results_values = array();

	foreach($new_results_raw as $new_result_raw)
	{
		$new_xml_results = new tandem_XmlReader($new_result_raw);
		array_push($new_results_identifiers, $new_xml_results->getXMLArrayValues(S_RESULTS_RESULTS_GROUP_IDENTIFIER));
		array_push($new_results_values, $new_xml_results->getXMLArrayValues(S_RESULTS_RESULTS_GROUP_VALUE));
	}
	unset($NEW_RESULTS, $new_xml_reader, $new_results_raw);


	// READ ORIGINAL RESULTS
	$original_xml_reader = new tandem_XmlReader($OLD_RESULTS);
	$original_system_hardware = $original_xml_reader->getXMLArrayValues(P_RESULTS_SYSTEM_HARDWARE);
	$original_system_software = $original_xml_reader->getXMLArrayValues(P_RESULTS_SYSTEM_SOFTWARE);
	$original_system_author = $original_xml_reader->getXMLArrayValues(P_RESULTS_SYSTEM_AUTHOR);
	$original_system_notes = $original_xml_reader->getXMLArrayValues(P_RESULTS_SYSTEM_NOTES);
	$original_system_date = $original_xml_reader->getXMLArrayValues(P_RESULTS_SYSTEM_DATE);
	$original_pts_version = $original_xml_reader->getXMLArrayValues(P_RESULTS_SYSTEM_PTSVERSION);
	$original_associated_identifiers = $original_xml_reader->getXMLArrayValues(P_RESULTS_SYSTEM_IDENTIFIERS);
	$original_results_raw = $original_xml_reader->getXMLArrayValues(P_RESULTS_RESULTS_GROUP);

	$original_suite_name = $original_xml_reader->getXMLValue(P_RESULTS_SUITE_NAME);
	$original_suite_version = $original_xml_reader->getXMLValue(P_RESULTS_SUITE_VERSION);

	$original_results_name = $original_xml_reader->getXMLArrayValues(P_RESULTS_TEST_TITLE);
	$original_results_version = $original_xml_reader->getXMLArrayValues(P_RESULTS_TEST_VERSION);
	$original_results_attributes = $original_xml_reader->getXMLArrayValues(P_RESULTS_TEST_ATTRIBUTES);
	$original_results_scale = $original_xml_reader->getXMLArrayValues(P_RESULTS_TEST_SCALE);
	$original_results_testname = $original_xml_reader->getXMLArrayValues(P_RESULTS_TEST_TESTNAME);
	$original_results_arguments = $original_xml_reader->getXMLArrayValues(P_RESULTS_TEST_ARGUMENTS);
	$original_results_proportion = $original_xml_reader->getXMLArrayValues(P_RESULTS_TEST_PROPORTION);
	$original_results_result_format = $original_xml_reader->getXMLArrayValues(P_RESULTS_TEST_RESULTFORMAT);

	$original_results_identifiers = array();
	$original_results_values = array();

	foreach($original_results_raw as $original_result_raw)
	{
		$original_xml_results = new tandem_XmlReader($original_result_raw);
		array_push($original_results_identifiers, $original_xml_results->getXMLArrayValues(S_RESULTS_RESULTS_GROUP_IDENTIFIER));
		array_push($original_results_values, $original_xml_results->getXMLArrayValues(S_RESULTS_RESULTS_GROUP_VALUE));
	}
	unset($OLD_RESULTS, $original_xml_reader, $original_results_raw);

	if(!defined("GLOBAL_COMPARISON") && getenv("PTS_MERGE") != "custom")
	{
		if($original_suite_name != $new_suite_name)
		{
			echo pts_string_header("Note: The test(s) don't match: " . $original_suite_name . " - " . $new_suite_name . ".\nNot all test results may be compatible.");
		}
		if($original_suite_version != $new_suite_version)
		{
			//pts_exit("Merge Failed! The test versions don't match: $original_suite_version - $new_suite_version\n");
		}
	}

	// Write the new merge

	$RESULTS = new tandem_XmlWriter();

	$RESULTS->setXslBinding("pts-results-viewer.xsl");

	$RESULTS->addXmlObject(P_RESULTS_SUITE_TITLE, 0, $new_suite_title);
	$RESULTS->addXmlObject(P_RESULTS_SUITE_NAME, 0, $new_suite_name);
	$RESULTS->addXmlObject(P_RESULTS_SUITE_VERSION, 0, $new_suite_version);
	$RESULTS->addXmlObject(P_RESULTS_SUITE_DESCRIPTION, 0, $new_suite_description);
	$RESULTS->addXmlObject(P_RESULTS_SUITE_TYPE, 0, $new_suite_type);
	$RESULTS->addXmlObject(P_RESULTS_SUITE_EXTENSIONS, 0, $new_suite_extensions);
	$RESULTS->addXmlObject(P_RESULTS_SUITE_PROPERTIES, 0, $new_suite_properties);

	// Same hardware and software?
	
	if(count($original_system_hardware) == 1 && count($new_system_hardware) == 1 && $original_system_hardware[0] == $new_system_hardware[0] && $original_system_software[0] == $new_system_software[0] && $original_pts_version[0] == $new_pts_version[0] && $original_system_notes[0] == $new_system_notes[0])
	{
		$USE_ID = pts_request_new_id();
		$RESULTS->addXmlObject(P_RESULTS_SYSTEM_HARDWARE, $USE_ID, $original_system_hardware[0]);
		$RESULTS->addXmlObject(P_RESULTS_SYSTEM_SOFTWARE, $USE_ID, $original_system_software[0]);
		$RESULTS->addXmlObject(P_RESULTS_SYSTEM_AUTHOR, $USE_ID, $original_system_author[0]);
		$RESULTS->addXmlObject(P_RESULTS_SYSTEM_DATE, $USE_ID, date("F j, Y h:i A"));
		$RESULTS->addXmlObject(P_RESULTS_SYSTEM_NOTES, $USE_ID, $original_system_notes[0]);
		$RESULTS->addXmlObject(P_RESULTS_SYSTEM_PTSVERSION, $USE_ID, $original_pts_version[0]);
		$RESULTS->addXmlObject(P_RESULTS_SYSTEM_IDENTIFIERS, $USE_ID, $original_associated_identifiers[0] . ", " . $new_associated_identifiers[0]);
	}
	else
	{
		if(!pts_version_comparable($original_pts_version[0], $new_pts_version[0]))
			echo pts_string_header("PTS Versions Do Not Match! For accurate results, you should only test against the same version!");

		for($i = 0; $i < count($original_system_hardware); $i++)
		{
			$USE_ID = pts_request_new_id();
			$RESULTS->addXmlObject(P_RESULTS_SYSTEM_HARDWARE, $USE_ID, $original_system_hardware[$i]);
			$RESULTS->addXmlObject(P_RESULTS_SYSTEM_SOFTWARE, $USE_ID, $original_system_software[$i]);
			$RESULTS->addXmlObject(P_RESULTS_SYSTEM_AUTHOR, $USE_ID, $original_system_author[$i]);
			$RESULTS->addXmlObject(P_RESULTS_SYSTEM_DATE, $USE_ID, $original_system_date[$i]);
			$RESULTS->addXmlObject(P_RESULTS_SYSTEM_NOTES, $USE_ID, $original_system_notes[$i]);
			$RESULTS->addXmlObject(P_RESULTS_SYSTEM_PTSVERSION, $USE_ID, $original_pts_version[$i]);
			$RESULTS->addXmlObject(P_RESULTS_SYSTEM_IDENTIFIERS, $USE_ID, $original_associated_identifiers[$i]);
		}
		for($i = 0; $i < count($new_system_hardware); $i++)
		{
			$USE_ID = pts_request_new_id();
			$RESULTS->addXmlObject(P_RESULTS_SYSTEM_HARDWARE, $USE_ID, $new_system_hardware[$i]);
			$RESULTS->addXmlObject(P_RESULTS_SYSTEM_SOFTWARE, $USE_ID, $new_system_software[$i]);
			$RESULTS->addXmlObject(P_RESULTS_SYSTEM_AUTHOR, $USE_ID, $new_system_author[$i]);
			$RESULTS->addXmlObject(P_RESULTS_SYSTEM_DATE, $USE_ID, $new_system_date[$i]);
			$RESULTS->addXmlObject(P_RESULTS_SYSTEM_NOTES, $USE_ID, $new_system_notes[$i]);
			$RESULTS->addXmlObject(P_RESULTS_SYSTEM_PTSVERSION, $USE_ID, $new_pts_version[$i]);
			$RESULTS->addXmlObject(P_RESULTS_SYSTEM_IDENTIFIERS, $USE_ID, $new_associated_identifiers[$i]);
		}
	}

	// Merge Results
	$merge_count = 0;
	for($r_o = 0; $r_o < count($original_results_identifiers); $r_o++)
	{
		$result_merged = false;
		for($r_n = 0; $r_n < count($new_results_identifiers) && !$result_merged; $r_n++)
		{
			if(!empty($original_results_identifiers[$r_o]) && !empty($new_results_identifiers[$r_n]) && $original_results_testname[$r_o] == $new_results_testname[$r_n] && $original_results_arguments[$r_o] == $new_results_arguments[$r_n] && pts_version_comparable($original_results_version[$r_o], $new_results_version[$r_n]))
			{
				$USE_ID = pts_request_new_id();
				$RESULTS->addXmlObject(P_RESULTS_TEST_TITLE, $USE_ID, $original_results_name[$r_o]);
				$RESULTS->addXmlObject(P_RESULTS_TEST_VERSION, $USE_ID, $original_results_version[$r_o]);
				$RESULTS->addXmlObject(P_RESULTS_TEST_ATTRIBUTES, $USE_ID, $original_results_attributes[$r_o]);
				$RESULTS->addXmlObject(P_RESULTS_TEST_SCALE, $USE_ID, $original_results_scale[$r_o]);
				$RESULTS->addXmlObject(P_RESULTS_TEST_PROPORTION, $USE_ID, $original_results_proportion[$r_o]);
				$RESULTS->addXmlObject(P_RESULTS_TEST_TESTNAME, $USE_ID, $original_results_testname[$r_o]);
				$RESULTS->addXmlObject(P_RESULTS_TEST_ARGUMENTS, $USE_ID, $original_results_arguments[$r_o]);
				$RESULTS->addXmlObject(P_RESULTS_TEST_RESULTFORMAT, $USE_ID, $original_results_result_format[$r_o]);

				for($o = 0; $o < count($original_results_identifiers[$r_o]); $o++)
				{
					$RESULTS->addXmlObject(P_RESULTS_RESULTS_GROUP_IDENTIFIER, $USE_ID, $original_results_identifiers[$r_o][$o], 5, "o-$r_o-$o");
					$RESULTS->addXmlObject(P_RESULTS_RESULTS_GROUP_VALUE, $USE_ID, $original_results_values[$r_o][$o], 5, "o-$r_o-$o");
				}
				for($o = 0; $o < count($new_results_identifiers[$r_n]); $o++)
				{
					$RESULTS->addXmlObject(P_RESULTS_RESULTS_GROUP_IDENTIFIER, $USE_ID, $new_results_identifiers[$r_n][$o], 5, "n-$r_n-$o");
					$RESULTS->addXmlObject(P_RESULTS_RESULTS_GROUP_VALUE, $USE_ID, $new_results_values[$r_n][$o], 5, "n-$r_n-$o");
				}

				$original_results_identifiers[$r_o] = "";
				$new_results_identifiers[$r_n] = "";
				$result_merged = true;
				$merge_count++;
			}
		}
	}

	// Add other results to bottom
	for($r_o = 0; $r_o < count($original_results_identifiers); $r_o++)
	{
		if(!empty($original_results_identifiers[$r_o]))
		{
			$USE_ID = pts_request_new_id();
			$RESULTS->addXmlObject(P_RESULTS_TEST_TITLE, $USE_ID, $original_results_name[$r_o]);
			$RESULTS->addXmlObject(P_RESULTS_TEST_VERSION, $USE_ID, $original_results_version[$r_o]);
			$RESULTS->addXmlObject(P_RESULTS_TEST_ATTRIBUTES, $USE_ID, $original_results_attributes[$r_o]);
			$RESULTS->addXmlObject(P_RESULTS_TEST_SCALE, $USE_ID, $original_results_scale[$r_o]);
			$RESULTS->addXmlObject(P_RESULTS_TEST_PROPORTION, $USE_ID, $original_results_proportion[$r_o]);
			$RESULTS->addXmlObject(P_RESULTS_TEST_TESTNAME, $USE_ID, $original_results_testname[$r_o]);
			$RESULTS->addXmlObject(P_RESULTS_TEST_ARGUMENTS, $USE_ID, $original_results_arguments[$r_o]);
			$RESULTS->addXmlObject(P_RESULTS_TEST_RESULTFORMAT, $USE_ID, $original_results_result_format[$r_o]);

			for($o = 0; $o < count($original_results_identifiers[$r_o]); $o++)
			{
				$RESULTS->addXmlObject(P_RESULTS_RESULTS_GROUP_IDENTIFIER, $USE_ID, $original_results_identifiers[$r_o][$o], 5, "o-$r_o-$o-s");
				$RESULTS->addXmlObject(P_RESULTS_RESULTS_GROUP_VALUE, $USE_ID, $original_results_values[$r_o][$o], 5, "o-$r_o-$o-s");
			}

			$original_results_identifiers[$r_o] = "";
		}
	}
	for($r_n = 0; $r_n < count($new_results_identifiers); $r_n++)
	{
		if(!empty($new_results_identifiers[$r_n]))
		{
			$USE_ID = pts_request_new_id();
			$RESULTS->addXmlObject(P_RESULTS_TEST_TITLE, $USE_ID, $new_results_name[$r_n]);
			$RESULTS->addXmlObject(P_RESULTS_TEST_VERSION, $USE_ID, $new_results_version[$r_n]);
			$RESULTS->addXmlObject(P_RESULTS_TEST_ATTRIBUTES, $USE_ID, $new_results_attributes[$r_n]);
			$RESULTS->addXmlObject(P_RESULTS_TEST_SCALE, $USE_ID, $new_results_scale[$r_n]);
			$RESULTS->addXmlObject(P_RESULTS_TEST_PROPORTION, $USE_ID, $new_results_proportion[$r_n]);
			$RESULTS->addXmlObject(P_RESULTS_TEST_TESTNAME, $USE_ID, $new_results_testname[$r_n]);
			$RESULTS->addXmlObject(P_RESULTS_TEST_ARGUMENTS, $USE_ID, $new_results_arguments[$r_n]);
			$RESULTS->addXmlObject(P_RESULTS_TEST_RESULTFORMAT, $USE_ID, $new_results_result_format[$r_n]);

			for($o = 0; $o < count($new_results_identifiers[$r_n]); $o++)
			{
				$RESULTS->addXmlObject(P_RESULTS_RESULTS_GROUP_IDENTIFIER, $USE_ID, $new_results_identifiers[$r_n][$o], 5, "n-$r_n-$o-s");
				$RESULTS->addXmlObject(P_RESULTS_RESULTS_GROUP_VALUE, $USE_ID, $new_results_values[$r_n][$o], 5, "n-$r_n-$o-s");
			}

			$new_results_identifiers[$r_n] = "";
		}
	}

	return $RESULTS->getXML();
}
function pts_merge_batch_tests_to_line_comparison($RESULT)
{
	// RE-READ LATEST RESULTS
	$xml_reader = new tandem_XmlReader($RESULT);
	$system_hardware = $xml_reader->getXMLArrayValues(P_RESULTS_SYSTEM_HARDWARE);
	$system_software = $xml_reader->getXMLArrayValues(P_RESULTS_SYSTEM_SOFTWARE);
	$system_author = $xml_reader->getXMLArrayValues(P_RESULTS_SYSTEM_AUTHOR);
	$system_notes = $xml_reader->getXMLArrayValues(P_RESULTS_SYSTEM_NOTES);
	$system_date = $xml_reader->getXMLArrayValues(P_RESULTS_SYSTEM_DATE);
	$pts_version = $xml_reader->getXMLArrayValues(P_RESULTS_SYSTEM_PTSVERSION);
	$associated_identifiers = $xml_reader->getXMLArrayValues(P_RESULTS_SYSTEM_IDENTIFIERS);
	$results_raw = $xml_reader->getXMLArrayValues(P_RESULTS_RESULTS_GROUP);

	$suite_name = $xml_reader->getXMLValue(P_RESULTS_SUITE_NAME);
	$suite_version = $xml_reader->getXMLValue(P_RESULTS_SUITE_VERSION);
	$suite_title = $xml_reader->getXMLValue(P_RESULTS_SUITE_TITLE);
	$suite_description = $xml_reader->getXMLValue(P_RESULTS_SUITE_DESCRIPTION);
	$suite_extensions = $xml_reader->getXMLValue(P_RESULTS_SUITE_EXTENSIONS);
	$suite_properties = $xml_reader->getXMLValue(P_RESULTS_SUITE_PROPERTIES);
	$suite_type = $xml_reader->getXMLValue(P_RESULTS_SUITE_TYPE);

	$results_name = $xml_reader->getXMLArrayValues(P_RESULTS_TEST_TITLE);
	$results_version = $xml_reader->getXMLArrayValues(P_RESULTS_TEST_VERSION);
	$results_attributes = $xml_reader->getXMLArrayValues(P_RESULTS_TEST_ATTRIBUTES);
	$results_scale = $xml_reader->getXMLArrayValues(P_RESULTS_TEST_SCALE);
	$results_testname = $xml_reader->getXMLArrayValues(P_RESULTS_TEST_TESTNAME);
	$results_arguments = $xml_reader->getXMLArrayValues(P_RESULTS_TEST_ARGUMENTS);
	$results_proportion = $xml_reader->getXMLArrayValues(P_RESULTS_TEST_PROPORTION);
	$results_result_format = $xml_reader->getXMLArrayValues(P_RESULTS_TEST_RESULTFORMAT);

	$results_identifiers = array();
	$results_values = array();

	foreach($results_raw as $result_raw)
	{
		$xml_results = new tandem_XmlReader($result_raw);
		array_push($results_identifiers, $xml_results->getXMLArrayValues(S_RESULTS_RESULTS_GROUP_IDENTIFIER));
		array_push($results_values, $xml_results->getXMLArrayValues(S_RESULTS_RESULTS_GROUP_VALUE));
	}

	// Some other work
	if(!empty($suite_properties))
		$suite_properties = explode(";", $suite_properties);
	else
		$suite_properties = array();

	if(!in_array("BATCH_LINE_ANALYSIS", $suite_properties)) // analysis type
		array_push($suite_properties, "BATCH_LINE_ANALYSIS");

	// Write the new merge

	$RESULTS = new tandem_XmlWriter();

	$RESULTS->setXslBinding("pts-results-viewer.xsl");

	$RESULTS->addXmlObject(P_RESULTS_SUITE_TITLE, 0, $suite_title);
	$RESULTS->addXmlObject(P_RESULTS_SUITE_NAME, 0, $suite_name);
	$RESULTS->addXmlObject(P_RESULTS_SUITE_VERSION, 0, $suite_version);
	$RESULTS->addXmlObject(P_RESULTS_SUITE_DESCRIPTION, 0, $suite_description);
	$RESULTS->addXmlObject(P_RESULTS_SUITE_TYPE, 0, $suite_type);
	$RESULTS->addXmlObject(P_RESULTS_SUITE_EXTENSIONS, 0, $suite_extensions);
	$RESULTS->addXmlObject(P_RESULTS_SUITE_PROPERTIES, 0, implode(";", $suite_properties));

	// Write system information
	for($i = 0; $i < count($system_hardware); $i++)
	{
		$USE_ID = pts_request_new_id();
		$RESULTS->addXmlObject(P_RESULTS_SYSTEM_HARDWARE, $USE_ID, $system_hardware[$i]);
		$RESULTS->addXmlObject(P_RESULTS_SYSTEM_SOFTWARE, $USE_ID, $system_software[$i]);
		$RESULTS->addXmlObject(P_RESULTS_SYSTEM_AUTHOR, $USE_ID, $system_author[$i]);
		$RESULTS->addXmlObject(P_RESULTS_SYSTEM_DATE, $USE_ID, $system_date[$i]);
		$RESULTS->addXmlObject(P_RESULTS_SYSTEM_NOTES, $USE_ID, $system_notes[$i]);
		$RESULTS->addXmlObject(P_RESULTS_SYSTEM_PTSVERSION, $USE_ID, $pts_version[$i]);
		$RESULTS->addXmlObject(P_RESULTS_SYSTEM_IDENTIFIERS, $USE_ID, $associated_identifiers[$i]);
	}

	// Merge Results
	$merge_count = 0;
//	$merge_patterns = array();
	for($r_o = 0; $r_o < count($results_identifiers); $r_o++)
	{
		$has_merged = false;
		for($r_n = 0; $r_n < count($results_identifiers); $r_n++)
		{
			if($r_o != $r_n && !empty($results_testname[$r_o]) && $results_testname[$r_o] == $results_testname[$r_n] && $results_result_format[$r_o] == "BAR_GRAPH" && $results_result_format[$r_n] == "BAR_GRAPH")
			{
				$similar_attributes = array();
				$r_o_test_attributes = array_reverse(explode(" - ", $results_attributes[$r_o]));
				$r_n_test_attributes = array_reverse(explode(" - ", $results_attributes[$r_n]));

				for($i = 0; $i < count($r_o_test_attributes); $i++)
				{
					if(in_array($r_o_test_attributes[$i], $r_n_test_attributes))
					{
						array_push($similar_attributes, $r_o_test_attributes[$i]);

						$removed = false;
						for($j = 0; $j < count($r_n_test_attributes) && !$removed; $j++)
							if($r_o_test_attributes[$i] == $r_n_test_attributes[$j])
							{
								unset($r_n_test_attributes[$j]);
								$removed = true;
							}

						unset($r_o_test_attributes[$i]);
					}
				}

				if(count($r_o_test_attributes) == 1 && count($r_n_test_attributes) == 1)
				{
					if(!$has_merged)
					{
						$similar_attributes_text = implode(" - ", $similar_attributes);
						$test_attribute = array_pop($r_o_test_attributes);
						$r_o_test_attributes_1 = explode(":", $test_attribute);

						if(count($r_o_test_attributes_1) > 1)
							$similar_attributes_text = trim($r_o_test_attributes_1[0]) . " Analysis [" . $similar_attributes_text . "]";

						$USE_ID = pts_request_new_id();
						$RESULTS->addXmlObject(P_RESULTS_TEST_TITLE, $USE_ID, $results_name[$r_o]);
						$RESULTS->addXmlObject(P_RESULTS_TEST_VERSION, $USE_ID, $results_version[$r_o]);
						$RESULTS->addXmlObject(P_RESULTS_TEST_ATTRIBUTES, $USE_ID, $similar_attributes_text);
						$RESULTS->addXmlObject(P_RESULTS_TEST_SCALE, $USE_ID, $results_scale[$r_o]);
						$RESULTS->addXmlObject(P_RESULTS_TEST_PROPORTION, $USE_ID, $results_proportion[$r_o]);
						$RESULTS->addXmlObject(P_RESULTS_TEST_TESTNAME, $USE_ID, $results_testname[$r_o]);
						$RESULTS->addXmlObject(P_RESULTS_TEST_ARGUMENTS, $USE_ID, $results_arguments[$r_o]);
						$RESULTS->addXmlObject(P_RESULTS_TEST_RESULTFORMAT, $USE_ID, "LINE_GRAPH");

						for($o = 0; $o < count($results_identifiers[$r_o]); $o++)
						{
							$show_attribute = trim(array_pop(explode(":", $test_attribute)));
							$RESULTS->addXmlObject(P_RESULTS_RESULTS_GROUP_IDENTIFIER, $USE_ID, $show_attribute, 5, "o-$r_o-$o");
							$RESULTS->addXmlObject(P_RESULTS_RESULTS_GROUP_VALUE, $USE_ID, $results_values[$r_o][$o], 5, "o-$r_o-$o");
						}
					}

					for($o = 0; $o < count($results_identifiers[$r_n]); $o++)
					{
						$show_attribute = trim(array_pop(explode(":", array_pop($r_n_test_attributes))));
						$RESULTS->addXmlObject(P_RESULTS_RESULTS_GROUP_IDENTIFIER, $USE_ID, $show_attribute, 5, "n-$r_n-$o");
						$RESULTS->addXmlObject(P_RESULTS_RESULTS_GROUP_VALUE, $USE_ID, $results_values[$r_n][$o], 5, "n-$r_n-$o");
					}
					$results_testname[$r_n] = null;
					$has_merged = true;
					$merge_count++;
				}

			/*	if($r_n == (count($results_identifiers) - 1) && $has_merged)
				{
					// Reset counter and try again
					$has_merged = false;
					$r_n = 0;
				} */
			}
		}
		$results_testname[$r_o] = null;
	}

	return $RESULTS->getXML();
}

?>
