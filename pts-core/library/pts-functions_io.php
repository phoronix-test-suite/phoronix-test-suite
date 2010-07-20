<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel
	pts-functions_io.php: General user input / output functions

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

function pts_string_header($heading, $char = '=')
{
	// Return a string header
	if(!isset($heading[1]))
	{
		return null;
	}

	$header_size = 40;

	foreach(explode("\n", $heading) as $line)
	{
		if(isset($line[($header_size + 1)])) // Line to write is longer than header size
		{
			$header_size = strlen($line);
		}
	}

	if(($terminal_width = pts_client::terminal_width()) < $header_size && $terminal_width > 0)
	{
		$header_size = $terminal_width;
	}

	return "\n" . str_repeat($char, $header_size) . "\n" . $heading . "\n" . str_repeat($char, $header_size) . "\n\n";
}

?>
