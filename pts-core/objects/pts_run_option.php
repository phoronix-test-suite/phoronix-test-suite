<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009 Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel

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

class pts_run_option
{
	var $command;
	var $arguments;
	var $preset_assignments;

	public function __construct($command, $pass_args = null, $set_assignments = "")
	{
		$command = strtolower($command);

		if(!empty($pass_args) && !is_array($pass_args))
		{
			$pass_args = array($pass_args);
		}

		if(!is_array($set_assignments))
		{
			$set_assignments = array();
		}


		$this->command = $command;
		$this->arguments = $pass_args;
		$this->preset_assignments = $set_assignments;
	}
	public function get_command()
	{
		return $this->command;
	}
	public function get_arguments()
	{
		return $this->arguments;
	}
	public function get_preset_assignments()
	{
		return $this->preset_assignments;
	}
	public function add_preset_assignment($name, $value)
	{
		$this->preset_assignments[$name] = $value;
	}
}

?>
