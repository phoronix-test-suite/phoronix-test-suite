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

class pts_table_value
{
	private $value_string;
	private $std_percent;
	private $std_error;
	private $delta;
	private $highlight;

	public function __construct($value_string = 0, $std_percent = 0, $std_error = 0, $delta = 0, $highlight = false)
	{
		$this->value_string = $value_string;
		$this->std_percent = $std_percent;
		$this->std_error = $std_error;
		$this->delta = $delta;
		$this->highlight = $highlight;
	}
	public function __toString()
	{
		return $this->value_string;
	}
	public function get_value()
	{
		return $this->value_string;
	}
	public function get_standard_deviation_percent()
	{
		return $this->std_percent;
	}
	public function get_standard_error()
	{
		return $this->std_error;
	}
	public function get_delta()
	{
		return $this->delta;
	}
	public function get_highlight()
	{
		return $this->highlight;
	}
	public function set_value_string($value)
	{
		$this->value_string = $value;
	}
	public function set_standard_deviation_percent($value)
	{
		$this->std_percent = $value;
	}
	public function set_standard_error($value)
	{
		$this->std_error = $value;
	}
	public function set_delta($value)
	{
		$this->delta = $value;
	}
	public function set_highlight($value)
	{
		$this->highlight = $value;
	}
}

?>
