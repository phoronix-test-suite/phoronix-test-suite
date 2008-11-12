<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel

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

class result_information
{
	public static function run($r)
	{
		if(is_file(($saved_results_file = SAVE_RESULTS_DIR . $r[0] . "/composite.xml")))
		{
			echo new pts_test_result_info_details($saved_results_file);
		}
		else
		{
			echo "\n" . $r[0] . " isn't a valid results file.\n";
		}
		echo "\n";
	}
}

?>
