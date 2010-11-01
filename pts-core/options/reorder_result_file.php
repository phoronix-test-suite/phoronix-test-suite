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

class reorder_result_file implements pts_option_interface
{
	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array("pts_types", "is_result_file"), null, "No result file was found.")
		);
	}
	public static function run($args)
	{
		$result = $args[0];

		$result_file = new pts_result_file($result);
		$result_file_identifiers = $result_file->get_system_identifiers();

		if(count($result_file_identifiers) < 2)
		{
			echo "\nThere are not multiple test runs in this result file.\n";
			return false;
		}

		$extract_selects = array();
		echo "\nEnter The New Order To Display The New Results, From Left To Right.\n";

		do
		{
			$extract_identifier = pts_user_io::prompt_text_menu("Select the test run to be showed next", $result_file_identifiers);
			array_push($extract_selects, new pts_result_merge_select($result, $extract_identifier));

			$old_identifiers = $result_file_identifiers;
			$result_file_identifiers = array();

			foreach($old_identifiers as $identifier)
			{
				if($identifier != $extract_identifier)
				{
					array_push($result_file_identifiers, $identifier);
				}
			}
		}
		while(count($result_file_identifiers) > 0);

		$ordered_result = pts_merge::merge_test_results_array($extract_selects);
		pts_client::save_test_result($args[0] . "/composite.xml", $ordered_result);
		pts_client::display_web_page(SAVE_RESULTS_DIR . $args[0] . "/composite.xml");
	}
}

?>
