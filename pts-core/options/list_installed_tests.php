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

class list_installed_tests implements pts_option_interface
{
	public static function run($r)
	{
		$installed_tests = pts_installed_tests_array();
		pts_client::$display->generic_heading(count($installed_tests) . " Tests Installed");

		if(count($installed_tests) > 0)
		{
			foreach(pts_installed_tests_array() as $identifier)
			{
				$xml_parser = new pts_test_tandem_XmlReader($identifier);
			 	echo (($name = $xml_parser->getXMLValue(P_TEST_TITLE)) != false ? sprintf("%-18ls - %-30ls\n", $identifier, $name) : null);
			}
			echo "\n";
		}
	}
}

?>
