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

class pts_input_type_restrictions
{
	private $name;
	private $type;
	private $min_length;
	private $max_length;
	private $min_value;
	private $max_value;
	private $enums;
	private $required;
	private $multi_enum_select;

	public function __construct($name = '', $type = '', $min_length = '', $max_length = '', $min_value = -1, $max_value = '', $enums = '')
	{
		$this->name = $name;
		$this->type = $type;
		$this->min_length = $min_length;
		$this->max_length = $max_length;
		$this->min_value = $min_value;
		$this->max_value = $max_value;
		$this->enums = $enums;
		$this->multi_enum_select = false;
	}
	public function get_name()
	{
		return $this->name;
	}
	public function get_type()
	{
		return $this->type;
	}
	public function get_min_length()
	{
		return $this->min_length;
	}
	public function get_max_length()
	{
		return $this->max_length;
	}
	public function get_min_value()
	{
		return $this->min_value;
	}
	public function get_max_value()
	{
		return $this->max_value;
	}
	public function get_enums()
	{
		return $this->enums;
	}
	public function is_enums_empty()
	{
		return empty($this->enums);
	}
	public function set_enums($enums)
	{
		$this->enums = $enums;
	}
	public function set_required($is_required)
	{
		$this->required = $is_required;
	}
	public function is_required()
	{
		return $this->required;
	}
	public function set_multi_enum_select($bo = false)
	{
		$this->multi_enum_select = $bo;
	}
	public function multi_enum_select()
	{
		return $this->multi_enum_select;
	}
	public function is_valid($input)
	{

	}
	public function cli_input()
	{

	}
	public function html_input()
	{

	}
}
?>
