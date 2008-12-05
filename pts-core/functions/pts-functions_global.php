<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts-functions_global.php: Functions needed for Phoronix Global.

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

function pts_is_global_id($global_id)
{
	// Checks if a string is a valid Phoronix Global ID
	return pts_global_valid_id_string($global_id) && trim(@file_get_contents("http://www.phoronix-test-suite.com/global/profile-check.php?id=" . $global_id)) == "REMOTE_FILE";
}
function pts_global_download_xml($global_id)
{
	// Download a saved test result from Phoronix Global
	return @file_get_contents("http://www.phoronix-test-suite.com/global/pts-results-viewer.php?id=" . $global_id);
}
function pts_clone_from_global($global_id)
{
	return pts_save_result($global_id . "/composite.xml", pts_global_download_xml($global_id));
}
function pts_global_valid_id_string($global_id)
{
	// Basic checking to see if the string is possibly a Global ID
	$is_valid = true;

	if(count(explode("-", $global_id)) < 3) // Global IDs should have three (or more) dashes
	{
		$is_valid = false;
	}

	if(strlen($global_id) < 13) // Shortest Possible ID would be X-000-000-000
	{
		$is_valid = false;
	}

	return $is_valid;
}

?>
