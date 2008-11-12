<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	phoronix-test-suite.php: The main code for initalizing the Phoronix Test Suite (pts-core) client

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


require("pts-core/functions/pts-functions.php");

$COMMAND = $argv[1];
pts_set_assignment("COMMAND", getenv("PTS_COMMAND"));

if(is_file("pts-core/options/" . strtolower($COMMAND) . ".php"))
{
	include_once("pts-core/options/" . strtolower($COMMAND) . ".php");
	eval(strtolower($COMMAND) . "::run();");
}

?>
