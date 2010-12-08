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

class validate_result_file implements pts_option_interface
{
	public static function run($r)
	{
		if(pts_result_file::is_test_result_file($r[0]))
		{
			$result_file = new pts_result_file($r[0]);
			pts_client::$display->generic_heading($r[0]);
			$valid = $result_file->xml_parser->validate();

			if($valid == false)
			{
				echo "\nErrors occurred parsing the main XML.\n";
				pts_validation::process_libxml_errors();
				return false;
			}
			else
			{
				echo "\nThe result file is Valid.\n";
			}
		}
	}
}

?>
