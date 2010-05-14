<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel
	pts.php: Version information for the Phoronix Test Suite.

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

if(PTS_MODE == "CLIENT")
{
	error_reporting(E_ERROR | E_STRICT);
}

define("PTS_VERSION", "2.6.0b3");
define("PTS_CORE_VERSION", 2560);
define("PTS_CODENAME", "LYNGEN");

function pts_codename($full_string = false)
{
	$codename = ucwords(strtolower(PTS_CODENAME));

	return ($full_string ? "PhoronixTestSuite/" : "") . $codename;
}
function pts_title($show_both = false)
{
	return "Phoronix Test Suite" . (PTS_VERSION != null ? " v" . PTS_VERSION : null) . (PTS_CODENAME != null && (PTS_VERSION == null || $show_both ) ? " (" . ucwords(strtolower(PTS_CODENAME)) . ")" : null);
}

?>
