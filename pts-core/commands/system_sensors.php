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

class system_sensors implements pts_option_interface
{
	const doc_section = 'System';
	const doc_description = 'Display the installed system hardware and software sensors in real-time as detected by the Phoronix Test Suite Phodevi Library.';

	public static function run($r)
	{
		pts_client::$display->generic_heading('Supported Sensors');
		foreach(phodevi::supported_sensors() as $sensor)
		{
			$supported_devices = call_user_func(array($sensor[2], 'get_supported_devices'));

			if ($supported_devices === NULL)
			{
				self::print_sensor ($sensor, NULL);
				continue;
			}

			foreach ($supported_devices as $device)
			{
				self::print_sensor ($sensor, $device);
			}
		}

		pts_client::$display->generic_heading('Unsupported Sensors');
		foreach(phodevi::unsupported_sensors() as $sensor)
		{
			echo '- ' . phodevi::sensor_name($sensor) . PHP_EOL;
		}
		echo PHP_EOL;
	}

	private static function print_sensor($sensor, $device)
	{
		if ($device === NULL)
		{
			$parameters = NULL;
		}
		else
		{
			$parameters = array();
			$parameters[call_user_func(array($sensor[2], 'get_primary_parameter_name'))] = $device;
		}
		$sensor_object = new $sensor[2](0, $parameters);
		echo phodevi::sensor_object_name($sensor_object) . ': ' . phodevi::read_sensor($sensor_object) . ' ' . phodevi::read_sensor_object_unit($sensor_object) . PHP_EOL;
	}
}

?>
