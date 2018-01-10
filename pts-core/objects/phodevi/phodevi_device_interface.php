<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2018, Phoronix Media
	Copyright (C) 2009 - 2018, Michael Larabel
	phodevi_device_interface: The abstract interface for the PTS Device Interface

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

abstract class phodevi_device_interface
{
	public static function properties()
	{
		return array();
	}
	// DROP BELOW XXX //
	public static function read_property($identifier)
	{
		return false;
	}

	public static function read_sensor($identifier)
	{
		return false;
	}
	public static function available_sensors()
	{
		return array();
	}
	public static function properties_for_notes()
	{
		return array();
	}
	public static function set_property($identifier, $value)
	{
		return false;
	}
}

?>
