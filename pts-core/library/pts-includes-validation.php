<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel
	pts-includes-validation.php: Functions needed validating test profiles, suites, and other PTS objects

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

function pts_validation_check_xml_tags(&$tandem_XmlReader, &$tags_to_check, &$append_missing_to)
{
	foreach($tags_to_check as $tag_check)
	{
		$to_check = $tandem_XmlReader->getXMLValue($tag_check[0]);

		if(empty($to_check))
		{
			array_push($append_missing_to, $tag_check);
		}
	}
}
function pts_validation_print_problem($type, $problems_r)
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

?>
