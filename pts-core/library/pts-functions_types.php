<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel
	pts-functions_types.php: Functions needed for type handling of tests/suites.

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

// TODO: pts_tests is the object to be cleaned up with pts-functions_types

function pts_identifier_type($identifier, $rewrite_cache = false)
{
	// Determine type of test based on identifier
	static $cache;

	if(!isset($cache[$identifier]) || $rewrite_cache)
	{
		$test_type = false;

		if(!empty($identifier))
		{
			if(is_file(XML_PROFILE_DIR . $identifier . ".xml"))
			{
				$test_type = "TYPE_TEST";
			}
			else if(is_file(XML_SUITE_DIR . $identifier . ".xml"))
			{
				$test_type = "TYPE_TEST_SUITE";
			}
		}

		$cache[$identifier] = $test_type;
	}

	return $cache[$identifier];
}
function pts_is_run_object($object)
{
	return pts_is_test($object) || pts_is_suite($object);
}
function pts_is_suite($object)
{
	$type = pts_identifier_type($object);

	return $type == "TYPE_TEST_SUITE" || $type == "TYPE_LOCAL_TEST_SUITE";
}
function pts_is_virtual_suite($object)
{
	return pts_location_virtual_suite($object) != false;
}
function pts_is_test($object)
{
	$type = pts_identifier_type($object);

	return $type == "TYPE_TEST" || $type == "TYPE_BASE_TEST";
}
function pts_is_base_test($object)
{
	$type = pts_identifier_type($object);

	return $type == "TYPE_BASE_TEST";
}
function pts_is_test_result($identifier)
{
	return is_file(SAVE_RESULTS_DIR . $identifier . "/composite.xml");
}

?>
