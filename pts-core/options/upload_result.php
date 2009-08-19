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

class upload_result implements pts_option_interface
{
	public static function required_function_sets()
	{
		return array("run");
	}
	public static function run($r)
	{
		if(($use_file = pts_find_result_file($r[0], false)) == false)
		{
			echo "\nThis result does not exist.\n";
		}
		else
		{
			if(!pts_is_assignment("AUTOMATED_MODE"))
			{
				$tags_input = pts_prompt_user_tags();
				echo "\n";
			}

			$upload_url = pts_global_upload_result($use_file, $tags_input);

			if(!empty($upload_url))
			{
				echo "\nResults Uploaded To: " . $upload_url . "\n\n";
				pts_set_assignment_next("PREV_GLOBAL_UPLOAD_URL", $upload_url);
				pts_module_process("__event_global_upload", $upload_url);
			}
			else
			{
				echo "\nResults Failed To Upload.\n";
			}
		}
	}
}

?>
