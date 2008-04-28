<?php

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
	$new_system_hardware = $new_xml_reader->getXMLArrayValues("PhoronixTestSuite/System/Hardware");
	$new_system_software = $new_xml_reader->getXMLArrayValues("PhoronixTestSuite/System/Software");
	$new_system_author = $new_xml_reader->getXMLArrayValues("PhoronixTestSuite/System/Author");
	$new_system_notes = $new_xml_reader->getXMLArrayValues("PhoronixTestSuite/System/TestNotes");
	$new_system_date = $new_xml_reader->getXMLArrayValues("PhoronixTestSuite/System/TestDate");
	$new_pts_version = $new_xml_reader->getXMLArrayValues("PhoronixTestSuite/System/Version");
	$new_associated_identifiers = $new_xml_reader->getXMLArrayValues("PhoronixTestSuite/System/AssociatedIdentifiers");
	$new_results_raw = $new_xml_reader->getXMLArrayValues("PhoronixTestSuite/Benchmark/Results");

	$new_suite_name = $new_xml_reader->getXMLValue("PhoronixTestSuite/Suite/Name");
	$new_suite_version = $new_xml_reader->getXMLValue("PhoronixTestSuite/Suite/Version");
	$new_suite_title = $new_xml_reader->getXMLValue("PhoronixTestSuite/Suite/Title");
	$new_suite_description = $new_xml_reader->getXMLValue("PhoronixTestSuite/Suite/Description");
	$new_suite_type = $new_xml_reader->getXMLValue("PhoronixTestSuite/Suite/Type");
	$new_suite_maintainer = $new_xml_reader->getXMLValue("PhoronixTestSuite/Suite/Maintainer");

	$new_results_version = $new_xml_reader->getXMLArrayValues("PhoronixTestSuite/Benchmark/Version");
	$new_results_testname = $new_xml_reader->getXMLArrayValues("PhoronixTestSuite/Benchmark/TestName");
	$new_results_arguments = $new_xml_reader->getXMLArrayValues("PhoronixTestSuite/Benchmark/TestArguments");

	$new_results_identifiers = array();
	$new_results_values = array();

	foreach($new_results_raw as $new_result_raw)
	{
		$new_xml_results = new tandem_XmlReader($new_result_raw);
		array_push($new_results_identifiers, $new_xml_results->getXMLArrayValues("Group/Entry/Identifier"));
		array_push($new_results_values, $new_xml_results->getXMLArrayValues("Group/Entry/Value"));
	}
	unset($NEW_RESULTS, $new_xml_reader, $new_results_raw);


	// READ ORIGINAL RESULTS
	$original_xml_reader = new tandem_XmlReader($OLD_RESULTS);
	$original_system_hardware = $original_xml_reader->getXMLArrayValues("PhoronixTestSuite/System/Hardware");
	$original_system_software = $original_xml_reader->getXMLArrayValues("PhoronixTestSuite/System/Software");
	$original_system_author = $original_xml_reader->getXMLArrayValues("PhoronixTestSuite/System/Author");
	$original_system_notes = $original_xml_reader->getXMLArrayValues("PhoronixTestSuite/System/TestNotes");
	$original_system_date = $original_xml_reader->getXMLArrayValues("PhoronixTestSuite/System/TestDate");
	$original_pts_version = $original_xml_reader->getXMLArrayValues("PhoronixTestSuite/System/Version");
	$original_associated_identifiers = $original_xml_reader->getXMLArrayValues("PhoronixTestSuite/System/AssociatedIdentifiers");
	$original_results_raw = $original_xml_reader->getXMLArrayValues("PhoronixTestSuite/Benchmark/Results");

	$original_suite_name = $original_xml_reader->getXMLValue("PhoronixTestSuite/Suite/Name");
	$original_suite_version = $original_xml_reader->getXMLValue("PhoronixTestSuite/Suite/Version");

	$original_results_name = $original_xml_reader->getXMLArrayValues("PhoronixTestSuite/Benchmark/Name");
	$original_results_version = $original_xml_reader->getXMLArrayValues("PhoronixTestSuite/Benchmark/Version");
	$original_results_attributes = $original_xml_reader->getXMLArrayValues("PhoronixTestSuite/Benchmark/Attributes");
	$original_results_scale = $original_xml_reader->getXMLArrayValues("PhoronixTestSuite/Benchmark/Scale");
	$original_results_testname = $original_xml_reader->getXMLArrayValues("PhoronixTestSuite/Benchmark/TestName");
	$original_results_arguments = $original_xml_reader->getXMLArrayValues("PhoronixTestSuite/Benchmark/TestArguments");
	$original_results_proportion = $original_xml_reader->getXMLArrayValues("PhoronixTestSuite/Benchmark/Proportion");
	$original_results_result_format = $original_xml_reader->getXMLArrayValues("PhoronixTestSuite/Benchmark/ResultFormat");

	$original_results_identifiers = array();
	$original_results_values = array();

	foreach($original_results_raw as $original_result_raw)
	{
		$original_xml_results = new tandem_XmlReader($original_result_raw);
		array_push($original_results_identifiers, $original_xml_results->getXMLArrayValues("Group/Entry/Identifier"));
		array_push($original_results_values, $original_xml_results->getXMLArrayValues("Group/Entry/Value"));
	}
	unset($OLD_RESULTS, $original_xml_reader, $original_results_raw);

	if(!defined("GLOBAL_COMPARISON"))
	{
		if($original_suite_name != $new_suite_name)
		{
			pts_exit("Merge Failed! The test(s) don't match: $original_suite_name - $new_suite_name\n");
		}
		if($original_suite_version != $new_suite_version)
		{
			pts_exit("Merge Failed! The test versions don't match: $original_suite_version - $new_suite_version\n");
		}
	}

	// Write the new merge

	$RESULTS = new tandem_XmlWriter();

	$RESULTS->setXslBinding("pts-results-viewer.xsl");

	$RESULTS->addXmlObject("PhoronixTestSuite/Suite/Title", 0, $new_suite_title);
	$RESULTS->addXmlObject("PhoronixTestSuite/Suite/Name", 0, $new_suite_name);
	$RESULTS->addXmlObject("PhoronixTestSuite/Suite/Version", 0, $new_suite_version);
	$RESULTS->addXmlObject("PhoronixTestSuite/Suite/Description", 0, $new_suite_description);
	$RESULTS->addXmlObject("PhoronixTestSuite/Suite/Type", 0, $new_suite_type);
	$RESULTS->addXmlObject("PhoronixTestSuite/Suite/Maintainer", 0, $new_suite_maintainer);

	// Same hardware and software?
	
	if(count($original_system_hardware) == 1 && count($new_system_hardware) == 1 && $original_system_hardware[0] == $new_system_hardware[0] && $original_system_software[0] == $new_system_software[0] && $original_pts_version[0] == $new_pts_version[0] && $original_system_notes[0] == $new_system_notes[0])
	{
		$USE_ID = pts_request_new_id();
		$RESULTS->addXmlObject("PhoronixTestSuite/System/Hardware", $USE_ID, $original_system_hardware[0]);
		$RESULTS->addXmlObject("PhoronixTestSuite/System/Software", $USE_ID, $original_system_software[0]);
		$RESULTS->addXmlObject("PhoronixTestSuite/System/Author", $USE_ID, $original_system_author[0]);
		$RESULTS->addXmlObject("PhoronixTestSuite/System/TestDate", $USE_ID, date("F j, Y h:i A"));
		$RESULTS->addXmlObject("PhoronixTestSuite/System/TestNotes", $USE_ID, $original_system_notes[0]);
		$RESULTS->addXmlObject("PhoronixTestSuite/System/Version", $USE_ID, $original_pts_version[0]);
		$RESULTS->addXmlObject("PhoronixTestSuite/System/AssociatedIdentifiers", $USE_ID, $original_associated_identifiers[0] . ", " . $new_associated_identifiers[0]);
	}
	else
	{
		if($original_pts_version[0] != $new_pts_version[0]) // TODO: add checks to scan entire pts_version arrays
			echo "PTS Versions Do Not Match! For accurate results, you should only test against the same version.";

		for($i = 0; $i < count($original_system_hardware); $i++)
		{
			$USE_ID = pts_request_new_id();
			$RESULTS->addXmlObject("PhoronixTestSuite/System/Hardware", $USE_ID, $original_system_hardware[$i]);
			$RESULTS->addXmlObject("PhoronixTestSuite/System/Software", $USE_ID, $original_system_software[$i]);
			$RESULTS->addXmlObject("PhoronixTestSuite/System/Author", $USE_ID, $original_system_author[$i]);
			$RESULTS->addXmlObject("PhoronixTestSuite/System/TestDate", $USE_ID, $original_system_date[$i]);
			$RESULTS->addXmlObject("PhoronixTestSuite/System/TestNotes", $USE_ID, $original_system_notes[$i]);
			$RESULTS->addXmlObject("PhoronixTestSuite/System/Version", $USE_ID, $original_pts_version[$i]);
			$RESULTS->addXmlObject("PhoronixTestSuite/System/AssociatedIdentifiers", $USE_ID, $original_associated_identifiers[$i]);
		}
		for($i = 0; $i < count($new_system_hardware); $i++)
		{
			$USE_ID = pts_request_new_id();
			$RESULTS->addXmlObject("PhoronixTestSuite/System/Hardware", $USE_ID, $new_system_hardware[$i]);
			$RESULTS->addXmlObject("PhoronixTestSuite/System/Software", $USE_ID, $new_system_software[$i]);
			$RESULTS->addXmlObject("PhoronixTestSuite/System/Author", $USE_ID, $new_system_author[$i]);
			$RESULTS->addXmlObject("PhoronixTestSuite/System/TestDate", $USE_ID, $new_system_date[$i]);
			$RESULTS->addXmlObject("PhoronixTestSuite/System/TestNotes", $USE_ID, $new_system_notes[$i]);
			$RESULTS->addXmlObject("PhoronixTestSuite/System/Version", $USE_ID, $new_pts_version[$i]);
			$RESULTS->addXmlObject("PhoronixTestSuite/System/AssociatedIdentifiers", $USE_ID, $new_associated_identifiers[$i]);
		}
	}

	for($b = 0; $b < count($original_results_identifiers); $b++)
	{
		$USE_ID = pts_request_new_id();
		$RESULTS->addXmlObject("PhoronixTestSuite/Benchmark/Name", $USE_ID, $original_results_name[$b]);
		$RESULTS->addXmlObject("PhoronixTestSuite/Benchmark/Version", $USE_ID, $original_results_version[$b]);
		$RESULTS->addXmlObject("PhoronixTestSuite/Benchmark/Attributes", $USE_ID, $original_results_attributes[$b]);
		$RESULTS->addXmlObject("PhoronixTestSuite/Benchmark/Scale", $USE_ID, $original_results_scale[$b]);
		$RESULTS->addXmlObject("PhoronixTestSuite/Benchmark/Proportion", $USE_ID, $original_results_proportion[$b]);
		$RESULTS->addXmlObject("PhoronixTestSuite/Benchmark/TestName", $USE_ID, $original_results_testname[$b]);
		$RESULTS->addXmlObject("PhoronixTestSuite/Benchmark/TestArguments", $USE_ID, $original_results_arguments[$b]);
		$RESULTS->addXmlObject("PhoronixTestSuite/Benchmark/ResultFormat", $USE_ID, $original_results_result_format[$b]);

		for($o = 0; $o < count($original_results_identifiers[$b]); $o++)
		{
			$RESULTS->addXmlObject("PhoronixTestSuite/Benchmark/Results/Group/Entry/Identifier", $USE_ID, $original_results_identifiers[$b][$o], 5, "o-$b-$o");
			$RESULTS->addXmlObject("PhoronixTestSuite/Benchmark/Results/Group/Entry/Value", $USE_ID, $original_results_values[$b][$o], 5, "o-$b-$o");
		}


		if($original_results_testname[$b] == $new_results_testname[$b] && $original_results_arguments[$b] == $new_results_arguments[$b] && $original_results_version[$b] == $new_results_version[$b])
			for($o = 0; $o < count($new_results_identifiers[$b]); $o++)
			{
				$RESULTS->addXmlObject("PhoronixTestSuite/Benchmark/Results/Group/Entry/Identifier", $USE_ID, $new_results_identifiers[$b][$o], 5, "n-$b-$o");
				$RESULTS->addXmlObject("PhoronixTestSuite/Benchmark/Results/Group/Entry/Value", $USE_ID, $new_results_values[$b][$o], 5, "n-$b-$o");
			}
	}	

	return $RESULTS->getXML();
}

?>
