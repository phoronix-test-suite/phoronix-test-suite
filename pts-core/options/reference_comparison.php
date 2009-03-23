<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel

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

class reference_comparison implements pts_option_interface
{
	public static function required_function_sets()
	{
		return array("merge");
	}
	public static function run($r)
	{
		$result = pts_find_result_file($r[0]);

		if($result == false)
		{
			echo "\nNo result file was specified.\n";
			return false;
		}

		$xml_parser = new pts_results_tandem_XmlReader($result);
		$result_test = $xml_parser->getXMLValue(P_RESULTS_SUITE_NAME);

		if(pts_is_suite($result_test))
		{
			echo "\nReference comparisons for suites are currently disabled.\n";
			return false;

			$xml_parser = new pts_suite_tandem_XmlReader($result_test);
			$reference_systems_xml = $xml_parser->getXMLValue(P_SUITE_REFERENCE_SYSTEMS);
		}
		else if(pts_is_test($result_test))
		{
			$xml_parser = new pts_test_tandem_XmlReader($result_test);
			$reference_systems_xml = $xml_parser->getXMLValue(P_TEST_REFERENCE_SYSTEMS);
		}
		else
		{
			echo "\n" . $result_test . " in " . $result . " could not be determined.\n";
			return false;
		}

		$reference_systems = array();
		$reference_count = 1;

		echo pts_string_header("Reference Comparison");

		foreach(array_map("trim", explode(",", $reference_systems_xml)) as $global_id)
		{
			if(pts_is_global_id($global_id))
			{
				if(!pts_is_test_result($global_id))
				{
					pts_clone_from_global($global_id, false);
				}

				$xml_parser = new pts_results_tandem_XmlReader($global_id);
				$ref_identifiers = $xml_parser->getXMLArrayValues(P_RESULTS_SYSTEM_IDENTIFIERS);
				$ref_hardware = $xml_parser->getXMLArrayValues(P_RESULTS_SYSTEM_HARDWARE);
				$ref_software = $xml_parser->getXMLArrayValues(P_RESULTS_SYSTEM_SOFTWARE);

				for($i = 0; $i < count($ref_identifiers); $i++)
				{
					echo $reference_count . ": " . $ref_identifiers[$i] . "\n\n";
					echo $ref_hardware[$i] . "\n\n" . $ref_software[$i] . "\n\n";

					$reference_systems[$reference_count] = new pts_result_merge_select($global_id, $ref_identifiers[$i]);
					$reference_count++;
				}
			}
		}

		if(count($reference_systems) == 0)
		{
			echo "\nNo reference systems found.\n\n";
			return false;
		}

		do
		{
			echo "\nSelect a reference system to compare to: ";
			$request_identifier = trim(fgets(STDIN));
		}
		while(!isset($reference_systems[$request_identifier]));

		$merged_results = pts_merge_test_results($r[0], $reference_systems[$request_identifier]);
		pts_save_result($r[0] . "/composite.xml", $merged_results);
		pts_display_web_browser(SAVE_RESULTS_DIR . $r[0] . "/composite.xml");
	}
}

?>
