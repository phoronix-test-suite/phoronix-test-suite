<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts_test_option: An object used for storing a test option and its possible values

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

class pts_test_option
{
	var $identifier = "";
	var $option_name = "";
	var $prefix = "";
	var $postfix = "";
	var $default_entry = -1;
	var $options = array();

	public function __construct($identifier, $option)
	{
		$this->identifier = $identifier;
		$this->option_name = $option;
	}
	public function set_option_prefix($prefix)
	{
		$this->prefix = $prefix;
	}
	public function set_option_postfix($postfix)
	{
		$this->postfix = $postfix;
	}
	public function set_option_default($default_node)
	{
		$default_node--;
		if(isset($this->options[$default_node]))
		{
			$this->default_entry = $default_node;
		}
	}
	public function get_identifier()
	{
		return $this->identifier;
	}
	public function get_name()
	{
		return $this->option_name;
	}
	public function get_option_prefix()
	{
		return $this->prefix;
	}
	public function get_option_postfix()
	{
		return $this->postfix;
	}
	public function get_option_default()
	{
		$default = $this->default_entry;

		if($default == -1)
		{
			$default = $this->option_count() - 1;
		}

		return $default;
	}
	public function add_option($name, $value)
	{
		array_push($this->options, array($name, $value));
	}
	public function get_all_option_names()
	{
		$names = array();

		for($i = 0; $i < $this->option_count(); $i++)
			array_push($names, $this->get_option_name($i));

		return $names;
	}
	public function get_option_name($index)
	{
		return $this->options[$index][0];
	}
	public function get_option_value($index)
	{
		return $this->options[$index][1];
	}
	public function option_count()
	{
		return count($this->options);
	}
}

?>
