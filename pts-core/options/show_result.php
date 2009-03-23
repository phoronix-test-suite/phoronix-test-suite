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

class show_result implements pts_option_interface
{
	public static function run($r)
	{
		if(($URL = pts_find_result_file($r[0])) != false)
		{
			if(!is_dir(SAVE_RESULTS_DIR . $r[0] . "/result-graphs/"))
			{
				pts_generate_graphs(file_get_contents($URL), SAVE_RESULTS_DIR . $r[0] . "/");
			}

			pts_run_shell_script(PTS_CORE_PATH . "scripts/launch-browser.sh", $URL);
		}
		else
		{
			echo "\n" . $r[0] . " was not found.\n";
		}
	}
}

?>
