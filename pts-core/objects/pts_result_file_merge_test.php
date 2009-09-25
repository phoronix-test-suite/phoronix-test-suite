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
	var $name;
	var $version;
	var $attributes;
	var $scale;
	var $test_name;
	var $arguments;
	var $proportion;
	var $format;

	var $identifiers;
	var $values;
	var $raw_values;

	public function __construct($name, $version, $attributes, $scale, $test_name, $arguments, $proportion, $format, $result_identifiers, $result_values, $result_raw_values)
	{
		$this->name = $name;
		$this->version = $version;
		$this->attributes = $attributes;
		$this->scale = $scale;
		$this->test_name = $test_name;
		$this->arguments = $arguments;
		$this->proportion = $proportion;
		$this->format = $format;

		$this->identifiers = $result_identifiers;
		$this->values = $result_values;
		$this->raw_values = $result_raw_values;
	}
	public function add_identifier($identifier)
	{
		array_push($this->identifiers, $identifier);
	}
	public function add_value($value)
	{
		array_push($this->values, $value);
	}
	public function add_raw_value($raw_value)
	{
		array_push($this->raw_values, $raw_value);
	}
	public function flush_result_data()
	{
		$this->identifiers = array();
		$this->values = array();
		$this->raw_values = array();
	}
	public function get_comparison_hash($show_version_and_attributes = true)
	{
		return $show_version_and_attributes ? pts_test_comparison_hash($this->get_test_name(), $this->get_arguments(), $this->get_attributes(), $this->get_version()) : pts_test_comparison_hash($test_identifier, $arguments);
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
		return trim(array_shift(explode("|", $this->get_scale())));
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
	public function get_identifiers()
	{
		return $this->identifiers;
	}
	public function get_values()
	{
		return $this->values;
	}
	public function get_raw_values()
	{
		return $this->raw_values;
	}
}

?>
