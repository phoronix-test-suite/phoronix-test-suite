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

class pts_type_handler
{
	public static function pts_identifier_type($identifier, $rewrite_cache = false)
	{
		// Determine type of test based on identifier
		static $cache;

		if(!isset($cache[$identifier]) || $rewrite_cache)
		{
			$test_type = false;

			if(!empty($identifier))
			{
				if(is_file(XML_PROFILE_LOCAL_DIR . $identifier . ".xml") && pts_validate_local_test_profile($identifier))
				{
					$test_type = "TYPE_LOCAL_TEST";
				}
				else if(is_file(XML_SUITE_LOCAL_DIR . $identifier . ".xml") && pts_validate_local_test_suite($identifier))
				{
					$test_type = "TYPE_LOCAL_TEST_SUITE";
				}
				else if(is_file(XML_PROFILE_DIR . $identifier . ".xml"))
				{
					$test_type = "TYPE_TEST";
				}
				else if(is_file(XML_SUITE_DIR . $identifier . ".xml"))
				{
					$test_type = "TYPE_TEST_SUITE";
				}
				else if(is_file(XML_PROFILE_CTP_BASE_DIR . $identifier . ".xml"))
				{
					$test_type = "TYPE_BASE_TEST";
				}
			}

			$cache[$identifier] = $test_type;
		}

		return $cache[$identifier];
	}
}

?>
