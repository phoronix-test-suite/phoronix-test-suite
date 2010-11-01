<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2010, Phoronix Media
	Copyright (C) 2009 - 2010, Michael Larabel

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

class pts_validation
{
	public static function check_xml_tags(&$obj, &$tags_to_check, &$append_missing_to)
	{
		foreach($tags_to_check as $tag_check)
		{
			$to_check = $obj->xml_parser->getXMLValue($tag_check[0]);

			if(empty($to_check))
			{
				array_push($append_missing_to, $tag_check);
			}
		}
	}
	public static function print_issue($type, $problems_r)
	{
		foreach($problems_r as $error)
		{
			list($target, $description) = $error;

			echo "\n" . $type . ": " . $description . "\n";

			if(!empty($target))
			{
				echo "TARGET: " . $target . "\n";
			}
		}
	}
	public static function required_test_tags()
	{
		return array(
		array(P_TEST_TITLE, "A title tag is required for standard test profiles."),
		array(P_TEST_PTSVERSION, "A version tag is required for standard test profiles."),
		array(P_TEST_HARDWARE_TYPE, "A hardware type tag is required for standard test profiles."),
		array(P_TEST_MAINTAINER, "Phoronix Media requires a maintainer tag for standard test profiles."),
		array(P_TEST_LICENSE, "Phoronix Media requires a license tag for standard test profiles."),
		array(P_TEST_STATUS, "Phoronix Media requires a status tag for standard test profiles."),
		array(P_TEST_DESCRIPTION, "Phoronix Media requires a description tag for standard test profiles."),
		array(P_TEST_SCALE, "A scale tag is required for most standard test profiles."),
		array(P_TEST_PROPORTION, "A proportion tag is required for most standard test profiles.")
		);
	}
	public static function recommended_test_tags()
	{
		return array(
		array(P_TEST_SOFTWARE_TYPE, "A tag for the software program's type is recommended for standard test profiles."),
		array(P_TEST_ENVIRONMENTSIZE, "A tag for the approximate disk size needed (in MB) for the test profile is recommended."),
		array(P_TEST_PROJECTURL, "A tag for the web-site URL of the tested software is recommended.")
		);
	}
}

?>
