<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2010, Phoronix Media
	Copyright (C) 2009 - 2010, Michael Larabel

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

class pts_command_execution_manager
{
	private static $to_run = array();

	public static function add_to_queue($command, $pass_args = null, $set_assignments = "")
	{
		return array_push(self::$to_run, new pts_command_run($command, $pass_args, $set_assignments));
	}
	public static function pull_next_in_queue()
	{
		return array_shift(self::$to_run);
	}
	public static function add_assignment_to_next_in_queue($assignment, $value)
	{
		if(($next_option = array_shift(self::$to_run)) != null)
		{
			$next_option->add_preset_assignment($assignment, $value);
			array_unshift(self::$to_run, $next_option);
		}
	}
}

?>
