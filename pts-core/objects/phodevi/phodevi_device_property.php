<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel
	phodevi_device_property.php: Device property object

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

class phodevi_device_property
{
	private $object_function;
	private $cache_code;

	public function __construct($function, $cache_code = false)
	{
		$this->object_function = $function;
		$this->cache_code = $cache_code;
	}
	public function get_device_function()
	{
		return $this->object_function;
	}
	public function cache_code()
	{
		return $this->cache_code;
	}
}

?>
