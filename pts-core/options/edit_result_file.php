<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel

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

class edit_result_file implements pts_option_interface
{
	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, "pts_find_result_file", 0, "No result file was found.")
		);
	}
	public static function run($args)
	{
		$edit_options = array(
		"Extract From Results",
		"Remove From Results",
		"Reorder Result Identifiers",
		"Rename Identifier"
		);

		$input_option = pts_user_io::prompt_text_menu("Select edit operation", $edit_options);

		switch($input_option)
		{
			case "Extract From Results":
				pts_client::run_next("extract_from_result_file", $args);
				break;
			case "Remove From Results":
				pts_client::run_next("remove_from_result_file", $args);
				break;
			case "Reorder Result Identifiers":
				pts_client::run_next("reorder_result_file", $args);
				break;
			case "Rename Identifier":
				pts_client::run_next("rename_identifier_in_result_file", $args);
				break;
		}
	}
}

?>
