<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel

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

class pts_user_error
{
	private $option_command;
	private $error_string;
	private $error_time;

	public function __construct($error_string)
	{
		// This object is incredibly simple right now
		$this->option_command = pts_read_assignment("COMMAND");
		$this->error_string = $error_string;
		$this->error_time = date("Y-m-d H:i:s");
	}
	public function get_error_string()
	{
		return $this->error_string;
	}
	public function get_error_time()
	{
		return $this->error_time;
	}
	public function get_option_command()
	{
		return $this->option_command;
	}
}

?>
