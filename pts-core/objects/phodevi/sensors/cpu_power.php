<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2017, Phoronix Media
	Copyright (C) 2009 - 2017, Michael Larabel

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

class cpu_power extends phodevi_sensor
{
	const SENSOR_TYPE = 'cpu';
	const SENSOR_SENSES = 'power';

	public function read_sensor()
	{
		if(phodevi::is_linux())
		{
			return $this->cpu_power_linux();
		}
		return -1;		// TODO make -1 a named constant
	}
	public static function get_unit()
	{
		$unit = null;

		if(is_readable('/sys/bus/i2c/drivers/ina3221x/0-0041/iio:device1/in_power1_input'))
		{
			$unit = 'Milliwatts';
		}
		else
		{
			$unit = 'Watts';
		}

		return $unit;
	}

	private function cpu_power_linux()
	{
		$cpu_power = -1;

		// Try hwmon interface for AMD 15h (Bulldozer FX CPUs) where this support was introduced for AMD CPUs and exposed by the fam15h_power hwmon driver
		// The fam15h_power driver doesn't expose the power consumption on a per-core/per-package basis but only an average
		$hwmon_watts = phodevi_linux_parser::read_sysfs_node('/sys/class/hwmon/hwmon*/device/power1_input', 'POSITIVE_NUMERIC', array('name' => 'fam15h_power'));

		if($hwmon_watts != -1)
		{
			if($hwmon_watts > 1000000)
			{
				// convert to Watts
				$hwmon_watts = $hwmon_watts / 1000000;
			}

			$cpu_power = pts_math::set_precision($hwmon_watts, 2);
		}
		else if(is_readable('/sys/bus/i2c/drivers/ina3221x/0-0041/iio:device1/in_power1_input'))
		{
			$in_power1_input = pts_file_io::file_get_contents('/sys/bus/i2c/drivers/ina3221x/0-0041/iio:device1/in_power1_input');
			if(is_numeric($in_power1_input) && $in_power1_input > 1)
			{
				$cpu_power = $in_power1_input;
			}
		}

		return $cpu_power;
	}
}

?>
