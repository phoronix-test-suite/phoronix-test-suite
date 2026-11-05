<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2026, Phoronix Media
	Copyright (C) 2026, Michael Larabel

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

class powenetics_power extends phodevi_sensor
{
	const SENSOR_TYPE = 'powenetics';
	const SENSOR_SENSES = 'power';
	const SENSOR_UNIT = 'Watts';
	const INSTANT_MEASUREMENT = true;

	// Shared handle + frame cache so all instances polled within one monitoring
	// tick decode a single frame rather than each opening the (exclusive) port.
	private static $shared_handle = null;
	private static $cached_frame = null;
	private static $cached_frame_time = 0;
	const FRAME_CACHE_MS = 100;

	private $rail_to_monitor = 'total';

	function __construct($instance, $parameter)
	{
		parent::__construct($instance, $parameter);

		if($parameter !== NULL)
		{
			$this->rail_to_monitor = $parameter;
		}
	}
	public static function get_supported_devices()
	{
		if(getenv('POWENETICS') == false)
		{
			// Hidden unless a Powenetics device/replay source is configured
			return NULL;
		}

		return pts_powenetics::device_tokens();
	}
	public static function parameter_check($parameter)
	{
		if($parameter === null || ($devices = self::get_supported_devices()) === NULL || in_array($parameter, $devices))
		{
			return true;
		}

		return false;
	}
	public function get_readable_device_name()
	{
		if(self::get_supported_devices() == NULL)
		{
			return NULL;
		}

		return pts_powenetics::readable_token_name($this->rail_to_monitor);
	}
	public function support_check()
	{
		$source = getenv('POWENETICS');
		if(empty($source))
		{
			return false;
		}

		$device = pts_powenetics::find_device($source);
		if(empty($device))
		{
			return false;
		}

		return pts_powenetics::probe($device);
	}
	public function read_sensor()
	{
		$frame = self::current_frame();

		if($frame === false || $frame === null)
		{
			return -1;
		}

		$watts = pts_powenetics::rail_watts($frame, $this->rail_to_monitor);

		return $watts == -1 ? -1 : pts_math::set_precision($watts, 2);
	}
	private static function current_frame()
	{
		$now = round(microtime(true) * 1000);

		if(self::$cached_frame !== null && ($now - self::$cached_frame_time) < self::FRAME_CACHE_MS)
		{
			return self::$cached_frame;
		}

		if(self::$shared_handle === null)
		{
			$device = pts_powenetics::find_device(getenv('POWENETICS'));
			if(empty($device))
			{
				return false;
			}

			$handle = new pts_powenetics($device);
			if(!$handle->open())
			{
				return false;
			}
			self::$shared_handle = $handle;
		}

		$frame = self::$shared_handle->read_frame();
		if($frame !== false)
		{
			self::$cached_frame = $frame;
			self::$cached_frame_time = $now;
		}

		return $frame;
	}
}

?>
