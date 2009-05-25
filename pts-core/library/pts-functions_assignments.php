<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
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
	return !pts_is_assignment($assignment) && pts_set_assignment($assignment, $value);
}
function pts_set_assignment($assignment, $value)
{
	$assignment = pts_to_array($assignment);

	foreach($assignment as $this_assignment)
	{
		pts_assignment("SET", $this_assignment, $value);
	}

	return true;
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
function pts_set_assignment_next($assignment, $value)
{
	$options = pts_run_option_static_array();

	if(count($options) > 0)
	{
		$next_option = array_shift($options);
		$next_option->add_preset_assignment($assignment, $value);
		array_unshift($options, $next_option);

		pts_run_option_static_array($options);
	}
}
function pts_unique_runtime_identifier()
{
	return ($id = pts_read_assignment("THIS_OPTION_IDENTIFIER")) != false ? $id : PTS_INIT_TIME;
}
function pts_time_elapsed()
{
	return time() - (($time = pts_read_assignment("START_TIME")) != false ? $time : PTS_INIT_TIME);
}

?>
