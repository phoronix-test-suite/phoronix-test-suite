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

class pts_result_file_merge_test
{
	private $name;
	private $version;
	private $attributes;
	private $scale;
	private $test_name;
	private $arguments;
	private $proportion;
	private $format;

	private $result_buffer;

	public function __construct($name, $version, $attributes, $scale, $test_name, $arguments, $proportion, $format, $result_buffer)
	{
		$this->name = $name;
		$this->version = $version;
		$this->attributes = $attributes;
		$this->scale = $scale;
		$this->test_name = $test_name;
		$this->arguments = $arguments;
		$this->proportion = $proportion;
		$this->format = $format;

		$this->result_buffer = $result_buffer;
	}
	public function add_result_to_buffer($identifier, $value, $raw_value)
	{
		$this->result_buffer->add_test_result($identifier, $value, $raw_value);
	}
	public function get_result_buffer()
	{
		return $this->result_buffer;
	}
	public function flush_result_buffer()
	{
		$this->result_buffer = new pts_test_result_buffer();
	}
	public function get_comparison_hash($show_version_and_attributes = true)
	{
		return $show_version_and_attributes ? pts_test_comparison_hash($this->get_test_name(), $this->get_arguments(), $this->get_attributes(), $this->get_version()) : pts_test_comparison_hash($this->get_test_name(), $this->get_arguments());
	}
	public function get_name()
	{
		return $this->name;
	}
	public function get_version()
	{
		return $this->version;
	}
	public function get_attributes()
	{
		return $this->attributes;
	}
	public function get_scale()
	{
		return $this->scale;
	}
	public function get_scale_formatted()
	{
		return trim(pts_first_string_in_string($this->get_scale(), '|'));
	}
	public function get_scale_special()
	{
		$scale_parts = explode("|", $this->scale);

		return count($scale_parts) == 2 ? trim($scale_parts[1]) : array();
	}
	public function get_test_name()
	{
		return $this->test_name;
	}
	public function get_arguments()
	{
		return $this->arguments;
	}
	public function get_proportion()
	{
		return $this->proportion;
	}
	public function get_format()
	{
		return $this->format;
	}
	public function set_format($format)
	{
		$this->format = $format;
	}
	public function set_scale($scale)
	{
		$this->scale = $scale;
	}
	public function set_attributes($attributes)
	{
		$this->attributes = $attributes;
	}
}

?>
