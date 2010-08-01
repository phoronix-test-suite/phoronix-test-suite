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
	public static function available_tests()
	{
		static $cache = null;

		if($cache == null)
		{
			$tests = glob(XML_PROFILE_DIR . "*.xml");
			$tests_local = glob(XML_PROFILE_LOCAL_DIR . "*.xml");

			if($tests != false && $tests_local != false)
			{
				$tests = array_unique(array_merge($tests, $tests_local));
			}
			else if($tests_local != false)
			{
				$tests = $tests_local;
			}
			else if($tests == false)
			{
				$tests = array();
			}

			asort($tests);

			foreach($tests as &$test)
			{
				$test = basename($test, ".xml");
			}

			$cache = $tests;
		}

		return $cache;
	}
	public static function supported_tests()
	{
		static $cache = null;

		if($cache == null)
		{
			$supported_tests = array();

			foreach(pts_tests::available_tests() as $identifier)
			{
				$test_profile = new pts_test_profile($identifier);

				if($test_profile->is_supported())
				{
					array_push($supported_tests, $identifier);
				}
			}

			$cache = $supported_tests;
		}

		return $cache;
	}
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
	public static function process_extra_test_variables($identifier)
	{
		$extra_vars = array();
		$extra_vars["HOME"] = TEST_ENV_DIR . $identifier . "/";

		$ctp_extension_string = "";
		$extends = pts_test_extends_below($identifier);
		foreach(array_merge(array($identifier), $extends) as $extended_test)
		{
			if(is_dir(TEST_ENV_DIR . $extended_test . "/"))
			{
				$ctp_extension_string .= TEST_ENV_DIR . $extended_test . ":";
				$extra_vars["TEST_" . strtoupper(str_replace("-", "_", $extended_test))] = TEST_ENV_DIR . $extended_test;
			}
		}

		if(!empty($ctp_extension_string))
		{
			$extra_vars["PATH"] = $ctp_extension_string . "\$PATH";
		}

		if(isset($extends[0]))
		{
			$extra_vars["TEST_EXTENDS"] = TEST_ENV_DIR . $extends[0];
		}

		return $extra_vars;
	}
}

?>
