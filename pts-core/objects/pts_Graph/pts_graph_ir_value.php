<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010, Phoronix Media
	Copyright (C) 2010, Michael Larabel

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

class pts_graph_ir_value
{
	private $value_string;
	private $attributes;

	public function __construct($value_string = 0, $attributes = array())
	{
		$this->value_string = $value_string;
		$this->attributes = $attributes;
	}
	public function __toString()
	{
		return "$this->value_string";
	}
	public function set_attribute($attribute, $value)
	{
		$this->attributes[$attribute] = $value;
	}
	public function get_attribute($attribute)
	{
		return isset($this->attributes[$attribute]) ? $this->attributes[$attribute] : null;
	}
	public function get_value()
	{
		return $this->value_string;
	}
}

?>
