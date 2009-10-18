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

class pts_assignment_manager
{
	static $assignments = array();

	public static function set($assignment, $value)
	{
		self::$assignments[$assignment] = $value;
	}
	public static function set_once($assignment, $value)
	{
		return !self::is_set($assignment) ? self::set($assignment, $value): false;
	}
	public static function read($assignment)
	{
		return self::is_set($assignment) ? self::$assignments[$assignment] : false;
	}
	public static function is_set($assignment)
	{
		return isset(self::$assignments[$assignment]);
	}
	public static function clear($assignment)
	{
		if(self::is_set($assignment))
		{
			unset(self::$assignments[$assignment]);
		}
	}
	public static function clear_all()
	{
		self::$assignments[$assignment] = array();
	}
}

?>
