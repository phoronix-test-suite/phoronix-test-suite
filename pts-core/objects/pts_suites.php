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

// TODO XXX: Eliminate this class, part of pts_tests and pts-functions_types
class pts_suites
{
	public static function available_suites()
	{
		static $cache = null;

		if($cache == null)
		{
			$suites = glob(XML_SUITE_DIR . "*.xml");

			if($suites == false)
			{
				$suites = array();
			}

			asort($suites);

			foreach($suites as &$suite)
			{
				$suite = basename($suite, ".xml");
			}

			$cache = $suites;
		}

		return $cache;
	}
	public static function supported_suites()
	{
		static $cache = null;

		if($cache == null)
		{
			$supported_suites = array();

			foreach(pts_suites::available_suites() as $identifier)
			{
				$suite = new pts_test_suite($identifier);

				if($suite->is_supported())
				{
					array_push($supported_suites, $identifier);
				}
			}

			$cache = $supported_suites;
		}

		return $cache;
	}
	public static function installed_suites()
	{
		$installed_suites = array();

		foreach(pts_suites::available_suites() as $suite)
		{
			$suite = new pts_test_suite($suite);
			if($suite->needs_updated_install() == false)
			{
				array_push($installed_suites, $suite);
			}
		}

		return $installed_suites;
	}
}

?>
