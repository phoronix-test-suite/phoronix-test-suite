<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel
	pts-includes-comparisons.php: Functions needed for performing reference comparisons

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

function pts_result_file_reference_tests($result)
{
	static $ref_systems_xml_strings = array();

	$result_file = new pts_result_file($result);
	$result_test = $result_file->get_suite_name();
	$result_identifiers = $result_file->get_system_identifiers();
	$test_result_hashes = array();
	$reference_tests = array();

	foreach($result_file->get_result_object_hashes() as $object_hash)
	{
		array_push($test_result_hashes, $object_hash);
	}

	if(!isset($ref_systems_xml_strings[$result_test]))
	{
		if(pts_is_suite($result_test))
		{
			$ref_systems_xml_strings[$result_test] = pts_suite_read_xml($result_test, P_SUITE_REFERENCE_SYSTEMS);
		}
		else if(pts_is_test($result_test))
		{
			$ref_systems_xml_strings[$result_test] = pts_test_read_xml($result_test, P_TEST_REFERENCE_SYSTEMS);
		}
		else
		{
			$ref_systems_xml_strings[$result_test] = null;
		}
	}

	$reference_ids = pts_trim_explode(",", $ref_systems_xml_strings[$result_test]);
	$reference_file_ids = pts_generic_reference_system_comparison_ids();

	foreach(array_merge($reference_ids, $reference_file_ids) as $global_id)
	{
		if(pts_is_global_id($global_id))
		{
			if(!pts_is_test_result($global_id))
			{
				pts_clone_from_global($global_id, false);
			}

			$global_result_file = new pts_result_file($global_id);
			$global_result_hashes = array();

			$global_result_identifiers = $global_result_file->get_system_identifiers();

			foreach($global_result_identifiers as $index => $identifier_check)
			{
				if(in_array($identifier_check, $result_identifiers))
				{
					unset($global_result_identifiers[$index]);
				}
			}

			if(count($global_result_identifiers) > 0)
			{
				$hash_failed = false;

				foreach($global_result_file->get_result_object_hashes() as $object_hash)
				{
					array_push($global_result_hashes, $object_hash);
				}
				foreach($test_result_hashes as &$hash)
				{
					if(!in_array($hash, $global_result_hashes))
					{
						$hash_failed = true;
						break;
					}
				}

				if(!$hash_failed)
				{
					foreach($global_result_identifiers as &$system_identifier)
					{
						array_push($reference_tests, new pts_result_merge_select($global_id, $system_identifier));
					}
				}
			}
		}
	}

	return $reference_tests;
}
function pts_download_all_generic_reference_system_comparison_results()
{
	foreach(pts_generic_reference_system_comparison_ids() as $comparison_id)
	{
		if(!pts_is_test_result($comparison_id))
		{
			pts_clone_from_global($comparison_id, false);
		}
	}
}

?>
