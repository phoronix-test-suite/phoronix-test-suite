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
	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, "!pts_is_test_result", null, "A saved result already exists with the same name."),
		new pts_argument_check(0, "pts_is_global_id", null, "No Phoronix Global result file found.")
		);

	}
	public static function run($args)
	{
		$identifier = $args[0];
		pts_save_result($identifier . "/composite.xml", pts_global_download_xml($identifier));
		echo "\nResult Saved To: " . SAVE_RESULTS_DIR . $identifier . "/composite.xml\n\n";
		pts_set_assignment_next("PREV_SAVE_RESULTS_IDENTIFIER", $identifier);
		//pts_display_web_browser(SAVE_RESULTS_DIR . $ARG_1 . "/index.html");
	}
}

?>
