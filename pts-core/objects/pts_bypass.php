<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2011, Phoronix Media
	Copyright (C) 2011, Michael Larabel

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

class pts_bypass
{
	private static $os_identifier_sha1 = null;
	private static $flags = 0;

	// Flags
	private const is_live_cd = (1 << 1);
	private const no_network_communication = (1 << 2);
	private const no_openbenchmarking_reporting = (1 << 3);

	public static function init()
	{
		self::$os_identifier_sha1 = sha1(phodevi::read_property('system', 'vendor-identifier'));

		switch(self::$os_identifier_sha1)
		{
			case 'b28d6a7148b34595c5b397dfcf5b12ac7932b3d': // Moscow 2011-04 client
				self::$flags = self::is_live_cd | self::no_network_communication | self::no_openbenchmarking_reporting;
				break;
		}
	}
	public static function is_live_cd()
	{
		return self::$flags & self::is_live_cd;
	}
	public static function no_network_communication()
	{
		return self::$flags & self::no_network_communication;
	}
	public static function no_openbenchmarking_reporting()
	{
		return self::$flags & self::no_openbenchmarking_reporting;
	}


}

?>
