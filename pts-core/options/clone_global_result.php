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

class clone_global_result implements pts_option_interface
{
	public static function run($r)
	{
		$identifier = $r[0];

		if(is_file(SAVE_RESULTS_DIR . $identifier . "/composite.xml"))
		{
			echo "A saved result already exists with the same name.\n\n";
		}
		else
		{
			if(pts_is_global_id($identifier))
			{
				pts_save_result($identifier . "/composite.xml", pts_global_download_xml($identifier));
				echo "Result Saved To: " . SAVE_RESULTS_DIR . $identifier . "/composite.xml\n\n";
				//pts_display_web_browser(SAVE_RESULTS_DIR . $ARG_1 . "/index.html");
			}
			else
			{
				echo $identifier . " is an unrecognized Phoronix Global ID.\n\n";
			}
		}
	}
}

?>
