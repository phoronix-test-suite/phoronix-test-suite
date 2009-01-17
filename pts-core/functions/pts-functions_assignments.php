<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts-functions_assignments.php: Functions for the assignment operations

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

function pts_assignment($process, $assignment = null, $value = null)
{
	static $assignments;
	$return = false;

	switch($process)
	{
		case "SET":
			$assignments[$assignment] = $value;
			break;
		case "READ":
			if(isset($assignments[$assignment]))
			{
				$return = $assignments[$assignment];
			}
			break;
		case "IS_SET":
			$return = isset($assignments[$assignment]);
			break;
		case "CLEAR":
			unset($assignments[$assignment]);
			break;
		case "CLEAR_ALL":
			$assignments = array();
			break;
	}

	return $return;
}
function pts_set_assignment_once($assignment, $value)
{
	$set_assignment = false;

	if(!pts_is_assignment($assignment))
	{
		pts_set_assignment($assignment, $value);
		$set_assignment = true;
	}

	return $set_assignment;
}
function pts_set_assignment($assignment, $value)
{
	$assignment = pts_to_array($assignment);

	foreach($assignment as $this_assignment)
	{
		pts_assignment("SET", $this_assignment, $value);
	}
}
function pts_read_assignment($assignment)
{
	return pts_assignment("READ", $assignment);
}
function pts_is_assignment($assignment)
{
	return pts_assignment("IS_SET", $assignment);
}
function pts_clear_assignments()
{
	pts_assignment("CLEAR_ALL");
}
function pts_clear_assignment($assignment)
{
	pts_assignment("CLEAR", $assignment);
}
function pts_unique_runtime_identifier()
{
	if(pts_is_assignment("THIS_OPTION_IDENTIFIER"))
	{
		$identifier = pts_read_assignment("THIS_OPTION_IDENTIFIER");
	}
	else
	{
		$identifier = PTS_INIT_TIME;
	}

	return $identifier;
}
function pts_time_elapsed()
{
	if(pts_is_assignment("START_TIME"))
	{
		$start_time = pts_read_assignment("START_TIME");
	}
	else
	{
		$start_time = PTS_INIT_TIME;
	}

	return (time() - $start_time);
}

?>
