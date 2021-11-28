<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2016, Phoronix Media
	Copyright (C) 2009 - 2016, Michael Larabel

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
	const INSTANT_MEASUREMENT = true;

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

	public function is_instant()
	{
		return static::INSTANT_MEASUREMENT;
	}

	public function get_instance()
	{
		return $this->instance_number;
	}

	/*
	 * Sensor-specific functions
	 */

	// Return human-readable string containing name of the device monitored
	// by the sensor instance. If your sensor takes no parameters, you can
	// leave this function as it is.
	public function get_readable_device_name()
	{
		return null;
	}

	// Check if passed parameters are correct. You probably want to
	// override this function if your sensor supports parametrization.
	public static function parameter_check($parameter_array)
	{
		return true;
	}

	// Return array containing all the device name strings supported by the sensor.
	// They can be passed in MONITOR environment variable to create object
	// responsible for monitoring specific device. You probably want to
	// override this function if your sensor supports parametrization.
	// It should return NULL on platforms where parameters are unsupported.
	public static function get_supported_devices()
	{
		return NULL;
	}

	// Check if sensor is supported on the current platform. In most cases you
	// do not need to override this one.
	public function support_check()
	{
		$test = $this->read_sensor();
		return is_numeric($test) && $test != -1;
	}

	// Read the sensor value and return it.
	abstract public function read_sensor();
}

?>
