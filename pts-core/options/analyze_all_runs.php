<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel

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

class analyze_all_runs implements pts_option_interface
{
	public static function run($r)
	{
		$identifier = $r[0];

		if(($file = pts_find_result_file($identifier)) != false)
		{
			$composite_xml = file_get_contents($file);

			pts_set_assignment("GRAPH_RENDER_TYPE", "CANDLESTICK");
			if(pts_save_result($identifier . "/composite.xml", $composite_xml))
			{
				echo "\n" . $identifier . " has been re-rendered to show all test runs.\n";
				pts_set_assignment_next("PREV_SAVE_RESULTS_IDENTIFIER", $identifier);
				pts_display_web_browser(SAVE_RESULTS_DIR . $identifier . "/index.html");
			}
		}
		else
		{
			echo pts_string_header($identifier . " was not found.");
		}
	}
}

?>
