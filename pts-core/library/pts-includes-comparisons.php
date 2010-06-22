<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2010, Phoronix Media
	Copyright (C) 2009 - 2010, Michael Larabel
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
	$test_result_hashes = $result_file->get_result_object_hashes();
	$reference_tests = array();

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

	$specific_reference_ids = pts_strings::trim_explode(",", $ref_systems_xml_strings[$result_test]);

	pts_process_reference_comparison_hashes($specific_reference_ids, $result_identifiers, $test_result_hashes, $reference_tests);
	pts_process_reference_comparison_hashes(pts_generic_reference_system_comparison_ids(), $result_identifiers, $test_result_hashes, $reference_tests, true);

	return $reference_tests;
}
function pts_process_reference_comparison_hashes($reference_ids_to_process, $original_test_result_identifiers, &$original_test_hashes, &$reference_tests, $handle_cache = false)
{
	static $hash_cache = null;

	foreach($reference_ids_to_process as $global_id)
	{
		if(pts_global_valid_id_string($global_id))
		{
			if(!pts_is_test_result($global_id))
			{
				pts_clone_from_global($global_id, false);
			}

			if($handle_cache && isset($hash_cache[$global_id]))
			{
				$global_result_identifiers = $hash_cache[$global_id]["identifiers"];
				$cache_hash_array = $hash_cache[$global_id]["hashes"];
			}
			else
			{
				$global_result_file = new pts_result_file($global_id);
				$global_result_identifiers = $global_result_file->get_system_identifiers();

				if($handle_cache)
				{
					$hash_cache[$global_id]["identifiers"] = $global_result_identifiers;
					$hash_cache[$global_id]["hashes"] = $global_result_file->get_result_object_hashes();
					$cache_hash_array = $hash_cache[$global_id]["hashes"];
				}
				else
				{
					$cache_hash_array = false;
				}
			}

			foreach($global_result_identifiers as $index => $identifier_check)
			{
				if(in_array($identifier_check, $original_test_result_identifiers))
				{
					unset($global_result_identifiers[$index]);
				}
			}

			if(count($global_result_identifiers) > 0)
			{
				$global_result_hashes = $cache_hash_array != false ? $cache_hash_array : $global_result_file->get_result_object_hashes();

				if(count(array_diff($original_test_hashes, $global_result_hashes)) == 0)
				{
					foreach($global_result_identifiers as $system_identifier)
					{
						array_push($reference_tests, new pts_result_merge_select($global_id, $system_identifier));
					}
				}
			}
		}
	}
}
function pts_generic_reference_system_build_cache()
{
	$original_test_hashes = array();
	$reference_tests = array();
	pts_process_reference_comparison_hashes(pts_generic_reference_system_comparison_ids(), array(), $original_test_hashes, $reference_tests, true);
}
function pts_download_all_generic_reference_system_comparison_results()
{
	$reference_cache_dir = is_dir("/var/cache/phoronix-test-suite/reference-comparisons/") ? "/var/cache/phoronix-test-suite/reference-comparisons/" : false;

	foreach(pts_generic_reference_system_comparison_ids() as $comparison_id)
	{
		if(!pts_is_test_result($comparison_id))
		{
			if($reference_cache_dir && is_readable($reference_cache_dir . $comparison_id . ".xml"))
			{
				// A cache is already available locally (likely from a PTS Live OS)
				pts_save_result($comparison_id . "/composite.xml", file_get_contents($reference_cache_dir . $comparison_id . ".xml"), false);
			}
			else
			{
				// Fetch from Phoronix Global
				pts_clone_from_global($comparison_id, false);
			}
		}
	}
}

?>
