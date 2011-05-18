<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2011, Phoronix Media
	Copyright (C) 2010 - 2011, Michael Larabel

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

class help implements pts_option_interface
{
	const doc_section = 'Other';
	const doc_description = "This option will display the list of available Phoronix Test Suite commands.";

	public static function run($r)
	{
		echo "\n" . pts_title(true) . "\n\n";
		echo pts_documentation::basic_description() . "\n\nView the included PDF / HTML documentation or visit http://www.phoronix-test-suite.com/ for full details.\n";
		$options = pts_documentation::client_commands_array();

		foreach($options as $section => &$contents)
		{
			if(empty($contents))
			{
				continue;
			}

			echo "\n" . strtoupper($section) . "\n\n";

			sort($contents);
			foreach($contents as &$option)
			{
				echo "    " . trim($option[0] . ' ' . implode(' ', $option[1])) . "\n";
			}
		}
		echo "\n\n";
	}
}

?>
