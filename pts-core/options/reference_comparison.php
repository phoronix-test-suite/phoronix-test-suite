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

		$reference_test_globals = pts_result_file_reference_tests($result);

		if(count($reference_test_globals) == 0)
		{
			echo "\nNo reference tests are available.\n\n";
			return false;
		}

		$merge_args = array($r[0]);
		if(pts_is_assignment("AUTOMATED_MODE"))
		{
			$reference_comparisons = pts_read_assignment("REFERENCE_COMPARISONS");

			foreach($reference_comparisons as $comparison)
			{
				array_push($merge_args, $comparison);
			}
		}
		else
		{
			echo pts_string_header("Reference Comparison");
			$reference_systems = array();
			$reference_count = 1;

			foreach($reference_test_globals as $global_id)
			{
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

			do
			{
				echo "\nSelect a reference system to compare to: ";
				$request_identifier = trim(fgets(STDIN));
			}
			while(!isset($reference_systems[$request_identifier]));
			array_push($merge_args, $reference_systems[$request_identifier]);
		}

		$merged_results = call_user_func_array("pts_merge_test_results", $merge_args);
		pts_save_result($r[0] . "/composite.xml", $merged_results);
		pts_set_assignment_next("PREV_SAVE_RESULTS_IDENTIFIER", $r[0]);

		if(($title = pts_read_assignment("PREV_SAVE_NAME_TITLE")) != false)
		{
			pts_set_assignment_next("PREV_SAVE_NAME_TITLE", $title . " + Comparison");
		}

		pts_display_web_browser(SAVE_RESULTS_DIR . $r[0] . "/composite.xml");
	}
}

?>
