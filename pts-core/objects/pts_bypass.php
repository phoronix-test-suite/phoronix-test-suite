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

pts_bypass::init();

class pts_bypass
{
	private static $os_identifier_sha1 = null;
	private static $flags = 0;

	// Flags
	private static $is_live_cd;
	private static $no_network_communication;
	private static $no_openbenchmarking_reporting;
	private static $user_agreement_skip;

	public static function init()
	{
		self::$os_identifier_sha1 = sha1(phodevi::read_property('system', 'vendor-identifier'));

		self::$is_live_cd = (1 << 1);
		self::$no_network_communication = (1 << 2);
		self::$no_openbenchmarking_reporting = (1 << 3);
		self::$user_agreement_skip = (1 << 4);

		switch(self::$os_identifier_sha1)
		{
			case 'b28d6a7148b34595c5b397dfcf5b12ac7932b3dc': // Moscow 2011-04 client
				self::$flags = self::$is_live_cd | self::$no_network_communication | self::$no_openbenchmarking_reporting | self::$user_agreement_skip;
				break;
		}
	}
	public static function os_identifier_hash()
	{
		return self::$os_identifier_sha1;
	}
	public static function is_live_cd()
	{
		return self::$flags & self::$is_live_cd;
	}
	public static function no_network_communication()
	{
		return self::$flags & self::$no_network_communication;
	}
	public static function no_openbenchmarking_reporting()
	{
		return self::$flags & self::$no_openbenchmarking_reporting;
	}
	public static function user_agreement_skip()
	{
		return self::$flags & self::$user_agreement_skip;
	}
}

?>
