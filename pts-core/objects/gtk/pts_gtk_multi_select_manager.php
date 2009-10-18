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

class pts_gtk_multi_select_manager
{
	private static $selects = array();

	public static function set_check_select($select, $name)
	{
		self::$selects[$name] = $select->get_active();
	}
	public static function set_radio_select($radio, $name)
	{
		self::$selects[$name] = $radio->child->get_label();
	}
	public static function get_select($radio_name)
	{
		return isset(self::$selects[$radio_name]) ? self::$selects[$radio_name] : -1;
	}
}

?>
