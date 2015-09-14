<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2010, Phoronix Media
	Copyright (C) 2009 - 2010, Michael Larabel

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

abstract class phodevi_sensor
{
	const SENSOR_TYPE = '';			//eg. cpu
	const SENSOR_SENSES = '';		//eg. power-usage
	const SENSOR_UNIT = '';			//eg. miliwatts
	const PRIMARY_PARAM_NAME = '';	//eg. cpu_number

	protected $instance_number;

	function __construct($instance, $parameter_array)
	{
		$this->instance_number = intval($instance);
	}

	public static function get_type()
	{
		return static::SENSOR_TYPE;
	}

	public static function get_sensor()
	{
		return static::SENSOR_SENSES;
	}

	public static function get_unit()
	{
		return static::SENSOR_UNIT;
	}

	public static function get_primary_parameter_name()
	{
		return static::PRIMARY_PARAM_NAME;
	}

	public function get_instance()
	{
		return $this->instance_number;
	}

	public function get_readable_params()
	{
		return null;
	}

	public static function parameter_check($parameter_array) // check if passed parameters are correct
	{
		return true;
	}

	public static function get_supported_devices()
	{
		return NULL;
	}

	abstract public function support_check();	   // for checking if sensor is supported on the current platform

	abstract public function read_sensor();
}

?>
