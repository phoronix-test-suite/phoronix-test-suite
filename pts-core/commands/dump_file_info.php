<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2017, Phoronix Media
	Copyright (C) 2017, Michael Larabel

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

class dump_file_info implements pts_option_interface
{
	const doc_section = 'Debugging';
	const doc_description = 'This option will dump the MD5 / SHA256 checksums and file size for a given file.';
	public static function run($r)
	{
		pts_client::$display->generic_heading('File Information');
		if(empty($r))
		{
			echo PHP_EOL . 'No files passed.' . PHP_EOL;
		}
		foreach($r as $f)
		{
			if(!is_file($f))
			{
				echo PHP_EOL . 'Not a file: ' . $f . PHP_EOL;
			}
			else
			{
				echo PHP_EOL . 'FILE:   ' . basename($f) . PHP_EOL;
				echo 'MD5:    ' . md5_file($f) . PHP_EOL;
				echo 'SHA256: ' . hash_file('sha256', $f) . PHP_EOL;
				echo 'SIZE:   ' . filesize($f) . PHP_EOL;
			}

		}
	}
}

?>
