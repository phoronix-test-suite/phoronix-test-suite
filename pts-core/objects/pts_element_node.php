<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2018, Phoronix Media
	Copyright (C) 2018, Michael Larabel

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
	protected $documentation;

	public function __construct($name, $value = null, $input_type_restrictions = null, $api = null, $documentation = null)
	{
		$this->name = $name;
		$this->value = $value;
		$this->input_type_restrictions = $input_type_restrictions;
		$this->api = $api;
		$this->documentation = $documentation;
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
	public function get_documentation()
	{
		return $this->documentation;
	}
}
?>
