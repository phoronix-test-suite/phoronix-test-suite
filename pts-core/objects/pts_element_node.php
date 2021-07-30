<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2018 - 2021, Phoronix Media
	Copyright (C) 2018 - 2021, Michael Larabel

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

class pts_element_node
{
	protected $name;
	protected $value;
	protected $input_type_restrictions;
	protected $api;
	protected $api_setter;
	protected $documentation;
	protected $default_value;
	protected $flags;
	protected $path;

	public function __construct($name, $value = null, $input_type_restrictions = null, $api = null, $documentation = null, $api_setter = null, $default_value = null, $flags = null, $path = null)
	{
		$this->name = $name;
		$this->value = $value;
		$this->input_type_restrictions = $input_type_restrictions;
		$this->api = $api;
		$this->documentation = $documentation;
		$this->api_setter = $api_setter;
		$this->default_value = $default_value;
		$this->flags = $flags;
		$this->path = $path;
	}
	public function get_name()
	{
		return $this->name;
	}
	public function get_value()
	{
		return $this->value;
	}
	public function get_input_type_restrictions()
	{
		return $this->input_type_restrictions;
	}
	public function get_api()
	{
		return $this->api;
	}
	public function get_api_setter()
	{
		return $this->api_setter;
	}
	public function get_documentation()
	{
		return $this->documentation;
	}
	public function get_default_value()
	{
		return $this->default_value;
	}
	public function get_flags()
	{
		return $this->flags;
	}
	public function get_flags_array()
	{
		return $this->flags != null ? explode(' ', $this->flags) : array();
	}
	public function set_path($path)
	{
		$this->path = $path;
	}
	public function get_path()
	{
		return $this->path;
	}
}
?>
