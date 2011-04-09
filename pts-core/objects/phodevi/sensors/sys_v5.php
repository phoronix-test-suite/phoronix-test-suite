<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2011, Phoronix Media
	Copyright (C) 2009 - 2011, Michael Larabel

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

class sys_v5 implements phodevi_sensor
{
	public static function get_type()
	{
		return 'sys';
	}
	public static function get_sensor()
	{
		return 'v5-voltage';
	}
	public static function get_unit()
	{
		return 'Volts';
	}
	public static function support_check()
	{
		$test = self::read_sensor();
		return is_numeric($test) && $test != -1;
	}
	public static function read_sensor()
	{
		if(phodevi::is_linux())
		{
			$sensor = phodevi_linux_parser::read_sensors(array('V5', '+5V'));
		}
		else
		{
			$sensor = -1;
		}

		return $sensor;
	}
}

?>
