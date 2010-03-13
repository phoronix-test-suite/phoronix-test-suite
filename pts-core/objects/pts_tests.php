<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel

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

class pts_tests
{
	public static function test_profile_location($identifier, $rewrite_cache = false)
	{
		static $cache;

		if(!isset($cache[$identifier]) || $rewrite_cache)
		{
			switch(pts_type_handler::pts_identifier_type($identifier))
			{
				case "TYPE_TEST":
					$location = XML_PROFILE_DIR . $identifier . ".xml";
					break;
				case "TYPE_LOCAL_TEST":
					$location = XML_PROFILE_LOCAL_DIR . $identifier . ".xml";
					break;
				case "TYPE_BASE_TEST":
					$location = XML_PROFILE_CTP_BASE_DIR . $identifier . ".xml";
					break;
				default:
					$location = false;
					break;
			}

			$cache[$identifier] = $location;
		}

		return $cache[$identifier];
	}
	public static function test_resources_location($identifier, $rewrite_cache = false)
	{
		static $cache;

		if(!isset($cache[$identifier]) || $rewrite_cache)
		{
			$type = pts_type_handler::pts_identifier_type($identifier);

			if($type == "TYPE_LOCAL_TEST" && is_dir(TEST_RESOURCE_LOCAL_DIR . $identifier))
			{
				$location = TEST_RESOURCE_LOCAL_DIR . $identifier . "/";
			}
			else if($type == "TYPE_BASE_TEST" && is_dir(TEST_RESOURCE_CTP_BASE_DIR . $identifier))
			{
				$location = TEST_RESOURCE_CTP_BASE_DIR . $identifier . "/";
			}
			else if(is_dir(TEST_RESOURCE_DIR . $identifier))
			{
				$location = TEST_RESOURCE_DIR . $identifier . "/";
			}
			else
			{
				$location = false;
			}

			$cache[$identifier] = $location;
		}

		return $cache[$identifier];
	}
	public static function test_hardware_type($test_identifier)
	{
		static $cache;

		if(!isset($cache[$test_identifier]))
		{
			$test_profile = new pts_test_profile($test_identifier);
			$test_subsystem = $test_profile->get_test_hardware_type();
			$cache[$test_identifier] = $test_subsystem;
			unset($test_profile);
		}

		return $cache[$test_identifier];
	}
}

?>
