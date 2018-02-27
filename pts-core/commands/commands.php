<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2018, Phoronix Media
	Copyright (C) 2018, Michael Larabel

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

class commands implements pts_option_interface
{
	const doc_section = 'Other';
	const doc_description = 'This option will display a short list of possible Phoronix Test Suite commands.';

	public static function run($r)
	{
		$options = pts_documentation::client_commands_array();
		$commands = array();
		foreach($options as $section => &$contents)
		{
			if(empty($contents))
			{
				continue;
			}
			foreach($contents as &$option)
			{
				$commands[] = $option[0];
			}
		}
		echo pts_user_io::display_packed_list($commands);
		echo PHP_EOL;
	}
}

?>
