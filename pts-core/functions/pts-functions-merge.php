<?php

/*
	Phoronix Test Suite "Trondheim"
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts-functions-merge.php: Functions needed to merge test results.
*/

function pts_find_file($file)
{
	if(is_file($file))
		$USE_FILE = $file;
	else if(is_file(SAVE_RESULTS_DIR . $file . "/composite.xml"))
		$USE_FILE = SAVE_RESULTS_DIR . $file . "/composite.xml";
	else if(trim(@file_get_contents("http://www.phoronix-test-suite.com/global/profile-check.php?id=$file")) == "REMOTE_FILE")
		$USE_FILE = "http://www.phoronix-test-suite.com/global/pts-results-viewer.php?id=$file";
	else
	{
		pts_exit("File: " . $file . " couldn't be found. Exiting...");
	}

	return $USE_FILE;
}
function pts_merge_benchmarks($OLD_RESULTS, $NEW_RESULTS)
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
	$new_suite_type = $new_xml_reader->getXMLValue(P_RESULTS_SUITE_TYPE);
	$new_suite_maintainer = $new_xml_reader->getXMLValue(P_RESULTS_SUITE_MAINTAINER);

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

	if(!defined("GLOBAL_COMPARISON"))
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
	$RESULTS->addXmlObject(P_RESULTS_SUITE_MAINTAINER, 0, $new_suite_maintainer);

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

?>
