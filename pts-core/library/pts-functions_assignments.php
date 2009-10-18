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

function pts_set_assignment_once($assignment, $value)
{
	return pts_assignment_manager::set_once($assignment, $value);
}
function pts_set_assignment($assignment, $value)
{
	return pts_assignment_manager::set($assignment, $value);
}
function pts_read_assignment($assignment)
{
	return pts_assignment_manager::read($assignment);
}
function pts_is_assignment($assignment)
{
	return pts_assignment_manager::is_set($assignment);
}
function pts_clear_assignment($assignment)
{
	pts_assignment_manager::clear($assignment);
}
function pts_set_assignment_next($assignment, &$value)
{
	pts_run_option_manager::add_assignment_to_next_run_option($assignment, $value);
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
