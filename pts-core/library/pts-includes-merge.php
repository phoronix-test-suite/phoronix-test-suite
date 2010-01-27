<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel
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

	if(count($files_to_combine) == 1 && is_array($files_to_combine[0]))
	{
		$files_to_combine = $files_to_combine[0];
	}

	$results = new tandem_XmlWriter();
	$test_result_manager = new pts_result_file_merge_manager();

	$results->setXslBinding("pts-results-viewer.xsl");
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

		if(!defined("ONLY_RESULTS_IN_XML"))
		{
			if($has_written_suite_info == false)
			{
				pts_result_file_suite_info_to_xml($this_result_file, $results);
				$has_written_suite_info = true;
			}

			pts_result_file_system_info_to_xml($this_result_file, $results, $added_systems_hash, $result_merge_select);
		}

		$test_result_manager->add_test_result_set($this_result_file->get_result_objects(), $result_merge_select);
	}

	// Write the actual test results
	pts_result_file_results_to_xml($test_result_manager, $results);

	return $results->getXML();
}
function pts_generate_analytical_batch_xml($analyze_file)
{
	$results = new tandem_XmlWriter();
	$results->setXslBinding("pts-results-viewer.xsl");

	$test_result_manager = new pts_result_file_analyze_manager();
	$result_file = new pts_result_file($analyze_file);
	$added_systems_hash = array();

	pts_result_file_suite_info_to_xml($result_file, $results);
	pts_result_file_system_info_to_xml($result_file, $results, $added_systems_hash, null);

	$test_result_manager->add_test_result_set($result_file->get_result_objects());
	pts_result_file_results_to_xml($test_result_manager, $results);

	return $results->getXML();
}
function pts_result_file_suite_info_to_xml(&$pts_result_file, &$xml_writer)
{
	$xml_writer->addXmlObject(P_RESULTS_SUITE_TITLE, 0, $pts_result_file->get_title());
	$xml_writer->addXmlObject(P_RESULTS_SUITE_NAME, 0, $pts_result_file->get_suite_name());
	$xml_writer->addXmlObject(P_RESULTS_SUITE_VERSION, 0, $pts_result_file->get_suite_version());
	$xml_writer->addXmlObject(P_RESULTS_SUITE_DESCRIPTION, 0, $pts_result_file->get_suite_description());
	$xml_writer->addXmlObject(P_RESULTS_SUITE_TYPE, 0, $pts_result_file->get_suite_type());
	$xml_writer->addXmlObject(P_RESULTS_SUITE_EXTENSIONS, 0, $pts_result_file->get_suite_extensions());
	$xml_writer->addXmlObject(P_RESULTS_SUITE_PROPERTIES, 0, $pts_result_file->get_suite_properties());
}
function pts_result_file_system_info_to_xml(&$pts_result_file, &$xml_writer, &$systems_hash, $result_merge_select)
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
function pts_result_file_results_to_xml(&$result_manager, &$xml_writer)
{
	$results_added = 0;
	foreach($result_manager->get_results() as $result_object)
	{
		$buffer_items = $result_object->get_result_buffer()->get_buffer_items();

		if(count($buffer_items) == 0)
		{
			continue;
		}

		$use_id = pts_request_new_id();
		$xml_writer->addXmlObject(P_RESULTS_TEST_TITLE, $use_id, $result_object->get_name());
		$xml_writer->addXmlObject(P_RESULTS_TEST_VERSION, $use_id, $result_object->get_version());
		$xml_writer->addXmlObject(P_RESULTS_TEST_ATTRIBUTES, $use_id, $result_object->get_attributes());
		$xml_writer->addXmlObject(P_RESULTS_TEST_SCALE, $use_id, $result_object->get_scale());
		$xml_writer->addXmlObject(P_RESULTS_TEST_PROPORTION, $use_id, $result_object->get_proportion());
		$xml_writer->addXmlObject(P_RESULTS_TEST_TESTNAME, $use_id, $result_object->get_test_name());
		$xml_writer->addXmlObject(P_RESULTS_TEST_ARGUMENTS, $use_id, $result_object->get_arguments());
		$xml_writer->addXmlObject(P_RESULTS_TEST_RESULTFORMAT, $use_id, $result_object->get_format());

		foreach($buffer_items as $i => &$buffer_item)
		{
			$xml_writer->addXmlObject(P_RESULTS_RESULTS_GROUP_IDENTIFIER, $use_id, $buffer_item->get_result_identifier(), 5, "o-$i-$results_added-r");
			$xml_writer->addXmlObject(P_RESULTS_RESULTS_GROUP_VALUE, $use_id, $buffer_item->get_result_value(), 5, "o-$i-$results_added-r");
			$xml_writer->addXmlObject(P_RESULTS_RESULTS_GROUP_RAW, $use_id, $buffer_item->get_result_raw(), 5, "o-$i-$results_added-r");
		}

		$results_added++;
	}
}
function pts_test_result_contains_result_identifier($test_result, $results_identifier)
{
	$result_file = new pts_result_file($test_result);

	return in_array($results_identifier, $result_file->get_system_identifiers());	
}
function pts_list_regressions_linear(&$result_file, $threshold = 0.05, $show_only_active_regressions = true)
{
	$regressions = array();

	foreach($result_file->get_result_objects() as $test_index => $result_object)
	{
		$prev_buffer_item = null;
		$this_test_regressions = array();

		foreach($result_object->get_result_buffer()->get_buffer_items() as $buffer_item)
		{
			if(!is_numeric($buffer_item->get_result_value()))
			{
				break;
			}

			if($prev_buffer_item != null && abs(1 - ($buffer_item->get_result_value() / $prev_buffer_item->get_result_value())) > $threshold)
			{
				$this_regression_marker = new pts_test_result_regression_marker($result_object, $prev_buffer_item, $buffer_item, $test_index);

				if($show_only_active_regressions)
				{
					foreach($this_test_regressions as $index => &$regression_marker)
					{
						if(abs(1 - ($regression_marker->get_base_value() / $this_regression_marker->get_regressed_value())) < 0.04)
						{
							// 1% tolerance, regression seems to be corrected
							unset($this_test_regressions[$index]);
							$this_regression_market = null;
							break;
						}
					}
				}

				if($this_regression_marker != null)
				{
					array_push($this_test_regressions, $this_regression_marker);
				}
			}

			$prev_buffer_item = $buffer_item;
		}

		foreach($this_test_regressions as &$regression_marker)
		{
			array_push($regressions, $regression_marker);
		}
	}

	return $regressions;
}
function pts_result_file_mto_compact(&$mto)
{
	// TODO: this may need to be cleaned up, its logic is rather messy
	if(count($mto->get_scale_special()) > 0)
	{
		// It's already doing something
		return;
	}

	$scale_special = array();
	$days = array();
	$systems = array();

	foreach($mto->get_result_buffer()->get_identifiers() as $identifier)
	{
		$identifier = pts_trim_explode(": ", $identifier);

		if(count($identifier) != 2)
		{
			// won't work
			return;
		}

		$system = $identifier[0];
		$date = $identifier[1];

		if(!isset($systems[$system]))
		{
			$systems[$system] = 0;
		}
		if(!isset($days[$date]))
		{
			$days[$date] = null;
		}
	}

	foreach(array_keys($days) as $day_key)
	{
		$days[$day_key] = $systems;
	}

	foreach($mto->get_result_buffer()->get_buffer_items() as $buffer_item)
	{
		list($system, $date) = pts_trim_explode(": ", $buffer_item->get_result_identifier());

		$days[$date][$system] = $buffer_item->get_result_value();

		if(!is_numeric($days[$date][$system]))
		{
			return;
		}
	}

	$mto->set_scale($mto->get_scale() . ' | ' . implode(',', array_keys($days)));
	$mto->set_format((count($days) < 8 ? "BAR_ANALYZE_GRAPH" : "LINE_GRAPH"));
	$mto->flush_result_buffer();

	$day_keys = array_keys($days);

	foreach(array_keys($systems) as $system_key)
	{
		$results = array();

		foreach($day_keys as $day_key)
		{
			array_push($results, $days[$day_key][$system_key]);
		}

		$mto->add_result_to_buffer($system_key, implode(',', $results), null);
	}
}

?>
