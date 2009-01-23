<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
	pts-includes-analytical.php: Functions to analyze test results

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

function pts_merge_batch_tests_to_line_comparison($RESULT)
{
	// Perform analyze line comparison

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
	$results_rawvalues = array();

	foreach($results_raw as $result_raw)
	{
		$xml_results = new tandem_XmlReader($result_raw);
		array_push($results_identifiers, $xml_results->getXMLArrayValues(S_RESULTS_RESULTS_GROUP_IDENTIFIER));
		array_push($results_values, $xml_results->getXMLArrayValues(S_RESULTS_RESULTS_GROUP_VALUE));
		array_push($results_rawvalues, $xml_results->getXMLArrayValues(S_RESULTS_RESULTS_GROUP_RAW));
	}

	// Some other work
	if(!empty($suite_properties))
	{
		$suite_properties = explode(";", $suite_properties);
	}
	else
	{
		$suite_properties = array();
	}

	if(!in_array("BATCH_LINE_ANALYSIS", $suite_properties)) // analysis type
	{
		array_push($suite_properties, "BATCH_LINE_ANALYSIS");
	}

	// Write the new merge

	$results = new tandem_XmlWriter();

	$results->setXslBinding("pts-results-viewer.xsl");

	$results->addXmlObject(P_RESULTS_SUITE_TITLE, 0, $suite_title);
	$results->addXmlObject(P_RESULTS_SUITE_NAME, 0, $suite_name);
	$results->addXmlObject(P_RESULTS_SUITE_VERSION, 0, $suite_version);
	$results->addXmlObject(P_RESULTS_SUITE_DESCRIPTION, 0, $suite_description);
	$results->addXmlObject(P_RESULTS_SUITE_TYPE, 0, $suite_type);
	$results->addXmlObject(P_RESULTS_SUITE_EXTENSIONS, 0, $suite_extensions);
	$results->addXmlObject(P_RESULTS_SUITE_PROPERTIES, 0, implode(";", $suite_properties));

	// Write system information
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
						{
							if($r_o_test_attributes[$i] == $r_n_test_attributes[$j])
							{
								unset($r_n_test_attributes[$j]);
								$removed = true;
							}
						}

						unset($r_o_test_attributes[$i]);
					}
				}

				if(count($r_o_test_attributes) == 1 && count($r_n_test_attributes) == 1)
				{
					if(!$has_merged)
					{
						$similar_attributes_text_add = implode(" - ", $similar_attributes);
						$test_attribute = array_pop($r_o_test_attributes);
						$r_o_test_attributes_1 = explode(":", $test_attribute);

						if(count($r_o_test_attributes_1) > 1)
						{
							$similar_attributes_text = trim($r_o_test_attributes_1[0]) . " Analysis";

							if(!empty($similar_attributes_text_add))
							{
								$similar_attributes_text .= " [" . $similar_attributes_text_add . "]";
							}
						}

						$USE_ID = pts_request_new_id();
						$results->addXmlObject(P_RESULTS_TEST_TITLE, $USE_ID, $results_name[$r_o]);
						$results->addXmlObject(P_RESULTS_TEST_VERSION, $USE_ID, $results_version[$r_o]);
						$results->addXmlObject(P_RESULTS_TEST_ATTRIBUTES, $USE_ID, $similar_attributes_text);
						$results->addXmlObject(P_RESULTS_TEST_SCALE, $USE_ID, $results_scale[$r_o]);
						$results->addXmlObject(P_RESULTS_TEST_PROPORTION, $USE_ID, $results_proportion[$r_o]);
						$results->addXmlObject(P_RESULTS_TEST_TESTNAME, $USE_ID, $results_testname[$r_o]);
						$results->addXmlObject(P_RESULTS_TEST_ARGUMENTS, $USE_ID, $results_arguments[$r_o]);
						$results->addXmlObject(P_RESULTS_TEST_RESULTFORMAT, $USE_ID, "LINE_GRAPH");

						for($o = 0; $o < count($results_identifiers[$r_o]); $o++)
						{
							$show_attribute = trim(array_pop(explode(":", $test_attribute)));
							$results->addXmlObject(P_RESULTS_RESULTS_GROUP_IDENTIFIER, $USE_ID, $show_attribute, 5, "o-$r_o-$o");
							$results->addXmlObject(P_RESULTS_RESULTS_GROUP_VALUE, $USE_ID, $results_values[$r_o][$o], 5, "o-$r_o-$o");
							$results->addXmlObject(P_RESULTS_RESULTS_GROUP_RAW, $USE_ID, $results_rawvalues[$r_o][$o], 5, "o-$r_o-$o");
						}
					}

					for($o = 0; $o < count($results_identifiers[$r_n]); $o++)
					{
						$show_attribute = trim(array_pop(explode(":", array_pop($r_n_test_attributes))));
						$results->addXmlObject(P_RESULTS_RESULTS_GROUP_IDENTIFIER, $USE_ID, $show_attribute, 5, "n-$r_n-$o");
						$results->addXmlObject(P_RESULTS_RESULTS_GROUP_VALUE, $USE_ID, $results_values[$r_n][$o], 5, "n-$r_n-$o");
						$results->addXmlObject(P_RESULTS_RESULTS_GROUP_RAW, $USE_ID, $results_rawvalues[$r_n][$o], 5, "n-$r_n-$o");
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

	return $results->getXML();
}

?>
