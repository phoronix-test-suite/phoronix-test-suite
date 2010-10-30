<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel

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

class pts_command_run
{
	private $command;
	private $arguments;

	public function __construct($command, $pass_args = null)
	{
		$command = strtolower($command);

		if(!empty($pass_args) && !is_array($pass_args))
		{
			$pass_args = array($pass_args);
		}

		$this->command = $command;
		$this->arguments = $pass_args;
	}
	public function get_command()
	{
		return $this->command;
	}
	public function get_command_arguments()
	{
		return $this->arguments;
	}
}

?>
